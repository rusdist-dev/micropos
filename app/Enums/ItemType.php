<?php

namespace App\Enums;

enum ItemType: string
{
    case Product = 'product';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Produk',
            self::Service => 'Jasa',
        };
    }
}
