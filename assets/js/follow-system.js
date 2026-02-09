/**
 * Follow System JavaScript
 *
 * Handles follow/unfollow button clicks for user profiles.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle follow/unfollow button clicks
        $(document).on('click', '.vh360-follow-btn, .vh360-unfollow-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var targetUserId = $btn.data('target');
            var nonce = $btn.data('nonce');
            
            // Disable button during request
            $btn.prop('disabled', true);
            
            $.ajax({
                url: vh360Follow.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_toggle_follow',
                    target_user_id: targetUserId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Toggle button class and text
                        if (response.data.following) {
                            $btn.removeClass('vh360-follow-btn').addClass('vh360-unfollow-btn vh360-follow-btn--following');
                            $btn.text(vh360Follow.unfollowText || 'Following');
                        } else {
                            $btn.removeClass('vh360-unfollow-btn vh360-follow-btn--following').addClass('vh360-follow-btn');
                            $btn.text(vh360Follow.followText || 'Follow');
                        }
                    } else {
                        // Show error message with better UX
                        var message = response.data || vh360Follow.errorText;
                        console.error('Follow error:', message);
                        // Temporarily show error state on button
                        var originalText = $btn.text();
                        $btn.text('Error').css('opacity', '0.7');
                        setTimeout(function() {
                            $btn.text(originalText).css('opacity', '1');
                        }, 2000);
                    }
                },
                error: function() {
                    console.error('Follow AJAX error');
                    // Temporarily show error state on button
                    var originalText = $btn.text();
                    $btn.text('Error').css('opacity', '0.7');
                    setTimeout(function() {
                        $btn.text(originalText).css('opacity', '1');
                    }, 2000);
                },
                complete: function() {
                    // Re-enable button
                    $btn.prop('disabled', false);
                }
            });
        });
    });
    
})(jQuery);
