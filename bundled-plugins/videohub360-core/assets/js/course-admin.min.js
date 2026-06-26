/**
 * Course Admin Enhancements
 *
 * Searchable eligible user selectors for course owner / instructor term meta.
 *
 * @package VideoHub360_Core
 */

(function ($) {
    'use strict';

    var strings = window.vh360CourseAdmin || {};

    function setStatus($results, message) {
        $results.html('<div class="vh360-course-user-result is-status">' + $('<div>').text(message).html() + '</div>').show();
    }

    function clearSelector($field) {
        $field.find('.vh360-course-user-id').val('');
        $field.find('.vh360-course-user-search').val('').focus();
        $field.find('.vh360-course-user-cleared').val('1');
        $field.find('.vh360-course-user-clear').hide();
        $field.find('.vh360-course-user-results').empty().hide();
    }

    function selectUser($field, user) {
        $field.find('.vh360-course-user-id').val(user.id);
        $field.find('.vh360-course-user-search').val(user.text);
        $field.find('.vh360-course-user-cleared').val('0');
        $field.find('.vh360-course-user-clear').show();
        $field.find('.vh360-course-user-results').empty().hide();
    }

    function searchUsers($field, query) {
        var $results = $field.find('.vh360-course-user-results');

        if (!query || query.length < 2) {
            $results.empty().hide();
            return;
        }

        setStatus($results, strings.searchingText || 'Searching…');

        $.ajax({
            url: strings.ajaxUrl,
            method: 'GET',
            dataType: 'json',
            data: {
                action: 'vh360_search_course_users',
                nonce: strings.nonce,
                q: query
            }
        }).done(function (response) {
            var users = response && response.success && response.data ? response.data : [];
            $results.empty();

            if (!users.length) {
                setStatus($results, strings.noResultsText || 'No eligible users found.');
                return;
            }

            users.forEach(function (user) {
                $('<button type="button" class="vh360-course-user-result"></button>')
                    .text(user.text)
                    .attr('data-user-id', user.id)
                    .data('user', user)
                    .appendTo($results);
            });

            $results.show();
        }).fail(function () {
            setStatus($results, strings.noResultsText || 'No eligible users found.');
        });
    }

    $(function () {
        $('.vh360-course-user-selector').each(function () {
            var $field = $(this);
            var timer = null;

            $field.on('input', '.vh360-course-user-search', function () {
                var query = $(this).val();

                $field.find('.vh360-course-user-id').val('');
                window.clearTimeout(timer);
                timer = window.setTimeout(function () {
                    searchUsers($field, query);
                }, 250);
            });

            $field.on('click', '.vh360-course-user-result:not(.is-status)', function () {
                selectUser($field, $(this).data('user'));
            });

            $field.on('click', '.vh360-course-user-clear', function (event) {
                event.preventDefault();
                clearSelector($field);
            });
        });

        $(document).on('click', function (event) {
            if (!$(event.target).closest('.vh360-course-user-selector').length) {
                $('.vh360-course-user-results').empty().hide();
            }
        });
    });
})(jQuery);
