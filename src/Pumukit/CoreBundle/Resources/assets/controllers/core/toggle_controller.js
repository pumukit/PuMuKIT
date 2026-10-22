import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { selector: String };

    toggle() {
        $(this.selectorValue).toggle();
    }
}
