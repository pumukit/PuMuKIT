window.success = (message) => {
    $('#div_messages_span_info').attr('data-content', message).snackbar('show');
};

window.no_success = (message) => {
    $('#div_messages_span_error').attr('data-content', message).snackbar('show');
};

$(document).ajaxSuccess(() => $.material.init());

$(document).ajaxError((event, xhr) => {
    if (403 === xhr.status) {
        window.location.href = window.location.href;
    }
});

$(() => {
    $.material.init();
    $('body').tooltip({ selector: '[data-toggle="tooltip"]' });

    $('body').on('hidden.bs.modal', '.pmk-modal', (event) => {
        $(event.currentTarget).removeData('bs.modal').find('.modal-content').html('');
    });

    $('body').on('shown.bs.modal loaded.bs.modal', '.pmk-modal', (event) => {
        if (0 !== $('.modal.fade.in:focus').length) {
            $(event.currentTarget).find('input:text').first().trigger('focus');
        }
    });

    $('#myModal').on('show.bs.modal', (event) => {
        $(event.currentTarget).find('.modal-content').html('');
    });

    if (document.querySelector('[data-naked-backoffice]')) {
        $('#myModal, #myAuxModal').on('shown.bs.modal', (event) => {
            const dialog = $(event.currentTarget).find('.modal-dialog');
            const top = Math.max(26, event.relatedTarget.getBoundingClientRect().top - 50 - dialog.height());
            dialog.css('margin', `${top}px auto 30px`);
        });
    }
});
