<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$storage_providers = $registry->get_storage_providers();
?>
<section class="vh360-studio-dashboard">
    <h2><?php esc_html_e( 'VH360 Studio', 'videohub360-studio' ); ?></h2>
    <p><?php esc_html_e( 'Studio foundation is active. Phase 1A provides secure job orchestration and provider placeholders only.', 'videohub360-studio' ); ?></p>
    <div class="vh360-studio-status-card">
        <p><strong><?php esc_html_e( 'Status:', 'videohub360-studio' ); ?></strong> <?php esc_html_e( 'Ready for browser-based recording workflow integration', 'videohub360-studio' ); ?></p>
        <p><strong><?php esc_html_e( 'Quality Preset:', 'videohub360-studio' ); ?></strong> <?php esc_html_e( 'Standard', 'videohub360-studio' ); ?></p>
        <p><strong><?php esc_html_e( 'Storage Provider:', 'videohub360-studio' ); ?></strong> <?php echo esc_html( isset( $storage_providers['local_media'] ) ? $storage_providers['local_media']->get_label() : __( 'Local Media Fallback', 'videohub360-studio' ) ); ?></p>
    </div>
    <h3><?php esc_html_e( 'Recent Recording Jobs', 'videohub360-studio' ); ?></h3>
    <?php if ( empty( $jobs ) ) : ?>
        <p><?php esc_html_e( 'No recording jobs have been created yet.', 'videohub360-studio' ); ?></p>
    <?php else : ?>
        <table class="vh360-dashboard-table"><thead><tr><th><?php esc_html_e( 'ID', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Room', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Status', 'videohub360-studio' ); ?></th><th><?php esc_html_e( 'Created', 'videohub360-studio' ); ?></th></tr></thead><tbody>
        <?php foreach ( $jobs as $job ) : ?>
            <tr><td><?php echo esc_html( $job['id'] ); ?></td><td><?php echo esc_html( $job['room_id'] ); ?></td><td><?php echo esc_html( $job['status'] ); ?></td><td><?php echo esc_html( $job['created_at'] ); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    <?php endif; ?>
</section>
