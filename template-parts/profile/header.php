<?php
/**
 * Profile Header Template Part
 *
 * Displays user avatar, cover image, display name, username, join date,
 * edit profile button, and social links.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get profile options
$profile_options = get_option('vh360_profile_options', array());
$profile_defaults = array(
    'enable_profiles' => true,
    'show_avatar' => true,
    'show_cover' => true,
    'show_social' => true,
    'show_stats' => true,
    'show_header_follow_button' => true,
);
$profile_options = wp_parse_args($profile_options, $profile_defaults);

// Check if profiles are enabled
if (!$profile_options['enable_profiles']) {
    return;
}

// Get the author being displayed
$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    return;
}

// Get user data
$avatar_url = vh360_get_user_avatar_url($author_id, 150);
$cover_image = vh360_get_user_cover_image($author_id);
$display_name = $author->display_name;
$username = $author->user_login;
$join_date = vh360_get_user_join_date($author_id, 'F Y');
$social_links = vh360_get_user_social_links($author_id);

// Check if current user can edit this profile
$can_edit = vh360_user_can_edit_profile($author_id);
?>

<div class="vh360-profile-header">
    <!-- Cover Image -->
    <?php if ($profile_options['show_cover']) : ?>
    <div class="vh360-profile-cover">
        <?php if ($cover_image) : ?>
            <img src="<?php echo esc_url($cover_image); ?>" alt="<?php echo esc_attr($display_name); ?> cover image">
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="vh360-profile-info">
        <div class="vh360-profile-main">
            <!-- Avatar -->
            <?php if ($profile_options['show_avatar']) : ?>
            <div class="vh360-profile-avatar">
                <?php if ($avatar_url) : ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                <?php else : ?>
                    <img src="<?php echo esc_url(get_avatar_url($author_id, array('size' => 150))); ?>" alt="<?php echo esc_attr($display_name); ?>">
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Profile Details -->
            <div class="vh360-profile-details">
                <h1 class="vh360-profile-name"><?php echo esc_html($display_name); ?></h1>
                <p class="vh360-profile-username">@<?php echo esc_html($username); ?></p>
                
                <?php if ($join_date) : ?>
                    <p class="vh360-profile-join-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <?php
                        /* translators: %s: Join date */
                        printf(esc_html__('Joined %s', 'videohub360-theme'), esc_html($join_date));
                        ?>
                    </p>
                <?php endif; ?>

                <?php
                // Check if this user is currently live
                $live_room_query = new WP_Query([
                    'post_type'      => 'videohub360',
                    'post_status'    => 'publish',
                    'author'         => $author_id,
                    'posts_per_page' => 1,
                    'meta_query'     => [
                        'relation' => 'AND',
                        [
                            'key'   => '_vh360_context',
                            'value' => 'live_room',
                        ],
                        [
                            'key'   => '_vh360_is_live',
                            'value' => 'yes',
                        ],
                    ],
                ]);

                if ($live_room_query->have_posts()) {
                    $live_room_query->the_post();
                    $room_url = get_permalink();
                    
                    // Safety: hide (and auto-clear) stale "LIVE NOW" states that were never properly ended.
                    $raw_live_start_time = get_post_meta(get_the_ID(), '_vh360_live_start_time', true);
                    $live_start_time = 0;
                    if (is_numeric($raw_live_start_time)) {
                        $live_start_time = (int) $raw_live_start_time;
                    } elseif (!empty($raw_live_start_time)) {
                        $ts = strtotime($raw_live_start_time);
                        if ($ts !== false) {
                            $live_start_time = $ts;
                        }
                    }
                    $max_live_age    = 12 * HOUR_IN_SECONDS; // adjust if you expect very long live sessions
                    $is_stale_live   = ($live_start_time > 0) && ($live_start_time < (time() - $max_live_age));
                    
                    // Extra safety: if the associated community post is marked ended, don't show LIVE NOW.
                    $went_live_post_id = get_post_meta(get_the_ID(), '_vh360_went_live_post_id', true);
                    if ($went_live_post_id) {
                        $community_live_status = get_post_meta($went_live_post_id, 'vh360_live_status', true);
                        if ($community_live_status === 'ended') {
                            $is_stale_live = true;
                        }
                    }
                    
                    if ($is_stale_live) {
                        // Stale LIVE flag detected — hide the badge, but do not mutate post meta here.
                        // (Ending state is handled by explicit end-stream actions.)
                    } else {
                        ?>
                        <div class="vh360-profile-live-indicator">
                            <span class="vh360-profile-live-badge">🔴 <?php esc_html_e('LIVE NOW', 'videohub360-theme'); ?></span>
                            <a href="<?php echo esc_url($room_url); ?>" class="vh360-profile-join-button">
                                <?php esc_html_e('Join Live', 'videohub360-theme'); ?>
                            </a>
                        </div>
                        <?php
                    }
                }
                wp_reset_postdata();
                ?>

                <!-- Edit Profile Button, Message Button, or Follow Button -->
                <div class="vh360-profile-actions">
                    <?php
                    $current_user_id = get_current_user_id();
                    
                    // Show Edit Profile button if user can edit this profile
                    if ($can_edit) :
                        $edit_url = function_exists( 'vh360_get_profile_edit_url' ) ? vh360_get_profile_edit_url( $author_id ) : home_url( '/dashboard/?tab=profile' );
                        ?>
                        <a href="<?php echo esc_url($edit_url); ?>" class="vh360-edit-profile-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            <?php esc_html_e('Edit Profile', 'videohub360-theme'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Show message button if viewing another user's profile and DM is enabled
                    if ($current_user_id && $current_user_id != $author_id && function_exists('vh360_can_send_message') && function_exists('vh360_get_dm_url') && vh360_can_send_message($current_user_id, $author_id)) :
                        $dm_url = vh360_get_dm_url($author_id);
                    ?>
                        <a href="<?php echo esc_url($dm_url); ?>" class="vh360-profile-message-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <?php esc_html_e('Message', 'videohub360-theme'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Show follow button if viewing another user's profile and setting is enabled
                    if ($current_user_id != $author_id && function_exists('vh360_follow_button')) :
                        // Check if follow button is enabled in settings
                        if (!empty($profile_options['show_header_follow_button'])) :
                            vh360_follow_button($author_id, 'vh360-profile-follow-btn');
                        endif;
                    endif;
                    ?>
                </div>

                <!-- Social Links (shown on mobile only) -->
                <?php if ($profile_options['show_social'] && (!empty($social_links) || $website)) : ?>
                    <div class="vh360-profile-links vh360-profile-links--mobile">

                        <?php foreach ($social_links as $platform => $url) : ?>
                            <?php if ($url) : ?>
                                <a href="<?php echo esc_url($url); ?>" class="vh360-profile-link" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html(ucfirst($platform)); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional inline styles for profile header layout */
.vh360-profile-main {
    background: var(--bg-color);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.vh360-profile-join-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-light);
    margin: 0.5rem 0;
}

.vh360-profile-actions {
    margin-top: 1rem;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .vh360-profile-main {
        padding: 1rem;
    }
    
    .vh360-profile-actions {
        flex-direction: column;
    }
    
    .vh360-profile-actions .vh360-edit-profile-btn,
    .vh360-profile-actions .vh360-follow-btn,
    .vh360-profile-actions .vh360-unfollow-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Hide social links in header on desktop (shown in sidebar) */
@media (min-width: 1024px) {
    .vh360-profile-links--mobile {
        display: none;
    }
}

/* Show social links in header on mobile (no sidebar) */
@media (max-width: 1023px) {
    .vh360-profile-links--mobile {
        display: flex;
    }
}
</style>