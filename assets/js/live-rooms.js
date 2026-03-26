/**
 * Live Rooms Form JavaScript
 *
 * Handles featured image preview for the Live Rooms form in the dashboard.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Live Rooms Form Handler
     */
    var VH360LiveRooms = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Handle upload button click to trigger file input
            $(document).on('click', '.vh360-live-room-form #vh360-upload-trigger', function(e) {
                e.preventDefault();
                $('.vh360-live-room-form #vh360_featured_image').trigger('click');
            });

            // Handle file input change for image preview
            $(document).on('change', '.vh360-live-room-form #vh360_featured_image', function(e) {
                self.handleImageSelect(e);
            });

            // Handle remove image button
            $(document).on('click', '.vh360-live-room-form #vh360-remove-image', function(e) {
                e.preventDefault();
                self.removeImage();
            });
        },

        /**
         * Handle image selection and show preview
         */
        handleImageSelect: function(e) {
            var file = e.target.files[0];
            
            if (!file) {
                return;
            }

            // Clear any existing error message
            this.clearError();

            // Validate file type
            var validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (validTypes.indexOf(file.type) === -1) {
                this.showError('Please select a valid image file (JPG, PNG, GIF, or WebP).');
                this.removeImage();
                return;
            }

            // Validate file size (5MB max)
            var maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxSize) {
                this.showError('Image file size must be less than 5MB.');
                this.removeImage();
                return;
            }

            // Show preview
            var reader = new FileReader();
            reader.onload = function(event) {
                $('#vh360-preview-img').attr('src', event.target.result);
                $('#vh360-image-preview').fadeIn(200);
            };
            reader.readAsDataURL(file);
        },

        /**
         * Show error message
         */
        showError: function(message) {
            // Remove any existing error
            this.clearError();
            
            // Create error message element
            var errorHtml = '<div class="vh360-form-error" role="alert">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<circle cx="12" cy="12" r="10"></circle>' +
                '<line x1="12" y1="8" x2="12" y2="12"></line>' +
                '<line x1="12" y1="16" x2="12.01" y2="16"></line>' +
                '</svg>' +
                '<span>' + message + '</span>' +
                '</div>';
            
            // Insert after file input
            $('.vh360-live-room-form #vh360_featured_image').after(errorHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('.vh360-form-error').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Clear error message
         */
        clearError: function() {
            $('.vh360-form-error').remove();
        },

        /**
         * Remove selected image and hide preview
         */
        removeImage: function() {
            $('.vh360-live-room-form #vh360_featured_image').val('');
            $('.vh360-live-room-form #vh360-preview-img').attr('src', '');
            $('.vh360-live-room-form #vh360-image-preview').fadeOut(200);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VH360LiveRooms.init();
    });

})(jQuery);
