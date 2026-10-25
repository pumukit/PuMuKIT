const toast = (message, type, delay) => {
    const container = document.getElementById('pmk-toasts');
    if (!container) {
        return;
    }
    const element = document.createElement('div');
    element.className = `toast align-items-center border-0 pmk-toast-${type}`;
    element.setAttribute('role', 'error' === type ? 'alert' : 'status');
    element.setAttribute('aria-live', 'error' === type ? 'assertive' : 'polite');
    element.setAttribute('aria-atomic', 'true');
    const row = document.createElement('div');
    row.className = 'd-flex';
    const body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = message;
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close btn-close-white me-2 m-auto';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', container.dataset.closeLabel ?? 'Close');
    row.append(body, close);
    element.append(row);
    container.append(element);
    element.addEventListener('hidden.bs.toast', () => element.remove());
    window.bootstrap.Toast.getOrCreateInstance(element, { delay }).show();
};

window.success = (message) => toast(message, 'info', 3000);

window.no_success = (message) => toast(message, 'error', 7000);

const loadModal = (modal, url) => {
    $(modal).find('.modal-content').load(url, () => $(modal).trigger('loaded.bs.modal'));
};

$(document).ajaxError((event, xhr) => {
    if (403 === xhr.status) {
        window.location.href = window.location.href;
    }
});

$(() => {
    $('body').tooltip({ selector: '[data-bs-toggle="tooltip"]' });

    window.addEventListener('click', (event) => {
        const trigger = event.target.closest?.('[data-bs-toggle="modal"][data-bs-target]');
        const current = trigger?.closest('.modal');
        if (!current) {
            return;
        }
        const target = document.querySelector(trigger.getAttribute('data-bs-target'));
        if (!target) {
            return;
        }
        if (current === target) {
            event.preventDefault();
            event.stopPropagation();
            loadModal(target, trigger.getAttribute('href'));

            return;
        }
        if (trigger.hasAttribute('data-bs-dismiss')) {
            $(current).modal('hide');
        }
    }, true);

    $('body').on('show.bs.modal', '.modal', (event) => {
        const href = event.relatedTarget?.getAttribute?.('href');
        if (event.target !== event.currentTarget || !href || href.startsWith('#')) {
            return;
        }
        loadModal(event.currentTarget, href);
    });

    $('body').on('hidden.bs.modal', '.pmk-modal', (event) => {
        $(event.currentTarget).find('.modal-content').html('');
    });

    $('body').on('shown.bs.modal loaded.bs.modal', '.pmk-modal', (event) => {
        if (0 !== $('.modal.fade.show:focus').length) {
            $(event.currentTarget).find('input:text').first().trigger('focus');
        }
    });

    $('#myModal').on('show.bs.modal', (event) => {
        $(event.currentTarget).find('.modal-content').html('');
    });

    if (document.querySelector('[data-naked-backoffice]')) {
        $('#myModal, #myAuxModal').on('shown.bs.modal', (event) => {
            if (!event.relatedTarget) {
                return;
            }
            const dialog = $(event.currentTarget).find('.modal-dialog');
            const top = Math.max(26, event.relatedTarget.getBoundingClientRect().top - 50 - dialog.height());
            dialog.css('margin', `${top}px auto 30px`);
        });
    }
});
