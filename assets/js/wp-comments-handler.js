/**
 * WordPress Comments JavaScript Handler (YouTube-style)
 *
 * Handles client-side interactions for YouTube-style WordPress comments:
 * - Comment likes with AJAX
 * - Reply toggle functionality
 * - Kebab menu interactions
 * - Comment form handling with loading states
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Debug logging helpers
    function vh360IsDebug() {
        return typeof window.__VH360_DEBUG !== 'undefined' && window.__VH360_DEBUG === true;
    }
    
    function vh360Log() {
        if (vh360IsDebug() && console && console.log) {
            console.log.apply(console, arguments);
        }
    }
    
    function vh360Warn() {
        if (vh360IsDebug() && console && console.warn) {
            console.warn.apply(console, arguments);
        }
    }
    
    /**
     * WordPress Comment Handler Class
     */
    var WPCommentHandler = {
        
        /**
         * Initialize the comment handler
         */
        init: function() {
            this.bindEvents();
            vh360Log('WPCommentHandler initialized');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Like button clicks
            $(document).on('click', '.vh360-wp-comment-like', function(e) {
                e.preventDefault();
                self.handleLike($(this));
            });
            
            // Reply button clicks
            $(document).on('click', '.vh360-wp-comment-reply', function(e) {
                e.preventDefault();
                self.handleReply($(this));
            });
            
            // Toggle replies button clicks
            $(document).on('click', '.vh360-wp-toggle-replies', function(e) {
                e.preventDefault();
                self.toggleReplies($(this));
            });
            
            // Kebab menu toggle
            $(document).on('click', '.vh360-kebab-toggle', function(e) {
                e.preventDefault();
                self.toggleKebabMenu($(this));
            });
            
            // Close kebab menu when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.vh360-comment-actions-menu-wrapper').length) {
                    $('.vh360-actions-menu').removeClass('vh360-actions-menu--open').addClass('vh360-actions-menu--hidden');
                    $('.vh360-kebab-toggle').attr('aria-expanded', 'false');
                }
            });
            
            // Edit button (placeholder - would need full implementation)
            $(document).on('click', '.vh360-wp-comment-edit-btn', function(e) {
                e.preventDefault();
                vh360Log('Edit comment:', $(this).data('comment-id'));
                // TODO: Implement inline editing if needed
                alert('Edit functionality would be implemented here. For now, please use the WordPress admin.');
            });
            
            // Delete button
            $(document).on('click', '.vh360-wp-comment-delete-btn', function(e) {
                e.preventDefault();
                self.handleDelete($(this));
            });
            
            // Comment form submit
            $('.vh360-wp-comment-form').on('submit', function(e) {
                self.handleFormSubmit($(this));
            });
            
            // Auto-expand textarea on focus
            $('.vh360-comment-textarea').on('focus', function() {
                $(this).attr('rows', 5);
            });
            
            // Auto-collapse textarea on blur if empty
            $('.vh360-comment-textarea').on('blur', function() {
                if ($(this).val().trim() === '') {
                    $(this).attr('rows', 3);
                }
            });
        },
        
        /**
         * Handle comment like/unlike
         */
        handleLike: function($button) {
            var commentId = $button.data('comment-id');
            
            if (!commentId) {
                vh360Warn('No comment ID found for like button');
                return;
            }
            
            // Check if nonce is available
            if (typeof vh360CommentsData === 'undefined' || !vh360CommentsData.activityNonce) {
                vh360Warn('Comment like nonce not available');
                this.showError($button, 'Unable to like comment. Please refresh the page.');
                return;
            }
            
            // Disable button during request
            $button.prop('disabled', true);
            
            // Store current state
            var wasLiked = $button.hasClass('vh360-liked');
            var $likeCount = $('.vh360-like-count[data-comment-id="' + commentId + '"]');
            var currentCount = parseInt($likeCount.text().match(/\d+/) || 0);
            
            // Optimistic UI update
            $button.toggleClass('vh360-liked');
            $button.attr('aria-pressed', !wasLiked);
            
            $.ajax({
                url: vh360CommentsData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_toggle_comment_like',
                    comment_id: commentId,
                    nonce: vh360CommentsData.activityNonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var liked = response.data.liked;
                        var count = response.data.count;
                        
                        // Update button state
                        $button.toggleClass('vh360-liked', liked);
                        $button.attr('aria-pressed', liked);
                        
                        // Update like count
                        if (count > 0) {
                            var likeText = count === 1 ? '1 like' : count + ' likes';
                            if ($likeCount.length) {
                                $likeCount.text(likeText);
                            } else {
                                // Add like count if it doesn't exist
                                $button.parent().append('<span class="vh360-like-count" data-comment-id="' + commentId + '" aria-live="polite">' + likeText + '</span>');
                            }
                        } else {
                            // Remove like count if zero
                            $likeCount.remove();
                        }
                        
                        vh360Log('Comment like toggled:', { commentId: commentId, liked: liked, count: count });
                    } else {
                        // Revert on error
                        $button.toggleClass('vh360-liked', wasLiked);
                        $button.attr('aria-pressed', wasLiked);
                        vh360Warn('Failed to toggle comment like:', response);
                    }
                },
                error: function(xhr, status, error) {
                    // Revert on error
                    $button.toggleClass('vh360-liked', wasLiked);
                    $button.attr('aria-pressed', wasLiked);
                    vh360Warn('AJAX error toggling comment like:', error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },
        
        /**
         * Handle reply button click
         */
        handleReply: function($button) {
            var commentId = $button.data('comment-id');
            var respondId = $button.data('respond-id') || 'respond';
            
            vh360Log('Reply to comment:', commentId);
            
            // Use WordPress's built-in comment reply functionality
            if (typeof addComment !== 'undefined' && typeof addComment.moveForm === 'function') {
                var commentElement = $button.closest('.vh360-comment-item').attr('id');
                addComment.moveForm(commentElement, commentId, respondId, commentId);
            } else {
                vh360Warn('WordPress comment reply function not available');
            }
        },
        
        /**
         * Toggle replies visibility
         */
        toggleReplies: function($button) {
            var commentId = $button.data('comment-id');
            var $repliesList = $button.closest('.vh360-comment-item').find('> .vh360-replies-list').first();
            
            if (!$repliesList.length) {
                vh360Warn('Replies list not found for comment:', commentId);
                return;
            }
            
            var isExpanded = $button.attr('aria-expanded') === 'true';
            
            // Toggle visibility
            $repliesList.toggleClass('vh360-replies-list--hidden');
            $button.attr('aria-expanded', !isExpanded);
            
            // Update button text
            var replyCount = $repliesList.find('> .vh360-comment-item').length;
            var buttonText = isExpanded ? 'View' : 'Hide';
            var replyText = replyCount === 1 ? ' reply' : ' replies';
            $button.html(
                '<svg class="vh360-toggle-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                buttonText + ' ' + replyCount + replyText
            );
            
            vh360Log('Toggled replies for comment:', commentId, 'expanded:', !isExpanded);
        },
        
        /**
         * Toggle kebab menu
         */
        toggleKebabMenu: function($button) {
            var $menu = $button.siblings('.vh360-actions-menu');
            var isExpanded = $button.attr('aria-expanded') === 'true';
            
            // Close all other menus first
            $('.vh360-actions-menu').not($menu).removeClass('vh360-actions-menu--open').addClass('vh360-actions-menu--hidden');
            $('.vh360-kebab-toggle').not($button).attr('aria-expanded', 'false');
            
            // Toggle this menu
            $menu.toggleClass('vh360-actions-menu--open vh360-actions-menu--hidden');
            $button.attr('aria-expanded', !isExpanded);
            
            vh360Log('Toggled kebab menu, expanded:', !isExpanded);
        },
        
        /**
         * Handle comment delete
         */
        handleDelete: function($button) {
            var commentId = $button.data('comment-id');
            
            if (!confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
                return;
            }
            
            vh360Log('Delete comment:', commentId);
            
            // For now, redirect to admin delete (secure method)
            // In a production implementation, you'd want AJAX with proper nonce verification
            if (typeof vh360CommentsData !== 'undefined' && vh360CommentsData.adminUrl) {
                window.location.href = vh360CommentsData.adminUrl + 'comment.php?action=deletecomment&c=' + commentId;
            } else {
                alert('Delete functionality requires admin access. Please use the WordPress admin panel.');
            }
        },
        
        /**
         * Handle comment form submit
         */
        handleFormSubmit: function($form) {
            var $submitBtn = $form.find('.vh360-comment-send-btn');
            var $textarea = $form.find('.vh360-comment-textarea');
            
            // Validate comment text
            if ($textarea.val().trim() === '') {
                $textarea.focus();
                return false; // Let browser validation handle it
            }
            
            // Show loading state
            $submitBtn.addClass('vh360-btn-loading').prop('disabled', true);
            
            vh360Log('Comment form submitted');
            
            // Form will submit normally, loading state will be cleared on page reload
            return true;
        },
        
        /**
         * Show error message
         */
        showError: function($element, message) {
            // Simple alert for now - could be enhanced with toast notifications
            alert(message);
        }
    };
    
    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        if ($('.vh360-comments-section').length) {
            WPCommentHandler.init();
        }
    });
    
})(jQuery);
