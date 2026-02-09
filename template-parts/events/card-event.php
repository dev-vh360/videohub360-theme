<?php
/**
 * Event Card Component
 *
 * Reusable event card for displaying in lists.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get event ID from args or global post
$event_id = isset($args['event_id']) ? $args['event_id'] : get_the_ID();

if (!$event_id) {
    return;
}

$event = get_post($event_id);

if (!$event || 'vh360_event' !== $event->post_type) {
    return;
}

// Get event meta
$start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
$location_type = get_post_meta($event_id, '_vh360_event_location_type', true);
$cost_type = get_post_meta($event_id, '_vh360_event_cost_type', true);

$is_upcoming = vh360_is_event_upcoming($event_id);
$is_past = vh360_is_event_past($event_id);

$card_classes = array('vh360-event-card');
if ($is_past) {
    $card_classes[] = 'vh360-event-card-past';
}

?>

<article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" data-event-id="<?php echo esc_attr($event_id); ?>">
    
    <!-- Featured Image -->
    <div class="vh360-event-card-image">
        <a href="<?php echo esc_url(get_permalink($event_id)); ?>">
            <?php if (has_post_thumbnail($event_id)) : ?>
                <?php echo get_the_post_thumbnail($event_id, 'medium_large', array('class' => 'vh360-event-thumbnail')); ?>
            <?php else : ?>
                <div class="vh360-event-placeholder">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
            <?php endif; ?>
        </a>
        
        <!-- Event Date Badge -->
        <?php if (!empty($start_date)) : 
            try {
                $date_obj = new DateTime($start_date, wp_timezone());
                ?>
                <div class="vh360-event-date-badge">
                    <span class="vh360-event-date-month">
                        <?php echo esc_html($date_obj->format('M')); ?>
                    </span>
                    <span class="vh360-event-date-day">
                        <?php echo esc_html($date_obj->format('d')); ?>
                    </span>
                </div>
                <?php
            } catch (Exception $e) {
                // Silently fail if date is invalid
            }
        endif; 
        ?>
        
        <!-- Status Badges -->
        <div class="vh360-event-card-badges">
            <?php if ($is_upcoming) : ?>
                <span class="vh360-event-badge vh360-event-badge-upcoming">
                    <?php esc_html_e('Upcoming', 'videohub360-theme'); ?>
                </span>
            <?php elseif ($is_past) : ?>
                <span class="vh360-event-badge vh360-event-badge-past">
                    <?php esc_html_e('Past', 'videohub360-theme'); ?>
                </span>
            <?php endif; ?>
            
            <?php echo vh360_get_event_status_badge($event_id); ?>
        </div>
    </div>
    
    <!-- Event Content -->
    <div class="vh360-event-card-content">
        
        <!-- Title -->
        <h3 class="vh360-event-card-title">
            <a href="<?php echo esc_url(get_permalink($event_id)); ?>">
                <?php echo esc_html($event->post_title); ?>
            </a>
        </h3>
        
        <!-- Meta Info -->
        <div class="vh360-event-card-meta">
            
            <!-- Date & Time -->
            <div class="vh360-event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span><?php echo esc_html(vh360_get_event_date_range($event_id)); ?></span>
            </div>
            
            <!-- Location -->
            <div class="vh360-event-card-meta-item">
                <?php if ($location_type === 'online') : ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <span><?php esc_html_e('Online Event', 'videohub360-theme'); ?></span>
                <?php else : ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span><?php echo esc_html(vh360_get_event_location($event_id)); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Cost -->
            <div class="vh360-event-card-meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span><?php echo esc_html(vh360_get_event_cost_display($event_id)); ?></span>
            </div>
            
        </div>
        
        <!-- Excerpt -->
        <?php if ($event->post_excerpt) : ?>
        <div class="vh360-event-card-excerpt">
            <?php echo wp_kses_post(wp_trim_words($event->post_excerpt, 20)); ?>
        </div>
        <?php endif; ?>
        
        <!-- Categories -->
        <?php
        $categories = get_the_terms($event_id, 'vh360_event_category');
        if ($categories && !is_wp_error($categories)) :
        ?>
        <div class="vh360-event-card-categories">
            <?php foreach (array_slice($categories, 0, 2) as $category) : ?>
                <span class="vh360-event-category-tag">
                    <?php echo esc_html($category->name); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Card Footer -->
    <div class="vh360-event-card-footer">
        <a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="vh360-event-card-link">
            <?php esc_html_e('View Details', 'videohub360-theme'); ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </a>
    </div>
    
</article>
