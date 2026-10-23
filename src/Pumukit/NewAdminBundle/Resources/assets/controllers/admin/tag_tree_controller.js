import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { childrenUrl: String, deleteUrl: String, messages: Object };

    toggle(event) {
        const { id, cod } = event.params;
        const cell = event.currentTarget.parentElement;
        if (cell.classList.contains('expanded')) {
            $(`.c_${cod}`).hide();
            $(`.c_${cod} .expanded`).removeClass('expanded').addClass('collapsed');
        } else if (cell.classList.contains('noload')) {
            cell.classList.remove('noload');
            this.loadChildren(id, cod);
        } else {
            $(`.d_${cod}`).show();
        }
        cell.classList.toggle('collapsed');
        cell.classList.toggle('expanded');
    }

    loadChildren(id, cod) {
        $(`.c_${cod}`).remove();
        const row = $(`#row_tag_${id}`);
        $.ajax(this.childrenUrlValue.replace('___id___', id), { type: 'GET' }).done((response) => {
            row.after(response);
            if (0 !== $(`.d_${cod}`).length) {
                $(`#row_tag_${id} .collapsed`).addClass('expanded').removeClass('collapsed');
            }
        });
    }

    remove(event) {
        event.preventDefault();
        const { id, pid, blocked, confirm, success } = event.params;
        if (blocked) {
            window.alert(blocked);

            return;
        }
        if (!window.confirm(confirm)) {
            return;
        }
        $.ajax(this.deleteUrlValue.replace('___id___', id), { type: 'DELETE', data: { _method: 'DELETE' } })
            .fail(() => window.alert(this.messagesValue.withChildren))
            .done(() => {
                $(`#row_tag_${id}`).remove();
                if (0 === $(`.d_${pid}`).length) {
                    $(`#row_tag_${pid} .expanded`).removeClass('expanded').addClass('element');
                }
                window.success(success);
            });
    }
}
