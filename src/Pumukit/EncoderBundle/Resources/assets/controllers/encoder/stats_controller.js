import { Controller } from '@hotwired/stimulus';

const RELOAD_INTERVAL = 30000;

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.scheduleReload();
        $(document.body)
            .on('shown.bs.modal.encoderStats loaded.bs.modal.encoderStats', '.pmk-modal', () => clearTimeout(this.timeout))
            .on('hidden.bs.modal.encoderStats', '.pmk-modal', () => this.scheduleReload());
    }

    disconnect() {
        clearTimeout(this.timeout);
        $(document.body).off('.encoderStats');
    }

    scheduleReload() {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => window.location.reload(), RELOAD_INTERVAL);
    }

    filter(event) {
        const query = new URLSearchParams(window.location.search);
        query.set(event.target.id, event.target.checked ? '1' : '0');
        window.location.href = `${window.location.pathname}?${query}`;
    }
}
