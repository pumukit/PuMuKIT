import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { domain: String };

    connect() {
        const cookie = this.getCookie(this.domainValue);
        $(this.element).toggle(cookie === null || cookie.indexOf('full') === -1);
    }

    accept(event) {
        event.preventDefault();
        document.cookie = `${this.domainValue}=full; path=/`;
        $(this.element).hide();
    }

    getCookie(name) {
        const nameEQ = `${name}=`;
        for (let cookie of document.cookie.split(';')) {
            cookie = cookie.trimStart();
            if (cookie.indexOf(nameEQ) === 0) {
                return cookie.substring(nameEQ.length);
            }
        }

        return null;
    }
}
