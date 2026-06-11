@props([
    'labels' => [],
    'data' => [],
    'height' => 300,
])

{{-- Grafik produk terlaris (doughnut). --}}
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
            const palette = [
                window.posColors.primary,
                window.posColors.warning,
                window.posColors.danger,
                window.posColors.primaryLight,
                window.posColors.gray,
            ];
            this.chart = new window.Chart(this.$refs.canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: this.labels,
                    datasets: [{
                        data: this.data,
                        backgroundColor: this.labels.map((_, i) => palette[i % palette.length]),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 14, font: { size: 11 } },
                        },
                        tooltip: {
                            callbacks: {
                                label: (c) => ' ' + c.label + ': ' + c.parsed + ' terjual',
                            },
                        },
                    },
                },
            });
        },
    }"
    style="height: {{ $height }}px"
>
    <canvas x-ref="canvas"></canvas>
</div>
