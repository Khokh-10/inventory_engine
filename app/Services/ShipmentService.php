<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ShippingProviderInterface;
use App\Enums\ProviderResponse;
use App\Enums\ReservationStatus;
use App\Enums\ShipmentStatus;
use App\Exceptions\InvalidReservationStateException;
use App\Exceptions\InvalidShipmentStateException;
use App\Models\Reservation;
use App\Models\Shipment;
use App\Models\ShipmentHistory;
use App\Models\ShipmentItem;
use App\Models\ShipmentWebhook;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShipmentService
{
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    /**
     * Create a shipment from a consumed reservation.
     */
    public function createShipment(Reservation $reservation): Shipment
    {
        return DB::transaction(function () use ($reservation): Shipment {
            if ($reservation->status !== ReservationStatus::CONSUMED) {
                throw new InvalidReservationStateException('Reservation must be consumed before creating a shipment.');
            }

            $shipment = Shipment::query()->create([
                'order_id' => $reservation->order_id,
                'tracking_number' => null,
                'status' => ShipmentStatus::PENDING,
            ]);

            foreach ($reservation->items as $item) {
                ShipmentItem::query()->create([
                    'shipment_id' => $shipment->getKey(),
                    'reservation_item_id' => $item->getKey(),
                    'quantity' => $item->quantity,
                    'shipped_quantity' => 0,
                    'remaining_quantity' => $item->quantity,
                ]);
            }

            $this->recordHistory($shipment, 'Shipment Created', [
                'order_id' => $reservation->order_id,
                'reservation_id' => $reservation->getKey(),
            ]);

            return $shipment;
        });
    }

    /**
     * Process a shipment using a shipping provider abstraction.
     */
    public function processShipment(int $shipmentId, ShippingProviderInterface $provider): Shipment
    {
        return DB::transaction(function () use ($shipmentId, $provider): Shipment {
            $shipment = $this->loadShipment($shipmentId, forUpdate: true);

            if ($shipment->status === ShipmentStatus::DELIVERED) {
                return $shipment;
            }

            $this->validateShipmentStatus($shipment, [ShipmentStatus::PENDING, ShipmentStatus::FAILED]);

            $this->recordHistory($shipment, 'Processing Started', [
                'provider' => $provider->name(),
            ], [
                'provider_name' => $provider->name(),
            ]);

            $response = $provider->ship($shipment);
            $trackingNumber = $provider->getTrackingNumber($shipment);
            $providerReference = $provider->getProviderReference($shipment);

            $shipment->forceFill([
                'tracking_number' => $trackingNumber ?? $shipment->tracking_number,
                'provider_reference' => $providerReference,
                'provider_name' => $provider->name(),
                'provider_response' => $response->value,
            ])->save();

            return match ($response) {
                ProviderResponse::SUCCESS => $this->updateShipmentStatus(
                    shipment: $shipment,
                    status: ShipmentStatus::IN_TRANSIT,
                    reason: 'Shipment processed successfully.',
                    event: 'In Transit',
                    metadata: ['provider_response' => $response->value],
                ),
                ProviderResponse::FAILED => $this->updateShipmentStatus(
                    shipment: $shipment,
                    status: ShipmentStatus::FAILED,
                    reason: 'Shipment processing failed.',
                    event: 'Processing Failed',
                    metadata: ['provider_response' => $response->value],
                ),
                ProviderResponse::TIMEOUT => $this->updateShipmentStatus(
                    shipment: $shipment,
                    status: ShipmentStatus::PENDING,
                    reason: 'Shipment processing timed out.',
                    event: 'Processing Started',
                    metadata: ['provider_response' => $response->value],
                ),
                ProviderResponse::PARTIAL_SUCCESS => $this->partialShipment(
                    shipment: $shipment,
                    reason: 'Shipment partially processed.',
                    provider: $provider,
                ),
                ProviderResponse::DUPLICATE => $this->updateShipmentStatus(
                    shipment: $shipment,
                    status: ShipmentStatus::IN_TRANSIT,
                    reason: 'Duplicate shipment processing ignored.',
                    event: 'In Transit',
                    metadata: ['provider_response' => $response->value],
                ),
            };
        });
    }

    /**
     * Confirm a shipment as delivered and update inventory.
     */
    public function confirmShipment(int $shipmentId): Shipment
    {
        return DB::transaction(function () use ($shipmentId): Shipment {
            $shipment = $this->loadShipment($shipmentId, forUpdate: true);

            if ($shipment->status === ShipmentStatus::DELIVERED) {
                return $shipment;
            }

            $this->validateShipmentStatus($shipment, [ShipmentStatus::IN_TRANSIT, ShipmentStatus::PARTIALLY_DELIVERED]);

            foreach ($this->loadShipmentItems($shipment) as $item) {
                $quantityToShip = max(0, $item->quantity - $item->shipped_quantity);
                if ($quantityToShip <= 0) {
                    continue;
                }

                $inventoryId = $item->reservationItem?->inventory_id;
                if ($inventoryId === null) {
                    continue;
                }

                $this->inventoryService->ship(
                    inventoryId: $inventoryId,
                    quantity: $quantityToShip,
                    referenceType: Shipment::class,
                    referenceId: (string) $shipment->getKey(),
                    performedBy: 'system',
                    notes: 'Shipment confirmed',
                );

                $item->shipped_quantity += $quantityToShip;
                $item->remaining_quantity = max(0, $item->quantity - $item->shipped_quantity);
                $item->save();
            }

            $shipment->status = ShipmentStatus::DELIVERED;
            $shipment->delivered_at = now();
            $shipment->failure_reason = null;
            $shipment->save();

            $this->recordHistory($shipment, 'Delivered', [
                'delivered_at' => $shipment->delivered_at->toIso8601String(),
            ]);

            return $shipment;
        });
    }

    /**
     * Mark shipment as failed.
     */
    public function failShipment(int $shipmentId, string $reason): Shipment
    {
        return DB::transaction(function () use ($shipmentId, $reason): Shipment {
            $shipment = $this->loadShipment($shipmentId, forUpdate: true);
            $shipment->status = ShipmentStatus::FAILED;
            $shipment->failure_reason = $reason;
            $shipment->save();

            $this->recordHistory($shipment, 'Processing Failed', [
                'reason' => $reason,
            ]);

            return $shipment;
        });
    }

    /**
     * Retry a failed or pending shipment.
     */
    public function retryShipment(int $shipmentId): Shipment
    {
        return DB::transaction(function () use ($shipmentId): Shipment {
            $shipment = $this->loadShipment($shipmentId, forUpdate: true);

            if ($shipment->status === ShipmentStatus::DELIVERED) {
                return $shipment;
            }

            $retryCount = (int) ($shipment->retry_count ?? 0);
            if ($retryCount >= self::MAX_RETRY_ATTEMPTS) {
                $shipment->status = ShipmentStatus::FAILED;
                $shipment->failure_reason = 'Maximum retry attempts reached.';
                $shipment->save();

                $this->recordHistory($shipment, 'Processing Failed', [
                    'reason' => 'Maximum retry attempts reached.',
                    'retry_count' => $retryCount,
                ]);

                return $shipment;
            }

            $shipment->retry_count = $retryCount + 1;
            $shipment->last_retry_at = now();
            $shipment->status = ShipmentStatus::PENDING;
            $shipment->failure_reason = null;
            $shipment->save();

            $this->recordHistory($shipment, 'Retry', [
                'retry_count' => $shipment->retry_count,
            ], [
                'retry_count' => $shipment->retry_count,
                'last_retry_at' => $shipment->last_retry_at?->toIso8601String(),
            ]);

            return $shipment;
        });
    }

    /**
     * Handle an incoming shipment webhook idempotently.
     */
    public function handleWebhook(int $shipmentId, string $eventId, array $payload, ?string $signature = null): ShipmentWebhook
    {
        return DB::transaction(function () use ($shipmentId, $eventId, $payload, $signature): ShipmentWebhook {
            if ($this->isDuplicateWebhook($shipmentId, $eventId)) {
                return ShipmentWebhook::query()->where('shipment_id', $shipmentId)->where('event_id', $eventId)->firstOrFail();
            }

            $this->verifyWebhookSignature($signature, $payload);

            $webhook = ShipmentWebhook::query()->create([
                'shipment_id' => $shipmentId,
                'event_id' => $eventId,
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            $webhook->forceFill([
                'provider' => $payload['provider'] ?? null,
                'event_type' => $payload['event'] ?? $payload['event_type'] ?? null,
                'status' => $payload['status'] ?? null,
                'processed_at' => now(),
            ])->save();

            if (($payload['event'] ?? '') === 'shipment.delivered') {
                $this->confirmShipment($shipmentId);
            }

            return $webhook;
        });
    }

    /**
     * Apply a partial shipment outcome.
     */
    public function partialShipment(Shipment $shipment, string $reason, ?ShippingProviderInterface $provider = null): Shipment
    {
        $shipment->status = ShipmentStatus::PARTIALLY_DELIVERED;
        $shipment->failure_reason = $reason;
        $shipment->save();

        $partialQuantity = $provider?->getPartialQuantity($shipment) ?? 0;

        foreach ($this->loadShipmentItems($shipment) as $item) {
            $quantityToShip = min($item->remaining_quantity, max(0, $partialQuantity));
            if ($quantityToShip <= 0) {
                continue;
            }

            $inventoryId = $item->reservationItem?->inventory_id;
            if ($inventoryId === null) {
                continue;
            }

            $this->inventoryService->ship(
                inventoryId: $inventoryId,
                quantity: $quantityToShip,
                referenceType: Shipment::class,
                referenceId: (string) $shipment->getKey(),
                performedBy: 'system',
                notes: 'Partial shipment confirmed',
            );

            $item->shipped_quantity += $quantityToShip;
            $item->remaining_quantity = max(0, $item->quantity - $item->shipped_quantity);
            $item->save();
        }

        $this->recordHistory($shipment, 'Partial Shipment', [
            'reason' => $reason,
            'partial_quantity' => $partialQuantity,
        ], [
            'provider_name' => $shipment->provider_name,
            'provider_response' => $shipment->provider_response,
        ]);

        return $shipment;
    }

    /**
     * Load a shipment with its items and related reservation items.
     */
    private function loadShipment(int $shipmentId, bool $forUpdate = false): Shipment
    {
        $query = Shipment::query()->with(['items.reservationItem']);

        if ($forUpdate) {
            $query->whereKey($shipmentId)->lockForUpdate();
        }

        return $query->findOrFail($shipmentId);
    }

    /**
     * Load shipment item rows for the shipment.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ShipmentItem>
     */
    private function loadShipmentItems(Shipment $shipment): 
        \Illuminate\Database\Eloquent\Collection
    {
        return ShipmentItem::query()
            ->where('shipment_id', $shipment->getKey())
            ->get();
    }

    /**
     * Validate shipment state for the requested operation.
     */
    private function validateShipmentStatus(Shipment $shipment, array $allowedStatuses): void
    {
        if (! in_array($shipment->status, $allowedStatuses, true)) {
            throw new InvalidShipmentStateException(sprintf('Invalid shipment state: %s', $shipment->status->value));
        }
    }

    /**
     * Update the shipment status and persist the event in history.
     */
    private function updateShipmentStatus(Shipment $shipment, ShipmentStatus $status, string $reason, string $event, array $metadata = []): Shipment
    {
        $shipment->status = $status;
        $shipment->failure_reason = $status === ShipmentStatus::FAILED ? $reason : null;
        $shipment->save();

        $this->recordHistory($shipment, $event, ['reason' => $reason], $metadata);

        return $shipment;
    }

    /**
     * Persist a shipment history entry.
     */
    private function recordHistory(Shipment $shipment, string $event, array $details = [], array $metadata = []): ShipmentHistory
    {
        return ShipmentHistory::query()->create([
            'shipment_id' => $shipment->getKey(),
            'event' => $event,
            'details' => $details,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Verify a webhook signature. This is kept as a placeholder for provider-specific validation.
     */
    private function verifyWebhookSignature(?string $signature, array $payload): void
    {
        if ($signature === null || $signature === '') {
            return;
        }

        if (! str_starts_with($signature, 'sha256=')) {
            throw new InvalidArgumentException('Invalid webhook signature.');
        }

        // Provider-specific signature verification can be implemented here.
    }

    /**
     * Detect duplicate webhooks based on event id and shipment id.
     */
    private function isDuplicateWebhook(int $shipmentId, string $eventId): bool
    {
        return ShipmentWebhook::query()
            ->where('shipment_id', $shipmentId)
            ->where('event_id', $eventId)
            ->exists();
    }
}
