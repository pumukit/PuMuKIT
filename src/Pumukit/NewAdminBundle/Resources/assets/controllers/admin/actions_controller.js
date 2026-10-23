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
        const { url, target, confirm, blocked, success, error, reload, errorMarker, hideModal, refresh, clear } = event.params;
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

        const selected = clear ? $($('tr.success').find(`input.${clear.name}_checkbox`)[0]).attr('id') : null;
        $(target).load(url, { _method: 'DELETE' }, (responseText, textStatus, xhr) => {
            if ('error' === textStatus || xhr.status >= 400) {
                window.no_success(`${error || xhr.statusText}: ${responseText}`);

                return;
            }
            if (clear && clear.id === selected) {
                $(`#preview_${clear.name}`).html(clear.message);
                $(`#edit_${clear.name}`).html('');
            }
            (refresh ?? []).forEach((item) => $(item.target).load(item.url));
            if (success) {
                window.success(success);
            }
        });
    }

    sort(event) {
        event.preventDefault();
        const { url, target, type, selected, selectedKey } = event.params;
        let href = type ? url.replace('__type__', encodeURIComponent($(type).val())) : url;
        const id = selected ? $(selected).val() : '';
        if (id && selectedKey) {
            href += `${href.includes('?') ? '&' : '?'}${selectedKey}=${encodeURIComponent(id)}`;
        }
        $(target).load(href, { _method: 'POST' });
    }

    load(event) {
        event.preventDefault();
        const { url, target, data, confirm, success } = event.params;
        if (confirm && !window.confirm(confirm)) {
            return;
        }
        $(target).load(url, data, () => {
            if (success) {
                window.success(success);
            }
        });
    }

    edit(event) {
        const { name, show, update } = event.params;
        $(`#preview_${name}`).load(show);
        $(`#edit_${name}`).load(update, () => window.init_tabs?.());
        $('.success').removeClass('success');
        $(event.currentTarget.parentNode).addClass('success');
    }

    create(event) {
        event.preventDefault();
        const { url, method, key, name, list, listUrl, showUrl, editUrl, reset, success } = event.params;
        if (reset) {
            $(reset)[0]?.reset();
        }
        $.ajax(url, { type: method ?? 'GET' })
            .fail((xhr, textStatus, errorThrown) => {
                const title = xhr.responseText.split('<title>').pop().split('</title>').shift().replace(/(\r\n|\n|\r)/gm, '');
                window.no_success(`${errorThrown}: ${textStatus}, ${title}`);
            })
            .done((response) => {
                const id = response[key];
                $(list).load(listUrl.replace('__id__', id), () => {
                    $(`#preview_${name}`).load(showUrl.replace('__id__', id));
                    $(`#edit_${name}`).load(editUrl.replace('__id__', id), () => window.init_tabs?.());
                });
                window.success(success);
            });
    }

    send(event) {
        event.preventDefault();
        const { url, reload } = event.params;
        $.ajax({ url: url.replace('__value__', encodeURIComponent(event.currentTarget.value ?? '')), method: 'POST' })
            .done((response) => {
                window.success(response.success);
                if (reload) {
                    window.location.reload();
                }
            })
            .fail((xhr) => window.no_success(xhr.statusText));
    }

    toggle(event) {
        event.preventDefault();
        event.params.selectors.forEach((selector) => $(selector).toggle());
    }

    go(event) {
        window.location.href = `${event.params.url}${event.currentTarget.value}`;
    }

    validateRange(event) {
        const { from, to, message } = event.params;
        const fromDate = $(from);
        const toDate = $(to);
        toDate[0].setCustomValidity('' !== toDate.val() && fromDate.val() >= toDate.val() ? message : '');
    }

    validity(event) {
        event.currentTarget.setCustomValidity('invalid' === event.type ? event.params.message : '');
    }

    magicUrl(event) {
        event.preventDefault();
        const { url, template, confirm, success, input, link } = event.params;
        if (!window.confirm(confirm)) {
            return;
        }
        $(input).load(url, { _method: 'POST' }, (responseText, textStatus, xhr) => {
            if ('error' === textStatus) {
                window.no_success(`${xhr.statusText}: ${responseText}`);

                return;
            }
            window.success(success);
            const magic = template.replace('__secret__', responseText);
            $(input).val(magic);
            $(link).attr('href', magic);
        });
    }

    notifyParent(event) {
        event.preventDefault();
        window.parent.postMessage(event.params.message, '*');
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
