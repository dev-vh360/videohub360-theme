/* global vh360Affiliates */
(function ($) {
    'use strict';

    // Select-all checkbox on payouts page
    $(document).on('change', '#vh360-select-all', function () {
        $('input[name="commission_ids[]"]').prop('checked', this.checked);
    });

})(jQuery);
