@props([
    'labels' => [],
    'data' => [],
    'height' => 300,
])

{{-- Grafik penjualan (line). Diinisialisasi setelah Chart.js tersedia. --}}
<div
    x-data="{
        chart: null,
        labels: @js($labels),
        data: @js($data),
        init() {
            this.$nextTick(() => this.render());
        },
        render() {
            if (! window.Chart) return;
            const ctx = this.$refs.canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, {{ $height }});
            gradient.addColorStop(0, window.posColors.primaryRgba(0.25));
            gradient.addColorStop(1, window.posColors.primaryRgba(0));

            this.chart = new window.Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.labels,
                    datasets: [{
                        label: 'Penjualan',
                        data: this.data,
                        borderColor: window.posColors.primary,
                        backgroundColor: gradient,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: window.posColors.primary,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (c) => 'Rp ' + c.parsed.y.toLocaleString('id-ID'),
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (v) => 'Rp ' + (v / 1000) + 'k' },
                            grid: { color: '#f1f5f9' },
                        },
                        x: { grid: { display: false } },
                    },
                },
            });
        },
    }"
    style="height: {{ $height }}px"
>
    <canvas x-ref="canvas"></canvas>
</div>
