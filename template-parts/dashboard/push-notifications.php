<?php
/**
 * Dashboard Tab: Push Notifications
 *
 * Frontend form to send push notifications via the VH360 PWA & App plugin.
 *
 * @package Videohub360_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	return;
}

if ( ! current_user_can( 'vh360_send_push' ) ) : ?>
	<div class="vh360-dashboard-card">
		<h2><?php esc_html_e( 'Push Notifications', 'videohub360-theme' ); ?></h2>
		<p><?php esc_html_e( 'You do not have permission to send push notifications.', 'videohub360-theme' ); ?></p>
	</div>
	<?php
	return;
endif;
?>

<div class="vh360-dashboard-card">
	<h2><?php esc_html_e( 'Send Push Notification', 'videohub360-theme' ); ?></h2>
	<p class="vh360-dashboard-help">
		<?php esc_html_e( 'This will send a push notification to subscribed users via your configured PWA/App push provider.', 'videohub360-theme' ); ?>
	</p>

	<form id="vh360-push-notification-form" class="vh360-dashboard-form vh360-push-form" autocomplete="off">
		<div class="vh360-push-grid">
			<div class="vh360-push-col vh360-push-col-main">
				<div class="vh360-push-field">
					<label for="vh360-push-title"><?php esc_html_e( 'Title', 'videohub360-theme' ); ?></label>
					<input type="text" id="vh360-push-title" name="title" maxlength="80" required />
				</div>

				<div class="vh360-push-field">
					<label for="vh360-push-body"><?php esc_html_e( 'Message', 'videohub360-theme' ); ?></label>
					<textarea id="vh360-push-body" name="body" rows="5" maxlength="240" required></textarea>
				</div>
			</div>

			<div class="vh360-push-col vh360-push-col-optional">
				<div class="vh360-push-field vh360-push-optional">
					<label for="vh360-push-url"><?php esc_html_e( 'Click URL (optional)', 'videohub360-theme' ); ?></label>
					<input type="url" id="vh360-push-url" name="url" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
					<p class="vh360-field-hint">
						<?php esc_html_e( 'If set, tapping the notification will open this page.', 'videohub360-theme' ); ?>
					</p>
				</div>

				<div class="vh360-push-field vh360-push-optional">
					<label for="vh360-push-icon"><?php esc_html_e( 'Icon URL (optional)', 'videohub360-theme' ); ?></label>
					<input type="url" id="vh360-push-icon" name="icon" placeholder="https://.../icon.png" />
					<p class="vh360-field-hint">
						<?php esc_html_e( 'Overrides the default notification icon if your provider supports it.', 'videohub360-theme' ); ?>
					</p>
				</div>
			</div>
		</div>

		<div class="vh360-push-actions">
			<button type="submit" class="vh360-dashboard-btn vh360-dashboard-btn-primary" id="vh360-push-submit">
				<?php esc_html_e( 'Send Notification', 'videohub360-theme' ); ?>
			</button>
			<span id="vh360-push-status" class="vh360-form-status" aria-live="polite"></span>
		</div>
	</form>
</div>

