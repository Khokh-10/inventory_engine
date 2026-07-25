<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case INITIAL_INTAKE = 'initial_intake';
    case RESERVE = 'reserve';
    case RELEASE = 'release';
    case PICK = 'pick';
    case SHIP = 'ship';
    case RETURN = 'return';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL_INTAKE => 'Initial Intake',
            self::RESERVE => 'Reserve',
            self::RELEASE => 'Release',
            self::PICK => 'Pick',
            self::SHIP => 'Ship',
            self::RETURN => 'Return',
            self::TRANSFER_IN => 'Transfer In',
            self::TRANSFER_OUT => 'Transfer Out',
            self::ADJUSTMENT_IN => 'Adjustment In',
            self::ADJUSTMENT_OUT => 'Adjustment Out',
        };
    }
}
