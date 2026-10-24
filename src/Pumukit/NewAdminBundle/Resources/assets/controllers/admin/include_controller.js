import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { src: String };

    connect() {
        $(this.element).load(this.srcValue);
    }
}
