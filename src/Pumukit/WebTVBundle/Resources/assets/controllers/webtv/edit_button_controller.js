import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { url: String };

    connect() {
        $.get(this.urlValue).done((response) => {
            $(this.element).html(response);
        });
    }
}
