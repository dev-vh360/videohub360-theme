<?php
/** Studio mode entry router. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$base_url    = remove_query_arg( 'studio_mode' );
$mobile_url  = add_query_arg( array( 'tab' => 'studio', 'studio_mode' => 'mobile' ), $base_url );
$desktop_url = add_query_arg( array( 'tab' => 'studio', 'studio_mode' => 'desktop' ), $base_url );
?>
<section class="vh360-studio-entry-router" data-vh360-studio-entry-router data-mobile-url="<?php echo esc_url( $mobile_url ); ?>" data-desktop-url="<?php echo esc_url( $desktop_url ); ?>">
    <div class="vh360-studio-entry-router__card">
        <p class="vh360-studio-entry-router__eyebrow"><?php esc_html_e( 'VH360 Studio', 'videohub360-studio' ); ?></p>
        <h2><?php esc_html_e( 'Choose your Studio experience', 'videohub360-studio' ); ?></h2>
        <p><?php esc_html_e( 'We will automatically route phones to Mobile Live and larger pointer-based devices to Production Studio. You can also choose manually.', 'videohub360-studio' ); ?></p>
        <div class="vh360-studio-entry-router__actions">
            <a class="button button-primary" href="<?php echo esc_url( $mobile_url ); ?>" data-studio-mode-choice="mobile"><?php esc_html_e( 'Open Mobile Live', 'videohub360-studio' ); ?></a>
            <a class="button" href="<?php echo esc_url( $desktop_url ); ?>" data-studio-mode-choice="desktop"><?php esc_html_e( 'Open Production Studio', 'videohub360-studio' ); ?></a>
        </div>
    </div>
</section>
