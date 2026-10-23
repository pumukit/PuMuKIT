import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { resource: String, count: Number, url: String };

    connect() {
        if (document.querySelectorAll(`.admin-pic-${this.resourceValue}`).length !== this.countValue) {
            $(`#pic_mms_${this.resourceValue}`).load(this.urlValue);
        }
    }
}
