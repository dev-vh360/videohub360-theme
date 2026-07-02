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
<section class="vh360-studio-dashboard">
    <h2><?php esc_html_e( 'VH360 Studio', 'videohub360-studio' ); ?></h2>
    <p><?php esc_html_e( 'Studio foundation is active. Phase 1A provides secure recording job orchestration, lifecycle tracking, and provider placeholders only.', 'videohub360-studio' ); ?></p>
    <div class="vh360-studio-status-card">
        <p><strong><?php esc_html_e( 'Status:', 'videohub360-studio' ); ?></strong> <?php esc_html_e( 'Ready for browser recording workflow integration', 'videohub360-studio' ); ?></p>
        <p><strong><?php esc_html_e( 'Default Quality Preset:', 'videohub360-studio' ); ?></strong> <?php echo esc_html( $default_label ); ?></p>
        <p><strong><?php esc_html_e( 'Recommended Replay/VOD Destination:', 'videohub360-studio' ); ?></strong> <?php echo esc_html( $storage_label ); ?></p>
        <p><?php esc_html_e( 'Local Media remains available only as a limited fallback for constrained hosting environments.', 'videohub360-studio' ); ?></p>
        <p><strong><?php esc_html_e( 'Lifecycle:', 'videohub360-studio' ); ?></strong> <?php echo esc_html( implode( ' → ', array( 'created', 'recording', 'stopping', 'uploading', 'processing', 'ready' ) ) ); ?></p>
    </div>
    <h3><?php esc_html_e( 'Recent Recording Jobs', 'videohub360-studio' ); ?></h3>
    <?php if ( empty( $jobs ) ) : ?>
        <p><?php esc_html_e( 'No recording jobs have been created yet.', 'videohub360-studio' ); ?></p>
    <?php else : ?>
        <table class="vh360-dashboard-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'ID', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Room', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Storage', 'videohub360-studio' ); ?></th>
                    <th><?php esc_html_e( 'Created', 'videohub360-studio' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $jobs as $job ) : ?>
                    <tr>
                        <td><?php echo esc_html( $job['id'] ); ?></td>
                        <td><?php echo esc_html( $job['room_id'] ); ?></td>
                        <td><?php echo esc_html( $job['status'] ); ?></td>
                        <td><?php echo esc_html( $job['storage_provider'] ); ?></td>
                        <td><?php echo esc_html( $job['created_at'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
