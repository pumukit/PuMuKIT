import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { activateUrl: String, deactivateUrl: String, token: String };

    async toggle() {
        const url = this.element.checked ? this.deactivateUrlValue : this.activateUrlValue;
        this.element.disabled = true;
        let ok = false;
        try {
            const response = await fetch(url, { method: 'POST', body: new URLSearchParams({ _token: this.tokenValue }), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            ok = response.ok;
        } catch {
            ok = false;
        }
        if (!ok) {
            this.element.checked = !this.element.checked;
            this.element.disabled = false;
        }
        window.dispatchEvent(new CustomEvent('encoder:changed'));
    }
}
