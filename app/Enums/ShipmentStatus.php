<?php

declare(strict_types=1);

namespace App\Enums;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case LABEL_CREATED = 'label_created';
    case PICKED = 'picked';
    case PACKED = 'packed';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case PARTIALLY_DELIVERED = 'partially_delivered';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::LABEL_CREATED => 'Label Created',
            self::PICKED => 'Picked',
            self::PACKED => 'Packed',
            self::IN_TRANSIT => 'In Transit',
            self::DELIVERED => 'Delivered',
            self::PARTIALLY_DELIVERED => 'Partially Delivered',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
