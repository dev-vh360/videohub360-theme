<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Push Token Manager
 * 
 * Manages device tokens for native push notifications (iOS APNs + Android FCM).
 * Handles CRUD operations, validation, and lifecycle management.
 */
class VH360_PWA_Push_Token_Manager {
	/** @var string */
	private $table_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'vh360_push_tokens';
	}

	/**
	 * Register or update a device token
	 * 
	 * @param array $data {
	 *     @type string $device_token Required
	 *     @type string $platform 'ios' or 'android'
	 *     @type int $user_id Optional
	 *     @type string $wrapper_type Optional (capacitor, cordova, flutter, react-native)
	 *     @type array $device_info Optional
	 *     @type string $app_version Optional
	 * }
	 * @return int|false Token ID on success, false on failure
	 */
	public function register_token( $data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $data['device_token'] ) || empty( $data['platform'] ) ) {
			return false;
		}

		$device_token = sanitize_text_field( $data['device_token'] );
		$platform = sanitize_text_field( $data['platform'] );

		// Validate platform
		if ( ! in_array( $platform, array( 'ios', 'android' ), true ) ) {
			return false;
		}

		// Validate token format
		if ( ! $this->validate_token_format( $device_token, $platform ) ) {
			return false;
		}

		// Check if token already exists
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_name} WHERE device_token = %s AND platform = %s LIMIT 1",
				$device_token,
				$platform
			)
		);

		$token_data = array(
			'device_token' => $device_token,
			'platform'     => $platform,
			'last_active'  => current_time( 'mysql' ),
			'is_active'    => 1,
		);

		if ( ! empty( $data['user_id'] ) ) {
			$token_data['user_id'] = absint( $data['user_id'] );
		}

		if ( ! empty( $data['wrapper_type'] ) ) {
			$token_data['wrapper_type'] = sanitize_text_field( $data['wrapper_type'] );
		}

		if ( ! empty( $data['device_info'] ) && is_array( $data['device_info'] ) ) {
			$token_data['device_info'] = wp_json_encode( $data['device_info'] );
		}

		if ( ! empty( $data['app_version'] ) ) {
			$token_data['app_version'] = sanitize_text_field( $data['app_version'] );
		}

		if ( $existing ) {
			// Update existing token - don't pass format array, let wpdb handle it
			$result = $wpdb->update(
				$this->table_name,
				$token_data,
				array( 'id' => $existing->id )
			);
			return $result !== false ? absint( $existing->id ) : false;
		} else {
			// Insert new token - don't pass format array, let wpdb handle it
			$token_data['created_at'] = current_time( 'mysql' );
			$result = $wpdb->insert(
				$this->table_name,
				$token_data
			);
			return $result ? absint( $wpdb->insert_id ) : false;
		}
	}

	/**
	 * Update token last_active timestamp
	 * 
	 * @param int $token_id
	 * @return bool
	 */
	public function update_last_active( $token_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'last_active' => current_time( 'mysql' ) ),
			array( 'id' => absint( $token_id ) ),
			array( '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Link token to user (called on wp_login)
	 * 
	 * @param string $device_token
	 * @param int $user_id
	 * @return bool
	 */
	public function link_token_to_user( $device_token, $user_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'user_id' => absint( $user_id ) ),
			array( 'device_token' => sanitize_text_field( $device_token ) ),
			array( '%d' ),
			array( '%s' )
		);

		return $result !== false;
	}

	/**
	 * Get active tokens
	 * 
	 * @param array $args {
	 *     @type int $user_id Optional
	 *     @type string $platform Optional 'ios' or 'android'
	 *     @type bool $active_only Default true
	 * }
	 * @return array Array of token objects
	 */
	public function get_tokens( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'user_id'     => null,
			'platform'    => null,
			'active_only' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$prepare_values = array();

		if ( null !== $args['user_id'] ) {
			$where[] = 'user_id = %d';
			$prepare_values[] = absint( $args['user_id'] );
		}

		if ( null !== $args['platform'] ) {
			$where[] = 'platform = %s';
			$prepare_values[] = sanitize_text_field( $args['platform'] );
		}

		if ( $args['active_only'] ) {
			$where[] = 'is_active = 1';
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY last_active DESC";

		if ( ! empty( $prepare_values ) ) {
			$query = $wpdb->prepare( $query, $prepare_values );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Get tokens for a specific user
	 * 
	 * @param int $user_id
	 * @return array
	 */
	public function get_user_tokens( $user_id ) {
		return $this->get_tokens( array( 'user_id' => absint( $user_id ) ) );
	}

	/**
	 * Get single token by ID
	 * 
	 * @param int $token_id
	 * @return object|null
	 */
	public function get_token_by_id( $token_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d LIMIT 1",
				absint( $token_id )
			)
		);
	}

	/**
	 * Deactivate token (soft delete)
	 * 
	 * @param int $token_id
	 * @return bool
	 */
	public function deactivate_token( $token_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'is_active' => 0 ),
			array( 'id' => absint( $token_id ) ),
			array( '%d' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Deactivate token by device_token string
	 * 
	 * @param string $device_token
	 * @param string $platform
	 * @return bool
	 */
	public function deactivate_token_by_string( $device_token, $platform ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'is_active' => 0 ),
			array(
				'device_token' => sanitize_text_field( $device_token ),
				'platform'     => sanitize_text_field( $platform ),
			),
			array( '%d' ),
			array( '%s', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Reactivate token
	 * 
	 * @param int $token_id
	 * @return bool
	 */
	public function reactivate_token( $token_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'is_active' => 1 ),
			array( 'id' => absint( $token_id ) ),
			array( '%d' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete token permanently
	 * 
	 * @param int $token_id
	 * @return bool
	 */
	public function delete_token( $token_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => absint( $token_id ) ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete inactive tokens older than X days
	 * 
	 * @param int $days Default 90
	 * @return int Number of tokens deleted
	 */
	public function cleanup_old_tokens( $days = 90 ) {
		global $wpdb;

		$days = absint( $days );
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE is_active = 0 AND last_active < %s",
				$cutoff_date
			)
		);

		return $result !== false ? $result : 0;
	}

	/**
	 * Validate token format
	 * 
	 * @param string $token
	 * @param string $platform
	 * @return bool
	 */
	public function validate_token_format( $token, $platform ) {
		$token = trim( $token );

		if ( empty( $token ) ) {
			return false;
		}

		if ( 'ios' === $platform ) {
			// iOS APNs: 64 hexadecimal characters
			return (bool) preg_match( '/^[a-f0-9]{64}$/i', $token );
		} elseif ( 'android' === $platform ) {
			// Android FCM: 152+ alphanumeric characters with underscores/hyphens/colons
			return strlen( $token ) >= 140 && preg_match( '/^[a-zA-Z0-9_:\-]+$/', $token );
		}

		return false;
	}

	/**
	 * Get token statistics
	 * 
	 * @return array {
	 *     @type int $total
	 *     @type int $active
	 *     @type int $inactive
	 *     @type int $ios
	 *     @type int $android
	 *     @type int $with_user
	 *     @type int $without_user
	 * }
	 */
	public function get_statistics() {
		global $wpdb;

		$stats = array(
			'total'        => 0,
			'active'       => 0,
			'inactive'     => 0,
			'ios'          => 0,
			'android'      => 0,
			'with_user'    => 0,
			'without_user' => 0,
		);

		// Total tokens
		$stats['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

		// Active tokens
		$stats['active'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1"
		);

		// Inactive tokens
		$stats['inactive'] = $stats['total'] - $stats['active'];

		// iOS tokens
		$stats['ios'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE platform = %s AND is_active = 1",
				'ios'
			)
		);

		// Android tokens
		$stats['android'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE platform = %s AND is_active = 1",
				'android'
			)
		);

		// Tokens with user
		$stats['with_user'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE user_id IS NOT NULL AND is_active = 1"
		);

		// Tokens without user
		$stats['without_user'] = $stats['active'] - $stats['with_user'];

		return $stats;
	}

	/**
	 * Create database table
	 * 
	 * @return bool
	 */
	public static function create_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'vh360_push_tokens';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NULL,
			device_token TEXT NOT NULL,
			platform ENUM('ios', 'android') NOT NULL,
			wrapper_type VARCHAR(50) NULL COMMENT 'capacitor, cordova, flutter, react-native',
			device_info JSON NULL COMMENT 'Device details from app',
			app_version VARCHAR(20) NULL,
			created_at DATETIME NOT NULL,
			last_active DATETIME NOT NULL,
			is_active TINYINT(1) DEFAULT 1,
			PRIMARY KEY (id),
			INDEX idx_user_id (user_id),
			INDEX idx_platform (platform),
			INDEX idx_last_active (last_active),
			INDEX idx_is_active (is_active),
			UNIQUE KEY unique_token_platform (device_token(191), platform)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Store database version
		update_option( 'vh360_pwa_push_tokens_db_version', '1.0' );

		return true;
	}

	/**
	 * Check if table exists
	 * 
	 * @return bool
	 */
	public function table_exists() {
		global $wpdb;
		$table_name = $this->table_name;
		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}
}
