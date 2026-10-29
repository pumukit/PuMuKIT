import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { urls: Object, current: String, messages: Object };

    filterTag(event) {
        event.preventDefault();
        const { tag, url } = event.params;
        $('.unescoTags').css('background-color', 'transparent');
        $('.draggableTag').css('background-color', 'transparent');
        $(`#children_${tag}`).css('background-color', '#EAEAEA');
        $('#list_mms').load(url, () => {
            const id = $('.success .checkbox label').first().data('id');
            if (null != id) {
                this.loadSelected(id);
            } else {
                $('#edit_mms').html('');
                $('#preview_mms').html('');
            }
        });
    }

    search(event) {
        event.preventDefault();
        const loader = $('#unesco_form_loader');
        loader.css('opacity', 1);
        $.ajax({ url: this.urlsValue.criteria, method: 'POST', data: $(event.currentTarget).serialize() })
            .done(() => this.reloadList(() => loader.css('opacity', 0)))
            .fail(() => window.no_success('error'));
    }

    resetSearch(event) {
        event.preventDefault();
        $.ajax({ url: this.urlsValue.removeSession, method: 'POST' }).done(() => {
            document.querySelectorAll('form#myForm input[type="text"]').forEach((input) => {
                input.value = '';
            });
            window.location.reload();
        });
    }

    sort(event) {
        event.preventDefault();
        const data = { sort: $('#pumukitnewadmin_unesco_sort_change_type').val(), sort_type: event.params.field };
        $.ajax({ url: this.urlsValue.criteria, method: 'POST', data })
            .done(() => this.reloadList())
            .fail(() => window.alert('error'));
    }

    batch(event) {
        event.preventDefault();
        const select = event.currentTarget;
        const option = select.value;
        select.value = 'default';
        if (!window.confirm(this.messagesValue.confirm)) {
            window.location.reload();

            return;
        }
        $.ajax({ url: this.urlsValue.options.replace('__OPTION__', option), type: 'POST', data: { data: this.checkedIds() } }).done(() => window.location.reload());
    }

    saveFields(event) {
        event.preventDefault();
        const data = {};
        for (let i = 1; i <= 10; ++i) {
            data[`custom_field_${i}`] = $(`#custom_field_${i}`).val();
        }
        $.ajax({ url: this.urlsValue.fields, type: 'POST', data }).done(() => {
            $('#list_mms').load(this.urlsValue.list);
            window.success(this.messagesValue.fields);
        });
    }

    remove(event) {
        event.preventDefault();
        const { url, confirm } = event.params;
        if (!window.confirm(confirm)) {
            return;
        }
        this.post(url, () => {
            $('#tag_list_unesco').load(this.urlsValue.menuTags);
            $('#list_mms').load(this.urlsValue.list, () => {
                $('#preview_mms').load(this.currentValue ? this.url('show', this.currentValue) : this.urlsValue.emptyShow);
                if (this.currentValue) {
                    $('#edit_mms').load(this.url('edit', this.currentValue));
                } else {
                    $('#edit_mms').html('');
                }
            });
        });
    }

    clone(event) {
        event.preventDefault();
        const { url, confirm } = event.params;
        if (!window.confirm(confirm)) {
            return;
        }
        this.post(url, () => {
            $('#tag_list_unesco').load(this.urlsValue.menuTags);
            $('#list_mms').load(this.urlsValue.list);
        });
    }

    dragRows(event) {
        const ids = this.checkedIds();
        const image = document.getElementById(`dnd_image_${ids.length}`);
        if (image) {
            const icon = document.createElement('img');
            icon.src = image.src;
            icon.width = 35;
            event.dataTransfer.setDragImage(icon, 0, 0);
        }
        event.dataTransfer.setData('data', ids.join(','));
    }

    highlightTags() {
        $('.draggableTag').css('background-color', '#beebff');
    }

    dragRowsEnd(event) {
        $('.draggableTag').css('background-color', 'transparent');
        event.currentTarget.style.opacity = '';
    }

    dragTag(event) {
        const { cod, title } = event.params;
        event.currentTarget.style.opacity = '0.4';
        $('.panel-body-preview ').css('border', '1px dotted black');
        event.dataTransfer.setData('cod', cod);
        event.dataTransfer.setData('title', title);
    }

    dragTagEnd(event) {
        event.currentTarget.style.opacity = '';
        $('.panel-body-preview ').css('border', 'none');
    }

    allowDrop(event) {
        event.preventDefault();
    }

    dropRows(event) {
        event.preventDefault();
        const { cod } = event.params;
        event.dataTransfer.getData('data').split(',').filter((id) => '' !== id).forEach((id) => {
            this.addTag(cod, id, (response) => {
                this.loadSelected(id);
                if (!response.error) {
                    this.count(cod, 1);
                    $('.success').removeClass('success');
                    $(`#tr_${id}`).addClass('success');
                    window.success(this.messagesValue.saved);
                }
            });
        });
    }

    dropTag(event) {
        event.preventDefault();
        const cod = event.dataTransfer.getData('cod');
        if (0 !== $(`#taglist_${cod}`).length) {
            return;
        }
        this.addTag(cod, this.currentValue, () => {
            this.loadSelected(this.currentValue);
            this.count(cod, 1);
            window.success(this.messagesValue.saved);
        });
    }

    removeTag(event) {
        event.preventDefault();
        const { url, cod } = event.params;
        $.ajax({ url, type: 'POST' }).done((response) => {
            if (response.error) {
                window.no_success(response.error);

                return;
            }
            this.loadSelected(this.currentValue);
            this.count(cod, -1);
            window.success(this.messagesValue.saved);
        });
    }

    addTag(cod, id, done) {
        $.ajax({ url: this.urlsValue.addTag.replace('__COD__', encodeURIComponent(cod)).replace('__ID__', id), type: 'POST' })
            .done(done)
            .fail(() => window.no_success(this.messagesValue.savingError));
    }

    reloadList(done) {
        $('#list_mms').load(this.urlsValue.list, () => {
            done?.();
            const selected = $('tr.success input.mm_checkbox');
            if (selected.length > 0) {
                this.loadSelected(selected.data('id'));
            } else {
                $('#edit_mms').html('');
                $('#preview_mms').load(this.urlsValue.emptyShow);
            }
        });
    }

    loadSelected(id) {
        $('#edit_mms').load(this.url('edit', id));
        $('#preview_mms').load(this.url('show', id));
    }

    post(url, done) {
        $.ajax({ url, method: 'POST' })
            .done((response) => {
                if (response?.error) {
                    window.no_success(response.error);

                    return;
                }
                done();
            })
            .fail((xhr) => window.no_success(xhr.responseJSON?.error ?? xhr.statusText));
    }

    count(cod, increment) {
        const counter = $(`#count_tag_${cod}`);
        counter.text(parseInt(counter.text(), 10) + increment);
    }

    checkedIds() {
        return $('input.mm_checkbox:checkbox:checked').map((index, input) => $(input).data('id')).get();
    }

    url(name, id) {
        return this.urlsValue[name].replace('__ID__', id);
    }
}
