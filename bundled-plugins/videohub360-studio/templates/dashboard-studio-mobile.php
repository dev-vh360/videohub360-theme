<?php
/**
 * Mobile Live dashboard.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$desktop_url = add_query_arg( array( 'tab' => 'studio', 'studio_mode' => 'desktop' ), remove_query_arg( 'studio_mode' ) );
?>
<section class="vh360-mobile-live" data-vh360-studio-mobile-live data-mobile-remote-count="0">
    <header class="vh360-mobile-live__header">
        <div>
            <p class="vh360-mobile-live__eyebrow"><?php echo esc_html( function_exists( 'vh360_studio_get_display_name' ) ? vh360_studio_get_display_name() : sprintf( __( '%s Studio', 'videohub360-studio' ), get_bloginfo( 'name' ) ) ); ?></p>
            <h2><?php esc_html_e( 'Mobile Live', 'videohub360-studio' ); ?></h2>
        </div>
        <a href="<?php echo esc_url( $desktop_url ); ?>" data-studio-mode-choice="desktop"><?php esc_html_e( 'Open Production Studio', 'videohub360-studio' ); ?></a>
    </header>

    <div class="vh360-mobile-live__status" data-mobile-status role="status" aria-live="polite" tabindex="-1">
        <?php esc_html_e( 'Set up your livestream, then preview your camera before going live.', 'videohub360-studio' ); ?>
    </div>

    <div class="vh360-mobile-live__device-statuses" aria-live="polite">
        <span data-mobile-camera-status><?php esc_html_e( 'Camera: not connected', 'videohub360-studio' ); ?></span>
        <span data-mobile-microphone-status><?php esc_html_e( 'Microphone: not connected', 'videohub360-studio' ); ?></span>
        <span data-mobile-connection-status><?php esc_html_e( 'Connection: not live', 'videohub360-studio' ); ?></span>
    </div>

    <div class="vh360-mobile-live__stage is-active" data-mobile-stage="setup" tabindex="-1">
        <label><?php esc_html_e( 'Title', 'videohub360-studio' ); ?><input type="text" data-mobile-title required></label>
        <label><?php esc_html_e( 'Description', 'videohub360-studio' ); ?><textarea data-mobile-description></textarea></label>
        <label><?php esc_html_e( 'Cover image', 'videohub360-studio' ); ?><input type="file" accept="image/*" data-mobile-cover></label>
        <div class="vh360-mobile-live__toggles">
            <label><input type="checkbox" data-mobile-chat checked> <?php esc_html_e( 'Chat on', 'videohub360-studio' ); ?></label>
            <label><input type="checkbox" data-mobile-viewer-count checked> <?php esc_html_e( 'Viewer count on', 'videohub360-studio' ); ?></label>
        </div>
        <details>
            <summary><?php esc_html_e( 'Advanced', 'videohub360-studio' ); ?></summary>
            <label><?php esc_html_e( 'Mode', 'videohub360-studio' ); ?>
                <select data-mobile-agora-mode>
                    <option value="broadcast"><?php esc_html_e( 'Broadcast', 'videohub360-studio' ); ?></option>
                    <option value="interactive"><?php esc_html_e( 'Interactive', 'videohub360-studio' ); ?></option>
                </select>
            </label>
            <label><input type="checkbox" data-mobile-everyone-host> <?php esc_html_e( 'Everyone can participate as host', 'videohub360-studio' ); ?></label>
            <label><input type="checkbox" data-mobile-require-passcode> <?php esc_html_e( 'Require host passcode', 'videohub360-studio' ); ?></label>
            <label data-mobile-passcode-field><?php esc_html_e( 'Host passcode', 'videohub360-studio' ); ?><input type="password" data-mobile-host-passcode></label>
        </details>
        <button class="vh360-mobile-live__primary" type="button" data-mobile-preview><?php esc_html_e( 'Preview Camera', 'videohub360-studio' ); ?></button>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="requesting_permissions" tabindex="-1">
        <p><?php esc_html_e( 'Requesting permissions…', 'videohub360-studio' ); ?></p>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="preview_ready" tabindex="-1">
        <div class="vh360-mobile-live__preview" data-agora-local-preview></div>
        <div class="vh360-mobile-live__controls">
            <button type="button" data-mobile-switch-camera><?php esc_html_e( 'Switch camera', 'videohub360-studio' ); ?></button>
            <button type="button" data-mobile-mute-audio aria-pressed="false"><?php esc_html_e( 'Mute mic', 'videohub360-studio' ); ?></button>
            <button type="button" data-mobile-mute-video aria-pressed="false"><?php esc_html_e( 'Camera off', 'videohub360-studio' ); ?></button>
        </div>
        <button class="vh360-mobile-live__primary" type="button" data-mobile-go-live><?php esc_html_e( 'Go Live', 'videohub360-studio' ); ?></button>
        <button type="button" data-mobile-back-setup><?php esc_html_e( 'Return to setup', 'videohub360-studio' ); ?></button>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="creating_broadcast" tabindex="-1">
        <p><?php esc_html_e( 'Starting your broadcast…', 'videohub360-studio' ); ?></p>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="connecting" tabindex="-1">
        <p><?php esc_html_e( 'Connecting to the live service…', 'videohub360-studio' ); ?></p>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="live" tabindex="-1">
        <div class="vh360-mobile-live__interactive-stage" data-mobile-interactive-stage>
            <div class="vh360-mobile-live__remote-stage" data-mobile-remote-stage>
                <div class="vh360-mobile-live__host-tile" data-mobile-host-tile>
                    <div class="vh360-mobile-live__host-video" data-mobile-live-preview></div>
                    <div class="vh360-mobile-live__participant-meta">
                        <span class="vh360-mobile-live__participant-name"><?php esc_html_e( 'You', 'videohub360-studio' ); ?></span>
                        <span class="vh360-mobile-live__microphone-state is-active" data-mobile-host-microphone aria-label="<?php esc_attr_e( 'Microphone on', 'videohub360-studio' ); ?>">
                            <svg class="vh360-mobile-live__microphone-icon vh360-mobile-live__microphone-icon--on" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3Zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V21H8v2h8v-2h-3v-3.08A7 7 0 0 0 19 11h-2Z"/></svg>
                            <svg class="vh360-mobile-live__microphone-icon vh360-mobile-live__microphone-icon--off" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m4.27 3 16.73 16.73-1.27 1.27-4.12-4.12A6.9 6.9 0 0 1 13 17.92V21h3v2H8v-2h3v-3.08A7 7 0 0 1 5 11h2a5 5 0 0 0 6.96 4.59l-1.52-1.52A3 3 0 0 1 9 11V10.63L3 4.27 4.27 3ZM12 2a3 3 0 0 1 3 3v6c0 .28-.04.55-.11.8L9 5.91V5a3 3 0 0 1 3-3Zm7 9a6.96 6.96 0 0 1-.84 3.32l-1.5-1.5c.22-.57.34-1.18.34-1.82h2Z"/></svg>
                        </span>
                    </div>
                </div>
            </div>
            <div class="vh360-mobile-live__top-overlay">
                <strong><?php esc_html_e( 'LIVE', 'videohub360-studio' ); ?></strong>
                <span data-mobile-duration>00:00</span>
                <span data-mobile-connection><?php esc_html_e( 'Connected', 'videohub360-studio' ); ?></span>
                <span class="vh360-mobile-live__participant-count" data-mobile-participant-count aria-label="<?php esc_attr_e( '0 participants', 'videohub360-studio' ); ?>" hidden>
                    <span class="vh360-mobile-live__participant-count-value" data-mobile-participant-count-value aria-hidden="true">0</span>
                    <span class="vh360-mobile-live__participant-count-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-1a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM9 13c-4.42 0-7 2.17-7 5v2h14v-2c0-2.83-2.58-5-7-5Zm6.55-.93c1.5 1.08 2.45 2.58 2.45 4.43V20h4v-2c0-2.71-2.36-4.82-6.45-5.93Z"/></svg></span>
                </span>
            </div>
            <div class="vh360-mobile-live__connection-banner" data-mobile-reconnect-banner hidden></div>
            <p class="vh360-mobile-live__participant-notice" data-mobile-participant-notice hidden></p>
            <button class="vh360-mobile-live__audio-enable" type="button" data-mobile-enable-participant-audio hidden><?php esc_html_e( 'Enable participant audio', 'videohub360-studio' ); ?></button>
            <div class="vh360-mobile-live__bottom-controls" data-mobile-live-controls>
                <button type="button" data-mobile-switch-camera><?php esc_html_e( 'Switch camera', 'videohub360-studio' ); ?></button>
                <button type="button" data-mobile-mute-audio aria-pressed="false"><?php esc_html_e( 'Mute mic', 'videohub360-studio' ); ?></button>
                <button type="button" data-mobile-mute-video aria-pressed="false"><?php esc_html_e( 'Camera off', 'videohub360-studio' ); ?></button>
                <button type="button" data-mobile-open-participants hidden><?php esc_html_e( 'Participants', 'videohub360-studio' ); ?></button>
                <button class="vh360-mobile-live__danger" type="button" data-mobile-end-live><?php esc_html_e( 'End Live', 'videohub360-studio' ); ?></button>
            </div>
            <aside class="vh360-mobile-live__participant-drawer" data-mobile-participant-drawer role="dialog" aria-modal="true" aria-hidden="true" hidden>
                <div class="vh360-mobile-live__participant-drawer-header">
                    <h3><?php esc_html_e( 'Participants', 'videohub360-studio' ); ?></h3>
                    <button type="button" data-mobile-close-participants><?php esc_html_e( 'Close participants', 'videohub360-studio' ); ?></button>
                </div>
                <div class="vh360-mobile-live__participant-list" data-mobile-participant-list></div>
                <button class="vh360-mobile-live__danger vh360-mobile-live__drawer-end" type="button" data-mobile-end-live><?php esc_html_e( 'End Live', 'videohub360-studio' ); ?></button>
            </aside>
        </div>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="ending" tabindex="-1">
        <p><?php esc_html_e( 'Ending livestream…', 'videohub360-studio' ); ?></p>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="end_failed" tabindex="-1">
        <p><?php esc_html_e( 'The server has not confirmed that the livestream ended.', 'videohub360-studio' ); ?></p>
        <button type="button" data-mobile-end-live><?php esc_html_e( 'End Live Again', 'videohub360-studio' ); ?></button>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="error" tabindex="-1">
        <p data-mobile-error-message><?php esc_html_e( 'Something went wrong.', 'videohub360-studio' ); ?></p>
        <button type="button" data-mobile-retry-permissions><?php esc_html_e( 'Retry Camera and Microphone', 'videohub360-studio' ); ?></button>
        <button type="button" data-mobile-back-setup><?php esc_html_e( 'Return to setup', 'videohub360-studio' ); ?></button>
    </div>

    <div class="vh360-mobile-live__stage" data-mobile-stage="ended" tabindex="-1">
        <h3><?php esc_html_e( 'Livestream ended', 'videohub360-studio' ); ?></h3>
        <a data-mobile-open-video target="_blank" rel="noopener"><?php esc_html_e( 'Open Video', 'videohub360-studio' ); ?></a>
        <a href="<?php echo esc_url( remove_query_arg( array( 'tab', 'studio_mode' ) ) ); ?>"><?php esc_html_e( 'Return to Dashboard', 'videohub360-studio' ); ?></a>
        <button type="button" data-mobile-start-another><?php esc_html_e( 'Start Another Live', 'videohub360-studio' ); ?></button>
    </div>
</section>
