let menus = 0;

export function remoteSource(url, map) {
    return async (term, signal) => {
        const target = new URL(url, window.location.href);
        target.searchParams.set('term', term);
        try {
            const response = await fetch(target, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal });
            const data = await response.json();

            return Array.isArray(data) ? data.map(map) : [];
        } catch {
            return [];
        }
    };
}

export class SuggestInput {
    constructor(element, { source, minLength = 1, delay = 250, openOnFocus = false, select = () => {} }) {
        this.element = element;
        this.source = source;
        this.minLength = minLength;
        this.delay = delay;
        this.openOnFocus = openOnFocus;
        this.select = select;
        this.items = [];
        this.cursor = -1;
        this.listeners = new AbortController();
        this.build();
    }

    build() {
        const signal = this.listeners.signal;
        this.menu = document.createElement('ul');
        this.menu.id = `pmk-suggest-${++menus}`;
        this.menu.className = 'dropdown-menu pmk-suggest-menu';
        this.menu.setAttribute('role', 'listbox');
        this.element.after(this.menu);
        this.element.setAttribute('role', 'combobox');
        this.element.setAttribute('aria-autocomplete', 'list');
        this.element.setAttribute('aria-controls', this.menu.id);
        this.element.setAttribute('aria-expanded', 'false');
        this.element.setAttribute('autocomplete', 'off');

        this.element.addEventListener('input', () => this.schedule(), { signal });
        this.element.addEventListener('keydown', (event) => this.keydown(event), { signal });
        this.element.addEventListener('blur', () => this.close(), { signal });
        this.element.addEventListener('focus', () => {
            if (this.openOnFocus) {
                this.search(this.element.value);
            }
        }, { signal });
        this.menu.addEventListener('mousedown', (event) => {
            const option = event.target.closest('[role="option"]');
            event.preventDefault();
            if (option) {
                this.choose(Number(option.dataset.index));
            }
        }, { signal });
    }

    schedule() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.search(this.element.value), this.delay);
    }

    async search(term) {
        clearTimeout(this.timer);
        this.request?.abort();
        if (term.length < this.minLength) {
            this.close();

            return;
        }
        const request = new AbortController();
        this.request = request;
        this.element.classList.add('pmk-suggest-loading');
        const items = await this.source(term, request.signal);
        if (request.signal.aborted) {
            return;
        }
        this.element.classList.remove('pmk-suggest-loading');
        if (document.activeElement !== this.element) {
            return;
        }
        this.render(items);
    }

    render(items) {
        this.items = items;
        this.cursor = -1;
        this.menu.replaceChildren(...items.map((item, index) => {
            const option = document.createElement('li');
            option.id = `${this.menu.id}-${index}`;
            option.className = 'dropdown-item';
            option.dataset.index = String(index);
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.textContent = item.text;
            if (item.description) {
                const description = document.createElement('small');
                description.className = 'd-block text-body-secondary';
                description.textContent = item.description;
                option.append(description);
            }

            return option;
        }));
        if (0 === items.length) {
            this.close();

            return;
        }
        this.menu.style.top = `${this.element.offsetTop + this.element.offsetHeight}px`;
        this.menu.style.left = `${this.element.offsetLeft}px`;
        this.menu.style.width = `${this.element.offsetWidth}px`;
        this.menu.classList.add('show');
        this.element.setAttribute('aria-expanded', 'true');
    }

    keydown(event) {
        const open = this.menu.classList.contains('show');
        if ('ArrowDown' === event.key || 'ArrowUp' === event.key) {
            event.preventDefault();
            if (!open) {
                this.search(this.element.value);

                return;
            }
            const step = 'ArrowDown' === event.key ? 1 : -1;
            this.highlight((this.cursor + step + this.items.length + 1) % (this.items.length + 1));

            return;
        }
        if ('Escape' === event.key && open) {
            event.preventDefault();
            event.stopPropagation();
            this.close();

            return;
        }
        if ('Enter' === event.key && open && -1 !== this.cursor) {
            event.preventDefault();
            this.choose(this.cursor);
        }
    }

    highlight(index) {
        this.cursor = index === this.items.length ? -1 : index;
        [...this.menu.children].forEach((option, position) => {
            option.classList.toggle('active', position === this.cursor);
            option.setAttribute('aria-selected', String(position === this.cursor));
        });
        if (-1 === this.cursor) {
            this.element.removeAttribute('aria-activedescendant');

            return;
        }
        const option = this.menu.children[this.cursor];
        this.element.setAttribute('aria-activedescendant', option.id);
        option.scrollIntoView({ block: 'nearest' });
    }

    choose(index) {
        const item = this.items[index];
        if (!item) {
            return;
        }
        this.element.value = item.value;
        this.close();
        this.select(item);
    }

    close() {
        clearTimeout(this.timer);
        this.request?.abort();
        this.element.classList.remove('pmk-suggest-loading');
        this.items = [];
        this.cursor = -1;
        this.menu.classList.remove('show');
        this.menu.replaceChildren();
        this.element.setAttribute('aria-expanded', 'false');
        this.element.removeAttribute('aria-activedescendant');
    }

    destroy() {
        this.close();
        this.listeners.abort();
        this.menu.remove();
        ['role', 'aria-autocomplete', 'aria-controls', 'aria-expanded'].forEach((attribute) => this.element.removeAttribute(attribute));
    }
}
