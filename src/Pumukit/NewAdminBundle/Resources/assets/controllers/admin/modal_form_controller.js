import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        target: String,
        successMessage: String,
        errorMessage: String,
        failMessage: String,
        titleError: Boolean,
        refresh: Array,
        created: Object,
        modal: { type: String, default: '#myModal' },
        disableSubmit: Boolean,
    };

    submit(event) {
        event.preventDefault();
        const form = this.element;
        if (this.disableSubmitValue) {
            $(form).find(':submit').prop('disabled', true);
        }
        if (this.modalValue) {
            $(this.modalValue).modal('hide');
        }

        $.ajax({ url: form.action, method: 'POST', data: $(form).serialize() })
            .done((response, textStatus, xhr) => {
                if (this.errorMessageValue && -1 !== xhr.responseText.indexOf('modal-header')) {
                    window.no_success(this.errorMessageValue);

                    return;
                }
                if (this.createdValue.key) {
                    this.loadCreated(response[this.createdValue.key]);
                } else if (this.targetValue) {
                    $(this.targetValue).html(response);
                }
                this.refreshValue.forEach(({ target, url }) => $(target).load(url));
                if (this.successMessageValue) {
                    window.success(this.successMessageValue);
                }
                this.dispatch('saved', { detail: { response } });
            })
            .fail((xhr, textStatus, errorThrown) => window.no_success(this.failure(xhr, textStatus, errorThrown)));
    }

    loadCreated(id) {
        const { list, listUrl, preview, showUrl } = this.createdValue;
        $(list).load(listUrl.replace('__id__', id), { _method: 'POST' }, () => {
            if (preview) {
                $(preview).load(showUrl.replace('__id__', id));
            }
        });
    }

    failure(xhr, textStatus, errorThrown) {
        if (this.failMessageValue) {
            return this.failMessageValue;
        }
        if (this.titleErrorValue) {
            const title = xhr.responseText.split('<title>').pop().split('</title>').shift().replace(/(\r\n|\n|\r)/gm, '');

            return `${errorThrown}: ${textStatus}, ${title}`;
        }

        return `${errorThrown}: ${xhr.responseText || textStatus}`;
    }
}
