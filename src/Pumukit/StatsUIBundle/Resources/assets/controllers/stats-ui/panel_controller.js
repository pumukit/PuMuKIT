import { Controller } from '@hotwired/stimulus';
import { CHANGE_EVENT } from '../../stats.js';

const DEFAULT_PRESET = 'today';
const MAX_DAYS = 731;

const iso = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');

const addDays = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);

function presetRange(preset, today) {
    switch (preset) {
        case 'today':
            return [today, today];
        case 'yesterday':
            return [addDays(today, -1), addDays(today, -1)];
        case 'last30':
            return [addDays(today, -29), today];
        case 'last7':
            return [addDays(today, -6), today];
        case 'last90':
            return [addDays(today, -89), today];
        case 'thisMonth':
            return [new Date(today.getFullYear(), today.getMonth(), 1), today];
        case 'lastMonth':
            return [new Date(today.getFullYear(), today.getMonth() - 1, 1), new Date(today.getFullYear(), today.getMonth(), 0)];
        case 'thisYear':
            return [new Date(today.getFullYear(), 0, 1), today];
        case 'last12':
            return [new Date(today.getFullYear() - 1, today.getMonth(), today.getDate() + 1), today];
        default:
            return [today, today];
    }
}

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['preset', 'from', 'to'];

    connect() {
        const query = new URLSearchParams(window.location.search);
        if (query.get('from') && query.get('to')) {
            this.presetTarget.value = query.get('preset') ?? '';
            this.apply(query.get('from'), query.get('to'), false);
        } else {
            this.presetTarget.value = query.get('preset') ?? DEFAULT_PRESET;
            this.preset();
        }
    }

    preset() {
        if (!this.presetTarget.value) {
            this.fromTarget.focus();

            return;
        }
        const [from, to] = presetRange(this.presetTarget.value, new Date());
        this.apply(iso(from), iso(to));
    }

    custom() {
        if (!this.fromTarget.value || !this.toTarget.value || this.fromTarget.value > this.toTarget.value) {
            return;
        }
        this.presetTarget.value = '';
        const [year, month, day] = this.toTarget.value.split('-').map(Number);
        const earliest = iso(addDays(new Date(year, month - 1, day), -(MAX_DAYS - 1)));
        this.apply(this.fromTarget.value < earliest ? earliest : this.fromTarget.value, this.toTarget.value);
    }

    apply(from, to, updateUrl = true) {
        this.fromTarget.value = from;
        this.toTarget.value = to;
        this.element.dataset.statsFrom = from;
        this.element.dataset.statsTo = to;

        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('from', from);
            url.searchParams.set('to', to);
            if (this.presetTarget.value) {
                url.searchParams.set('preset', this.presetTarget.value);
            } else {
                url.searchParams.delete('preset');
            }
            window.history.replaceState({}, '', url);
        }

        window.dispatchEvent(new CustomEvent(CHANGE_EVENT, { detail: { from, to } }));
    }
}
