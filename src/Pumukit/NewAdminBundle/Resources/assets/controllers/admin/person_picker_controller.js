import { Controller } from '@hotwired/stimulus';
import { SuggestInput, remoteSource } from '../../suggest.js';

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
        this.suggest = new SuggestInput(this.nameTarget, {
            source: remoteSource(this.autocompleteUrlValue, (item) => ({ id: item.id, text: item.label, description: String(item.desc ?? '').trim(), value: item.value })),
            minLength: 2,
            select: (item) => {
                this.personTarget.value = item.id;
            },
        });
    }

    disconnect() {
        this.suggest?.destroy();
        this.suggest = null;
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
