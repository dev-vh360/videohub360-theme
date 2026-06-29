(function($) {
    'use strict';

    function reindexScheduleRows() {
        $('.vh360-youtube-schedule-row').each(function(index) {
            var $row = $(this);
            $row.attr('data-index', index).data('index', index);
            $row.find(':input[name]').each(function() {
                var $field = $(this);
                $field.attr('name', $field.attr('name').replace(/vh360_youtube_schedules\[\d+\]/, 'vh360_youtube_schedules[' + index + ']'));
            });
        });
        updateRemoveButtons();
        $('#vh360-add-youtube-schedule').attr('data-next-index', $('.vh360-youtube-schedule-row').length);
    }

    function resetScheduleRow($row) {
        var $addButton = $('#vh360-add-youtube-schedule');
        $row.find(':input').each(function() {
            var $field = $(this);
            var name = $field.attr('name') || '';

            if ($field.is(':checkbox')) {
                $field.prop('checked', true);
            } else if ($field.is('select')) {
                $field.val('sunday');
            } else if ($field.attr('type') === 'time') {
                $field.val('10:00');
            } else if (name.indexOf('[expected_duration_minutes]') !== -1) {
                $field.val($addButton.data('duration-default') || 120);
            } else if (name.indexOf('[precheck_minutes]') !== -1) {
                $field.val($addButton.data('precheck-default') || 30);
            } else if (name.indexOf('[grace_minutes]') !== -1) {
                $field.val($addButton.data('grace-default') || 20);
            } else if (name.indexOf('[category]') !== -1) {
                $field.val('0');
            } else {
                $field.val('');
            }
        });
    }

    function updateRemoveButtons() {
        var rowCount = $('.vh360-youtube-schedule-row').length;
        $('.vh360-youtube-remove-schedule').prop('disabled', rowCount <= 1).toggleClass('is-disabled', rowCount <= 1);
    }

    function formatCheckResult(data) {
        if (!data) {
            return 'Check failed. Please try again.';
        }
        if (data.result === 'disabled') {
            return 'YouTube auto-broadcast is currently disabled.';
        }
        if (data.result === 'outside_schedule_window') {
            return 'No check needed: current time is outside the configured schedule window.';
        }
        if (data.result === 'api_error' || data.error_message) {
            return 'API error: ' + data.error_message;
        }
        if (data.active_live_found) {
            return 'Live found: video ID ' + (data.video_id || 'unknown') + ', post ID ' + (data.post_id || 'not available') + '.';
        }
        if (data.result === 'upcoming_prepared') {
            return 'Upcoming video prepared: video ID ' + (data.video_id || 'unknown') + ', post ID ' + (data.post_id || 'not available') + '.';
        }
        return 'No live video found.';
    }

    $(function() {
        updateRemoveButtons();

        $('#vh360-add-youtube-schedule').on('click', function(e) {
            e.preventDefault();
            var $rows = $('.vh360-youtube-schedule-row');
            if (!$rows.length) {
                return;
            }

            var $clone = $rows.first().clone(false);
            resetScheduleRow($clone);
            $rows.last().after($clone);
            reindexScheduleRows();
        });

        $(document).on('click', '.vh360-youtube-remove-schedule', function(e) {
            e.preventDefault();
            var $rows = $('.vh360-youtube-schedule-row');
            if ($rows.length <= 1) {
                resetScheduleRow($rows.first());
                updateRemoveButtons();
                return;
            }
            $(this).closest('.vh360-youtube-schedule-row').remove();
            reindexScheduleRows();
        });

        $('#vh360-youtube-check-now').on('click', function(e) {
            e.preventDefault();
            var $button = $(this);
            var $result = $('#vh360-youtube-check-result');

            $button.prop('disabled', true);
            $result.removeClass('is-error is-success').addClass('is-loading').text('Checking...');

            $.post(ajaxurl, {
                action: 'vh360_youtube_check_now',
                nonce: $button.data('nonce')
            }).done(function(response) {
                if (response && response.success) {
                    $result.removeClass('is-loading is-error').addClass(response.data && response.data.active_live_found ? 'is-success' : '').text(formatCheckResult(response.data));
                } else {
                    var message = response && response.data && response.data.message ? response.data.message : 'Check failed.';
                    $result.removeClass('is-loading is-success').addClass('is-error').text(message);
                }
            }).fail(function(xhr) {
                $result.removeClass('is-loading is-success').addClass('is-error').text('Check failed: HTTP ' + xhr.status + '.');
            }).always(function() {
                $button.prop('disabled', false);
            });
        });
    });
})(jQuery);
