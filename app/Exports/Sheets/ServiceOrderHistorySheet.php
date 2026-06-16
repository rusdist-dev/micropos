<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceOrderHistorySheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    /**
     * @param  Collection<int,\App\Models\ServiceOrder>  $orders  (sudah eager-load items, customer, technician, operator)
     */
    public function __construct(private readonly Collection $orders)
    {
    }

    public function title(): string
    {
        return 'Order Servis';
    }

    public function headings(): array
    {
        return ['No', 'Invoice', 'Tanggal', 'Tenggang Waktu', 'Tgl Selesai', 'Pelanggan', 'Teknisi', 'Operator', 'Status Servis', 'Status Bayar', 'Jumlah Item', 'Subtotal', 'Diskon', 'Total', 'Dibayar', 'Sisa'];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;
        $sumSubtotal = 0;
        $sumDiscount = 0;
        $sumTotal = 0;
        $sumPaid = 0;
        $sumRemaining = 0;

        foreach ($this->orders as $order) {
            $subtotal = (float) $order->subtotal;
            $discount = (float) $order->discount;
            $total = (float) $order->total;
            $paid = (float) $order->paid_amount;
            $remaining = $order->remaining();
            $sumSubtotal += $subtotal;
            $sumDiscount += $discount;
            $sumTotal += $total;
            $sumPaid += $paid;
            $sumRemaining += $remaining;

            $rows[] = [
                $no++,
                $order->invoice_number,
                $order->created_at?->format('d/m/Y H:i'),
                $order->due_date?->format('d/m/Y'),
                $order->completed_at?->format('d/m/Y H:i'),
                $order->customer?->name ?? 'Pelanggan Umum',
                $order->technician?->name ?? '-',
                $order->operator?->name ?? '-',
                $order->service_status?->label(),
                $order->payment_status?->label(),
                $order->items->count(),
                $subtotal,
                $discount,
                $total,
                $paid,
                $remaining,
            ];
        }

        // Baris total di bawah kolom Subtotal..Sisa.
        $rows[] = ['', '', '', '', '', '', '', '', '', '', 'TOTAL', $sumSubtotal, $sumDiscount, $sumTotal, $sumPaid, $sumRemaining];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);

        // Baris total: heading (1) + jumlah order + baris total (1).
        $totalRow = $this->orders->count() + 2;
        $sheet->getStyle("K{$totalRow}:P{$totalRow}")->getFont()->setBold(true);

        return [];
    }
}
