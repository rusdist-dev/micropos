<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /** Statistik kartu dashboard. */
    public function stats(): array
    {
        $today = Carbon::today();

        return [
            'total_products' => Product::count(),
            'total_customers' => CustomerType::count(),
            'today_sales' => (float) Transaction::whereDate('created_at', $today)->sum('total'),
            'low_stock_count' => Product::whereColumn('stock', '<=', 'min_stock')->count(),
        ];
    }

    /** Data grafik penjualan N hari terakhir. */
    public function salesChart(int $days = 7): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = Transaction::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->isoFormat('dd'); // nama hari singkat
            $data[] = (float) ($rows[$key] ?? 0);
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
