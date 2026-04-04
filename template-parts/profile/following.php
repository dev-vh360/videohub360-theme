<?php
/**
 * Profile Following Template Part
 *
 * Displays list of users this profile is following.
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

<main class="vh360-profile-following-section">
    
    <div class="vh360-profile-section-header">
        <h2 class="vh360-profile-section-title">
            <?php
            /* translators: %s: Number of users following */
            printf(esc_html__('Following %s', 'videohub360-theme'), number_format_i18n($total_following));
            ?>
        </h2>
    </div>

    <?php if (!empty($following_paged)) : ?>
        <!-- Following Grid -->
        <div class="vh360-user-grid">
            <?php foreach ($following_paged as $following_id) : ?>
                <?php
                get_template_part('template-parts/components/card-user', null, array(
                    'user_id' => $following_id,
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
        <div class="vh360-empty-state">
            <div class="vh360-empty-state-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <h2 class="vh360-empty-state-title"><?php esc_html_e('Not Following Anyone', 'videohub360-theme'); ?></h2>
            <p class="vh360-empty-state-message"><?php esc_html_e("This user isn't following anyone yet.", 'videohub360-theme'); ?></p>
        </div>
    <?php endif; ?>

</main>
