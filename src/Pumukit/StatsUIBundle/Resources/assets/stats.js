export const COLORS = {
    current: '#2a78d6',
    previous: '#86b6ef',
    ink: '#52514e',
    muted: '#898781',
    grid: '#e1e0d9',
    heat: ['#86b6ef', '#5598e7', '#2a78d6', '#1c5cab', '#104281'],
};

export const CHANGE_EVENT = 'stats-ui:change';

const locale = document.documentElement.lang || navigator.language || 'en';

export const numberFormat = new Intl.NumberFormat(locale);
export const percentFormat = new Intl.NumberFormat(locale, { style: 'percent', maximumFractionDigits: 1 });

export function panel(element) {
    return element.closest('[data-stats-reports]');
}

export async function fetchReport(element, report, parameters = {}) {
    const context = panel(element).dataset;
    const query = new URLSearchParams({ from: context.statsFrom, to: context.statsTo, ...parameters });
    if (context.statsSeries) {
        query.set('series', context.statsSeries);
    }
    if (context.statsMultimediaObject) {
        query.set('multimediaObject', context.statsMultimediaObject);
    }
    const url = JSON.parse(context.statsReports)[report];
    const response = await fetch(`${url}?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (!response.ok) {
        throw new Error(`${response.status} ${response.statusText}`);
    }

    return response.json();
}

export function link(element, type, id) {
    return JSON.parse(panel(element).dataset.statsLinks)[type].replace('__ID__', id);
}


export function viewsLabel(messages, count) {
    return messages.views.replace('%count%', numberFormat.format(count));
}

export function showState(target, message) {
    target.textContent = message ?? '';
    target.hidden = !message;
}

export function formatBucket(date, interval) {
    if ('hour' === interval) {
        return date.slice(11, 16);
    }
    const [year, month, day] = date.split('-').map(Number);
    const value = new Date(year, month - 1, day);
    const options = 'month' === interval ? { month: 'short', year: 'numeric' } : { day: 'numeric', month: 'short' };

    return new Intl.DateTimeFormat(locale, options).format(value);
}
