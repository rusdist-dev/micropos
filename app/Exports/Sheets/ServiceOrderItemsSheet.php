<?php

namespace App\Exports\Sheets;

use App\Enums\ItemType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceOrderItemsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    /**
     * @param  Collection<int,\App\Models\ServiceOrder>  $orders
     */
    public function __construct(private readonly Collection $orders)
    {
    }

    public function title(): string
    {
        return 'Detail Item Servis';
    }

    public function headings(): array
    {
        return ['No', 'Invoice', 'Tanggal', 'Pelanggan', 'Teknisi', 'Item', 'Tipe', 'Harga', 'Qty', 'Subtotal'];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;
        $sumSubtotal = 0;

        foreach ($this->orders as $order) {
            foreach ($order->items as $item) {
                $sumSubtotal += (float) $item->subtotal;

                $rows[] = [
                    $no++,
                    $order->invoice_number,
                    $order->created_at?->format('d/m/Y H:i'),
                    $order->customer?->name ?? 'Pelanggan Umum',
                    $order->technician?->name ?? '-',
                    $item->item_name,
                    $item->item_type === ItemType::Product ? 'Produk' : 'Jasa',
                    (float) $item->price_snapshot,
                    (int) $item->qty,
                    (float) $item->subtotal,
                ];
            }
        }

        $rows[] = ['', '', '', '', '', '', '', '', 'TOTAL', $sumSubtotal];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $itemCount = $this->orders->sum(fn ($o) => $o->items->count());
        $totalRow = $itemCount + 2; // heading + baris data + baris total
        $sheet->getStyle("I{$totalRow}:J{$totalRow}")->getFont()->setBold(true);

        return [];
    }
}
