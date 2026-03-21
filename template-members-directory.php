<?php
/**
 * Template Name: Members Directory
 *
 * Displays all site members with search, filtering, and sorting capabilities.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Resolve effective directory mode (global + page overrides)
$mode = vh360_get_members_directory_effective_mode(get_queried_object_id());

// Get members options from admin settings
$members_options = get_option('vh360_members_options', array());
$members_defaults = array(
    'enable_directory' => true,
    'per_page' => 12,
    'default_sort' => 'newest',
    'enable_search' => true,
    'visible_roles' => array(),
);
$members_options = wp_parse_args($members_options, $members_defaults);

// Check if directory is enabled
if (!$members_options['enable_directory']) {
    // Redirect to home if directory is disabled
    wp_safe_redirect(home_url('/'), 302);
    exit;
}

// Map default_sort to orderby/order values
$sort_mapping = array(
    'newest' => array('orderby' => 'registered', 'order' => 'DESC'),
    'oldest' => array('orderby' => 'registered', 'order' => 'ASC'),
    'alphabetical' => array('orderby' => 'display_name', 'order' => 'ASC'),
    'active' => array('orderby' => 'post_count', 'order' => 'DESC'),
);
$default_sort = isset($sort_mapping[$members_options['default_sort']]) 
    ? $sort_mapping[$members_options['default_sort']] 
    : $sort_mapping['newest'];

get_header();

// Header visibility and content from customizer - with dynamic defaults based on mode
$vh360_show_header  = (bool) get_theme_mod('vh360_show_members_header', 1);

// Dynamic defaults based on directory mode
$default_title = ($mode['audience'] === 'professionals_only') 
    ? __('Professionals Directory', 'videohub360-theme')
    : __('Members Directory', 'videohub360-theme');
$default_desc = ($mode['audience'] === 'professionals_only')
    ? __('Browse approved professionals', 'videohub360-theme')
    : __('Discover and connect with our community members', 'videohub360-theme');

$vh360_header_title = get_theme_mod('vh360_members_header_title', $default_title);
$vh360_header_desc  = get_theme_mod('vh360_members_header_description', $default_desc);

// Dynamic count label based on mode
$count_label = ($mode['audience'] === 'professionals_only')
    ? __('Total Professionals', 'videohub360-theme')
    : __('Total Members', 'videohub360-theme');
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-members-directory">
        
        <!-- Page Header -->
        <?php if ($vh360_show_header) : ?>
        <header class="vh360-members-header">
            <div class="vh360-container">
                <h1 class="vh360-members-title">
                    <?php echo esc_html($vh360_header_title); ?>
                </h1>
                <p class="vh360-members-description">
                    <?php echo esc_html($vh360_header_desc); ?>
                </p>
                
                <!-- Member Count -->
                <div class="vh360-member-count">
                    <?php
                    // Get count based on effective mode
                    $count_args = array(
                        'audience' => $mode['audience'],
                        'account_types' => $mode['professionals_account_types'],
                        'require_professional_approval' => $mode['professionals_require_approval'],
                    );
                    $total_count = vh360_get_member_count($count_args);
                    ?>
                    <span class="vh360-count-number"><?php echo esc_html(number_format_i18n($total_count)); ?></span>
                    <span class="vh360-count-label"><?php echo esc_html($count_label); ?></span>
                </div>
            </div>
        </header>
        <?php endif; ?>
        
        <!-- Search and Filters -->
        <?php if ($members_options['enable_search']) : ?>
        <div class="vh360-members-controls">
            <div class="vh360-container">
                
                <!-- Search Bar -->
                <div class="vh360-search-wrapper">
                    <input 
                        type="text" 
                        id="vh360-member-search" 
                        class="vh360-search-input" 
                        placeholder="<?php esc_attr_e('Search members by name or username...', 'videohub360-theme'); ?>"
                        aria-label="<?php esc_attr_e('Search members', 'videohub360-theme'); ?>"
                    >
                    <button type="button" class="vh360-search-clear" aria-label="<?php esc_attr_e('Clear search', 'videohub360-theme'); ?>" style="display: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <!-- Mobile Filters Toggle -->
<button
    type="button"
    class="vh360-controls-toggle"
    aria-expanded="false"
    aria-controls="vh360-members-filters-panel"
>
    <?php esc_html_e('Filters & Sort', 'videohub360-theme'); ?>
</button>

<!-- Filters and Sort -->
<div id="vh360-members-filters-panel" class="vh360-controls-panel is-collapsed">
    <div class="vh360-filters-wrapper">
                    
                    <?php if ($mode['audience'] === 'all_members') : ?>
                    <!-- Role Filter (only for all_members mode) -->
                    <div class="vh360-filter-group">
                        <label for="vh360-role-filter" class="vh360-filter-label">
                            <?php esc_html_e('Role:', 'videohub360-theme'); ?>
                        </label>
                        <select id="vh360-role-filter" class="vh360-filter-select">
                            <option value=""><?php esc_html_e('All Roles', 'videohub360-theme'); ?></option>
                            <?php
                            // Generate role options from visible_roles setting
                            if (!empty($members_options['visible_roles'])) {
                                global $wp_roles;
                                foreach ($members_options['visible_roles'] as $role_key) {
                                    if (isset($wp_roles->roles[$role_key])) {
                                        $role_name = $wp_roles->roles[$role_key]['name'];
                                        echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role_name) . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($members_options['enable_category_filter'])) :
                        $category_choices = function_exists('vh360_get_member_category_choices') 
                            ? vh360_get_member_category_choices() 
                            : array();
                        
                        if (!empty($category_choices)) :
                    ?>
                    <!-- Category Filter -->
                    <div class="vh360-filter-group">
                        <label for="vh360-category-filter" class="vh360-filter-label">
                            <?php esc_html_e('Category:', 'videohub360-theme'); ?>
                        </label>
                        <select id="vh360-category-filter" class="vh360-filter-select">
                            <option value=""><?php esc_html_e('All Categories', 'videohub360-theme'); ?></option>
                            <?php
                            foreach ($category_choices as $slug => $label) {
                                echo '<option value="' . esc_attr($slug) . '">' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <?php endif; endif; ?>
                    
                    <!-- Join Date Filter -->
                    <div class="vh360-filter-group">
                        <label for="vh360-date-filter" class="vh360-filter-label">
                            <?php esc_html_e('Joined:', 'videohub360-theme'); ?>
                        </label>
                        <select id="vh360-date-filter" class="vh360-filter-select">
                            <option value=""><?php esc_html_e('All Time', 'videohub360-theme'); ?></option>
                            <option value="week"><?php esc_html_e('Last 7 Days', 'videohub360-theme'); ?></option>
                            <option value="month"><?php esc_html_e('Last 30 Days', 'videohub360-theme'); ?></option>
                            <option value="year"><?php esc_html_e('Last Year', 'videohub360-theme'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Sort -->
                    <div class="vh360-filter-group">
                        <label for="vh360-sort-select" class="vh360-filter-label">
                            <?php esc_html_e('Sort By:', 'videohub360-theme'); ?>
                        </label>
                        <select id="vh360-sort-select" class="vh360-filter-select">
                            <option value="registered_desc"><?php esc_html_e('Newest First', 'videohub360-theme'); ?></option>
                            <option value="registered_asc"><?php esc_html_e('Oldest First', 'videohub360-theme'); ?></option>
                            <option value="display_name_asc"><?php esc_html_e('A-Z', 'videohub360-theme'); ?></option>
                            <option value="display_name_desc"><?php esc_html_e('Z-A', 'videohub360-theme'); ?></option>
                            <option value="post_count_desc"><?php esc_html_e('Most Videos', 'videohub360-theme'); ?></option>
                        </select>
                    </div>
                    
                    <!-- View Toggle -->
                    <div class="vh360-view-toggle">
                        <button 
                            type="button" 
                            class="vh360-view-btn vh360-view-grid active" 
                            data-view="grid"
                            aria-label="<?php esc_attr_e('Grid view', 'videohub360-theme'); ?>"
                            title="<?php esc_attr_e('Grid view', 'videohub360-theme'); ?>"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </button>
                        <button 
                            type="button" 
                            class="vh360-view-btn vh360-view-list" 
                            data-view="list"
                            aria-label="<?php esc_attr_e('List view', 'videohub360-theme'); ?>"
                            title="<?php esc_attr_e('List view', 'videohub360-theme'); ?>"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    
                </div> <!-- .vh360-filters-wrapper -->
                </div> <!-- #vh360-members-filters-panel -->
                
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Members Grid -->
        <div class="vh360-members-content">
            <div class="vh360-container">
                
                <!-- Loading State -->
                <div id="vh360-members-loading" class="vh360-loading" style="display: none;">
                    <div class="vh360-spinner"></div>
                    <p><?php esc_html_e('Loading members...', 'videohub360-theme'); ?></p>
                </div>
                
                <!-- Members Grid/List -->
                <div id="vh360-members-grid" class="vh360-members-grid view-grid">
                    <?php
                    // Get initial members using effective mode
                    $per_page = absint($members_options['per_page']);
                    $initial_args = array(
                        'audience' => $mode['audience'],
                        'account_types' => $mode['professionals_account_types'],
                        'require_professional_approval' => $mode['professionals_require_approval'],
                        'number' => $per_page,
                        'orderby' => $default_sort['orderby'],
                        'order' => $default_sort['order'],
                    );
                    $members = vh360_get_members($initial_args);
                    
                    if (!empty($members)) :
                        foreach ($members as $member) :
                            get_template_part('template-parts/components/card-profile', null, array(
                                'user_id' => $member->ID,
                                'show_avatar' => true,
                                'show_bio' => true,
                                'show_stats' => !empty($mode['show_card_stats']),
                                'show_follow_button' => !empty($mode['show_card_follow_button']),
                                'avatar_size' => 80,
                            ));
                        endforeach;
                    endif;
                    ?>
                </div>
                
                <!-- No Results State -->
                <div id="vh360-members-empty" class="vh360-empty-state" style="display: none;">
                    <div class="vh360-empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="vh360-empty-title"><?php esc_html_e('No Members Found', 'videohub360-theme'); ?></h3>
                    <p class="vh360-empty-text"><?php esc_html_e('Try adjusting your search or filters to find what you\'re looking for.', 'videohub360-theme'); ?></p>
                </div>
                
                <!-- Pagination -->
                <div id="vh360-members-pagination" class="vh360-pagination">
                    <?php
                    // Get total count based on effective mode
                    $count_args = array(
                        'audience' => $mode['audience'],
                        'account_types' => $mode['professionals_account_types'],
                        'require_professional_approval' => $mode['professionals_require_approval'],
                    );
                    $total_members = vh360_get_member_count($count_args);
                    $max_pages = ceil($total_members / $per_page);
                    
                    if ($max_pages > 1) :
                    ?>
                        <button 
                            type="button" 
                            class="vh360-pagination-btn vh360-pagination-prev" 
                            data-page="1"
                            disabled
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            <?php esc_html_e('Previous', 'videohub360-theme'); ?>
                        </button>
                        
                        <span class="vh360-pagination-info">
                            <?php esc_html_e('Page', 'videohub360-theme'); ?>
                            <span id="vh360-current-page">1</span>
                            <?php esc_html_e('of', 'videohub360-theme'); ?>
                            <span id="vh360-total-pages"><?php echo esc_html($max_pages); ?></span>
                        </span>
                        
                        <button 
                            type="button" 
                            class="vh360-pagination-btn vh360-pagination-next" 
                            data-page="2"
                            <?php echo ($max_pages <= 1) ? 'disabled' : ''; ?>
                        >
                            <?php esc_html_e('Next', 'videohub360-theme'); ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
    </main>
</div>

<?php
get_footer();
