<?php
/**
 * Dashboard Events Tab
 *
 * Frontend event manager for creating, editing, and managing events.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure only logged-in users access this tab
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('You must be logged in to manage events.', 'videohub360-theme') . '</p>';
    return;
}

$current_user_id = get_current_user_id();


$vh360_is_licensed = ( function_exists('vh360_theme_is_license_valid') ? vh360_theme_is_license_valid() : ( function_exists('videohub360_license_is_valid') && videohub360_license_is_valid() ) );
$vh360_license_url = function_exists('vh360_theme_get_license_admin_url') ? vh360_theme_get_license_admin_url() : admin_url('admin.php?page=videohub360-license');
// Check if user can create events
$can_create_events = vh360_user_can_create_events();

// Get user's events
$args = array(
    'post_type'      => 'vh360_event',
    'author'         => $current_user_id,
    'post_status'    => array('publish', 'draft'),
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
);

$user_events = new WP_Query($args);
?>

<div class="vh360-dashboard-events">
    
    <!-- Header -->
    <div class="vh360-dashboard-section-header">
        <div class="vh360-dashboard-section-title-wrapper">
            <h2 class="vh360-dashboard-section-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <?php esc_html_e('Manage Events', 'videohub360-theme'); ?>
            </h2>
            <p class="vh360-dashboard-section-subtitle">
                <?php esc_html_e('Create and manage your events', 'videohub360-theme'); ?>
            </p>
        </div>
        
        <?php if ($can_create_events) : ?>
        <button class="vh360-dashboard-btn vh360-dashboard-btn-primary vh360-create-event-btn" <?php echo !$vh360_is_licensed ? 'vh360-locked' : ''; ?>" <?php echo !$vh360_is_licensed ? 'disabled="disabled" aria-disabled="true"' : ''; ?> title="<?php echo !$vh360_is_licensed ? esc_attr__('Activate your license to create new content.', 'videohub360-theme') : ''; ?>">>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <?php esc_html_e('Create Event', 'videohub360-theme'); ?>
        </button>
        <?php endif; ?>
    </div>
    
    <?php if (!$can_create_events) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-warning">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <?php esc_html_e('You do not have permission to create events.', 'videohub360-theme'); ?>
        </div>
    <?php endif; ?>
    
    <!-- Events List -->
    <div class="vh360-dashboard-events-list">
        <?php if ($user_events->have_posts()) : ?>
            
            <div class="vh360-dashboard-events-grid">
                <?php 
                while ($user_events->have_posts()) : 
                    $user_events->the_post();
                    $event_id = get_the_ID();
                    $start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
                    $location_type = get_post_meta($event_id, '_vh360_event_location_type', true);
                    $event_status = get_post_meta($event_id, '_vh360_event_status', true);
                    $is_upcoming = vh360_is_event_upcoming($event_id);
                    
                    // Get RSVP count
                    $rsvp_count = absint(get_post_meta($event_id, '_vh360_event_rsvp_count', true));
                    ?>
                    
                    <div class="vh360-dashboard-event-card" data-event-id="<?php echo esc_attr($event_id); ?>">
                        
                        <!-- Event Thumbnail -->
                        <div class="vh360-dashboard-event-thumbnail">
                            <?php if (has_post_thumbnail($event_id)) : ?>
                                <?php the_post_thumbnail('medium'); ?>
                            <?php else : ?>
                                <div class="vh360-dashboard-event-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status Badge -->
                            <?php echo vh360_get_event_status_badge($event_id); ?>
                        </div>
                        
                        <!-- Event Info -->
                        <div class="vh360-dashboard-event-info">
                            <h3 class="vh360-dashboard-event-title">
                                <a href="<?php echo esc_url(get_permalink($event_id)); ?>" target="_blank">
                                    <?php echo esc_html(get_the_title()); ?>
                                </a>
                            </h3>
                            
                            <div class="vh360-dashboard-event-meta">
                                <!-- Date -->
                                <div class="vh360-dashboard-event-meta-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span><?php echo esc_html(vh360_get_event_date_range($event_id)); ?></span>
                                </div>
                                
                                <!-- Location -->
                                <div class="vh360-dashboard-event-meta-item">
                                    <?php if ($location_type === 'online') : ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="2" y1="12" x2="22" y2="12"></line>
                                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                        </svg>
                                        <span><?php esc_html_e('Online', 'videohub360-theme'); ?></span>
                                    <?php else : ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span><?php echo esc_html(vh360_get_event_location($event_id)); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- RSVPs -->
                                <div class="vh360-dashboard-event-meta-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    <span><?php echo sprintf(esc_html__('%d RSVPs', 'videohub360-theme'), $rsvp_count); ?></span>
                                </div>
                            </div>
                            
                            <div class="vh360-dashboard-event-status-row">
                                <span class="vh360-dashboard-event-post-status <?php echo esc_attr('status-' . get_post_status()); ?>">
                                    <?php echo esc_html(get_post_status() === 'publish' ? __('Published', 'videohub360-theme') : __('Draft', 'videohub360-theme')); ?>
                                </span>
                                
                                <?php if ($is_upcoming) : ?>
                                    <span class="vh360-dashboard-event-upcoming-badge">
                                        <?php esc_html_e('Upcoming', 'videohub360-theme'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Event Actions -->
                        <div class="vh360-dashboard-event-actions">
                            <button class="vh360-dashboard-btn-icon vh360-edit-event-btn" 
                                    data-event-id="<?php echo esc_attr($event_id); ?>"
                                    title="<?php esc_attr_e('Edit Event', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            
                            <button class="vh360-dashboard-btn-icon vh360-view-rsvps-btn" 
                                    data-event-id="<?php echo esc_attr($event_id); ?>"
                                    title="<?php esc_attr_e('View RSVPs', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </button>
                            
                            <a href="<?php echo esc_url(get_permalink($event_id)); ?>" 
                               class="vh360-dashboard-btn-icon"
                               target="_blank"
                               title="<?php esc_attr_e('View Event', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            
                            <button class="vh360-dashboard-btn-icon vh360-dashboard-btn-danger vh360-delete-event-btn" 
                                    data-event-id="<?php echo esc_attr($event_id); ?>"
                                    title="<?php esc_attr_e('Delete Event', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                        
                    </div>
                    
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
            
        <?php else : ?>
            
            <!-- Empty State -->
            <div class="vh360-dashboard-empty-state">
                <div class="vh360-dashboard-empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <h3 class="vh360-dashboard-empty-title">
                    <?php esc_html_e('No Events Yet', 'videohub360-theme'); ?>
                </h3>
                <p class="vh360-dashboard-empty-text">
                    <?php esc_html_e('Create your first event to get started.', 'videohub360-theme'); ?>
                </p>
                <?php if ($can_create_events) : ?>
                <button class="vh360-dashboard-btn vh360-dashboard-btn-primary vh360-create-event-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <?php esc_html_e('Create Event', 'videohub360-theme'); ?>
                </button>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
    </div>
    
</div>

<!-- Event Editor Modal -->
<div id="vh360-event-editor-modal" class="vh360-modal-overlay" style="display: none;">
    <div class="vh360-modal vh360-event-editor-modal">
        <div class="vh360-modal-header">
            <h3 class="vh360-modal-title" id="vh360-event-modal-title">
                <?php esc_html_e('Create Event', 'videohub360-theme'); ?>
            </h3>
            <button class="vh360-modal-close" aria-label="<?php esc_attr_e('Close', 'videohub360-theme'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <div class="vh360-modal-content">
            <form id="vh360-event-form" class="vh360-event-form">
                <input type="hidden" id="vh360-event-id" name="event_id" value="">
                
                <!-- Basic Info -->
                <div class="vh360-form-section">
                    <h4 class="vh360-form-section-title"><?php esc_html_e('Basic Information', 'videohub360-theme'); ?></h4>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-event-title" class="vh360-form-label">
                            <?php esc_html_e('Event Title', 'videohub360-theme'); ?> <span class="vh360-required">*</span>
                        </label>
                        <input type="text" id="vh360-event-title" name="title" class="vh360-form-control" required>
                    </div>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-event-description" class="vh360-form-label">
                            <?php esc_html_e('Description', 'videohub360-theme'); ?>
                        </label>
                        <textarea id="vh360-event-description" name="content" class="vh360-form-control" rows="5"></textarea>
                    </div>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-event-excerpt" class="vh360-form-label">
                            <?php esc_html_e('Short Summary', 'videohub360-theme'); ?>
                        </label>
                        <textarea id="vh360-event-excerpt" name="excerpt" class="vh360-form-control" rows="2"></textarea>
                        <small class="vh360-form-help"><?php esc_html_e('Brief description shown in event listings', 'videohub360-theme'); ?></small>
                    </div>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-event-featured-image" class="vh360-form-label">
                            <?php esc_html_e('Featured Image', 'videohub360-theme'); ?>
                        </label>
                        <input type="file" id="vh360-event-featured-image" name="featured_image" class="vh360-file-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                        <input type="hidden" id="vh360-event-featured-image-id" name="featured_image_id" value="">
                        <button type="button" class="vh360-upload-button" id="vh360-event-upload-trigger">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <?php esc_html_e('Upload Image', 'videohub360-theme'); ?>
                        </button>
                        <div id="vh360-event-image-preview" class="vh360-image-preview" style="display: none;">
                            <img src="" alt="<?php esc_attr_e('Preview', 'videohub360-theme'); ?>" id="vh360-event-preview-img">
                            <button type="button" class="vh360-remove-image" id="vh360-event-remove-image" aria-label="<?php esc_attr_e('Remove image', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <small class="vh360-form-help">
                            <?php esc_html_e('Upload a featured image for your event. Supported formats: JPG, PNG, GIF, WebP (Max 5MB)', 'videohub360-theme'); ?>
                        </small>
                    </div>
                </div>
                
                <!-- Date & Time -->
                <div class="vh360-form-section">
                    <h4 class="vh360-form-section-title"><?php esc_html_e('Date & Time', 'videohub360-theme'); ?></h4>
                    
                    <div class="vh360-form-row">
                        <div class="vh360-form-group">
                            <label for="vh360-event-start-date" class="vh360-form-label">
                                <?php esc_html_e('Start Date', 'videohub360-theme'); ?> <span class="vh360-required">*</span>
                            </label>
                            <input type="date" id="vh360-event-start-date" name="start_date" class="vh360-form-control" required>
                        </div>
                        
                        <div class="vh360-form-group">
                            <label for="vh360-event-start-time" class="vh360-form-label">
                                <?php esc_html_e('Start Time', 'videohub360-theme'); ?>
                            </label>
                            <input type="time" id="vh360-event-start-time" name="start_time" class="vh360-form-control">
                        </div>
                    </div>
                    
                    <div class="vh360-form-row">
                        <div class="vh360-form-group">
                            <label for="vh360-event-end-date" class="vh360-form-label">
                                <?php esc_html_e('End Date', 'videohub360-theme'); ?>
                            </label>
                            <input type="date" id="vh360-event-end-date" name="end_date" class="vh360-form-control">
                        </div>
                        
                        <div class="vh360-form-group">
                            <label for="vh360-event-end-time" class="vh360-form-label">
                                <?php esc_html_e('End Time', 'videohub360-theme'); ?>
                            </label>
                            <input type="time" id="vh360-event-end-time" name="end_time" class="vh360-form-control">
                        </div>
                    </div>
                </div>
                
                <!-- Location -->
                <div class="vh360-form-section">
                    <h4 class="vh360-form-section-title"><?php esc_html_e('Location', 'videohub360-theme'); ?></h4>
                    
                    <div class="vh360-form-group">
                        <label class="vh360-form-label"><?php esc_html_e('Location Type', 'videohub360-theme'); ?></label>
                        <div class="vh360-radio-group">
                            <label class="vh360-radio-label" for="vh360-location-physical">
                                <input type="radio" id="vh360-location-physical" name="location_type" value="physical" checked>
                                <span><?php esc_html_e('Physical Location', 'videohub360-theme'); ?></span>
                            </label>
                            <label class="vh360-radio-label" for="vh360-location-online">
                                <input type="radio" id="vh360-location-online" name="location_type" value="online">
                                <span><?php esc_html_e('Online Event', 'videohub360-theme'); ?></span>
                            </label>
                            <label class="vh360-radio-label" for="vh360-location-both">
                                <input type="radio" id="vh360-location-both" name="location_type" value="both">
                                <span><?php esc_html_e('Hybrid', 'videohub360-theme'); ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <div id="vh360-physical-location" class="vh360-location-fields">
                        <div class="vh360-form-group">
                            <label for="vh360-event-venue-name" class="vh360-form-label">
                                <?php esc_html_e('Venue Name', 'videohub360-theme'); ?>
                            </label>
                            <input type="text" id="vh360-event-venue-name" name="venue_name" class="vh360-form-control">
                        </div>
                        
                        <div class="vh360-form-group">
                            <label for="vh360-event-venue-address" class="vh360-form-label">
                                <?php esc_html_e('Address', 'videohub360-theme'); ?>
                            </label>
                            <input type="text" id="vh360-event-venue-address" name="venue_address" class="vh360-form-control">
                        </div>
                        
                        <div class="vh360-form-row">
                            <div class="vh360-form-group">
                                <label for="vh360-event-venue-city" class="vh360-form-label">
                                    <?php esc_html_e('City', 'videohub360-theme'); ?>
                                </label>
                                <input type="text" id="vh360-event-venue-city" name="venue_city" class="vh360-form-control">
                            </div>
                            
                            <div class="vh360-form-group">
                                <label for="vh360-event-venue-state" class="vh360-form-label">
                                    <?php esc_html_e('State/Province', 'videohub360-theme'); ?>
                                </label>
                                <input type="text" id="vh360-event-venue-state" name="venue_state" class="vh360-form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div id="vh360-online-location" class="vh360-location-fields" style="display: none;">
                        <div class="vh360-form-group">
                            <label for="vh360-event-online-url" class="vh360-form-label">
                                <?php esc_html_e('Meeting URL', 'videohub360-theme'); ?>
                            </label>
                            <input type="url" id="vh360-event-online-url" name="online_url" class="vh360-form-control" placeholder="https://zoom.us/">
                        </div>
                    </div>
                </div>
                
                <!-- Registration & Cost -->
                <div class="vh360-form-section">
                    <h4 class="vh360-form-section-title"><?php esc_html_e('Registration & Cost', 'videohub360-theme'); ?></h4>
                    
                    <div class="vh360-form-group">
                        <label class="vh360-checkbox-label">
                            <input type="checkbox" name="registration_required" value="1">
                            <span><?php esc_html_e('Registration Required', 'videohub360-theme'); ?></span>
                        </label>
                    </div>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-event-cost-type" class="vh360-form-label">
                            <?php esc_html_e('Cost', 'videohub360-theme'); ?>
                        </label>
                        <select id="vh360-event-cost-type" name="cost_type" class="vh360-form-control">
                            <option value="free"><?php esc_html_e('Free', 'videohub360-theme'); ?></option>
                            <option value="paid"><?php esc_html_e('Paid', 'videohub360-theme'); ?></option>
                            <option value="donation"><?php esc_html_e('Donation', 'videohub360-theme'); ?></option>
                        </select>
                    </div>
                    
                    <div class="vh360-form-group" id="vh360-cost-amount-group" style="display: none;">
                        <label for="vh360-event-cost-amount" class="vh360-form-label">
                            <?php esc_html_e('Amount', 'videohub360-theme'); ?>
                        </label>
                        <input type="number" id="vh360-event-cost-amount" name="cost_amount" class="vh360-form-control" min="0" step="0.01">
                    </div>
                </div>
                
                <!-- Status -->
                <div class="vh360-form-section">
                    <h4 class="vh360-form-section-title"><?php esc_html_e('Event Status', 'videohub360-theme'); ?></h4>
                    
                    <div class="vh360-form-row">
                        <div class="vh360-form-group">
                            <label for="vh360-event-event-status" class="vh360-form-label">
                                <?php esc_html_e('Status', 'videohub360-theme'); ?>
                            </label>
                            <select id="vh360-event-event-status" name="event_status" class="vh360-form-control">
                                <option value="scheduled"><?php esc_html_e('Scheduled', 'videohub360-theme'); ?></option>
                                <option value="cancelled"><?php esc_html_e('Cancelled', 'videohub360-theme'); ?></option>
                                <option value="postponed"><?php esc_html_e('Postponed', 'videohub360-theme'); ?></option>
                                <option value="completed"><?php esc_html_e('Completed', 'videohub360-theme'); ?></option>
                            </select>
                        </div>
                        
                        <div class="vh360-form-group">
                            <label for="vh360-event-post-status" class="vh360-form-label">
                                <?php esc_html_e('Publish Status', 'videohub360-theme'); ?>
                            </label>
                            <select id="vh360-event-post-status" name="status" class="vh360-form-control">
                                <option value="draft"><?php esc_html_e('Draft', 'videohub360-theme'); ?></option>
                                <option value="publish"><?php esc_html_e('Published', 'videohub360-theme'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="vh360-form-actions">
                    <button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-modal-close">
                        <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
                    </button>
                    <button type="submit" class="vh360-dashboard-btn vh360-dashboard-btn-primary" id="vh360-event-submit-btn">
                        <span class="vh360-btn-text"><?php esc_html_e('Create Event', 'videohub360-theme'); ?></span>
                        <span class="vh360-btn-loading" style="display: none;">
                            <svg class="vh360-spinner" width="16" height="16" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"></path>
                            </svg>
                            <?php esc_html_e('Saving', 'videohub360-theme'); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- RSVP List Modal -->
<div id="vh360-rsvp-list-modal" class="vh360-modal-overlay" style="display: none;">
    <div class="vh360-modal">
        <div class="vh360-modal-header">
            <h3 class="vh360-modal-title"><?php esc_html_e('Event RSVPs', 'videohub360-theme'); ?></h3>
            <button class="vh360-modal-close" aria-label="<?php esc_attr_e('Close', 'videohub360-theme'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <div class="vh360-modal-content">
            <div id="vh360-rsvp-list-content">
                <!-- RSVP list will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>
