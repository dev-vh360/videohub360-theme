<?php
/**
 * Centered Search Bar Component
 *
 * YouTube-style centered search bar with live search results and filtering.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vh360-search-bar-centered">
    <!-- Mobile Search Icon Toggle -->
    <button class="vh360-search-bar-centered__mobile-toggle" aria-label="<?php echo esc_attr__('Open Search', 'videohub360-theme'); ?>" aria-expanded="false">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>
    
    <div class="vh360-search-bar-centered__container">
        <!-- Mobile Close Button - Outside dropdown so always visible -->
        <button class="vh360-search-bar-centered__mobile-close" aria-label="<?php echo esc_attr__('Close Search', 'videohub360-theme'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        
        <div class="vh360-search-bar-centered__input-wrapper">
            <input 
                type="text" 
                class="vh360-search-bar-centered__input" 
                placeholder="<?php echo esc_attr__('Search videos, members, events...', 'videohub360-theme'); ?>"
                aria-label="<?php echo esc_attr__('Search', 'videohub360-theme'); ?>"
                autocomplete="off"
                id="vh360-search-input"
            >
            <button 
                type="button" 
                class="vh360-search-bar-centered__button"
                aria-label="<?php echo esc_attr__('Search', 'videohub360-theme'); ?>"
            >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- Search Results Dropdown -->
        <div class="vh360-search-bar-centered__dropdown" id="vh360-search-dropdown" aria-hidden="true">
            <!-- Filter Tabs -->
            <div class="vh360-search-bar-centered__filters">
                <button class="vh360-search-bar-centered__filter-tab active" data-filter="all">
                    <?php esc_html_e('All', 'videohub360-theme'); ?>
                </button>
                <button class="vh360-search-bar-centered__filter-tab" data-filter="videos">
                    <?php esc_html_e('Videos', 'videohub360-theme'); ?>
                </button>
                <button class="vh360-search-bar-centered__filter-tab" data-filter="members">
                    <?php esc_html_e('Members', 'videohub360-theme'); ?>
                </button>
                <button class="vh360-search-bar-centered__filter-tab" data-filter="events">
                    <?php esc_html_e('Events', 'videohub360-theme'); ?>
                </button>
                <button class="vh360-search-bar-centered__filter-tab" data-filter="galleries">
                    <?php esc_html_e('Galleries', 'videohub360-theme'); ?>
                </button>
                <button class="vh360-search-bar-centered__filter-tab" data-filter="bulletins">
                    <?php esc_html_e('Bulletins', 'videohub360-theme'); ?>
                </button>
                <button class="vh360-search-bar-centered__filter-tab" data-filter="posts">
                    <?php esc_html_e('Posts', 'videohub360-theme'); ?>
                </button>
            </div>
            
            <!-- Results Container -->
            <div class="vh360-search-bar-centered__results" id="vh360-search-results">
                <!-- Results will be populated by JavaScript -->
            </div>
            
            <!-- Loading State -->
            <div class="vh360-search-bar-centered__loading" style="display: none;">
                <div class="vh360-search-bar-centered__spinner"></div>
                <p><?php esc_html_e('Searching...', 'videohub360-theme'); ?></p>
            </div>
            
            <!-- Empty State -->
            <div class="vh360-search-bar-centered__empty" style="display: none;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p><?php esc_html_e('No results found. Try a different search term.', 'videohub360-theme'); ?></p>
            </div>
            
            <!-- Error State -->
            <div class="vh360-search-bar-centered__error" style="display: none;">
                <p><?php esc_html_e('An error occurred. Please try again.', 'videohub360-theme'); ?></p>
            </div>
        </div>
    </div>
</div>
