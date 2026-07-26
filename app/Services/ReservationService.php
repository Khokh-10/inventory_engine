<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Exceptions\InvalidReservationStateException;
use App\Exceptions\ReservationAlreadyCancelledException;
use App\Exceptions\ReservationExpiredException;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    /**
     * Create a reservation for an order using inventory service for stock movements.
     *
     * Idempotency: if the order already has a non-terminal reservation
     * (ACTIVE or CONSUMED), we return that existing reservation instead of
     * creating a duplicate. This protects against the same command/request
     * being executed twice (e.g. a retried queue job, a double form submit,
     * or a client retrying after a timeout without knowing the first
     * request actually succeeded).
     */
    public function createReservation(Order $order, array $reservationItems): Reservation
    {
        return DB::transaction(function () use ($order, $reservationItems): Reservation {
            // Lock the order row so two concurrent calls for the same order
            // can't both pass the "no existing reservation" check at once.
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $existing = Reservation::query()
                ->where('order_id', $order->getKey())
                ->whereIn('status', [ReservationStatus::ACTIVE, ReservationStatus::CONSUMED])
                ->first();

            if ($existing !== null) {
                return $this->loadReservationWithItems($existing->getKey());
            }

            $reservation = Reservation::query()->create([
                'order_id' => $order->getKey(),
                'status' => ReservationStatus::ACTIVE,
                'expires_at' => now()->addDay(),
            ]);

            foreach ($reservationItems as $item) {
                $inventoryId = (int) $item['inventory_id'];
                $quantity = (int) $item['quantity'];

                $this->inventoryService->reserve(
                    inventoryId: $inventoryId,
                    quantity: $quantity,
                    referenceType: Reservation::class,
                    referenceId: (string) $reservation->getKey(),
                    performedBy: 'system',
                    notes: 'Reservation created',
                );

                ReservationItem::query()->create([
                    'reservation_id' => $reservation->getKey(),
                    'inventory_id' => $inventoryId,
                    'quantity' => $quantity,
                ]);
            }

            return $this->loadReservationWithItems($reservation->getKey());
        });
    }

    /**
     * Cancel an active reservation and release reserved inventory.
     */
    public function cancelReservation(int $reservationId): Reservation
    {
        return DB::transaction(function () use ($reservationId): Reservation {
            $reservation = $this->loadReservationWithItems($reservationId, forUpdate: true);
            $this->validateReservationStatus($reservation, [ReservationStatus::ACTIVE]);

            foreach ($reservation->items as $item) {
                $this->inventoryService->release(
                    inventoryId: $item->inventory_id,
                    quantity: $item->quantity,
                    referenceType: Reservation::class,
                    referenceId: (string) $reservation->getKey(),
                    performedBy: 'system',
                    notes: 'Reservation cancelled',
                );
            }

            $reservation->status = ReservationStatus::CANCELLED;
            $reservation->save();

            return $this->loadReservationWithItems($reservation->getKey());
        });
    }

    /**
     * Expire an active reservation and release its inventory.
     */
    public function expireReservation(int $reservationId): Reservation
    {
        return DB::transaction(function () use ($reservationId): Reservation {
            $reservation = $this->loadReservationWithItems($reservationId, forUpdate: true);
            $this->validateReservationStatus($reservation, [ReservationStatus::ACTIVE]);

            foreach ($reservation->items as $item) {
                $this->inventoryService->release(
                    inventoryId: $item->inventory_id,
                    quantity: $item->quantity,
                    referenceType: Reservation::class,
                    referenceId: (string) $reservation->getKey(),
                    performedBy: 'system',
                    notes: 'Reservation expired',
                );
            }

            $reservation->status = ReservationStatus::EXPIRED;
            $reservation->save();

            return $this->loadReservationWithItems($reservation->getKey());
        });
    }

    /**
     * Mark a reservation as consumed when picking begins.
     */
    public function consumeReservation(int $reservationId): Reservation
    {
        return DB::transaction(function () use ($reservationId): Reservation {
            $reservation = $this->loadReservationWithItems($reservationId, forUpdate: true);
            $this->validateReservationStatus($reservation, [ReservationStatus::ACTIVE]);

            $reservation->status = ReservationStatus::CONSUMED;
            $reservation->save();

            return $this->loadReservationWithItems($reservation->getKey());
        });
    }

    /**
     * Validate that the reservation is in an allowed state for the operation.
     */
    private function validateReservationStatus(Reservation $reservation, array $allowedStatuses): void
    {
        $status = $reservation->status instanceof ReservationStatus
            ? $reservation->status
            : ReservationStatus::from($reservation->status);

        if ($status === ReservationStatus::CANCELLED) {
            throw new ReservationAlreadyCancelledException('Reservation is already cancelled.');
        }

        if ($status === ReservationStatus::EXPIRED) {
            throw new ReservationExpiredException('Reservation has already expired.');
        }

        if (! in_array($status, $allowedStatuses, true)) {
            throw new InvalidReservationStateException(sprintf('Invalid reservation state: %s', $status->value));
        }
    }

    /**
     * Load a reservation with its items, optionally locking the row for update.
     */
    private function loadReservationWithItems(int $reservationId, bool $forUpdate = false): Reservation
    {
        $query = Reservation::query()->with('items');

        if ($forUpdate) {
            $query->whereKey($reservationId)->lockForUpdate();
        }

        return $query->findOrFail($reservationId);
    }
}