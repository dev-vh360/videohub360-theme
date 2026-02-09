/**
 * Events Frontend JavaScript
 *
 * Handles filtering, search, and pagination for events archive.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

(function($) {
    'use strict';

    /**
     * Events handler
     */
    const VH360Events = {
        
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
            // Filter tabs
            $('.vh360-event-filter').on('click', this.handleFilterClick);
            
            // Category filter
            $('#vh360-event-category').on('change', this.handleCategoryChange);
            
            // Search
            let searchTimeout;
            $('#vh360-event-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    VH360Events.handleSearch();
                }, 500);
            });

            // Pagination
            $(document).on('click', '.vh360-events-pagination a', this.handlePaginationClick);
        },

        /**
         * Handle filter click
         */
        handleFilterClick: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const filter = $btn.data('filter');
            
            // Update active state
            $('.vh360-event-filter').removeClass('active');
            $btn.addClass('active');
            
            // Load filtered events
            VH360Events.loadEvents(filter, 1);
        },

        /**
         * Handle category change
         */
        handleCategoryChange: function() {
            const filter = $('.vh360-event-filter.active').data('filter');
            VH360Events.loadEvents(filter, 1);
        },

        /**
         * Handle search
         */
        handleSearch: function() {
            const filter = $('.vh360-event-filter.active').data('filter');
            VH360Events.loadEvents(filter, 1);
        },

        /**
         * Handle pagination click
         */
        handlePaginationClick: function(e) {
            e.preventDefault();
            
            const $link = $(this);
            const page = $link.text();
            const filter = $('.vh360-event-filter.active').data('filter');
            
            // Scroll to top
            $('html, body').animate({
                scrollTop: $('#vh360-events-list-container').offset().top - 100
            }, 300);
            
            VH360Events.loadEvents(filter, parseInt(page));
        },

        /**
         * Load events via AJAX
         */
        loadEvents: function(filter, page) {
            const $container = $('#vh360-events-list-container');
            const search = $('#vh360-event-search').val();
            const category = $('#vh360-event-category').val();
            
            // Show loading state
            $container.html('<div class="vh360-events-loading"><div class="vh360-spinner"></div></div>');
            
            $.ajax({
                url: vh360Events.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_load_events',
                    nonce: vh360Events.nonce,
                    filter: filter || 'upcoming',
                    page: page || 1,
                    search: search,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                        
                        // Update pagination if needed
                        if (response.data.pagination) {
                            VH360Events.updatePagination(response.data.pagination);
                        }
                    } else {
                        $container.html('<div class="vh360-events-error">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $container.html('<div class="vh360-events-error">' + vh360Events.i18n.error + '</div>');
                }
            });
        },

        /**
         * Update pagination
         */
        updatePagination: function(pagination) {
            // Simple pagination update - can be enhanced later
            // For now, the server returns the full HTML with pagination included
        }
    };

    /**
     * RSVP handler
     */
    const VH360EventRSVP = {
        
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
            $('.vh360-event-rsvp-btn').on('click', this.handleRsvp);
        },

        /**
         * Handle RSVP button click
         */
        handleRsvp: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const eventId = $btn.data('event-id');
            
            if ($btn.prop('disabled')) {
                return;
            }
            
            // Disable button
            $btn.prop('disabled', true);
            
            $.ajax({
                url: vh360Events.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_event_rsvp',
                    nonce: vh360Events.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success) {
                        // Update button state
                        const isRsvpd = response.data.is_rsvpd;
                        const count = response.data.count;
                        
                        if (isRsvpd) {
                            $btn.addClass('vh360-event-rsvpd');
                            $btn.find('.vh360-rsvp-text').text(vh360Events.i18n.rsvpd || 'RSVP\'d');
                            $btn.find('svg').html('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>');
                        } else {
                            $btn.removeClass('vh360-event-rsvpd');
                            $btn.find('.vh360-rsvp-text').text(vh360Events.i18n.rsvp || 'RSVP');
                            $btn.find('svg').html('<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line>');
                        }
                        
                        $btn.find('.vh360-rsvp-count').text('(' + count + ')');
                        
                        // Show success message
                        VH360EventRSVP.showNotice('success', response.data.message);
                    } else {
                        VH360EventRSVP.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    VH360EventRSVP.showNotice('error', vh360Events.i18n.error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Show notice message
         */
        showNotice: function(type, message) {
            // Create notice element
            const $notice = $('<div class="vh360-event-notice vh360-event-notice-' + type + '">' + message + '</div>');
            
            // Append to body
            $('body').append($notice);
            
            // Animate in
            setTimeout(function() {
                $notice.addClass('vh360-event-notice-show');
            }, 10);
            
            // Auto-remove after 3 seconds
            setTimeout(function() {
                $notice.removeClass('vh360-event-notice-show');
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            }, 3000);
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        if (typeof vh360Events !== 'undefined') {
            VH360Events.init();
            VH360EventRSVP.init();
        }
    });

})(jQuery);
