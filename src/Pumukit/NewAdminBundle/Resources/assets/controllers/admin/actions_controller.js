import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    preview(event) {
        const { url, name } = event.params;
        $(`#preview_${name}`).load(url);
        $('.success').removeClass('success');
        $(event.currentTarget.parentNode).addClass('success');
    }

    checkAll(event) {
        $(`input.${event.params.name}_checkbox`).prop('checked', event.currentTarget.checked);
    }

    remove(event) {
        event.preventDefault();
        const { url, target, confirm, blocked, success, error, reload, errorMarker, hideModal, refresh } = event.params;
        if (blocked) {
            window.alert(blocked);

            return;
        }
        if (confirm && !window.confirm(confirm)) {
            return;
        }
        if (hideModal) {
            $('#myModal').modal('hide');
        }

        if (reload) {
            $.ajax({ url, method: 'DELETE' })
                .done((response) => {
                    if (errorMarker && String(response).includes(errorMarker)) {
                        window.no_success(error);

                        return;
                    }
                    if (success) {
                        window.success(success);
                    }
                    $(target).load(reload);
                })
                .fail(() => window.no_success(error));

            return;
        }

        $(target).load(url, { _method: 'DELETE' }, (responseText, textStatus, xhr) => {
            if ('error' === textStatus || xhr.status >= 400) {
                window.no_success(`${error || xhr.statusText}: ${responseText}`);

                return;
            }
            (refresh ?? []).forEach((item) => $(item.target).load(item.url));
            if (success) {
                window.success(success);
            }
        });
    }

    load(event) {
        event.preventDefault();
        const { url, target, data } = event.params;
        $(target).load(url, data);
    }

    navigate(event) {
        event.preventDefault();
        window.location.href = event.params.url;
    }

    cancel(event) {
        event.preventDefault();
    }

    alert(event) {
        event.preventDefault();
        window.alert(event.params.message);
    }
}
