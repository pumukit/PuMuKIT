import { Controller } from '@hotwired/stimulus';

const BUSY_INTERVAL = 10000;
const IDLE_INTERVAL = 60000;
const FILTER_BLOCKS = {
    show_waiting: ['pending'],
    show_paused: ['pending'],
    show_error: ['executed'],
    show_finished: ['executed'],
};

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['block'];
    static values = { url: String, busy: Boolean };

    connect() {
        this.modalOpen = false;
        this.html = new Map();

        this.onVisibility = () => (document.hidden ? this.stop() : this.refresh());
        this.onModalShow = () => {
            this.modalOpen = true;
            this.stop();
        };
        this.onModalHidden = () => {
            this.modalOpen = false;
            this.schedule();
        };
        this.onChanged = () => this.refresh();
        this.onClick = (event) => this.paginate(event);

        document.addEventListener('visibilitychange', this.onVisibility);
        document.addEventListener('show.bs.modal', this.onModalShow);
        document.addEventListener('hidden.bs.modal', this.onModalHidden);
        window.addEventListener('encoder:changed', this.onChanged);
        this.element.addEventListener('click', this.onClick);

        this.schedule();
    }

    disconnect() {
        this.stop();
        document.removeEventListener('visibilitychange', this.onVisibility);
        document.removeEventListener('show.bs.modal', this.onModalShow);
        document.removeEventListener('hidden.bs.modal', this.onModalHidden);
        window.removeEventListener('encoder:changed', this.onChanged);
        this.element.removeEventListener('click', this.onClick);
    }

    schedule() {
        this.stop();
        if (this.modalOpen || document.hidden) {
            return;
        }
        this.timeout = window.setTimeout(() => this.refresh(), this.busyValue ? BUSY_INTERVAL : IDLE_INTERVAL);
    }

    stop() {
        window.clearTimeout(this.timeout);
    }

    async refresh(names = this.blockTargets.map((element) => element.dataset.block)) {
        this.stop();
        await Promise.all(names.map((name) => this.load(name)));
        this.schedule();
    }

    async load(name) {
        const element = this.blockTargets.find((target) => target.dataset.block === name);
        if (!element) {
            return;
        }
        try {
            const response = await fetch(this.urlValue.replace('__block__', name) + window.location.search, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                return;
            }
            this.busyValue = '1' === response.headers.get('X-Encoder-Busy');
            const html = await response.text();
            if (this.html.get(name) !== html) {
                this.html.set(name, html);
                element.innerHTML = html;
            }
        } catch {
            this.html.delete(name);
        }
    }

    filter(event) {
        const query = new URLSearchParams(window.location.search);
        query.set(event.target.id, event.target.checked ? '1' : '0');
        this.replaceQuery(query);
        this.refresh(FILTER_BLOCKS[event.target.id] ?? []);
    }

    paginate(event) {
        const link = event.target.closest('.pagination a[href]');
        const block = link?.closest('[data-block]');
        if (!block || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey) {
            return;
        }
        event.preventDefault();
        this.replaceQuery(new URL(link.href, window.location.href).searchParams);
        this.refresh([block.dataset.block]);
    }

    replaceQuery(query) {
        const search = query.toString();
        window.history.replaceState(null, '', window.location.pathname + (search ? `?${search}` : ''));
    }
}
