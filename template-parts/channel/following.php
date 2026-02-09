<?php
/**
 * Channel Following Template Part
 *
 * Displays list of users this channel is following.
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

// Get following list
$following = function_exists('vh360_get_following_user_ids') ? vh360_get_following_user_ids($author_id) : array();

// Get pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$per_page = 24;
$total_following = count($following);
$total_pages = ceil($total_following / $per_page);

// Paginate following
$offset = ($paged - 1) * $per_page;
$following_paged = array_slice($following, $offset, $per_page);
?>

<div class="vh360-channel-following-section">
    
    <div class="vh360-channel-section-header">
        <h2 class="vh360-channel-section-title">
            <?php
            /* translators: %s: Number of users following */
            printf(esc_html__('Following %s', 'videohub360-theme'), number_format_i18n($total_following));
            ?>
        </h2>
    </div>

    <?php if (!empty($following_paged)) : ?>
        <!-- Following Grid -->
        <div class="vh360-users-grid">
            <?php foreach ($following_paged as $following_id) : 
                $following_user = get_userdata($following_id);
                if (!$following_user) {
                    continue;
                }
                
                $following_url = get_author_posts_url($following_id);
                $following_avatar = function_exists('vh360_get_user_avatar_url') ? vh360_get_user_avatar_url($following_id, 80) : get_avatar_url($following_id, array('size' => 80));
                $following_stats = function_exists('vh360_get_user_stats') ? vh360_get_user_stats($following_id) : array('followers' => 0, 'videos' => 0);
            ?>
                <article class="vh360-user-card">
                    <a href="<?php echo esc_url($following_url); ?>" class="vh360-user-card-link">
                        <div class="vh360-user-card-content">
                            <!-- User Avatar -->
                            <div class="vh360-user-avatar">
                                <img src="<?php echo esc_url($following_avatar); ?>" alt="<?php echo esc_attr($following_user->display_name); ?>">
                            </div>

                            <!-- User Info -->
                            <div class="vh360-user-info">
                                <h3 class="vh360-user-name"><?php echo esc_html($following_user->display_name); ?></h3>
                                <p class="vh360-user-username">@<?php echo esc_html($following_user->user_login); ?></p>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Follow Button -->
                    <?php if (get_current_user_id() && get_current_user_id() != $following_id && function_exists('vh360_follow_button')) : ?>
                        <div class="vh360-user-actions">
                            <?php vh360_follow_button($following_id, 'vh360-user-follow-btn'); ?>
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
            <h3 class="vh360-empty-title"><?php esc_html_e('Not following anyone yet', 'videohub360-theme'); ?></h3>
            <p class="vh360-empty-description">
                <?php esc_html_e('This channel isn\'t following anyone yet.', 'videohub360-theme'); ?>
            </p>
        </div>
    <?php endif; ?>

</div>
