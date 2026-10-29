import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        name: String,
        plural: String,
        preview: String,
        urls: Object,
        messages: Object,
        announce: Object,
    };

    run() {
        const option = this.element.selectedOptions[0];
        const action = this.element.value;
        if ('sort' === option?.dataset.type) {
            this.reorder(option);
        } else if (action in this.actions()) {
            this.actions()[action]();
        }
        this.element.value = 'default';
    }

    actions() {
        return {
            delete_selected: () => this.deleteSelected(),
            delete_selected_series: () => this.deleteSelectedSeries(),
            invert_announce_selected: () => this.invertAnnounce(),
            cut_mm: () => this.cut(),
            paste_mm: () => this.paste(),
            add_playlist: () => this.addToPlaylist(),
            delete_selected_groups: () => this.deleteSelectedGroups(),
            promote_to: () => this.promote(),
        };
    }

    selectedIds(selector = `.${this.nameValue}_checkbox:checked`) {
        return $(selector).map((index, element) => element.id).get();
    }

    deleteUrl(ids) {
        return this.urlsValue.delete.replace('__ids__', JSON.stringify(ids));
    }

    message(key) {
        return this.messagesValue[key] ?? '';
    }

    deleteSelected() {
        const ids = this.selectedIds();
        if (0 === ids.length) {
            return;
        }
        if (this.pluralValue.includes('user')) {
            const users = parseInt($('#numberUsers').html().replace(/(\r\n|\n|\r)/gm, '').trim().split(' ')[0], 10);
            if (ids.length === users || 1 === users) {
                window.no_success(this.message('cannotDeleteAll'));

                return;
            }
        }
        if (!window.confirm(this.message('confirm'))) {
            return;
        }

        $.ajax({ url: this.deleteUrl(ids), type: 'POST', data: { _method: 'DELETE' } })
            .fail((xhr, textStatus, errorThrown) => {
                if (this.pluralValue.includes('tags')) {
                    let status = '';
                    try {
                        status = JSON.parse(xhr.responseText).status ?? '';
                    } catch {
                        status = '';
                    }
                    window.no_success(`${errorThrown}: ${this.message('tagsWithChildren')} ${status}`);

                    return;
                }
                window.no_success(`${errorThrown}: ${xhr.responseText}`);
            })
            .done((response) => {
                this.clearDeletedPreview(ids);
                $(`#list_${this.pluralValue}`).html(response);
                window.success(this.message('success'));
            });
    }

    deleteSelectedSeries() {
        const ids = this.selectedIds();
        if (0 === ids.length) {
            return;
        }
        $.ajax({ url: this.urlsValue.info.replace('__ids__', JSON.stringify(ids)), type: 'GET' }).done((info) => {
            const { seriesConfirm, objects, and, events } = this.messagesValue;
            if (!window.confirm(`${seriesConfirm} ${info.mms} ${objects} ${and} ${info.events} ${events}.`)) {
                return;
            }
            $.ajax({ url: this.deleteUrl(ids), type: 'POST', data: { _method: 'DELETE' } })
                .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
                .done((response) => {
                    this.clearDeletedPreview(ids);
                    $(`#list_${this.pluralValue}`).html(response);
                    window.success(this.message('success'));
                });
        });
    }

    invertAnnounce() {
        const ids = this.selectedIds();
        if (0 === ids.length || !window.confirm(this.message('confirm'))) {
            return;
        }
        $(`#list_${this.pluralValue}`).load(this.urlsValue.invert.replace('__ids__', JSON.stringify(ids)), { _method: 'POST' });
        const { edit, checkbox } = this.announceValue;
        const editing = $(edit).val();
        if (!ids.includes(editing)) {
            return;
        }
        const announce = $(checkbox.replace('__id__', editing));
        if (1 === announce.length) {
            announce[0].checked = !announce[0].checked;
        }
    }

    reorder(option) {
        $(`#list_${this.pluralValue}`).load(option.dataset.url, { _method: 'POST' }, () => {
            const active = this.message('active');
            $(this.element).find("option[data-type='sort'][data-active='1']").each((index, current) => {
                current.dataset.active = '0';
                current.textContent = current.textContent.replace(active, '');
            });
            option.dataset.active = '1';
            option.textContent += active;
        });
    }

    cut() {
        const ids = this.selectedIds();
        if (0 === ids.length) {
            window.no_success(this.message('noneSelected'));

            return;
        }
        $.ajax({ url: this.urlsValue.cut.replace('__ids__', JSON.stringify(ids)), type: 'GET' }).done((cut) => {
            const paste = $(this.element).find('#paste_mm');
            paste.text(`${this.message('paste')} (${cut.join(', ')})`).show();
            $(`.${this.nameValue}_checkbox:checked`).prop('checked', false);
            window.success(this.message(1 === ids.length ? 'cutOne' : 'cutMany'));
        });
    }

    paste() {
        if (!window.confirm(this.message('pasteConfirm'))) {
            return;
        }
        $(`#list_${this.pluralValue}`).load(this.urlsValue.paste, { _method: 'POST' }, () => {
            $(this.element).find('#paste_mm').hide();
            window.success(this.message('pasted'));
        });
    }

    addToPlaylist() {
        const ids = this.selectedIds();
        if (0 === ids.length) {
            window.no_success(this.message('noneSelected'));

            return;
        }
        $.ajax({ url: this.urlsValue.playlist.replace('__ids__', JSON.stringify(ids)), type: 'GET' }).done((response) => {
            const modal = $('#myModal');
            modal.modal('show');
            modal.find('.modal-content').html(response);
        });
    }

    clearDeletedPreview(ids) {
        const preview = this.previewValue;
        if (!preview) {
            return;
        }
        const selected = $($('tr.success').find(`input.${this.nameValue}_checkbox`)[0]).attr('id');
        if (ids.includes(selected)) {
            $(`#preview_${preview}`).html(this.message('selectAny'));
            $(`#edit_${preview}`).html('');
        }
    }

    deleteSelectedGroups() {
        const ids = this.selectedIds();
        if (0 === ids.length || !window.confirm(this.message('confirm'))) {
            return;
        }
        let message = '';
        let succeeded = false;
        $.ajax({ url: this.deleteUrl(ids), type: 'POST', data: { _method: 'DELETE' } })
            .fail((xhr, textStatus, errorThrown) => {
                message = `${errorThrown}: ${xhr.responseText}`;
            })
            .done((response) => {
                message = response;
                succeeded = true;
            })
            .always(() => {
                $('#list_groups').load(this.urlsValue.list, { _method: 'POST' });
                if (succeeded) {
                    window.success(message);
                } else {
                    window.no_success(message);
                }
            });
    }

    promote() {
        const ids = this.selectedIds(`.${this.nameValue}_checkbox:checked.nosuperadmin`);
        if (0 === ids.length) {
            window.no_success(this.message('selectUser'));

            return;
        }
        const option = $(this.element).find(':selected');
        $.ajax({ url: this.urlsValue.promote, type: 'POST', data: { ids, profile: option.data('profile') } })
            .fail(() => window.no_success(this.message('promoteError')))
            .done(() => {
                ids.forEach((id) => $(`#profile${id}`).text(option.data('profilename')));
                $(`.${this.nameValue}_checkbox:checked`).prop('checked', false);
                window.success(this.message('promoted'));
            });
    }
}
