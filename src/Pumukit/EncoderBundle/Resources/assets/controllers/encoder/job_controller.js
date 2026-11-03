import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['priority', 'priorityValue', 'priorityInput'];
    static values = {
        id: String,
        token: String,
        updateUrl: String,
        retryUrl: String,
        errorUrl: String,
        deleteUrl: String,
    };

    togglePriority(event) {
        event.preventDefault();
        this.priorityTargets.forEach((element) => element.classList.toggle('pmk-display-none'));
    }

    async savePriority(event) {
        event.preventDefault();
        const job = await this.send(this.updateUrlValue, 'POST', { priority: this.priorityInputTarget.value });
        if (job) {
            this.priorityValueTarget.textContent = job.priority;
            this.togglePriority(event);
        }
    }

    retry(event) {
        event.preventDefault();
        this.send(this.retryUrlValue, 'POST');
    }

    stop(event) {
        event.preventDefault();
        this.send(this.errorUrlValue, 'POST');
    }

    remove(event) {
        event.preventDefault();
        this.send(this.deleteUrlValue, 'DELETE');
    }

    async send(url, method, data = {}) {
        try {
            const response = await fetch(url, {
                method,
                body: new URLSearchParams({ jobId: this.idValue, _token: this.tokenValue, ...data }),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            return response.ok ? await response.json() : null;
        } catch {
            return null;
        } finally {
            window.dispatchEvent(new CustomEvent('encoder:changed'));
        }
    }
}
