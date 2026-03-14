/**
 * Blog Archive Frontend JavaScript
 *
 * Handles filtering, search, sorting, and pagination for blog archive.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

(function($) {
    'use strict';

    /**
     * Blog Archive Handler
     */
    const VH360Blog = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.updateResultsCount();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Search with debounce
            let searchTimeout;
            $('#vh360-blog-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    VH360Blog.handleSearch();
                }, 500);
            });

            // Category filter
            $('#vh360-blog-category').on('change', this.handleCategoryChange);
            
            // Tag filter
            $('#vh360-blog-tag').on('change', this.handleTagChange);
            
            // Sort
            $('#vh360-blog-sort').on('change', this.handleSortChange);

            // Pagination (delegated event for AJAX-loaded content)
            $(document).on('click', '.vh360-blog-pagination a', this.handlePaginationClick);
        },

        /**
         * Handle search input
         */
        handleSearch: function() {
            VH360Blog.loadPosts(1);
        },

        /**
         * Handle category change
         */
        handleCategoryChange: function() {
            VH360Blog.loadPosts(1);
        },

        /**
         * Handle tag change
         */
        handleTagChange: function() {
            VH360Blog.loadPosts(1);
        },

        /**
         * Handle sort change
         */
        handleSortChange: function() {
            VH360Blog.loadPosts(1);
        },

        /**
         * Handle pagination click
         */
        handlePaginationClick: function(e) {
            e.preventDefault();
            
            const $link = $(this);
            const url = new URL($link.attr('href'));
            const page = parseInt(url.searchParams.get('paged')) || 1;
            
            // Scroll to top of results
            $('html, body').animate({
                scrollTop: $('#vh360-blog-results-container').offset().top - 100
            }, 300);
            
            VH360Blog.loadPosts(page);
        },

        /**
         * Load posts via AJAX
         */
        loadPosts: function(page) {
            const $container = $('#vh360-blog-results-container');
            const search = $('#vh360-blog-search').val();
            const category = $('#vh360-blog-category').val();
            const tag = $('#vh360-blog-tag').val();
            const sort = $('#vh360-blog-sort').val();
            
            // Show loading state
            $container.html('<div class="vh360-blog-loading"><div class="vh360-blog-spinner"></div></div>');
            
            $.ajax({
                url: vh360Blog.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'vh360_load_blog_posts',
                    nonce: vh360Blog.nonce,
                    page: page || 1,
                    search: search,
                    category: category,
                    tag: tag,
                    sort: sort
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                        
                        // Update results count
                        VH360Blog.updateResultsCount(response.data.total);
                        
                        // Update URL without reload (optional)
                        if (window.history && window.history.pushState) {
                            const params = new URLSearchParams();
                            if (page > 1) params.set('paged', page);
                            if (search) params.set('s', search);
                            if (category) params.set('category', category);
                            if (tag) params.set('tag', tag);
                            if (sort && sort !== 'date_desc') params.set('sort', sort);
                            
                            const newUrl = params.toString() 
                                ? window.location.pathname + '?' + params.toString()
                                : window.location.pathname;
                            
                            window.history.pushState({}, '', newUrl);
                        }
                    } else {
                        $container.html('<div class="vh360-blog-error">' + 
                            (response.data.message || vh360Blog.i18n.error) + 
                            '</div>');
                    }
                },
                error: function() {
                    $container.html('<div class="vh360-blog-error">' + 
                        vh360Blog.i18n.error + 
                        '</div>');
                }
            });
        },

        /**
         * Update results count
         */
        updateResultsCount: function(total) {
            const $count = $('#vh360-blog-results-count');
            
            if (total !== undefined) {
                if (total === 0) {
                    $count.text('');
                } else if (total === 1) {
                    $count.text(vh360Blog.i18n.oneResult);
                } else {
                    $count.text(vh360Blog.i18n.resultsCount.replace('%d', total));
                }
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        if ($('.vh360-blog-archive').length) {
            VH360Blog.init();
        }
    });

})(jQuery);
