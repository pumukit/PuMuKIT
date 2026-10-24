import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['channel', 'tooltip', 'series'];
    static values = { urls: Object, messages: Object, current: String, autoload: Boolean, locale: String };

    connect() {
        if (!this.autoloadValue || !this.currentValue) {
            return;
        }
        if (this.currentValue !== $('#inputMMId').val()) {
            $('#edit_events').load(this.url('edit', this.currentValue));
        }
        $('#preview_events').load(this.url('show', this.currentValue));
    }

    channelTargetConnected(element) {
        const input = $(element);
        input.autocomplete({
            source: (request, response) => {
                $.getJSON(this.urlsValue.channelSuggest, { term: request.term })
                    .done((data) => response(data.map((item) => ({ id: item.id, label: item.text, value: item.text }))))
                    .fail(() => response([]));
            },
            delay: 250,
            minLength: 0,
            select: (event, ui) => {
                $('#live_channel_input_name').val(ui.item.label);
                $('#live_channel_input_id').val(ui.item.id);
                this.channelChosen = true;
            },
        });
        input.on('focus.adminLiveEvent', () => input.autocomplete('search', input.val()));
        input.on('input.adminLiveEvent', () => {
            if ('' === input.val() && this.channelChosen) {
                $('#live_channel_input_name').val('');
                $('#live_channel_input_id').val('');
                this.channelChosen = false;
            }
        });
    }

    channelTargetDisconnected(element) {
        $(element).off('.adminLiveEvent');
        if ($(element).autocomplete('instance')) {
            $(element).autocomplete('destroy');
        }
    }

    tooltipTargetConnected(element) {
        $(element).tooltip({ container: 'body' });
    }

    seriesTargetConnected(element) {
        const input = $(element);
        input.autocomplete({
            source: this.urlsValue.seriesSuggest,
            delay: 250,
            minLength: 2,
            select: (event, ui) => $('#seriesSuggestValidate').val(ui.item.id),
        });
        input.autocomplete('instance')._renderItem = (ul, item) => {
            const link = $('<a>').text(`${item.id} - ${item.title}`).append($('<br>'), $('<span class="form-control">'));

            return $('<li>').append(link).appendTo(ul);
        };
    }

    seriesTargetDisconnected(element) {
        if ($(element).autocomplete('instance')) {
            $(element).autocomplete('destroy');
        }
    }

    batch(event) {
        const select = event.currentTarget;
        if ('delete_selected' !== select.value || !window.confirm(this.messagesValue.deleteSelected)) {
            return;
        }
        $.ajax({ url: this.urlsValue.deleteSelected, method: 'POST', data: $('input[name="events_checkbox[]"]:checked').serialize() })
            .done(() => {
                this.reloadList();
                window.success(this.messagesValue.deleted);
                select.value = 'default';
            })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${textStatus}`));
    }

    create(event) {
        event.preventDefault();
        $.ajax({ url: this.urlsValue.create, method: 'POST' })
            .done((response) => {
                if (response.error) {
                    window.no_success(response.error);

                    return;
                }
                $('#list_events').html(response);
                window.success(this.messagesValue.updated);
            })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${textStatus}`));
    }

    option(event) {
        event.preventDefault();
        const { url, confirm } = event.params;
        if (confirm && !window.confirm(confirm)) {
            return;
        }
        $.ajax({ url, method: 'POST' })
            .done((response) => {
                this.reloadList();
                window.success(response.status);
            })
            .fail((xhr) => window.no_success(xhr.responseJSON?.status));
    }

    sort(event) {
        event.preventDefault();
        $.ajax({ url: this.urlsValue.sorting, method: 'POST', data: { field: event.params.field } }).done(() => this.reloadList());
    }

    filterType(event) {
        event.preventDefault();
        $.ajax({ url: this.urlsValue.listType.replace('__type__', event.params.type), method: 'GET' }).done((response) => $('#list_events').html(response));
    }

    filter(event) {
        event.preventDefault();
        $('#list_events').load(this.urlsValue.list, $(event.currentTarget).serialize());
    }

    resetFilters(event) {
        event.preventDefault();
        $('#filter_events')[0]?.reset();
        $.ajax({ url: this.urlsValue.resetSession, method: 'GET' }).done(() => this.reloadList());
    }

    saveEvent(event) {
        event.preventDefault();
        const form = event.currentTarget;
        $('#remember_save_event').hide();
        $.ajax({ url: event.params.url, method: 'POST', data: $(form).serialize() })
            .done((response) => {
                if (response.error) {
                    window.no_success(response.error);

                    return;
                }
                this.reloadList();
                $('#inputMMTitle').val($(`#pumukitnewadmin_live_event_i18n_name_${this.localeValue}`).val());
                window.success(this.messagesValue.updated);
            })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${'error' !== textStatus ? textStatus : xhr.responseJSON?.status}`));
    }

    toggleContact() {
        const contact = $('#contact');
        contact.prop('disabled', !contact.prop('disabled'));
    }

    toggleExternalUrl() {
        const externalUrl = $('#externalURL');
        const disabled = !externalUrl.prop('disabled');
        externalUrl.prop('disabled', disabled);
        $('#iframeURL').prop('disabled', disabled);
        if (disabled) {
            externalUrl.val('');
        }
    }

    autocompleteSeries(event) {
        event.preventDefault();
        const { id } = event.params;
        $.ajax({ url: this.url('autocompleteSeries', id), method: 'POST' }).done(() => {
            $('#edit_events').load(this.url('edit', id));
            window.success(this.messagesValue.seriesUpdated);
        });
    }

    session(event) {
        event.preventDefault();
        const { url, list, confirm, form } = event.params;
        if (confirm && !window.confirm(confirm)) {
            return;
        }
        this.sessionRequest(url, list, form ? $(form).serialize() : undefined);
    }

    saveSession(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const { url, list } = event.params;
        if ($('#pumukitnewadmin_event_session_start').val() > $('#pumukitnewadmin_event_session_ends').val()) {
            window.no_success(this.messagesValue.sessionDates);

            return;
        }
        $('#updatesessionmodal').modal('hide');
        this.sessionRequest(url, list, $(form).serialize(), () => $('#updatesessionmodal').hide());
    }

    sessionRequest(url, list, data, done) {
        $.ajax({ url, method: 'POST', data })
            .done((response, textStatus, xhr) => {
                if (-1 !== xhr.responseText.indexOf('modal-header')) {
                    window.no_success(this.messagesValue.sessionError);

                    return;
                }
                $('#list_live_events').load(list);
                this.reloadList();
                done?.();
                window.success(this.messagesValue.sessionUpdated);
            })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${textStatus}`));
    }

    changeSeries(event) {
        event.preventDefault();
        const series = $('#seriesSuggestValidate');
        if ('' === series.val()) {
            window.no_success(this.messagesValue.checkSeries);

            return;
        }
        const multimediaObject = $('#mmo_series_change');
        const url = multimediaObject.length ? this.url('editSeries', multimediaObject.val()) : this.urlsValue.create;
        $.ajax({ url, method: 'POST', data: series.serialize() })
            .done((response) => {
                if (response.error) {
                    window.no_success(response.error);

                    return;
                }
                $('#myModal').modal('hide');
                this.reloadList();
                series.val('');
                window.success(this.messagesValue.updated);
            })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${textStatus}`));
    }

    reloadList() {
        $('#list_events').load(this.urlsValue.list);
    }

    url(name, id) {
        return this.urlsValue[name].replace('__ID__', id);
    }
}
