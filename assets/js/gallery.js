/**
 * Gallery JavaScript
 *
 * Handles gallery functionality including lazy loading,
 * masonry grid initialization, and image upload preview.
 *
 * NOTE: Lightbox functionality is handled by gallery-photoswipe.js
 * to avoid conflicts and ensure single-click close behavior.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Gallery object
     */
    var VH360Gallery = {

        /**
         * Initialize gallery functionality
         */
        init: function() {
            // NOTE: Lightbox is handled by gallery-photoswipe.js
            // Do NOT add lightbox functionality here to avoid conflicts
            this.lazyLoading();
            this.masonryGrid();
            this.imageUpload();
            this.filterGallery();
            this.viewToggle();
            this.deleteImage();
        },

        /**
         * Lazy loading support
         */
        lazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var image = entry.target;
                            var src = image.getAttribute('data-src');
                            
                            if (src) {
                                image.src = src;
                                image.removeAttribute('data-src');
                                image.classList.add('loaded');
                            }
                            
                            observer.unobserve(image);
                        }
                    });
                });

                $('.vh360-gallery-image[data-src]').each(function() {
                    imageObserver.observe(this);
                });
            } else {
                // Fallback for browsers without IntersectionObserver
                $('.vh360-gallery-image[data-src]').each(function() {
                    var $img = $(this);
                    $img.attr('src', $img.data('src')).removeAttr('data-src');
                });
            }
        },

        /**
         * Masonry grid initialization
         */
        masonryGrid: function() {
            var $grid = $('.vh360-gallery-grid.masonry');
            
            if (!$grid.length) {
                return;
            }

            // Simple masonry implementation
            function layoutMasonry() {
                $grid.find('.vh360-gallery-item').each(function() {
                    $(this).css('break-inside', 'avoid');
                });
            }

            // Layout on load
            layoutMasonry();

            // Re-layout on image load
            $grid.find('img').on('load', function() {
                layoutMasonry();
            });

            // Re-layout on window resize
            var resizeTimer;
            $(window).on('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(layoutMasonry, 250);
            });
        },

        /**
         * Image upload preview
         */
        imageUpload: function() {
            var $uploadInput = $('.vh360-gallery-upload-input');
            var $uploadBtn = $('.vh360-gallery-upload-btn');

            if (!$uploadInput.length) {
                return;
            }

            // Open file dialog
            $uploadBtn.on('click', function(e) {
                e.preventDefault();
                $uploadInput.trigger('click');
            });

            // Handle file selection
            $uploadInput.on('change', function() {
                var files = this.files;
                
                if (!files.length) {
                    return;
                }

                // Preview and upload files
                for (var i = 0; i < files.length; i++) {
                    VH360Gallery.uploadImage(files[i]);
                }

                // Reset input
                $(this).val('');
            });

            // Drag and drop support
            var $uploadArea = $('.vh360-gallery-upload');
            
            $uploadArea.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            $uploadArea.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });

            $uploadArea.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');

                var files = e.originalEvent.dataTransfer.files;
                
                for (var i = 0; i < files.length; i++) {
                    if (files[i].type.match('image.*')) {
                        VH360Gallery.uploadImage(files[i]);
                    }
                }
            });
        },

        /**
         * Upload image via AJAX
         */
        uploadImage: function(file) {
            // Create preview
            var reader = new FileReader();
            var $container = $('.vh360-gallery-grid');
            var previewId = 'preview-' + Date.now();

            reader.onload = function(e) {
                var previewHtml = '<div class="vh360-gallery-item vh360-gallery-item-uploading" id="' + previewId + '">' +
                    '<div class="vh360-gallery-image-wrapper">' +
                    '<img class="vh360-gallery-image" src="' + e.target.result + '" alt="Uploading...">' +
                    '<div class="vh360-gallery-overlay">' +
                    '<div class="vh360-gallery-spinner"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                $container.prepend(previewHtml);
            };
            reader.readAsDataURL(file);

            // Upload via AJAX
            var formData = new FormData();
            formData.append('action', 'vh360_upload_gallery_image');
            formData.append('nonce', vh360Gallery.nonce);
            formData.append('image', file);

            $.ajax({
                url: vh360Gallery.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Replace preview with actual image
                        $('#' + previewId).removeClass('vh360-gallery-item-uploading');
                        $('#' + previewId).find('img').attr('src', response.data.url);
                        $('#' + previewId).find('.vh360-gallery-overlay').remove();
                    } else {
                        // Show error
                        alert(response.data.message || 'Failed to upload image');
                        $('#' + previewId).remove();
                    }
                },
                error: function() {
                    alert('An error occurred while uploading the image.');
                    $('#' + previewId).remove();
                }
            });
        },

        /**
         * Filter gallery
         */
        filterGallery: function() {
            $('.vh360-gallery-filter-btn').on('click', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var filter = $btn.data('filter');

                // Update active state
                $('.vh360-gallery-filter-btn').removeClass('active');
                $btn.addClass('active');

                // Filter items
                if (filter === 'all') {
                    $('.vh360-gallery-item').show();
                } else {
                    $('.vh360-gallery-item').hide();
                    $('.vh360-gallery-item[data-category="' + filter + '"]').show();
                }
            });
        },

        /**
         * View toggle (grid vs masonry)
         */
        viewToggle: function() {
            $('.vh360-gallery-view-btn').on('click', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var view = $btn.data('view');
                var $grid = $('.vh360-gallery-grid');

                // Update active state
                $('.vh360-gallery-view-btn').removeClass('active');
                $btn.addClass('active');

                // Toggle view
                if (view === 'masonry') {
                    $grid.addClass('masonry');
                    VH360Gallery.masonryGrid();
                } else {
                    $grid.removeClass('masonry');
                }
            });
        },

        /**
         * Delete image
         */
        deleteImage: function() {
            $(document).on('click', '.vh360-gallery-action-btn.delete', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (!confirm('Are you sure you want to delete this image?')) {
                    return;
                }

                var $btn = $(this);
                var $item = $btn.closest('.vh360-gallery-item');
                var attachmentId = $item.data('attachment-id');

                if (!attachmentId) {
                    return;
                }

                $btn.prop('disabled', true);

                $.ajax({
                    url: vh360Gallery.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vh360_delete_gallery_image',
                        nonce: vh360Gallery.nonce,
                        attachment_id: attachmentId
                    },
                    success: function(response) {
                        if (response.success) {
                            $item.fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            alert(response.data.message || 'Failed to delete image');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the image.');
                        $btn.prop('disabled', false);
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VH360Gallery.init();
    });

})(jQuery);
