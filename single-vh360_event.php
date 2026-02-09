<?php
/**
 * Single Event Template
 *
 * Template for displaying a single event.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    
    $event_id = get_the_ID();
    
    // Get event meta
    $start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
    $start_time = get_post_meta($event_id, '_vh360_event_start_time', true);
    $end_date = get_post_meta($event_id, '_vh360_event_end_date', true);
    $end_time = get_post_meta($event_id, '_vh360_event_end_time', true);
    $location_type = get_post_meta($event_id, '_vh360_event_location_type', true);
    $venue_name = get_post_meta($event_id, '_vh360_event_venue_name', true);
    $venue_address = get_post_meta($event_id, '_vh360_event_venue_address', true);
    $venue_city = get_post_meta($event_id, '_vh360_event_venue_city', true);
    $venue_state = get_post_meta($event_id, '_vh360_event_venue_state', true);
    $venue_zip = get_post_meta($event_id, '_vh360_event_venue_zip', true);
    $venue_country = get_post_meta($event_id, '_vh360_event_venue_country', true);
    $online_url = get_post_meta($event_id, '_vh360_event_online_url', true);
    $event_status = get_post_meta($event_id, '_vh360_event_status', true);
    $registration_required = get_post_meta($event_id, '_vh360_event_registration_required', true);
    $registration_deadline = get_post_meta($event_id, '_vh360_event_registration_deadline', true);
    $max_attendees = get_post_meta($event_id, '_vh360_event_max_attendees', true);
    $cost_type = get_post_meta($event_id, '_vh360_event_cost_type', true);
    $cost_amount = get_post_meta($event_id, '_vh360_event_cost_amount', true);
    $organizer_name = get_post_meta($event_id, '_vh360_event_organizer_name', true);
    $organizer_email = get_post_meta($event_id, '_vh360_event_organizer_email', true);
    $organizer_phone = get_post_meta($event_id, '_vh360_event_organizer_phone', true);
    
    $is_upcoming = vh360_is_event_upcoming($event_id);
    $is_past = vh360_is_event_past($event_id);
    
    ?>
    
    <article id="event-<?php echo esc_attr($event_id); ?>" <?php post_class('vh360-single-event'); ?>>
        
        <div class="vh360-event-container">
            
            <!-- Event Header -->
            <div class="vh360-event-header">
                
                <!-- Back Link -->
                <div class="vh360-event-back">
                    <a href="<?php echo esc_url(get_post_type_archive_link('vh360_event')); ?>" class="vh360-event-back-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <?php esc_html_e('Back to Events', 'videohub360-theme'); ?>
                    </a>
                </div>
                
                <!-- Event Title & Meta -->
                <div class="vh360-event-title-section">
                    <h1 class="vh360-event-title"><?php the_title(); ?></h1>
                    
                    <div class="vh360-event-meta">
                        <?php echo vh360_get_event_status_badge($event_id); ?>
                        
                        <?php if ($is_upcoming) : ?>
                            <span class="vh360-event-badge vh360-event-badge-upcoming">
                                <?php esc_html_e('Upcoming', 'videohub360-theme'); ?>
                            </span>
                        <?php elseif ($is_past) : ?>
                            <span class="vh360-event-badge vh360-event-badge-past">
                                <?php esc_html_e('Past Event', 'videohub360-theme'); ?>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Categories -->
                        <?php
                        $categories = get_the_terms($event_id, 'vh360_event_category');
                        if ($categories && !is_wp_error($categories)) :
                            foreach ($categories as $category) :
                        ?>
                            <span class="vh360-event-category">
                                <?php echo esc_html($category->name); ?>
                            </span>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                    
                    <?php get_template_part('template-parts/events/event-author', null, array('event_id' => $event_id)); ?>
                </div>
                
                <!-- Featured Image -->
                <?php if (has_post_thumbnail()) : ?>
                <div class="vh360-event-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
                <?php endif; ?>
                
            </div>
            
            <div class="vh360-event-content-wrapper">
                
                <!-- Event Details Sidebar -->
                <aside class="vh360-event-sidebar">
                    
                    <!-- Date & Time -->
                    <div class="vh360-event-detail-card">
                        <h3 class="vh360-event-detail-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php esc_html_e('Date & Time', 'videohub360-theme'); ?>
                        </h3>
                        <p class="vh360-event-detail-content">
                            <?php echo esc_html(vh360_get_event_date_range($event_id)); ?>
                        </p>
                    </div>
                    
                    <!-- Location -->
                    <div class="vh360-event-detail-card">
                        <h3 class="vh360-event-detail-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php esc_html_e('Location', 'videohub360-theme'); ?>
                        </h3>
                        
                        <?php if ($location_type === 'online' || $location_type === 'both') : ?>
                            <div class="vh360-event-location-item">
                                <p class="vh360-event-location-type">🌐 <?php esc_html_e('Online Event', 'videohub360-theme'); ?></p>
                                <?php if (!empty($online_url)) : ?>
                                    <a href="<?php echo esc_url($online_url); ?>" target="_blank" rel="noopener" class="vh360-event-location-link">
                                        <?php esc_html_e('Join Meeting', 'videohub360-theme'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($location_type === 'physical' || $location_type === 'both') : ?>
                            <div class="vh360-event-location-item">
                                <p class="vh360-event-location-type">📍 <?php esc_html_e('Physical Location', 'videohub360-theme'); ?></p>
                                <?php if (!empty($venue_name)) : ?>
                                    <p class="vh360-event-venue-name"><?php echo esc_html($venue_name); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($venue_address)) : ?>
                                    <p class="vh360-event-venue-address">
                                        <?php echo esc_html($venue_address); ?><br>
                                        <?php if (!empty($venue_city)) echo esc_html($venue_city); ?>
                                        <?php if (!empty($venue_state)) echo ', ' . esc_html($venue_state); ?>
                                        <?php if (!empty($venue_zip)) echo ' ' . esc_html($venue_zip); ?><br>
                                        <?php if (!empty($venue_country)) echo esc_html($venue_country); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Cost -->
                    <div class="vh360-event-detail-card">
                        <h3 class="vh360-event-detail-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <?php esc_html_e('Cost', 'videohub360-theme'); ?>
                        </h3>
                        <p class="vh360-event-detail-content vh360-event-cost">
                            <?php echo esc_html(vh360_get_event_cost_display($event_id)); ?>
                        </p>
                    </div>
                    
                    <!-- Organizer -->
                    <?php if (!empty($organizer_name) || !empty($organizer_email)) : ?>
                    <div class="vh360-event-detail-card">
                        <h3 class="vh360-event-detail-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <?php esc_html_e('Organizer', 'videohub360-theme'); ?>
                        </h3>
                        <?php if (!empty($organizer_name)) : ?>
                            <p class="vh360-event-organizer-name"><?php echo esc_html($organizer_name); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($organizer_email)) : ?>
                            <p class="vh360-event-organizer-email">
                                <a href="mailto:<?php echo esc_attr($organizer_email); ?>">
                                    <?php echo esc_html($organizer_email); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($organizer_phone)) : ?>
                            <p class="vh360-event-organizer-phone">
                                <a href="tel:<?php echo esc_attr($organizer_phone); ?>">
                                    <?php echo esc_html($organizer_phone); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Actions -->
                    <div class="vh360-event-actions">
                        <a href="<?php echo esc_url(add_query_arg('event_id', $event_id, get_post_type_archive_link('vh360_event') . 'download-ics/')); ?>" 
                           class="vh360-event-action-btn vh360-event-add-calendar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <?php esc_html_e('Add to Calendar', 'videohub360-theme'); ?>
                        </a>
                        
                        <?php 
                        // RSVP functionality
                        $user_id = get_current_user_id();
                        $rsvps = get_post_meta($event_id, '_vh360_event_rsvps', true);
                        if (!is_array($rsvps)) {
                            $rsvps = array();
                        }
                        $user_rsvp_index = $user_id ? array_search($user_id, array_column($rsvps, 'user_id')) : false;
                        $is_rsvpd = $user_rsvp_index !== false;
                        $rsvp_count = count($rsvps);
                        ?>
                        
                        <button class="vh360-event-action-btn vh360-event-rsvp-btn <?php echo $is_rsvpd ? 'vh360-event-rsvpd' : ''; ?>" 
                                data-event-id="<?php echo esc_attr($event_id); ?>"
                                <?php if (!$user_id) : ?>disabled title="<?php esc_attr_e('Login to RSVP', 'videohub360-theme'); ?>"<?php endif; ?>>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <?php if ($is_rsvpd) : ?>
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                <?php else : ?>
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                <?php endif; ?>
                            </svg>
                            <span class="vh360-rsvp-text">
                                <?php echo $is_rsvpd ? esc_html__('RSVP\'d', 'videohub360-theme') : esc_html__('RSVP', 'videohub360-theme'); ?>
                            </span>
                            <span class="vh360-rsvp-count">(<?php echo esc_html($rsvp_count); ?>)</span>
                        </button>
                    </div>
                    
                </aside>
                
                <!-- Event Content -->
                <div class="vh360-event-main-content">
                    
                    <?php if (has_excerpt()) : ?>
                    <div class="vh360-event-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="vh360-event-description">
                        <?php the_content(); ?>
                    </div>
                    
                    <!-- Tags -->
                    <?php
                    $tags = get_the_terms($event_id, 'vh360_event_tag');
                    if ($tags && !is_wp_error($tags)) :
                    ?>
                    <div class="vh360-event-tags">
                        <h3 class="vh360-event-tags-title"><?php esc_html_e('Tags:', 'videohub360-theme'); ?></h3>
                        <div class="vh360-event-tags-list">
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="vh360-event-tag">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        </div>
        
    </article>
    
    <?php
endwhile;

get_footer();
