/**
 * Members Directory JavaScript
 *
 * Handles search, filtering, sorting, and pagination for members directory.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

var VH360StorageCompat = window.VH360Storage || {
  getPreference: function(key, def){ try { var value = window['localStorage'].getItem(key); return value === null ? def : value; } catch (e) { return def; } },
  setPreference: function(key, value){ try { window['localStorage'].setItem(key, value); } catch (e) {} },
  removePreference: function(key){ try { window['localStorage'].removeItem(key); } catch (e) {} },
  registerPreferenceKey: function(){}
};
    
    // State management
    const state = {
        currentPage: 1,
        totalPages: 1,
        search: '',
        role: '',
        category: '',
        joinDate: '',
        sortBy: 'registered',
        sortOrder: 'DESC',
        view: 'grid',
        isLoading: false,
        directoryMode: null,
        pageId: 0,
        perPage: 12
    };
    
    // DOM elements
    const $searchInput = $('#vh360-member-search');
    const $searchClear = $('.vh360-search-clear');
    const $roleFilter = $('#vh360-role-filter');
    const $categoryFilter = $('#vh360-category-filter');
    const $dateFilter = $('#vh360-date-filter');
    const $sortSelect = $('#vh360-sort-select');
    const $viewBtns = $('.vh360-view-btn');
    const $membersGrid = $('#vh360-members-grid');
    const $loading = $('#vh360-members-loading');
    const $emptyState = $('#vh360-members-empty');
    const $pagination = $('#vh360-members-pagination');
    const $prevBtn = $('.vh360-pagination-prev');
    const $nextBtn = $('.vh360-pagination-next');
    const $currentPage = $('#vh360-current-page');
    const $totalPages = $('#vh360-total-pages');
    const $filtersToggle = $('.vh360-controls-toggle');
    const $filtersPanel = $('#vh360-members-filters-panel');
    
    
    /**
     * Mobile filters collapse helpers
     */
    function setFiltersCollapsed(collapsed) {
        if (!$filtersPanel.length || !$filtersToggle.length) return;

        $filtersPanel.toggleClass('is-collapsed', collapsed);
        $filtersToggle.attr('aria-expanded', collapsed ? 'false' : 'true');
    }

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
    }

    function initMobileFiltersCollapse() {
        if (!$filtersPanel.length || !$filtersToggle.length) return;

        // Default state based on viewport (collapsed on mobile, open on desktop)
        if (isMobile()) {
            setFiltersCollapsed(true);
        } else {
            setFiltersCollapsed(false);
        }

        // Toggle click
        $filtersToggle.on('click', function() {
            const collapsed = $filtersPanel.hasClass('is-collapsed');
            setFiltersCollapsed(!collapsed);
        });

        // If viewport changes, reset state appropriately
        $(window).on('resize', debounce(function() {
            if (isMobile()) {
                setFiltersCollapsed(true);
            } else {
                setFiltersCollapsed(false);
            }
        }, 150));
    }


