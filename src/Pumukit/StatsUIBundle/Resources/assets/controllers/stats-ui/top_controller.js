import { Controller } from '@hotwired/stimulus';
import { fetchReport, link, numberFormat, panel, showState } from '../../stats.js';

const LIMIT = 5;

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['body', 'state'];
    static values = { report: String, link: String, messages: Object };

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
            const top = await fetchReport(this.element, this.reportValue, { limit: LIMIT });
            if (request !== this.request) {
                return;
            }
            this.render(top);
        } catch {
            this.bodyTarget.replaceChildren();
            showState(this.stateTarget, this.messagesValue.loadError);
        } finally {
            this.element.classList.remove('stats-loading');
        }
    }

    render(top) {
        this.bodyTarget.replaceChildren(...top.items.map((item) => {
            const row = document.createElement('tr');

            const title = document.createElement('td');
            title.className = 'stats-top-title';
            const anchor = document.createElement('a');
            anchor.href = link(this.element, this.linkValue, item.id);
            anchor.textContent = item.title || this.messagesValue.untitled;
            anchor.title = anchor.textContent;
            title.append(anchor);

            const views = document.createElement('td');
            views.className = 'text-end stats-top-views';
            views.textContent = numberFormat.format(item.views);

            row.append(title, views);

            return row;
        }));

        this.element.querySelector('table').hidden = 0 === top.items.length;
        showState(this.stateTarget, top.items.length ? null : this.messagesValue.empty);
    }
}
