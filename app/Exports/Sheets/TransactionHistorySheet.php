<?php

namespace App\Exports\Sheets;

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

class TransactionHistorySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
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
        return 'Riwayat Transaksi';
    }

    public function headings(): array
    {
        return ['No', 'Invoice', 'Tanggal', 'Pelanggan', 'Kasir', 'Jumlah Item', 'Subtotal', 'Diskon', 'Total', 'Profit'];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;
        $sumSubtotal = 0;
        $sumDiscount = 0;
        $sumTotal = 0;
        $sumProfit = 0;

        foreach ($this->transactions as $trx) {
            $discount = (float) $trx->discount;
            // Profit bersih: profit per item dikurangi diskon tingkat transaksi.
            $profit = $trx->items->sum(fn ($item) => $this->itemProfit($item)) - $discount;
            $subtotal = (float) $trx->subtotal;
            $total = (float) $trx->total;
            $sumSubtotal += $subtotal;
            $sumDiscount += $discount;
            $sumTotal += $total;
            $sumProfit += $profit;

            $rows[] = [
                $no++,
                $trx->invoice_number,
                $trx->created_at?->format('d/m/Y H:i'),
                $trx->customer?->name ?? 'Pelanggan Umum',
                $trx->kasir?->name ?? '-',
                $trx->items->count(),
                $subtotal,
                $discount,
                $total,
                $profit,
            ];
        }

        // Baris total di bawah kolom Subtotal..Profit.
        $rows[] = ['', '', '', '', '', 'TOTAL', $sumSubtotal, $sumDiscount, $sumTotal, $sumProfit];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        // Baris total: heading (1) + jumlah transaksi + baris total (1).
        $totalRow = $this->transactions->count() + 2;
        $sheet->getStyle("F{$totalRow}:J{$totalRow}")->getFont()->setBold(true);

        return [];
    }
}
