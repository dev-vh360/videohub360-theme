<?php
/**
 * Locked Studio dashboard view.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$license_url = admin_url( 'admin.php?page=videohub360-license' );
?>
<section class="vh360-studio-license-lock" aria-labelledby="vh360-studio-license-lock-title">
    <div class="vh360-studio-license-lock__icon" aria-hidden="true">🔒</div>
    <h2 id="vh360-studio-license-lock-title" class="vh360-studio-license-lock__title">
        <?php esc_html_e( 'Studio requires an active VideoHub360 license', 'videohub360-studio' ); ?>
    </h2>
    <p class="vh360-studio-license-lock__message">
        <?php esc_html_e( 'Activate your site license to create livestreams, record productions, and publish replays.', 'videohub360-studio' ); ?>
    </p>
    <?php if ( current_user_can( 'manage_options' ) ) : ?>
        <a class="vh360-studio-license-lock__action" href="<?php echo esc_url( $license_url ); ?>">
            <?php esc_html_e( 'Activate License', 'videohub360-studio' ); ?>
        </a>
    <?php else : ?>
        <p class="vh360-studio-license-lock__message">
            <?php esc_html_e( 'Ask a site administrator to activate the VideoHub360 license.', 'videohub360-studio' ); ?>
        </p>
    <?php endif; ?>
</section>
