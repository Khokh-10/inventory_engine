<?php

declare(strict_types=1);

namespace App\Services\Providers;

use App\Contracts\ShippingProviderInterface;
use App\Enums\ProviderResponse;
use App\Models\Shipment;

// ensure ProviderResponse resolves in this workspace

class MockShippingProvider implements ShippingProviderInterface
{
    /**
     * Probability weights for simulated outcomes.
     *
     * @var array<ProviderResponse, int>
     */
    private array $weights = [
    ProviderResponse::SUCCESS->value => 50,
    ProviderResponse::FAILED->value => 15,
    ProviderResponse::TIMEOUT->value => 15,
    ProviderResponse::PARTIAL_SUCCESS->value => 10,
    ProviderResponse::DUPLICATE->value => 10,
    ];
    /**
     * Internal state used to simulate provider responses.
     */
    private ?string $trackingNumber = null;

    private ?string $providerReference = null;

    private int $partialQuantity = 0;

    public function name(): string
    {
        return 'MockShippingProvider';
    }

    public function ship(Shipment $shipment): ProviderResponse
    {
        $response = $this->selectResponse();
        if ($response === ProviderResponse::FAILED) {
            return ProviderResponse::FAILED;
        }
        $this->trackingNumber = $this->generateTrackingNumber();
        $this->providerReference = $this->generateProviderReference();

        if ($response === ProviderResponse::PARTIAL_SUCCESS) {
            $this->partialQuantity = $this->calculatePartialQuantity($shipment);
            return ProviderResponse::PARTIAL_SUCCESS;
        }

        $this->partialQuantity = 0;

        return $response;
    }

    public function getTrackingNumber(Shipment $shipment): ?string
    {
        return $this->trackingNumber;
    }

    public function getProviderReference(Shipment $shipment): ?string
    {
        return $this->providerReference;
    }

    public function getPartialQuantity(Shipment $shipment): int
    {
        return $this->partialQuantity;
    }

  private function selectResponse(): ProviderResponse
{
    $roll = random_int(1, 100);
    $cursor = 0;

    foreach ($this->weights as $response => $weight) {
        $cursor += $weight;
        if ($roll <= $cursor) {
            return ProviderResponse::from($response);
        }
    }

    return ProviderResponse::SUCCESS;
}

    private function calculatePartialQuantity(Shipment $shipment): int
    {
        $totalQuantity = $shipment->items->sum('quantity');

        return max(1, (int) floor($totalQuantity * 0.6));
    }

    private function generateTrackingNumber(): string
    {
        return sprintf('MOCK-%s-%d', date('Ymd'), random_int(100000, 999999));
    }

    private function generateProviderReference(): string
    {
        return sprintf('REF-%s', bin2hex(random_bytes(4)));
    }
}
