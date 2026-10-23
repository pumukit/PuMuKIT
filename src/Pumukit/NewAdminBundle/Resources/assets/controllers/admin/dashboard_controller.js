import { Controller } from '@hotwired/stimulus';

const HOUR_BUCKETS = [
    { start: 0, label: '00–04' },
    { start: 4, label: '04–08' },
    { start: 8, label: '08–12' },
    { start: 12, label: '12–16' },
    { start: 16, label: '16–20' },
    { start: 20, label: '20–24' },
];

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { activity: Array, range: Object, groupBy: String, messages: Object };

    connect() {
        const numberFormat = new Intl.NumberFormat(navigator.language || 'en-US');
        const bucket = (start) => HOUR_BUCKETS.find((item) => item.start === start);
        const points = this.activityValue.map((row) => ({
            x: row.date,
            y: row.hour + 2,
            r: Math.max(4, Math.min(22, Math.sqrt(row.count) * 5)),
            bucketStart: row.hour,
            count: row.count,
        }));
        this.chart = new window.Chart(this.element.getContext('2d'), {
            type: 'bubble',
            data: {
                datasets: [{
                    label: this.messagesValue.dataset,
                    data: points,
                    backgroundColor: 'rgba(59, 139, 186, 0.55)',
                    borderColor: 'rgba(59, 139, 186, 1)',
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        min: this.rangeValue.from,
                        max: this.rangeValue.to,
                        time: { unit: ['year', 'month'].includes(this.groupByValue) ? this.groupByValue : 'day' },
                        grid: { display: false },
                    },
                    y: {
                        min: 0,
                        max: 24,
                        ticks: { stepSize: 4, callback: (value) => bucket(value)?.label ?? '' },
                        title: { display: true, text: this.messagesValue.hour },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: ([item]) => `${item.raw.x} · ${bucket(item.raw.bucketStart) ? `${bucket(item.raw.bucketStart).label} h` : ''}`,
                            label: (context) => `${numberFormat.format(context.raw.count)} ${this.messagesValue.unit}`,
                        },
                    },
                },
            },
        });
    }

    disconnect() {
        this.chart?.destroy();
    }
}
