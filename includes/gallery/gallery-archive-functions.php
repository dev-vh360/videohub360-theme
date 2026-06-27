<?php
/**
 * Gallery archive helpers.
 *
 * @package Videohub360_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function vh360_get_gallery_archive_filters() {
	$taxonomy_category = is_tax( 'vh360_gallery_category' ) ? get_queried_object() : null;
	$taxonomy_tag      = is_tax( 'vh360_gallery_tag' ) ? get_queried_object() : null;

	return array(
		'search'   => isset( $_GET['gallery_search'] ) ? sanitize_text_field( wp_unslash( $_GET['gallery_search'] ) ) : '',
		'category' => isset( $_GET['gallery_category'] ) ? sanitize_key( wp_unslash( $_GET['gallery_category'] ) ) : ( $taxonomy_category instanceof WP_Term ? $taxonomy_category->slug : '' ),
		'tag'      => isset( $_GET['gallery_tag'] ) ? sanitize_key( wp_unslash( $_GET['gallery_tag'] ) ) : ( $taxonomy_tag instanceof WP_Term ? $taxonomy_tag->slug : '' ),
		'sort'     => isset( $_GET['gallery_sort'] ) ? sanitize_key( wp_unslash( $_GET['gallery_sort'] ) ) : 'date_desc',
	);
}

function vh360_get_gallery_archive_query( $filters = array() ) {
	$filters  = wp_parse_args( $filters, vh360_get_gallery_archive_filters() );
	$settings = vh360_get_gallery_settings();
	$per_page = isset( $settings['galleries_per_page'] ) ? absint( $settings['galleries_per_page'] ) : 12;
	$paged    = max( 1, absint( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) ) );

	$query_args = array(
		'post_type'      => 'vh360_gallery',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
	);

	if ( '' !== $filters['search'] ) {
		$query_args['s'] = $filters['search'];
	}

	$tax_query = array();
	if ( '' !== $filters['category'] ) {
		$tax_query[] = array(
			'taxonomy' => 'vh360_gallery_category',
			'field'    => 'slug',
			'terms'    => $filters['category'],
		);
	}
	if ( '' !== $filters['tag'] ) {
		$tax_query[] = array(
			'taxonomy' => 'vh360_gallery_tag',
			'field'    => 'slug',
			'terms'    => $filters['tag'],
		);
	}
	if ( ! empty( $tax_query ) ) {
		$query_args['tax_query'] = $tax_query;
	}

	switch ( $filters['sort'] ) {
		case 'date_asc':
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'ASC';
			break;
		case 'title_asc':
			$query_args['orderby'] = 'title';
			$query_args['order']   = 'ASC';
			break;
		case 'title_desc':
			$query_args['orderby'] = 'title';
			$query_args['order']   = 'DESC';
			break;
		case 'date_desc':
		default:
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
			break;
	}

	return new WP_Query( $query_args );
}

function vh360_get_gallery_archive_base_url() {
	return get_post_type_archive_link( 'vh360_gallery' );
}
