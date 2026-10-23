import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['name', 'person', 'create'];
    static values = {
        autocompleteUrl: String,
        linkUrl: String,
        createUrl: String,
        target: String,
        preview: String,
        modal: String,
        messages: Object,
    };

    connect() {
        if (!this.hasNameTarget || !this.autocompleteUrlValue) {
            return;
        }
        const input = $(this.nameTarget);
        input.autocomplete({
            source: this.autocompleteUrlValue,
            delay: 250,
            minLength: 2,
            select: (event, ui) => {
                this.personTarget.value = ui.item.id;
            },
        });
        input.autocomplete('instance')._renderItem = (ul, item) => {
            const link = $('<a>').attr('title', item.id).text(item.label).append($('<br>'), $('<span class="form-control">').text(item.desc));

            return $('<li>').append(link).appendTo(ul);
        };
    }

    disconnect() {
        if (this.hasNameTarget && $(this.nameTarget).autocomplete('instance')) {
            $(this.nameTarget).autocomplete('destroy');
        }
    }

    clear() {
        this.personTarget.value = '';
    }

    use(event) {
        event.preventDefault();
        const personId = this.personTarget.value;
        if ('' === personId) {
            window.alert(this.messagesValue.selectFirst);

            return;
        }
        $(this.targetValue).load(this.linkUrlValue.replace('__personId__', personId), { _method: 'POST' }, () => {
            window.success(this.messagesValue.linked);
            if (this.previewValue) {
                $('#preview_mm').load(this.previewValue, { _method: 'POST' });
            }
        });
        $(this.modalValue).modal('hide');
    }

    create() {
        this.createTarget.setAttribute('href', encodeURI(this.createUrlValue.replace('__aux__', this.nameTarget.value)));
    }
}
