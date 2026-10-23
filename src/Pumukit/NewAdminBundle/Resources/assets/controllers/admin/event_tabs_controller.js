import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { listUrl: String, calendarUrl: String };

    connect() {
        $(this.element).on('show.bs.tab.eventTabs', (event) => this.shown(event));
        const last = localStorage.getItem('lastEventTab');
        if (last) {
            $(this.element).find(`a[href="#${last}"]`).tab('show');
        }
    }

    disconnect() {
        $(this.element).off('show.bs.tab.eventTabs');
    }

    shown(event) {
        const tab = event.target.href.split('#')[1];
        localStorage.setItem('lastEventTab', tab);
        $.ajax({ url: 'calEvent' === tab ? this.calendarUrlValue : this.listUrlValue, type: 'GET' });
    }
}
