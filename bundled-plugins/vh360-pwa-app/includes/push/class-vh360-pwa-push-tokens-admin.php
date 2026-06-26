<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not already loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Push Tokens List Table
 */
class VH360_Push_Tokens_List_Table extends WP_List_Table {
	/** @var VH360_PWA_Push_Token_Manager */
	private $token_manager;

	/**
	 * Constructor
	 */
	public function __construct( $token_manager ) {
		$this->token_manager = $token_manager;

		parent::__construct(
			array(
				'singular' => 'token',
				'plural'   => 'tokens',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get columns
	 * 
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'id'          => __( 'ID', 'vh360-pwa-app' ),
			'platform'    => __( 'Platform', 'vh360-pwa-app' ),
			'user'        => __( 'User', 'vh360-pwa-app' ),
			'device_info' => __( 'Device Info', 'vh360-pwa-app' ),
			'wrapper'     => __( 'Wrapper', 'vh360-pwa-app' ),
			'last_active' => __( 'Last Active', 'vh360-pwa-app' ),
			'created_at'  => __( 'Created', 'vh360-pwa-app' ),
			'status'      => __( 'Status', 'vh360-pwa-app' ),
		);
	}

	/**
	 * Get sortable columns
	 * 
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'id'          => array( 'id', true ),
			'platform'    => array( 'platform', false ),
			'user'        => array( 'user_id', false ),
			'last_active' => array( 'last_active', false ),
			'created_at'  => array( 'created_at', false ),
			'status'      => array( 'is_active', false ),
		);
	}

	/**
	 * Get bulk actions
	 * 
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'deactivate' => __( 'Deactivate', 'vh360-pwa-app' ),
			'activate'   => __( 'Activate', 'vh360-pwa-app' ),
			'delete'     => __( 'Delete Permanently', 'vh360-pwa-app' ),
		);
	}

	/**
	 * Column checkbox
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="token_ids[]" value="%d" />', $item->id );
	}

	/**
	 * Column ID
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_id( $item ) {
		$page_param = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
		
		$actions = array(
			'view' => sprintf(
				'<a href="#" class="vh360-view-token" data-token-id="%d">%s</a>',
				$item->id,
				__( 'View', 'vh360-pwa-app' )
			),
		);

		if ( $item->is_active ) {
			$actions['deactivate'] = sprintf(
				'<a href="?page=%s&action=deactivate&token_id=%d&_wpnonce=%s">%s</a>',
				esc_attr( $page_param ),
				$item->id,
				wp_create_nonce( 'deactivate_token_' . $item->id ),
				__( 'Deactivate', 'vh360-pwa-app' )
			);
		} else {
			$actions['activate'] = sprintf(
				'<a href="?page=%s&action=activate&token_id=%d&_wpnonce=%s">%s</a>',
				esc_attr( $page_param ),
				$item->id,
				wp_create_nonce( 'activate_token_' . $item->id ),
				__( 'Activate', 'vh360-pwa-app' )
			);
		}

		$actions['delete'] = sprintf(
			'<a href="?page=%s&action=delete&token_id=%d&_wpnonce=%s" class="submitdelete">%s</a>',
			esc_attr( $page_param ),
			$item->id,
			wp_create_nonce( 'delete_token_' . $item->id ),
			__( 'Delete', 'vh360-pwa-app' )
		);

		return sprintf( '<strong>%d</strong> %s', $item->id, $this->row_actions( $actions ) );
	}

	/**
	 * Column platform
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_platform( $item ) {
		$icon = 'ios' === $item->platform ? '🍎' : '🤖';
		return sprintf( '%s %s', $icon, esc_html( ucfirst( $item->platform ) ) );
	}

	/**
	 * Column user
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_user( $item ) {
		if ( $item->user_id ) {
			$user = get_userdata( $item->user_id );
			if ( $user ) {
				return sprintf(
					'<a href="%s">%s</a>',
					get_edit_user_link( $item->user_id ),
					esc_html( $user->user_login )
				);
			}
			return sprintf( __( 'User #%d', 'vh360-pwa-app' ), $item->user_id );
		}
		return '<em>' . __( 'Guest', 'vh360-pwa-app' ) . '</em>';
	}

	/**
	 * Column device_info
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_device_info( $item ) {
		if ( $item->device_info ) {
			$info = json_decode( $item->device_info, true );
			if ( is_array( $info ) ) {
				$model = $info['model'] ?? '';
				$os_version = $info['os_version'] ?? '';
				if ( $model || $os_version ) {
					return esc_html( trim( "$model $os_version" ) );
				}
			}
		}
		return '—';
	}

	/**
	 * Column wrapper
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_wrapper( $item ) {
		if ( $item->wrapper_type ) {
			return '<code>' . esc_html( $item->wrapper_type ) . '</code>';
		}
		if ( $item->app_version ) {
			return '<small>' . esc_html( $item->app_version ) . '</small>';
		}
		return '—';
	}

	/**
	 * Column last_active
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_last_active( $item ) {
		$timestamp = strtotime( $item->last_active );
		$time_diff = human_time_diff( $timestamp, current_time( 'timestamp' ) );
		return sprintf(
			'<abbr title="%s">%s ago</abbr>',
			esc_attr( $item->last_active ),
			esc_html( $time_diff )
		);
	}

	/**
	 * Column created_at
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_created_at( $item ) {
		return esc_html( mysql2date( 'Y-m-d', $item->created_at ) );
	}

	/**
	 * Column status
	 * 
	 * @param object $item
	 * @return string
	 */
	public function column_status( $item ) {
		if ( $item->is_active ) {
			return '<span class="vh360-status-badge vh360-status-active">✓ ' . __( 'Active', 'vh360-pwa-app' ) . '</span>';
		}
		return '<span class="vh360-status-badge vh360-status-inactive">○ ' . __( 'Inactive', 'vh360-pwa-app' ) . '</span>';
	}

	/**
	 * Prepare items
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = 20;
		$current_page = $this->get_pagenum();

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Handle bulk actions
		$this->process_bulk_action();

		// Build query
		$table_name = $wpdb->prefix . 'vh360_push_tokens';
		$where = array( '1=1' );

		// Filter by platform
		if ( ! empty( $_GET['platform'] ) && in_array( $_GET['platform'], array( 'ios', 'android' ), true ) ) {
			$where[] = $wpdb->prepare( 'platform = %s', sanitize_text_field( $_GET['platform'] ) );
		}

		// Filter by status
		if ( isset( $_GET['status'] ) ) {
			if ( 'active' === $_GET['status'] ) {
				$where[] = 'is_active = 1';
			} elseif ( 'inactive' === $_GET['status'] ) {
				$where[] = 'is_active = 0';
			}
		}

		// Search by user
		if ( ! empty( $_GET['s'] ) ) {
			$search = sanitize_text_field( $_GET['s'] );
			// Search by user ID or email
			$user = get_user_by( 'email', $search );
			if ( $user ) {
				$where[] = $wpdb->prepare( 'user_id = %d', $user->ID );
			} elseif ( is_numeric( $search ) ) {
				$where[] = $wpdb->prepare( 'user_id = %d', absint( $search ) );
			}
		}

		$where_clause = implode( ' AND ', $where );

		// Order
		$orderby = ! empty( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';
		$order = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

		$allowed_orderby = array( 'id', 'platform', 'user_id', 'last_active', 'created_at', 'is_active' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'id';
		}

		if ( ! in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		// Get total items
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}" );

		// Get items
		$offset = ( $current_page - 1 ) * $per_page;
		$this->items = $wpdb->get_results(
			"SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT {$per_page} OFFSET {$offset}"
		);

		// Set pagination
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Process bulk actions
	 */
	public function process_bulk_action() {
		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		// Handle single actions
		if ( in_array( $action, array( 'deactivate', 'activate', 'delete' ), true ) ) {
			if ( empty( $_GET['token_id'] ) ) {
				return;
			}

			$token_id = absint( $_GET['token_id'] );
			check_admin_referer( $action . '_token_' . $token_id );

			if ( 'deactivate' === $action ) {
				$this->token_manager->deactivate_token( $token_id );
				wp_safe_redirect( remove_query_arg( array( 'action', 'token_id', '_wpnonce' ) ) );
				exit;
			} elseif ( 'activate' === $action ) {
				$this->token_manager->reactivate_token( $token_id );
				wp_safe_redirect( remove_query_arg( array( 'action', 'token_id', '_wpnonce' ) ) );
				exit;
			} elseif ( 'delete' === $action ) {
				$this->token_manager->delete_token( $token_id );
				wp_safe_redirect( remove_query_arg( array( 'action', 'token_id', '_wpnonce' ) ) );
				exit;
			}
		}

		// Handle bulk actions
		if ( empty( $_GET['token_ids'] ) || ! is_array( $_GET['token_ids'] ) ) {
			return;
		}

		check_admin_referer( 'bulk-tokens' );

		$token_ids = array_map( 'absint', $_GET['token_ids'] );

		foreach ( $token_ids as $token_id ) {
			if ( 'deactivate' === $action ) {
				$this->token_manager->deactivate_token( $token_id );
			} elseif ( 'activate' === $action ) {
				$this->token_manager->reactivate_token( $token_id );
			} elseif ( 'delete' === $action ) {
				$this->token_manager->delete_token( $token_id );
			}
		}

		wp_safe_redirect( remove_query_arg( array( 'action', 'action2', 'token_ids', '_wpnonce', '_wp_http_referer' ) ) );
		exit;
	}

	/**
	 * Display filters
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$platform = isset( $_GET['platform'] ) ? sanitize_text_field( $_GET['platform'] ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

		echo '<div class="alignleft actions">';

		// Platform filter
		echo '<select name="platform">';
		echo '<option value="">' . esc_html__( 'All Platforms', 'vh360-pwa-app' ) . '</option>';
		echo '<option value="ios"' . selected( $platform, 'ios', false ) . '>' . esc_html__( 'iOS', 'vh360-pwa-app' ) . '</option>';
		echo '<option value="android"' . selected( $platform, 'android', false ) . '>' . esc_html__( 'Android', 'vh360-pwa-app' ) . '</option>';
		echo '</select>';

		// Status filter
		echo '<select name="status">';
		echo '<option value="">' . esc_html__( 'All Statuses', 'vh360-pwa-app' ) . '</option>';
		echo '<option value="active"' . selected( $status, 'active', false ) . '>' . esc_html__( 'Active', 'vh360-pwa-app' ) . '</option>';
		echo '<option value="inactive"' . selected( $status, 'inactive', false ) . '>' . esc_html__( 'Inactive', 'vh360-pwa-app' ) . '</option>';
		echo '</select>';

		submit_button( __( 'Filter', 'vh360-pwa-app' ), '', 'filter_action', false );

		echo '</div>';
	}
}

/**
 * Push Tokens Admin
 * 
 * Handles admin UI for device token management.
 */
class VH360_Push_Tokens_Admin {
	/** @var VH360_PWA_Push_Token_Manager */
	private $token_manager;

	/**
	 * Constructor
	 * 
	 * @param VH360_PWA_Push_Token_Manager $token_manager
	 */
	public function __construct( $token_manager ) {
		$this->token_manager = $token_manager;
	}

	/**
	 * Register hooks
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_vh360_get_token_details', array( $this, 'ajax_get_token_details' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'vh360-pwa-push',
			__( 'Device Tokens', 'vh360-pwa-app' ),
			__( 'Device Tokens', 'vh360-pwa-app' ),
			'manage_options',
			'vh360-pwa-push-tokens',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'vh360-pwa-push-tokens' ) ) {
			return;
		}

		wp_enqueue_style(
			'vh360-pwa-push-tokens',
			VH360_PWA_APP_URL . 'assets/admin/push-tokens.css',
			array(),
			vh360_pwa_app_asset_version('assets/admin/push-tokens.css')
		);

		wp_enqueue_script(
			'vh360-pwa-push-tokens',
			VH360_PWA_APP_URL . 'assets/admin/push-tokens.js',
			array( 'jquery' ),
			vh360_pwa_app_asset_version('assets/admin/push-tokens.js'),
			true
		);

		wp_localize_script(
			'vh360-pwa-push-tokens',
			'VH360PushTokens',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vh360_pwa_push_tokens' ),
			)
		);
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap vh360-push-tokens-admin">';
		echo '<h1>' . esc_html__( 'Device Tokens', 'vh360-pwa-app' ) . '</h1>';

		// Statistics
		$this->render_statistics();

		// List table
		$list_table = new VH360_Push_Tokens_List_Table( $this->token_manager );
		$list_table->prepare_items();

		echo '<form method="get">';
		$page_param = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : 'vh360-pwa-push-tokens';
		echo '<input type="hidden" name="page" value="' . esc_attr( $page_param ) . '" />';
		$list_table->search_box( __( 'Search by User', 'vh360-pwa-app' ), 'token' );
		$list_table->display();
		echo '</form>';

		// Token detail modal
		$this->render_token_modal();

		echo '</div>';
	}

	/**
	 * Render statistics
	 */
	private function render_statistics() {
		$stats = $this->token_manager->get_statistics();

		echo '<div class="vh360-push-tokens-stats">';
		echo '<div class="vh360-stats-grid">';

		echo '<div class="vh360-stat-box">';
		echo '<div class="vh360-stat-value">' . number_format_i18n( $stats['total'] ) . '</div>';
		echo '<div class="vh360-stat-label">' . esc_html__( 'Total Tokens', 'vh360-pwa-app' ) . '</div>';
		echo '</div>';

		echo '<div class="vh360-stat-box">';
		echo '<div class="vh360-stat-value vh360-stat-success">' . number_format_i18n( $stats['active'] ) . '</div>';
		echo '<div class="vh360-stat-label">' . esc_html__( 'Active Tokens', 'vh360-pwa-app' ) . '</div>';
		echo '</div>';

		echo '<div class="vh360-stat-box">';
		echo '<div class="vh360-stat-value">' . number_format_i18n( $stats['ios'] ) . '</div>';
		echo '<div class="vh360-stat-label">🍎 ' . esc_html__( 'iOS Tokens', 'vh360-pwa-app' ) . '</div>';
		echo '</div>';

		echo '<div class="vh360-stat-box">';
		echo '<div class="vh360-stat-value">' . number_format_i18n( $stats['android'] ) . '</div>';
		echo '<div class="vh360-stat-label">🤖 ' . esc_html__( 'Android Tokens', 'vh360-pwa-app' ) . '</div>';
		echo '</div>';

		echo '<div class="vh360-stat-box">';
		echo '<div class="vh360-stat-value">' . number_format_i18n( $stats['inactive'] ) . '</div>';
		echo '<div class="vh360-stat-label">' . esc_html__( 'Inactive Tokens', 'vh360-pwa-app' ) . '</div>';
		echo '</div>';

		echo '<div class="vh360-stat-box">';
		echo '<div class="vh360-stat-value">' . number_format_i18n( $stats['without_user'] ) . '</div>';
		echo '<div class="vh360-stat-label">' . esc_html__( 'Guest Tokens', 'vh360-pwa-app' ) . '</div>';
		echo '</div>';

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render token modal
	 */
	private function render_token_modal() {
		?>
		<div id="vh360-token-modal" class="vh360-modal" style="display:none;">
			<div class="vh360-modal-content">
				<span class="vh360-modal-close">&times;</span>
				<h2><?php esc_html_e( 'Token Details', 'vh360-pwa-app' ); ?></h2>
				<div id="vh360-token-modal-body">
					<div class="vh360-modal-loading"><?php esc_html_e( 'Loading...', 'vh360-pwa-app' ); ?></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Get token details
	 */
	public function ajax_get_token_details() {
		check_ajax_referer( 'vh360_pwa_push_tokens', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vh360-pwa-app' ) ) );
		}

		$token_id = isset( $_POST['token_id'] ) ? absint( $_POST['token_id'] ) : 0;
		if ( ! $token_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid token ID.', 'vh360-pwa-app' ) ) );
		}

		$token = $this->token_manager->get_token_by_id( $token_id );
		if ( ! $token ) {
			wp_send_json_error( array( 'message' => __( 'Token not found.', 'vh360-pwa-app' ) ) );
		}

		// Format token details
		$html = '<table class="vh360-token-details-table">';

		$html .= '<tr><th>' . esc_html__( 'ID', 'vh360-pwa-app' ) . '</th><td>' . esc_html( $token->id ) . '</td></tr>';
		$html .= '<tr><th>' . esc_html__( 'Platform', 'vh360-pwa-app' ) . '</th><td>' . esc_html( ucfirst( $token->platform ) ) . '</td></tr>';

		if ( $token->user_id ) {
			$user = get_userdata( $token->user_id );
			$user_display = $user ? $user->user_login . ' (' . $user->user_email . ')' : 'User #' . $token->user_id;
			$html .= '<tr><th>' . esc_html__( 'User', 'vh360-pwa-app' ) . '</th><td>' . esc_html( $user_display ) . '</td></tr>';
		}

		$html .= '<tr><th>' . esc_html__( 'Device Token', 'vh360-pwa-app' ) . '</th><td><code style="word-break:break-all;">' . esc_html( substr( $token->device_token, 0, 50 ) ) . '...</code></td></tr>';

		if ( $token->wrapper_type ) {
			$html .= '<tr><th>' . esc_html__( 'Wrapper Type', 'vh360-pwa-app' ) . '</th><td>' . esc_html( $token->wrapper_type ) . '</td></tr>';
		}

		if ( $token->app_version ) {
			$html .= '<tr><th>' . esc_html__( 'App Version', 'vh360-pwa-app' ) . '</th><td>' . esc_html( $token->app_version ) . '</td></tr>';
		}

		if ( $token->device_info ) {
			$device_info = json_decode( $token->device_info, true );
			if ( is_array( $device_info ) ) {
				$html .= '<tr><th>' . esc_html__( 'Device Info', 'vh360-pwa-app' ) . '</th><td><pre style="margin:0;white-space:pre-wrap;">' . esc_html( wp_json_encode( $device_info, JSON_PRETTY_PRINT ) ) . '</pre></td></tr>';
			}
		}

		$html .= '<tr><th>' . esc_html__( 'Created', 'vh360-pwa-app' ) . '</th><td>' . esc_html( $token->created_at ) . '</td></tr>';
		$html .= '<tr><th>' . esc_html__( 'Last Active', 'vh360-pwa-app' ) . '</th><td>' . esc_html( $token->last_active ) . '</td></tr>';
		$html .= '<tr><th>' . esc_html__( 'Status', 'vh360-pwa-app' ) . '</th><td>' . ( $token->is_active ? '<span style="color:green;">✓ Active</span>' : '<span style="color:red;">○ Inactive</span>' ) . '</td></tr>';

		$html .= '</table>';

		// Action buttons
		$html .= '<div style="margin-top:20px;">';
		if ( $token->is_active ) {
			$html .= '<a href="?page=vh360-pwa-push-tokens&action=deactivate&token_id=' . $token->id . '&_wpnonce=' . wp_create_nonce( 'deactivate_token_' . $token->id ) . '" class="button">' . esc_html__( 'Deactivate Token', 'vh360-pwa-app' ) . '</a> ';
		} else {
			$html .= '<a href="?page=vh360-pwa-push-tokens&action=activate&token_id=' . $token->id . '&_wpnonce=' . wp_create_nonce( 'activate_token_' . $token->id ) . '" class="button">' . esc_html__( 'Activate Token', 'vh360-pwa-app' ) . '</a> ';
		}
		$html .= '<a href="?page=vh360-pwa-push-tokens&action=delete&token_id=' . $token->id . '&_wpnonce=' . wp_create_nonce( 'delete_token_' . $token->id ) . '" class="button button-secondary" onclick="return confirm(\'' . esc_js( __( 'Are you sure you want to delete this token permanently?', 'vh360-pwa-app' ) ) . '\');">' . esc_html__( 'Delete Permanently', 'vh360-pwa-app' ) . '</a>';
		$html .= '</div>';

		wp_send_json_success( array( 'html' => $html ) );
	}
}
