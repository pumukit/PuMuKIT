import { Controller } from '@hotwired/stimulus';
import { COLORS, fetchReport, panel, showState, viewsLabel } from '../../stats.js';

const locale = document.documentElement.lang || navigator.language || 'en';
const dayNames = Array.from({ length: 7 }, (_, index) => new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(new Date(2024, 0, 1 + index)));
const longDayNames = Array.from({ length: 7 }, (_, index) => new Intl.DateTimeFormat(locale, { weekday: 'long' }).format(new Date(2024, 0, 1 + index)));

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['grid', 'state'];
    static values = { messages: Object };

    connect() {
        if (panel(this.element).dataset.statsFrom) {
            this.load();
        }
    }

    async load() {
        const request = (this.request ?? 0) + 1;
        this.request = request;
        this.element.classList.add('stats-loading');
        try {
            const heatmap = await fetchReport(this.element, 'heatmap');
            if (request !== this.request) {
                return;
            }
            this.render(heatmap);
        } catch {
            this.gridTarget.replaceChildren();
            showState(this.stateTarget, this.messagesValue.loadError);
        } finally {
            this.element.classList.remove('stats-loading');
        }
    }

    render(heatmap) {
        const table = document.createElement('table');
        table.className = 'stats-heatmap-table';

        const head = document.createElement('tr');
        head.append(document.createElement('th'));
        for (let hour = 0; hour < 24; hour++) {
            const cell = document.createElement('th');
            cell.scope = 'col';
            cell.textContent = 0 === hour % 3 ? String(hour).padStart(2, '0') : '';
            head.append(cell);
        }
        const thead = document.createElement('thead');
        thead.append(head);

        const tbody = document.createElement('tbody');
        heatmap.cells.forEach((hours, day) => {
            const row = document.createElement('tr');
            const label = document.createElement('th');
            label.scope = 'row';
            label.textContent = dayNames[day];
            row.append(label);
            hours.forEach((views, hour) => {
                const cell = document.createElement('td');
                const level = views > 0 && heatmap.max > 0 ? Math.min(COLORS.heat.length - 1, Math.floor((views / heatmap.max) * COLORS.heat.length)) : -1;
                if (level >= 0) {
                    cell.style.backgroundColor = COLORS.heat[level];
                }
                cell.title = `${longDayNames[day]} ${String(hour).padStart(2, '0')}:00 · ${viewsLabel(this.messagesValue, views)}`;
                row.append(cell);
            });
            tbody.append(row);
        });
        table.append(thead, tbody);

        const legend = document.createElement('div');
        legend.className = 'stats-heatmap-legend';
        legend.append(this.messagesValue.less);
        [null, ...COLORS.heat].forEach((color) => {
            const swatch = document.createElement('span');
            swatch.className = 'stats-heatmap-swatch';
            if (color) {
                swatch.style.backgroundColor = color;
            }
            legend.append(swatch);
        });
        legend.append(this.messagesValue.more);

        this.gridTarget.replaceChildren(table, legend);
        showState(this.stateTarget, heatmap.max > 0 ? null : this.messagesValue.empty);
    }
}
