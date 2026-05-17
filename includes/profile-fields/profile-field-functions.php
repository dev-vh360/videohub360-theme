<?php
/**
 * VH360 Profile Fields – Public Helper Functions
 *
 * Thin wrappers around VH360_Profile_Fields that templates and other theme
 * code call without needing to touch the class directly.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return all registered field definitions (built-in + custom).
 *
 * @return array Keyed by field_id, sorted by display_order.
 */
function vh360_get_profile_field_definitions() {
	return VH360_Profile_Fields::get_instance()->get_all_fields();
}

/**
 * Return fields applicable to a user in a given context.
 *
 * @param int    $user_id User ID.
 * @param string $context 'edit', 'business_edit', or 'public'.
 * @return array
 */
function vh360_get_profile_fields_for_user( $user_id, $context = 'edit' ) {
	return VH360_Profile_Fields::get_instance()->get_fields_for_context( $user_id, $context );
}

/**
 * Render editable profile fields inside an existing form.
 *
 * @param int    $user_id User ID.
 * @param string $context 'edit' or 'business_edit'.
 */
function vh360_render_profile_fields( $user_id, $context = 'edit' ) {
	VH360_Profile_Fields::get_instance()->render_edit_fields( $user_id, $context );
}

/**
 * Save profile fields from posted data.
 *
 * Call after nonce verification and core field saving.
 *
 * @param int    $user_id     User ID.
 * @param array  $posted_data $_POST data.
 * @param string $context     'edit', 'business_edit', or 'all'.
 */
function vh360_save_profile_fields( $user_id, $posted_data, $context = 'all' ) {
	VH360_Profile_Fields::get_instance()->save_fields( $user_id, $posted_data, $context );
}

/**
 * Return publicly-visible fields for a user (non-empty values only).
 *
 * @param int $user_id Profile owner user ID.
 * @return array Array of ['field' => definition, 'value' => saved value].
 */
function vh360_get_public_profile_fields( $user_id ) {
	$manager = VH360_Profile_Fields::get_instance();
	$fields  = $manager->get_fields_for_context( $user_id, 'public' );
	$result  = array();
	foreach ( $fields as $field_id => $field ) {
		$value = $manager->get_field_value( $user_id, $field );
		if ( '' !== $value && null !== $value && false !== $value ) {
			$result[] = array(
				'field' => $field,
				'value' => $value,
			);
		}
	}
	return $result;
}

/**
 * Output the public "Profile Details" block for a user.
 *
 * @param int $user_id Profile owner user ID.
 */
function vh360_render_public_profile_fields( $user_id ) {
	VH360_Profile_Fields::get_instance()->render_public_fields( $user_id );
}
