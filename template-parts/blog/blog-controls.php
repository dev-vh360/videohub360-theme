<?php
/**
 * Blog Archive Controls
 *
 * Search, category filter, tag filter, and sort controls for the blog archive.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get categories and tags
$categories = get_categories(array(
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
));

$tags = get_tags(array(
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
));

// Get current filter values
$current_category = is_category() ? get_queried_object_id() : 0;
$current_tag = is_tag() ? get_queried_object_id() : 0;
$current_search = get_search_query();
?>

<div class="vh360-blog-controls">
    <div class="vh360-container">
        
        <div class="vh360-blog-controls-row">
            
            <!-- Search -->
            <div class="vh360-blog-search">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="vh360-blog-search-icon">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="search" 
                       id="vh360-blog-search" 
                       placeholder="<?php esc_attr_e('Search posts...', 'videohub360-theme'); ?>" 
                       class="vh360-blog-search-input"
                       value="<?php echo esc_attr($current_search); ?>">
            </div>
            
            <!-- Category Filter -->
            <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
            <div class="vh360-blog-filter">
                <label for="vh360-blog-category" class="vh360-blog-filter-label">
                    <?php esc_html_e('Category', 'videohub360-theme'); ?>
                </label>
                <select id="vh360-blog-category" class="vh360-blog-select">
                    <option value=""><?php esc_html_e('All Categories', 'videohub360-theme'); ?></option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>"
                                <?php selected($current_category, $category->term_id); ?>>
                            <?php echo esc_html($category->name); ?> (<?php echo absint($category->count); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Tag Filter -->
            <?php if (!empty($tags) && !is_wp_error($tags)) : ?>
            <div class="vh360-blog-filter">
                <label for="vh360-blog-tag" class="vh360-blog-filter-label">
                    <?php esc_html_e('Tag', 'videohub360-theme'); ?>
                </label>
                <select id="vh360-blog-tag" class="vh360-blog-select">
                    <option value=""><?php esc_html_e('All Tags', 'videohub360-theme'); ?></option>
                    <?php foreach ($tags as $tag) : ?>
                        <option value="<?php echo esc_attr($tag->term_id); ?>"
                                <?php selected($current_tag, $tag->term_id); ?>>
                            <?php echo esc_html($tag->name); ?> (<?php echo absint($tag->count); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Sort -->
            <div class="vh360-blog-filter">
                <label for="vh360-blog-sort" class="vh360-blog-filter-label">
                    <?php esc_html_e('Sort By', 'videohub360-theme'); ?>
                </label>
                <select id="vh360-blog-sort" class="vh360-blog-select">
                    <option value="date_desc"><?php esc_html_e('Newest First', 'videohub360-theme'); ?></option>
                    <option value="date_asc"><?php esc_html_e('Oldest First', 'videohub360-theme'); ?></option>
                    <option value="title_asc"><?php esc_html_e('Title A—Z', 'videohub360-theme'); ?></option>
                    <option value="title_desc"><?php esc_html_e('Title Z—A', 'videohub360-theme'); ?></option>
                    <option value="comment_count"><?php esc_html_e('Most Commented', 'videohub360-theme'); ?></option>
                </select>
            </div>
            
        </div>
        
        <!-- Results Count (populated by JS) -->
        <div class="vh360-blog-results-count" id="vh360-blog-results-count"></div>
        
    </div>
</div>
