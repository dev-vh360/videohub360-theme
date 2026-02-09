(function ($) {
  'use strict';

  function setStatus($el, text, type) {
    $el.removeClass('is-error is-success');
    if (type) {
      $el.addClass(type === 'error' ? 'is-error' : 'is-success');
    }
    $el.text(text || '');
  }

  $(document).on('submit', '#vh360-push-notification-form', function (e) {
    e.preventDefault();

    if (typeof window.vh360PushNotifications === 'undefined') {
      return;
    }

    var $form = $(this);
    var $status = $('#vh360-push-status');
    var $btn = $('#vh360-push-submit');

    setStatus($status, vh360PushNotifications.i18n.sending, null);
    $btn.prop('disabled', true);

    var payload = {
      action: 'vh360_pwa_push_send_frontend',
      nonce: vh360PushNotifications.nonce,
      title: ($form.find('[name="title"]').val() || '').trim(),
      body: ($form.find('[name="body"]').val() || '').trim(),
      url: ($form.find('[name="url"]').val() || '').trim(),
      icon: ($form.find('[name="icon"]').val() || '').trim()
    };

    $.post(vh360PushNotifications.ajaxUrl, payload)
      .done(function (resp) {
        if (resp && resp.success) {
          setStatus($status, resp.data && resp.data.message ? resp.data.message : vh360PushNotifications.i18n.sent, 'success');
          $form[0].reset();
        } else {
          var msg = (resp && resp.data && resp.data.message) ? resp.data.message : vh360PushNotifications.i18n.error;
          setStatus($status, msg, 'error');
        }
      })
      .fail(function () {
        setStatus($status, vh360PushNotifications.i18n.error, 'error');
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });
})(jQuery);
