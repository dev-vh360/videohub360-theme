<?php
/**
 * Template Name: Profile Edit
 *
 * Compatibility redirect to canonical dashboard profile editors.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user_id = absint( get_current_user_id() );

if ( ! $current_user_id ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

$redirect_url = function_exists( 'vh360_get_profile_edit_url' )
	? vh360_get_profile_edit_url( $current_user_id )
	: home_url( '/dashboard/?tab=profile' );

wp_safe_redirect( $redirect_url );
exit;
