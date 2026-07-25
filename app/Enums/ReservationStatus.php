<?php

declare(strict_types=1);

namespace App\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case RELEASED = 'released';
    case CONSUMED = 'consumed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::RELEASED => 'Released',
            self::CONSUMED => 'Consumed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
