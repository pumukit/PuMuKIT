import { Controller } from '@hotwired/stimulus';
import { BarController, BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

const COLORS = { bar: '#2a78d6', muted: '#898781', grid: '#e1e0d9' };
const locale = document.documentElement.lang || navigator.language || 'en';
const numberFormat = new Intl.NumberFormat(locale);
const percentFormat = new Intl.NumberFormat(locale, { style: 'percent', maximumFractionDigits: 0 });
const monthFormat = new Intl.DateTimeFormat(locale, { month: 'short', year: '2-digit' });
const UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

const iso = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');

function formatBytes(bytes) {
    let value = bytes;
    let unit = 0;
    while (value >= 1024 && unit < UNITS.length - 1) {
        value /= 1024;
        unit += 1;
    }

    return `${new Intl.NumberFormat(locale, { maximumFractionDigits: unit > 2 ? 1 : 0 }).format(value)} ${UNITS[unit]}`;
}

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['series', 'multimediaObjects', 'duration', 'views', 'activity', 'activityState', 'status', 'jobs', 'storage', 'storageState'];
    static values = { urls: Object, messages: Object };

    connect() {
        this.load('summary', (data) => this.renderSummary(data), () => this.fail(this.seriesTarget));
        this.load('activity', (data) => this.renderActivity(data), () => this.state(this.activityStateTarget, this.messagesValue.loadError));
        this.load('status', (data) => this.renderCounts(this.statusTarget, data.items, true), () => this.fail(this.statusTarget));
        if (this.urlsValue.views) {
            const today = iso(new Date());
            this.load('views', (data) => { this.viewsTarget.textContent = numberFormat.format(data.views); }, () => this.fail(this.viewsTarget), { from: today, to: today });
        }
        if (this.urlsValue.jobs) {
            this.load('jobs', (data) => this.renderCounts(this.jobsTarget, data.items, false), () => this.fail(this.jobsTarget));
        }
        if (this.urlsValue.storage) {
            this.load('storage', (data) => this.renderStorage(data), () => this.state(this.storageStateTarget, this.messagesValue.loadError));
        }
    }

    disconnect() {
        this.chart?.destroy();
    }

    async load(report, render, onError, parameters = null) {
        const query = parameters ? `?${new URLSearchParams(parameters)}` : '';
        try {
            const response = await fetch(`${this.urlsValue[report]}${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error(`${response.status} ${response.statusText}`);
            }
            render(await response.json());
        } catch {
            onError();
        }
    }

    renderSummary(summary) {
        this.seriesTarget.textContent = numberFormat.format(summary.series);
        this.multimediaObjectsTarget.textContent = numberFormat.format(summary.multimediaObjects);
        this.durationTarget.textContent = this.formatDuration(summary.duration);
    }

    renderActivity(activity) {
        const labels = activity.months.map((month) => {
            const [year, number] = month.month.split('-').map(Number);

            return monthFormat.format(new Date(year, number - 1, 1));
        });
        this.chart = new Chart(this.activityTarget, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data: activity.months.map((month) => month.count),
                    backgroundColor: COLORS.bar,
                    borderRadius: { topLeft: 4, topRight: 4 },
                    maxBarThickness: 32,
                }],
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, border: { color: COLORS.grid }, ticks: { color: COLORS.muted } },
                    y: { beginAtZero: true, grid: { color: COLORS.grid }, border: { display: false }, ticks: { color: COLORS.muted, precision: 0 } },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (item) => ` ${this.messagesValue.videos.replace('%count%', numberFormat.format(item.parsed.y))}`,
                            afterLabel: (item) => ` ${this.formatDuration(activity.months[item.dataIndex].duration)}`,
                        },
                    },
                },
            },
        });
        this.state(this.activityStateTarget, activity.months.some((month) => month.count > 0) ? null : this.messagesValue.empty);
    }

    renderCounts(target, items, withBars) {
        const max = Math.max(1, ...items.map((item) => item.count));
        target.replaceChildren(...items.map((item) => {
            const row = document.createElement('li');
            row.className = withBars ? 'panel-bar' : `panel-count panel-count-${item.key}`;

            const label = document.createElement('span');
            label.className = withBars ? 'panel-bar-label' : 'panel-count-label';
            if ('error' === item.key && item.count > 0) {
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-triangle-exclamation';
                icon.setAttribute('aria-hidden', 'true');
                label.append(icon, ' ');
            }
            label.append(this.messagesValue.labels[item.key] ?? item.key);

            const value = document.createElement('span');
            value.className = withBars ? 'panel-bar-value' : 'panel-count-value';
            value.textContent = numberFormat.format(item.count);
            row.append(label, value);

            if (withBars) {
                const track = document.createElement('span');
                track.className = 'panel-bar-track';
                const fill = document.createElement('span');
                fill.className = 'panel-bar-fill';
                fill.style.width = `${(item.count / max) * 100}%`;
                track.append(fill);
                row.append(track);
            }

            return row;
        }));
    }

    renderStorage(storage) {
        this.storageTarget.replaceChildren(...storage.items.map((item) => {
            const row = document.createElement('li');
            row.className = 'panel-bar';

            const label = document.createElement('span');
            label.className = 'panel-bar-label';
            label.textContent = item.dirs.join(', ');
            label.title = item.dirs.join('\n');

            const value = document.createElement('span');
            value.className = 'panel-bar-value';
            const ratio = item.total > 0 ? item.used / item.total : 0;
            value.textContent = `${this.messagesValue.usedOf.replace('%used%', formatBytes(item.used)).replace('%total%', formatBytes(item.total))} · ${percentFormat.format(ratio)}`;

            const track = document.createElement('span');
            track.className = 'panel-bar-track';
            const fill = document.createElement('span');
            fill.className = ratio >= 0.9 ? 'panel-bar-fill panel-bar-fill-warning' : 'panel-bar-fill';
            fill.style.width = `${ratio * 100}%`;
            track.append(fill);

            row.append(label, value, track);

            return row;
        }));
        this.state(this.storageStateTarget, storage.items.length ? null : this.messagesValue.empty);
    }

    formatDuration(seconds) {
        return this.messagesValue.hours
            .replace('%hours%', numberFormat.format(Math.floor(seconds / 3600)))
            .replace('%minutes%', String(Math.floor((seconds % 3600) / 60)));
    }

    fail(target) {
        target.textContent = '–';
        target.title = this.messagesValue.loadError;
    }

    state(target, message) {
        target.textContent = message ?? '';
        target.hidden = !message;
    }
}
