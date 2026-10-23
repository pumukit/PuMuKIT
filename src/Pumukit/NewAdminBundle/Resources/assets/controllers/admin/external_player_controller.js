import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['frame', 'url'];

    connect() {
        this.onResize = () => this.resize();
        $(window).on('resize', this.onResize);
    }

    disconnect() {
        $(window).off('resize', this.onResize);
    }

    resize() {
        $(this.frameTarget).height($(this.frameTarget).width() / 16 * 9);
    }

    preview(event) {
        event.preventDefault();
        this.frameTarget.setAttribute('src', this.urlTarget.value);
    }

    submit(event) {
        event.preventDefault();
        const form = event.currentTarget;
        $('#myModal').modal('hide');
        $.ajax({ url: form.action, type: 'POST', data: $(form).serialize() }).done(() => window.location.reload());
    }
}
