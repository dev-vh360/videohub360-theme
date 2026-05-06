/* global vh360AffFrontend */
(function ($) {
    'use strict';

    // Copy referral URL to clipboard
    $(document).on('click', '.vh360-aff-copy-btn', function () {
        var targetId = $(this).data('target');
        var $input = $('#' + targetId);

        if (!$input.length) {
            return;
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText($input.val()).then(function () {
                showCopied();
            });
        } else {
            $input.select();
            document.execCommand('copy');
            showCopied();
        }

        function showCopied() {
            var $btn = $(document).find('.vh360-aff-copy-btn[data-target="' + targetId + '"]');
            var original = $btn.text();
            $btn.text(vh360AffFrontend.copied || 'Copied!');
            setTimeout(function () {
                $btn.text(original);
            }, 2000);
        }
    });

})(jQuery);
