import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        name: String,
        plural: String,
        preview: String,
        urls: Object,
        messages: Object,
    };

    run() {
        const action = this.element.value;
        if (action in this.actions()) {
            this.actions()[action]();
        }
        this.element.value = 'default';
    }

    actions() {
        return {
            delete_selected: () => this.deleteSelected(),
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
            const users = parseInt($.trim($('#numberUsers').html().replace(/(\r\n|\n|\r)/gm, '')).split(' ')[0], 10);
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
                ids.forEach((id) => $(`#profile${id}`).html(option.data('profilename')));
                $(`.${this.nameValue}_checkbox:checked`).prop('checked', false);
                window.success(this.message('promoted'));
            });
    }
}
