<?php
/**
 * Generated image quality controls.
 *
 * This module changes encoder quality only when WordPress generates or
 * regenerates image files. It does not disable responsive images or thumbnail
 * generation, and it does not alter WordPress large-image scaling.
 *
 * @package Videohub360_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether higher-quality image generation is enabled.
 *
 * The missing-key default keeps the feature enabled on existing installations.
 *
 * @return bool
 */
function vh360_high_quality_images_enabled() {
	$options = get_option( 'vh360_appearance_options', array() );

	if ( ! array_key_exists( 'enable_high_quality_images', $options ) ) {
		return true;
	}

	return ! empty( $options['enable_high_quality_images'] );
}

/**
 * Increase the encoder quality for generated JPEG and WebP files.
 *
 * @param int    $quality   Quality supplied by WordPress or the image editor.
 * @param string $mime_type Image MIME type.
 * @return int
 */
function vh360_filter_generated_image_quality( $quality, $mime_type ) {
	if ( ! vh360_high_quality_images_enabled() ) {
		return $quality;
	}

	if ( in_array( $mime_type, array( 'image/jpeg', 'image/webp' ), true ) ) {
		return 90;
	}

	return $quality;
}

add_filter(
	'wp_editor_set_quality',
	'vh360_filter_generated_image_quality',
	20,
	2
);
