import { Controller } from '@hotwired/stimulus';
import { CategoryScale, Chart, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip } from 'chart.js';
import { COLORS, fetchReport, formatBucket, numberFormat, panel, showState, viewsLabel } from '../../stats.js';

Chart.register(CategoryScale, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['canvas', 'state'];
    static values = { messages: Object };

    connect() {
        if (panel(this.element).dataset.statsFrom) {
            this.load();
        }
    }

    disconnect() {
        this.chart?.destroy();
    }

    async load() {
        const request = (this.request ?? 0) + 1;
        this.request = request;
        this.element.classList.add('panel-loading');
        try {
            const timeline = await fetchReport(this.element, 'timeline');
            if (request !== this.request) {
                return;
            }
            this.render(timeline);
            showState(this.stateTarget, timeline.points.some((point) => point.views > 0) ? null : this.messagesValue.empty);
        } catch {
            showState(this.stateTarget, this.messagesValue.loadError);
        } finally {
            this.element.classList.remove('panel-loading');
        }
    }

    render(timeline) {
        const labels = timeline.points.map((point) => formatBucket(point.date, timeline.interval));
        const previousLabels = timeline.points.map((point) => (point.previousDate && 'hour' !== timeline.interval ? formatBucket(point.previousDate, timeline.interval) : ''));
        const datasets = [
            {
                label: this.messagesValue.current,
                data: timeline.points.map((point) => point.views),
                borderColor: COLORS.current,
                backgroundColor: 'rgba(42, 120, 214, 0.08)',
                borderWidth: 2,
                fill: 'origin',
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHitRadius: 12,
                tension: 0.25,
            },
            {
                label: this.messagesValue.previousPeriod,
                data: timeline.points.map((point) => point.previousViews),
                borderColor: COLORS.previous,
                borderWidth: 2,
                borderDash: [6, 4],
                fill: false,
                pointRadius: 0,
                pointHoverRadius: 4,
                pointHitRadius: 12,
                tension: 0.25,
            },
        ];

        if (this.chart) {
            this.chart.data.labels = labels;
            this.chart.data.datasets = datasets;
            this.chart.options.plugins.tooltip.callbacks.title = (items) => this.tooltipTitle(items, labels, previousLabels);
            this.chart.update();

            return;
        }

        this.chart = new Chart(this.canvasTarget, {
            type: 'line',
            data: { labels, datasets },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: COLORS.grid },
                        ticks: { color: COLORS.muted, maxRotation: 0, autoSkipPadding: 16 },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: COLORS.grid },
                        border: { display: false },
                        ticks: { color: COLORS.muted, precision: 0, callback: (value) => numberFormat.format(value) },
                    },
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { color: COLORS.ink, boxWidth: 24, boxHeight: 2, usePointStyle: false },
                    },
                    tooltip: {
                        callbacks: {
                            title: (items) => this.tooltipTitle(items, labels, previousLabels),
                            label: (item) => ` ${item.dataset.label}: ${viewsLabel(this.messagesValue, item.parsed.y)}`,
                        },
                    },
                },
            },
        });
    }

    tooltipTitle(items, labels, previousLabels) {
        const index = items[0]?.dataIndex ?? 0;

        return previousLabels[index] ? `${labels[index]} · ${previousLabels[index]}` : labels[index];
    }
}
