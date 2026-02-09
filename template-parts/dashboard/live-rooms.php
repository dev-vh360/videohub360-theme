<?php
/**
 * Dashboard Live Rooms Tab
 *
 * Displays the list of previously created Live Rooms for the user.
 * The form to create a Live Room has been moved to the "Go Live" tab.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure only logged-in users access this tab
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('You must be logged in to view Live Rooms.', 'videohub360-theme') . '</p>';
    return;
}

$current_user_id = get_current_user_id();

// Query Live Rooms for this user
$live_rooms_query = new WP_Query(array(
    'post_type'      => 'videohub360',
    'author'         => $current_user_id,
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => array(
        array(
            'key'   => '_vh360_context',
            'value' => 'live_room',
        ),
    ),
));

?>
<div class="vh360-dashboard-section vh360-dashboard-live-rooms">
    <header class="vh360-dashboard-section-header">
        <h2 class="vh360-dashboard-section-title"><?php esc_html_e('Live Rooms', 'videohub360-theme'); ?></h2>
        <p class="vh360-dashboard-section-subtitle">
            <?php esc_html_e('View and manage your previously created Live Rooms.', 'videohub360-theme'); ?>
        </p>
    </header>

    <div class="vh360-dashboard-card vh360-live-room-list-card">
        <h3 class="vh360-dashboard-card-title"><?php esc_html_e('Your Live Rooms', 'videohub360-theme'); ?></h3>

        <?php if ($live_rooms_query->have_posts()) : ?>
            <div class="vh360-live-room-grid">
                <?php while ($live_rooms_query->have_posts()) : $live_rooms_query->the_post(); ?>
                    <?php
                    $room_id        = get_the_ID();
                    $is_live        = get_post_meta($room_id, '_vh360_is_live', true) === 'yes';
                    $stream_stopped = get_post_meta($room_id, '_vh360_stream_stopped', true) === 'yes';

                    if ($stream_stopped) {
                        $status_label = esc_html__('Ended', 'videohub360-theme');
                        $status_class = 'ended';
                    } elseif ($is_live) {
                        $status_label = esc_html__('Live', 'videohub360-theme');
                        $status_class = 'live';
                    } else {
                        $status_label = esc_html__('Offline', 'videohub360-theme');
                        $status_class = 'offline';
                    }
                    ?>
                    <div class="vh360-live-room-card">
                        <div class="vh360-live-room-card-header">
                            <span class="vh360-live-room-status vh360-live-room-status-<?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </div>
                        <div class="vh360-live-room-card-body">
                            <h4 class="vh360-live-room-card-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h4>
                            <p class="vh360-live-room-card-date">
                                <?php echo esc_html(get_the_date()); ?>
                            </p>
                        </div>
                        <div class="vh360-live-room-card-footer">
                            <a href="<?php the_permalink(); ?>" class="vh360-dashboard-btn vh360-dashboard-btn-secondary">
                                <?php esc_html_e('Open Live Room', 'videohub360-theme'); ?>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <div class="vh360-dashboard-empty">
                <div class="vh360-dashboard-empty-icon">🔴</div>
                <p class="vh360-dashboard-empty-title">
                    <?php esc_html_e('No Live Rooms yet', 'videohub360-theme'); ?>
                </p>
                <p class="vh360-dashboard-empty-text">
                    <?php esc_html_e('Use the "Go Live" button in the sidebar to create your first Live Room.', 'videohub360-theme'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div><!-- .vh360-dashboard-live-rooms -->
