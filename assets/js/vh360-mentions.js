(function($) {
    'use strict';

    /**
     * Simple @mention autocomplete for VideoHub360.
     *
     * This script is intentionally self-contained and does not depend on
     * any of the existing community.js code, to avoid conflicts.
     */
    $(document).ready(function() {

        const MENTION_INPUT_SELECTOR = '.vh360-post-textarea, .vh360-comment-form textarea[name="comment"]';
        const MENTION_MIN_CHARS = 1;
        const MENTION_DEBOUNCE_MS = 200;

        const state = {
            active: false,
            query: '',
            caretStart: 0,
            $target: null,
            $menu: null,
            results: [],
            selectedIndex: -1,
            debounceTimer: null
        };

        function init() {
            // Delegate so this works for dynamically loaded forms too.
            $(document)
                .off('keyup.vh360Mentions', MENTION_INPUT_SELECTOR)
                .off('keydown.vh360Mentions', MENTION_INPUT_SELECTOR)
                .on('keyup.vh360Mentions', MENTION_INPUT_SELECTOR, handleKeyup)
                .on('keydown.vh360Mentions', MENTION_INPUT_SELECTOR, handleKeydown);
        }

        function handleKeyup(event) {
            const el = event.target;
            const value = el.value || '';
            const caretPos = el.selectionStart;

            if (typeof caretPos !== 'number') {
                hideMenu();
                return;
            }

            // Basic debug to confirm handler is firing.
            if (window.console && console.debug) {
            }

            const textBeforeCaret = value.slice(0, caretPos);
            const atIndex = textBeforeCaret.lastIndexOf('@');

            if (atIndex === -1) {
                state.active = false;
                hideMenu();
                return;
            }

            // Require @ to start a new word.
            const charBefore = atIndex > 0 ? textBeforeCaret.charAt(atIndex - 1) : ' ';
            if (!/\s/.test(charBefore)) {
                state.active = false;
                hideMenu();
                return;
            }

            const query = textBeforeCaret.slice(atIndex + 1);

            if (query.indexOf(' ') !== -1 || query.length < MENTION_MIN_CHARS) {
                state.active = false;
                hideMenu();
                return;
            }

            state.active = true;
            state.query = query;
            state.caretStart = atIndex;
            state.$target = $(el);

            if (window.console && console.debug) {
            }

            // Debounced search
            if (state.debounceTimer) {
                clearTimeout(state.debounceTimer);
            }
            state.debounceTimer = setTimeout(function() {
                performSearch(query);
            }, MENTION_DEBOUNCE_MS);
        }

        function handleKeydown(event) {
            if (!state.$menu || !state.$menu.is(':visible')) {
                return;
            }

            const key = event.key;

            if (key === 'ArrowDown' || key === 'Down') {
                event.preventDefault();
                moveSelection(1);
            } else if (key === 'ArrowUp' || key === 'Up') {
                event.preventDefault();
                moveSelection(-1);
            } else if (key === 'Enter' || key === 'Tab') {
                if (state.selectedIndex >= 0) {
                    event.preventDefault();
                    const user = state.results[state.selectedIndex];
                    if (user) {
                        insertHandle(user.handle);
                    }
                }
            } else if (key === 'Escape' || key === 'Esc') {
                hideMenu();
            }
        }

        function performSearch(query) {
            if (!query || typeof vh360Community === 'undefined' || !vh360Community.ajaxurl) {
                return;
            }

            $.ajax({
                url: vh360Community.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'vh360_search_user_mentions',
                    q: query,
                    nonce: vh360Community.mentionNonce || vh360Community.nonce
                },
                success: function(response) {
                    if (!response || !response.success || !response.data || !Array.isArray(response.data.users)) {
                        hideMenu();
                        return;
                    }
                    state.results = response.data.users;
                    renderMenu();
                },
                error: function(xhr, status, error) {
                    if (window.console && console.error) {
                    }
                    hideMenu();
                }
            });
        }

        function renderMenu() {
            if (!state.$target || !state.results.length) {
                hideMenu();
                return;
            }

            if (!state.$menu) {
                state.$menu = $('<div class="vh360-mention-menu"></div>');
                $('body').append(state.$menu);
            }

            let html = '';
            state.selectedIndex = -1;

            state.results.forEach(function(user, index) {
                const avatar = user.avatar ? '<img src="' + user.avatar + '" alt="">' : '';
                const name = $('<div/>').text(user.name || '').html();
                const handle = $('<div/>').text('@' + (user.handle || '')).html();
                const selectedClass = index === 0 ? ' is-selected' : '';
                if (index === 0) {
                    state.selectedIndex = 0;
                }
                html += '<div class="vh360-mention-item' + selectedClass + '" data-index="' + index + '">' +
                    avatar +
                    '<div class="vh360-mention-meta"><div class="vh360-mention-name">' + name + '</div><div class="vh360-mention-handle">' + handle + '</div></div>' +
                    '</div>';
            });

            state.$menu.html(html);

            state.$menu.off('mousedown.vh360Mentions').on('mousedown.vh360Mentions', '.vh360-mention-item', function(e) {
                e.preventDefault();
                const index = parseInt($(this).data('index'), 10);
                const user = state.results[index];
                if (user) {
                    insertHandle(user.handle);
                }
            });

            const offset = state.$target.offset();
            const height = state.$target.outerHeight();
            state.$menu.css({
                top: offset.top + height + 4,
                left: offset.left,
                position: 'absolute'
            }).show();
        }

        function moveSelection(delta) {
            if (!state.$menu || !state.results.length) {
                return;
            }
            let index = state.selectedIndex;
            if (index < 0) {
                index = 0;
            }
            index += delta;
            if (index < 0) {
                index = state.results.length - 1;
            } else if (index >= state.results.length) {
                index = 0;
            }
            state.selectedIndex = index;

            state.$menu.find('.vh360-mention-item').removeClass('is-selected')
                .eq(index).addClass('is-selected');
        }

        function insertHandle(handle) {
            if (!state.$target || typeof handle !== 'string') {
                hideMenu();
                return;
            }

            const el = state.$target.get(0);
            const value = el.value || '';
            const caretPos = el.selectionStart;

            const before = value.slice(0, state.caretStart);
            const after = value.slice(caretPos);
            const insertion = '@' + handle + ' ';

            el.value = before + insertion + after;

            const newCaret = before.length + insertion.length;
            if (typeof el.setSelectionRange === 'function') {
                el.setSelectionRange(newCaret, newCaret);
            }

            hideMenu();
        }

        function hideMenu() {
            if (state.$menu) {
                state.$menu.hide();
            }
            state.active = false;
            state.query = '';
            state.results = [];
            state.selectedIndex = -1;
        }

        // Initialize on ready
        init();
    });
})(jQuery);