<?php
/**
 * Blog Archive Header
 *
 * Header section for the blog archive with title and description.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get values from args or use defaults (for category/tag archives called directly)
$show_header = isset($args['show_header']) ? $args['show_header'] : true;
$blog_title = isset($args['header_title']) ? $args['header_title'] : __('Blog', 'videohub360-theme');
$blog_description = isset($args['header_desc']) ? $args['header_desc'] : __('Discover articles, insights, and updates from our community', 'videohub360-theme');

// Override with page title if this is a category or tag archive
if (is_category() || is_tag()) {
    $show_header = true;
    $blog_title = single_term_title('', false);
    $blog_description = term_description();
}

if (!$show_header) {
    return;
}
?>

<div class="vh360-blog-header">
    <div class="vh360-container">
        <h1 class="vh360-blog-title">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <?php echo esc_html($blog_title); ?>
        </h1>
        <?php if (!empty($blog_description)) : ?>
            <div class="vh360-blog-description">
                <?php echo wp_kses_post(wpautop($blog_description)); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
