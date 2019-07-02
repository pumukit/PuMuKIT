$(function() {
    $(".dynamic_image").on("mouseover", function (event) {
        event.preventDefault();
        var that = $(this);
        setTimeout(function () {
            if (that.data('dynamic-pic') != '') {
                that.attr('src', that.data('dynamic-pic'));
            }
        }, 300);
    });

    $(".dynamic_image").on("mouseout", function (event) {
        event.preventDefault();
        var that = $(this);
        setTimeout(function () {
            if (that.data('static-pic') != '') {
                that.attr('src', that.data('static-pic'));
            }
        }, 300);
    });
});