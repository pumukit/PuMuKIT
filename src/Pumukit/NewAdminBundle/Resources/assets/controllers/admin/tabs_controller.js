import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { key: String };

    connect() {
        $(this.element).on('show.bs.tab.adminTabs', (event) => localStorage.setItem(this.keyValue, event.target.href.split('#')[1]));
        const last = localStorage.getItem(this.keyValue);
        if (last) {
            $(this.element).find(`a[href="#${last}"]`).tab('show');
        }
    }

    disconnect() {
        $(this.element).off('show.bs.tab.adminTabs');
    }
}
