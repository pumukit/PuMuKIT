import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'submit', 'error'];
    static values = { checkUrl: String };

    check() {
        const folderName = this.inputTarget.value;
        $.post(this.checkUrlValue, { folder: folderName }).done((data) => {
            const valid = data || folderName === '';
            this.submitTarget.disabled = !valid;
            $(this.errorTarget).toggle(!valid);
        });
    }
}
