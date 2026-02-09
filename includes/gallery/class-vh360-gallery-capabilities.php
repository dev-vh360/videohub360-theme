<?php
/**
 * Gallery Capabilities
 *
 * Manages custom capabilities for the gallery post type.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_Gallery_Capabilities
 *
 * Handles gallery capabilities registration and management.
 */
class VH360_Gallery_Capabilities {

	/**
	 * Singleton instance.
	 *
	 * @var VH360_Gallery_Capabilities|null
	 */
	private static $instance = null;

	/**
	 * Gallery capabilities.
	 *
	 * @var array
	 */
	private $capabilities = array(
		// Primitive capabilities.
		'edit_vh360_gallery'             => true,
		'read_vh360_gallery'             => true,
		'delete_vh360_gallery'           => true,
		'edit_vh360_galleries'           => true,
		'edit_others_vh360_galleries'    => true,
		'publish_vh360_galleries'        => true,
		'read_private_vh360_galleries'   => true,
		'delete_vh360_galleries'         => true,
		'delete_private_vh360_galleries' => true,
		'delete_published_vh360_galleries' => true,
		'delete_others_vh360_galleries'  => true,
		'edit_private_vh360_galleries'   => true,
		'edit_published_vh360_galleries' => true,
		// Custom gallery capabilities.
		'create_vh360_galleries'         => true,
		'manage_vh360_gallery_terms'     => true,
		'edit_vh360_gallery_terms'       => true,
		'delete_vh360_gallery_terms'     => true,
		'assign_vh360_gallery_terms'     => true,
	);

	/**
	 * Get singleton instance.
	 *
	 * @return VH360_Gallery_Capabilities
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Add capabilities on init (needed for frontend) and admin_init.
		add_action( 'init', array( $this, 'add_capabilities' ) );
		add_action( 'after_switch_theme', array( $this, 'add_capabilities' ) );
	}

	/**
	 * Add gallery capabilities to roles.
	 */
	public function add_capabilities() {
		// Check if capabilities have been added with current version.
		$caps_version = get_option( 'vh360_gallery_caps_version', '' );
		$current_version = '1.2.0';
		
		// If capabilities were already added for this version, skip.
		if ( $caps_version === $current_version ) {
			return;
		}

		// Get roles.
		$administrator = get_role( 'administrator' );

		// Administrator gets all capabilities.
		if ( $administrator ) {
			foreach ( $this->capabilities as $cap => $grant ) {
				$administrator->add_cap( $cap );
			}
		}

		// Note: Other roles (editor, author, contributor, subscriber) will receive
		// gallery capabilities via the VH360 Theme Permissions Settings page.
		// This change allows centralized control of gallery permissions.

		// Mark capabilities as added with current version.
		update_option( 'vh360_gallery_caps_version', $current_version );
		// Also update old option for backward compatibility.
		update_option( 'vh360_gallery_caps_added', true );
	}

	/**
	 * Remove gallery capabilities from roles.
	 *
	 * Used during theme deactivation.
	 */
	public static function remove_capabilities() {
		$roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

		$capabilities = array(
			'edit_vh360_gallery',
			'read_vh360_gallery',
			'delete_vh360_gallery',
			'edit_vh360_galleries',
			'edit_others_vh360_galleries',
			'publish_vh360_galleries',
			'read_private_vh360_galleries',
			'delete_vh360_galleries',
			'delete_private_vh360_galleries',
			'delete_published_vh360_galleries',
			'delete_others_vh360_galleries',
			'edit_private_vh360_galleries',
			'edit_published_vh360_galleries',
			'create_vh360_galleries',
			'manage_vh360_gallery_terms',
			'edit_vh360_gallery_terms',
			'delete_vh360_gallery_terms',
			'assign_vh360_gallery_terms',
		);

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $capabilities as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}

		delete_option( 'vh360_gallery_caps_added' );
	}

	/**
	 * Check if current user can create galleries.
	 *
	 * @return bool
	 */
	public static function can_create_gallery() {
		return current_user_can( 'create_vh360_galleries' ) || current_user_can( 'publish_vh360_galleries' );
	}

	/**
	 * Check if current user can edit a specific gallery.
	 *
	 * @param int $gallery_id Gallery post ID.
	 * @return bool
	 */
	public static function can_edit_gallery( $gallery_id ) {
		$gallery = get_post( $gallery_id );
		if ( ! $gallery || 'vh360_gallery' !== $gallery->post_type ) {
			return false;
		}

		// User can edit if they're the author or have edit_others permission.
		if ( get_current_user_id() === (int) $gallery->post_author ) {
			return current_user_can( 'edit_vh360_gallery', $gallery_id );
		}

		return current_user_can( 'edit_others_vh360_galleries' );
	}

	/**
	 * Check if current user can delete a specific gallery.
	 *
	 * @param int $gallery_id Gallery post ID.
	 * @return bool
	 */
	public static function can_delete_gallery( $gallery_id ) {
		$gallery = get_post( $gallery_id );
		if ( ! $gallery || 'vh360_gallery' !== $gallery->post_type ) {
			return false;
		}

		// User can delete if they're the author or have delete_others permission.
		if ( get_current_user_id() === (int) $gallery->post_author ) {
			return current_user_can( 'delete_vh360_gallery', $gallery_id );
		}

		return current_user_can( 'delete_others_vh360_galleries' );
	}

	/**
	 * Check if current user can manage gallery images.
	 *
	 * @param int $gallery_id Gallery post ID.
	 * @return bool
	 */
	public static function can_manage_gallery_images( $gallery_id ) {
		return self::can_edit_gallery( $gallery_id );
	}
}

// Initialize.
VH360_Gallery_Capabilities::get_instance();
