import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        const current = this.element.querySelector('.current');
        if (current) {
            this.element.scrollTop = current.offsetTop - 200;
        }
    }
}
