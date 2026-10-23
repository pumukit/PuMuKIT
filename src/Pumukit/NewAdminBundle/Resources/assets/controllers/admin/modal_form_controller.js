import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        target: String,
        successMessage: String,
        errorMessage: String,
        refresh: Array,
        modal: { type: String, default: '#myModal' },
        disableSubmit: Boolean,
    };

    submit(event) {
        event.preventDefault();
        const form = this.element;
        if (this.disableSubmitValue) {
            $(form).find(':submit').prop('disabled', true);
        }
        $(this.modalValue).modal('hide');

        $.ajax({ url: form.action, method: 'POST', data: $(form).serialize() })
            .done((response, textStatus, xhr) => {
                if (this.errorMessageValue && -1 !== xhr.responseText.indexOf('modal-header')) {
                    window.no_success(this.errorMessageValue);

                    return;
                }
                if (this.targetValue) {
                    $(this.targetValue).html(response);
                }
                this.refreshValue.forEach(({ target, url }) => $(target).load(url));
                if (this.successMessageValue) {
                    window.success(this.successMessageValue);
                }
            })
            .fail((xhr, textStatus, errorThrown) => {
                window.no_success(`${errorThrown}: ${xhr.responseText || textStatus}`);
            });
    }
}
