<?php
/**
 * Dashboard Bulletins Tab
 *
 * Frontend bulletin manager for creating, editing, and managing bulletins.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure only logged-in users access this tab
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('You must be logged in to manage bulletins.', 'videohub360-theme') . '</p>';
    return;
}

$current_user_id = get_current_user_id();


$vh360_is_licensed = ( function_exists('vh360_theme_is_license_valid') ? vh360_theme_is_license_valid() : ( function_exists('videohub360_license_is_valid') && videohub360_license_is_valid() ) );
$vh360_license_url = function_exists('vh360_theme_get_license_admin_url') ? vh360_theme_get_license_admin_url() : admin_url('admin.php?page=videohub360-license');
// Check if user can create bulletins
$can_create_bulletins = vh360_user_can_create_bulletins();

// Get user's bulletins with pagination
$args = array(
    'post_type'      => 'vh360_bulletin',
    'author'         => $current_user_id,
    'post_status'    => array('publish', 'draft'),
    'posts_per_page' => 50, // Limit to prevent performance issues
    'orderby'        => 'date',
    'order'          => 'DESC',
);

$user_bulletins = new WP_Query($args);
?>

<div class="vh360-dashboard-bulletins">
    
    <!-- Header -->
    <div class="vh360-dashboard-section-header">
        <div class="vh360-dashboard-section-title-wrapper">
            <h2 class="vh360-dashboard-section-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <?php esc_html_e('Manage Bulletins', 'videohub360-theme'); ?>
            </h2>
            <p class="vh360-dashboard-section-subtitle">
                <?php esc_html_e('Create and manage your bulletins and announcements', 'videohub360-theme'); ?>
            </p>
        </div>
        
        <?php if ($can_create_bulletins) : ?>
        <button class="vh360-dashboard-btn vh360-dashboard-btn-primary vh360-create-bulletin-btn" <?php echo !$vh360_is_licensed ? 'vh360-locked' : ''; ?>" <?php echo !$vh360_is_licensed ? 'disabled="disabled" aria-disabled="true"' : ''; ?> title="<?php echo !$vh360_is_licensed ? esc_attr__('Activate your license to create new content.', 'videohub360-theme') : ''; ?>">>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <?php esc_html_e('Create Bulletin', 'videohub360-theme'); ?>
        </button>
        <?php endif; ?>
    </div>
    
    <?php if (!$can_create_bulletins) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-warning">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <?php esc_html_e('You do not have permission to create bulletins.', 'videohub360-theme'); ?>
        </div>
    <?php endif; ?>
    
    <!-- Bulletins List -->
    <div class="vh360-dashboard-bulletins-list">
        <?php if ($user_bulletins->have_posts()) : ?>
            
            <div class="vh360-dashboard-bulletins-grid">
                <?php 
                while ($user_bulletins->have_posts()) : 
                    $user_bulletins->the_post();
                    $bulletin_id = get_the_ID();
                    
                    // Get all meta data at once to avoid N+1 queries
                    $meta = get_post_meta($bulletin_id);
                    $priority = isset($meta['_vh360_bulletin_priority'][0]) ? $meta['_vh360_bulletin_priority'][0] : 'normal';
                    $type = isset($meta['_vh360_bulletin_display_type'][0]) ? $meta['_vh360_bulletin_display_type'][0] : 'info';
                    $expiry_date = isset($meta['_vh360_bulletin_expiry_date'][0]) ? $meta['_vh360_bulletin_expiry_date'][0] : '';
                    
                    // Priority labels and colors
                    $priority_labels = array(
                        'normal' => __('Normal', 'videohub360-theme'),
                        'important' => __('Important', 'videohub360-theme'),
                        'urgent' => __('Urgent', 'videohub360-theme')
                    );
                    
                    $type_labels = array(
                        'announcement' => __('Announcement', 'videohub360-theme'),
                        'alert' => __('Alert', 'videohub360-theme'),
                        'update' => __('Update', 'videohub360-theme'),
                        'info' => __('Info', 'videohub360-theme')
                    );
                    ?>
                    
                    <div class="vh360-dashboard-bulletin-card" data-bulletin-id="<?php echo esc_attr($bulletin_id); ?>">
                        
                        <!-- Bulletin Thumbnail -->
                        <div class="vh360-dashboard-bulletin-thumbnail">
                            <?php if (has_post_thumbnail($bulletin_id)) : ?>
                                <?php echo get_the_post_thumbnail($bulletin_id, 'medium', array('class' => 'vh360-bulletin-thumbnail-img')); ?>
                            <?php else : ?>
                                <div class="vh360-dashboard-bulletin-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Priority Badge -->
                            <span class="vh360-bulletin-priority-badge vh360-priority-<?php echo esc_attr($priority ?: 'normal'); ?>">
                                <?php echo esc_html($priority_labels[$priority] ?? $priority_labels['normal']); ?>
                            </span>
                        </div>
                        
                        <!-- Bulletin Info -->
                        <div class="vh360-dashboard-bulletin-info">
                            <h3 class="vh360-dashboard-bulletin-title">
                                <?php the_title(); ?>
                            </h3>
                            
                            <div class="vh360-dashboard-bulletin-meta">
                                <span class="vh360-bulletin-type">
                                    <?php echo esc_html($type_labels[$type] ?? $type_labels['info']); ?>
                                </span>
                                <span class="vh360-bulletin-status">
                                    <?php 
                                    if (get_post_status() === 'publish') {
                                        echo '<span class="vh360-status-published">' . esc_html__('Published', 'videohub360-theme') . '</span>';
                                    } else {
                                        echo '<span class="vh360-status-draft">' . esc_html__('Draft', 'videohub360-theme') . '</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($expiry_date) : ?>
                                <p class="vh360-dashboard-bulletin-expiry">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <?php 
                                    $expiry_ts = is_numeric($expiry_date) ? (int) $expiry_date : strtotime($expiry_date);
                                    if ($expiry_ts) {
                                        printf(
                                            /* translators: %s: expiry date */
                                            esc_html__('Expires: %s', 'videohub360-theme'),
                                            esc_html(date_i18n(get_option('date_format'), $expiry_ts))
                                        );
                                    }
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="vh360-dashboard-bulletin-date">
                                <?php 
                                printf(
                                    /* translators: %s: date */
                                    esc_html__('Created: %s', 'videohub360-theme'),
                                    esc_html(get_the_date())
                                ); 
                                ?>
                            </p>
                        </div>
                        
                        <!-- Actions -->
                        <div class="vh360-dashboard-bulletin-actions">
                            <button class="vh360-dashboard-btn-icon vh360-edit-bulletin-btn" 
                                    data-bulletin-id="<?php echo esc_attr($bulletin_id); ?>"
                                    title="<?php esc_attr_e('Edit Bulletin', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            
                            <a href="<?php echo esc_url(get_permalink($bulletin_id)); ?>" 
                               class="vh360-dashboard-btn-icon"
                               target="_blank"
                               title="<?php esc_attr_e('View Bulletin', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            
                            <button class="vh360-dashboard-btn-icon vh360-dashboard-btn-danger vh360-delete-bulletin-btn" 
                                    data-bulletin-id="<?php echo esc_attr($bulletin_id); ?>"
                                    title="<?php esc_attr_e('Delete Bulletin', 'videohub360-theme'); ?>">
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
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <h3 class="vh360-dashboard-empty-title">
                    <?php esc_html_e('No Bulletins Yet', 'videohub360-theme'); ?>
                </h3>
                <p class="vh360-dashboard-empty-text">
                    <?php esc_html_e('Create your first bulletin to get started.', 'videohub360-theme'); ?>
                </p>
                <?php if ($can_create_bulletins) : ?>
                <button class="vh360-dashboard-btn vh360-dashboard-btn-primary vh360-create-bulletin-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <?php esc_html_e('Create Bulletin', 'videohub360-theme'); ?>
                </button>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
    </div>
    
