import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['simpleDate', 'advancedDate', 'year', 'start', 'end'];
    static values = { locale: String };

    connect() {
        const isMobile = $(window).width() <= 480 || Modernizr.touchevents;
        const datePickers = $([this.startTarget, this.endTarget]);
        if (isMobile && Modernizr.inputtypes.date) {
            datePickers.find('input').attr('type', 'date');
            $(this.advancedDateTarget).find('.input-group-addon').remove();
            datePickers.removeClass('input-group');
            return;
        }

        datePickers
            .datetimepicker({
                useCurrent: false,
                viewMode: 'years',
                format: 'YYYY-MM-DD',
                locale: this.localeValue,
            })
            .on('dp.change', () => $(this.element).trigger('submit'));
    }

    disconnect() {
        $([this.startTarget, this.endTarget]).off('dp.change');
    }

    submit(event) {
        $('#search_spinner').show();
        $('#search_results').hide();
        $('.pagination').hide();
        event.target.form.submit();
    }

    toggleDates() {
        $([this.simpleDateTarget, this.advancedDateTarget]).toggle();
        this.yearTarget.disabled = !this.yearTarget.disabled;
        $([this.startTarget, this.endTarget]).find('.form-control').prop('disabled', (i, disabled) => !disabled);
    }

    reset() {
        window.location.href = window.location.pathname;
    }
}
