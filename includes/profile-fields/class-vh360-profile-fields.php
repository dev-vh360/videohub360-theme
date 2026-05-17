<?php
/**
 * VH360 Profile Fields Manager
 *
 * Centralized registry for profile field definitions, frontend rendering,
 * saving, sanitization, account-type targeting, and public About-section display.
 *
 * Built-in business fields preserve their existing meta keys and saved data.
 * Admin-created custom fields use the prefix: _vh360_custom_profile_{field_key}
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VH360_Profile_Fields – singleton manager class.
 */
class VH360_Profile_Fields {

	/** @var VH360_Profile_Fields|null Singleton instance. */
	private static $instance = null;

	/** @var array Built-in managed field definitions (defaults). */
	private $builtin_fields = array();

	/** @var array|null Admin-created custom field definitions (lazy-loaded). */
	private $custom_fields = null;

	/** @var array Admin overrides for built-in field visibility settings. */
	private $builtin_overrides = array();

	// ----------------------------------------------------------------
	// Singleton
	// ----------------------------------------------------------------

	/**
	 * Return the singleton instance.
	 *
	 * @return VH360_Profile_Fields
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Private constructor – use get_instance(). */
	private function __construct() {
		$this->builtin_overrides = get_option( 'vh360_builtin_field_settings', array() );
		if ( ! is_array( $this->builtin_overrides ) ) {
			$this->builtin_overrides = array();
		}
		$this->register_builtin_fields();
	}

	// ----------------------------------------------------------------
	// Field Registration
	// ----------------------------------------------------------------

