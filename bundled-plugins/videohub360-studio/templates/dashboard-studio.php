<?php
/**
 * Studio dashboard tab template.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$default_label     = isset( $quality_presets[ $default_preset ]['label'] ) ? $quality_presets[ $default_preset ]['label'] : $default_preset;
$is_admin          = current_user_can( 'manage_options' );
$display_job_title = static function( $job ) {
    if ( ! empty( $job['display_title'] ) ) {
        return $job['display_title'];
    }
    foreach ( array( 'replay_video_id', 'live_video_id' ) as $post_id_key ) {
        if ( empty( $job[ $post_id_key ] ) ) {
            continue;
        }
        $title = get_the_title( absint( $job[ $post_id_key ] ) );
        if ( $title ) {
            return $title;
        }
    }
    return __( 'Studio replay', 'videohub360-studio' );
};
$format_replay_created_at = static function( $value ) {
    if ( empty( $value ) ) {
        return '—';
    }
    $timestamp = mysql2date( 'U', $value, false );
    if ( ! $timestamp ) {
        return $value;
    }
    $today     = wp_date( 'Y-m-d', current_time( 'timestamp' ) );
    $yesterday = wp_date( 'Y-m-d', current_time( 'timestamp' ) - DAY_IN_SECONDS );
    $date      = wp_date( 'Y-m-d', $timestamp );
    if ( $today === $date ) {
        return sprintf( __( 'Today %s', 'videohub360-studio' ), wp_date( get_option( 'time_format' ), $timestamp ) );
    }
    if ( $yesterday === $date ) {
        return sprintf( __( 'Yesterday %s', 'videohub360-studio' ), wp_date( get_option( 'time_format' ), $timestamp ) );
    }
    return wp_date( 'M j, Y', $timestamp );
};
$public_replay_url = static function( $job ) {
    if ( ! empty( $job['replay_url'] ) ) {
        return $job['replay_url'];
    }
    if ( ! empty( $job['permalink'] ) ) {
        return $job['permalink'];
    }
    if ( ! empty( $job['replay_video_id'] ) ) {
        return get_permalink( absint( $job['replay_video_id'] ) );
    }
    return '';
};
$friendly_job_status = static function( $job ) {
    if ( ! empty( $job['error_message'] ) || ( isset( $job['status'] ) && 'failed' === $job['status'] ) ) {
        return __( 'Needs attention', 'videohub360-studio' );
    }
    if ( ! empty( $job['replay_video_id'] ) || ! empty( $job['replay_url'] ) || ( isset( $job['status'] ) && 'ready' === $job['status'] ) ) {
        return __( 'Published', 'videohub360-studio' );
    }
    $labels = array(
        'created'   => __( 'Created', 'videohub360-studio' ),
        'recording' => __( 'Recording', 'videohub360-studio' ),
        'uploading' => __( 'Uploading', 'videohub360-studio' ),
        'processing'=> __( 'Preparing', 'videohub360-studio' ),
        'ready'     => __( 'Ready', 'videohub360-studio' ),
        'cancelled' => __( 'Cancelled', 'videohub360-studio' ),
    );
    return isset( $labels[ $job['status'] ] ) ? $labels[ $job['status'] ] : ucfirst( str_replace( '_', ' ', $job['status'] ) );
};
?>
<section class="vh360-studio-dashboard" data-vh360-studio-dashboard>
    <header class="vh360-studio-topbar">
        <div>
            <p class="vh360-studio-kicker"><?php esc_html_e( 'Production console', 'videohub360-studio' ); ?></p>
            <h2><?php echo esc_html( function_exists( 'vh360_studio_get_display_name' ) ? vh360_studio_get_display_name() : __( 'Studio', 'videohub360-studio' ) ); ?></h2>
        </div>
        <div class="vh360-studio-screen-reader-status" aria-live="polite" data-studio-status>
            <?php esc_html_e( 'Checking browser support…', 'videohub360-studio' ); ?>
        </div>
        <p class="vh360-studio-on-air-notice" data-on-air-notice hidden>
            <?php esc_html_e( 'For best results, keep this Studio tab open and visible while broadcasting or recording.', 'videohub360-studio' ); ?>
        </p>
        <div class="vh360-studio-topbar-links" aria-live="polite">
            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-studio-fullscreen>
                <?php esc_html_e( 'Fullscreen Studio', 'videohub360-studio' ); ?>
            </button>
            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-open-studio-window>
                <?php esc_html_e( 'Open Studio Window', 'videohub360-studio' ); ?>
            </button>

            <div class="vh360-studio-viewer-actions" data-viewer-link-wrap hidden>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-open-viewer-link disabled>
                    <?php esc_html_e( 'Open Viewer', 'videohub360-studio' ); ?>
                </button>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-copy-viewer-link>
                    <?php esc_html_e( 'Copy Link', 'videohub360-studio' ); ?>
                </button>
                <span class="vh360-studio-copy-feedback" data-copy-viewer-feedback hidden></span>
            </div>
        </div>
    </header>

    <div class="vh360-studio-workbench">
        <div class="vh360-studio-monitors">
            <section class="vh360-studio-monitor vh360-studio-monitor--preview" aria-labelledby="vh360-studio-preview-title">
                <div class="vh360-studio-monitor-header">
                    <h3 id="vh360-studio-preview-title"><?php esc_html_e( 'Preview', 'videohub360-studio' ); ?></h3>
                    <button
                        type="button"
                        class="vh360-studio-monitor-action"
                        data-toggle-media-controls
                        aria-expanded="false"
                        aria-controls="vh360-studio-selected-media-controls"
                        hidden
                    >
                        <?php esc_html_e( 'Media Controls', 'videohub360-studio' ); ?>
                    </button>
                </div>
                <div class="vh360-studio-monitor-screen vh360-studio-preview-stage">
                    <video class="vh360-studio-preview-video vh360-studio-preview-video--camera" data-camera-preview playsinline muted aria-label="<?php esc_attr_e( 'Local camera preview', 'videohub360-studio' ); ?>"></video>
                    <video class="vh360-studio-preview-video vh360-studio-preview-video--screen" data-screen-preview playsinline muted aria-label="<?php esc_attr_e( 'Screen-share preview', 'videohub360-studio' ); ?>"></video>
                    <div class="vh360-studio-preview-media" data-media-preview aria-label="<?php esc_attr_e( 'Media source preview', 'videohub360-studio' ); ?>"></div>
                    <span class="vh360-studio-monitor-empty" data-preview-empty><?php esc_html_e( 'Choose a Scene to stage it in Preview.', 'videohub360-studio' ); ?></span>
                </div>
                <div class="vh360-studio-media-playback" data-media-playback-controls hidden>
                    <button type="button" class="vh360-studio-icon-button" data-media-play-pause aria-label="<?php esc_attr_e( 'Play media', 'videohub360-studio' ); ?>">
                        <span data-media-play-pause-label><?php esc_html_e( 'Play', 'videohub360-studio' ); ?></span>
                    </button>

                    <button type="button" class="vh360-studio-icon-button" data-media-restart aria-label="<?php esc_attr_e( 'Restart media', 'videohub360-studio' ); ?>">
                        <?php esc_html_e( 'Restart', 'videohub360-studio' ); ?>
                    </button>

                    <label class="vh360-studio-media-loop">
                        <input type="checkbox" data-media-loop checked>
                        <?php esc_html_e( 'Loop', 'videohub360-studio' ); ?>
                    </label>

                    <input type="range" min="0" max="1000" value="0" step="1" data-media-seek aria-label="<?php esc_attr_e( 'Media timeline', 'videohub360-studio' ); ?>">

                    <span class="vh360-studio-media-time" data-media-time>00:00 / 00:00</span>
                </div>
                <div
                    id="vh360-studio-selected-media-controls"
                    class="vh360-studio-selected-media-controls"
                    data-selected-media-controls
                    hidden
                >
                    <h4><?php esc_html_e( 'Selected Media', 'videohub360-studio' ); ?></h4>
                    <p class="vh360-studio-selected-media-name" data-selected-media-name></p>

                    <label>
                        <?php esc_html_e( 'Fit mode', 'videohub360-studio' ); ?>
                        <select data-media-fit-mode>
                            <option value="fit"><?php esc_html_e( 'Fit to canvas', 'videohub360-studio' ); ?></option>
                            <option value="fill"><?php esc_html_e( 'Fill / crop', 'videohub360-studio' ); ?></option>
                            <option value="stretch"><?php esc_html_e( 'Stretch', 'videohub360-studio' ); ?></option>
                            <option value="original"><?php esc_html_e( 'Original size', 'videohub360-studio' ); ?></option>
                            <option value="custom"><?php esc_html_e( 'Custom', 'videohub360-studio' ); ?></option>
                        </select>
                    </label>

                    <label>
                        <?php esc_html_e( 'Scale', 'videohub360-studio' ); ?>
                        <input type="range" min="10" max="300" step="1" value="100" data-media-scale>
                        <span data-media-scale-value>100%</span>
                    </label>

                    <label>
                        <?php esc_html_e( 'Position X', 'videohub360-studio' ); ?>
                        <input type="range" min="-100" max="100" step="1" value="0" data-media-position-x>
                        <span data-media-position-x-value>0</span>
                    </label>

                    <label>
                        <?php esc_html_e( 'Position Y', 'videohub360-studio' ); ?>
                        <input type="range" min="-100" max="100" step="1" value="0" data-media-position-y>
                        <span data-media-position-y-value>0</span>
                    </label>

                    <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-media-reset-transform>
                        <?php esc_html_e( 'Reset media transform', 'videohub360-studio' ); ?>
                    </button>

                    <p class="vh360-studio-help" data-program-resolution-details></p>
                </div>
            </section>

            <section class="vh360-studio-transition-panel" aria-labelledby="vh360-studio-transition-title">
                <h3 id="vh360-studio-transition-title"><?php esc_html_e( 'Transition', 'videohub360-studio' ); ?></h3>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-transition-cut><?php esc_html_e( 'Cut', 'videohub360-studio' ); ?></button>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-transition-fade><?php esc_html_e( 'Fade', 'videohub360-studio' ); ?></button>
                <label class="vh360-studio-transition-duration"><?php esc_html_e( 'Duration', 'videohub360-studio' ); ?><input type="number" min="0" max="2000" step="50" value="300" data-transition-duration></label>
                <p><?php esc_html_e( 'Stage a source in Preview, then send it to Program.', 'videohub360-studio' ); ?></p>
            </section>

            <section class="vh360-studio-monitor vh360-studio-monitor--program" aria-labelledby="vh360-studio-program-title">
                <div class="vh360-studio-monitor-header">
                    <h3 id="vh360-studio-program-title"><?php esc_html_e( 'Program', 'videohub360-studio' ); ?></h3>
                    <span class="vh360-studio-pill"><?php esc_html_e( 'Program output', 'videohub360-studio' ); ?></span>
                </div>
                <div class="vh360-studio-monitor-screen">
                    <canvas class="vh360-studio-program-canvas" data-program-canvas aria-label="<?php esc_attr_e( 'Program output preview', 'videohub360-studio' ); ?>"></canvas>
                    <div data-agora-local-preview class="vh360-studio-agora-preview"></div>
                    <span class="vh360-studio-monitor-empty" data-program-empty><?php esc_html_e( 'Choose a Preview source, then use Cut or Fade to send it to Program.', 'videohub360-studio' ); ?></span>
                </div>
                <div class="vh360-studio-program-live-controls" aria-label="<?php esc_attr_e( 'Live Program controls', 'videohub360-studio' ); ?>">
                    <span class="vh360-studio-program-live-status" data-studio-program-live-status><?php esc_html_e( 'Not live', 'videohub360-studio' ); ?></span>
                    <span class="vh360-studio-program-diagnostics" data-program-diagnostics><?php esc_html_e( 'Program active', 'videohub360-studio' ); ?></span>
                    <div class="vh360-studio-program-live-actions">
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-studio-toggle-mic disabled><?php esc_html_e( 'Mute', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-studio-toggle-video disabled><?php esc_html_e( 'Video Off', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--danger" data-program-end-live disabled><?php esc_html_e( 'End Live', 'videohub360-studio' ); ?></button>
                    </div>
                </div>
            </section>
        </div>

        <div class="vh360-studio-dock-grid">
            <section class="vh360-studio-dock" aria-labelledby="vh360-studio-scenes-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-scenes-title"><?php esc_html_e( 'Scenes', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body">
                    <ul class="vh360-studio-scene-list" data-scene-list>
                        <li><button type="button" data-scene-source="camera"><?php esc_html_e( 'Camera Only', 'videohub360-studio' ); ?></button></li>
                        <li><button type="button" data-scene-source="screen"><?php esc_html_e( 'Screen Share', 'videohub360-studio' ); ?></button></li>
                    </ul>
                    <div class="vh360-studio-scene-controls" aria-label="<?php esc_attr_e( 'Scene controls', 'videohub360-studio' ); ?>">
                        <div class="vh360-studio-scene-add-menu-wrap">
                            <button
                                type="button"
                                class="vh360-studio-scene-control-button"
                                data-toggle-media-source-menu
                                aria-label="<?php esc_attr_e( 'Add media source', 'videohub360-studio' ); ?>"
                                aria-haspopup="true"
                                aria-expanded="false"
                            >
                                <span aria-hidden="true">+</span>
                            </button>

                            <div class="vh360-studio-scene-add-menu" data-media-source-menu hidden>
                                <button type="button" data-open-local-media-source>
                                    <span><?php esc_html_e( 'Local Media', 'videohub360-studio' ); ?></span>
                                    <small><?php esc_html_e( 'Current session only', 'videohub360-studio' ); ?></small>
                                </button>

                                <button type="button" data-open-upload-media-source>
                                    <span><?php esc_html_e( 'Upload to Studio', 'videohub360-studio' ); ?></span>
                                    <small><?php esc_html_e( 'Saved until deleted', 'videohub360-studio' ); ?></small>
                                </button>
                            </div>
                        </div>

                        <button type="button" class="vh360-studio-scene-control-button" data-delete-selected-media-scene aria-label="<?php esc_attr_e( 'Delete selected media scene', 'videohub360-studio' ); ?>" disabled>
                            <span aria-hidden="true">−</span>
                        </button>
                    </div>
                    <p class="vh360-studio-help"><?php esc_html_e( 'Scene buttons stage the matching source in Preview.', 'videohub360-studio' ); ?></p>
                </div>
            </section>

            <section class="vh360-studio-dock" aria-labelledby="vh360-studio-sources-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-sources-title"><?php esc_html_e( 'Sources', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body vh360-studio-control-stack">
                    <div class="vh360-studio-source-device-header">
                        <span><?php esc_html_e( 'Input devices', 'videohub360-studio' ); ?></span>
                        <button type="button" class="vh360-studio-button vh360-studio-button--ghost vh360-studio-refresh-devices" data-refresh-devices><?php esc_html_e( 'Refresh Devices', 'videohub360-studio' ); ?></button>
                    </div>
                    <label for="vh360-studio-camera-select"><?php esc_html_e( 'Camera', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-camera-select" data-camera-select disabled>
                        <option value=""><?php esc_html_e( 'Grant camera access to list devices', 'videohub360-studio' ); ?></option>
                    </select>
                    <label for="vh360-studio-mic-select"><?php esc_html_e( 'Microphone', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-mic-select" data-mic-select disabled>
                        <option value=""><?php esc_html_e( 'Grant microphone access to list devices', 'videohub360-studio' ); ?></option>
                    </select>
                    <p class="vh360-studio-help" data-device-status><?php esc_html_e( 'Refresh devices after plugging in a USB camera or granting browser permission.', 'videohub360-studio' ); ?></p>
                    <div class="vh360-studio-device-actions">
                        <button type="button" class="vh360-studio-button vh360-studio-button--ghost" data-test-camera><?php esc_html_e( 'Test Camera', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--ghost" data-test-microphone><?php esc_html_e( 'Test Microphone', 'videohub360-studio' ); ?></button>
                    </div>
                    <p class="vh360-studio-device-active" data-active-devices><?php esc_html_e( 'Camera: Permission required · Microphone: Permission required', 'videohub360-studio' ); ?></p>
                    <label for="vh360-studio-quality-select"><?php esc_html_e( 'Quality preset', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-quality-select" data-quality-select>
                        <?php foreach ( $quality_presets as $preset_id => $preset ) : ?>
                            <option value="<?php echo esc_attr( $preset_id ); ?>" <?php selected( $preset_id, $default_preset ); ?>><?php echo esc_html( $preset['label'] ); ?><?php if ( ! empty( $preset['recommended'] ) ) : ?> <?php esc_html_e( '(recommended)', 'videohub360-studio' ); ?><?php endif; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="vh360-studio-help" data-quality-details><?php echo esc_html( $default_label ); ?> · <?php esc_html_e( 'Higher quality creates larger files and longer uploads; lower presets remain available for smaller recordings.', 'videohub360-studio' ); ?></p>
                </div>
            </section>

            <section class="vh360-studio-dock" aria-labelledby="vh360-studio-audio-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-audio-title"><?php esc_html_e( 'Audio Mixer', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body">
                    <?php
                    $audio_channels = array(
                        'mic'    => __( 'Microphone', 'videohub360-studio' ),
                        'screen' => __( 'Screen Share', 'videohub360-studio' ),
                        'media'  => __( 'Media/Asset', 'videohub360-studio' ),
                        'master' => __( 'Master Output', 'videohub360-studio' ),
                    );
                    ?>
                    <div class="vh360-studio-audio-mixer" role="group" aria-label="<?php esc_attr_e( 'Studio audio mixer channels', 'videohub360-studio' ); ?>">
                        <?php foreach ( $audio_channels as $channel_id => $channel_label ) : ?>
                            <?php
                            $channel_display = 'mic' === $channel_id ? __( 'Mic/Aux', 'videohub360-studio' ) : $channel_label;
                            $initial_status  = 'master' === $channel_id ? __( 'Active', 'videohub360-studio' ) : __( 'Off', 'videohub360-studio' );
                            ?>
                            <div class="vh360-studio-audio-channel" data-mixer-channel="<?php echo esc_attr( $channel_id ); ?>">
                                <span class="vh360-studio-audio-status" data-mixer-status="<?php echo esc_attr( $channel_id ); ?>"><?php echo esc_html( $initial_status ); ?></span>
                                <strong class="vh360-studio-audio-name"><?php echo esc_html( $channel_display ); ?></strong>
                                <div class="vh360-studio-audio-strip-body">
                                    <?php if ( 'master' !== $channel_id ) : ?>
                                        <label class="vh360-studio-audio-gain">
                                            <span class="screen-reader-text"><?php echo esc_html( sprintf( __( '%s gain', 'videohub360-studio' ), $channel_label ) ); ?></span>
                                            <input type="range" min="0" max="150" value="100" data-mixer-gain="<?php echo esc_attr( $channel_id ); ?>">
                                        </label>
                                    <?php else : ?>
                                        <span class="vh360-studio-audio-master-note"><?php esc_html_e( 'Mix', 'videohub360-studio' ); ?></span>
                                    <?php endif; ?>
                                    <div class="vh360-studio-meter" aria-label="<?php echo esc_attr( $channel_label ); ?> <?php esc_attr_e( 'level', 'videohub360-studio' ); ?>"><span <?php echo 'mic' === $channel_id ? 'data-mic-meter ' : ''; ?>data-mixer-meter="<?php echo esc_attr( $channel_id ); ?>"></span></div>
                                </div>
                                <?php if ( 'master' !== $channel_id ) : ?>
                                    <button type="button" class="vh360-studio-audio-mute" data-mixer-mute="<?php echo esc_attr( $channel_id ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( sprintf( __( 'Mute %s', 'videohub360-studio' ), $channel_label ) ); ?>"><span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Mute %s', 'videohub360-studio' ), $channel_label ) ); ?></span></button>
                                <?php else : ?>
                                    <span class="vh360-studio-audio-master-note vh360-studio-audio-master-note--bottom"><?php esc_html_e( 'Output', 'videohub360-studio' ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="vh360-studio-dock" aria-labelledby="vh360-studio-live-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-live-title"><?php esc_html_e( 'Stream Controls', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body">
                    <p class="vh360-studio-help"><?php esc_html_e( 'Videohub360 handles the live connection automatically. Studio broadcasts the Program output and mixed Studio audio while viewers watch the public single video page.', 'videohub360-studio' ); ?></p>
                    <div class="vh360-studio-live-grid">
                        <p><label><?php esc_html_e( 'Title', 'videohub360-studio' ); ?><input type="text" data-broadcast-title placeholder="<?php esc_attr_e( 'My livestream', 'videohub360-studio' ); ?>"></label></p>
                        <p><label><?php esc_html_e( 'Description', 'videohub360-studio' ); ?><textarea data-broadcast-description rows="3"></textarea></label></p>
                        <div class="vh360-studio-cover-control" data-cover-control>
                            <label><?php esc_html_e( 'Cover Image / Featured Image', 'videohub360-studio' ); ?></label>
                            <input type="hidden" data-cover-image-id value="">
                            <input type="file" accept="image/*" data-cover-image-file hidden>
                            <div class="vh360-studio-cover-preview" data-cover-image-preview hidden>
                                <img src="" alt="<?php esc_attr_e( 'Selected cover image preview', 'videohub360-studio' ); ?>" data-cover-image-preview-img>
                            </div>
                            <div class="vh360-studio-actions vh360-studio-actions--compact">
                                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-select-cover-image><?php esc_html_e( 'Upload Cover Image', 'videohub360-studio' ); ?></button>
                                <button type="button" class="vh360-studio-button" data-remove-cover-image hidden><?php esc_html_e( 'Remove Cover', 'videohub360-studio' ); ?></button>
                            </div>
                            <p class="vh360-studio-help"><?php esc_html_e( 'Upload an image from your device to use for the livestream, replay, and saved replay media attachment.', 'videohub360-studio' ); ?></p>
                        </div>
                        <p><label><?php esc_html_e( 'Mode', 'videohub360-studio' ); ?><select data-broadcast-mode><option value="broadcast"><?php esc_html_e( 'Broadcast', 'videohub360-studio' ); ?></option><option value="interactive"><?php esc_html_e( 'Interactive', 'videohub360-studio' ); ?></option></select></label></p>
                        <p><label><input type="checkbox" data-broadcast-viewer-count checked> <?php esc_html_e( 'Display viewer count', 'videohub360-studio' ); ?></label></p>
                        <p><label><input type="checkbox" data-broadcast-chat checked> <?php esc_html_e( 'Display live chat', 'videohub360-studio' ); ?></label></p>
                        <p data-interactive-only><label><input type="checkbox" data-broadcast-everyone-host> <?php esc_html_e( 'Allow everyone to be host', 'videohub360-studio' ); ?></label></p>
                        <p data-interactive-only><label><input type="checkbox" data-broadcast-require-passcode> <?php esc_html_e( 'Require passcode to join/present', 'videohub360-studio' ); ?></label></p>
                        <p data-passcode-wrap hidden><label><?php esc_html_e( 'Passcode', 'videohub360-studio' ); ?><input type="password" data-broadcast-passcode autocomplete="new-password"></label></p>
                    </div>
                    <div class="vh360-studio-actions">
                        <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-go-live><?php esc_html_e( 'Go Live', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-end-live disabled><?php esc_html_e( 'End Live', 'videohub360-studio' ); ?></button>
                    </div>
                    <div class="vh360-studio-job-result" aria-live="polite" data-broadcast-status></div>
                </div>
            </section>

            <section class="vh360-studio-dock vh360-studio-dock--recording" aria-labelledby="vh360-studio-recorder-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-recorder-title"><?php esc_html_e( 'Recording / Replay', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body">
                    <div class="vh360-studio-workflow-summary">
                        <div><strong><?php esc_html_e( 'Status', 'videohub360-studio' ); ?></strong><span data-recording-summary-status><?php esc_html_e( 'Ready to record', 'videohub360-studio' ); ?></span></div>
                        <div><strong><?php esc_html_e( 'Duration', 'videohub360-studio' ); ?></strong><span data-recording-timer>00:00</span></div>
                        <div><strong><?php esc_html_e( 'Progress', 'videohub360-studio' ); ?></strong><span data-recording-progress-label>0%</span></div>
                    </div>
                    <progress class="vh360-studio-progress" max="100" value="0" data-recording-progress></progress>
                    <div class="vh360-studio-actions">
                        <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-start-recording><?php esc_html_e( 'Start recording', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-stop-recording hidden disabled><?php esc_html_e( 'Stop recording', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-retry-chunks hidden disabled><?php esc_html_e( 'Retry failed chunks', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button" data-finalize-recording hidden disabled><?php esc_html_e( 'Prepare replay', 'videohub360-studio' ); ?></button>
                    </div>
                    <p class="vh360-studio-help"><?php esc_html_e( 'Studio uploads recordings in chunks and can retry failed chunks during this browser session.', 'videohub360-studio' ); ?></p>
                    <div class="vh360-studio-job-result" aria-live="polite" data-recording-status></div>
                    <div class="vh360-studio-actions">
                        <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-publish-replay hidden disabled><?php esc_html_e( 'Publish replay', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-check-replay-status hidden disabled><?php esc_html_e( 'Check replay status', 'videohub360-studio' ); ?></button>
                    </div>
                    <div class="vh360-studio-publish-status" aria-live="polite" data-publishing-status></div>
                    <p class="vh360-studio-replay-link" data-replay-link-wrap hidden><strong><?php esc_html_e( 'Replay published.', 'videohub360-studio' ); ?></strong> <a href="#" class="vh360-studio-button vh360-studio-button--secondary" data-replay-link target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open replay', 'videohub360-studio' ); ?></a></p>
                    <?php if ( $is_admin ) : ?>
                        <details class="vh360-studio-technical-details">
                            <summary><?php esc_html_e( 'Technical Recording Details', 'videohub360-studio' ); ?></summary>
                            <div class="vh360-studio-recorder-stats">
                                <div><strong><?php esc_html_e( 'Current job ID', 'videohub360-studio' ); ?></strong><span data-recording-job-id>—</span></div>
                                <div><strong><?php esc_html_e( 'Selected MIME type', 'videohub360-studio' ); ?></strong><span data-recording-mime>—</span></div>
                                <div><strong><?php esc_html_e( 'Chunks uploaded', 'videohub360-studio' ); ?></strong><span data-recording-uploaded>0</span></div>
                                <div><strong><?php esc_html_e( 'Chunks pending', 'videohub360-studio' ); ?></strong><span data-recording-pending>0</span></div>
                                <div><strong><?php esc_html_e( 'Chunks failed', 'videohub360-studio' ); ?></strong><span data-recording-failed>0</span></div>
                                <div><strong><?php esc_html_e( 'Total bytes uploaded', 'videohub360-studio' ); ?></strong><span data-recording-bytes>0</span></div>
                                <div><strong><?php esc_html_e( 'Finalize status', 'videohub360-studio' ); ?></strong><span data-recording-finalize-status>—</span></div>
                                <div><strong><?php esc_html_e( 'Raw replay URL', 'videohub360-studio' ); ?></strong><span data-replay-raw-url>—</span></div>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="vh360-studio-lower-panels">
        <section class="vh360-studio-lower-panel" aria-labelledby="vh360-studio-readiness-title">
            <h3 id="vh360-studio-readiness-title"><?php esc_html_e( 'Studio readiness', 'videohub360-studio' ); ?></h3>
            <div class="vh360-studio-readiness-summary" data-readiness-summary>
                <strong data-readiness-heading><?php esc_html_e( 'Checking Studio…', 'videohub360-studio' ); ?></strong>
                <p data-readiness-message><?php esc_html_e( 'Checking browser support and permissions.', 'videohub360-studio' ); ?></p>
                <ul data-readiness-issues hidden></ul>
            </div>
            <?php if ( $is_admin ) : ?>
                <details class="vh360-studio-technical-details">
                    <summary><?php esc_html_e( 'Browser details', 'videohub360-studio' ); ?></summary>
                    <ul class="vh360-studio-checks" data-support-checks></ul>
                    <div class="vh360-studio-operator-status" aria-live="polite">
                        <h4><?php esc_html_e( 'Operator status', 'videohub360-studio' ); ?></h4>
                        <dl>
                            <div><dt><?php esc_html_e( 'Program canvas', 'videohub360-studio' ); ?></dt><dd data-operator-canvas-support>—</dd></div>
                            <div><dt><?php esc_html_e( 'Program source', 'videohub360-studio' ); ?></dt><dd data-operator-program-source>—</dd></div>
                            <div><dt><?php esc_html_e( 'Recording format', 'videohub360-studio' ); ?></dt><dd data-operator-recording-format>—</dd></div>
                            <div><dt><?php esc_html_e( 'Active job', 'videohub360-studio' ); ?></dt><dd data-operator-active-job>—</dd></div>
                            <div><dt><?php esc_html_e( 'Last REST error', 'videohub360-studio' ); ?></dt><dd data-operator-last-rest-error><?php esc_html_e( 'None', 'videohub360-studio' ); ?></dd></div>
                        </dl>
                    </div>
                </details>
            <?php endif; ?>
        </section>

        <section class="vh360-studio-lower-panel vh360-studio-recent-jobs" aria-labelledby="vh360-studio-recent-title">
            <h3 id="vh360-studio-recent-title"><?php esc_html_e( 'Recent Replays', 'videohub360-studio' ); ?></h3>
            <p data-empty-replays <?php echo empty( $jobs ) ? '' : 'hidden'; ?>><?php esc_html_e( 'No replays yet.', 'videohub360-studio' ); ?></p>
            <table class="vh360-recent-replays-table">
                <thead><tr><th><?php esc_html_e( 'Recording', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Status', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Created', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Replay', 'videohub360-studio' ); ?></th></tr></thead>
                <tbody data-recent-replays-body>
                    <?php foreach ( $jobs as $job ) : ?>
                <?php $replay_url = $public_replay_url( $job ); ?>
                        <tr data-job-id="<?php echo esc_attr( $job['id'] ); ?>"><td><?php echo esc_html( $display_job_title( $job ) ); ?></td><td><?php echo esc_html( $friendly_job_status( $job ) ); ?></td><td><?php echo esc_html( $format_replay_created_at( $job['created_at'] ) ); ?></td><td><?php if ( $replay_url ) : ?><a href="<?php echo esc_url( $replay_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open replay', 'videohub360-studio' ); ?></a><?php else : ?><?php echo esc_html( '—' ); ?><?php endif; ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ( $is_admin ) : ?>
                <details class="vh360-studio-technical-details vh360-studio-technical-jobs">
                    <summary><?php esc_html_e( 'Technical job history', 'videohub360-studio' ); ?></summary>
                    <p data-empty-jobs <?php echo empty( $jobs ) ? '' : 'hidden'; ?>><?php esc_html_e( 'No recording jobs have been created yet.', 'videohub360-studio' ); ?></p>
                    <div class="vh360-studio-technical-table-wrap">
                        <table class="vh360-dashboard-table">
                            <thead><tr><th><?php esc_html_e( 'ID', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Room', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Status', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Created', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'File Size', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'MIME Type', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Assembled', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Temp Expires', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Publish Status', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Replay', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Last Error', 'videohub360-studio' ); ?></th></tr></thead>
                            <tbody data-recent-jobs-technical-body>
                                <?php foreach ( $jobs as $job ) : ?>
                                    <tr data-job-id="<?php echo esc_attr( $job['id'] ); ?>"><td><?php echo esc_html( $job['id'] ); ?></td><td><?php echo esc_html( $job['room_id'] ); ?></td><td><?php echo esc_html( $job['status'] ); ?></td><td><?php echo esc_html( $job['created_at'] ); ?></td><td><?php echo esc_html( ! empty( $job['file_size'] ) ? size_format( absint( $job['file_size'] ) ) : '—' ); ?></td><td><?php echo esc_html( ! empty( $job['mime_type'] ) ? $job['mime_type'] : '—' ); ?></td><td><?php echo esc_html( ! empty( $job['assembled_at'] ) ? $job['assembled_at'] : '—' ); ?></td><td><?php echo esc_html( ! empty( $job['temp_expires_at'] ) ? $job['temp_expires_at'] : '—' ); ?></td><td><?php echo esc_html( ! empty( $job['publish_provider_status'] ) ? $job['publish_provider_status'] : '—' ); ?></td><td><?php if ( ! empty( $job['replay_video_id'] ) ) : ?><a href="<?php echo esc_url( get_permalink( absint( $job['replay_video_id'] ) ) ); ?>"><?php echo esc_html( absint( $job['replay_video_id'] ) ); ?></a><?php else : ?><?php echo esc_html( '—' ); ?><?php endif; ?></td><td><?php echo esc_html( ! empty( $job['error_message'] ) ? $job['error_message'] : '—' ); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endif; ?>
        </section>
    </div>

    <div class="vh360-studio-modal" data-media-source-modal hidden role="dialog" aria-modal="true" aria-labelledby="vh360-studio-media-source-title">
        <div class="vh360-studio-modal__backdrop" data-close-media-source-modal></div>

        <div class="vh360-studio-modal__panel">
            <div class="vh360-studio-modal__header">
                <h3 id="vh360-studio-media-source-title" data-media-source-modal-title><?php esc_html_e( 'Add Media Source', 'videohub360-studio' ); ?></h3>
                <button type="button" class="vh360-studio-modal__close" data-close-media-source-modal aria-label="<?php esc_attr_e( 'Close', 'videohub360-studio' ); ?>">×</button>
            </div>

            <div class="vh360-studio-modal__body">
                <label class="vh360-studio-field">
                    <span><?php esc_html_e( 'Media file', 'videohub360-studio' ); ?></span>
                    <input type="file" accept="image/*,video/*" data-persistent-media-source-input>
                </label>

                <label class="vh360-studio-field">
                    <span><?php esc_html_e( 'Display name', 'videohub360-studio' ); ?></span>
                    <input type="text" data-persistent-media-source-name maxlength="120" placeholder="<?php esc_attr_e( 'Example: Welcome Video', 'videohub360-studio' ); ?>">
                </label>

                <p class="vh360-studio-help" data-media-source-modal-help>
                    <?php esc_html_e( 'Imported media will be saved to Studio and remain available until you delete it.', 'videohub360-studio' ); ?>
                </p>

                <div class="vh360-studio-modal__status" data-persistent-media-source-status hidden></div>
            </div>

            <div class="vh360-studio-modal__footer">
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-close-media-source-modal>
                    <?php esc_html_e( 'Cancel', 'videohub360-studio' ); ?>
                </button>
                <button type="button" class="vh360-studio-button" data-import-media-source>
                    <?php esc_html_e( 'Add to Studio', 'videohub360-studio' ); ?>
                </button>
            </div>
        </div>
    </div>

</section>
