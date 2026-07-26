<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ProviderResponse;
use App\Models\Shipment;

interface ShippingProviderInterface
{
    /**
     * Return the human-readable provider name.
     */
    public function name(): string;

    /**
     * Process the shipment and return a typed response.
     */
    public function ship(Shipment $shipment): ProviderResponse;

    /**
     * Return the provider tracking number for the shipment.
     */
    public function getTrackingNumber(Shipment $shipment): ?string;

    /**
     * Return a provider-specific shipment reference.
     */
    public function getProviderReference(Shipment $shipment): ?string;

    /**
     * Return the quantity the provider processed in a partial shipment event.
     */
    public function getPartialQuantity(Shipment $shipment): int;
}
