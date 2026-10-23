import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        deleteUrl: String,
        checkUrl: String,
        name: String,
        deleteMessage: String,
        canDelete: Boolean,
        messages: Object,
    };

    removeResource(event) {
        event.preventDefault();
        const { url, action, target, count, confirm, success, button } = event.params;
        if (!window.confirm(confirm)) {
            return;
        }
        this.send(url, { _method: 'DELETE', action }, target, success, () => {
            if (1 === Number(count)) {
                $(button).attr('disabled', 'disabled');
            }
        });
    }

    removeAll(event) {
        event.preventDefault();
        event.stopPropagation();
        const { url, target, confirm, success } = event.params;
        if (!window.confirm(confirm)) {
            return;
        }
        const button = event.currentTarget;
        this.send(url, { _method: 'DELETE' }, target, success, () => $(button).attr('disabled', 'disabled'));
    }

    send(url, data, target, message, done) {
        $.ajax({ url, type: 'POST', data })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                $(target).html(response);
                done();
                window.success(message);
            })
            .done(() => this.refresh());
    }

    refresh() {
        $.ajax({ url: this.checkUrlValue, type: 'GET' })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                if (!this.element.isConnected) {
                    return;
                }
                this.nameValue = response.groupName;
                this.deleteMessageValue = response.deleteMessage;
                this.canDeleteValue = Boolean(Number(response.canbedeleted));
            });
    }

    deleteGroup(event) {
        event.preventDefault();
        if (!this.canDeleteValue) {
            window.alert(this.deleteMessageValue);

            return;
        }
        if (!window.confirm(this.deleteMessageValue)) {
            return;
        }
        $('#myModal').modal('hide');
        $('#list_groups').load(this.deleteUrlValue, { _method: 'DELETE' }, (responseText, textStatus, xhr) => {
            if (xhr.status >= 400) {
                window.no_success(`${this.messagesValue.error} "${this.nameValue}": ${responseText}`);
            } else {
                window.success(`${this.messagesValue.group} "${this.nameValue}" ${this.messagesValue.deleted}`);
            }
        });
    }
}
