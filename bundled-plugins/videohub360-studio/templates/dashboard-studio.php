<?php
/**
 * Studio dashboard tab template.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$storage_providers = $registry->get_storage_providers();
$default_label     = isset( $quality_presets[ $default_preset ]['label'] ) ? $quality_presets[ $default_preset ]['label'] : $default_preset;
$storage_label     = isset( $storage_providers['videopress'] ) ? $storage_providers['videopress']->get_label() : __( 'VideoPress', 'videohub360-studio' );
?>
<section class="vh360-studio-dashboard" data-vh360-studio-dashboard>
    <header class="vh360-studio-topbar">
        <div>
            <p class="vh360-studio-kicker"><?php esc_html_e( 'Production console', 'videohub360-studio' ); ?></p>
            <h2><?php esc_html_e( 'VH360 Studio', 'videohub360-studio' ); ?></h2>
        </div>
        <div class="vh360-studio-screen-reader-status" aria-live="polite" data-studio-status>
            <?php esc_html_e( 'Checking browser support…', 'videohub360-studio' ); ?>
        </div>
        <div class="vh360-studio-topbar-links" aria-live="polite">
            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-studio-fullscreen>
                <?php esc_html_e( 'Fullscreen Studio', 'videohub360-studio' ); ?>
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
                    <span class="vh360-studio-pill"><?php esc_html_e( 'Local tests', 'videohub360-studio' ); ?></span>
                </div>
                <div class="vh360-studio-monitor-screen vh360-studio-preview-stage">
                    <video class="vh360-studio-preview-video vh360-studio-preview-video--camera" data-camera-preview playsinline muted aria-label="<?php esc_attr_e( 'Local camera preview', 'videohub360-studio' ); ?>"></video>
                    <video class="vh360-studio-preview-video vh360-studio-preview-video--screen" data-screen-preview playsinline muted aria-label="<?php esc_attr_e( 'Screen-share preview', 'videohub360-studio' ); ?>"></video>
                    <span class="vh360-studio-monitor-empty" data-preview-empty><?php esc_html_e( 'Start camera preview or screen share from the Sources panel.', 'videohub360-studio' ); ?></span>
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
            </section>
        </div>

        <div class="vh360-studio-dock-grid">
            <section class="vh360-studio-dock" aria-labelledby="vh360-studio-scenes-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-scenes-title"><?php esc_html_e( 'Scenes', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body">
                    <ul class="vh360-studio-scene-list">
                        <li><button type="button" data-scene-source="camera"><?php esc_html_e( 'Camera Only', 'videohub360-studio' ); ?></button></li>
                        <li><button type="button" data-scene-source="screen"><?php esc_html_e( 'Screen Share', 'videohub360-studio' ); ?></button></li>
                    </ul>
                    <p class="vh360-studio-help"><?php esc_html_e( 'Scene buttons stage the matching source in Preview.', 'videohub360-studio' ); ?></p>
                </div>
            </section>

            <section class="vh360-studio-dock" aria-labelledby="vh360-studio-sources-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-sources-title"><?php esc_html_e( 'Sources', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body vh360-studio-control-stack">
                    <ul class="vh360-studio-source-list">
                        <li><button type="button" data-preview-source="camera"><?php esc_html_e( 'Camera', 'videohub360-studio' ); ?></button></li>
                        <li><button type="button" data-preview-source="screen"><?php esc_html_e( 'Screen Share', 'videohub360-studio' ); ?></button></li>
                    </ul>
                    <label for="vh360-studio-camera-select"><?php esc_html_e( 'Camera', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-camera-select" data-camera-select disabled>
                        <option value=""><?php esc_html_e( 'Grant camera access to list devices', 'videohub360-studio' ); ?></option>
                    </select>
                    <div class="vh360-studio-actions">
                        <button type="button" class="vh360-studio-button" data-start-preview><?php esc_html_e( 'Start camera & mic preview', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-stop-preview disabled><?php esc_html_e( 'Stop preview', 'videohub360-studio' ); ?></button>
                    </div>
                    <div class="vh360-studio-actions">
                        <button type="button" class="vh360-studio-button" data-start-screen><?php esc_html_e( 'Start screen share', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-stop-screen disabled><?php esc_html_e( 'Stop screen share', 'videohub360-studio' ); ?></button>
                    </div>
                    <label for="vh360-studio-quality-select"><?php esc_html_e( 'Quality preset', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-quality-select" data-quality-select>
                        <?php foreach ( $quality_presets as $preset_id => $preset ) : ?>
                            <option value="<?php echo esc_attr( $preset_id ); ?>" <?php selected( $preset_id, $default_preset ); ?>><?php echo esc_html( $preset['label'] ); ?><?php if ( ! empty( $preset['recommended'] ) ) : ?> <?php esc_html_e( '(recommended)', 'videohub360-studio' ); ?><?php endif; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="vh360-studio-help" data-quality-details><?php echo esc_html( $default_label ); ?></p>
                </div>
            </section>

            <section class="vh360-studio-dock" aria-labelledby="vh360-studio-audio-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-audio-title"><?php esc_html_e( 'Audio Mixer', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body">
                    <label for="vh360-studio-mic-select"><?php esc_html_e( 'Microphone', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-mic-select" data-mic-select disabled>
                        <option value=""><?php esc_html_e( 'Grant microphone access to list devices', 'videohub360-studio' ); ?></option>
                    </select>
                    <div class="vh360-studio-audio-channel">
                        <strong><?php esc_html_e( 'Mic/Aux', 'videohub360-studio' ); ?></strong>
                        <div class="vh360-studio-meter" aria-label="<?php esc_attr_e( 'Microphone level', 'videohub360-studio' ); ?>"><span data-mic-meter></span></div>
                    </div>
                </div>
            </section>

            <section class="vh360-studio-dock" aria-labelledby="vh360-studio-live-title">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-live-title"><?php esc_html_e( 'Stream Controls', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body">
                    <p class="vh360-studio-help"><?php esc_html_e( 'Agora is automatic. Studio broadcasts your camera and microphone while viewers watch the public single video page.', 'videohub360-studio' ); ?></p>
                    <div class="vh360-studio-live-grid">
                        <p><label><?php esc_html_e( 'Title', 'videohub360-studio' ); ?><input type="text" data-broadcast-title placeholder="<?php esc_attr_e( 'My livestream', 'videohub360-studio' ); ?>"></label></p>
                        <p><label><?php esc_html_e( 'Description', 'videohub360-studio' ); ?><textarea data-broadcast-description rows="3"></textarea></label></p>
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
                    <div class="vh360-studio-recorder-stats">
                        <div><strong><?php esc_html_e( 'Current job ID', 'videohub360-studio' ); ?></strong><span data-recording-job-id>—</span></div>
                        <div><strong><?php esc_html_e( 'Selected MIME type', 'videohub360-studio' ); ?></strong><span data-recording-mime>—</span></div>
                        <div><strong><?php esc_html_e( 'Recording timer', 'videohub360-studio' ); ?></strong><span data-recording-timer>00:00</span></div>
                        <div><strong><?php esc_html_e( 'Chunks uploaded', 'videohub360-studio' ); ?></strong><span data-recording-uploaded>0</span></div>
                        <div><strong><?php esc_html_e( 'Chunks pending', 'videohub360-studio' ); ?></strong><span data-recording-pending>0</span></div>
                        <div><strong><?php esc_html_e( 'Chunks failed', 'videohub360-studio' ); ?></strong><span data-recording-failed>0</span></div>
                        <div><strong><?php esc_html_e( 'Total bytes uploaded', 'videohub360-studio' ); ?></strong><span data-recording-bytes>0</span></div>
                        <div><strong><?php esc_html_e( 'Finalize status', 'videohub360-studio' ); ?></strong><span data-recording-finalize-status>—</span></div>
                    </div>
                    <progress class="vh360-studio-progress" max="100" value="0" data-recording-progress></progress>
                    <div class="vh360-studio-actions">
                        <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-start-recording><?php esc_html_e( 'Start recording', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-stop-recording disabled><?php esc_html_e( 'Stop recording', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-retry-chunks disabled><?php esc_html_e( 'Retry failed chunks', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button" data-finalize-recording disabled><?php esc_html_e( 'Finalize recording', 'videohub360-studio' ); ?></button>
                    </div>
                    <div class="vh360-studio-job-result" aria-live="polite" data-recording-status></div>
                    <label for="vh360-studio-storage-select"><?php esc_html_e( 'Replay destination', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-storage-select" data-storage-select>
                        <?php foreach ( $storage_providers as $provider_id => $provider ) : ?>
                            <option value="<?php echo esc_attr( $provider_id ); ?>" <?php selected( $provider_id, 'videopress' ); ?>><?php echo esc_html( $provider->get_label() ); ?><?php if ( 'videopress' === $provider_id ) : ?> <?php esc_html_e( '(recommended)', 'videohub360-studio' ); ?><?php elseif ( 'local_media' === $provider_id ) : ?> <?php esc_html_e( '(limited fallback)', 'videohub360-studio' ); ?><?php endif; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="vh360-studio-help"><?php printf( esc_html__( 'Default: %s. Local Media Fallback stores recordings in your WordPress Media Library and is best for testing or smaller recordings. VideoPress is recommended for shared/cloud-hosting replay delivery.', 'videohub360-studio' ), esc_html( $storage_label ) ); ?></p>
                    <div class="vh360-studio-actions">
                        <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-publish-replay><?php esc_html_e( 'Publish replay', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-check-publishing-status><?php esc_html_e( 'Check publishing status', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-create-job><?php esc_html_e( 'Create setup job', 'videohub360-studio' ); ?></button>
                    </div>
                    <div class="vh360-studio-job-result" aria-live="polite" data-job-result></div>
                    <div class="vh360-studio-publish-status" aria-live="polite" data-publishing-status></div>
                    <p class="vh360-studio-replay-link" data-replay-link-wrap hidden><strong><?php esc_html_e( 'Replay:', 'videohub360-studio' ); ?></strong> <a href="#" data-replay-link target="_blank" rel="noopener noreferrer"></a></p>
                </div>
            </section>
        </div>
    </div>

    <div class="vh360-studio-lower-panels">
        <section class="vh360-studio-lower-panel" aria-labelledby="vh360-studio-readiness-title">
            <h3 id="vh360-studio-readiness-title"><?php esc_html_e( 'Studio readiness', 'videohub360-studio' ); ?></h3>
            <p><?php esc_html_e( 'These checks confirm browser support for preview, recording, and chunked uploads.', 'videohub360-studio' ); ?></p>
            <ul class="vh360-studio-checks" data-support-checks></ul>
        </section>

        <section class="vh360-studio-lower-panel vh360-studio-recent-jobs" aria-labelledby="vh360-studio-recent-title">
            <h3 id="vh360-studio-recent-title"><?php esc_html_e( 'Recent Recording Jobs', 'videohub360-studio' ); ?></h3>
            <p data-empty-jobs <?php echo empty( $jobs ) ? '' : 'hidden'; ?>><?php esc_html_e( 'No recording jobs have been created yet.', 'videohub360-studio' ); ?></p>
            <table class="vh360-dashboard-table">
                <thead><tr><th><?php esc_html_e( 'ID', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Room', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Status', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Storage', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Created', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'File Size', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'MIME Type', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Assembled', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Temp Expires', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Provider Status', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Replay', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Last Error', 'videohub360-studio' ); ?></th></tr></thead>
                <tbody data-recent-jobs-body>
                    <?php foreach ( $jobs as $job ) : ?>
                        <tr><td><?php echo esc_html( $job['id'] ); ?></td><td><?php echo esc_html( $job['room_id'] ); ?></td><td><?php echo esc_html( $job['status'] ); ?></td><td><?php echo esc_html( $job['storage_provider'] ); ?></td><td><?php echo esc_html( $job['created_at'] ); ?></td><td><?php echo esc_html( ! empty( $job['file_size'] ) ? size_format( absint( $job['file_size'] ) ) : '—' ); ?></td><td><?php echo esc_html( ! empty( $job['mime_type'] ) ? $job['mime_type'] : '—' ); ?></td><td><?php echo esc_html( ! empty( $job['assembled_at'] ) ? $job['assembled_at'] : '—' ); ?></td><td><?php echo esc_html( ! empty( $job['temp_expires_at'] ) ? $job['temp_expires_at'] : '—' ); ?></td><td><?php echo esc_html( ! empty( $job['publish_provider_status'] ) ? $job['publish_provider_status'] : '—' ); ?></td><td><?php if ( ! empty( $job['replay_video_id'] ) ) : ?><a href="<?php echo esc_url( get_permalink( absint( $job['replay_video_id'] ) ) ); ?>"><?php echo esc_html( absint( $job['replay_video_id'] ) ); ?></a><?php else : ?><?php echo esc_html( '—' ); ?><?php endif; ?></td><td><?php echo esc_html( ! empty( $job['error_message'] ) ? $job['error_message'] : '—' ); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</section>
