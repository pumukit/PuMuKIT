import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['priority', 'priorityValue', 'priorityInput'];
    static values = {
        id: String,
        updateUrl: String,
        retryUrl: String,
        errorUrl: String,
        deleteUrl: String,
    };

    togglePriority(event) {
        event.preventDefault();
        $(this.priorityTargets).toggle();
    }

    savePriority(event) {
        event.preventDefault();
        $.post(this.updateUrlValue, { jobId: this.idValue, priority: this.priorityInputTarget.value }).done((job) => {
            $(this.priorityValueTarget).text(job.priority);
            $(this.priorityTargets).toggle();
        });
    }

    retry(event) {
        this.postAndReload(event, this.retryUrlValue, { jobId: this.idValue });
    }

    stop(event) {
        this.postAndReload(event, this.errorUrlValue, { jobId: this.idValue, _method: 'POST' });
    }

    remove(event) {
        this.postAndReload(event, this.deleteUrlValue, { jobId: this.idValue, _method: 'DELETE' });
    }

    postAndReload(event, url, data) {
        event.preventDefault();
        $.post(url, data).always(() => window.location.reload());
    }
}
