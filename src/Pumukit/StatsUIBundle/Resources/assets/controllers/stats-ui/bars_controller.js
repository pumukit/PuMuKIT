import { Controller } from '@hotwired/stimulus';
import { fetchReport, numberFormat, panel, percentFormat, showState, viewsLabel } from '../../stats.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['list', 'state'];
    static values = { report: String, messages: Object };

    connect() {
        if (panel(this.element).dataset.statsFrom) {
            this.load();
        }
    }

    async load() {
        const request = (this.request ?? 0) + 1;
        this.request = request;
        this.element.classList.add('panel-loading');
        try {
            const distribution = await fetchReport(this.element, this.reportValue);
            if (request !== this.request) {
                return;
            }
            this.render(distribution);
        } catch {
            this.listTarget.replaceChildren();
            showState(this.stateTarget, this.messagesValue.loadError);
        } finally {
            this.element.classList.remove('panel-loading');
        }
    }

    render(distribution) {
        const items = distribution.items.filter((item) => item.views > 0).sort((a, b) => b.views - a.views);
        const max = Math.max(1, ...items.map((item) => item.views));

        this.listTarget.replaceChildren(...items.map((item) => {
            const row = document.createElement('li');
            row.className = 'panel-bar';
            row.title = `${this.messagesValue.labels[item.key] ?? item.key}: ${viewsLabel(this.messagesValue, item.views)}`;

            const label = document.createElement('span');
            label.className = 'panel-bar-label';
            label.textContent = this.messagesValue.labels[item.key] ?? item.key;

            const value = document.createElement('span');
            value.className = 'panel-bar-value';
            value.textContent = `${numberFormat.format(item.views)} · ${percentFormat.format(item.share)}`;

            const track = document.createElement('span');
            track.className = 'panel-bar-track';
            const fill = document.createElement('span');
            fill.className = 'panel-bar-fill';
            fill.style.width = `${(item.views / max) * 100}%`;
            track.append(fill);

            row.append(label, value, track);

            return row;
        }));

        showState(this.stateTarget, items.length ? null : this.messagesValue.empty);
    }
}
