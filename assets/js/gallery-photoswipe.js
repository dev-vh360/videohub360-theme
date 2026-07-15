/**
 * Gallery PhotoSwipe Integration
 *
 * Handles PhotoSwipe lightbox initialization for galleries.
 * Uses custom simple lightbox as primary solution for reliability.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

(function($) {
    'use strict';

    /**
     * VH360 Gallery Lightbox Handler
     * 
     * Uses a custom simple lightbox implementation for maximum reliability
     * instead of PhotoSwipe which has tap handling issues.
     */
    var VH360GalleryLightbox = {

        currentGallery: null,
        currentIndex: 0,
        items: [],
        scrollY: 0,
        scrollElement: null,
        $lightbox: null,
        isOpen: false,

        /**
         * Initialize lightbox for all galleries
         */
        init: function() {
            this.createLightboxElement();
            this.initGalleries();
            this.bindEvents();
        },

        /**
         * Create the lightbox element once
         */
        createLightboxElement: function() {
            // Only create if doesn't exist
            if ($('#vh360-gallery-lightbox').length) {
                this.$lightbox = $('#vh360-gallery-lightbox');
                return;
            }

            var html = '<div id="vh360-gallery-lightbox" class="vh360-gallery-lightbox" role="dialog" aria-modal="true" aria-hidden="true">' +
                '<div class="vh360-gallery-lightbox-backdrop"></div>' +
                '<div class="vh360-gallery-lightbox-wrapper">' +
                '<button type="button" class="vh360-gallery-lightbox-close" aria-label="Close lightbox">' +
                '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<line x1="18" y1="6" x2="6" y2="18"></line>' +
                '<line x1="6" y1="6" x2="18" y2="18"></line>' +
                '</svg>' +
                '</button>' +
                '<button type="button" class="vh360-gallery-lightbox-nav vh360-gallery-lightbox-prev" aria-label="Previous image">' +
                '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<polyline points="15 18 9 12 15 6"></polyline>' +
                '</svg>' +
                '</button>' +
                '<div class="vh360-gallery-lightbox-content">' +
                '<img class="vh360-gallery-lightbox-image" src="" alt="" />' +
                '<div class="vh360-gallery-lightbox-loading">' +
                '<div class="vh360-gallery-lightbox-spinner"></div>' +
                '</div>' +
                '</div>' +
                '<button type="button" class="vh360-gallery-lightbox-nav vh360-gallery-lightbox-next" aria-label="Next image">' +
                '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<polyline points="9 18 15 12 9 6"></polyline>' +
                '</svg>' +
                '</button>' +
                '<div class="vh360-gallery-lightbox-footer">' +
                '<div class="vh360-gallery-lightbox-counter"></div>' +
                '<div class="vh360-gallery-lightbox-caption"></div>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('body').append(html);
            this.$lightbox = $('#vh360-gallery-lightbox');
        },

        /**
         * Bind global events
         */
        bindEvents: function() {
            var self = this;
            
            // Re-initialize when new content is loaded (AJAX)
            $(document).on('vh360:gallery:loaded', function() {
                self.initGalleries();
            });

            // Close button click
            this.$lightbox.on('click', '.vh360-gallery-lightbox-close', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.close();
            });

            // Backdrop click to close
            this.$lightbox.on('click', '.vh360-gallery-lightbox-backdrop', function(e) {
                e.preventDefault();
                self.close();
            });

            // Previous button
            this.$lightbox.on('click', '.vh360-gallery-lightbox-prev', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.showPrev();
            });

            // Next button
            this.$lightbox.on('click', '.vh360-gallery-lightbox-next', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.showNext();
            });

            // Keyboard navigation
            $(document).on('keydown.vh360lightbox', function(e) {
                if (!self.isOpen) return;
                
                switch(e.key) {
                    case 'Escape':
                        self.close();
                        break;
                    case 'ArrowLeft':
                        self.showPrev();
                        break;
                    case 'ArrowRight':
                        self.showNext();
                        break;
                }
            });

            // Prevent clicks on content from closing
            this.$lightbox.on('click', '.vh360-gallery-lightbox-content', function(e) {
                e.stopPropagation();
            });

            // Swipe support for touch devices
            var touchStartX = 0;
            var touchEndX = 0;
            
            this.$lightbox.on('touchstart', function(e) {
                touchStartX = e.originalEvent.changedTouches[0].screenX;
            });
            
            this.$lightbox.on('touchend', function(e) {
                touchEndX = e.originalEvent.changedTouches[0].screenX;
                var diff = touchStartX - touchEndX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        self.showNext();
                    } else {
                        self.showPrev();
                    }
                }
            });
        },

        /**
         * Initialize galleries with lightbox
         */
        initGalleries: function() {
            var self = this;

            // Find all gallery containers with lightbox enabled
            $('.vh360-gallery-lightbox-enabled, .vh360-gallery-container[data-pswp-uid]').each(function() {
                var $gallery = $(this);
                
                if ($gallery.data('vh360-lightbox-initialized')) {
                    return;
                }

                // Bind click events to gallery items
                $gallery.find('.vh360-gallery-item-link').on('click.vh360lightbox', function(e) {
                    e.preventDefault();
                    
                    var $link = $(this);
                    var $item = $link.closest('.vh360-gallery-item');
                    var index = $gallery.find('.vh360-gallery-item').index($item);

                    self.open($gallery, index);
                });

                $gallery.data('vh360-lightbox-initialized', true);
            });
        },

        /**
         * Get items from gallery
         */
        getItems: function($gallery) {
            var items = [];

            $gallery.find('.vh360-gallery-item').each(function() {
                var $item = $(this);
                var $link = $item.find('.vh360-gallery-item-link');
                var size = $link.data('size');
                var dimensions = size ? size.split('x') : [1200, 900];

                items.push({
                    src: $link.attr('href'),
                    w: parseInt(dimensions[0], 10),
                    h: parseInt(dimensions[1], 10),
                    caption: $link.data('caption') || '',
                    thumb: $item.find('img').attr('src')
                });
            });

            return items;
        },

        /**
         * Open lightbox
         */
        open: function($gallery, index) {
            var self = this;

            // Store scroll position
            this.scrollElement = window.VH360ScrollContext ? window.VH360ScrollContext.getElement() : window;
            this.scrollY = window.VH360ScrollContext ? window.VH360ScrollContext.getScrollTop() : (window.pageYOffset || document.documentElement.scrollTop);
            if (window.VH360ScrollContext && window.VH360ScrollContext.lock) {
                window.VH360ScrollContext.lock('gallery-lightbox');
            }

            // Get items
            this.items = this.getItems($gallery);
            this.currentGallery = $gallery;
            this.currentIndex = index || 0;

            if (this.items.length === 0) {
                return;
            }

            // Show lightbox
            this.isOpen = true;
            this.$lightbox
                .attr('aria-hidden', 'false')
                .addClass('vh360-gallery-lightbox-open');
            
            $('body').addClass('vh360-lightbox-open');

            // Show current image
            this.showImage(this.currentIndex);

            // Focus the lightbox for keyboard navigation
            this.$lightbox.find('.vh360-gallery-lightbox-close').focus();
        },

        /**
         * Close lightbox
         */
        close: function() {
            if (!this.isOpen) return;

            this.isOpen = false;
            
            // Hide lightbox
            this.$lightbox
                .attr('aria-hidden', 'true')
                .removeClass('vh360-gallery-lightbox-open');
            
            $('body').removeClass('vh360-lightbox-open');

            // Clear image src to stop any loading
            this.$lightbox.find('.vh360-gallery-lightbox-image').attr('src', '');

            // Restore scroll position
            if (window.VH360ScrollContext && window.VH360ScrollContext.unlock) {
                window.VH360ScrollContext.unlock('gallery-lightbox');
            }
            if (this.scrollElement && this.scrollElement !== window) {
                this.scrollElement.scrollTop = this.scrollY;
            } else {
                window.scrollTo(0, this.scrollY);
            }

            // Reset state
            this.currentGallery = null;
            this.currentIndex = 0;
            this.items = [];
        },

        /**
         * Show image at index
         */
        showImage: function(index) {
            var self = this;

            if (index < 0 || index >= this.items.length) {
                return;
            }

            this.currentIndex = index;
            var item = this.items[index];

            // Show loading
            this.$lightbox.find('.vh360-gallery-lightbox-loading').show();
            this.$lightbox.find('.vh360-gallery-lightbox-image').css('opacity', '0');

            // Update counter
            this.$lightbox.find('.vh360-gallery-lightbox-counter').text(
                (index + 1) + ' / ' + this.items.length
            );

            // Update caption
            var $caption = this.$lightbox.find('.vh360-gallery-lightbox-caption');
            if (item.caption) {
                $caption.text(item.caption).show();
            } else {
                $caption.hide();
            }

            // Update navigation buttons visibility
            this.$lightbox.find('.vh360-gallery-lightbox-prev').toggle(index > 0);
            this.$lightbox.find('.vh360-gallery-lightbox-next').toggle(index < this.items.length - 1);

            // Load image
            var img = new Image();
            img.onload = function() {
                self.$lightbox.find('.vh360-gallery-lightbox-image')
                    .attr('src', item.src)
                    .attr('alt', item.caption || '')
                    .css('opacity', '1');
                self.$lightbox.find('.vh360-gallery-lightbox-loading').hide();
            };
            img.onerror = function() {
                self.$lightbox.find('.vh360-gallery-lightbox-loading').hide();
            };
            img.src = item.src;
        },

        /**
         * Show previous image
         */
        showPrev: function() {
            if (this.currentIndex > 0) {
                this.showImage(this.currentIndex - 1);
            }
        },

        /**
         * Show next image
         */
        showNext: function() {
            if (this.currentIndex < this.items.length - 1) {
                this.showImage(this.currentIndex + 1);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VH360GalleryLightbox.init();
    });

    // Export for external access
    window.VH360GalleryLightbox = VH360GalleryLightbox;
    // Alias for backward compatibility
    window.VH360PhotoSwipe = VH360GalleryLightbox;

})(jQuery);
