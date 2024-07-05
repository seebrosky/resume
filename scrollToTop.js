jQuery(function ($) {
    var $window = $(window);
    var $buttonTop = $('.button-top');
    var scrollTimer;

    $buttonTop.on('click', function () {
        $('html, body').animate({
            scrollTop: 0,
        }, 500);
    });

    $window.on('scroll', function () {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            if ($window.scrollTop() > 100) {
                $buttonTop.addClass('button-top-visible');
            } else {
                $buttonTop.removeClass('button-top-visible');
            }
        }, 250);
    });  
});
