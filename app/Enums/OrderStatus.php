<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case RESERVED = 'reserved';
    case PARTIALLY_PICKED = 'partially_picked';
    case PICKED = 'picked';
    case PARTIALLY_SHIPPED = 'partially_shipped';
    case SHIPPED = 'shipped';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::RESERVED => 'Reserved',
            self::PARTIALLY_PICKED => 'Partially Picked',
            self::PICKED => 'Picked',
            self::PARTIALLY_SHIPPED => 'Partially Shipped',
            self::SHIPPED => 'Shipped',
            self::RETURNED => 'Returned',
            self::CANCELLED => 'Cancelled',
        };
    }
}
