import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['playlists', 'none', 'newForm', 'title', 'create', 'ok', 'ids', 'id'];
    static values = { createUrl: String, addUrl: String, messages: Object };

    titles() {
        this.createTarget.disabled = this.titleTargets.some((input) => '' === input.value);
    }

    selected() {
        this.okTarget.disabled = 0 === this.checked().length;
    }

    createPlaylist(event) {
        event.preventDefault();
        $.post(this.createUrlValue, $(this.newFormTarget).serialize())
            .done((data) => {
                const input = $('<input checked="checked" type="checkbox" class="add_playlist_form_checkbox">')
                    .attr({ name: `pub_${data.playlistId}`, id: data.playlistId });
                const label = $('<label>').text(` ${data.title}`).prepend(input);
                $(this.playlistsTarget).prepend($('<div class="form-check pmk-padding-top-10">').append(label));
                this.okTarget.disabled = false;
                this.newFormTarget.reset();
                $(this.element).find('.new_playlist').toggle();
                if (this.hasNoneTarget) {
                    $(this.noneTarget).hide();
                }
            })
            .fail(() => window.no_success(this.messagesValue.createError));
    }

    save(event) {
        event.preventDefault();
        let ids = this.idsTarget.value;
        if ('' !== ids) {
            ids = JSON.parse(ids);
        }
        if ('' !== this.idTarget.value) {
            ids = [this.idTarget.value];
        }
        $.post(this.addUrlValue, { ids, series_ids: this.checked().map((input) => input.id) })
            .done(() => $('#myModal').modal('hide'))
            .fail(() => window.no_success(this.messagesValue.addError));
    }

    checked() {
        return [...this.element.querySelectorAll('.add_playlist_form_checkbox')].filter((input) => input.checked);
    }
}
