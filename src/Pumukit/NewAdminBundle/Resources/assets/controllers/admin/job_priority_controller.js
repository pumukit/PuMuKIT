import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['show', 'edit', 'value', 'input'];
    static values = { url: String, job: String };

    toggle(event) {
        event?.preventDefault();
        $(this.showTarget).toggle();
        $(this.editTarget).toggle();
    }

    save(event) {
        event.preventDefault();
        $.post(this.urlValue, { jobId: this.jobValue, priority: this.inputTarget.value }).done((response) => {
            this.valueTarget.textContent = response.priority;
            this.toggle();
        });
    }
}
