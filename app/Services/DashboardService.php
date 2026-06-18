<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Enums\ServiceStatus;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Statistik kartu dashboard: pendapatan & laba hari ini / bulan ini, plus
     * perbandingan terhadap periode sebelumnya (kemarin & bulan lalu).
     *
     * Pendapatan = penjualan kasir + pendapatan servis. Pendapatan servis diakui
     * saat status terminal: Selesai (total, by completed_at) & Batal (cancellation_fee, by canceled_at).
     *
     * Laba kotor = pendapatan - HPP. HPP memakai purchase_price produk saat ini
     * (transaction_items tidak menyimpan cost snapshot), jadi nilainya pendekatan.
     * Servis dianggap laba penuh kecuali bagian sparepart (produk) yang dipakai.
     */
    public function stats(): array
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();

        // Hari ini vs kemarin (rentang waktu penuh per hari).
        $todayFig = $this->periodFigures($today, $tomorrow);
        $yesterdayFig = $this->periodFigures($today->copy()->subDay(), $today);

        // Bulan berjalan (1..hari ini) vs periode setara bulan lalu (1..hari yang sama),
        // supaya perbandingan adil — bukan bulan-berjalan vs bulan-lalu-penuh.
        $monthStart = $today->copy()->startOfMonth();
        $lastMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $monthFig = $this->periodFigures($monthStart, $tomorrow);
        $lastMonthFig = $this->periodFigures($lastMonthStart, $lastMonthStart->copy()->addDays($today->day));

        return [
            'total_products' => Product::count(),
            // Nilai aset = total (stok x harga modal) seluruh produk.
            'total_asset_value' => (float) Product::sum(DB::raw('stock * purchase_price')),
            'low_stock_count' => Product::whereColumn('stock', '<=', 'min_stock')->count(),

            // Hari ini
            'today_sales' => $todayFig['sales'],
            'today_sales_cashier' => $todayFig['sales_cashier'],
            'today_sales_service' => $todayFig['sales_service'],
            'today_profit' => $todayFig['profit'],
            'today_margin_pct' => $this->margin($todayFig['profit'], $todayFig['sales']),

            // Bulan berjalan
            'month_sales' => $monthFig['sales'],
            'month_profit' => $monthFig['profit'],
            'month_margin_pct' => $this->margin($monthFig['profit'], $monthFig['sales']),

            // Perbandingan (persen; null bila tak ada pembanding)
            'compare' => [
                'today_sales' => $this->delta($todayFig['sales'], $yesterdayFig['sales']),
                'today_profit' => $this->delta($todayFig['profit'], $yesterdayFig['profit']),
                'month_sales' => $this->delta($monthFig['sales'], $lastMonthFig['sales']),
                'month_profit' => $this->delta($monthFig['profit'], $lastMonthFig['profit']),
            ],
        ];
    }

    /**
     * Hitung pendapatan & laba pada rentang [start, end) — half-open agar batas
     * antar-periode tidak terhitung ganda. Menggabungkan kasir (by created_at) dan
     * servis (selesai by completed_at, batal by canceled_at).
     *
     * @return array{sales:float,sales_cashier:float,sales_service:float,profit:float}
     */
    private function periodFigures(Carbon $start, Carbon $end): array
    {
        // --- Kasir: diakui pada created_at ---
        $cashierSales = (float) Transaction::whereBetween('created_at', [$start, $end])->sum('total');
        $cashierHpp = (float) DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->where('transaction_items.item_type', ItemType::Product->value)
            ->where('transactions.created_at', '>=', $start)
            ->where('transactions.created_at', '<', $end)
            ->sum(DB::raw('transaction_items.qty * products.purchase_price'));

        // --- Servis selesai: diakui pada completed_at; HPP = sparepart terpakai ---
        $serviceDoneSales = (float) ServiceOrder::where('service_status', ServiceStatus::Selesai->value)
            ->whereBetween('completed_at', [$start, $end])->sum('total');
        $serviceDoneHpp = (float) DB::table('service_order_items')
            ->join('service_orders', 'service_order_items.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_items.product_id', '=', 'products.id')
            ->where('service_order_items.item_type', ItemType::Product->value)
            ->where('service_orders.service_status', ServiceStatus::Selesai->value)
            ->where('service_orders.completed_at', '>=', $start)
            ->where('service_orders.completed_at', '<', $end)
            ->sum(DB::raw('service_order_items.qty * products.purchase_price'));

        // --- Servis batal: hanya biaya pembatalan (tanpa HPP), diakui pada canceled_at ---
        $serviceCancelSales = (float) ServiceOrder::where('service_status', ServiceStatus::Batal->value)
            ->whereBetween('canceled_at', [$start, $end])->sum('cancellation_fee');

        $salesService = $serviceDoneSales + $serviceCancelSales;
        $profitCashier = $cashierSales - $cashierHpp;
        $profitService = ($serviceDoneSales - $serviceDoneHpp) + $serviceCancelSales;

        return [
            'sales' => $cashierSales + $salesService,
            'sales_cashier' => $cashierSales,
            'sales_service' => $salesService,
            'profit' => $profitCashier + $profitService,
        ];
    }

    /** Persentase perubahan current vs previous; null bila previous = 0 (tak ada pembanding). */
    private function delta(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /** Margin laba (%) terhadap pendapatan; null bila pendapatan = 0. */
    private function margin(float $profit, float $sales): ?float
    {
        if ($sales == 0.0) {
            return null;
        }

        return round($profit / $sales * 100, 1);
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

    /**
     * Produk yang stoknya <= min_stock, paling kritis (selisih terkecil/minus) di atas.
     * Tidak memfilter is_active agar konsisten dengan kartu "Stok Rendah".
     *
     * @return list<array{id:int,name:string,sku:?string,stock:int,min_stock:int}>
     */
    public function lowStockProducts(int $limit = 8): array
    {
        return Product::query()
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderByRaw('stock - min_stock asc')
            ->orderBy('stock')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'stock', 'min_stock'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => (int) $p->stock,
                'min_stock' => (int) $p->min_stock,
            ])->all();
    }

    /**
     * Pelanggan loyal berdasarkan total belanja: gabungan transaksi kasir & order
     * servis (selain batal). Pelanggan tanpa identitas (customer_id null) diabaikan.
     *
     * @return list<array{id:int,name:string,transactions:int,total_spent:float}>
     */
    public function loyalCustomers(int $limit = 5): array
    {
        $agg = [];
        $accumulate = function ($rows) use (&$agg): void {
            foreach ($rows as $r) {
                $agg[$r->customer_id]['trx'] = ($agg[$r->customer_id]['trx'] ?? 0) + (int) $r->trx;
                $agg[$r->customer_id]['spent'] = ($agg[$r->customer_id]['spent'] ?? 0) + (float) $r->spent;
            }
        };

        $accumulate(Transaction::query()
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) as trx, SUM(total) as spent')
            ->groupBy('customer_id')->get());

        $accumulate(ServiceOrder::query()
            ->whereNotNull('customer_id')
            ->where('service_status', '!=', ServiceStatus::Batal->value)
            ->selectRaw('customer_id, COUNT(*) as trx, SUM(total) as spent')
            ->groupBy('customer_id')->get());

        uasort($agg, fn ($a, $b) => $b['spent'] <=> $a['spent']);
        $top = array_slice($agg, 0, $limit, true);

        $names = CustomerType::whereIn('id', array_keys($top))->pluck('name', 'id');

        return collect($top)->map(fn ($v, $id) => [
            'id' => (int) $id,
            'name' => $names[$id] ?? '—',
            'transactions' => $v['trx'],
            'total_spent' => $v['spent'],
        ])->values()->all();
    }

    public function all(): array
    {
        return [
            'stats' => $this->stats(),
            'sales_chart' => $this->salesChart(),
            'top_products' => $this->topProducts(),
            'low_stock_products' => $this->lowStockProducts(),
            'loyal_customers' => $this->loyalCustomers(),
        ];
    }
}
