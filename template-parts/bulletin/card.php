<?php
/**
 * Bulletin Card Component
 *
 * Reusable bulletin card for displaying in lists.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get bulletin ID from args or global post
$bulletin_id = isset($args['bulletin_id']) ? $args['bulletin_id'] : get_the_ID();
$show_actions = isset($args['show_actions']) ? $args['show_actions'] : true;
$compact = isset($args['compact']) ? $args['compact'] : false;

if (!$bulletin_id) {
    return;
}

$priority = vh360_get_bulletin_priority($bulletin_id);
$dismissible = get_post_meta($bulletin_id, '_vh360_bulletin_dismissible', true);
$expiry_date = get_post_meta($bulletin_id, '_vh360_bulletin_expiry_date', true);
$user_id = get_current_user_id();
$is_read = vh360_is_bulletin_read($bulletin_id, $user_id);
$is_expired = vh360_is_bulletin_expired($bulletin_id);

// Don't show expired bulletins
if ($is_expired) {
    return;
}

$priority_colors = array(
    'normal' => '#3b82f6',
    'important' => '#f59e0b',
    'urgent' => '#ef4444'
);

$priority_labels = array(
    'normal' => __('Normal', 'videohub360-theme'),
    'important' => __('Important', 'videohub360-theme'),
    'urgent' => __('Urgent', 'videohub360-theme')
);

$card_classes = array('vh360-bulletin-card');
if (!$is_read) {
    $card_classes[] = 'vh360-bulletin-unread';
}
if ($compact) {
    $card_classes[] = 'vh360-bulletin-compact';
}
$card_classes[] = 'vh360-bulletin-priority-' . $priority;

?>

<div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" 
     data-bulletin-id="<?php echo esc_attr($bulletin_id); ?>" 
     data-priority="<?php echo esc_attr($priority); ?>">
    
    <!-- Priority Indicator Bar -->
    <div class="vh360-bulletin-priority-bar" style="background-color: <?php echo esc_attr($priority_colors[$priority]); ?>;"></div>
    
    <!-- Bulletin Content -->
    <div class="vh360-bulletin-content">
        
        <!-- Header -->
        <div class="vh360-bulletin-header">
            <div class="vh360-bulletin-meta">
                <span class="vh360-bulletin-priority-badge" 
                      style="background-color: <?php echo esc_attr($priority_colors[$priority]); ?>;">
                    <?php echo esc_html($priority_labels[$priority]); ?>
                </span>
                <?php if (!$is_read) : ?>
                    <span class="vh360-bulletin-unread-indicator">
                        <svg width="8" height="8" viewBox="0 0 8 8" fill="currentColor">
                            <circle cx="4" cy="4" r="4"/>
                        </svg>
                        <?php esc_html_e('New', 'videohub360-theme'); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if ($show_actions && $dismissible) : ?>
                <button class="vh360-bulletin-dismiss" 
                        title="<?php esc_attr_e('Dismiss', 'videohub360-theme'); ?>"
                        aria-label="<?php esc_attr_e('Dismiss bulletin', 'videohub360-theme'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Title -->
        <h3 class="vh360-bulletin-title">
            <a href="<?php echo esc_url(get_permalink($bulletin_id)); ?>">
                <?php echo esc_html(get_the_title($bulletin_id)); ?>
            </a>
        </h3>
        
        <!-- Excerpt -->
        <?php if (!$compact) : ?>
            <div class="vh360-bulletin-excerpt">
                <?php 
                $excerpt = get_the_excerpt($bulletin_id);
                if ($excerpt) {
                    echo wp_kses_post(wpautop($excerpt));
                } else {
                    echo wp_kses_post(wp_trim_words(get_post_field('post_content', $bulletin_id), 20));
                }
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="vh360-bulletin-footer">
            <div class="vh360-bulletin-date">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <?php echo esc_html(human_time_diff(get_post_time('U', false, $bulletin_id), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'videohub360-theme'); ?>
            </div>
            
            <?php if ($expiry_date) : ?>
                <div class="vh360-bulletin-expiry">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?php 
                    $current_time = current_time('timestamp');
                    if ($expiry_date > $current_time) {
                        /* translators: %s: Time until expiry */
                        printf(esc_html__('Expires in %s', 'videohub360-theme'), 
                               esc_html(human_time_diff($current_time, $expiry_date)));
                    } else {
                        esc_html_e('Expired', 'videohub360-theme');
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($show_actions) : ?>
                <div class="vh360-bulletin-actions">
                    <a href="<?php echo esc_url(get_permalink($bulletin_id)); ?>" 
                       class="vh360-bulletin-view-link">
                        <?php esc_html_e('View Details', 'videohub360-theme'); ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                    <?php 
                    // Check if user can edit this bulletin using standard WordPress capability
                    if (current_user_can('edit_post', $bulletin_id)) : 
                        // Get admin edit URL
                        $edit_url = admin_url(sprintf('post.php?post=%d&action=edit', $bulletin_id));
                    ?>
                        <a href="<?php echo esc_url($edit_url); ?>" 
                           class="vh360-bulletin-edit-link">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            <?php esc_html_e('Edit', 'videohub360-theme'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
    
</div>
