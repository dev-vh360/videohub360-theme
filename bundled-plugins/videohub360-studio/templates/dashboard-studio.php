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
    <header class="vh360-studio-hero">
        <div>
            <p class="vh360-studio-kicker"><?php esc_html_e( 'Creator setup', 'videohub360-studio' ); ?></p>
            <h2><?php esc_html_e( 'VH360 Studio', 'videohub360-studio' ); ?></h2>
            <p><?php esc_html_e( 'Confirm your browser, camera, microphone, screen-share, quality, and replay destination before a future recording session.', 'videohub360-studio' ); ?></p>
        </div>
        <div class="vh360-studio-status" aria-live="polite" data-studio-status>
            <?php esc_html_e( 'Checking browser support…', 'videohub360-studio' ); ?>
        </div>
    </header>

    <div class="vh360-studio-grid">
        <section class="vh360-studio-card vh360-studio-card--wide" aria-labelledby="vh360-studio-readiness-title">
            <h3 id="vh360-studio-readiness-title"><?php esc_html_e( 'Studio readiness', 'videohub360-studio' ); ?></h3>
            <p><?php esc_html_e( 'These checks confirm browser support for preview, recording, and chunked uploads.', 'videohub360-studio' ); ?></p>
            <ul class="vh360-studio-checks" data-support-checks></ul>
        </section>

        <section class="vh360-studio-card" aria-labelledby="vh360-studio-camera-title">
            <h3 id="vh360-studio-camera-title"><?php esc_html_e( 'Camera preview', 'videohub360-studio' ); ?></h3>
            <label for="vh360-studio-camera-select"><?php esc_html_e( 'Camera', 'videohub360-studio' ); ?></label>
            <select id="vh360-studio-camera-select" data-camera-select disabled>
                <option value=""><?php esc_html_e( 'Grant camera access to list devices', 'videohub360-studio' ); ?></option>
            </select>
            <div class="vh360-studio-video-shell">
                <video data-camera-preview playsinline muted aria-label="<?php esc_attr_e( 'Local camera preview', 'videohub360-studio' ); ?>"></video>
            </div>
        </section>

        <section class="vh360-studio-card" aria-labelledby="vh360-studio-mic-title">
            <h3 id="vh360-studio-mic-title"><?php esc_html_e( 'Microphone preview', 'videohub360-studio' ); ?></h3>
            <label for="vh360-studio-mic-select"><?php esc_html_e( 'Microphone', 'videohub360-studio' ); ?></label>
            <select id="vh360-studio-mic-select" data-mic-select disabled>
                <option value=""><?php esc_html_e( 'Grant microphone access to list devices', 'videohub360-studio' ); ?></option>
            </select>
            <div class="vh360-studio-meter" aria-label="<?php esc_attr_e( 'Microphone level', 'videohub360-studio' ); ?>">
                <span data-mic-meter></span>
            </div>
            <div class="vh360-studio-actions">
                <button type="button" class="vh360-studio-button" data-start-preview><?php esc_html_e( 'Start camera & mic preview', 'videohub360-studio' ); ?></button>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-stop-preview disabled><?php esc_html_e( 'Stop preview', 'videohub360-studio' ); ?></button>
            </div>
        </section>

        <section class="vh360-studio-card vh360-studio-card--wide" aria-labelledby="vh360-studio-screen-title">
            <h3 id="vh360-studio-screen-title"><?php esc_html_e( 'Screen-share test', 'videohub360-studio' ); ?></h3>
            <p><?php esc_html_e( 'Test screen sharing without recording or combining it with your camera.', 'videohub360-studio' ); ?></p>
            <div class="vh360-studio-video-shell vh360-studio-video-shell--screen">
                <video data-screen-preview playsinline muted aria-label="<?php esc_attr_e( 'Screen-share preview', 'videohub360-studio' ); ?>"></video>
            </div>
            <div class="vh360-studio-actions">
                <button type="button" class="vh360-studio-button" data-start-screen><?php esc_html_e( 'Test screen share', 'videohub360-studio' ); ?></button>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-stop-screen disabled><?php esc_html_e( 'Stop screen share', 'videohub360-studio' ); ?></button>
            </div>
        </section>

        <section class="vh360-studio-card" aria-labelledby="vh360-studio-quality-title">
            <h3 id="vh360-studio-quality-title"><?php esc_html_e( 'Quality preset', 'videohub360-studio' ); ?></h3>
            <p><?php esc_html_e( 'Choose a controlled preset for the future browser recorder.', 'videohub360-studio' ); ?></p>
            <label for="vh360-studio-quality-select"><?php esc_html_e( 'Preset', 'videohub360-studio' ); ?></label>
            <select id="vh360-studio-quality-select" data-quality-select>
                <?php foreach ( $quality_presets as $preset_id => $preset ) : ?>
                    <option value="<?php echo esc_attr( $preset_id ); ?>" <?php selected( $preset_id, $default_preset ); ?>>
                        <?php echo esc_html( $preset['label'] ); ?>
                        <?php if ( ! empty( $preset['recommended'] ) ) : ?>
                            <?php esc_html_e( '(recommended)', 'videohub360-studio' ); ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="vh360-studio-help" data-quality-details><?php echo esc_html( $default_label ); ?></p>
        </section>

        <section class="vh360-studio-card" aria-labelledby="vh360-studio-storage-title">
            <h3 id="vh360-studio-storage-title"><?php esc_html_e( 'Replay destination', 'videohub360-studio' ); ?></h3>
            <p><?php esc_html_e( 'VideoPress is the recommended replay/VOD destination. VideoPress can publish validated Studio replays when Jetpack/VideoPress is available. Publitio remains a placeholder.', 'videohub360-studio' ); ?></p>
            <label for="vh360-studio-storage-select"><?php esc_html_e( 'Destination', 'videohub360-studio' ); ?></label>
            <select id="vh360-studio-storage-select" data-storage-select>
                <?php foreach ( $storage_providers as $provider_id => $provider ) : ?>
                    <option value="<?php echo esc_attr( $provider_id ); ?>" <?php selected( $provider_id, 'videopress' ); ?>>
                        <?php echo esc_html( $provider->get_label() ); ?>
                        <?php if ( 'videopress' === $provider_id ) : ?>
                            <?php esc_html_e( '(recommended)', 'videohub360-studio' ); ?>
                        <?php elseif ( 'local_media' === $provider_id ) : ?>
                            <?php esc_html_e( '(limited fallback)', 'videohub360-studio' ); ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="vh360-studio-help"><?php printf( esc_html__( 'Default: %s. Local Media Fallback stores recordings in your WordPress Media Library and is best for testing or smaller recordings. VideoPress is recommended for shared/cloud-hosting replay delivery.', 'videohub360-studio' ), esc_html( $storage_label ) ); ?></p>
        </section>



        <section class="vh360-studio-card vh360-studio-card--wide" aria-labelledby="vh360-studio-recorder-title">
            <h3 id="vh360-studio-recorder-title"><?php esc_html_e( 'Browser recording upload', 'videohub360-studio' ); ?></h3>
            <p><?php esc_html_e( 'This records in your browser and uploads chunks to WordPress for later replay publishing.', 'videohub360-studio' ); ?></p>
            <div class="vh360-studio-recorder-grid">
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
        </section>

        <section class="vh360-studio-card vh360-studio-card--wide" aria-labelledby="vh360-studio-publishing-title">
            <h3 id="vh360-studio-publishing-title"><?php esc_html_e( 'Publish Replay', 'videohub360-studio' ); ?></h3>
            <p><?php esc_html_e( 'Publish the current finalized processing job through the selected replay destination and create/update a normal VideoHub360 replay post. VideoPress requires a real GUID before the job is marked ready.', 'videohub360-studio' ); ?></p>
            <div class="vh360-studio-actions">
                <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-publish-replay><?php esc_html_e( 'Publish replay', 'videohub360-studio' ); ?></button>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-check-publishing-status><?php esc_html_e( 'Check publishing status', 'videohub360-studio' ); ?></button>
            </div>
            <div class="vh360-studio-publish-status" aria-live="polite" data-publishing-status></div>
            <p class="vh360-studio-replay-link" data-replay-link-wrap hidden>
                <strong><?php esc_html_e( 'Replay:', 'videohub360-studio' ); ?></strong>
                <a href="#" data-replay-link target="_blank" rel="noopener noreferrer"></a>
            </p>
        </section>

        <section class="vh360-studio-card vh360-studio-card--wide" aria-labelledby="vh360-studio-job-title">
            <h3 id="vh360-studio-job-title"><?php esc_html_e( 'Setup job', 'videohub360-studio' ); ?></h3>
            <p><?php esc_html_e( 'Create a safe setup job in the created state. Use this job to start browser recording and chunked uploads. Use the publishing panel to publish validated processing jobs.', 'videohub360-studio' ); ?></p>
            <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-create-job><?php esc_html_e( 'Create setup job', 'videohub360-studio' ); ?></button>
            <div class="vh360-studio-job-result" aria-live="polite" data-job-result></div>
        </section>
    </div>

    <section class="vh360-studio-card vh360-studio-card--wide vh360-studio-recent-jobs" aria-labelledby="vh360-studio-recent-title">
        <h3 id="vh360-studio-recent-title"><?php esc_html_e( 'Recent Recording Jobs', 'videohub360-studio' ); ?></h3>
        <p data-empty-jobs <?php echo empty( $jobs ) ? '' : 'hidden'; ?>><?php esc_html_e( 'No recording jobs have been created yet.', 'videohub360-studio' ); ?></p>
        <table class="vh360-dashboard-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'ID', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Room', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Storage', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Created', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'File Size', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'MIME Type', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Assembled', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Temp Expires', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Provider Status', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Replay', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Last Error', 'videohub360-studio' ); ?></th>
                </tr>
            </thead>
            <tbody data-recent-jobs-body>
                <?php foreach ( $jobs as $job ) : ?>
                    <tr>
                        <td><?php echo esc_html( $job['id'] ); ?></td>
                        <td><?php echo esc_html( $job['room_id'] ); ?></td>
                        <td><?php echo esc_html( $job['status'] ); ?></td>
                        <td><?php echo esc_html( $job['storage_provider'] ); ?></td>
                        <td><?php echo esc_html( $job['created_at'] ); ?></td>
                        <td><?php echo esc_html( ! empty( $job['file_size'] ) ? size_format( absint( $job['file_size'] ) ) : '—' ); ?></td>
                        <td><?php echo esc_html( ! empty( $job['mime_type'] ) ? $job['mime_type'] : '—' ); ?></td>
                        <td><?php echo esc_html( ! empty( $job['assembled_at'] ) ? $job['assembled_at'] : '—' ); ?></td>
                        <td><?php echo esc_html( ! empty( $job['temp_expires_at'] ) ? $job['temp_expires_at'] : '—' ); ?></td>
                        <td><?php echo esc_html( ! empty( $job['publish_provider_status'] ) ? $job['publish_provider_status'] : '—' ); ?></td>
                        <td><?php if ( ! empty( $job['replay_video_id'] ) ) : ?><a href="<?php echo esc_url( get_permalink( absint( $job['replay_video_id'] ) ) ); ?>"><?php echo esc_html( absint( $job['replay_video_id'] ) ); ?></a><?php else : ?><?php echo esc_html( '—' ); ?><?php endif; ?></td>
                        <td><?php echo esc_html( ! empty( $job['error_message'] ) ? $job['error_message'] : '—' ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</section>
