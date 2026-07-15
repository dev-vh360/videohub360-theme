/**
 * Bulletin System JavaScript
 *
 * Handles AJAX interactions for bulletins, including marking as read,
 * dismissing, and updating badge counts.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Toast notification helper
    function showToast(message, type = 'success') {
        // Remove existing toasts
        $('.vh360-toast').remove();
        
        const toastClass = type === 'error' ? 'vh360-toast-error' : 'vh360-toast-success';
        const icon = type === 'error' ? '⚠️' : '✓';
        
        const toast = $('<div class="vh360-toast ' + toastClass + '">' +
            '<span class="vh360-toast-icon">' + icon + '</span>' +
            '<span class="vh360-toast-message">' + message + '</span>' +
            '</div>');
        
        $('body').append(toast);
        
        // Trigger animation
        setTimeout(function() {
            toast.addClass('show');
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(function() {
            toast.removeClass('show');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 3000);
    }

    // Update unread count badge
    function updateBadgeCount(count) {
        const badge = $('.vh360-bulletin-badge');
        
        if (count > 0) {
            badge.text(count).show();
        } else {
            badge.hide();
        }
    }

    // Mark bulletin as read
    function markBulletinRead(bulletinId, callback) {
        $.ajax({
            url: vh360Bulletins.ajaxurl,
            type: 'POST',
            data: {
                action: 'vh360_mark_bulletin_read',
                nonce: vh360Bulletins.nonce,
                bulletin_id: bulletinId
            },
            success: function(response) {
                if (response.success) {
                    updateBadgeCount(response.data.unread_count);
                    if (callback) callback(response);
                } else {
                    showToast(response.data.message || vh360Bulletins.strings.error, 'error');
                }
            },
            error: function() {
                showToast(vh360Bulletins.strings.error, 'error');
            }
        });
    }

    // Dismiss bulletin
    function dismissBulletin(bulletinId, $element, callback) {
        $.ajax({
            url: vh360Bulletins.ajaxurl,
            type: 'POST',
            data: {
                action: 'vh360_dismiss_bulletin',
                nonce: vh360Bulletins.nonce,
                bulletin_id: bulletinId
            },
            beforeSend: function() {
                $element.addClass('vh360-bulletin-dismissing');
            },
            success: function(response) {
                if (response.success) {
                    // Fade out and remove the element
                    $element.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if there are no more bulletins
                        if ($('.vh360-bulletin-card').length === 0) {
                            const emptyState = '<div class="vh360-bulletins-empty">' +
                                '<div class="vh360-bulletins-empty-icon">📢</div>' +
                                '<p class="vh360-bulletins-empty-title">' + 
                                (typeof vh360Bulletins !== 'undefined' && vh360Bulletins.strings.no_bulletins 
                                    ? vh360Bulletins.strings.no_bulletins 
                                    : 'No bulletins to display') +
                                '</p>' +
                                '</div>';
                            $('.vh360-bulletins-list, .vh360-dashboard-bulletins').html(emptyState);
                        }
                    });
                    
                    updateBadgeCount(response.data.unread_count);
                    showToast(vh360Bulletins.strings.dismissed);
                    
                    if (callback) callback(response);
                } else {
                    $element.removeClass('vh360-bulletin-dismissing');
                    showToast(response.data.message || vh360Bulletins.strings.error, 'error');
                }
            },
            error: function() {
                $element.removeClass('vh360-bulletin-dismissing');
                showToast(vh360Bulletins.strings.error, 'error');
            }
        });
    }

    // Mark all bulletins as read
    function markAllBulletinsRead(callback) {
        $.ajax({
            url: vh360Bulletins.ajaxurl,
            type: 'POST',
            data: {
                action: 'vh360_mark_all_bulletins_read',
                nonce: vh360Bulletins.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update UI - remove unread indicators
                    $('.vh360-bulletin-card').removeClass('vh360-bulletin-unread');
                    $('.vh360-bulletin-unread-indicator').remove();
                    
                    updateBadgeCount(0);
                    showToast(response.data.message);
                    
                    if (callback) callback(response);
                } else {
                    showToast(response.data.message || vh360Bulletins.strings.error, 'error');
                }
            },
            error: function() {
                showToast(vh360Bulletins.strings.error, 'error');
            }
        });
    }

    // Get bulletin count (for polling)
    function getBulletinCount(callback) {
        $.ajax({
            url: vh360Bulletins.ajaxurl,
            type: 'POST',
            data: {
                action: 'vh360_get_bulletin_count',
                nonce: vh360Bulletins.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateBadgeCount(response.data.count);
                    if (callback) callback(response.data.count);
                }
            }
        });
    }

    // Document ready
    $(document).ready(function() {
        
        // Dismiss bulletin button click
        $(document).on('click', '.vh360-bulletin-dismiss', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $card = $button.closest('.vh360-bulletin-card, .vh360-bulletin-banner');
            const bulletinId = $card.data('bulletin-id');
            
            if (bulletinId) {
                dismissBulletin(bulletinId, $card);
            }
        });

        // Mark as read button click
        $(document).on('click', '.vh360-bulletin-mark-read', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $card = $button.closest('.vh360-bulletin-card');
            const bulletinId = $card.data('bulletin-id');
            
            if (bulletinId) {
                markBulletinRead(bulletinId, function() {
                    $card.removeClass('vh360-bulletin-unread');
                    $card.find('.vh360-bulletin-unread-indicator').fadeOut(200, function() {
                        $(this).remove();
                    });
                    $button.fadeOut(200);
                });
            }
        });

        // Mark all as read button click
        $(document).on('click', '.vh360-bulletin-mark-all-read', function(e) {
            e.preventDefault();
            
            markAllBulletinsRead(function() {
                $(this).fadeOut(200);
            });
        });

        // Auto-mark as read when viewing single bulletin
        if ($('body').hasClass('single-vh360_bulletin')) {
            const bulletinId = $('.vh360-single-bulletin').data('bulletin-id');
            if (bulletinId) {
                // Mark as read after 2 seconds of viewing
                setTimeout(function() {
                    markBulletinRead(bulletinId);
                }, 2000);
            }
        }

        // Filter buttons on archive page
        $(document).on('click', '.vh360-bulletin-filter', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const filter = $button.data('filter');
            
            // Update active state
            $('.vh360-bulletin-filter').removeClass('active');
            $button.addClass('active');
            
            // Filter bulletins
            if (filter === 'all') {
                $('.vh360-bulletin-card').fadeIn(200);
            } else {
                $('.vh360-bulletin-card').hide();
                $('.vh360-bulletin-card[data-priority="' + filter + '"]').fadeIn(200);
            }
        });

        // Search bulletins
        const $searchInput = $('#vh360-bulletin-search');
        if ($searchInput.length) {
            $searchInput.on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.vh360-bulletin-card').each(function() {
                    const $card = $(this);
                    const title = $card.find('.vh360-bulletin-title').text().toLowerCase();
                    const excerpt = $card.find('.vh360-bulletin-excerpt').text().toLowerCase();
                    
                    if (title.indexOf(searchTerm) !== -1 || excerpt.indexOf(searchTerm) !== -1) {
                        $card.fadeIn(200);
                    } else {
                        $card.fadeOut(200);
                    }
                });
            });
        }

        // Smooth scroll to bulletin
        $(document).on('click', 'a[href^="#bulletin-"]', function(e) {
            e.preventDefault();
            
            const target = $(this).attr('href');
            const $target = $(target);
            
            if ($target.length) {
                window.VH360ScrollContext && window.VH360ScrollContext.scrollTo ? window.VH360ScrollContext.scrollTo($target.offset().top - 100, { behavior: 'smooth' }) : $('html, body').animate({
                    scrollTop: $target.offset().top - 100
                }, 500);
            }
        });

        // Optional: Poll for new bulletins every 5 minutes (only if user is logged in and page is visible)
        if ($('body').hasClass('logged-in') && typeof vh360Bulletins !== 'undefined') {
            function pollBulletinCount() {
                // Only poll if page is visible
                if (!document.hidden) {
                    getBulletinCount();
                }
                setTimeout(pollBulletinCount, 300000); // 5 minutes
            }
            
            // Start polling
            setTimeout(pollBulletinCount, 300000);
            
            // Pause/resume polling when visibility changes
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // Refresh count when page becomes visible
                    getBulletinCount();
                }
            });
        }
        
        // Copy link button
        $(document).on('click', '.vh360-share-copy', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const url = $button.data('url');
            const originalText = $button.html();
            
            if (navigator.clipboard && url) {
                navigator.clipboard.writeText(url).then(function() {
                    $button.html('✓ ' + (vh360Bulletins.strings.copied || 'Copied!'));
                    setTimeout(function() {
                        $button.html(originalText);
                    }, 2000);
                }).catch(function() {
                    // Fallback for older browsers
                    const tempInput = $('<input>');
                    $('body').append(tempInput);
                    tempInput.val(url).select();
                    document.execCommand('copy');
                    tempInput.remove();
                    $button.html('✓ ' + (vh360Bulletins.strings.copied || 'Copied!'));
                    setTimeout(function() {
                        $button.html(originalText);
                    }, 2000);
                });
            }
        });

        // Close banner with X button
        $(document).on('click', '.vh360-bulletin-banner-close', function(e) {
            e.preventDefault();
            
            const $banner = $(this).closest('.vh360-bulletin-banner');
            const bulletinId = $banner.data('bulletin-id');
            
            if (bulletinId) {
                dismissBulletin(bulletinId, $banner);
            }
        });
    });

})(jQuery);
