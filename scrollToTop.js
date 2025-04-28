jQuery(function($) {
  var $win    = $(window),
      $btn    = $('.button-top'),
      idle    = null,
      idleMs  = 2250;   // fade‐out timer for when no activity

  function showBtn() {
    if ( $win.scrollTop() > 100 ) {
      $btn
        .addClass('button-top-visible')
        .stop(true)     // stop any running fade
        .css('display','block'); // ensure it’s in the flow
      restartIdleTimer();
    }
  }

  function hideBtn() {
    $btn
      .removeClass('button-top-visible');
  }

  function restartIdleTimer() {
    clearTimeout(idle);
    idle = setTimeout(hideBtn, idleMs);
  }

  // Scroll → show immediately, then schedule hide
  $win.on('scroll', function() {
    showBtn();
  });

  // Mousemove → same as scroll for showing/resetting
  $(document).on('mousemove', function() {
    showBtn();
  });

  // Click-to-top:
  $btn.on('click', function() {
    $('html, body').animate({ scrollTop: 0 }, 500);
  });
});
