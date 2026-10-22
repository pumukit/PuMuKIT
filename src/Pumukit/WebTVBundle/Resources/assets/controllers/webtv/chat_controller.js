import { Controller } from '@hotwired/stimulus';
import { trans } from '@pumukit/translator';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['body', 'list', 'nameGroup', 'name', 'nameError', 'message'];
    static values = { postUrl: String, listUrl: String, interval: Number };

    connect() {
        this.update();
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    keypress(event) {
        if ((event.keyCode || event.which) === 13) {
            event.preventDefault();
            this.post();
        }
    }

    submit(event) {
        event.preventDefault();
        this.post();
    }

    post() {
        if (this.nameTarget.value === '') {
            $(this.nameErrorTarget).show();
            return;
        }

        $(this.nameErrorTarget).hide();
        $(this.nameGroupTarget).hide();
        $.ajax({
            url: this.postUrlValue,
            type: 'POST',
            data: { method: 'POST', name: this.nameTarget.value, message: this.messageTarget.value },
        }).done((data) => {
            if (data !== '' && data.message === 'Successful') {
                this.messageTarget.value = '';
                this.update();
            }
        });
        this.messageTarget.value = '';
    }

    update() {
        clearTimeout(this.timer);
        $(this.listTarget).load(this.listUrlValue, (response, status) => {
            if (status === 'error') {
                $('#chat-list-error').html(trans('Sorry but there was an error')).show();
            } else {
                this.bodyTarget.scrollTop = this.bodyTarget.scrollHeight - this.bodyTarget.clientHeight;
            }
        });
        this.timer = setTimeout(() => this.update(), this.intervalValue);
    }
}
