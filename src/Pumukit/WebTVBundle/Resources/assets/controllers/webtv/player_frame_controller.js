import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { cinema: Boolean };

    connect() {
        if (this.cinemaValue && !this.element.parentElement.matches('.main-content')) {
            $(this.element).detach().prependTo('.main-content');
            return;
        }

        this.onResize = () => this.resize();
        $(window).on('resize', this.onResize);
        this.resize();

        if (document.readyState === 'complete') {
            this.element.focus();
        } else {
            $(window).one('load', () => this.element.focus());
        }
    }

    disconnect() {
        if (this.onResize) {
            $(window).off('resize', this.onResize);
        }
    }

    resize() {
        $(this.element).height($(this.element).width() / 16 * 9);
    }
}
