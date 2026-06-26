/**
 * VH360 Theme Admin JavaScript
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Debug logging helper - only log when __VH360_DEBUG is enabled
    const vh360Log = (...args) => { if (window.__VH360_DEBUG) console.log(...args); };
    
    /**
     * Initialize admin functionality
     */
    $(document).ready(function() {
        
        // Confirmation dialogs for destructive actions
        $('.vh360-confirm-action').on('click', function(e) {
            var confirmMessage = $(this).data('confirm');
            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Copy shortcode to clipboard
        $('.vh360-copy-shortcode').on('click', function(e) {
            e.preventDefault();
            var shortcode = $(this).data('shortcode');
            
            // Create temporary input element
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            
            try {
                document.execCommand('copy');
                
                // Show success feedback
                var $button = $(this);
                var originalText = $button.text();
                $button.text('Copied!').prop('disabled', true);
                
                setTimeout(function() {
                    $button.text(originalText).prop('disabled', false);
                }, 2000);
            } catch (err) {
                alert('Failed to copy shortcode. Please copy manually: ' + shortcode);
            }
            
            $temp.remove();
        });
        
        // AJAX clear cache
        $('.vh360-clear-cache-ajax').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(vh360Admin.confirmClearCache)) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: vh360Admin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_clear_cache',
                    nonce: vh360Admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.text('Cleared!');
                        setTimeout(function() {
                            $button.text('Clear Cache').prop('disabled', false);
                        }, 2000);
                    } else {
                        alert('Error clearing cache: ' + response.data);
                        $button.text('Clear Cache').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error clearing cache. Please try again.');
                    $button.text('Clear Cache').prop('disabled', false);
                }
            });
        });
        
        // Form validation
        $('form').on('submit', function(e) {
            var $form = $(this);
            
            // Validate number inputs
            $form.find('input[type="number"]').each(function() {
                var $input = $(this);
                var min = parseFloat($input.attr('min'));
                var max = parseFloat($input.attr('max'));
                var val = parseFloat($input.val());
                
                if (!isNaN(min) && val < min) {
                    alert('Value must be at least ' + min);
                    $input.focus();
                    e.preventDefault();
                    return false;
                }
                
                if (!isNaN(max) && val > max) {
                    alert('Value must be at most ' + max);
                    $input.focus();
                    e.preventDefault();
                    return false;
                }
            });
        });
        
        // Toggle visibility of conditional settings
        $('[data-toggle-target]').on('change', function() {
            var target = $(this).data('toggle-target');
            var $target = $(target);
            
            if ($(this).is(':checkbox')) {
                if ($(this).is(':checked')) {
                    $target.slideDown();
                } else {
                    $target.slideUp();
                }
            }
        });
        
        // Initialize conditional settings visibility
        $('[data-toggle-target]').trigger('change');
        
        // Auto-dismiss notices after 5 seconds
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make stats cards clickable
        $('.vh360-stats-card').on('click', function(e) {
            if ($(e.target).is('a') || $(e.target).closest('a').length) {
                return;
            }
            
            var $link = $(this).find('a.vh360-stats-link');
            if ($link.length) {
                window.location.href = $link.attr('href');
            }
        });
        
        // Add loading state to submit buttons
        $('form').on('submit', function() {
            var $submitButton = $(this).find('input[type="submit"], button[type="submit"]');
            if (!$submitButton.hasClass('vh360-confirm-action')) {
                $submitButton.prop('disabled', true);
                
                var originalText = $submitButton.val() || $submitButton.text();
                if ($submitButton.is('input')) {
                    $submitButton.val('Saving...');
                } else {
                    $submitButton.text('Saving...');
                }
            }
        });
        
        // Smooth scroll to notices
        if ($('.notice').length) {
            $('html, body').animate({
                scrollTop: $('.notice').first().offset().top - 50
            }, 500);
        }
        
    });
    
    /**
     * AJAX handler for clearing cache
     */
    function clearCacheAjax() {
        $.ajax({
            url: vh360Admin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vh360_clear_cache',
                nonce: vh360Admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    vh360Log('Cache cleared successfully');
                }
            }
        });
    }
    
})(jQuery);
