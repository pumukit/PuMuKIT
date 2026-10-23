import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['letter'];
    static values = { url: String, target: String, letters: String };

    submit(event) {
        event.preventDefault();
        $(this.targetValue).load(this.urlValue, $(this.element).serialize());
    }

    reset(event) {
        event.preventDefault();
        this.clearLetters();
        $(this.element).find('input.reset').val('');
        $(this.targetValue).load(`${this.urlValue}?criteria[reset]=true`);
    }

    letter(event) {
        event.preventDefault();
        this.clearLetters();
        $(event.currentTarget).addClass('text-pumukit');
        this.letterTarget.value = event.params.letter;
        $(this.targetValue).load(this.urlValue, $(this.element).serialize());
    }

    clearLetters() {
        if (this.lettersValue) {
            $(`${this.lettersValue}.text-pumukit`).removeClass('text-pumukit');
        }
    }
}
