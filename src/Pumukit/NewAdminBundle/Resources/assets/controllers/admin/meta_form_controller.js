import { Controller } from '@hotwired/stimulus';
import { KeywordsInput, keywordPool } from '../../keywords.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { remember: String, prefix: String, always: Boolean, keywords: Object, dates: Object, title: Object };

    connect() {
        this.initKeywords();
    }

    disconnect() {
        this.destroyKeywords();
    }

    changed(event) {
        if (!event.target.id?.startsWith(this.prefixValue)) {
            return;
        }
        if (this.alwaysValue || 'checkbox' === event.target.type || event.target.value !== event.target.defaultValue) {
            $(this.rememberValue).css('display', 'inline-block');
        }
    }

    hideRemember() {
        $(this.rememberValue).hide();
    }

    checkDates(event) {
        const { record, publication, message, block } = this.datesValue;
        if (!record) {
            return;
        }
        const recordDate = new Date($(record).val());
        const publicationDate = new Date($(publication).val());
        if (publicationDate.getTime() < recordDate.getTime()) {
            window.no_success(message);
            if (block) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }
    }

    saved() {
        const { source, target } = this.titleValue;
        const title = $(source);
        if (title.length) {
            $(target).val(title.val());
        }
    }

    cancel(event) {
        event.preventDefault();
        this.element.reset();
        this.destroyKeywords();
        this.initKeywords();
        this.hideRemember();
    }

    initKeywords() {
        this.keywordInputs = Object.entries(this.keywordsValue).flatMap(([type, url]) => [...this.element.querySelectorAll(`input.${type}.materialtags`)].map((input) => new KeywordsInput(input, keywordPool(url))));
    }

    destroyKeywords() {
        (this.keywordInputs ?? []).forEach((keywords) => keywords.destroy());
        this.keywordInputs = [];
    }
}
