import { Controller } from '@hotwired/stimulus';
import { BarController, BarElement, CategoryScale, Chart, LinearScale, Tooltip } from 'chart.js';
import { trans } from '@pumukit/translator';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

const PAGE_SIZE = 10;
const MOST_VIEWED_COLOR = '#ED6D00';
const VIEWS_COLOR = '#1f77b4';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [
        'title',
        'from',
        'to',
        'preset',
        'totalViews',
        'totalObjects',
        'mostViewedTitle',
        'mostViewedBody',
        'mostViewedChart',
        'preview',
        'previewTemplate',
        'pager',
        'viewsBody',
        'viewsTotal',
        'viewsChart',
        'group',
    ];

    static values = {
        mode: String,
        id: String,
        locale: String,
        page: Number,
        urls: Object,
    };

    connect() {
        this.groupBy = 'month';
        this.charts = {};
        this.requests = {};
        const { fromDate, toDate } = this.defaultRange();
        this.fromTarget.value = fromDate;
        this.toTarget.value = toDate;
        this.load();
    }

    disconnect() {
        Object.values(this.charts).forEach((chart) => chart.destroy());
        this.charts = {};
    }

    preset(event) {
        const range = this.presetRange(event.currentTarget.value);
        if (!range) {
            return;
        }
        this.fromTarget.value = range.fromDate;
        this.toTarget.value = range.toDate;
        this.setPage(0);
        this.load();
    }

    dates() {
        this.presetTarget.value = '';
        if ('' === this.fromTarget.value || '' === this.toTarget.value || this.fromTarget.value > this.toTarget.value) {
            return;
        }
        this.setPage(0);
        this.load();
    }

    presetRange(preset) {
        const today = new Date();
        const day = (offset) => new Date(today.getFullYear(), today.getMonth(), today.getDate() + offset);
        const ranges = {
            yesterday: [day(-1), day(-1)],
            last7: [day(-6), today],
            last30: [day(-29), today],
            thisMonth: [new Date(today.getFullYear(), today.getMonth(), 1), new Date(today.getFullYear(), today.getMonth() + 1, 0)],
            lastMonth: [new Date(today.getFullYear(), today.getMonth() - 1, 1), new Date(today.getFullYear(), today.getMonth(), 0)],
            thisYear: [new Date(today.getFullYear(), 0, 1), today],
            all: [new Date(1970, 0, 1), today],
        };
        if (!ranges[preset]) {
            return null;
        }
        const [from, to] = ranges[preset];

        return { fromDate: this.isoDate(from), toDate: this.isoDate(to) };
    }

    search(event) {
        event.preventDefault();
        this.setPage(0);
        this.load();
    }

    reset(event) {
        event.preventDefault();
        window.location.href = window.location.href.split('?')[0];
    }

    group(event) {
        event.preventDefault();
        this.groupBy = event.params.group;
        this.groupTargets.forEach((button) => {
            button.classList.toggle('active-button', button.getAttribute('data-stats-ui--stats-group-param') === this.groupBy);
        });
        this.loadViews();
    }

    goToPage(event) {
        event.preventDefault();
        this.setPage(event.params.page);
        this.loadMostViewed();
    }

    setPage(page) {
        this.pageValue = page;
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.history.pushState({}, '', url);
    }

    load() {
        this.loadMostViewed();
        this.loadViews();
    }

    async loadMostViewed() {
        const { criteria, fromDate, toDate } = this.filters();
        const page = this.pageValue;
        const params = { ...this.criteriaParams(criteria), from_date: fromDate, to_date: toDate, page, limit: PAGE_SIZE };

        if (this.modeValue === 'series') {
            const response = await this.request('mostViewed', this.urlsValue.seriesMostViewed, { ...params, sort: -1 });
            if (response) {
                this.renderMostViewedSeries(response, page);
            }
        } else if (this.modeValue === 'mmobj') {
            const response = await this.request('mostViewed', this.urlsValue.mmobjMostViewed, params);
            if (response) {
                this.renderMostViewedMultimediaObjects(response, page, true);
            }
        } else if (this.modeValue === 'series-id') {
            const response = await this.request('mostViewed', this.urlsValue.mmobjMostViewed, { ...params, 'criteria[series]': this.idValue, sort: -1 });
            if (response) {
                this.renderMostViewedMultimediaObjects(response, page, false);
            }
        } else {
            const response = await this.request('mostViewed', this.urlsValue.multimediaObjects, { ...params, 'criteria[id]': this.idValue, sort: -1 });
            if (response) {
                this.renderMultimediaObject(response.mmobjs[0], page, fromDate, toDate);
            }
        }
    }

    async loadViews() {
        const { criteria, fromDate, toDate } = this.filters();
        const params = { ...this.criteriaParams(criteria), from_date: fromDate, to_date: toDate, page: 0, group_by: this.groupBy, sort: 1 };
        let response;

        if (this.modeValue === 'series-id') {
            response = await this.request('views', this.urlsValue.viewsSeries, { ...params, series: this.idValue });
        } else if (this.modeValue === 'mmobj-id') {
            response = await this.request('views', this.urlsValue.viewsMmobj, { ...params, mmobj: this.idValue });
        } else {
            response = await this.request('views', this.urlsValue.views, params);
        }

        if (response) {
            this.renderViews(response.views);
        }
    }

    renderMostViewedSeries(response, page) {
        this.setTotalObjects(response.total);
        const rows = response.series.map((item, index) => {
            const videos = this.cell('videos', 0);
            this.countSeriesVideos(item.series.id, videos);

            return this.row(page * PAGE_SIZE + index + 1, [
                this.cell('title', this.link(this.url('seriesStats', item.series.id), this.i18n(item.series.i18nTitle))),
                videos,
                this.cell('views', item.num_viewed),
            ]);
        });

        this.fillMostViewed(rows, page, ['title', 'videos', 'views']);
        this.renderPager(response);
        this.renderMostViewedChart(response.series.map((item) => [this.i18n(item.series.i18nTitle), item.num_viewed]));
    }

    renderMostViewedMultimediaObjects(response, page, linkSeries) {
        this.setTotalObjects(response.total);
        const rows = response.mmobjs.map((item, index) => {
            const mmobj = item.mmobj;
            const series = linkSeries ? this.link(this.url('seriesStats', this.seriesId(mmobj)), mmobj.seriesTitle) : mmobj.seriesTitle;

            return this.row(page * PAGE_SIZE + index + 1, [
                this.cell('title', this.link(this.url('mmobjStats', mmobj.id), this.i18n(mmobj.i18nTitle))),
                this.cell('recording', this.formatDateTime(mmobj.recordDate)),
                this.cell('series', series),
                this.cell('views', item.num_viewed),
            ]);
        });

        this.fillMostViewed(rows, page, ['title', 'recording', 'series', 'views']);
        this.renderPager(response);
        this.renderMostViewedChart(response.mmobjs.map((item) => [this.i18n(item.mmobj.i18nTitle), item.num_viewed]));
    }

    renderMultimediaObject(mmobj, page, fromDate, toDate) {
        if (!mmobj) {
            this.mostViewedBodyTarget.replaceChildren();
            return;
        }

        const views = this.cell('views', 0);
        this.countMultimediaObjectViews(fromDate, toDate, views);
        this.mostViewedBodyTarget.replaceChildren(
            this.row(page * PAGE_SIZE + 1, [
                this.cell('title', this.i18n(mmobj.i18nTitle)),
                this.cell('recording', this.formatDateTime(mmobj.recordDate)),
                this.cell('series', this.link(this.url('seriesStats', this.seriesId(mmobj)), mmobj.seriesTitle)),
                views,
            ]),
        );
        this.renderPreview(mmobj);
        this.mostViewedTitleTarget.textContent = mmobj.seriesTitle ?? '';
    }

    renderPreview(mmobj) {
        const title = this.i18n(mmobj.i18nTitle);
        const subtitle = this.i18n(mmobj.i18nSubtitle);
        const card = this.previewTemplateTarget.content.cloneNode(true);

        card.querySelector('a').href = this.url('mmobjPortal', mmobj.id);
        const image = card.querySelector('img');
        if (mmobj.pics?.length) {
            image.src = mmobj.pics[0].url;
        } else {
            image.remove();
        }
        card.querySelector('.title').title = title;
        card.querySelector('.mmobj_thumbnail_title').textContent = title;
        card.querySelector('abbr').title = subtitle;
        card.querySelector('abbr').textContent = subtitle;
        card.querySelector('.date').textContent = this.formatDateTime(mmobj.recordDate);

        this.previewTarget.replaceChildren(card);
    }

    renderViews(views) {
        const total = views.reduce((sum, view) => sum + view.numView, 0);
        this.viewsBodyTarget.replaceChildren(
            ...views.map((view) => {
                const tr = document.createElement('tr');
                const date = this.cell('date', this.formatPeriod(view._id, 'long'));
                const count = this.cell('views', view.numView);
                date.classList.add('stats-half');
                count.classList.add('stats-half', 'text-end');
                tr.append(date, count);

                return tr;
            }),
        );
        if (this.hasTotalViewsTarget) {
            this.totalViewsTarget.textContent = total;
        }
        this.viewsTotalTarget.textContent = total;

        const labels = views.map((view) => this.formatPeriod(view._id, 'short'));
        this.renderChart('views', this.viewsChartTarget, {
            type: 'bar',
            data: { labels, datasets: [{ data: views.map((view) => view.numView), backgroundColor: VIEWS_COLOR }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 500 },
                scales: {
                    x: { ticks: { autoSkip: true, maxRotation: 0 } },
                    y: { beginAtZero: true, ticks: { callback: (value) => this.compact(value) } },
                },
            },
        });
    }

    renderMostViewedChart(items) {
        if (!this.hasMostViewedChartTarget) {
            return;
        }

        const labels = items.map(([label]) => label);
        this.renderChart('mostViewed', this.mostViewedChartTarget, {
            type: 'bar',
            data: { labels, datasets: [{ data: items.map(([, value]) => value), backgroundColor: MOST_VIEWED_COLOR }] },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 500 },
                scales: {
                    x: { beginAtZero: true, ticks: { callback: (value) => this.compact(value) } },
                    y: { ticks: { callback: (value, index) => this.truncate(labels[index]) } },
                },
            },
        });
    }

    renderChart(name, canvas, config) {
        this.charts[name]?.destroy();
        this.charts[name] = new Chart(canvas, config);
    }

    renderPager(response) {
        const pages = Math.ceil(response.total / PAGE_SIZE);
        const active = Number(response.page);
        const list = document.createElement('ul');
        list.className = 'pagination';

        list.append(this.pageItem(trans('Previous'), active > 0 ? active - 1 : null));
        this.pageRange(pages, active).forEach((page) => {
            const item = this.pageItem(page === '...' ? page : page + 1, page === '...' ? null : page, false);
            if (page === active) {
                item.classList.add('current-page');
            }
            list.append(item);
        });
        list.append(this.pageItem(trans('Next'), active < pages - 1 ? active + 1 : null));

        this.pagerTarget.replaceChildren(list);
    }

    pageItem(text, page, disableWhenEmpty = true) {
        const item = document.createElement('li');
        const link = document.createElement('a');
        item.className = 'page-item';
        link.className = 'page-link';
        link.textContent = text;
        if (page === null && disableWhenEmpty) {
            item.classList.add('disabled');
        } else if (page !== null) {
            link.href = '#';
            link.setAttribute('data-action', 'stats-ui--stats#goToPage');
            link.setAttribute('data-stats-ui--stats-page-param', page);
        }
        item.append(link);

        return item;
    }

    pageRange(pages, page) {
        if (pages <= 6) {
            return Array.from({ length: pages }, (value, index) => index);
        }
        if (page > 3 && page < pages - 3) {
            return [0, '...', page - 1, page, page + 1, '...', pages - 1];
        }
        if (page <= 3) {
            return [0, 1, 2, 3, 4, '...', pages - 1];
        }

        return [0, '...', pages - 3, pages - 2, pages - 1];
    }

    fillMostViewed(rows, page, headers) {
        for (let index = rows.length; index < PAGE_SIZE; index++) {
            rows.push(this.row(page * PAGE_SIZE + index + 1, headers.map((header) => this.cell(header, ''))));
        }
        this.mostViewedBodyTarget.replaceChildren(...rows);
    }

    async countSeriesVideos(seriesId, cell) {
        const response = await this.request(null, this.urlsValue.multimediaObjects, { 'criteria[series]': seriesId, sort: -1 });
        if (response) {
            cell.textContent = response.total;
        }
    }

    async countMultimediaObjectViews(fromDate, toDate, cell) {
        const response = await this.request(null, this.urlsValue.viewsMmobj, { mmobj: this.idValue, from_date: fromDate, to_date: toDate, sort: -1 });
        if (response) {
            cell.textContent = response.views.reduce((sum, view) => sum + view.numView, 0);
        }
    }

    async request(name, url, params) {
        const id = name ? (this.requests[name] = (this.requests[name] ?? 0) + 1) : null;
        const query = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                query.set(key, value);
            }
        });

        try {
            const response = await fetch(`${url}?${query}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                return null;
            }
            const data = await response.json();

            return name && id !== this.requests[name] ? null : data;
        } catch {
            return null;
        }
    }

    filters() {
        const fromDate = this.fromTarget.value;
        const toDate = this.toTarget.value;
        const range = fromDate && toDate ? { fromDate, toDate } : this.defaultRange();

        return { criteria: this.hasTitleTarget ? this.titleTarget.value.trim() : '', ...range };
    }

    criteriaParams(criteria) {
        return criteria ? { 'criteria[$text][$search]': criteria } : {};
    }

    defaultRange() {
        const today = new Date();

        return { fromDate: `${today.getFullYear()}-01-01`, toDate: this.isoDate(today) };
    }

    setTotalObjects(total) {
        if (this.hasTotalObjectsTarget) {
            this.totalObjectsTarget.textContent = total;
        }
    }

    row(ranking, cells) {
        const tr = document.createElement('tr');
        tr.append(this.cell('ranking', ranking), ...cells);

        return tr;
    }

    cell(header, content) {
        const td = document.createElement('td');
        td.setAttribute('headers', header);
        td.append(content instanceof Node ? content : String(content ?? ''));

        return td;
    }

    link(href, text) {
        const a = document.createElement('a');
        a.href = href;
        a.textContent = text ?? '';

        return a;
    }

    url(name, id) {
        return this.urlsValue[name].replace('__ID__', encodeURIComponent(id));
    }

    seriesId(mmobj) {
        return String(mmobj.series ?? '').split('/').pop();
    }

    i18n(values) {
        return values?.[this.localeValue] ?? '';
    }

    isDetail() {
        return this.modeValue.endsWith('-id');
    }

    truncate(label = '') {
        return label.length > 25 ? `${label.slice(0, 24)}…` : label;
    }

    compact(value) {
        return new Intl.NumberFormat(this.localeValue, { notation: 'compact' }).format(value);
    }

    formatPeriod(period, style) {
        const [year, month, day] = String(period).split('-').map(Number);
        if (day) {
            const date = new Date(year, month - 1, day);

            return style === 'long'
                ? new Intl.DateTimeFormat(this.localeValue, { day: '2-digit', month: 'long', year: 'numeric' }).format(date)
                : date.toLocaleDateString(this.localeValue);
        }
        if (month) {
            return style === 'long'
                ? new Intl.DateTimeFormat(this.localeValue, { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1))
                : `${month}/${year}`;
        }

        return String(year);
    }

    formatDateTime(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        const pad = (number) => String(number).padStart(2, '0');

        return `${this.isoDate(date)} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
    }

    isoDate(date) {
        const pad = (number) => String(number).padStart(2, '0');

        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }
}
