import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { urls: Object, messages: Object };

    create(event) {
        event.preventDefault();
        $('#series_style_msg').hide();
        $.ajax({ url: this.urlsValue.create, type: 'POST', data: $(event.currentTarget).serialize() })
            .done((response) => {
                this.reloadList();
                $('#style_show').load(this.urlsValue.show.replace('__ID__', response.id));
                window.success(this.messagesValue.created);
            })
            .fail(() => window.no_success(this.messagesValue.createError));
    }

    show(event) {
        event.preventDefault();
        $('#series_style_msg').hide();
        $('#style_show').load(this.urlsValue.show.replace('__ID__', event.params.id));
    }

    remove(event) {
        event.preventDefault();
        $.ajax({ url: event.params.url, type: 'POST' })
            .done((response) => {
                this.reloadList();
                $('#style_show').load(this.urlsValue.empty);
                if ('error' === response[0]) {
                    window.no_success(response.msg);
                } else {
                    window.success(response.msg);
                }
            })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseJSON?.msg ?? textStatus}`));
    }

    save(event) {
        event.preventDefault();
        $.ajax({ url: this.urlsValue.edit, type: 'POST', data: $(event.currentTarget).serialize() })
            .done((response) => {
                if ('error' === response[0]) {
                    window.no_success(this.messagesValue.editError);
                } else {
                    window.success(this.messagesValue.created);
                }
            })
            .fail(() => window.no_success(this.messagesValue.editError));
    }

    reloadList() {
        $('#style_list').load(this.urlsValue.list);
    }
}