/**
     * Initialize members directory
     */
    function init() {
        // Load directory mode configuration from localized data
        if (typeof vh360Members !== 'undefined') {
            state.directoryMode = vh360Members.directoryMode || null;
            state.pageId = vh360Members.pageId || 0;
            state.perPage = vh360Members.perPage || 12;
            
            // For professionals_only mode, force role to empty
            // (role filter won't be rendered in template and handler won't be attached)
            if (state.directoryMode && state.directoryMode.audience === 'professionals_only') {
                state.role = '';
            }
        }
        
        // Initialize totalPages from server-rendered value
        if ($totalPages.length && $totalPages.text()) {
            state.totalPages = parseInt($totalPages.text(), 10) || 1;
        }
        
        // Initialize currentPage from server-rendered value
        if ($currentPage.length && $currentPage.text()) {
            state.currentPage = parseInt($currentPage.text(), 10) || 1;
        }
        
        // Event listeners
        $searchInput.on('input', debounce(handleSearch, 500));
        $searchClear.on('click', clearSearch);
        
        // Only attach role filter if in all_members mode
        if (!state.directoryMode || state.directoryMode.audience === 'all_members') {
            $roleFilter.on('change', handleFilterChange);
        }
        
        // Attach category filter if element exists
        if ($categoryFilter.length) {
            $categoryFilter.on('change', handleFilterChange);
        }
        
        $dateFilter.on('change', handleFilterChange);
        $sortSelect.on('change', handleSortChange);
        $viewBtns.on('click', handleViewToggle);
        $prevBtn.on('click', handlePrevPage);
        $nextBtn.on('click', handleNextPage);
        
        // Load view preference from localStorage
        const savedView = VH360StorageCompat.getPreference('vh360_members_view');
        if (savedView) {
            state.view = savedView;
            updateViewState();
        }
        
        // Mobile collapse behavior
        initMobileFiltersCollapse();

        // Parse URL parameters on load
        parseUrlParams();
    }
    
    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Handle search input
     */
    function handleSearch() {
        const searchValue = $searchInput.val().trim();
        state.search = searchValue;
        state.currentPage = 1;
        
        // Show/hide clear button
        if (searchValue) {
            $searchClear.show();
        } else {
            $searchClear.hide();
        }
        
        loadMembers();
        updateUrl();
    }
    
    /**
     * Clear search
     */
    function clearSearch() {
        $searchInput.val('');
        state.search = '';
        state.currentPage = 1;
        $searchClear.hide();
        loadMembers();
        updateUrl();
    }
    
    /**
     * Handle filter change
     */
    function handleFilterChange() {
        // Only update role if in all_members mode
        if (!state.directoryMode || state.directoryMode.audience === 'all_members') {
            state.role = $roleFilter.val();
        } else {
            state.role = ''; // Force empty for professionals_only
        }
        
        // Update category if filter exists
        if ($categoryFilter.length) {
            state.category = $categoryFilter.val();
        }
        
        state.joinDate = $dateFilter.val();
        state.currentPage = 1;
        loadMembers();
        updateUrl();
    }
    
    /**
     * Handle sort change
     */
    function handleSortChange() {
        const sortValue = $sortSelect.val();
        const parts = sortValue.split('_');
        state.sortBy = parts[0];
        state.sortOrder = parts[1].toUpperCase();
        state.currentPage = 1;
        loadMembers();
        updateUrl();
    }
    
    /**
     * Handle view toggle
     */
    function handleViewToggle() {
        const view = $(this).data('view');
        state.view = view;
        
        // Save preference
        VH360StorageCompat.setPreference('vh360_members_view', view);
        
        updateViewState();
    }
    
    /**
     * Update view state
     */
    function updateViewState() {
        $viewBtns.removeClass('active');
        $(`.vh360-view-btn[data-view="${state.view}"]`).addClass('active');
        $membersGrid.removeClass('view-grid view-list').addClass(`view-${state.view}`);
    }
    
    /**
     * Handle previous page
     */
    function handlePrevPage() {
        if (state.currentPage > 1) {
            state.currentPage--;
            loadMembers();
            updateUrl();
            scrollToTop();
        }
    }
    
    /**
     * Handle next page
     */
    function handleNextPage() {
        if (state.currentPage < state.totalPages) {
            state.currentPage++;
            loadMembers();
            updateUrl();
            scrollToTop();
        }
    }
    
    /**
     * Load members via AJAX
     */
    function loadMembers() {
        if (state.isLoading) {
            return;
        }
        
        state.isLoading = true;
        $loading.show();
        $membersGrid.addClass('loading');
        $emptyState.hide();
        
        const data = {
            action: 'vh360_search_members',
            nonce: vh360Members.nonce,
            directory_page_id: state.pageId,
            search: state.search,
            category: state.category,
            join_date: state.joinDate,
            orderby: state.sortBy,
            order: state.sortOrder,
            page: state.currentPage
        };
        
        // Only include role for all_members mode
        if (!state.directoryMode || state.directoryMode.audience === 'all_members') {
            data.role = state.role;
        }
        
        $.ajax({
            url: vh360Members.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $membersGrid.html(response.data.html);
                    state.totalPages = response.data.max_pages;
                    updatePagination();
                    $emptyState.hide();
                } else {
                    $membersGrid.empty();
                    $emptyState.show();
                }
            },
            error: function() {
                $membersGrid.html('<p class="vh360-error">' + vh360Members.strings.error + '</p>');
            },
            complete: function() {
                state.isLoading = false;
                $loading.hide();
                $membersGrid.removeClass('loading');
            }
        });
    }
    
    /**
     * Update pagination UI
     */
    function updatePagination() {
        $currentPage.text(state.currentPage);
        $totalPages.text(state.totalPages);
        
        // Update prev button
        if (state.currentPage <= 1) {
            $prevBtn.prop('disabled', true);
        } else {
            $prevBtn.prop('disabled', false);
        }
        
        // Update next button
        if (state.currentPage >= state.totalPages) {
            $nextBtn.prop('disabled', true);
        } else {
            $nextBtn.prop('disabled', false);
        }
        
        // Hide pagination if only one page
        if (state.totalPages <= 1) {
            $pagination.hide();
        } else {
            $pagination.show();
        }
    }
    
    /**
     * Update URL with current state
     */
    function updateUrl() {
        const params = new URLSearchParams();
        
        if (state.search) params.set('search', state.search);
        
        // Only include role param for all_members mode
        if ((!state.directoryMode || state.directoryMode.audience === 'all_members') && state.role) {
            params.set('role', state.role);
        }
        
        if (state.category) params.set('category', state.category);
        if (state.joinDate) params.set('joined', state.joinDate);
        if (state.sortBy !== 'registered' || state.sortOrder !== 'DESC') {
            params.set('sort', `${state.sortBy}_${state.sortOrder.toLowerCase()}`);
        }
        if (state.currentPage > 1) params.set('page', state.currentPage);
        
        const newUrl = params.toString() ? `?${params.toString()}` : window.location.pathname;
        window.history.replaceState({}, '', newUrl);
    }
    
    /**
     * Parse URL parameters on load
     */
    function parseUrlParams() {
        const params = new URLSearchParams(window.location.search);
        
        if (params.has('search')) {
            state.search = params.get('search');
            $searchInput.val(state.search);
            $searchClear.show();
        }
        
        // Only parse role for all_members mode
        if ((!state.directoryMode || state.directoryMode.audience === 'all_members') && params.has('role')) {
            state.role = params.get('role');
            $roleFilter.val(state.role);
        }
        
        if (params.has('category')) {
            state.category = params.get('category');
            if ($categoryFilter.length) {
                $categoryFilter.val(state.category);
            }
        }
        
        if (params.has('joined')) {
            state.joinDate = params.get('joined');
            $dateFilter.val(state.joinDate);
        }
        
        if (params.has('sort')) {
            const sort = params.get('sort');
            const parts = sort.split('_');
            state.sortBy = parts[0];
            state.sortOrder = parts[1].toUpperCase();
            $sortSelect.val(sort);
        }
        
        if (params.has('page')) {
            state.currentPage = parseInt(params.get('page'));
        }
        
        // If we have URL params, reload members
        if (params.toString()) {
            loadMembers();
        }
    }
    
    /**
     * Scroll to top of members grid
     */
    function scrollToTop() {
        $('html, body').animate({
            scrollTop: $('.vh360-members-controls').offset().top - 20
        }, 400);
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        init();
    });
    
})(jQuery);
