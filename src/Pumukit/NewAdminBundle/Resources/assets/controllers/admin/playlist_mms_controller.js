import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { urls: Object };

    select(event) {
        const { id, pos } = event.params;
        $('.success').removeClass('success');
        $(event.currentTarget).addClass('success');
        $('#preview_playlistmm').load(this.urlsValue.show.replace('__ID__', id).replace('__POS__', pos));
        $('#edit_playlistmm').load(this.urlsValue.info.replace('__ID__', id), () => window.init_tabs?.());
    }

    remove(event) {
        event.preventDefault();
        const { id, pos } = event.params;
        this.deleteItems({ [pos]: id });
    }

    batch(event) {
        const select = event.currentTarget;
        if ('delete_selected' === select.value) {
            const items = {};
            $('.playlistmm_checkbox:checked').each((index, input) => {
                items[$(input).data('pos')] = input.id;
            });
            if (0 !== Object.keys(items).length) {
                this.deleteItems(items);
            }
        }
        select.value = 'default';
    }

    deleteItems(items) {
        $('#list_playlistmms').load(this.urlsValue.delete.replace('__ids__', encodeURIComponent(JSON.stringify(items))));
    }
}
