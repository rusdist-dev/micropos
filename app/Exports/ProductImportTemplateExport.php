<?php

namespace App\Exports;

use App\Models\PriceType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template import produk: kolom tetap + satu kolom per tipe harga aktif (paling kanan).
 */
class ProductImportTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    private array $priceTypeCodes;
    private array $priceTypeNames;

    public function __construct()
    {
        $types = PriceType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['code', 'name']);
        $this->priceTypeCodes = $types->pluck('code')->all();
        $this->priceTypeNames = $types->pluck('name')->all();
    }

    public function title(): string
    {
        return 'Produk';
    }

    public function headings(): array
    {
        // Kolom tetap + kolom tipe harga (pakai code agar pencocokan pasti).
        return array_merge(
            ['name', 'sku', 'brand', 'stock', 'min_stock', 'purchase_price', 'description', 'is_active'],
            $this->priceTypeCodes,
        );
    }

    public function array(): array
    {
        // Satu baris contoh.
        $example = ['Kabel UTP Cat6', 'KBL-UTP6', 'Belden', 500, 50, 4500, 'Contoh produk', 1];
        foreach ($this->priceTypeCodes as $code) {
            // Harga contoh berbeda tiap tipe.
            $example[] = 7000;
        }

        return [$example];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

        return [];
    }
}
