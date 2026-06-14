<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Enums\ServiceStatus;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Statistik kartu dashboard.
     *
     * Pendapatan = penjualan kasir + pendapatan servis. Pendapatan servis diakui
     * saat status terminal: Selesai (total, by completed_at) & Batal (cancellation_fee, by canceled_at).
     */
    public function stats(): array
    {
        $today = Carbon::today();

        $cashier = (float) Transaction::whereDate('created_at', $today)->sum('total');
        $service = (float) ServiceOrder::where('service_status', ServiceStatus::Selesai->value)
                ->whereDate('completed_at', $today)->sum('total')
            + (float) ServiceOrder::where('service_status', ServiceStatus::Batal->value)
                ->whereDate('canceled_at', $today)->sum('cancellation_fee');

        return [
            'total_products' => Product::count(),
            // Nilai aset = total (stok x harga modal) seluruh produk.
            'total_asset_value' => (float) Product::sum(DB::raw('stock * purchase_price')),
            'today_sales' => $cashier + $service,
            'today_sales_cashier' => $cashier,
            'today_sales_service' => $service,
            'low_stock_count' => Product::whereColumn('stock', '<=', 'min_stock')->count(),
        ];
    }

    /** Data grafik pendapatan N hari terakhir (kasir + servis). */
    public function salesChart(int $days = 7): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $cashier = Transaction::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        // Servis selesai diakui pada tanggal completed_at.
        $serviceDone = ServiceOrder::query()
            ->where('service_status', ServiceStatus::Selesai->value)
            ->where('completed_at', '>=', $start)
            ->selectRaw('DATE(completed_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        // Servis batal: hanya biaya pembatalan, pada tanggal canceled_at.
        $serviceCanceled = ServiceOrder::query()
            ->where('service_status', ServiceStatus::Batal->value)
            ->where('canceled_at', '>=', $start)
            ->selectRaw('DATE(canceled_at) as d, SUM(cancellation_fee) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->isoFormat('dd'); // nama hari singkat
            $data[] = (float) ($cashier[$key] ?? 0)
                + (float) ($serviceDone[$key] ?? 0)
                + (float) ($serviceCanceled[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** Produk terlaris berdasarkan total qty terjual. */
    public function topProducts(int $limit = 5): array
    {
        $rows = TransactionItem::query()
            ->where('item_type', ItemType::Product->value)
            ->select('item_name', DB::raw('SUM(qty) as qty_sold'))
            ->groupBy('item_name')
            ->orderByDesc('qty_sold')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('item_name')->all(),
            'data' => $rows->pluck('qty_sold')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    public function all(): array
    {
        return [
            'stats' => $this->stats(),
            'sales_chart' => $this->salesChart(),
            'top_products' => $this->topProducts(),
        ];
    }
}
