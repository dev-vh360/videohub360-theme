<?php
/**
 * Blog Archive Empty State
 *
 * Displayed when no posts are found.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vh360-blog-empty">
    <div class="vh360-blog-empty-icon">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="12" y1="18" x2="12" y2="12"></line>
            <line x1="9" y1="15" x2="15" y2="15"></line>
        </svg>
    </div>
    <h2 class="vh360-blog-empty-title">
        <?php esc_html_e('No Posts Found', 'videohub360-theme'); ?>
    </h2>
    <p class="vh360-blog-empty-text">
        <?php esc_html_e('There are currently no posts matching your criteria. Try adjusting your filters or check back later.', 'videohub360-theme'); ?>
    </p>
</div>
