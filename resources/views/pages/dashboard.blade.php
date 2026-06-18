<x-layouts.app title="Dashboard">
    <div
        x-data="{
            loading: true,
            error: null,
            stats: {
                total_products: 0, total_asset_value: 0, low_stock_count: 0,
                today_sales: 0, today_sales_cashier: 0, today_sales_service: 0, today_profit: 0, today_margin_pct: null,
                month_sales: 0, month_profit: 0, month_margin_pct: null,
                compare: { today_sales: null, today_profit: null, month_sales: null, month_profit: null },
            },
            lowStock: [],
            loyalCustomers: [],
            salesChart: null,
            topChart: null,
            deltaText(pct) { return pct === null ? 'baru' : (pct >= 0 ? '+' : '') + pct + '%'; },
            deltaClass(pct) { return pct === null ? 'text-gray-400' : (pct >= 0 ? 'text-emerald-600' : 'text-danger-600'); },
            deltaIcon(pct) { return pct === null ? '' : (pct >= 0 ? '▲' : '▼'); },
            marginText(pct) { return pct === null ? '—' : 'Margin ' + pct + '%'; },
            async load() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await window.api.get('/api/dashboard');
                    const d = res.data;
                    this.stats = d.stats;
                    this.lowStock = d.low_stock_products ?? [];
                    this.loyalCustomers = d.loyal_customers ?? [];
                    await this.$nextTick();
                    this.renderSales(d.sales_chart);
                    this.renderTop(d.top_products);
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            },
            renderSales(payload) {
                if (! window.Chart || ! this.$refs.salesCanvas) return;
                const ctx = this.$refs.salesCanvas.getContext('2d');
                const grad = ctx.createLinearGradient(0, 0, 0, 300);
                grad.addColorStop(0, window.posColors.primaryRgba(0.25));
                grad.addColorStop(1, window.posColors.primaryRgba(0));
                this.salesChart?.destroy();
                this.salesChart = new window.Chart(ctx, {
                    type: 'line',
                    data: { labels: payload.labels, datasets: [{
                        label: 'Pendapatan', data: payload.data,
                        borderColor: window.posColors.primary, backgroundColor: grad,
                        borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3,
                    }] },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => window.rupiah(c.parsed.y) } } },
                        scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'Rp ' + (v / 1000) + 'k' }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } },
                    },
                });
            },
            renderTop(payload) {
                if (! window.Chart || ! this.$refs.topCanvas) return;
                const palette = [window.posColors.primary, window.posColors.warning, window.posColors.danger, window.posColors.primaryLight, window.posColors.gray];
                this.topChart?.destroy();
                this.topChart = new window.Chart(this.$refs.topCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: { labels: payload.labels, datasets: [{ data: payload.data, backgroundColor: payload.labels.map((_, i) => palette[i % palette.length]), borderWidth: 2, borderColor: '#fff' }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14, font: { size: 11 } } }, tooltip: { callbacks: { label: (c) => ' ' + c.label + ': ' + c.parsed + ' terjual' } } } },
                });
            },
        }"
        x-init="load()"
    >
        {{-- Error --}}
        <template x-if="error">
            <x-ui.alert variant="danger" title="Gagal memuat dashboard" class="mb-4">
                <span x-text="error"></span>
            </x-ui.alert>
        </template>

        {{-- Kartu finansial: pendapatan & laba, hari ini & bulan ini --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Pendapatan Hari Ini --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pendapatan Hari Ini</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : window.rupiah(stats.today_sales)"></p>
                        <p class="mt-1 text-xs text-gray-400" x-show="!loading">
                            Kasir <span class="font-medium text-gray-600" x-text="window.rupiah(stats.today_sales_cashier)"></span>
                            &middot; Servis <span class="font-medium text-gray-600" x-text="window.rupiah(stats.today_sales_service)"></span>
                        </p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><x-heroicon-o-banknotes class="h-6 w-6" /></span>
                </div>
                <p class="mt-3 text-xs font-medium" x-show="!loading" :class="deltaClass(stats.compare.today_sales)">
                    <span x-text="deltaIcon(stats.compare.today_sales)"></span>
                    <span x-text="deltaText(stats.compare.today_sales)"></span>
                    <span class="text-gray-400">vs kemarin</span>
                </p>
            </div>

            {{-- Laba Hari Ini --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Laba Hari Ini</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : window.rupiah(stats.today_profit)"></p>
                        <p class="mt-1 text-xs text-gray-400" x-show="!loading" x-text="marginText(stats.today_margin_pct)"></p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><x-heroicon-o-arrow-trending-up class="h-6 w-6" /></span>
                </div>
                <p class="mt-3 text-xs font-medium" x-show="!loading" :class="deltaClass(stats.compare.today_profit)">
                    <span x-text="deltaIcon(stats.compare.today_profit)"></span>
                    <span x-text="deltaText(stats.compare.today_profit)"></span>
                    <span class="text-gray-400">vs kemarin</span>
                </p>
            </div>

            {{-- Pendapatan Bulan Ini --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pendapatan Bulan Ini</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : window.rupiah(stats.month_sales)"></p>
                        <p class="mt-1 text-xs text-gray-400" x-show="!loading">sejak awal bulan</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><x-heroicon-o-calendar-days class="h-6 w-6" /></span>
                </div>
                <p class="mt-3 text-xs font-medium" x-show="!loading" :class="deltaClass(stats.compare.month_sales)">
                    <span x-text="deltaIcon(stats.compare.month_sales)"></span>
                    <span x-text="deltaText(stats.compare.month_sales)"></span>
                    <span class="text-gray-400">vs periode bulan lalu</span>
                </p>
            </div>

            {{-- Laba Bulan Ini --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Laba Bulan Ini</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : window.rupiah(stats.month_profit)"></p>
                        <p class="mt-1 text-xs text-gray-400" x-show="!loading" x-text="marginText(stats.month_margin_pct)"></p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><x-heroicon-o-arrow-trending-up class="h-6 w-6" /></span>
                </div>
                <p class="mt-3 text-xs font-medium" x-show="!loading" :class="deltaClass(stats.compare.month_profit)">
                    <span x-text="deltaIcon(stats.compare.month_profit)"></span>
                    <span x-text="deltaText(stats.compare.month_profit)"></span>
                    <span class="text-gray-400">vs periode bulan lalu</span>
                </p>
            </div>
        </div>

        {{-- Kartu inventaris --}}
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Produk</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : stats.total_products"></p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 text-gray-600"><x-heroicon-o-cube class="h-6 w-6" /></span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Nilai Aset</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : window.rupiah(stats.total_asset_value)"></p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 text-gray-600"><x-heroicon-o-banknotes class="h-6 w-6" /></span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Stok Rendah</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : stats.low_stock_count"></p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-danger-50 text-danger-600"><x-heroicon-o-exclamation-triangle class="h-6 w-6" /></span>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-ui.card title="Pendapatan 7 Hari Terakhir" class="lg:col-span-2">
                <div class="relative" style="height: 300px">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center">
                        <x-ui.loading-spinner size="lg" />
                    </div>
                    <canvas x-ref="salesCanvas" x-show="!loading"></canvas>
                </div>
            </x-ui.card>

            <x-ui.card title="Produk Terlaris">
                <div class="relative" style="height: 300px">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center">
                        <x-ui.loading-spinner size="lg" />
                    </div>
                    <canvas x-ref="topCanvas" x-show="!loading"></canvas>
                </div>
            </x-ui.card>
        </div>

        {{-- Daftar stok rendah & pelanggan loyal --}}
        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Stok rendah --}}
            <x-ui.card title="Produk Stok Rendah" padding="p-0">
                <x-slot:actions>
                    <span class="rounded-full bg-danger-50 px-2.5 py-0.5 text-xs font-semibold text-danger-600"
                          x-text="(loading ? 0 : stats.low_stock_count) + ' produk'"></span>
                </x-slot:actions>

                <div x-show="loading" class="flex items-center justify-center py-12">
                    <x-ui.loading-spinner size="lg" />
                </div>

                <template x-if="!loading && lowStock.length === 0">
                    <p class="px-6 py-10 text-center text-sm text-gray-400">Semua stok aman 👍</p>
                </template>

                <ul x-show="!loading && lowStock.length > 0" class="divide-y divide-gray-100">
                    <template x-for="p in lowStock" :key="p.id">
                        <li class="flex items-center justify-between px-6 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900" x-text="p.name"></p>
                                <p class="truncate text-xs text-gray-400" x-text="p.sku || '—'"></p>
                            </div>
                            <span class="ml-3 shrink-0 rounded-md px-2 py-1 text-xs font-semibold"
                                  :class="p.stock <= 0 ? 'bg-danger-50 text-danger-600' : 'bg-warning-50 text-warning-600'">
                                <span x-text="p.stock <= 0 ? 'Habis' : ('Sisa ' + p.stock)"></span>
                                <span class="font-normal opacity-70" x-text="'/ min ' + p.min_stock"></span>
                            </span>
                        </li>
                    </template>
                </ul>
            </x-ui.card>

            {{-- Pelanggan loyal --}}
            <x-ui.card title="Pelanggan Loyal" padding="p-0">
                <x-slot:actions>
                    <span class="text-xs text-gray-400">berdasarkan total belanja</span>
                </x-slot:actions>

                <div x-show="loading" class="flex items-center justify-center py-12">
                    <x-ui.loading-spinner size="lg" />
                </div>

                <template x-if="!loading && loyalCustomers.length === 0">
                    <p class="px-6 py-10 text-center text-sm text-gray-400">Belum ada data pelanggan</p>
                </template>

                <ul x-show="!loading && loyalCustomers.length > 0" class="divide-y divide-gray-100">
                    <template x-for="(c, i) in loyalCustomers" :key="c.id">
                        <li class="flex items-center gap-3 px-6 py-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-600" x-text="i + 1"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900" x-text="c.name"></p>
                                <p class="text-xs text-gray-400" x-text="c.transactions + 'x transaksi'"></p>
                            </div>
                            <span class="ml-3 shrink-0 text-sm font-semibold text-gray-900" x-text="window.rupiah(c.total_spent)"></span>
                        </li>
                    </template>
                </ul>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
