<?php

namespace App\Enums;

enum ReturnDirection: string
{
    case Returned = 'returned';
    case Exchange = 'exchange';

    public function label(): string
    {
        return match ($this) {
            self::Returned => 'Dikembalikan',
            self::Exchange => 'Penukaran',
        };
    }
}
