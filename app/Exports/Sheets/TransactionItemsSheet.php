<?php

namespace App\Exports\Sheets;

use App\Enums\ItemType;
use App\Exports\Concerns\ComputesProfit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionItemsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    use ComputesProfit;

    /**
     * @param  Collection<int,\App\Models\Transaction>  $transactions
     */
    public function __construct(private readonly Collection $transactions)
    {
    }

    public function title(): string
    {
        return 'Detail Item Terjual';
    }

    public function headings(): array
    {
        return ['No', 'Invoice', 'Tanggal', 'Kasir', 'Item', 'Tipe', 'Tipe Harga', 'Harga Jual', 'Qty', 'Subtotal', 'Harga Beli', 'Profit'];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;
        $sumSubtotal = 0;
        $sumProfit = 0;

        foreach ($this->transactions as $trx) {
            foreach ($trx->items as $item) {
                $cost = $this->itemCost($item);
                $profit = $this->itemProfit($item);
                $sumSubtotal += (float) $item->subtotal;
                $sumProfit += $profit;

                $rows[] = [
                    $no++,
                    $trx->invoice_number,
                    $trx->created_at?->format('d/m/Y H:i'),
                    $trx->kasir?->name ?? '-',
                    $item->item_name,
                    $item->item_type === ItemType::Product ? 'Produk' : 'Jasa',
                    $item->price_type_used ?? '-',
                    (float) $item->price_snapshot,
                    (int) $item->qty,
                    (float) $item->subtotal,
                    $cost,
                    $profit,
                ];
            }
        }

        $rows[] = ['', '', '', '', '', '', '', '', 'TOTAL', $sumSubtotal, '', $sumProfit];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

        $itemCount = $this->transactions->sum(fn ($t) => $t->items->count());
        $totalRow = $itemCount + 2; // heading + baris data + baris total
        $sheet->getStyle("I{$totalRow}:L{$totalRow}")->getFont()->setBold(true);

        return [];
    }
}
