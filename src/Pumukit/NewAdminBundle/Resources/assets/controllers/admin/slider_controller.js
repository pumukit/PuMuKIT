import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['item', 'position'];
    static values = { url: String, token: String, messages: Object };

    start(event) {
        this.dragged = event.currentTarget;
        this.dragged.classList.add('dragging');
        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
        }
    }

    end(event) {
        event.currentTarget.classList.remove('dragging');
        this.dragged = null;
        this.positionTargets.forEach((position, index) => {
            position.textContent = index + 1;
        });
    }

    over(event) {
        event.preventDefault();
        const item = event.currentTarget;
        if (!this.dragged || this.dragged === item) {
            return;
        }
        const rect = item.getBoundingClientRect();
        item.parentNode.insertBefore(this.dragged, event.clientY < rect.top + rect.height / 2 ? item : item.nextSibling);
    }

    save(event) {
        const button = event.currentTarget;
        button.disabled = true;
        $.ajax({ url: this.urlValue, type: 'POST', data: { order: this.itemTargets.map((item) => item.dataset.id), _token: this.tokenValue } })
            .done((response) => {
                button.disabled = false;
                if (response.success) {
                    window.success(this.messagesValue.saved);
                } else {
                    window.no_success(this.messagesValue.error);
                }
            })
            .fail(() => {
                button.disabled = false;
                window.no_success(this.messagesValue.error);
            });
    }
}
