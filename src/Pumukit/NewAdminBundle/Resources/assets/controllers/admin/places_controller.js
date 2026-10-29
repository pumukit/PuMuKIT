import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { urls: Object, messages: Object, spinner: String };

    children(event) {
        event.preventDefault();
        const { id, title } = event.params;
        $('.name_of_children').text(title);
        this.loadInto('.children-list', this.url('children', id));
    }

    preview(event) {
        event.preventDefault();
        this.loadInto('.preview-precinct', this.url('preview', event.params.id));
    }

    remove(event) {
        event.preventDefault();
        const { id, parent, success } = event.params;
        if (!window.confirm(this.messagesValue.confirm)) {
            return;
        }
        $.ajax({ url: this.url('delete', id), type: 'POST' })
            .done(() => {
                if (parent) {
                    $('.children-list').load(this.url('children', parent));
                }
                $('.parent-list').load(this.urlsValue.parent);
                window.success(success);
            })
            .fail(() => window.no_success(this.messagesValue.deleteError));
    }

    batch(event) {
        event.preventDefault();
        const select = event.currentTarget;
        const { name, parent, success } = event.params;
        const ids = $(`.${name}_checkbox:checked`).map((index, input) => input.id).get();
        if (0 === ids.length || 'delete_selected' !== select.value || !window.confirm(this.messagesValue.confirm)) {
            return;
        }
        $.ajax({ url: this.urlsValue.batchDelete.replace('__ids__', JSON.stringify(ids)), type: 'POST', data: { _method: 'DELETE' } })
            .done(() => {
                $('.parent-list').load(this.urlsValue.parent);
                if (parent) {
                    $('.children-list').load(this.url('children', parent));
                }
                window.success(success);
            })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${this.messagesValue.withChildren} ${xhr.responseJSON?.status ?? ''}`));
    }

    loadInto(selector, url) {
        $(selector).html($('<tr class="text-center pmk-font-size-12"><td colspan="8"><img class="center"></td></tr>').find('img').attr('src', this.spinnerValue).end());
        $('html,body').scrollTop(0);
        $.ajax({ url, type: 'GET' })
            .done((response) => $(selector).html(response))
            .fail(() => window.no_success(this.messagesValue.loadError));
    }

    url(name, id) {
        return this.urlsValue[name].replace('__id__', id);
    }
}
