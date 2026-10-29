import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['form', 'message'];

    submit(event) {
        event.preventDefault();
        $.ajax({
            url: this.formTarget.action,
            method: 'POST',
            data: $(this.formTarget).serialize(),
        }).done((data) => {
            $(this.messageTarget)
                .text(data.message)
                .attr('class', data.success ? 'bg-success' : 'bg-warning')
                .show()
                .delay(5000)
                .fadeOut();
            if (data.success) {
                this.formTarget.reset();
                grecaptcha.reset();
            }
        });
    }
}
