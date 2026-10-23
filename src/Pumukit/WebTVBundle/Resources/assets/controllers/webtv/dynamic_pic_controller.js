import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { dynamic: String, static: String };

    disconnect() {
        clearTimeout(this.timeout);
    }

    show() {
        this.swap(this.dynamicValue);
    }

    hide() {
        this.swap(this.staticValue);
    }

    swap(src) {
        if (!src) {
            return;
        }
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.element.src = src;
        }, 300);
    }
}
