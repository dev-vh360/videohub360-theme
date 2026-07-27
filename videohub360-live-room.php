<?php
/**
 * Template for Videohub360 Community Live Room
 *
 * This template is used for Videohub360 posts that are:
 * - Marked as livestreams, and
 * - Have Usage Context set to "Community Live Room (live_room)".
 *
 * It reuses the VideoHub360 plugin's livestream and chat capabilities,
 * but wraps them in the Videohub360 Theme's community layout.
 *
 * @package Videohub360_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Live Room state changes from active to processing to replay-ready. Prevent a
// full-page cache from preserving an earlier offline state after those metadata
// transitions.
if ( ! defined( 'DONOTCACHEPAGE' ) ) {
    define( 'DONOTCACHEPAGE', true );
}
if ( ! headers_sent() ) {
    nocache_headers();
}

// Resolve the queried Live Room before loading the header. Header widgets and
// secondary loops must never be allowed to change which post supplies the room
// and replay metadata.
$post_id = absint( get_queried_object_id() );
$queried_live_room = $post_id ? get_post( $post_id ) : null;

// Ensure each renderer is available independently. The replay renderer must not
// depend on the livestream/chat renderers also being missing.
$renderer_files = array(
    'videohub360_render_chat_container'      => WP_PLUGIN_DIR . '/videohub360-core/includes/renderers/render-chat.php',
    'videohub360_render_livestream'          => WP_PLUGIN_DIR . '/videohub360-core/includes/renderers/render-livestream.php',
    'videohub360_get_studio_replay_state'    => WP_PLUGIN_DIR . '/videohub360-core/includes/renderers/render-replay.php',
);

foreach ( $renderer_files as $renderer_function => $renderer_path ) {
    if ( ! function_exists( $renderer_function ) && file_exists( $renderer_path ) ) {
        require_once $renderer_path;
    }
}

if ( ! function_exists( 'videohub360_render_livestream' ) || ! function_exists( 'videohub360_render_chat_container' ) ) {
    wp_die( esc_html__( 'VideoHub360 Core plugin is required for Live Room functionality. Please activate the VideoHub360 plugin.', 'videohub360-theme' ) );
}

// Ensure chat assets are enqueued for Live Room
add_action('wp_enqueue_scripts', function() {
    // The core plugin already enqueues assets and localizes vh360Data
    // for is_singular('videohub360') pages, which includes Live Rooms.
    // This hook is kept for any future Live Room-specific asset needs.
}, 20);

get_header();

// Header visibility and content from Customizer for Live Room
$vh360_show_header  = (bool) get_theme_mod('vh360_show_live_room_header', 1);
$vh360_header_title = get_theme_mod('vh360_live_room_header_title', __('Live Room', 'videohub360-theme'));
$vh360_header_desc  = get_theme_mod('vh360_live_room_header_description', __('Join live sessions and broadcasts from the community.', 'videohub360-theme'));

// Keep the global post aligned with the queried Live Room after the header has
// rendered, but continue using the immutable queried post ID resolved above.
global $post;
if ( $queried_live_room instanceof WP_Post ) {
    $post = $queried_live_room;
}

// Basic safety: use default template if this isn't a videohub360 post.
if ( ! $post_id || 'videohub360' !== get_post_type( $post_id ) ) {
    // Fallback to normal single template
    get_template_part('single');
    get_footer();
    return;
}

// Collect livestream meta in the same shape used by the plugin's single template
$livestream_fields = [
    'is_live' => get_post_meta($post_id, '_vh360_is_live', true) ?: 'no',
    'type' => get_post_meta($post_id, '_vh360_type', true) ?: 'embed',
    'embed_code' => get_post_meta($post_id, '_vh360_embed_code', true),
    'stream_url' => get_post_meta($post_id, '_vh360_stream_url', true),
    'api_url' => get_post_meta($post_id, '_vh360_api_url', true),
    'poster' => get_post_meta($post_id, '_vh360_poster', true),
    'viewer_count' => get_post_meta($post_id, '_vh360_viewer_count', true) ?: 'no',
    'live_badge' => get_post_meta($post_id, '_vh360_live_badge', true) ?: 'yes',
    'badge_text' => get_post_meta($post_id, '_vh360_badge_text', true) ?: 'LIVE',
    'badge_color' => get_post_meta($post_id, '_vh360_badge_color', true) ?: '#e53935',
    'offline_message' => get_post_meta($post_id, '_vh360_offline_message', true),
    'live_start_time' => get_post_meta($post_id, '_vh360_live_start_time', true),
    'stream_stopped' => get_post_meta($post_id, '_vh360_stream_stopped', true) ?: 'no',
    'chat_enabled' => get_post_meta($post_id, '_vh360_chat_enabled', true),
    'chat_placement' => get_post_meta($post_id, '_vh360_chat_placement', true),
    'agora_channel_name' => get_post_meta($post_id, '_vh360_agora_channel_name', true),
    'agora_mode' => get_post_meta($post_id, '_vh360_agora_mode', true) ?: 'interactive',
    'agora_everyone_is_host' => get_post_meta($post_id, '_vh360_agora_everyone_is_host', true) ?: 'no',
    'host_passcode' => get_post_meta($post_id, '_vh360_host_passcode', true),
];

// Check if this is an appointment Live Room
$is_appointment_room = !empty(get_post_meta($post_id, '_vh360_appointment_event_id', true));

if ( function_exists( 'videohub360_get_studio_replay_state' ) ) {
    $studio_replay_state = videohub360_get_studio_replay_state( $post_id );
} else {
    // Fail safely from the standardized post metadata rather than treating a
    // published replay as an ordinary offline room when the shared helper has
    // not been loaded for any reason.
    $studio_replay_state = array(
        'ready'        => 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_ready', true ) || 'yes' === get_post_meta( $post_id, '_vh360_live_room_has_replay', true ),
        'pending'      => 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_pending', true ),
        'failed'       => 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_failed', true ),
        'status'       => sanitize_key( get_post_meta( $post_id, '_vh360_studio_replay_status', true ) ),
        'provider'     => sanitize_key( get_post_meta( $post_id, '_vh360_studio_storage_provider', true ) ?: get_post_meta( $post_id, '_vh360_studio_provider', true ) ),
        'playback_url' => get_post_meta( $post_id, 'video_url', true ),
        'embed_html'   => get_post_meta( $post_id, 'videohub360_custom_html', true ),
        'poster_url'   => get_post_meta( $post_id, '_vh360_studio_poster_url', true ) ?: get_post_meta( $post_id, 'poster_url', true ),
    );
}

// Determine if livestream is effectively live
// For appointment rooms, always render the livestream UI when livestream mode is enabled
// (even after stream_stopped, so professional can restart the session)
if ($is_appointment_room) {
    $is_live = ($livestream_fields['is_live'] === 'yes');
} else {
    // For regular Live Rooms, only show UI when actually live and not stopped
    $is_live = ($livestream_fields['is_live'] === 'yes' && $livestream_fields['stream_stopped'] !== 'yes');
}

// Determine if chat should be enabled for this livestream
$global_chat_enabled = get_option('videohub360_chat_enabled', 1);
$chat_enabled = (bool) $global_chat_enabled;

if (!empty($livestream_fields['chat_enabled'])) {
    $chat_enabled = ($livestream_fields['chat_enabled'] === 'yes');
}

// Live Room ALWAYS uses popup chat placement, never inline or sidebar
$chat_placement = 'popup';

// User context for chat / Agora
$is_user_logged_in = is_user_logged_in();
$user_display_name = '';
$user_avatar = '';
$user_logout_url = '';
$user_login_url = vh360_get_login_page_url_with_redirect(get_permalink($post_id));

if ($is_user_logged_in) {
    $current_user = wp_get_current_user();
    $user_display_name = $current_user->display_name ?: $current_user->user_login;
    // Use full avatar HTML like the plugin template expects
    $user_avatar = get_avatar($current_user->ID, 24);
    $user_logout_url = vh360_get_logout_url(get_permalink($post_id));
}

// Define moderation capabilities for the current user (for chat UI)
$can_moderate = vh360_user_can_manage_live_room($post_id) || current_user_can('moderate_comments') || current_user_can('manage_options');

// Provide a local copy of the plugin's livestream renderer if needed


// Build Live Room layout using theme's community styles
?>
<div id="primary" class="content-area vh360-live-room-area">
    <main id="main" class="site-main vh360-live-room-main">

        <?php if ($vh360_show_header) : ?>
        <header class="vh360-activity-header vh360-live-room-header">
            <div class="vh360-container">
                <h1 class="vh360-activity-title">
                    <?php echo esc_html($vh360_header_title); ?>
                </h1>
                <?php if (!empty($vh360_header_desc)) : ?>
                <p class="vh360-activity-description">
                    <?php echo esc_html($vh360_header_desc); ?>
                </p>
                <?php endif; ?>
            </div>
        </header>
        <?php endif; ?>

        <div class="vh360-container">
            <!-- Live Room Stage - Full Width (No Sidebar) -->
            <section class="vh360-live-room-player">
                <?php if ($is_live) : ?>
                    <div class="videohub360-video-player" id="videohub360-livestream-player-root">
                        <?php 
                        // Show settings gear icon for hosts, hide for non-hosts
                        $hide_settings = !vh360_user_can_manage_live_room(get_the_ID());
                        echo videohub360_render_livestream($livestream_fields, $chat_enabled, $chat_placement, $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $hide_settings); 
                        ?>
                    </div>
                <?php elseif (!$is_appointment_room && !empty($studio_replay_state['ready'])) : ?>
                    <div class="vh360-studio-replay-wrapper">
                        <?php
                        if ( function_exists( 'videohub360_render_studio_replay' ) ) {
                            echo videohub360_render_studio_replay( $post_id );
                        } elseif ( ! empty( $studio_replay_state['embed_html'] ) ) {
                            echo wp_kses_post( $studio_replay_state['embed_html'] );
                        } elseif ( ! empty( $studio_replay_state['playback_url'] ) ) {
                            echo wp_video_shortcode(
                                array(
                                    'src'    => esc_url( $studio_replay_state['playback_url'] ),
                                    'poster' => esc_url( $studio_replay_state['poster_url'] ),
                                )
                            );
                        } else {
                            echo '<div class="vh360-offline-message">' . esc_html__( 'The replay is ready, but its playback source could not be loaded.', 'videohub360-theme' ) . '</div>';
                        }
                        ?>
                    </div>
                <?php elseif (!$is_appointment_room && !empty($studio_replay_state['pending'])) : ?>
                    <div class="vh360-offline-wrapper vh360-replay-processing-wrapper">
                        <?php echo function_exists('videohub360_render_studio_replay') ? videohub360_render_studio_replay($post_id) : esc_html__('Replay is processing. Please check back soon.', 'videohub360-theme'); ?>
                    </div>
                <?php elseif (!$is_appointment_room && !empty($studio_replay_state['failed'])) : ?>
                    <div class="vh360-offline-wrapper vh360-replay-failed-wrapper">
                        <div class="vh360-offline-message"><?php esc_html_e('Replay processing failed.', 'videohub360-theme'); ?></div>
                    </div>
                <?php else : ?>
                    <div class="vh360-offline-wrapper">
                        <?php if (!empty($livestream_fields['offline_message'])) : ?>
                            <div class="vh360-offline-message">
                                <?php echo wp_kses_post($livestream_fields['offline_message']); ?>
                            </div>
                        <?php else : ?>
                            <div class="vh360-offline-message">
                                <?php
                                if (function_exists('vh360_the_offline_message')) {
                                    vh360_the_offline_message(get_the_ID(), 'live_room_offline');
                                } else {
                                    // Fallback if plugin not loaded
                                    echo '<h3>' . esc_html__('This Live Room is not currently live.', 'videohub360-theme') . '</h3>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <header class="vh360-live-room-details-header">
                <div class="vh360-live-room-title-wrap">
                    <h1 class="vh360-live-room-title"><?php echo esc_html(get_the_title($post_id)); ?></h1>
                    <?php if ($is_live && $livestream_fields['live_badge'] === 'yes') : ?>
                        <span class="vh360-live-badge" style="background-color: <?php echo esc_attr($livestream_fields['badge_color']); ?>;">
                            <?php echo esc_html($livestream_fields['badge_text']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="vh360-live-room-meta">
                    <?php if ($is_live && $livestream_fields['viewer_count'] === 'yes') : ?>
                        <span class="vh360-live-viewers">
                            🔴 <span id="vh360-live-viewer-count"><?php echo esc_html__('Live', 'videohub360-theme'); ?></span>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($livestream_fields['live_start_time'])) : ?>
                        <span class="vh360-live-start-time">
                            <?php
                            $start_ts = strtotime($livestream_fields['live_start_time']);
                            if ($start_ts) {
                                printf(
                                    esc_html__('Started %s', 'videohub360-theme'),
                                    esc_html(human_time_diff($start_ts, current_time('timestamp')))
                                );
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($chat_enabled) : ?>
                        <!-- Chat button - always show when chat is enabled, even after stream ends -->
                        <button type="button" id="videohub360-open-chat-btn" class="videohub360-open-chat-btn">
                            💬 Chat
                        </button>
                    <?php endif; ?>
                </div>
            </header>

            <?php
            // Host info for Live Room (author of the current post)
            $host_id = isset($post->post_author) ? $post->post_author : get_the_author_meta('ID');
            $host_name = $host_id ? get_the_author_meta('display_name', $host_id) : '';
            $host_avatar = $host_id ? get_avatar($host_id, 48) : '';
            $host_profile_url = $host_id ? get_author_posts_url($host_id) : '';
            ?>

            <section class="vh360-live-room-host">
                <a href="<?php echo esc_url($host_profile_url); ?>" class="vh360-host-link">
                    <span class="vh360-host-avatar"><?php echo $host_avatar; ?></span>
                    <span class="vh360-host-name-row">
                        <span class="vh360-host-name"><?php echo esc_html($host_name); ?></span>
                        <span class="vh360-host-badge"><?php esc_html_e('Host', 'videohub360-theme'); ?></span>
                    </span>
                </a>
            </section>

              <section class="vh360-live-room-description">
                <?php
                while (have_posts()) :
                    the_post();
                    the_content();
                endwhile;
                ?>
            </section>

            <?php if ($chat_enabled) : ?>
                <!-- Popup chat container (rendered by core plugin) -->
                <!-- Always render when chat is enabled, even after stream ends, so users can revisit chat history -->
                <!-- Live Room always uses popup placement, never inline or sidebar -->
                <?php
                echo videohub360_render_chat_container(
                    'popup',
                    $is_user_logged_in,
                    $user_avatar,
                    $user_display_name,
                    $user_logout_url,
                    $can_moderate,
                    $livestream_fields
                );
                ?>
            <?php endif; ?>
        </div>
    </main>
</div>


<!-- Moderation Panel Modal -->
<?php if ($can_moderate): ?>
<div id="vh360-moderation-modal" class="vh360-moderation-modal" style="display: none;">
    <div class="vh360-moderation-modal-content">
        <div class="vh360-moderation-modal-header">
            <h3>Moderation Panel</h3>
            <button type="button" class="vh360-moderation-modal-close" id="vh360-moderation-modal-close">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                </svg>
            </button>
        </div>
        <div class="vh360-moderation-modal-body">
            <div class="vh360-moderation-loading" id="vh360-moderation-loading">
                <p>Loading moderation data...</p>
            </div>
            
            <div class="vh360-moderation-content" id="vh360-moderation-content" style="display: none;">
                <div class="vh360-moderation-section">
                    <h4>💬 Text Chat - Banned Users <span class="vh360-moderation-count" id="vh360-chat-banned-count">0</span></h4>
                    <div class="vh360-moderation-list" id="vh360-chat-banned-users-list">
                        <p class="vh360-no-items">No chat banned users</p>
                    </div>
                </div>
                
                <div class="vh360-moderation-section">
                    <h4>💬 Text Chat - Timed Out Users <span class="vh360-moderation-count" id="vh360-chat-timeout-count">0</span></h4>
                    <div class="vh360-moderation-list" id="vh360-chat-timeout-users-list">
                        <p class="vh360-no-items">No chat timed out users</p>
                    </div>
                </div>
                
                <div class="vh360-moderation-section">
                    <h4>🎥 Video Chat - Banned Users <span class="vh360-moderation-count" id="vh360-agora-banned-count">0</span></h4>
                    <div class="vh360-moderation-list" id="vh360-agora-banned-users-list">
                        <p class="vh360-no-items">No video banned users</p>
                    </div>
                </div>
                
                <div class="vh360-moderation-section">
                    <h4>🎥 Video Chat - Timed Out Users <span class="vh360-moderation-count" id="vh360-agora-timeout-count">0</span></h4>
                    <div class="vh360-moderation-list" id="vh360-agora-timeout-users-list">
                        <p class="vh360-no-items">No video timed out users</p>
                    </div>
                </div>
            </div>
            
            <div class="vh360-moderation-error" id="vh360-moderation-error" style="display: none;">
                <p>Failed to load moderation data. Please try again.</p>
                <button type="button" class="vh360-moderation-retry" id="vh360-moderation-retry">Retry</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<?php
get_footer();
