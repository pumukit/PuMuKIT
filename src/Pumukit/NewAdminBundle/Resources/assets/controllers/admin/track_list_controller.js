import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { refresh: Array, poll: Array, delay: { type: Number, default: 8000 }, status: Object, errorMessage: String };

    connect() {
        this.refreshValue.forEach(({ target, url }) => $(target).load(url));
        if (0 === this.pollValue.length) {
            return;
        }
        if (this.statusValue.target) {
            $(this.statusValue.target).html(this.statusValue.html);
        }
        this.timer = setTimeout(() => this.pollValue.forEach(({ target, url }) => $(target).load(url)), this.delayValue);
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    extractPic(event) {
        event.preventDefault();
        const { url, mm } = event.params;
        const loader = $(`#pic_mms_${mm}_load`);
        loader.show();
        $.ajax({ url, type: 'POST' })
            .done((response) => {
                loader.hide();
                $(`#pic_mms_${mm}`).html(response);
            })
            .fail(() => {
                loader.hide();
                window.no_success(this.errorMessageValue);
            });
    }
}
