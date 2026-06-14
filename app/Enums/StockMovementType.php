<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case Supply = 'supply';
    case Opname = 'opname';
    case ReturnIn = 'return_in';
    case ReturnOut = 'return_out';
    case Adjustment = 'adjustment';
    case ServiceOut = 'service_out';
    case ServiceIn = 'service_in';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Penjualan',
            self::Supply => 'Supply',
            self::Opname => 'Opname',
            self::ReturnIn => 'Retur Masuk',
            self::ReturnOut => 'Retur Keluar (Tukar)',
            self::Adjustment => 'Penyesuaian',
            self::ServiceOut => 'Pemakaian Servis',
            self::ServiceIn => 'Pengembalian Servis',
        };
    }
}
