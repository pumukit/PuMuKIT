import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { url: String, interval: { type: Number, default: 15000 } };

    connect() {
        this.timer = window.setInterval(() => this.refresh(), this.intervalValue);
    }

    disconnect() {
        window.clearInterval(this.timer);
    }

    refresh() {
        document.querySelectorAll('.pmk-job-animate').forEach((job) => {
            const id = job.parentElement?.dataset.id;
            if (id) {
                $(`#table_mms_status_wrapper_${id}`).load(this.urlValue.replace('__id__', id));
            }
        });
    }
}
