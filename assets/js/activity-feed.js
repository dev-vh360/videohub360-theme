/**
 * Activity Feed JavaScript
 *
 * Handles filtering, infinite scroll, and load more for activity feed.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // State management
    const state = {
        currentType: 'all',
        offset: 20, // Initial activities already loaded
        isLoading: false,
        hasMore: true,
        infiniteScrollEnabled: true
    };
    
    // DOM elements
    const $filterTabs = $('.vh360-filter-tab');
    const $activityStream = $('#vh360-activity-stream');
    const $loading = $('#vh360-activity-loading');
    const $emptyState = $('#vh360-activity-empty');
    const $loadMoreWrapper = $('#vh360-load-more-wrapper');
    const $loadMoreBtn = $('#vh360-load-more-btn');
    
    /**
     * Initialize activity feed
     */
    function init() {
        // Event listeners
        $filterTabs.on('click', handleFilterClick);
        $loadMoreBtn.on('click', handleLoadMore);
        
        // Infinite scroll
        if (state.infiniteScrollEnabled) {
            $(window).on('scroll', throttle(handleScroll, 200));
        }
        
        // Parse URL parameters on load
        parseUrlParams();
        
        // Initialize sticky tabs shadow effect
        initStickyTabs();
    }
    
    /**
     * Initialize sticky feed tabs with shadow on scroll
     */
    function initStickyTabs() {
        const $tabs = $('.vh360-feed-tabs');
        if (!$tabs.length) {
            return;
        }
        
        // Add sticky shadow on scroll
        $(window).on('scroll', function() {
            if ($(window).scrollTop() > 0) {
                $tabs.addClass('is-sticky');
            } else {
                $tabs.removeClass('is-sticky');
            }
        });
    }
    
    /**
     * Throttle function
     */
    function throttle(func, wait) {
        let timeout;
        let lastRan;
        
        return function executedFunction(...args) {
            if (!lastRan) {
                func(...args);
                lastRan = Date.now();
            } else {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if ((Date.now() - lastRan) >= wait) {
                        func(...args);
                        lastRan = Date.now();
                    }
                }, wait - (Date.now() - lastRan));
            }
        };
    }
    
    /**
     * Handle filter tab click
     */
    function handleFilterClick() {
        const type = $(this).data('type');
        
        if (type === state.currentType) {
            return; // Already selected
        }
        
        state.currentType = type;
        state.offset = 0;
        state.hasMore = true;
        
        // Update UI
        $filterTabs.removeClass('active');
        $(this).addClass('active');
        
        // Clear stream and load new activities
        $activityStream.empty();
        loadActivities(true);
        
        // Update URL
        updateUrl();
    }
    
    /**
     * Handle load more button click
     */
    function handleLoadMore() {
        loadActivities(false);
    }
    
    /**
     * Handle infinite scroll
     */
    function handleScroll() {
        if (!state.infiniteScrollEnabled || !state.hasMore || state.isLoading) {
            return;
        }
        
        // Check if load more button is in viewport
        if ($loadMoreBtn.length) {
            const btnOffset = $loadMoreBtn.offset().top;
            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            
            if (scrollTop + windowHeight >= btnOffset - 200) {
                loadActivities(false);
            }
        }
    }
    
    /**
     * Load activities via AJAX
     */
    function loadActivities(replace = false) {
        if (state.isLoading) {
            return;
        }
        
        state.isLoading = true;
        
        if (replace) {
            $loading.show();
            $activityStream.addClass('loading');
        } else {
            $loadMoreBtn.prop('disabled', true).addClass('loading');
        }
        
        $emptyState.hide();
        
        const data = {
            action: 'vh360_load_activities',
            nonce: vh360Activity.nonce,
            type: state.currentType,
            offset: state.offset
        };
        
        $.ajax({
            url: vh360Activity.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    const html = response.data.html;
                    
                    if (replace) {
                        $activityStream.html(html);
                        // Scroll to top of stream
                        scrollToStream();
                    } else {
                        // Append new activities with animation
                        const $newItems = $(html);
                        $newItems.hide();
                        $activityStream.append($newItems);
                        $newItems.fadeIn(400);
                    }
                    
                    // Update state
                    state.offset = response.data.offset;
                    
                    // Check if there are more activities
                    if (response.data.count < 20) {
                        state.hasMore = false;
                        $loadMoreWrapper.fadeOut();
                    } else {
                        $loadMoreWrapper.fadeIn();
                    }
                    
                    // Show empty state if no activities
                    if (state.offset === 0) {
                        $emptyState.show();
                        $loadMoreWrapper.hide();
                    }
                } else {
                    if (replace) {
                        $activityStream.empty();
                        $emptyState.show();
                    } else {
                        state.hasMore = false;
                        $loadMoreWrapper.fadeOut();
                    }
                }
            },
            error: function() {
                if (replace) {
                    $activityStream.html('<p class="vh360-error">' + vh360Activity.strings.error + '</p>');
                } else {
                    showError();
                }
            },
            complete: function() {
                state.isLoading = false;
                $loading.hide();
                $activityStream.removeClass('loading');
                $loadMoreBtn.prop('disabled', false).removeClass('loading');
            }
        });
    }
    
    /**
     * Show error message
     */
    function showError() {
        const $error = $('<div class="vh360-activity-error">' + vh360Activity.strings.error + '</div>');
        $activityStream.append($error);
        setTimeout(() => {
            $error.fadeOut(() => $error.remove());
        }, 3000);
    }
    
    /**
     * Update URL with current filter
     */
    function updateUrl() {
        const params = new URLSearchParams();
        
        if (state.currentType !== 'all') {
            params.set('type', state.currentType);
        }
        
        const newUrl = params.toString() ? `?${params.toString()}` : window.location.pathname;
        window.history.replaceState({}, '', newUrl);
    }
    
    /**
     * Parse URL parameters on load
     */
    function parseUrlParams() {
        const params = new URLSearchParams(window.location.search);
        
        if (params.has('type')) {
            const type = params.get('type');
            const $tab = $(`.vh360-filter-tab[data-type="${type}"]`);
            
            if ($tab.length) {
                $tab.trigger('click');
            }
        }
    }
    
    /**
     * Scroll to activity stream
     */
    function scrollToStream() {
        const streamOffset = $activityStream.offset().top - 100;
        $('html, body').animate({
            scrollTop: streamOffset
        }, 400);
    }
    
    /**
     * Check if element is in viewport
     */
    function isInViewport($element) {
        if (!$element.length) {
            return false;
        }
        
        const elementTop = $element.offset().top;
        const elementBottom = elementTop + $element.outerHeight();
        const viewportTop = $(window).scrollTop();
        const viewportBottom = viewportTop + $(window).height();
        
        return elementBottom > viewportTop && elementTop < viewportBottom;
    }
    
    /**
     * Update activity timestamps periodically
     */
    function updateTimestamps() {
        // This could be enhanced to update timestamps in real-time
        // For now, we'll just refresh on filter change
    }
    
    /**
     * Enable/disable infinite scroll
     */
    function toggleInfiniteScroll(enable) {
        state.infiniteScrollEnabled = enable;
        
        if (enable) {
            $(window).on('scroll', throttle(handleScroll, 200));
        } else {
            $(window).off('scroll');
        }
    }
    
    /**
     * Add activity in real-time (for future WebSocket integration)
     */
    function addActivityRealtime(activity) {
        // Placeholder for future real-time activity updates
        // Could use WebSockets or Server-Sent Events
    }
    
    /**
     * Mobile Compose Modal functionality
     */
    function initMobileCompose() {
        const $composeFab = $('.vh360-mobile-compose-fab');
        const $composeModal = $('.vh360-mobile-compose-modal');
        const $modalClose = $('.vh360-modal-close');
        
        if (!$composeFab.length || !$composeModal.length) {
            return;
        }
        
        // Open modal on FAB click
        $composeFab.on('click', function(e) {
            e.preventDefault();
            $composeModal.addClass('active');
            // Focus on textarea after animation
            setTimeout(function() {
                $composeModal.find('.vh360-post-textarea').focus();
            }, 300);
        });
        
        // Close modal on close button click
        $modalClose.on('click', function(e) {
            e.preventDefault();
            $composeModal.removeClass('active');
        });
        
        // Close modal on backdrop click
        $composeModal.on('click', function(e) {
            if ($(e.target).hasClass('vh360-mobile-compose-modal')) {
                $composeModal.removeClass('active');
            }
        });
        
        // Prevent closing when clicking inside the sheet
        $('.vh360-mobile-compose-sheet').on('click', function(e) {
            e.stopPropagation();
        });
    }
    
    /**
     * Handle share button click
     */
    function handleShareClick(event) {
        const $btn = $(event.currentTarget);
        const postId = $btn.data('post-id');
        const nonce = $btn.data('nonce');
        
        if (!postId) {
            console.error('Share: Missing post ID');
            return;
        }
        
        // Show share UI (copy link, modal, etc.)
        showShareModal(postId);
        
        // Track share via AJAX
        trackShare(postId, nonce);
    }
    
    /**
     * Track share via AJAX
     */
    function trackShare(postId, nonce) {
        $.ajax({
            url: vh360Activity.ajaxurl,
            type: 'POST',
            data: {
                action: 'vh360_increment_share',
                post_id: postId,
                nonce: nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.count) {
                    updateShareCount(postId, response.data.count);
                }
            },
            error: function(xhr, status, error) {
                console.error('Share tracking failed:', error);
            }
        });
    }
    
    /**
     * Update share count in DOM
     */
    function updateShareCount(postId, newCount) {
        const $statsRow = $('.vh360-post-stats[data-post-id="' + postId + '"]');
        const $shareCount = $statsRow.find('.vh360-stat-count[data-stat="shares"]');
        
        if ($shareCount.length) {
            // Update existing count
            $shareCount.text(vh360FormatNumber(newCount));
        } else {
            // Add share stat if it didn't exist before
            addShareStatToRow($statsRow, newCount);
        }
    }
    
    /**
     * Add share stat to stats row (if it didn't exist)
     */
    function addShareStatToRow($statsRow, count) {
        // Check if stats row is empty (no likes/comments)
        const hasOtherStats = $statsRow.find('.vh360-post-stat').length > 0;
        
        let shareHtml = '';
        
        if (hasOtherStats) {
            shareHtml += '<span class="vh360-stat-separator">•</span>';
        }
        
        shareHtml += '<span class="vh360-post-stat vh360-post-stat-shares">';
        shareHtml += '<span class="vh360-stat-count" data-stat="shares">' + vh360FormatNumber(count) + '</span>';
        
        // Use localized strings for singular/plural
        const label = count === 1 ? vh360Activity.strings.share : vh360Activity.strings.shares;
        shareHtml += '<span class="vh360-stat-label">' + label + '</span>';
        shareHtml += '</span>';
        
        $statsRow.append(shareHtml);
        
        // Show stats row if it was hidden
        $statsRow.show();
    }
    
    /**
     * Scroll to comments section
     */
    function scrollToComments(event) {
        event.preventDefault();
        
        const $link = $(event.currentTarget);
        const postId = $link.data('post-id');
        const targetId = '#vh360-comments-section-' + postId;
        const $target = $(targetId);
        
        if ($target.length) {
            const offset = $target.offset().top - 100; // 100px from top for header
            
            $('html, body').animate({
                scrollTop: offset
            }, 400, function() {
                // Focus first comment or comment input for accessibility
                const $firstComment = $target.find('.vh360-comment').first();
                if ($firstComment.length) {
                    $firstComment.attr('tabindex', '-1').focus();
                }
            });
        }
    }
    
    /**
     * Format number for display (e.g., 1000 -> 1K)
     * Matches PHP vh360_format_number() implementation
     */
    function vh360FormatNumber(num) {
        num = Math.abs(parseInt(num));
        
        if (num < 1000) {
            return num.toString();
        }
        
        if (num < 1000000) {
            var formatted = Math.round(num / 100) / 10; // Same as PHP round($num / 1000, 1)
            return formatted + 'K';
        }
        
        if (num < 1000000000) {
            var formatted = Math.round(num / 100000) / 10; // Same as PHP round($num / 1000000, 1)
            return formatted + 'M';
        }
        
        var formatted = Math.round(num / 100000000) / 10; // Same as PHP round($num / 1000000000, 1)
        return formatted + 'B';
    }
    
    /**
     * Show share modal/UI (implement based on existing theme pattern)
     */
    function showShareModal(postId) {
        // Try to get post URL from the current page if available
        const $post = $('.vh360-community-post[data-post-id="' + postId + '"]');
        let postUrl;
        
        // Check if we're on a single post page
        if ($('body').hasClass('single-vh360_post')) {
            postUrl = window.location.href;
        } else {
            // For activity feed, construct URL using WordPress pretty permalinks
            // This will work with most permalink structures
            postUrl = window.location.origin + '/?post_type=vh360_post&p=' + postId;
        }
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(postUrl).then(function() {
                showShareNotification(vh360Activity.strings.shareSuccess || 'Link copied to clipboard!');
            }).catch(function(err) {
                console.error('Could not copy link:', err);
                showShareNotification(vh360Activity.strings.shareError || 'Could not copy link');
            });
        } else {
            // Fallback for older browsers
            showShareNotification('Share link: ' + postUrl);
        }
    }
    
    /**
     * Show share notification
     */
    function showShareNotification(message) {
        // Create simple notification element - CSS is now in utilities.css
        const $notification = $('<div class="vh360-share-notification">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $notification.remove();
            });
        }, 3000);
    }
    
    /**
     * Collapsible Comments functionality
     */
    function initCollapsibleComments() {
        // Handle toggle replies button click (new class name)
        $(document).on('click', '.vh360-toggle-replies', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const commentId = $btn.data('comment-id');
            const $repliesList = $btn.siblings('.vh360-replies-list');
            
            if ($repliesList.hasClass('vh360-replies-list--hidden')) {
                // Show replies
                $repliesList.removeClass('vh360-replies-list--hidden');
                $btn.text($btn.text().replace('View', 'Hide'));
            } else {
                // Hide replies
                $repliesList.addClass('vh360-replies-list--hidden');
                $btn.text($btn.text().replace('Hide', 'View'));
            }
        });
        
        // Legacy support for old class name
        $(document).on('click', '.vh360-view-replies-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const commentId = $btn.data('comment-id');
            const $replies = $('.vh360-comment-replies[data-parent-id="' + commentId + '"]');
            
            if ($replies.hasClass('collapsed')) {
                // Expand replies
                $replies.removeClass('collapsed');
                $btn.addClass('expanded');
                $btn.find('.vh360-reply-count-text').text($btn.find('.vh360-reply-count-text').text().replace('View', 'Hide'));
            } else {
                // Collapse replies
                $replies.addClass('collapsed');
                $btn.removeClass('expanded');
                $btn.find('.vh360-reply-count-text').text($btn.find('.vh360-reply-count-text').text().replace('Hide', 'View'));
            }
        });
    }
    
    /**
     * Handle Reply button click - toggle reply form for specific comment
     * Support both new (.vh360-action-reply) and old (.vh360-comment-reply-btn) class names
     */
    $(document).on('click', '.vh360-action-reply, .vh360-comment-reply-btn', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const commentId = $btn.data('comment-id');
        const $comment = $btn.closest('.vh360-comment-item, .vh360-comment');
        const $replyForm = $comment.find('.vh360-comment-reply-form');
        
        // If reply form doesn't exist, create it
        if ($replyForm.length === 0) {
            // Get the comment author username for @mention (not display name)
            const commentAuthor = $comment.find('.vh360-comment-author').first().data('username') || '';
            createReplyForm(commentId, $comment, commentAuthor);
        } else {
            // Toggle visibility
            $replyForm.slideToggle(200, function() {
                if ($replyForm.is(':visible')) {
                    $replyForm.find('textarea').focus();
                }
            });
        }
        
        // Optional: Close other open reply forms
        // $('.vh360-comment-reply-form').not($replyForm).slideUp(200);
    });
    
    /**
     * Create reply form for a comment
     */
    function createReplyForm(commentId, $comment, commentAuthor) {
        const currentUserId = vh360Activity.currentUserId || '';
        const currentUserAvatar = vh360Activity.currentUserAvatar || '';
        
        // Prepare @mention if author name is provided
        const mentionText = commentAuthor ? `@${commentAuthor} ` : '';
        
        const replyFormHtml = `
            <div class="vh360-comment-reply-form" 
                 data-comment-id="${commentId}" 
                 style="display: none;">
                <div class="vh360-reply-composer">
                    <div class="vh360-reply-avatar">
                        <img src="${currentUserAvatar}" alt="" class="vh360-avatar-img">
                    </div>
                    <div class="vh360-reply-input-wrapper">
                        <textarea 
                            class="vh360-reply-textarea" 
                            placeholder="Write a reply..." 
                            data-parent-id="${commentId}"
                            aria-label="Reply to comment"
                            rows="1">${mentionText}</textarea>
                        <button class="vh360-reply-send-btn" 
                                type="button" 
                                disabled
                                aria-label="Send reply">
                            <span class="vh360-btn-text">
                                <svg class="vh360-reply-send-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/>
                                </svg>
                            </span>
                            <span class="sr-only">Send</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Insert after the entire comment row (not inside the grid)
        const $commentRow = $comment.find('.vh360-comment-row').first();
        if ($commentRow.length > 0) {
            $commentRow.after(replyFormHtml);
        } else {
            // Fallback: insert after actions if comment-row not found (shouldn't happen)
            const $actions = $comment.find('.vh360-comment-actions').first();
            if ($actions.length > 0) {
                $actions.after(replyFormHtml);
            } else {
                // Last fallback: append to comment
                $comment.append(replyFormHtml);
            }
        }
        
        // Show and focus
        const $newForm = $comment.find('.vh360-comment-reply-form');
        $newForm.slideDown(200, function() {
            const $textarea = $newForm.find('textarea');
            $textarea.focus();
            // Set initial height
            $textarea.css('height', '40px');
            
            // If there's a mention, move cursor to end of text
            if (mentionText) {
                const textLength = $textarea.val().length;
                $textarea[0].setSelectionRange(textLength, textLength);
            }
        });
        
        // Bind textarea events (auto-grow, enable send button)
        bindReplyFormEvents($newForm);
    }
    
    /**
     * Bind events to reply form
     */
    function bindReplyFormEvents($form) {
        const $textarea = $form.find('.vh360-reply-textarea');
        const $sendBtn = $form.find('.vh360-reply-send-btn');
        
        // Auto-grow textarea with proper min/max constraints
        $textarea.on('input', function() {
            const minHeight = 40;
            const maxHeight = 150;
            
            // Reset height to auto to get proper scrollHeight
            this.style.height = 'auto';
            
            // Calculate new height based on content
            let newHeight = this.scrollHeight;
            
            // Apply min/max constraints
            newHeight = Math.max(minHeight, Math.min(newHeight, maxHeight));
            
            // Set new height
            this.style.height = newHeight + 'px';
            
            // Add/remove scrollbar class if content exceeds max height
            if (this.scrollHeight > maxHeight) {
                $(this).addClass('vh360-textarea-scrollable');
            } else {
                $(this).removeClass('vh360-textarea-scrollable');
            }
            
            // Enable/disable send button based on content
            const content = $(this).val().trim();
            if (content.length > 0) {
                $sendBtn.prop('disabled', false).addClass('vh360-btn-active');
            } else {
                $sendBtn.prop('disabled', true).removeClass('vh360-btn-active');
            }
        });
        
        // Send on Enter (Shift+Enter for new line)
        $textarea.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!$sendBtn.prop('disabled')) {
                    $sendBtn.click();
                }
            }
        });
        
        // Send button click
        $sendBtn.on('click', function() {
            submitReply($textarea);
        });
    }
    
    /**
     * Submit reply via AJAX
     */
    function submitReply($textarea) {
        const content = $textarea.val().trim();
        const parentId = $textarea.data('parent-id');
        const $post = $textarea.closest('.vh360-community-post');
        const postId = $post.data('post-id');
        const $replyForm = $textarea.closest('.vh360-comment-reply-form');
        
        // Don't submit if empty
        if (!content) return;
        
        const $sendBtn = $textarea.siblings('.vh360-reply-send-btn');
        
        // Don't submit if already submitting
        if ($sendBtn.hasClass('vh360-btn-loading')) {
            return;
        }
        
        // Show loading state
        $sendBtn.addClass('vh360-btn-loading').prop('disabled', true);
        $sendBtn.find('.vh360-btn-text, svg').hide();
        if ($sendBtn.find('.vh360-btn-spinner').length === 0) {
            $sendBtn.append('<span class="vh360-btn-spinner" aria-hidden="true"></span>');
        }
        $sendBtn.find('.vh360-btn-spinner').show();
        $textarea.prop('disabled', true);
        
        // Clear any previous errors
        $replyForm.find('.vh360-reply-error').remove();
        
        $.ajax({
            url: vh360Activity.ajaxurl,
            type: 'POST',
            data: {
                action: 'vh360_add_activity_comment',
                nonce: vh360Activity.commentNonce,
                post_id: postId,
                parent_id: parentId,
                comment: content
            },
            success: function(response) {
                if (response.success) {
                    // Clear form
                    $textarea.val('').css('height', '40px');
                    $textarea.removeClass('vh360-textarea-scrollable');
                    $sendBtn.prop('disabled', true).removeClass('vh360-btn-active');
                    
                    // Refresh entire comments section for this post
                    if (response.data.html) {
                        const $commentsSection = $post.find('.vh360-comments-section');
                        const $commentsList = $commentsSection.find('.vh360-comments-list');
                        
                        if ($commentsList.length) {
                            $commentsList.html(response.data.html);
                        }
                    }
                    
                    // Update comment count in post stats with actual count from server (Fix #4)
                    if (response.data.new_comment_count !== undefined) {
                        updateCommentCountAbsolute(postId, response.data.new_comment_count);
                    }
                    
                    // Hide reply form
                    $replyForm.slideUp(200);
                    
                    // Show success flash on parent comment
                    const $comment = $replyForm.closest('.vh360-comment');
                    $comment.addClass('vh360-comment-success-flash');
                    setTimeout(function() {
                        $comment.removeClass('vh360-comment-success-flash');
                    }, 1000);
                } else {
                    // Show error message
                    const errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'Failed to submit reply. Please try again.';
                    showReplyError($replyForm, errorMsg);
                }
            },
            error: function() {
                showReplyError($replyForm, 'Network error. Please check your connection and try again.');
            },
            complete: function() {
                // Reset button state
                $sendBtn.removeClass('vh360-btn-loading').prop('disabled', false);
                $sendBtn.find('.vh360-btn-text, svg').show();
                $sendBtn.find('.vh360-btn-spinner').hide();
                $textarea.prop('disabled', false);
            }
        });
    }
    
    /**
     * Show error message in reply form
     */
    function showReplyError($form, message) {
        // Remove any existing error
        $form.find('.vh360-reply-error').remove();
        
        // Add error message (using text() for XSS protection)
        const $error = $('<div class="vh360-reply-error"></div>').text(message);
        $form.prepend($error);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $error.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Update comment count in post stats row (incremental)
     */
    function updateCommentCount(postId, increment) {
        const $post = $(`.vh360-community-post[data-post-id="${postId}"]`);
        const $commentStat = $post.find('.vh360-post-stat-comments, .vh360-stat-comments');
        
        if ($commentStat.length) {
            const $countElem = $commentStat.find('.vh360-stat-count[data-stat="comments"]');
            const currentCount = parseInt($countElem.text()) || 0;
            const newCount = currentCount + increment;
            
            // Update count
            $countElem.text(vh360FormatNumber(newCount));
            
            // Update label (singular/plural)
            const $labelElem = $commentStat.find('.vh360-stat-label');
            if ($labelElem.length) {
                const commentText = newCount === 1 ? 'COMMENT' : 'COMMENTS';
                $labelElem.text(commentText);
            }
        }
    }
    
    /**
     * Update comment count in post stats row (absolute value from server)
     * Use this when you have the exact count from server response
     */
    function updateCommentCountAbsolute(postId, newCount) {
        const $post = $(`.vh360-community-post[data-post-id="${postId}"], .vh360-shared-post[data-post-id="${postId}"]`);
        const $commentCount = $post.find('.vh360-stat-count[data-stat="comments"]');
        const $commentLabel = $post.find('.vh360-stat-comments .vh360-stat-label, .vh360-stat-item[data-action="comments"] .vh360-stat-label');
        
        if ($commentCount.length) {
            $commentCount.text(vh360FormatNumber(newCount));
            
            // Update label (singular/plural)
            if ($commentLabel.length) {
                const label = newCount === 1 ? 'COMMENT' : 'COMMENTS';
                $commentLabel.text(label);
            }
        }
    }
    
    /**
     * Handle comment like button click
     * Support both new (.vh360-action-like) and old (.vh360-comment-like-btn) class names
     */
    $(document).on('click', '.vh360-action-like, .vh360-comment-like-btn', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const commentId = $btn.data('comment-id');
        const $countEl = $btn.siblings('.vh360-like-count, .vh360-comment-like-count');
        
        // Check if button is disabled (not logged in)
        if ($btn.prop('disabled')) {
            return;
        }
        
        // Prevent double-clicks
        if ($btn.hasClass('processing')) {
            return;
        }
        
        $btn.addClass('processing');
        
        $.ajax({
            url: vh360Activity.ajaxurl,
            type: 'POST',
            data: {
                action: 'vh360_toggle_comment_like',
                nonce: vh360Activity.nonce,
                comment_id: commentId
            },
            success: function(response) {
                if (response.success) {
                    // Toggle liked state - support both old and new class names
                    if (response.data.liked) {
                        $btn.addClass('vh360-liked liked');
                    } else {
                        $btn.removeClass('vh360-liked liked');
                    }
                    
                    // Update count
                    const newCount = response.data.count;
                    if (newCount > 0) {
                        if ($countEl.length) {
                            // Update existing count with proper format
                            const countText = newCount === 1 ? newCount + ' like' : newCount + ' likes';
                            $countEl.text(countText);
                        } else {
                            // Add new count element
                            const countText = newCount === 1 ? newCount + ' like' : newCount + ' likes';
                            $btn.after('<span class="vh360-like-count">' + countText + '</span>');
                        }
                    } else {
                        $countEl.remove();
                    }
                } else {
                    // Show error (console or notification)
                    console.error('Failed to toggle like:', response.data && response.data.message);
                }
            },
            error: function() {
                console.error('AJAX error toggling comment like');
            },
            complete: function() {
                $btn.removeClass('processing');
            }
        });
    });
    
    /**
     * Main Comment Form Enhancement
     * Handles auto-grow textarea, Enter key submission, and send button
     */
    const VH360CommentForm = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Auto-grow main comment textarea
            $(document).on('input', '.vh360-comment-textarea', function() {
                self.autoGrowTextarea(this);
                self.toggleSendButton(this);
            });
            
            // Enter key to submit (Shift+Enter for new line)
            $(document).on('keydown', '.vh360-comment-textarea', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const $sendBtn = $(this).closest('.vh360-comment-input-wrapper').find('.vh360-comment-send-btn');
                    if (!$sendBtn.prop('disabled')) {
                        self.submitComment(this);
                    }
                }
            });
            
            // Send button click
            $(document).on('click', '.vh360-comment-send-btn', function(e) {
                e.preventDefault();
                const $textarea = $(this).closest('.vh360-comment-input-wrapper').find('.vh360-comment-textarea');
                self.submitComment($textarea[0]);
            });
        },
        
        autoGrowTextarea: function(textarea) {
            const $textarea = $(textarea);
            const minHeight = 40;
            const maxHeight = 150;
            
            // Reset height to auto to get proper scrollHeight
            $textarea.css('height', 'auto');
            let newHeight = textarea.scrollHeight;
            newHeight = Math.max(minHeight, Math.min(newHeight, maxHeight));
            $textarea.css('height', newHeight + 'px');
        },
        
        toggleSendButton: function(textarea) {
            const $textarea = $(textarea);
            const $sendBtn = $textarea.closest('.vh360-comment-input-wrapper').find('.vh360-comment-send-btn');
            const content = $textarea.val().trim();
            
            if (content.length > 0) {
                $sendBtn.prop('disabled', false).addClass('vh360-btn-active');
            } else {
                $sendBtn.prop('disabled', true).removeClass('vh360-btn-active');
            }
        },
        
        submitComment: function(textarea) {
            const $textarea = $(textarea);
            const $form = $textarea.closest('.vh360-comment-form');
            const $sendBtn = $form.find('.vh360-comment-send-btn');
            const content = $textarea.val().trim();
            
            if (!content || $sendBtn.hasClass('vh360-btn-loading')) {
                return;
            }
            
            const postId = $form.data('post-id');
            
            if (!postId) {
                console.error('No post ID found');
                return;
            }
            
            // Show loading
            $sendBtn.addClass('vh360-btn-loading').prop('disabled', true);
            $textarea.prop('disabled', true);
            
            $.ajax({
                url: vh360Activity.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_submit_comment',
                    nonce: vh360Activity.commentNonce,
                    post_id: postId,
                    content: content
                },
                success: function(response) {
                    if (response.success) {
                        // Clear textarea
                        $textarea.val('').css('height', '40px');
                        
                        // Refresh comments section
                        if (response.data.html) {
                            const $post = $form.closest('.vh360-community-post, .vh360-shared-post');
                            const $commentsSection = $post.find('.vh360-comments-section');
                            const $commentsList = $commentsSection.find('.vh360-comments-list');
                            
                            if ($commentsList.length) {
                                $commentsList.html(response.data.html);
                            }
                        }
                        
                        // UPDATE COMMENT COUNT IN STATS (FIX #4)
                        if (response.data.new_comment_count !== undefined) {
                            const $post = $form.closest('.vh360-community-post, .vh360-shared-post');
                            const postId = $post.data('post-id');
                            updateCommentCountAbsolute(postId, response.data.new_comment_count);
                        }
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Failed to post comment');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                },
                complete: function() {
                    $sendBtn.removeClass('vh360-btn-loading').prop('disabled', false);
                    $textarea.prop('disabled', false);
                    
                    // Reset button state
                    const content = $textarea.val().trim();
                    if (content.length === 0) {
                        $sendBtn.prop('disabled', true).removeClass('vh360-btn-active');
                    }
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        init();
        initMobileCompose();
        initCollapsibleComments();
        VH360CommentForm.init(); // Initialize main comment form
        
        // Share button click
        $(document).on('click', '.vh360-share-btn', handleShareClick);
        
        // Scroll to comments
        $(document).on('click', '.vh360-scroll-to-comments', scrollToComments);
    });
    
    // Expose some methods for external use
    window.vh360ActivityFeed = {
        reload: function() {
            state.offset = 0;
            state.hasMore = true;
            $activityStream.empty();
            loadActivities(true);
        },
        toggleInfiniteScroll: toggleInfiniteScroll
    };
    
})(jQuery);

/**
 * VH360 Share System
 * Handles Facebook-style post sharing with modal and interactive stats
 */
const VH360ShareSystem = (function($) {
    'use strict';
    
    let currentPostId = null;
    
    /**
     * Format number for display (e.g., 1000 -> 1K)
     * Matches PHP vh360_format_number() implementation
     */
    function vh360FormatNumber(num) {
        num = Math.abs(parseInt(num));
        
        if (num < 1000) {
            return num.toString();
        }
        
        if (num < 1000000) {
            var formatted = Math.round(num / 100) / 10;
            return formatted + 'K';
        }
        
        var formatted = Math.round(num / 100000) / 10;
        return formatted + 'M';
    }
    
    /**
     * Initialize the share system
     */
    function init() {
        bindStatButtonHandlers();
        bindModalHandlers();
    }
    
    /**
     * Bind click handlers to stat buttons
     * Note: Like functionality is handled by community.js (loaded globally)
     * This handles only comments and share buttons to avoid conflicts
     */
    function bindStatButtonHandlers() {
        // Use event delegation for dynamically loaded posts
        $(document).on('click', '.vh360-stat-item', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const action = $btn.data('action');
            const postId = $btn.data('post-id');
            
            switch(action) {
                case 'comments':
                    scrollToComments(postId);
                    break;
                case 'share':
                    openShareModal(postId);
                    break;
                // Like is handled by community.js to avoid duplicate handlers
            }
        });
    }
    
    /**
     * Scroll to comments section
     */
    function scrollToComments(postId) {
        const $commentsSection = $('#vh360-comments-section-' + postId);
        if ($commentsSection.length) {
            const offset = $commentsSection.offset().top - 100;
            $('html, body').animate({
                scrollTop: offset
            }, 400, function() {
                // Focus on comment input for accessibility
                const $textarea = $commentsSection.find('textarea').first();
                if ($textarea.length) {
                    $textarea.focus();
                }
            });
        }
    }
    
    /**
     * Open share modal
     */
    function openShareModal(postId) {
        currentPostId = postId;
        const $modal = $('#vh360-share-modal');
        const $preview = $('#vh360-share-preview');
        const $textarea = $('#vh360-share-comment');
        
        // Clear previous content
        $textarea.val('');
        $preview.html('');
        
        // Get post content for preview
        const $post = $('.vh360-community-post[data-post-id="' + postId + '"]');
        if ($post.length) {
            // Clone the post content for preview
            const $postClone = $post.clone();
            
            // Remove actions, comments, and stats from preview
            $postClone.find('.vh360-community-actions').remove();
            $postClone.find('.vh360-comments-section').remove();
            $postClone.find('.vh360-post-stats').remove();
            $postClone.find('.vh360-community-footer').remove();
            
            // Wrap in original post card styling
            const previewHtml = '<div class="vh360-original-post-card">' + 
                               $postClone.html() + 
                               '</div>';
            $preview.html(previewHtml);
        }
        
        // Show modal
        $modal.addClass('show');
        $('body').addClass('vh360-modal-open');
        
        // Focus textarea
        setTimeout(function() {
            $textarea.focus();
        }, 300);
    }
    
    /**
     * Close share modal
     */
    function closeShareModal() {
        const $modal = $('#vh360-share-modal');
        const $textarea = $('#vh360-share-comment');
        const $btn = $('#vh360-share-timeline-btn');
        
        $modal.removeClass('show');
        $('body').removeClass('vh360-modal-open');
        
        // Clear textarea
        $textarea.val('');
        
        // Reset button state
        $btn.prop('disabled', false).text('Share to Timeline');
        
        // Clear preview
        $('#vh360-share-preview').html('');
        
        currentPostId = null;
    }
    
    /**
     * Bind modal-related event handlers
     */
    function bindModalHandlers() {
        // Close modal button
        $(document).on('click', '.vh360-modal-close', function(e) {
            e.preventDefault();
            closeShareModal();
        });
        
        // Close on overlay click
        $(document).on('click', '#vh360-share-modal', function(e) {
            if ($(e.target).is('#vh360-share-modal')) {
                closeShareModal();
            }
        });
        
        // Close on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#vh360-share-modal').hasClass('show')) {
                closeShareModal();
            }
        });
        
        // Share to timeline button
        $(document).on('click', '#vh360-share-timeline-btn', function(e) {
            e.preventDefault();
            shareToTimeline();
        });
    }
    
    /**
     * Share post to timeline
     */
    function shareToTimeline() {
        if (!currentPostId) {
            console.error('No post selected for sharing');
            return;
        }
        
        // Check if vh360Activity is defined
        if (typeof vh360Activity === 'undefined') {
            console.error('vh360Activity is not defined');
            showNotification('Unable to share post. Please refresh the page and try again.', 'error');
            return;
        }
        
        const $btn = $('#vh360-share-timeline-btn');
        const $textarea = $('#vh360-share-comment');
        const shareComment = $textarea.val().trim();
        
        // Disable button and show loading
        $btn.prop('disabled', true).text('Sharing...');
        
        $.ajax({
            url: vh360Activity.ajaxurl,
            type: 'POST',
            data: {
                action: 'vh360_share_post',
                post_id: currentPostId,
                share_comment: shareComment,
                share_type: 'timeline',
                nonce: vh360Activity.nonce
            },
            success: function(response) {
                if (response && response.success) {
                    // Update share count on original post
                    if (response.data.share_count && response.data.post_id) {
                        updateShareCount(response.data.post_id, response.data.share_count);
                    }
                    
                    // Prepend new shared post to feed if we have HTML
                    if (response.data.post_html) {
                        const $newPost = $(response.data.post_html);
                        $newPost.hide();
                        $('#vh360-activity-stream').prepend($newPost);
                        $newPost.fadeIn(400);
                    }
                    
                    // Close modal
                    closeShareModal();
                    
                    // Show success notification
                    showNotification('Post shared successfully!', 'success');
                } else {
                    const message = response && response.data && response.data.message 
                        ? response.data.message 
                        : 'Failed to share post';
                    showNotification(message, 'error');
                    
                    // Reset button on error
                    $btn.prop('disabled', false).text('Share to Timeline');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showNotification('Network error. Please try again.', 'error');
                
                // Reset button on error
                $btn.prop('disabled', false).text('Share to Timeline');
            },
            complete: function() {
                // Only reset if not already reset in error handlers
                if (!$btn.prop('disabled')) {
                    return;
                }
                $btn.prop('disabled', false).text('Share to Timeline');
            },
            timeout: 30000 // 30 second timeout
        });
    }
    
    /**
     * Update share count in stats row
     */
    function updateShareCount(postId, newCount) {
        const $statsRow = $('.vh360-post-stats[data-post-id="' + postId + '"]');
        const $shareBtn = $statsRow.find('.vh360-stat-shares');
        const $countElem = $shareBtn.find('.vh360-stat-count');
        
        if ($countElem.length) {
            $countElem.text(vh360FormatNumber(newCount));
            $shareBtn.show();
        } else {
            // Add share button if it doesn't exist
            addShareStatToRow($statsRow, newCount);
        }
        
        $statsRow.show();
    }
    
    /**
     * Add share stat button to row if it doesn't exist
     */
    function addShareStatToRow($statsRow, count) {
        const hasOtherStats = $statsRow.find('.vh360-stat-item').length > 0;
        
        let html = '';
        if (hasOtherStats) {
            html += '<span class="vh360-stat-separator">•</span>';
        }
        
        html += '<button class="vh360-stat-item vh360-stat-shares" ' +
                'data-post-id="' + $statsRow.data('post-id') + '" ' +
                'data-action="share" ' +
                'type="button" ' +
                'aria-label="Share this post">' +
                '<span class="vh360-stat-count" data-stat="shares">' + vh360FormatNumber(count) + '</span>' +
                '<span class="vh360-stat-label">SHARES</span>' +
                '</button>';
        
        $statsRow.append(html);
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        const $notification = $('<div class="vh360-notification ' + (type || 'success') + '">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $notification.remove();
            });
        }, 3000);
    }
    
    // Public API
    return {
        init: init,
        openShareModal: openShareModal,
        closeShareModal: closeShareModal
    };
    
})(jQuery);

// Initialize share system on document ready
jQuery(document).ready(function() {
    VH360ShareSystem.init();
});

// Expose to global scope for external use
window.VH360ShareSystem = VH360ShareSystem;
