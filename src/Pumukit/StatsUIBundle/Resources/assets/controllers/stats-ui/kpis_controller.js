import { Controller } from '@hotwired/stimulus';
import { fetchReport, numberFormat, panel } from '../../stats.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['views', 'uniqueViews', 'coverage', 'coverageDetail', 'unviewed', 'series'];
    static values = { messages: Object };

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
            const summary = await fetchReport(this.element, 'summary');
            if (request !== this.request) {
                return;
            }
            this.render(summary);
        } catch {
            this.viewsTarget.textContent = '–';
            this.viewsTarget.title = this.messagesValue.loadError;
        } finally {
            this.element.classList.remove('panel-loading');
        }
    }

    render(summary) {
        this.viewsTarget.textContent = numberFormat.format(summary.views);
        this.uniqueViewsTarget.textContent = numberFormat.format(summary.uniqueViews);

        if (this.hasCoverageTarget && null !== summary.publishedObjects) {
            this.coverageTarget.textContent = numberFormat.format(summary.publishedObjects - summary.unviewedObjects);
            this.coverageDetailTarget.textContent = this.messagesValue.coverage.replace('%published%', numberFormat.format(summary.publishedObjects));
        }
        if (this.hasUnviewedTarget && null !== summary.unviewedObjects) {
            this.unviewedTarget.textContent = numberFormat.format(summary.unviewedObjects);
        }
        if (this.hasSeriesTarget) {
            this.seriesTarget.textContent = numberFormat.format(summary.viewedSeries);
        }
    }
}
