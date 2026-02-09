<?php
/**
 * Group Card Component
 *
 * Reusable group card component for displaying group information
 * with cover image, member count, and join button.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get args with defaults
$args = wp_parse_args($args, array(
    'group_id' => get_the_ID(),
    'show_cover' => true,
    'show_members' => true,
    'show_join_button' => true,
));

$group_id = $args['group_id'];

if (!$group_id) {
    return;
}

// Get group data
$group_url = get_permalink($group_id);
$group_name = get_the_title($group_id);
$group_description = get_the_excerpt($group_id);
$group_cover = get_the_post_thumbnail_url($group_id, 'vh360-group-cover');
$member_count = get_post_meta($group_id, '_vh360_member_count', true);
$member_count = $member_count ? absint($member_count) : 0;
$is_private = get_post_meta($group_id, '_vh360_group_private', true);
$is_featured = get_post_meta($group_id, '_vh360_group_featured', true);

// Check if user is a member
$is_member = false;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $user_groups = get_user_meta($user_id, '_vh360_joined_groups', true);
    if (is_array($user_groups)) {
        $is_member = in_array($group_id, $user_groups);
    }
}
?>

<article class="vh360-group-card" data-group-id="<?php echo esc_attr($group_id); ?>">
    <a href="<?php echo esc_url($group_url); ?>" class="vh360-group-card-link">
        <?php if ($args['show_cover']) : ?>
            <div class="vh360-group-cover">
                <?php if ($group_cover) : ?>
                    <img 
                        src="<?php echo esc_url($group_cover); ?>" 
                        alt="<?php echo esc_attr($group_name); ?>"
                        loading="lazy"
                    >
                <?php endif; ?>
                
                <?php if ($is_private) : ?>
                    <span class="vh360-group-badge private">
                        <?php esc_html_e('Private', 'videohub360-theme'); ?>
                    </span>
                <?php elseif ($is_featured) : ?>
                    <span class="vh360-group-badge featured">
                        <?php esc_html_e('Featured', 'videohub360-theme'); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="vh360-group-card-body">
            <h3 class="vh360-group-name"><?php echo esc_html($group_name); ?></h3>
            
            <?php if ($group_description) : ?>
                <p class="vh360-group-description"><?php echo esc_html($group_description); ?></p>
            <?php endif; ?>
            
            <?php if ($args['show_members']) : ?>
                <div class="vh360-group-meta">
                    <div class="vh360-group-meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="vh360-group-meta-icon">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span class="vh360-group-members-count">
                            <?php echo esc_html(vh360_format_number($member_count)); ?> 
                            <?php echo esc_html(_n('member', 'members', $member_count, 'videohub360-theme')); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </a>
    
    <?php if ($args['show_join_button'] && is_user_logged_in()) : ?>
        <div class="vh360-group-card-footer">
            <?php if ($is_member) : ?>
                <button 
                    class="vh360-group-leave-btn" 
                    data-group-id="<?php echo esc_attr($group_id); ?>"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('vh360_group_action')); ?>"
                >
                    <?php esc_html_e('Leave', 'videohub360-theme'); ?>
                </button>
            <?php else : ?>
                <button 
                    class="vh360-group-join-btn" 
                    data-group-id="<?php echo esc_attr($group_id); ?>"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('vh360_group_action')); ?>"
                >
                    <?php esc_html_e('Join', 'videohub360-theme'); ?>
                </button>
            <?php endif; ?>
        </div>
    <?php elseif ($args['show_join_button'] && !is_user_logged_in()) : ?>
        <div class="vh360-group-card-footer">
            <a href="<?php echo esc_url(vh360_get_login_page_url_with_redirect(get_permalink($group_id))); ?>" class="vh360-group-join-btn">
                <?php esc_html_e('Log in to Join', 'videohub360-theme'); ?>
            </a>
        </div>
    <?php endif; ?>
</article>

<style>
/* Group Card Styles - See groups.css for full styles */
/* This is a minimal inline style to ensure the component works standalone */
.vh360-group-card {
    background: var(--bg-color, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: var(--border-radius, 8px);
    overflow: hidden;
    transition: var(--transition, all 0.3s ease);
    display: flex;
    flex-direction: column;
}

.vh360-group-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-4px);
}

.vh360-group-card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.vh360-group-cover {
    position: relative;
    width: 100%;
    height: 180px;
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    overflow: hidden;
}

.vh360-group-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition, all 0.3s ease);
}

.vh360-group-card:hover .vh360-group-cover img {
    transform: scale(1.05);
}

.vh360-group-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.375rem 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    color: #2563eb;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: var(--border-radius, 8px);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vh360-group-badge.private {
    background: rgba(239, 68, 68, 0.95);
    color: #ffffff;
}

.vh360-group-badge.featured {
    background: rgba(245, 158, 11, 0.95);
    color: #ffffff;
}

.vh360-group-card-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.vh360-group-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.75rem;
    color: var(--text-color, #1f2937);
    line-height: 1.3;
}

.vh360-group-description {
    font-size: 0.875rem;
    color: var(--text-light, #6b7280);
    margin: 0 0 1rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.vh360-group-meta {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
    font-size: 0.875rem;
    color: var(--text-light, #6b7280);
}

.vh360-group-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vh360-group-card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
    background: var(--bg-light, #f9fafb);
}

.vh360-group-join-btn,
.vh360-group-leave-btn {
    display: block;
    width: 100%;
    padding: 0.75rem 1.5rem;
    background: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: var(--border-radius, 8px);
    font-size: 0.875rem;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: var(--transition, all 0.3s ease);
    text-decoration: none;
}

.vh360-group-join-btn:hover {
    background: #1e40af;
    color: #ffffff;
}

.vh360-group-leave-btn {
    background: var(--bg-color, #ffffff);
    color: var(--text-color, #1f2937);
    border: 1px solid var(--border-color, #e5e7eb);
}

.vh360-group-leave-btn:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #ef4444;
}
</style>
