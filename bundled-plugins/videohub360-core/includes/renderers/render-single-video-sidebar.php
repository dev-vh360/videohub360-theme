<?php
/**
 * Single video sidebar renderer.
 *
 * @package VideoHub360
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('videohub360_render_single_video_sidebar')) {
    function videohub360_render_single_video_sidebar($post_id, $chat_enabled, $chat_placement, $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_moderate, $livestream_fields) {
        ob_start();
        ?>
        <aside class="videohub360-sidebar">
            <?php if ($chat_enabled && $chat_placement === 'sidebar'): ?>
                <div class="videohub360-sidebar-chat">
                    <?php echo videohub360_render_chat_container('inline', $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_moderate, $livestream_fields); ?>
                </div>
            <?php endif; ?>
            <h2><?php echo esc_html(videohub360_get_sidebar_title($post_id)); ?></h2>
            <ul>
            <?php
            // Use global helper function for sidebar query
            $videohub360_query = videohub360_build_sidebar_query($post_id);

            if ($videohub360_query->have_posts()) :
                while ($videohub360_query->have_posts()) : $videohub360_query->the_post();
                    $sidebar_views = get_post_meta(get_the_ID(), '_videohub360_post_views_count', true);
                    $sidebar_views = $sidebar_views ? $sidebar_views : 0;
                    $permalink = get_permalink();
                    $title = get_the_title();
                    $is_live = get_post_meta(get_the_ID(), '_vh360_is_live', true);
                    $stream_stopped = get_post_meta(get_the_ID(), '_vh360_stream_stopped', true);
                    $live_badge = get_post_meta(get_the_ID(), '_vh360_live_badge', true);
                    $badge_text = get_post_meta(get_the_ID(), '_vh360_badge_text', true) ?: __('LIVE', 'videohub360');
                    $badge_color = get_post_meta(get_the_ID(), '_vh360_badge_color', true) ?: '#e53935';
                    $show_live_badge = ($is_live === 'yes' && $stream_stopped !== 'yes' && $live_badge !== 'no');
                    ?>
                    <li>
                        <a href="<?php echo esc_url($permalink); ?>" class="videohub360-sidebar-thumb-link">
                            <?php
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('medium', array(
                                    'class' => 'videohub360-sidebar-thumbnail',
                                    'alt' => esc_attr($title),
                                ));
                            } else {
                                ?>
                                <div class="videohub360-sidebar-thumbnail"></div>
                                <?php
                            }
                            if ($show_live_badge):
                                ?>
                                <span class="videohub360-live-badge videohub360-live-badge-sidebar" data-badge-color="<?php echo esc_attr($badge_color); ?>">
                                    <?php echo esc_html($badge_text); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="videohub360-sidebar-info">
                            <a href="<?php echo esc_url($permalink); ?>" class="videohub360-sidebar-title"><?php echo esc_html($title); ?></a>
                            <?php
                            // videohub360_render_author_badge() handles all escaping internally
                            echo videohub360_render_author_badge(get_the_ID(), array(
                                'variant' => 'name_only',
                                'link' => true,
                            ));
                            ?>
                            <div class="videohub360-sidebar-meta">
                                <?php echo esc_html(get_the_date()); ?><br>
                                <?php
                                printf(
                                    /* translators: %s: compact view count. */
                                    esc_html__('%s views', 'videohub360'),
                                    esc_html(videohub360_compact_views($sidebar_views))
                                );
                                ?>
                            </div>
                        </div>
                    </li>
                    <?php
                endwhile;
                wp_reset_postdata();
            else:
                ?>
                <li><?php echo esc_html__('No other VideoHub360 videos found.', 'videohub360'); ?></li>
            <?php endif; ?>
            </ul>
        </aside>
        <?php
        return ob_get_clean();
    }
}
