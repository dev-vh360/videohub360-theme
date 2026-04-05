<?php
/**
 * Profile Followers Template Part
 *
 * Displays list of users who follow this profile.
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
$paged = max(1, absint(get_query_var('paged')));
$per_page = 24;
$total_followers = count($followers);
$total_pages = ceil($total_followers / $per_page);

// Paginate followers
$offset = ($paged - 1) * $per_page;
$followers_paged = array_slice($followers, $offset, $per_page);
?>

<main class="vh360-profile-followers-section">
    
    <div class="vh360-profile-section-header">
        <h2 class="vh360-profile-section-title">
            <?php
            /* translators: %s: Number of followers */
            printf(esc_html(_n('%s Follower', '%s Followers', $total_followers, 'videohub360-theme')), number_format_i18n($total_followers));
            ?>
        </h2>
    </div>

    <?php if (!empty($followers_paged)) : ?>
        <!-- Followers Grid -->
        <div class="vh360-user-grid">
            <?php foreach ($followers_paged as $follower_id) : ?>
                <?php
                get_template_part('template-parts/components/card-user', null, array(
                    'user_id' => $follower_id,
                    'show_avatar' => true,
                    'show_bio' => true,
                    'show_follow_button' => true,
                    'avatar_size' => 64,
                ));
                ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div class="vh360-profile-pagination">
                <?php
                // Preserve tab parameter in pagination
                $base_url = add_query_arg('tab', 'followers', get_author_posts_url($author_id));
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%', $base_url),
                    'format' => '',
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
        <div class="vh360-empty-state">
            <div class="vh360-empty-state-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <h2 class="vh360-empty-state-title"><?php esc_html_e('No Followers Yet', 'videohub360-theme'); ?></h2>
            <p class="vh360-empty-state-message"><?php esc_html_e("This user doesn't have any followers yet.", 'videohub360-theme'); ?></p>
        </div>
    <?php endif; ?>

</main>
