<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryState: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case PICKED = 'picked';
    case SHIPPED = 'shipped';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::RESERVED => 'Reserved',
            self::PICKED => 'Picked',
            self::SHIPPED => 'Shipped',
            self::RETURNED => 'Returned',
        };
    }
}
