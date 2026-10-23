import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['simpleDate', 'advancedDate', 'year', 'start', 'end'];

    connect() {
        const dates = $([this.startTarget, this.endTarget]);
        dates.find('input').attr('type', 'date');
        dates.find('.input-group-addon').remove();
        dates.removeClass('input-group');
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
