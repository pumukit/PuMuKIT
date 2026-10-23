import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { remember: String, prefix: String, always: Boolean, keywords: Object, dates: Object, title: Object };

    connect() {
        this.initKeywords();
    }

    disconnect() {
        $(this.element).find('input.materialtags').materialtags('destroy');
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
        $(this.element).find('input.materialtags').materialtags('destroy');
        this.initKeywords();
        this.hideRemember();
    }

    initKeywords() {
        Object.entries(this.keywordsValue).forEach(([type, url]) => {
            const inputs = $(this.element).find(`input.${type}.materialtags`);
            if (0 === inputs.length) {
                return;
            }
            const index = this.index(type, url);
            inputs.materialtags({ typeaheadjs: { name: 'keywords', displayKey: '_id', valueKey: '_id', source: index.ttAdapter() } });
            inputs.off('itemAdded.metaForm').on('itemAdded.metaForm', (event) => index.add({ _id: event.item }));
        });
    }

    index(type, url) {
        const name = `${type}keywords`;
        if (!window[name]) {
            window[name] = new window.Bloodhound({
                datumTokenizer: window.Bloodhound.tokenizers.obj.whitespace('_id'),
                queryTokenizer: window.Bloodhound.tokenizers.whitespace,
                prefetch: { url, ttl: 1800000 },
            });
            window[name].initialize();
        }

        return window[name];
    }
}
