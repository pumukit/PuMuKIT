import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { modal: { type: String, default: '#myModal' } };

    connect() {
        this.remove = () => this.element.remove();
        $(this.modalValue).one('hide.bs.modal', this.remove);
    }

    disconnect() {
        $(this.modalValue).off('hide.bs.modal', this.remove);
    }
}
