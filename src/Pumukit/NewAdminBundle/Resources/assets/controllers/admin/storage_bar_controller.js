import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { percent: Number };

    connect() {
        this.element.style.width = `${this.percentValue}%`;
    }
}
