<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Process = 'process';
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Process => 'Proses',
            self::Selesai => 'Selesai',
            self::Batal => 'Batal',
        };
    }
}
