<?php
/**
 * Template Name: Bulletins Archive
 *
 * Archive page showing all active bulletins with filters.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Header visibility and content from customizer
$vh360_show_header  = (bool) get_theme_mod('vh360_show_bulletins_header', 1);
$vh360_header_title = get_theme_mod('vh360_bulletins_header_title', __('Bulletins', 'videohub360-theme'));
$vh360_header_desc  = get_theme_mod('vh360_bulletins_header_description', __('Stay updated with the latest announcements and important information.', 'videohub360-theme'));

$user_id = get_current_user_id();
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get all active bulletins (simplified query like archive template)
$args = array(
    'post_type' => 'vh360_bulletin',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC'
);

$bulletins_query = new WP_Query($args);

?>

<div id="primary" class="content-area vh360-bulletins-archive <?php echo $vh360_show_header ? '' : 'vh360-template-header-off'; ?>">
    <main id="main" class="site-main">
        
        <!-- Header -->
        <?php if ( $vh360_show_header ) : ?>
        <div class="vh360-bulletins-header">
            <div class="vh360-container">
                <h1 class="vh360-bulletins-title">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <?php echo esc_html( $vh360_header_title ); ?>
                </h1>
                <p class="vh360-bulletins-description">
                    <?php esc_html_e('Stay updated with the latest news and important announcements.', 'videohub360-theme'); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="vh360-container">
            
            <!-- Filters & Search -->
            <div class="vh360-bulletins-controls">
                
                <!-- Filter Tabs -->
                <div class="vh360-bulletins-filters">
                    <button class="vh360-bulletin-filter active" data-filter="all">
                        <?php esc_html_e('All', 'videohub360-theme'); ?>
                    </button>
                    <button class="vh360-bulletin-filter" data-filter="urgent">
                        <?php esc_html_e('Urgent', 'videohub360-theme'); ?>
                    </button>
                    <button class="vh360-bulletin-filter" data-filter="important">
                        <?php esc_html_e('Important', 'videohub360-theme'); ?>
                    </button>
                    <button class="vh360-bulletin-filter" data-filter="normal">
                        <?php esc_html_e('Normal', 'videohub360-theme'); ?>
                    </button>
                </div>
                
                <!-- Search -->
                <div class="vh360-bulletins-search">
                    <input type="search" 
                           id="vh360-bulletin-search" 
                           placeholder="<?php esc_attr_e('Search bulletins...', 'videohub360-theme'); ?>" 
                           class="vh360-bulletin-search-input">
                </div>
                
                <?php if ($user_id && vh360_has_unread_bulletins($user_id)) : ?>
                    <button class="vh360-bulletin-mark-all-read vh360-btn-secondary">
                        <?php esc_html_e('Mark All as Read', 'videohub360-theme'); ?>
                    </button>
                <?php endif; ?>
                
            </div>
            
            <!-- Bulletins List -->
            <?php if ($bulletins_query->have_posts()) : ?>
                
                <div class="vh360-bulletins-list">
                    <?php 
                    while ($bulletins_query->have_posts()) : 
                        $bulletins_query->the_post();
                        
                        // Skip if expired
                        $expiry_date = get_post_meta(get_the_ID(), '_vh360_bulletin_expiry_date', true);
                        if ($expiry_date && $expiry_date < current_time('timestamp')) {
                            continue;
                        }
                        
                        // For logged-in users, check if they can see it and haven't dismissed it
                        if ($user_id) {
                            // Check if user has dismissed this bulletin
                            $dismissed_bulletins = get_user_meta($user_id, '_vh360_dismissed_bulletins', true);
                            if (is_array($dismissed_bulletins) && in_array(get_the_ID(), $dismissed_bulletins)) {
                                continue;
                            }
                            
                            // Check if user can see this bulletin based on targeting
                            if (!vh360_can_user_see_bulletin(get_the_ID(), $user_id)) {
                                continue;
                            }
                        }
                        
                        get_template_part('template-parts/bulletin/card', null, array(
                            'bulletin_id' => get_the_ID(),
                            'show_actions' => true,
                            'compact' => false
                        ));
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($bulletins_query->max_num_pages > 1) : ?>
                    <div class="vh360-bulletins-pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $bulletins_query->max_num_pages,
                            'current' => $paged,
                            'prev_text' => '&larr; ' . __('Previous', 'videohub360-theme'),
                            'next_text' => __('Next', 'videohub360-theme') . ' &rarr;',
                            'type' => 'list',
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
            <?php else : ?>
                
                <!-- Empty State -->
                <div class="vh360-bulletins-empty">
                    <div class="vh360-bulletins-empty-icon">📢</div>
                    <h2 class="vh360-bulletins-empty-title">
                        <?php esc_html_e('No Bulletins Available', 'videohub360-theme'); ?>
                    </h2>
                    <p class="vh360-bulletins-empty-text">
                        <?php esc_html_e('There are currently no bulletins to display. Check back later for updates.', 'videohub360-theme'); ?>
                    </p>
                </div>
                
            <?php endif; ?>
            
        </div>
        
    </main>
</div>

<style>
/* Bulletins Archive Styles */
.vh360-bulletins-archive {
    background: var(--bg-color, #f9fafb);
    min-height: 100vh;
    padding-bottom: 4rem;
}

/* Container - matching other templates */
.vh360-bulletins-archive .vh360-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* vh360-bulletins-header styles are inherited from dynamic-css.php for consistency */

.vh360-bulletins-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
}

