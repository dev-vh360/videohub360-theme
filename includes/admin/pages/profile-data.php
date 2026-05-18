<?php
/**
 * Profile Data Admin Viewer
 *
 * Displays paginated user profile data in the WordPress admin, mirroring the
 * fields exported in the CSV export tool. Admins can search, filter by account
 * type, and drill into a detailed view for any individual user.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'videohub360-theme' ) );
}

$admin = VH360_Theme_Admin::get_instance();

// ----------------------------------------------------------------
// Determine whether we are showing the detail view or the list.
// ----------------------------------------------------------------
$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

if ( $user_id ) {
	// ============================================================
	// DETAIL VIEW
	// ============================================================

	$profile_data = $admin->get_user_profile_data_for_admin( $user_id );
	if ( ! $profile_data ) {
		$page_title = __( 'Profile Data', 'videohub360-theme' );
		include VH360_THEME_DIR . '/includes/admin/partials/header.php';
		echo '<div class="notice notice-error"><p>' . esc_html__( 'User not found.', 'videohub360-theme' ) . '</p></div>';
		include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
		return;
	}

	$user    = $profile_data['user'];
	$wp      = $profile_data['wp'];

	$page_title = sprintf(
		/* translators: %s: display name */
		__( 'Profile Data: %s', 'videohub360-theme' ),
		$user->display_name
	);
	include VH360_THEME_DIR . '/includes/admin/partials/header.php';

	// Back link
	$list_url = admin_url( 'admin.php?page=vh360-profile-data' );
	?>

	<div class="vh360-profile-data-detail">

		<!-- Toolbar -->
		<div class="vh360-profile-data-toolbar" style="margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
			<a href="<?php echo esc_url( $list_url ); ?>" class="button">
				&larr; <?php esc_html_e( 'Back to All Users', 'videohub360-theme' ); ?>
			</a>
			<?php if ( current_user_can( 'edit_users' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user->ID ) ); ?>" class="button">
					<?php esc_html_e( 'Edit WordPress User', 'videohub360-theme' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vh360_export_user_profiles' ), 'vh360_export_user_profiles' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Export User Profile Data CSV', 'videohub360-theme' ); ?>
			</a>
		</div>

		<!-- A. WordPress User Info -->
		<div class="vh360-admin-card">
			<h2><?php esc_html_e( 'WordPress User Info', 'videohub360-theme' ); ?></h2>
			<table class="widefat fixed striped" style="margin-top:0;">
				<tbody>
					<tr>
						<th style="width:220px;"><?php esc_html_e( 'User ID', 'videohub360-theme' ); ?></th>
						<td><?php echo esc_html( $wp['user_id'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Username', 'videohub360-theme' ); ?></th>
						<td><?php echo esc_html( $wp['user_login'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Email', 'videohub360-theme' ); ?></th>
						<td><?php echo esc_html( $wp['user_email'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Display Name', 'videohub360-theme' ); ?></th>
						<td><?php echo esc_html( $wp['display_name'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'First Name', 'videohub360-theme' ); ?></th>
						<td><?php echo $wp['first_name'] !== '' ? esc_html( $wp['first_name'] ) : '&mdash;'; ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Last Name', 'videohub360-theme' ); ?></th>
						<td><?php echo $wp['last_name'] !== '' ? esc_html( $wp['last_name'] ) : '&mdash;'; ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Website URL', 'videohub360-theme' ); ?></th>
						<td>
							<?php if ( $wp['user_url'] ) : ?>
								<a href="<?php echo esc_url( $wp['user_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $wp['user_url'] ); ?></a>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Bio / Description', 'videohub360-theme' ); ?></th>
						<td>
							<?php
							if ( $wp['description'] !== '' ) {
								echo nl2br( esc_html( $wp['description'] ) );
							} else {
								echo '&mdash;';
							}
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Registered Date', 'videohub360-theme' ); ?></th>
						<td><?php echo esc_html( $wp['user_registered'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Roles', 'videohub360-theme' ); ?></th>
						<td><?php echo ! empty( $wp['roles'] ) ? esc_html( implode( ', ', $wp['roles'] ) ) : '&mdash;'; ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- B. Videohub360 Profile Info -->
		<div class="vh360-admin-card">
			<h2><?php esc_html_e( 'Videohub360 Profile Info', 'videohub360-theme' ); ?></h2>
			<table class="widefat fixed striped" style="margin-top:0;">
				<tbody>
					<?php foreach ( $profile_data['vh360_meta'] as $meta_key => $info ) : ?>
						<?php
						$value = $info['value'];
						// Detect social link fields and make them clickable.
						$social_keys = array( '_vh360_twitter', '_vh360_facebook', '_vh360_youtube', '_vh360_instagram', '_vh360_linkedin', '_vh360_tiktok', '_vh360_twitch' );
						$is_url      = in_array( $meta_key, $social_keys, true ) || $meta_key === '_vh360_website';
						?>
						<tr>
							<th style="width:220px;"><?php echo esc_html( $info['label'] ); ?></th>
							<td>
								<?php
								if ( $value !== '' ) {
									if ( $is_url && filter_var( $value, FILTER_VALIDATE_URL ) ) {
										echo '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $value ) . '</a>';
									} else {
										echo esc_html( $value );
									}
								} else {
									echo '&mdash;';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- D. Built-in VH360 Profile Fields -->
		<?php if ( ! empty( $profile_data['builtin_fields'] ) ) : ?>
			<div class="vh360-admin-card">
				<h2><?php esc_html_e( 'Built-in VH360 Profile Fields', 'videohub360-theme' ); ?></h2>
				<table class="widefat fixed striped" style="margin-top:0;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Field Label', 'videohub360-theme' ); ?></th>
							<th><?php esc_html_e( 'Meta Key', 'videohub360-theme' ); ?></th>
							<th><?php esc_html_e( 'Value', 'videohub360-theme' ); ?></th>
							<th><?php esc_html_e( 'Visibility', 'videohub360-theme' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $profile_data['builtin_fields'] as $field ) : ?>
							<tr>
								<td><?php echo esc_html( $field['label'] ); ?></td>
								<td><code><?php echo esc_html( $field['meta_key'] ); ?></code></td>
								<td>
									<?php
									$type  = isset( $field['type'] ) ? $field['type'] : 'text';
									$value = $field['value'];
									if ( 'textarea' === $type && $value !== '' ) {
										echo nl2br( esc_html( $value ) );
									} elseif ( 'url' === $type && $value !== '' && filter_var( $value, FILTER_VALIDATE_URL ) ) {
										echo '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $value ) . '</a>';
									} else {
										// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped inside helper
										echo $admin->format_profile_value_for_admin( $value, $type );
									}
									?>
								</td>
								<td>
									<?php if ( null !== $field['visibility'] ) : ?>
										<span class="vh360-visibility-badge vh360-visibility-<?php echo esc_attr( strtolower( $field['visibility'] ) ); ?>">
											<?php echo esc_html( $field['visibility'] ); ?>
										</span>
									<?php else : ?>
										&mdash;
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<!-- E. Custom Profile Fields -->
		<?php if ( ! empty( $profile_data['custom_fields'] ) ) : ?>
			<div class="vh360-admin-card">
				<h2><?php esc_html_e( 'Custom Profile Fields', 'videohub360-theme' ); ?></h2>
				<table class="widefat fixed striped" style="margin-top:0;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Field Label', 'videohub360-theme' ); ?></th>
							<th><?php esc_html_e( 'Field Type', 'videohub360-theme' ); ?></th>
							<th><?php esc_html_e( 'Field ID', 'videohub360-theme' ); ?></th>
							<th><?php esc_html_e( 'Meta Key', 'videohub360-theme' ); ?></th>
							<th><?php esc_html_e( 'Value', 'videohub360-theme' ); ?></th>
							<th><?php esc_html_e( 'Visibility', 'videohub360-theme' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $profile_data['custom_fields'] as $field ) : ?>
							<tr>
								<td><?php echo esc_html( $field['label'] ); ?></td>
								<td><?php echo esc_html( $field['type'] ); ?></td>
								<td><?php echo esc_html( $field['field_id'] ); ?></td>
								<td><code><?php echo esc_html( $field['meta_key'] ); ?></code></td>
								<td>
									<?php
									$type  = isset( $field['type'] ) ? $field['type'] : 'text';
									$value = $field['value'];
									if ( 'textarea' === $type && $value !== '' ) {
										echo nl2br( esc_html( $value ) );
									} elseif ( 'url' === $type && $value !== '' && filter_var( $value, FILTER_VALIDATE_URL ) ) {
										echo '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $value ) . '</a>';
									} else {
										// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped inside helper
										echo $admin->format_profile_value_for_admin( $value, $type );
									}
									?>
								</td>
								<td>
									<?php if ( null !== $field['visibility'] ) : ?>
										<span class="vh360-visibility-badge vh360-visibility-<?php echo esc_attr( strtolower( $field['visibility'] ) ); ?>">
											<?php echo esc_html( $field['visibility'] ); ?>
										</span>
									<?php else : ?>
										&mdash;
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php elseif ( empty( $profile_data['custom_fields'] ) ) : ?>
			<div class="vh360-admin-card">
				<h2><?php esc_html_e( 'Custom Profile Fields', 'videohub360-theme' ); ?></h2>
				<p><?php esc_html_e( 'No custom profile fields have been created yet.', 'videohub360-theme' ); ?></p>
			</div>
		<?php endif; ?>

	</div><!-- .vh360-profile-data-detail -->

	<?php
	include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
	return;
}

// ============================================================
// LIST VIEW  (default: paginated users table)
// ============================================================

$page_title = __( 'Profile Data', 'videohub360-theme' );
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

// --- Filters from query string ---
$search_query  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$account_type  = isset( $_GET['account_type'] ) ? sanitize_key( $_GET['account_type'] ) : '';
$current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page      = 20;

// --- Build WP_User_Query args ---
$query_args = array(
	'number'  => $per_page,
	'offset'  => ( $current_page - 1 ) * $per_page,
	'orderby' => 'registered',
	'order'   => 'DESC',
	'count_total' => true,
);

if ( $search_query ) {
	$query_args['search']         = '*' . $search_query . '*';
	$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name', 'user_nicename' );
}

if ( $account_type ) {
	$query_args['meta_query'] = array(
		array(
			'key'     => '_vh360_account_type',
			'value'   => $account_type,
			'compare' => '=',
		),
	);
}

$user_query  = new WP_User_Query( $query_args );
$users       = $user_query->get_results();
$total_users = $user_query->get_total();
$total_pages = (int) ceil( $total_users / $per_page );

// --- Filled profile fields count helper (all VH360 meta keys) ---
$defs         = $admin->get_profile_export_field_definitions();
$all_vh360_keys = array_merge(
	array_keys( $defs['vh360_meta_columns'] ),
	array_keys( $defs['builtin_field_columns'] ),
	array_keys( $defs['custom_field_columns'] )
);

// --- Page base URL (without pagination) ---
$base_url_args = array( 'page' => 'vh360-profile-data' );
if ( $search_query ) {
	$base_url_args['s'] = $search_query;
}
if ( $account_type ) {
	$base_url_args['account_type'] = $account_type;
}
$base_url = admin_url( 'admin.php?' . http_build_query( $base_url_args ) );

$account_types = array(
	''             => __( 'All Account Types', 'videohub360-theme' ),
	'creator'      => __( 'Creator', 'videohub360-theme' ),
	'client'       => __( 'Client', 'videohub360-theme' ),
	'professional' => __( 'Professional', 'videohub360-theme' ),
	'organization' => __( 'Organization', 'videohub360-theme' ),
);
?>

<div class="vh360-profile-data-list">

	<!-- Top toolbar: CSV export -->
	<div class="vh360-profile-data-toolbar" style="margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vh360_export_user_profiles' ), 'vh360_export_user_profiles' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Export User Profile Data CSV', 'videohub360-theme' ); ?>
		</a>
	</div>

	<!-- Search & Filters -->
	<div class="vh360-admin-card" style="padding:16px 20px;">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="vh360-profile-data">
			<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
				<input
					type="search"
					name="s"
					value="<?php echo esc_attr( $search_query ); ?>"
					placeholder="<?php esc_attr_e( 'Search by username, email, display name…', 'videohub360-theme' ); ?>"
					style="min-width:280px;"
					class="regular-text"
				>
				<select name="account_type">
					<?php foreach ( $account_types as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $account_type, $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="button"><?php esc_html_e( 'Apply Filters', 'videohub360-theme' ); ?></button>
				<?php if ( $search_query || $account_type ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=vh360-profile-data' ) ); ?>" class="button">
						<?php esc_html_e( 'Reset', 'videohub360-theme' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</form>
	</div>

	<!-- Results count + pagination (top) -->
	<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
		<p style="margin:0;">
			<?php
			printf(
				/* translators: %s: number of users */
				esc_html( _n( '%s user found', '%s users found', $total_users, 'videohub360-theme' ) ),
				esc_html( number_format_i18n( $total_users ) )
			);
			?>
		</p>
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav-pages">
				<?php if ( $current_page > 1 ) : ?>
					<a class="button" href="<?php echo esc_url( $base_url . '&paged=' . ( $current_page - 1 ) ); ?>">&laquo; <?php esc_html_e( 'Previous', 'videohub360-theme' ); ?></a>
				<?php endif; ?>
				<span style="padding:0 8px;">
					<?php
					printf(
						/* translators: 1: current page, 2: total pages */
						esc_html__( 'Page %1$d of %2$d', 'videohub360-theme' ),
						esc_html( $current_page ),
						esc_html( $total_pages )
					);
					?>
				</span>
				<?php if ( $current_page < $total_pages ) : ?>
					<a class="button" href="<?php echo esc_url( $base_url . '&paged=' . ( $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'videohub360-theme' ); ?> &raquo;</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- Users Table -->
	<table class="widefat striped" style="border-collapse:collapse;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'User', 'videohub360-theme' ); ?></th>
				<th><?php esc_html_e( 'Email', 'videohub360-theme' ); ?></th>
				<th><?php esc_html_e( 'Account Type', 'videohub360-theme' ); ?></th>
				<th><?php esc_html_e( 'Display Name', 'videohub360-theme' ); ?></th>
				<th><?php esc_html_e( 'Registered', 'videohub360-theme' ); ?></th>
				<th><?php esc_html_e( 'Filled Fields', 'videohub360-theme' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'videohub360-theme' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $users ) ) : ?>
				<tr>
					<td colspan="7" style="text-align:center;padding:20px;">
						<?php esc_html_e( 'No users found.', 'videohub360-theme' ); ?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $users as $user ) : ?>
					<?php
					$user_account_type = get_user_meta( $user->ID, '_vh360_account_type', true );
					$account_label     = isset( $account_types[ $user_account_type ] ) && $user_account_type
						? $account_types[ $user_account_type ]
						: '&mdash;';

					// Count filled VH360 profile fields for this user.
					$filled_count = 0;
					foreach ( $all_vh360_keys as $key ) {
						$val = get_user_meta( $user->ID, $key, true );
						if ( '' !== $val && null !== $val ) {
							$filled_count++;
						}
					}

					$detail_url   = admin_url( 'admin.php?page=vh360-profile-data&user_id=' . $user->ID );
					$edit_user_url = admin_url( 'user-edit.php?user_id=' . $user->ID );
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $user->user_login ); ?></a></strong>
							<br><span style="color:#646970;font-size:12px;">#<?php echo esc_html( $user->ID ); ?></span>
						</td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td>
							<?php if ( $user_account_type ) : ?>
								<span class="vh360-account-type-badge vh360-account-type-<?php echo esc_attr( $user_account_type ); ?>">
									<?php echo esc_html( $account_label ); ?>
								</span>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td><?php echo $user->display_name ? esc_html( $user->display_name ) : '&mdash;'; ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?></td>
						<td><?php echo esc_html( $filled_count ); ?></td>
						<td>
							<a href="<?php echo esc_url( $detail_url ); ?>" class="button button-small">
								<?php esc_html_e( 'View Details', 'videohub360-theme' ); ?>
							</a>
							<?php if ( current_user_can( 'edit_users' ) ) : ?>
								<a href="<?php echo esc_url( $edit_user_url ); ?>" class="button button-small" style="margin-left:4px;">
									<?php esc_html_e( 'Edit User', 'videohub360-theme' ); ?>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Pagination (bottom) -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav" style="margin-top:12px;text-align:right;">
			<?php if ( $current_page > 1 ) : ?>
				<a class="button" href="<?php echo esc_url( $base_url . '&paged=' . ( $current_page - 1 ) ); ?>">&laquo; <?php esc_html_e( 'Previous', 'videohub360-theme' ); ?></a>
			<?php endif; ?>
			<span style="padding:0 8px;">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages */
					esc_html__( 'Page %1$d of %2$d', 'videohub360-theme' ),
					esc_html( $current_page ),
					esc_html( $total_pages )
				);
				?>
			</span>
			<?php if ( $current_page < $total_pages ) : ?>
				<a class="button" href="<?php echo esc_url( $base_url . '&paged=' . ( $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'videohub360-theme' ); ?> &raquo;</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

</div><!-- .vh360-profile-data-list -->

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
