<?php
/**
 * Archive Template for Events
 *
 * Archive page showing all events with filters.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Header visibility and content from customizer
$vh360_show_header  = (bool) get_theme_mod('vh360_show_events_header', 1);
$vh360_header_title = get_theme_mod('vh360_events_header_title', __('Community Events', 'videohub360-theme'));
$vh360_header_desc  = get_theme_mod('vh360_events_header_description', __('Discover and join exciting events in our community', 'videohub360-theme'));

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get events
$args = vh360_get_events_query_args(array(
    'paged' => $paged,
));

$events_query = new WP_Query($args);

?>

<div id="primary" class="content-area vh360-events-archive <?php echo $vh360_show_header ? '' : 'vh360-template-header-off'; ?>">
    <main id="main" class="site-main">
        
        <!-- Header -->
        <?php if ($vh360_show_header) : ?>
        <div class="vh360-events-header">
            <div class="vh360-container">
                <h1 class="vh360-events-title">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?php echo esc_html($vh360_header_title); ?>
                </h1>
                <p class="vh360-events-description">
                    <?php echo esc_html($vh360_header_desc); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="vh360-container">
            
            <!-- Filters & Search -->
            <div class="vh360-events-controls">
                
                <!-- Filter Tabs -->
                <div class="vh360-events-filters">
                    <button class="vh360-event-filter active" data-filter="upcoming">
                        <?php esc_html_e('Upcoming', 'videohub360-theme'); ?>
                    </button>
                    <button class="vh360-event-filter" data-filter="past">
                        <?php esc_html_e('Past', 'videohub360-theme'); ?>
                    </button>
                    <button class="vh360-event-filter" data-filter="all">
                        <?php esc_html_e('All Events', 'videohub360-theme'); ?>
                    </button>
                </div>
                
                <!-- Category Filter -->
                <?php
                $categories = get_terms(array(
                    'taxonomy'   => 'vh360_event_category',
                    'hide_empty' => true,
                ));
                
                if (!empty($categories) && !is_wp_error($categories)) :
                ?>
                <div class="vh360-events-category-filter">
                    <select id="vh360-event-category" class="vh360-event-category-select">
                        <option value=""><?php esc_html_e('All Categories', 'videohub360-theme'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- Search -->
                <div class="vh360-events-search">
                    <input type="search" 
                           id="vh360-event-search" 
                           placeholder="<?php esc_attr_e('Search events...', 'videohub360-theme'); ?>" 
                           class="vh360-event-search-input">
                </div>
                
            </div>
            
            <!-- Events List -->
            <div id="vh360-events-list-container">
                <?php if ($events_query->have_posts()) : ?>
                    
                    <div class="vh360-events-list">
                        <?php 
                        while ($events_query->have_posts()) : 
                            $events_query->the_post();
                            
                            get_template_part('template-parts/events/card-event', null, array(
                                'event_id' => get_the_ID(),
                            ));
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($events_query->max_num_pages > 1) : ?>
                        <div class="vh360-events-pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $events_query->max_num_pages,
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
                    <div class="vh360-events-empty">
                        <div class="vh360-events-empty-icon">📅</div>
                        <h2 class="vh360-events-empty-title">
                            <?php esc_html_e('No Events Found', 'videohub360-theme'); ?>
                        </h2>
                        <p class="vh360-events-empty-text">
                            <?php esc_html_e('There are currently no events to display. Check back later for updates.', 'videohub360-theme'); ?>
                        </p>
                    </div>
                    
                <?php endif; ?>
            </div>
            
        </div>
        
    </main>
</div>

<?php
get_footer();