.vh360-bulletins-description {
    font-size: 1.125rem;
    margin: 0 0 1.5rem;
    opacity: 0.95;
}

.vh360-bulletins-controls {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.vh360-bulletins-filters {
    display: flex;
    gap: 0.5rem;
    background: var(--bg-color, #ffffff);
    padding: 0.25rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.vh360-bulletin-filter {
    padding: 0.5rem 1.25rem;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-light, #6b7280);
    cursor: pointer;
    transition: all 0.2s;
}

.vh360-bulletin-filter:hover {
    background: var(--bg-light, #f3f4f6);
    color: var(--text-color, #1f2937);
}

.vh360-bulletin-filter.active {
    background: var(--primary-color, #3b82f6);
    color: white;
}

.vh360-bulletins-search {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.vh360-bulletin-search-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    background: var(--bg-color, #ffffff);
    transition: all 0.2s;
}

.vh360-bulletin-search-input:focus {
    outline: none;
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.vh360-btn-secondary {
    padding: 0.75rem 1.5rem;
    background: var(--bg-color, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-color, #1f2937);
    cursor: pointer;
    transition: all 0.2s;
}

.vh360-btn-secondary:hover {
    background: var(--bg-light, #f3f4f6);
    border-color: var(--border-color-dark, #d1d5db);
}

.vh360-bulletins-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.vh360-bulletins-pagination {
    margin-top: 3rem;
}

.vh360-bulletins-pagination ul {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

.vh360-bulletins-pagination li {
    margin: 0;
}

.vh360-bulletins-pagination a,
.vh360-bulletins-pagination span {
    display: block;
    padding: 0.5rem 1rem;
    background: var(--bg-color, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 0.375rem;
    color: var(--text-color, #1f2937);
    text-decoration: none;
    transition: all 0.2s;
}

.vh360-bulletins-pagination a:hover {
    background: var(--primary-color, #3b82f6);
    color: white;
    border-color: var(--primary-color, #3b82f6);
}

.vh360-bulletins-pagination .current {
    background: var(--primary-color, #3b82f6);
    color: white;
    border-color: var(--primary-color, #3b82f6);
}

.vh360-bulletins-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--bg-color, #ffffff);
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.vh360-bulletins-empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.vh360-bulletins-empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    color: var(--text-color, #1f2937);
}

.vh360-bulletins-empty-text {
    font-size: 1rem;
    color: var(--text-light, #6b7280);
    margin: 0;
}

@media (max-width: 768px) {
    .vh360-bulletins-title {
        font-size: 1.5rem;
    }
    
    .vh360-bulletins-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .vh360-bulletins-filters {
        overflow-x: auto;
    }
    
    .vh360-bulletins-search {
        max-width: none;
    }
    
    .vh360-bulletins-list {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_footer();