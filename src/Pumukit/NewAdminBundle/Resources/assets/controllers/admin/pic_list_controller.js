import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { resource: String, defaultPic: String };

    connect() {
        const first = document.querySelector(`img.pic-${this.resourceValue}.first`);
        const src = first ? first.src : this.defaultPicValue;
        document.querySelectorAll(`img.pic-${this.resourceValue}.preview`).forEach((img) => {
            img.src = src;
        });
    }
}
