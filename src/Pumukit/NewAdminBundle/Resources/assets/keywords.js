const LIMIT = 5;
const TTL = 1800000;
const pools = new Map();

class KeywordPool {
    constructor(url) {
        this.url = url;
        this.words = [];
        this.loaded = this.load();
    }

    async load() {
        const cached = this.cached();
        if (cached) {
            this.words = cached;

            return;
        }
        try {
            const response = await fetch(this.url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const data = await response.json();
            this.words = Object.values(data).map((item) => String(item?._id ?? '')).filter((word) => '' !== word);
            this.store();
        } catch {
            this.words = [];
        }
    }

    cached() {
        try {
            const entry = JSON.parse(localStorage.getItem(this.key()) ?? 'null');

            return entry && Date.now() - entry.time < TTL ? entry.words : null;
        } catch {
            return null;
        }
    }

    store() {
        try {
            localStorage.setItem(this.key(), JSON.stringify({ time: Date.now(), words: this.words }));

            return true;
        } catch {
            return false;
        }
    }

    key() {
        return `pumukit.keywords.${this.url}`;
    }

    add(word) {
        if (!this.words.includes(word)) {
            this.words.push(word);
        }
    }

    match(query, exclude) {
        const tokens = query.toLowerCase().split(/\s+/).filter((token) => '' !== token);
        if (0 === tokens.length) {
            return [];
        }

        return this.words
            .filter((word) => !exclude.includes(word))
            .filter((word) => {
                const parts = word.toLowerCase().split(/\s+/);

                return tokens.every((token) => parts.some((part) => part.startsWith(token)));
            })
            .slice(0, LIMIT);
    }
}

export function keywordPool(url) {
    if (!pools.has(url)) {
        pools.set(url, new KeywordPool(url));
    }

    return pools.get(url);
}

export class KeywordsInput {
    constructor(element, pool) {
        this.element = element;
        this.pool = pool;
        this.items = [];
        this.cursor = -1;
        this.build();
        this.split(element.value).forEach((word) => this.add(word, false));
        this.element.value = this.items.join(',');
    }

    build() {
        this.container = document.createElement('div');
        this.container.className = 'materialize-tags';
        this.wrapper = document.createElement('span');
        this.wrapper.className = 'keywords-input';
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'n-tag';
        this.input.placeholder = this.element.getAttribute('placeholder') ?? '';
        this.input.setAttribute('autocomplete', 'off');
        this.menu = document.createElement('div');
        this.menu.className = 'tt-dropdown-menu';
        this.menu.hidden = true;
        this.wrapper.append(this.input, this.menu);
        this.container.append(this.wrapper);
        this.element.style.display = 'none';
        this.element.before(this.container);

        this.container.addEventListener('click', (event) => {
            if (event.target.closest('[data-role="remove"]')) {
                this.remove(event.target.closest('.chip').dataset.item);

                return;
            }
            if (!this.element.disabled) {
                this.input.focus();
            }
        });
        this.input.addEventListener('focus', () => this.container.classList.add('active'));
        this.input.addEventListener('blur', () => {
            this.container.classList.remove('active');
            this.add(this.input.value);
            this.clear();
        });
        this.input.addEventListener('input', () => this.suggest());
        this.input.addEventListener('keydown', (event) => this.keydown(event));
        this.menu.addEventListener('mousedown', (event) => {
            const suggestion = event.target.closest('.tt-suggestion');
            if (suggestion) {
                event.preventDefault();
                this.add(suggestion.textContent);
                this.clear();
            }
        });
    }

    keydown(event) {
        const suggestions = [...this.menu.children];
        if ('ArrowDown' === event.key || 'ArrowUp' === event.key) {
            if (0 !== suggestions.length) {
                event.preventDefault();
                this.highlight(((this.cursor + 1 + ('ArrowDown' === event.key ? 1 : -1) + suggestions.length + 1) % (suggestions.length + 1)) - 1);
            }

            return;
        }
        if ('Escape' === event.key) {
            this.close();

            return;
        }
        if ('Backspace' === event.key && '' === this.input.value && 0 !== this.items.length) {
            this.remove(this.items[this.items.length - 1]);

            return;
        }
        const confirm = 'Enter' === event.key || ',' === event.key || ('Tab' === event.key && '' !== this.input.value);
        if (!confirm) {
            return;
        }
        event.preventDefault();
        this.add(-1 !== this.cursor && suggestions[this.cursor] ? suggestions[this.cursor].textContent : this.input.value);
        this.clear();
    }

    suggest() {
        const matches = this.pool.match(this.input.value.trim(), this.items);
        this.menu.replaceChildren(...matches.map((word) => {
            const suggestion = document.createElement('div');
            suggestion.className = 'tt-suggestion';
            suggestion.textContent = word;

            return suggestion;
        }));
        this.cursor = -1;
        this.menu.hidden = 0 === matches.length;
    }

    highlight(index) {
        this.cursor = index;
        [...this.menu.children].forEach((suggestion, position) => suggestion.classList.toggle('tt-cursor', position === index));
    }

    close() {
        this.menu.hidden = true;
        this.menu.replaceChildren();
        this.cursor = -1;
    }

    clear() {
        this.input.value = '';
        this.close();
    }

    split(value) {
        return String(value ?? '').split(',').map((word) => word.trim()).filter((word) => '' !== word);
    }

    add(value, notify = true) {
        const words = this.split(value);
        if (0 === words.length) {
            return;
        }
        words.forEach((word) => {
            if (this.items.includes(word)) {
                $(this.chip(word)).hide().fadeIn();

                return;
            }
            this.items.push(word);
            const chip = document.createElement('span');
            chip.className = 'chip';
            chip.dataset.item = word;
            const remove = document.createElement('i');
            remove.className = 'chip-remove fa-solid fa-xmark';
            remove.dataset.role = 'remove';
            chip.append(word, remove);
            this.wrapper.before(chip, ' ');
            this.pool.add(word);
        });
        if (notify) {
            this.push();
        }
    }

    remove(word) {
        const chip = this.chip(word);
        if (!chip || this.element.disabled) {
            return;
        }
        if (chip.nextSibling?.nodeType === Node.TEXT_NODE) {
            chip.nextSibling.remove();
        }
        chip.remove();
        this.items = this.items.filter((item) => item !== word);
        this.push();
    }

    chip(word) {
        return [...this.container.querySelectorAll('.chip')].find((chip) => chip.dataset.item === word);
    }

    push() {
        this.element.value = this.items.join(',');
        this.element.dispatchEvent(new Event('change', { bubbles: true }));
    }

    destroy() {
        this.container.remove();
        this.element.style.display = '';
    }
}