</div>

<!-- Bulletin Editor Modal -->
<div id="vh360-bulletin-editor-modal" class="vh360-modal-overlay" style="display: none;">
    <div class="vh360-modal vh360-bulletin-editor-modal">
        <div class="vh360-modal-header">
            <h3 class="vh360-modal-title" id="vh360-bulletin-modal-title">
                <?php esc_html_e('Create Bulletin', 'videohub360-theme'); ?>
            </h3>
            <button class="vh360-modal-close" aria-label="<?php esc_attr_e('Close', 'videohub360-theme'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <div class="vh360-modal-content">
            <form id="vh360-bulletin-form" class="vh360-bulletin-form">
                <input type="hidden" id="vh360-bulletin-id" name="bulletin_id" value="">
                
                <!-- Basic Info -->
                <div class="vh360-form-section">
                    <h4 class="vh360-form-section-title"><?php esc_html_e('Basic Information', 'videohub360-theme'); ?></h4>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-bulletin-title" class="vh360-form-label">
                            <?php esc_html_e('Bulletin Title', 'videohub360-theme'); ?> <span class="vh360-required">*</span>
                        </label>
                        <input type="text" id="vh360-bulletin-title" name="title" class="vh360-form-control" required>
                    </div>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-bulletin-content" class="vh360-form-label">
                            <?php esc_html_e('Content', 'videohub360-theme'); ?>
                        </label>
                        <textarea id="vh360-bulletin-content" name="content" class="vh360-form-control" rows="5"></textarea>
                    </div>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-bulletin-excerpt" class="vh360-form-label">
                            <?php esc_html_e('Short Summary', 'videohub360-theme'); ?>
                        </label>
                        <textarea id="vh360-bulletin-excerpt" name="excerpt" class="vh360-form-control" rows="2"></textarea>
                        <small class="vh360-form-help"><?php esc_html_e('Brief description shown in bulletin listings', 'videohub360-theme'); ?></small>
                    </div>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-bulletin-featured-image" class="vh360-form-label">
                            <?php esc_html_e('Featured Image', 'videohub360-theme'); ?>
                        </label>
                        <input type="file" id="vh360-bulletin-featured-image" name="featured_image" class="vh360-file-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                        <input type="hidden" id="vh360-bulletin-featured-image-id" name="featured_image_id" value="">
                        <button type="button" class="vh360-upload-button" id="vh360-bulletin-upload-trigger">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <?php esc_html_e('Upload Image', 'videohub360-theme'); ?>
                        </button>
                        <div id="vh360-bulletin-image-preview" class="vh360-image-preview" style="display: none;">
                            <img src="" alt="<?php esc_attr_e('Preview', 'videohub360-theme'); ?>" id="vh360-bulletin-preview-img">
                            <button type="button" class="vh360-remove-image" id="vh360-bulletin-remove-image" aria-label="<?php esc_attr_e('Remove image', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Bulletin Settings -->
                <div class="vh360-form-section">
                    <h4 class="vh360-form-section-title"><?php esc_html_e('Bulletin Settings', 'videohub360-theme'); ?></h4>
                    
                    <div class="vh360-form-row">
                        <div class="vh360-form-group">
                            <label for="vh360-bulletin-priority" class="vh360-form-label">
                                <?php esc_html_e('Priority', 'videohub360-theme'); ?>
                            </label>
                            <select id="vh360-bulletin-priority" name="priority" class="vh360-form-control">
                                <option value="normal"><?php esc_html_e('Normal', 'videohub360-theme'); ?></option>
                                <option value="important"><?php esc_html_e('Important', 'videohub360-theme'); ?></option>
                                <option value="urgent"><?php esc_html_e('Urgent', 'videohub360-theme'); ?></option>
                            </select>
                        </div>
                        
                        <div class="vh360-form-group">
                            <label for="vh360-bulletin-type" class="vh360-form-label">
                                <?php esc_html_e('Category', 'videohub360-theme'); ?>
                            </label>
                            <select id="vh360-bulletin-type" name="type" class="vh360-form-control">
                                <option value="announcement"><?php esc_html_e('Announcement', 'videohub360-theme'); ?></option>
                                <option value="alert"><?php esc_html_e('Alert', 'videohub360-theme'); ?></option>
                                <option value="update"><?php esc_html_e('Update', 'videohub360-theme'); ?></option>
                                <option value="info"><?php esc_html_e('Info', 'videohub360-theme'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="vh360-form-row">
                        <div class="vh360-form-group">
                            <label for="vh360-bulletin-audience" class="vh360-form-label">
                                <?php esc_html_e('Audience', 'videohub360-theme'); ?>
                            </label>
                            <select id="vh360-bulletin-audience" name="audience" class="vh360-form-control">
                                <option value="site_wide"><?php esc_html_e('Site-wide', 'videohub360-theme'); ?></option>
                                <option value="role"><?php esc_html_e('Role', 'videohub360-theme'); ?></option>
                                <option value="user"><?php esc_html_e('Specific User', 'videohub360-theme'); ?></option>
                            </select>
                        </div>
                        
                        <div class="vh360-form-group" id="vh360-bulletin-target-wrapper" style="display: none;">
                            <label for="vh360-bulletin-target" class="vh360-form-label">
                                <?php esc_html_e('Target', 'videohub360-theme'); ?>
                            </label>
                            <input type="text" id="vh360-bulletin-target" name="target" class="vh360-form-control" placeholder="<?php esc_attr_e('Enter role slug or user ID', 'videohub360-theme'); ?>">
                            <small class="vh360-form-help" id="vh360-bulletin-target-help"><?php esc_html_e('Enter role slug (e.g., subscriber) or user ID', 'videohub360-theme'); ?></small>
                        </div>
                    </div>
                    
                    <?php if (current_user_can('vh360_manage_bulletin_banner')) : ?>
                    <div class="vh360-form-group">
                        <label class="vh360-form-checkbox">
                            <input type="checkbox" id="vh360-bulletin-show-banner" name="show_banner" value="1">
                            <span><?php esc_html_e('Show as site-wide banner (urgent + site-wide only)', 'videohub360-theme'); ?></span>
                        </label>
                        <small class="vh360-form-help"><?php esc_html_e('This displays a prominent banner to all users. Only available for urgent, site-wide bulletins.', 'videohub360-theme'); ?></small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="vh360-form-group">
                        <label for="vh360-bulletin-expiry-date" class="vh360-form-label">
                            <?php esc_html_e('Expiry Date', 'videohub360-theme'); ?>
                        </label>
                        <input type="date" id="vh360-bulletin-expiry-date" name="expiry_date" class="vh360-form-control">
                        <small class="vh360-form-help"><?php esc_html_e('Optional. Bulletin will be hidden after this date.', 'videohub360-theme'); ?></small>
                    </div>
                    
                    <div class="vh360-form-group">
                        <label class="vh360-form-checkbox">
                            <input type="checkbox" id="vh360-bulletin-dismissible" name="dismissible" value="1">
                            <span><?php esc_html_e('Allow users to dismiss this bulletin', 'videohub360-theme'); ?></span>
                        </label>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="vh360-form-actions">
                    <button type="button" class="vh360-dashboard-btn vh360-modal-close">
                        <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
                    </button>
                    <button type="submit" class="vh360-dashboard-btn vh360-dashboard-btn-primary" id="vh360-bulletin-submit-btn">
                        <span class="vh360-btn-text"><?php esc_html_e('Create Bulletin', 'videohub360-theme'); ?></span>
                        <span class="vh360-btn-loading" style="display: none;">
                            <svg class="vh360-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
