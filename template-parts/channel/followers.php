<?php
/**
 * Channel Followers Template Part
 *
 * Displays list of users who follow this channel.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the author being displayed
$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    return;
}

// Get followers list
$followers = function_exists('vh360_get_followers') ? vh360_get_followers($author_id) : array();

// Get pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$per_page = 24;
$total_followers = count($followers);
$total_pages = ceil($total_followers / $per_page);

// Paginate followers
$offset = ($paged - 1) * $per_page;
$followers_paged = array_slice($followers, $offset, $per_page);
?>

<div class="vh360-channel-followers-section">
    
    <div class="vh360-channel-section-header">
        <h2 class="vh360-channel-section-title">
            <?php
            /* translators: %s: Number of followers */
            printf(esc_html(_n('%s Follower', '%s Followers', $total_followers, 'videohub360-theme')), number_format_i18n($total_followers));
            ?>
        </h2>
    </div>

    <?php if (!empty($followers_paged)) : ?>
        <!-- Followers Grid -->
        <div class="vh360-users-grid">
            <?php foreach ($followers_paged as $follower_id) : 
                $follower = get_userdata($follower_id);
                if (!$follower) {
                    continue;
                }
                
                $follower_url = get_author_posts_url($follower_id);
                $follower_avatar = function_exists('vh360_get_user_avatar_url') ? vh360_get_user_avatar_url($follower_id, 80) : get_avatar_url($follower_id, array('size' => 80));
                $follower_stats = function_exists('vh360_get_user_stats') ? vh360_get_user_stats($follower_id) : array('followers' => 0, 'videos' => 0);
            ?>
                <article class="vh360-user-card">
                    <a href="<?php echo esc_url($follower_url); ?>" class="vh360-user-card-link">
                        <div class="vh360-user-card-content">
                            <!-- User Avatar -->
                            <div class="vh360-user-avatar">
                                <img src="<?php echo esc_url($follower_avatar); ?>" alt="<?php echo esc_attr($follower->display_name); ?>">
                            </div>

                            <!-- User Info -->
                            <div class="vh360-user-info">
                                <h3 class="vh360-user-name"><?php echo esc_html($follower->display_name); ?></h3>
                                <p class="vh360-user-username">@<?php echo esc_html($follower->user_login); ?></p>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Follow Button -->
                    <?php if (get_current_user_id() && get_current_user_id() != $follower_id && function_exists('vh360_follow_button')) : ?>
                        <div class="vh360-user-actions">
                            <?php vh360_follow_button($follower_id, 'vh360-user-follow-btn'); ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div class="vh360-channel-pagination">
                <?php
                echo paginate_links(array(
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $total_pages,
                    'prev_text' => '&laquo; ' . esc_html__('Previous', 'videohub360-theme'),
                    'next_text' => esc_html__('Next', 'videohub360-theme') . ' &raquo;',
                    'type' => 'list',
                    'end_size' => 2,
                    'mid_size' => 2,
                ));
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <!-- Empty State -->
        <div class="vh360-channel-empty-state">
            <div class="vh360-empty-icon">👥</div>
            <h3 class="vh360-empty-title"><?php esc_html_e('No followers yet', 'videohub360-theme'); ?></h3>
            <p class="vh360-empty-description">
                <?php esc_html_e('This channel doesn\'t have any followers yet.', 'videohub360-theme'); ?>
            </p>
        </div>
    <?php endif; ?>

</div>
