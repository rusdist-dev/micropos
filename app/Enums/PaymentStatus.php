<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'belum_bayar';
    case Dp = 'dp';
    case Lunas = 'lunas';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Bayar',
            self::Dp => 'DP',
            self::Lunas => 'Lunas',
        };
    }
}
