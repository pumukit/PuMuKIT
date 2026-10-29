import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['add', 'search', 'searchList', 'searchLoader', 'url', 'urlList', 'urlLoader', 'wrongUrl', 'videos', 'videosLoader', 'noVideos'];
    static values = { urls: Object, pages: Number };

    connect() {
        this.page = 0;
        this.loadingVideos = false;
        if (this.hasVideosTarget) {
            this.moreVideos();
        }
    }

    tab(event) {
        const form = this.element.querySelector(`#${event.currentTarget.getAttribute('aria-controls')} form.modal_form`);
        this.addTarget.disabled = !this.hasChecked(form);
    }

    changed(event) {
        this.addTarget.disabled = !this.hasChecked(event.currentTarget);
    }

    add() {
        const ids = [...this.element.querySelectorAll('.tab-pane.active fieldset input[type="checkbox"]')].filter((input) => input.checked).map((input) => input.value);
        $('#list_playlistmms').load(this.urlsValue.add.replace('__ids__', encodeURIComponent(JSON.stringify(ids))), { _method: 'POST' });
    }

    search() {
        const list = $(this.searchListTarget);
        $(this.searchLoaderTarget).show();
        list.hide();
        list.load(this.urlsValue.search.replace('__search__', encodeURI(this.searchTarget.value)), () => {
            $(this.searchLoaderTarget).hide();
            list.show();
        });
    }

    searchKey(event) {
        if ('Enter' === event.key) {
            this.search();
        }
    }

    findUrl() {
        let id = this.urlTarget.value;
        if (!/^\w*$/.test(id)) {
            const match = /video\/(\w*)\b/.exec(id);
            if (!match) {
                $(this.urlLoaderTarget).hide();
                $(this.urlListTarget).hide();
                $(this.wrongUrlTarget).show();

                return;
            }
            id = match[1];
        }
        $(this.wrongUrlTarget).hide();
        $(this.urlLoaderTarget).show();
        $(this.urlListTarget).hide();
        $(this.urlListTarget).load(this.urlsValue.url.replace('__id__', id), () => {
            $(this.urlLoaderTarget).hide();
            $(this.urlListTarget).show();
            this.addTarget.disabled = true;
            $(this.element).find('#url .mmobj_elem').removeClass('checked');
        });
    }

    urlPlayerLoaded() {
        this.addTarget.disabled = false;
        $(this.element).find('#url .mmobj_elem').addClass('checked');
    }

    resize() {
        const player = $(this.element).find('#paellaiframe');
        player.height((player.width() / 16) * 9);
    }

    moreVideos() {
        const list = this.videosTarget;
        if (!this.loadingVideos && this.page < this.pagesValue && list.offsetHeight + list.scrollTop >= list.scrollHeight) {
            this.loadingVideos = true;
            this.page += 1;
            $(this.videosLoaderTarget).show();
            $.get(this.urlsValue.videos.replace('__page__', this.page)).done((data) => {
                $(list).append(data);
                $(this.videosLoaderTarget).hide();
                this.loadingVideos = false;
            });
        }
        if (this.pagesValue < 1) {
            $(this.videosLoaderTarget).hide();
            $(this.noVideosTarget).show();
        }
    }

    hasChecked(form) {
        return !!form && [...form.querySelectorAll('input[type="checkbox"]')].some((input) => input.checked);
    }
}
