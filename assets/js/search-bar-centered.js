/**
 * Centered Search Bar JavaScript
 * Handles live search functionality with debouncing and filtering
 * Supports two modes:
 * 1. Grouped Mode: Results organized by content type with filter tabs and headings
 * 2. Unified Mode: Single flat list of all results without categories or filters
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Wait for DOM to be ready
    $(document).ready(function() {
        const searchContainer = $('.vh360-search-bar-centered');
        const searchInput = $('#vh360-search-input');
        const searchDropdown = $('#vh360-search-dropdown');
        const searchResults = $('#vh360-search-results');
        const loadingState = $('.vh360-search-bar-centered__loading');
        const emptyState = $('.vh360-search-bar-centered__empty');
        const errorState = $('.vh360-search-bar-centered__error');
        const filterTabs = $('.vh360-search-bar-centered__filter-tab');
        const mobileToggle = $('.vh360-search-bar-centered__mobile-toggle');
        const mobileClose = $('.vh360-search-bar-centered__mobile-close');
        
        let searchTimeout = null;
        let currentQuery = '';
        let currentFilter = 'all';
        let lastResults = null;
        let isMobileSearchActive = false;
        
        // Get mode from localized settings
        const isGroupedMode = vh360SearchBar.groupResults;
        const availableTypes = vh360SearchBar.availableTypes || [];
        
        // In unified mode, force filter to 'all' and disable filter functionality
        if (!isGroupedMode) {
            currentFilter = 'all';
        }
        
        // Mobile toggle handlers
        mobileToggle.on('click', function() {
            openMobileSearch();
        });
        
        mobileClose.on('click', function() {
            closeMobileSearch();
        });
        
        function openMobileSearch() {
            isMobileSearchActive = true;
            searchContainer.addClass('mobile-search-active');
            $('body').css('overflow', 'hidden'); // Prevent background scrolling
            
            // Focus input after animation
            setTimeout(function() {
                searchInput.focus();
            }, 100);
        }
        
        function closeMobileSearch() {
            isMobileSearchActive = false;
            searchContainer.removeClass('mobile-search-active');
            $('body').css('overflow', ''); // Restore scrolling
            hideDropdown();
            searchInput.val(''); // Clear search
            currentQuery = '';
        }
        
        // Debounced search function
        function performSearch(query) {
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Minimum 2 characters required
            if (query.length < 2) {
                hideDropdown();
                return;
            }
            
            // Show loading state
            showLoading();
            
            // Debounce search by 300ms
            searchTimeout = setTimeout(function() {
                currentQuery = query;
                
                // Make AJAX request
                $.ajax({
                    url: vh360SearchBar.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'vh360_advanced_search',
                        nonce: vh360SearchBar.nonce,
                        query: query,
                        type: currentFilter
                    },
                    success: function(response) {
                        if (response.success) {
                            lastResults = response.data.results;
                            displayResults(response.data.results, query);
                        } else {
                            showError();
                        }
                    },
                    error: function() {
                        showError();
                    }
                });
            }, 300);
        }
        
        // Display search results
        // Supports two modes:
        // - Grouped: Results organized by type with headings (current behavior)
        // - Unified: Single flat list without type headings (new mode)
        function displayResults(results, query) {
            searchResults.empty();
            
            // Count total results
            let totalResults = 0;
            for (const type in results) {
                totalResults += results[type].length;
            }
            
            // Hide loading, show dropdown
            loadingState.hide();
            errorState.hide();
            
            // Check if we have any results
            if (totalResults === 0) {
                emptyState.show();
                searchResults.hide();
                showDropdown();
                return;
            }
            
            emptyState.hide();
            searchResults.show();
            
            if (isGroupedMode) {
                // GROUPED MODE: Display results organized by type with headings
                displayGroupedResults(results, query);
            } else {
                // UNIFIED MODE: Display results as a single flat list
                displayUnifiedResults(results, query);
            }
            
            showDropdown();
        }
        
        // Display results in grouped mode (with category headings)
        function displayGroupedResults(results, query) {
            const typeLabels = {
                videos: vh360SearchBar.i18n.videos,
                members: vh360SearchBar.i18n.members,
                events: vh360SearchBar.i18n.events,
                galleries: vh360SearchBar.i18n.galleries,
                bulletins: vh360SearchBar.i18n.bulletins,
                posts: vh360SearchBar.i18n.posts
            };
            
            // Render groups in the order of availableTypes (not by object key iteration)
            availableTypes.forEach(function(type) {
                if (results[type] && results[type].length > 0) {
                    const group = $('<div class="vh360-search-bar-centered__result-group"></div>');
                    // Use localized label or fallback to uppercased type
                    // Note: Custom types added via filter should provide i18n labels
                    const title = $('<h3 class="vh360-search-bar-centered__result-group-title"></h3>')
                        .text(typeLabels[type] || type.toUpperCase());
                    group.append(title);
                    
                    results[type].forEach(function(item) {
                        const resultItem = createResultItem(item, query);
                        group.append(resultItem);
                    });
                    
                    searchResults.append(group);
                }
            });
        }
        
        // Display results in unified mode (single flat list, no headings)
        function displayUnifiedResults(results, query) {
            // Flatten results into a single array, maintaining the order of availableTypes
            const flatResults = [];
            
            availableTypes.forEach(function(type) {
                if (results[type] && results[type].length > 0) {
                    results[type].forEach(function(item) {
                        flatResults.push(item);
                    });
                }
            });
            
            // Render all results without group headings
            flatResults.forEach(function(item) {
                const resultItem = createResultItem(item, query);
                searchResults.append(resultItem);
            });
        }
        
        // Create result item HTML
        function createResultItem(item, query) {
            const link = $('<a></a>')
                .attr('href', item.url)
                .addClass('vh360-search-bar-centered__result-item')
                .addClass('vh360-search-bar-centered__result-item--' + item.type);
            
            // Add thumbnail/avatar
            if (item.type === 'member' || item.type === 'post') {
                const avatar = $('<img>')
                    .attr('src', item.avatar || vh360SearchBar.defaultAvatar)
                    .attr('alt', item.title)
                    .addClass('vh360-search-bar-centered__result-avatar')
                    .attr('loading', 'lazy');
                link.append(avatar);
            } else if (item.thumbnail && item.thumbnail !== '') {
                const thumbnail = $('<img>')
                    .attr('src', item.thumbnail)
                    .attr('alt', item.title)
                    .addClass('vh360-search-bar-centered__result-thumbnail')
                    .attr('loading', 'lazy');
                link.append(thumbnail);
            }
            
            // Add content
            const content = $('<div class="vh360-search-bar-centered__result-content"></div>');
            const title = $('<h4 class="vh360-search-bar-centered__result-title"></h4>').html(highlightText(item.title, query));
            content.append(title);
            
            // Add metadata based on type
            let metaText = '';
            switch (item.type) {
                case 'video':
                    const views = parseInt(item.views) || 0;
                    metaText = item.author + ' • ' + views.toLocaleString() + ' ' + vh360SearchBar.i18n.views;
                    break;
                case 'member':
                    metaText = '@' + item.username + ' • ' + item.role;
                    break;
                case 'event':
                    metaText = item.date;
                    if (item.location) {
                        metaText += ' • ' + item.location;
                    }
                    break;
                case 'gallery':
                    const imageCount = parseInt(item.image_count) || 0;
                    metaText = item.author + ' • ' + imageCount + ' ' + vh360SearchBar.i18n.images;
                    break;
                case 'bulletin':
                    metaText = item.date;
                    if (item.priority) {
                        metaText += ' • ' + item.priority;
                    }
                    break;
                case 'post':
                    metaText = item.author + ' • ' + item.date;
                    break;
            }
            
            if (metaText) {
                const meta = $('<p class="vh360-search-bar-centered__result-meta"></p>').text(metaText);
                content.append(meta);
            }
            
            link.append(content);
            return link;
        }
        
        // Highlight search query in text
        function highlightText(text, query) {
            if (!query || !text) return text;
            
            const regex = new RegExp('(' + escapeRegExp(query) + ')', 'gi');
            return text.replace(regex, '<span class="vh360-search-bar-centered__highlight">$1</span>');
        }
        
        // Escape special regex characters
        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        // Show dropdown
        function showDropdown() {
            searchDropdown.attr('aria-hidden', 'false');
        }
        
        // Hide dropdown
        function hideDropdown() {
            searchDropdown.attr('aria-hidden', 'true');
        }
        
        // Show loading state
        function showLoading() {
            loadingState.show();
            searchResults.hide();
            emptyState.hide();
            errorState.hide();
            showDropdown();
        }
        
        // Show error state
        function showError() {
            loadingState.hide();
            searchResults.hide();
            emptyState.hide();
            errorState.show();
            showDropdown();
        }
        
        // Handle input changes
        searchInput.on('input', function() {
            const query = $(this).val().trim();
            performSearch(query);
        });
        
        // Handle filter tab clicks (only in grouped mode)
        if (isGroupedMode) {
            filterTabs.on('click', function() {
                const filter = $(this).data('filter');
                
                // Update active state
                filterTabs.removeClass('active');
                $(this).addClass('active');
                
                // Update current filter
                currentFilter = filter;
                
                // Re-run search with new filter
                if (currentQuery.length >= 2) {
                    performSearch(currentQuery);
                }
            });
        }
        
        // Close dropdown when clicking outside (desktop only)
        $(document).on('click', function(e) {
            if (!isMobileSearchActive && !$(e.target).closest('.vh360-search-bar-centered').length) {
                hideDropdown();
            }
        });
        
        // Close dropdown/mobile search on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if (isMobileSearchActive) {
                    closeMobileSearch();
                } else {
                    hideDropdown();
                    searchInput.blur();
                }
            }
        });
        
        // Handle Enter key to search or navigate
        searchInput.on('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                
                // If we have results, go to first result
                const firstResult = searchResults.find('.vh360-search-bar-centered__result-item').first();
                if (firstResult.length) {
                    window.location.href = firstResult.attr('href');
                }
            }
        });
        
        // Focus input when clicking search button
        $('.vh360-search-bar-centered__button').on('click', function() {
            searchInput.focus();
        });
    });
    
})(jQuery);