	/**
	 * Register built-in managed fields.
	 *
	 * Meta keys are preserved exactly as they were hard-coded before,
	 * so all existing user data remains intact.
	 */
	private function register_builtin_fields() {
		$defaults = array(
			'business_name'        => array(
				'field_id'                => 'business_name',
				'label'                   => __( 'Business Name', 'videohub360-theme' ),
				'meta_key'                => '_vh360_business_name',
				'type'                    => 'text',
				'placeholder'             => __( 'Your business or practice name', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 10,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'business_type'        => array(
				'field_id'                => 'business_type',
				'label'                   => __( 'Business Type', 'videohub360-theme' ),
				'meta_key'                => '_vh360_business_type',
				'type'                    => 'text',
				'placeholder'             => __( 'e.g., Licensed Therapist, Consulting Firm', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 20,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'credentials'          => array(
				'field_id'                => 'credentials',
				'label'                   => __( 'Credentials', 'videohub360-theme' ),
				'meta_key'                => '_vh360_credentials',
				'type'                    => 'text',
				'placeholder'             => __( 'Professional credentials, certifications, licenses', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 30,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'specialties'          => array(
				'field_id'                => 'specialties',
				'label'                   => __( 'Specialties', 'videohub360-theme' ),
				'meta_key'                => '_vh360_specialties',
				'type'                    => 'textarea',
				'placeholder'             => __( 'Describe your areas of expertise and specialization', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 40,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'location'             => array(
				'field_id'                => 'location',
				'label'                   => __( 'Location', 'videohub360-theme' ),
				'meta_key'                => '_vh360_location',
				'type'                    => 'text',
				'placeholder'             => __( 'City, State', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => true,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 50,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'telehealth'           => array(
				'field_id'                => 'telehealth',
				'label'                   => __( 'Telehealth Available', 'videohub360-theme' ),
				'meta_key'                => '_vh360_telehealth',
				'type'                    => 'checkbox',
				'placeholder'             => '',
				'description'             => __( 'Telehealth/Remote services available', 'videohub360-theme' ),
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 60,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'accepting_new_clients' => array(
				'field_id'                => 'accepting_new_clients',
				'label'                   => __( 'Accepting New Clients', 'videohub360-theme' ),
				'meta_key'                => '_vh360_accepting_new_clients',
				'type'                    => 'checkbox',
				'placeholder'             => '',
				'description'             => __( 'Currently accepting new clients', 'videohub360-theme' ),
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 70,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'booking_url'          => array(
				'field_id'                => 'booking_url',
				'label'                   => __( 'Booking URL', 'videohub360-theme' ),
				'meta_key'                => '_vh360_booking_url',
				'type'                    => 'url',
				'placeholder'             => __( 'https://your-booking-site.com', 'videohub360-theme' ),
				'description'             => __( 'URL for online booking or scheduling', 'videohub360-theme' ),
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 80,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'contact_phone'        => array(
				'field_id'                => 'contact_phone',
				'label'                   => __( 'Phone Number', 'videohub360-theme' ),
				'meta_key'                => '_vh360_contact_phone',
				'type'                    => 'phone',
				'placeholder'             => __( 'Business phone number', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => false,
				'allow_user_public_toggle' => true,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 90,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'contact_email'        => array(
				'field_id'                => 'contact_email',
				'label'                   => __( 'Contact Email', 'videohub360-theme' ),
				'meta_key'                => '_vh360_contact_email',
				'type'                    => 'email',
				'placeholder'             => __( 'Business contact email', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => false,
				'allow_user_public_toggle' => true,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 100,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'pricing_info'         => array(
				'field_id'                => 'pricing_info',
				'label'                   => __( 'Pricing Information', 'videohub360-theme' ),
				'meta_key'                => '_vh360_pricing_info',
				'type'                    => 'textarea',
				'placeholder'             => __( 'Pricing details, rates, packages, etc.', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 110,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
			'insurance_info'       => array(
				'field_id'                => 'insurance_info',
				'label'                   => __( 'Insurance Information', 'videohub360-theme' ),
				'meta_key'                => '_vh360_insurance_info',
				'type'                    => 'textarea',
				'placeholder'             => __( 'Insurance providers accepted', 'videohub360-theme' ),
				'description'             => '',
				'required'                => false,
				'show_on_edit_profile'    => false,
				'show_on_public_about'    => true,
				'allow_user_public_toggle' => false,
				'account_types'           => array( 'professional', 'organization' ),
				'display_order'           => 120,
				'status'                  => 'active',
				'is_builtin'              => true,
			),
		);

		// Apply admin overrides to mutable built-in settings.
		$mutable_keys = array( 'show_on_public_about', 'status' );
		foreach ( $defaults as $field_id => $def ) {
			if ( isset( $this->builtin_overrides[ $field_id ] ) ) {
				foreach ( $mutable_keys as $key ) {
					if ( array_key_exists( $key, $this->builtin_overrides[ $field_id ] ) ) {
						$def[ $key ] = $this->builtin_overrides[ $field_id ][ $key ];
					}
				}
			}
			$this->builtin_fields[ $field_id ] = $def;
		}
	}

	// ----------------------------------------------------------------
	// Field Access
	// ----------------------------------------------------------------

	/**
	 * Get all built-in field definitions (with admin overrides applied).
	 *
	 * @return array
	 */
	public function get_builtin_fields() {
		return $this->builtin_fields;
	}

	/**
	 * Get admin-created custom field definitions.
	 *
	 * @return array
	 */
	public function get_custom_fields() {
		if ( null === $this->custom_fields ) {
			$raw                = get_option( 'vh360_custom_profile_fields', array() );
			$this->custom_fields = is_array( $raw ) ? $raw : array();
		}
		return $this->custom_fields;
	}

	/**
	 * Get all field definitions sorted by display_order.
	 *
	 * @return array
	 */
	public function get_all_fields() {
		$fields = array_merge( $this->get_builtin_fields(), $this->get_custom_fields() );
		uasort(
			$fields,
			function ( $a, $b ) {
				return (int) $a['display_order'] - (int) $b['display_order'];
			}
		);
		return $fields;
	}

	/**
	 * Get the HTML form field name for a field.
	 *
	 * Built-in fields use the field_id directly (preserving existing form names).
	 * Custom fields use 'vh360_custom_{field_id}' to avoid conflicts.
	 *
	 * @param array $field Field definition.
	 * @return string
	 */
	public function get_form_field_name( $field ) {
		if ( ! empty( $field['is_builtin'] ) ) {
			return $field['field_id'];
		}
		return 'vh360_custom_' . $field['field_id'];
	}

	/**
	 * Get the current meta value for a field.
	 *
	 * @param int   $user_id User ID.
	 * @param array $field   Field definition.
	 * @return mixed
	 */
	public function get_field_value( $user_id, $field ) {
		return get_user_meta( $user_id, $field['meta_key'], true );
	}

	// ----------------------------------------------------------------
	// Context-filtered Field Lists
	// ----------------------------------------------------------------

	/**
	 * Get fields applicable to a user in a given rendering context.
	 *
	 * Contexts:
	 *   'edit'          – General Edit Profile tab (custom fields with show_on_edit_profile=true).
	 *   'business_edit' – Business Profile tab (all built-in fields + professional/org custom fields).
	 *   'public'        – Public About section (fields with show_on_public_about=true, user-visible).
	 *
	 * @param int    $user_id User ID.
	 * @param string $context Rendering context.
	 * @return array Array of field definitions keyed by field_id.
	 */
	public function get_fields_for_context( $user_id, $context = 'edit' ) {
		$account_type = function_exists( 'vh360_get_user_account_type' )
			? vh360_get_user_account_type( $user_id )
			: '';

		$all_fields = $this->get_all_fields();
		$result     = array();

		foreach ( $all_fields as $field_id => $field ) {

			// Global status guard.
			if ( $field['status'] !== 'active' ) {
				continue;
			}

			// Account-type restriction.
			if ( ! empty( $field['account_types'] ) && ! in_array( $account_type, $field['account_types'], true ) ) {
				continue;
			}

			switch ( $context ) {

				case 'edit':
					// General Edit Profile tab: only non-builtin custom fields that are enabled for editing.
					if ( ! empty( $field['is_builtin'] ) ) {
						continue 2; // Skip all builtin fields here; they live in business_edit.
					}
					if ( empty( $field['show_on_edit_profile'] ) ) {
						continue 2;
					}
					break;

				case 'business_edit':
					// Business Profile tab: all active builtin fields +
					// custom fields targeting professional/org with show_on_edit_profile=true.
					if ( ! empty( $field['is_builtin'] ) ) {
						// Builtin fields always appear in business_edit.
						break;
					}
					if ( empty( $field['show_on_edit_profile'] ) ) {
						continue 2;
					}
					// Only business-targeted custom fields belong in the business tab.
					$types              = isset( $field['account_types'] ) ? (array) $field['account_types'] : array();
					$has_business_type  = ! empty( array_intersect( $types, array( 'professional', 'organization' ) ) );
					if ( ! $has_business_type ) {
						continue 2;
					}
					break;

				case 'public':
					if ( empty( $field['show_on_public_about'] ) ) {
						continue 2;
					}
					// Check user-level visibility override when the toggle is allowed.
					if ( ! empty( $field['allow_user_public_toggle'] ) ) {
						$vis_key     = '_vh360_profile_field_public_' . $field['field_id'];
						$user_choice = get_user_meta( $user_id, $vis_key, true );
						// '0' = user explicitly hid the field; '' = default (follow admin setting = show).
						if ( $user_choice === '0' ) {
							continue 2;
						}
					}
					break;

				default:
					break;
			}

			$result[ $field_id ] = $field;
		}

		return $result;
	}

	// ----------------------------------------------------------------
	// Sanitization
	// ----------------------------------------------------------------

	/**
	 * Sanitize a field value by field type.
	 *
	 * @param string $type      Field type.
	 * @param mixed  $raw_value Raw posted value.
	 * @return string|int Sanitized value.
	 */
	public function sanitize_value( $type, $raw_value ) {
		switch ( $type ) {
			case 'text':
			case 'phone':
				return sanitize_text_field( wp_unslash( $raw_value ) );

			case 'number':
				return sanitize_text_field( wp_unslash( $raw_value ) );

			case 'textarea':
				return sanitize_textarea_field( wp_unslash( $raw_value ) );

			case 'email':
				return sanitize_email( wp_unslash( $raw_value ) );

			case 'url':
				return esc_url_raw( wp_unslash( $raw_value ) );

			case 'checkbox':
				return ( '1' === $raw_value || 1 === $raw_value || true === $raw_value ) ? '1' : '0';

			default:
				// Includes any legacy 'select' values stored before select was removed.
				return sanitize_text_field( wp_unslash( $raw_value ) );
		}
	}

	/**
	 * Sanitize and save a single field value.
	 *
	 * @param int   $user_id   User ID.
	 * @param array $field     Field definition.
	 * @param mixed $raw_value Raw value to save.
	 */
	public function save_field_value( $user_id, $field, $raw_value ) {
		$sanitized = $this->sanitize_value( $field['type'], $raw_value );
		if ( '' === $sanitized ) {
			delete_user_meta( $user_id, $field['meta_key'] );
		} else {
			update_user_meta( $user_id, $field['meta_key'], $sanitized );
		}
	}

	/**
	 * Save all fields for a user in a context from posted data.
	 *
	 * @param int    $user_id     User ID.
	 * @param array  $posted_data $_POST data.
	 * @param string $context     'edit', 'business_edit', or 'all'.
	 */
	public function save_fields( $user_id, $posted_data, $context = 'all' ) {
		$account_type = function_exists( 'vh360_get_user_account_type' )
			? vh360_get_user_account_type( $user_id )
			: '';

		$fields = ( 'all' === $context )
			? $this->get_all_fields()
			: $this->get_fields_for_context( $user_id, $context );

		foreach ( $fields as $field_id => $field ) {
			if ( $field['status'] !== 'active' ) {
				continue;
			}

			// Respect account-type restriction.
			if ( ! empty( $field['account_types'] ) && ! in_array( $account_type, $field['account_types'], true ) ) {
				continue;
			}

			$form_name = $this->get_form_field_name( $field );

			if ( 'checkbox' === $field['type'] ) {
				$raw = ( isset( $posted_data[ $form_name ] ) && '1' === $posted_data[ $form_name ] ) ? '1' : '0';
				$this->save_field_value( $user_id, $field, $raw );
			} else {
				if ( ! isset( $posted_data[ $form_name ] ) ) {
					continue; // Field not posted — leave existing meta untouched.
				}
				$this->save_field_value( $user_id, $field, $posted_data[ $form_name ] );
			}
		}

		// Save user-level visibility toggles for any field that allows them.
		$this->save_visibility_toggles( $user_id, $posted_data );
	}

	/**
	 * Save user-level public-visibility toggles.
	 *
	 * @param int   $user_id     User ID.
	 * @param array $posted_data $_POST data.
	 */
	public function save_visibility_toggles( $user_id, $posted_data ) {
		$all_fields = $this->get_all_fields();
		foreach ( $all_fields as $field_id => $field ) {
			if ( empty( $field['allow_user_public_toggle'] ) ) {
				continue;
			}
			$vis_form_name = 'vh360_field_visibility_' . $field['field_id'];
			$vis_meta_key  = '_vh360_profile_field_public_' . $field['field_id'];

			if ( ! isset( $posted_data[ $vis_form_name ] ) ) {
				// The visibility toggle was not rendered for this form (different context).
				// Leave the stored preference untouched.
				continue;
			}

			// The hidden input ensures value is always '0' (unchecked) or '1' (checked).
			$is_public = ( '1' === $posted_data[ $vis_form_name ] ) ? '1' : '0';
			update_user_meta( $user_id, $vis_meta_key, $is_public );
		}
	}

	/**
	 * Sanitize a custom field definition from admin input.
	 *
	 * @param array $raw Raw field definition from admin form.
	 * @return array|null Sanitized definition or null on failure.
	 */
	public function sanitize_field_definition( $raw ) {
		$allowed_types        = array( 'text', 'textarea', 'email', 'url', 'phone', 'number', 'checkbox' );
		$allowed_account_types = array( 'creator', 'client', 'professional', 'organization' );

		$field_id = sanitize_key( isset( $raw['field_id'] ) ? $raw['field_id'] : '' );
		if ( ! $field_id ) {
			return null;
		}

		// Prevent overwriting built-in field keys.
		if ( isset( $this->builtin_fields[ $field_id ] ) ) {
			return null;
		}

		$type = sanitize_key( isset( $raw['type'] ) ? $raw['type'] : 'text' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			// Fallback: 'select' fields saved before the type was removed are treated as text.
			$type = 'text';
		}

		$account_types = array();
		if ( isset( $raw['account_types'] ) && is_array( $raw['account_types'] ) ) {
			foreach ( $raw['account_types'] as $at ) {
				$at = sanitize_key( $at );
				if ( in_array( $at, $allowed_account_types, true ) ) {
					$account_types[] = $at;
				}
			}
		}

		$meta_key = '_vh360_custom_profile_' . $field_id;

		return array(
			'field_id'                => $field_id,
			'label'                   => sanitize_text_field( isset( $raw['label'] ) ? $raw['label'] : '' ),
			'meta_key'                => $meta_key,
			'type'                    => $type,
			'placeholder'             => sanitize_text_field( isset( $raw['placeholder'] ) ? $raw['placeholder'] : '' ),
			'description'             => sanitize_text_field( isset( $raw['description'] ) ? $raw['description'] : '' ),
			'required'                => ! empty( $raw['required'] ) ? 1 : 0,
			'show_on_edit_profile'    => ! empty( $raw['show_on_edit_profile'] ) ? 1 : 0,
			'show_on_public_about'    => ! empty( $raw['show_on_public_about'] ) ? 1 : 0,
			'allow_user_public_toggle' => ! empty( $raw['allow_user_public_toggle'] ) ? 1 : 0,
			'account_types'           => $account_types,
			'display_order'           => isset( $raw['display_order'] ) ? absint( $raw['display_order'] ) : 100,
			'status'                  => ( isset( $raw['status'] ) && 'inactive' === $raw['status'] ) ? 'inactive' : 'active',
			'is_builtin'              => false,
		);
	}

	/**
	 * Sanitize a complete array of custom field definitions (used in settings API).
	 *
	 * @param mixed $input Raw input (array of field definitions keyed by field_id).
	 * @return array Sanitized array.
	 */
	public function sanitize_custom_fields_option( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		$sanitized = array();
		foreach ( $input as $field_id => $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$raw['field_id'] = $field_id;
			$clean           = $this->sanitize_field_definition( $raw );
			if ( $clean ) {
				$sanitized[ $clean['field_id'] ] = $clean;
			}
		}
		return $sanitized;
	}

	// ----------------------------------------------------------------
	// Frontend Rendering – Edit Form
	// ----------------------------------------------------------------

	/**
	 * Render editable fields for a context.
	 *
	 * Outputs HTML form groups; must be inside an existing <form>.
	 *
	 * @param int    $user_id User ID.
	 * @param string $context 'edit' or 'business_edit'.
	 */
	public function render_edit_fields( $user_id, $context = 'edit' ) {
		$fields = $this->get_fields_for_context( $user_id, $context );
		if ( empty( $fields ) ) {
			return;
		}

		echo '<div class="vh360-profile-fields-edit">';
		foreach ( $fields as $field_id => $field ) {
			$value     = $this->get_field_value( $user_id, $field );
			$form_name = $this->get_form_field_name( $field );
			$this->render_single_edit_field( $field, $value, $form_name, $user_id );
		}
		echo '</div>';
	}

	/**
	 * Render a single editable form field.
	 *
	 * @param array  $field     Field definition.
	 * @param mixed  $value     Current field value.
	 * @param string $form_name HTML name attribute.
	 * @param int    $user_id   Current user ID (for visibility toggle).
	 */
	private function render_single_edit_field( $field, $value, $form_name, $user_id ) {
		$html_id     = 'vh360_pf_' . esc_attr( $field['field_id'] );
		$label       = esc_html( $field['label'] );
		$placeholder = isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$description = isset( $field['description'] ) ? $field['description'] : '';
		$required    = ! empty( $field['required'] );

		echo '<div class="vh360-dashboard-form-group">';

		if ( 'checkbox' !== $field['type'] ) {
			echo '<label for="' . esc_attr( $html_id ) . '">';
			echo $label; // Already escaped above.
			if ( $required ) {
				echo ' <span class="vh360-required" aria-hidden="true">*</span>';
			}
			echo '</label>';
		}

		switch ( $field['type'] ) {

			case 'text':
			case 'phone':
				printf(
					'<input type="text" name="%s" id="%s" class="vh360-dashboard-input" value="%s" placeholder="%s"%s>',
					esc_attr( $form_name ),
					esc_attr( $html_id ),
					esc_attr( $value ),
					$placeholder,
					$required ? ' required' : ''
				);
				break;

			case 'number':
				printf(
					'<input type="number" name="%s" id="%s" class="vh360-dashboard-input" value="%s" placeholder="%s"%s>',
					esc_attr( $form_name ),
					esc_attr( $html_id ),
					esc_attr( $value ),
					$placeholder,
					$required ? ' required' : ''
				);
				break;

			case 'email':
				printf(
					'<input type="email" name="%s" id="%s" class="vh360-dashboard-input" value="%s" placeholder="%s"%s>',
					esc_attr( $form_name ),
					esc_attr( $html_id ),
					esc_attr( $value ),
					$placeholder,
					$required ? ' required' : ''
				);
				break;

			case 'url':
				printf(
					'<input type="url" name="%s" id="%s" class="vh360-dashboard-input" value="%s" placeholder="%s"%s>',
					esc_attr( $form_name ),
					esc_attr( $html_id ),
					esc_attr( $value ),
					$placeholder,
					$required ? ' required' : ''
				);
				break;

			case 'textarea':
				printf(
					'<textarea name="%s" id="%s" rows="4" class="vh360-dashboard-textarea" placeholder="%s"%s>%s</textarea>',
					esc_attr( $form_name ),
					esc_attr( $html_id ),
					$placeholder,
					$required ? ' required' : '',
					esc_textarea( $value )
				);
				break;

			case 'checkbox':
				echo '<div class="vh360-dashboard-checkbox-group">';
				echo '<label class="vh360-dashboard-checkbox-label">';
				printf(
					'<input type="checkbox" name="%s" value="1"%s>',
					esc_attr( $form_name ),
					checked( $value, '1', false )
				);
				echo ' ' . $label;
				echo '</label>';
				echo '</div>';
				break;
		}

		if ( $description && 'checkbox' !== $field['type'] ) {
			echo '<p class="vh360-dashboard-field-description">' . esc_html( $description ) . '</p>';
		}

		// User-level public visibility toggle.
		if ( ! empty( $field['allow_user_public_toggle'] ) && ! empty( $field['show_on_public_about'] ) ) {
			$vis_meta_key  = '_vh360_profile_field_public_' . $field['field_id'];
			$vis_form_name = 'vh360_field_visibility_' . $field['field_id'];
			$user_choice   = get_user_meta( $user_id, $vis_meta_key, true );
			// Default (not set / not '0') = visible.
			$is_visible    = ( $user_choice !== '0' );
			echo '<div class="vh360-field-visibility-toggle">';
			echo '<label class="vh360-dashboard-checkbox-label">';
			// Hidden input ensures the browser always submits a value so unchecking reliably saves 0.
			printf(
				'<input type="hidden" name="%s" value="0">',
				esc_attr( $vis_form_name )
			);
			printf(
				'<input type="checkbox" name="%s" value="1"%s>',
				esc_attr( $vis_form_name ),
				checked( $is_visible, true, false )
			);
			echo ' ' . esc_html__( 'Show publicly on profile', 'videohub360-theme' );
			echo '</label>';
			echo '</div>';
		}

		echo '</div>';
	}

	// ----------------------------------------------------------------
	// Frontend Rendering – Public About
	// ----------------------------------------------------------------

	/**
	 * Render publicly-visible profile fields.
	 *
	 * Outputs a "Profile Details" block with all applicable, non-empty fields.
	 *
	 * @param int   $user_id Profile owner user ID.
	 * @param array $args    Optional arguments:
	 *                       - 'exclude' (array) Field IDs to skip (e.g. when a template already displays them).
	 */
	public function render_public_fields( $user_id, $args = array() ) {
		$exclude       = isset( $args['exclude'] ) && is_array( $args['exclude'] ) ? $args['exclude'] : array();
		$fields        = $this->get_fields_for_context( $user_id, 'public' );
		$field_outputs = array();

		foreach ( $fields as $field_id => $field ) {
			if ( in_array( $field_id, $exclude, true ) ) {
				continue;
			}
			$value  = $this->get_field_value( $user_id, $field );
			if ( '' === $value || null === $value || false === $value ) {
				continue;
			}
			$output = $this->render_public_field_value( $field, $value );
			if ( '' !== $output ) {
				$field_outputs[] = array(
					'field'  => $field,
					'output' => $output,
				);
			}
		}

		if ( empty( $field_outputs ) ) {
			return;
		}

		echo '<div class="vh360-profile-fields">';
		echo '<h3 class="vh360-profile-fields-title">' . esc_html__( 'Profile Details', 'videohub360-theme' ) . '</h3>';
		echo '<dl class="vh360-profile-field-list">';

		foreach ( $field_outputs as $item ) {
			echo '<div class="vh360-profile-field">';
			echo '<dt class="vh360-profile-field-label">' . esc_html( $item['field']['label'] ) . '</dt>';
			echo '<dd class="vh360-profile-field-value">' . $item['output'] . '</dd>'; // Escaped within render_public_field_value.
			echo '</div>';
		}

		echo '</dl>';
		echo '</div>';
	}

	/**
	 * Render the escaped public display value for a single field.
	 *
	 * @param array  $field Field definition.
	 * @param mixed  $value Field value.
	 * @return string Escaped HTML output (empty string if nothing to show).
	 */
	private function render_public_field_value( $field, $value ) {
		switch ( $field['type'] ) {

			case 'text':
			case 'phone':
			case 'number':
				return esc_html( $value );

			case 'checkbox':
				// For checkboxes, only display a row when the box is checked.
				return ( '1' === $value ) ? esc_html__( 'Yes', 'videohub360-theme' ) : '';

			case 'textarea':
				return wpautop( wp_kses_post( $value ) );

			case 'email':
				$email = sanitize_email( $value );
				if ( ! $email ) {
					return '';
				}
				return '<a href="mailto:' . antispambot( $email ) . '">' . antispambot( $email ) . '</a>';

			case 'url':
				$url = esc_url( $value );
				if ( ! $url ) {
					return '';
				}
				return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . esc_html( $value ) . '</a>';

			default:
				return esc_html( $value );
		}
	}
}
