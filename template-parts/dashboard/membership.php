<?php
/**
 * Dashboard Tab: Membership
 *
 * Renders the subscription management UI inside the frontend dashboard.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Show checkout return notices (set by Stripe return handler)
$user_id = get_current_user_id();
$notice  = '';
$notice_type = '';

if ( $user_id ) {
	$success_notice = get_transient( 'vh360_stripe_return_notice_' . $user_id );
	if ( $success_notice ) {
		$notice      = $success_notice['message'];
		$notice_type = $success_notice['type'];
		delete_transient( 'vh360_stripe_return_notice_' . $user_id );
	}
}

if ( $notice ) : ?>
	<div class="vh360-membership-notice vh360-membership-notice-<?php echo esc_attr( $notice_type ); ?>" style="margin-bottom: 20px;">
		<?php echo esc_html( $notice ); ?>
	</div>
<?php endif;

// Render the subscription management UI
if ( class_exists( 'VH360_Membership_Subscription_Management' ) ) {
	$manager = VH360_Membership_Subscription_Management::get_instance();
	echo $manager->render_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode handles its own escaping
} else {
	echo '<p>' . esc_html__( 'Membership management is not available.', 'videohub360-theme' ) . '</p>';
}
