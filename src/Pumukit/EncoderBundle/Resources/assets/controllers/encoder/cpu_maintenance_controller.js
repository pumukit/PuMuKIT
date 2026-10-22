import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { activateUrl: String, deactivateUrl: String };

    toggle() {
        const url = this.element.checked ? this.deactivateUrlValue : this.activateUrlValue;
        $.get(url).done(() => window.location.reload());
    }
}
