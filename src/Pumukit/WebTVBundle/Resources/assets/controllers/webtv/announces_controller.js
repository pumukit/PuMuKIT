import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['list', 'loading', 'empty'];
    static values = { url: String };

    connect() {
        const now = new Date();
        this.month = now.getMonth() + 1;
        this.year = now.getFullYear();
        this.monthLoaded = true;

        this.onScroll = () => {
            if ($(window).scrollTop() + window.innerHeight + $('footer').height() >= $(document).height()) {
                this.load(false);
            }
        };
        $(window).on('scroll', this.onScroll);

        this.load(true);
    }

    disconnect() {
        $(window).off('scroll', this.onScroll);
    }

    load(showEmpty) {
        if (!this.monthLoaded) {
            return;
        }

        this.monthLoaded = false;
        $(this.loadingTarget).show();
        $.get(this.urlValue, { date: `${this.month}/${this.year}` }).done((data, textStatus, response) => {
            $(this.loadingTarget).hide();
            if (response.getResponseHeader('X-Date') !== '---') {
                this.month = response.getResponseHeader('X-Date-Month') - 1;
                this.year = response.getResponseHeader('X-Date-Year');
                if (this.month === -1) {
                    this.month = 11;
                    this.year -= 1;
                }
                $(this.listTarget).append(data);
                this.monthLoaded = true;
                if ($('.main-content').height() <= $(window).height()) {
                    this.load(false);
                }
            } else if (showEmpty && this.hasEmptyTarget) {
                this.emptyTarget.hidden = false;
            }
        });
    }
}
