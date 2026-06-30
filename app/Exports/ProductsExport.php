<?php

namespace App\Exports;

use App\Models\PriceType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export produk sesuai filter aktif (search/kategori/merek/status).
 * Kolom mengikuti template import agar hasilnya bisa diimpor kembali:
 * kolom tetap + satu kolom per tipe harga aktif (paling kanan).
 */
class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    private array $priceTypeCodes;

    /**
     * @param  array{search?:string|null,category_id?:int|null,brand?:string|null,is_active?:bool|null}  $filters
     */
    public function __construct(private array $filters = [])
    {
        $this->priceTypeCodes = PriceType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('code')
            ->all();
    }

    public function title(): string
    {
        return 'Produk';
    }

    public function query(): Builder
    {
        return Product::query()
            ->with(['prices', 'category'])
            ->when(! empty($this->filters['search']), function ($q) {
                $term = $this->filters['search'];
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%"));
            })
            ->when(! empty($this->filters['category_id']), fn ($q) => $q->where('category_id', (int) $this->filters['category_id']))
            ->when(! empty($this->filters['brand']), fn ($q) => $q->where('brand', $this->filters['brand']))
            ->when(isset($this->filters['is_active']), fn ($q) => $q->where('is_active', $this->filters['is_active']))
            ->orderBy('name');
    }

    public function headings(): array
    {
        return array_merge(
            ['name', 'sku', 'category', 'brand', 'stock', 'min_stock', 'purchase_price', 'description', 'is_active'],
            $this->priceTypeCodes,
        );
    }

    /** @param  Product  $product */
    public function map($product): array
    {
        $row = [
            $product->name,
            $product->sku,
            $product->category?->name,
            $product->brand,
            $product->stock,
            $product->min_stock,
            $product->purchase_price,
            $product->description,
            $product->is_active ? 1 : 0,
        ];

        $pricesByCode = $product->prices->keyBy('price_type');
        foreach ($this->priceTypeCodes as $code) {
            $row[] = $pricesByCode->get($code)?->price;
        }

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

        return [];
    }
}
