<?php
/**
 * Profile About Details
 *
 * Renders additional public profile fields for the Profile Mode About tab
 * without duplicating the desktop rail intro card.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id = isset( $args['author_id'] )
    ? absint( $args['author_id'] )
    : ( isset( $author_id ) ? absint( $author_id ) : absint( get_the_author_meta( 'ID' ) ) );

if ( ! $author_id ) {
    return;
}

if ( function_exists( 'vh360_render_public_profile_fields' ) ) {
    vh360_render_public_profile_fields( $author_id );
}
