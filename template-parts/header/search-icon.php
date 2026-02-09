<?php
/**
 * Search Icon Component
 *
 * Placeholder for future advanced search feature with modal.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<button class="header-icon header-search-toggle" aria-label="<?php esc_attr_e('Search', 'videohub360-theme'); ?>" aria-controls="header-search-modal" aria-expanded="false">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>

<div id="header-search-modal" class="header-search-modal" aria-hidden="true">
    <div class="header-search-modal__backdrop"></div>
    <div class="header-search-modal__content">
        <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
            <label for="header-search-input" class="screen-reader-text"><?php esc_html_e('Search for:', 'videohub360-theme'); ?></label>
            <input type="search" id="header-search-input" name="s" placeholder="<?php esc_attr_e('Search...', 'videohub360-theme'); ?>" autocomplete="off">
            <button type="submit" aria-label="<?php esc_attr_e('Submit search', 'videohub360-theme'); ?>">
                <?php esc_html_e('Search', 'videohub360-theme'); ?>
            </button>
        </form>
        <button class="header-search-close" aria-label="<?php esc_attr_e('Close search', 'videohub360-theme'); ?>">&times;</button>
    </div>
</div>
