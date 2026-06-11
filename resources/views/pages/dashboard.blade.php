<x-layouts.app title="Dashboard">
    <div
        x-data="{
            loading: true,
            error: null,
            stats: { total_products: 0, total_customers: 0, today_sales: 0, low_stock_count: 0 },
            salesChart: null,
            topChart: null,
            async load() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await window.api.get('/api/dashboard');
                    const d = res.data;
                    this.stats = d.stats;
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
                grad.addColorStop(0, 'rgba(20,184,166,0.25)');
                grad.addColorStop(1, 'rgba(20,184,166,0)');
                this.salesChart?.destroy();
                this.salesChart = new window.Chart(ctx, {
                    type: 'line',
                    data: { labels: payload.labels, datasets: [{
                        label: 'Penjualan', data: payload.data,
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

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Produk</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : stats.total_products"></p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><x-heroicon-o-cube class="h-6 w-6" /></span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Pelanggan</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : stats.total_customers"></p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 text-gray-600"><x-heroicon-o-users class="h-6 w-6" /></span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Penjualan Hari Ini</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900" x-text="loading ? '…' : window.rupiah(stats.today_sales)"></p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-warning-50 text-warning-600"><x-heroicon-o-banknotes class="h-6 w-6" /></span>
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
            <x-ui.card title="Penjualan 7 Hari Terakhir" class="lg:col-span-2">
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
    </div>
</x-layouts.app>
