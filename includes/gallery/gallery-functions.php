<?php
/**
 * Gallery Helper Functions
 *
 * Utility functions for working with galleries.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get gallery images.
 *
 * @param int    $gallery_id Gallery post ID.
 * @param string $size       Image size.
 * @return array Array of image data.
 */
function vh360_get_gallery_images( $gallery_id, $size = 'medium' ) {
	$image_ids = get_post_meta( $gallery_id, '_vh360_gallery_images', true );

	if ( ! is_array( $image_ids ) || empty( $image_ids ) ) {
		return array();
	}

	$images = array();
	foreach ( $image_ids as $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			continue;
		}

		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			continue;
		}

		$thumb_src = wp_get_attachment_image_src( $attachment_id, $size );
		$full_src  = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( ! $thumb_src || ! $full_src ) {
			continue;
		}

		$images[] = array(
			'id'      => $attachment_id,
			'src'     => $thumb_src[0],
			'width'   => $thumb_src[1],
			'height'  => $thumb_src[2],
			'full'    => $full_src[0],
			'full_w'  => $full_src[1],
			'full_h'  => $full_src[2],
			'alt'     => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'caption' => $attachment->post_excerpt,
			'title'   => $attachment->post_title,
		);
	}

	return $images;
}

/**
 * Get gallery image count.
 *
 * @param int $gallery_id Gallery post ID.
 * @return int Number of images.
 */
function vh360_get_gallery_image_count( $gallery_id ) {
	$image_ids = get_post_meta( $gallery_id, '_vh360_gallery_images', true );
	return is_array( $image_ids ) ? count( $image_ids ) : 0;
}

/**
 * Get gallery layout setting.
 *
 * @param int $gallery_id Gallery post ID.
 * @return string Layout type (grid, masonry, justified).
 */
function vh360_get_gallery_layout( $gallery_id ) {
	$layout = get_post_meta( $gallery_id, '_vh360_gallery_layout', true );
	return $layout ? $layout : 'grid';
}

/**
 * Get gallery columns setting.
 *
 * @param int $gallery_id Gallery post ID.
 * @return int Number of columns.
 */
function vh360_get_gallery_columns( $gallery_id ) {
	$columns = get_post_meta( $gallery_id, '_vh360_gallery_columns', true );
	return $columns ? absint( $columns ) : 3;
}

/**
 * Check if gallery has lightbox enabled.
 *
 * Lightbox is enabled by default for galleries. An empty value (newly created gallery
 * or gallery created before this meta was introduced) defaults to enabled for better
 * user experience. Only explicitly set '0' disables the lightbox.
 *
 * @param int $gallery_id Gallery post ID.
 * @return bool True if lightbox is enabled, false otherwise.
 */
function vh360_gallery_has_lightbox( $gallery_id ) {
	$lightbox = get_post_meta( $gallery_id, '_vh360_gallery_lightbox', true );
	// Empty string (not set) defaults to enabled for backward compatibility.
	return '1' === $lightbox || '' === $lightbox;
}

/**
 * Get gallery image size setting.
 *
 * @param int $gallery_id Gallery post ID.
 * @return string Image size name.
 */
function vh360_get_gallery_image_size( $gallery_id ) {
	$size = get_post_meta( $gallery_id, '_vh360_gallery_image_size', true );
	return $size ? $size : 'medium';
}

/**
 * Get user's galleries.
 *
 * @param int   $user_id User ID (default: current user).
 * @param array $args    Additional query args.
 * @return array Array of gallery posts.
 */
function vh360_get_user_galleries( $user_id = 0, $args = array() ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return array();
	}

	$defaults = array(
		'post_type'      => 'vh360_gallery',
		'post_status'    => array( 'publish', 'pending', 'draft' ),
		'author'         => $user_id,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$args = wp_parse_args( $args, $defaults );

	return get_posts( $args );
}

/**
 * Get user's gallery count.
 *
 * @param int $user_id User ID (default: current user).
 * @return int Number of galleries.
 */
function vh360_get_user_gallery_count( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return 0;
	}

	$count_posts = wp_count_posts( 'vh360_gallery' );
	
	// For current user, count their own galleries.
	global $wpdb;
	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d AND post_status IN ('publish', 'pending', 'draft')",
			'vh360_gallery',
			$user_id
		)
	);

	return absint( $count );
}

/**
 * Get all published galleries.
 *
 * @param array $args Query arguments.
 * @return array Array of gallery posts.
 */
function vh360_get_galleries( $args = array() ) {
	$defaults = array(
		'post_type'      => 'vh360_gallery',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$args = wp_parse_args( $args, $defaults );

	return get_posts( $args );
}

/**
 * Get gallery categories.
 *
 * @param array $args Term query arguments.
 * @return array Array of term objects.
 */
function vh360_get_gallery_categories( $args = array() ) {
	$defaults = array(
		'taxonomy'   => 'vh360_gallery_category',
		'hide_empty' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	return get_terms( $args );
}

/**
 * Get gallery tags.
 *
 * @param array $args Term query arguments.
 * @return array Array of term objects.
 */
function vh360_get_gallery_tags( $args = array() ) {
	$defaults = array(
		'taxonomy'   => 'vh360_gallery_tag',
		'hide_empty' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	return get_terms( $args );
}

/**
 * Get galleries by category.
 *
 * @param int|string $category Category ID or slug.
 * @param array      $args     Additional query args.
 * @return array Array of gallery posts.
 */
function vh360_get_galleries_by_category( $category, $args = array() ) {
	$defaults = array(
		'post_type'      => 'vh360_gallery',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'tax_query'      => array(
			array(
				'taxonomy' => 'vh360_gallery_category',
				'field'    => is_numeric( $category ) ? 'term_id' : 'slug',
				'terms'    => $category,
			),
		),
	);

	$args = wp_parse_args( $args, $defaults );

	return get_posts( $args );
}

/**
 * Render gallery HTML.
 *
 * @param int   $gallery_id Gallery post ID.
 * @param array $args       Display arguments.
 * @return string Gallery HTML.
 */
function vh360_render_gallery( $gallery_id, $args = array() ) {
	$gallery = get_post( $gallery_id );
	if ( ! $gallery || 'vh360_gallery' !== $gallery->post_type ) {
		return '';
	}

	$defaults = array(
		'layout'     => vh360_get_gallery_layout( $gallery_id ),
		'columns'    => vh360_get_gallery_columns( $gallery_id ),
		'size'       => vh360_get_gallery_image_size( $gallery_id ),
		'lightbox'   => vh360_gallery_has_lightbox( $gallery_id ),
		'show_title' => false,
	);

	$args   = wp_parse_args( $args, $defaults );
	$images = vh360_get_gallery_images( $gallery_id, $args['size'] );

	if ( empty( $images ) ) {
		return '<p class="vh360-gallery-empty">' . esc_html__( 'No images in this gallery.', 'videohub360-theme' ) . '</p>';
	}

	$layout_class = 'vh360-gallery-' . esc_attr( $args['layout'] );
	$col_class    = 'vh360-gallery-cols-' . esc_attr( $args['columns'] );
	$lb_class     = $args['lightbox'] ? 'vh360-gallery-lightbox-enabled' : '';

	ob_start();
	?>
	<div class="vh360-gallery-container <?php echo esc_attr( "$layout_class $col_class $lb_class" ); ?>" 
		 data-gallery-id="<?php echo esc_attr( $gallery_id ); ?>"
		 data-pswp-uid="gallery-<?php echo esc_attr( $gallery_id ); ?>">
		
		<?php if ( $args['show_title'] ) : ?>
			<h3 class="vh360-gallery-title"><?php echo esc_html( get_the_title( $gallery_id ) ); ?></h3>
		<?php endif; ?>
		
		<div class="vh360-gallery-grid <?php echo 'masonry' === $args['layout'] ? 'masonry' : ''; ?>">
			<?php foreach ( $images as $index => $image ) : ?>
				<figure class="vh360-gallery-item" 
						data-index="<?php echo esc_attr( $index ); ?>"
						itemprop="associatedMedia" 
						itemscope 
						itemtype="http://schema.org/ImageObject">
					<a href="<?php echo esc_url( $image['full'] ); ?>" 
					   class="vh360-gallery-item-link"
					   data-size="<?php echo esc_attr( $image['full_w'] . 'x' . $image['full_h'] ); ?>"
					   data-caption="<?php echo esc_attr( $image['caption'] ); ?>"
					   itemprop="contentUrl">
						<div class="vh360-gallery-image-wrapper">
							<img src="<?php echo esc_url( $image['src'] ); ?>" 
								 alt="<?php echo esc_attr( $image['alt'] ); ?>"
								 width="<?php echo esc_attr( $image['width'] ); ?>"
								 height="<?php echo esc_attr( $image['height'] ); ?>"
								 loading="lazy"
								 class="vh360-gallery-image"
								 itemprop="thumbnail">
						</div>
						<div class="vh360-gallery-overlay">
							<span class="vh360-gallery-zoom-icon" aria-hidden="true">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<circle cx="11" cy="11" r="8"></circle>
									<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
									<line x1="11" y1="8" x2="11" y2="14"></line>
									<line x1="8" y1="11" x2="14" y2="11"></line>
								</svg>
							</span>
						</div>
					</a>
					<?php if ( ! empty( $image['caption'] ) ) : ?>
						<figcaption class="vh360-gallery-caption" itemprop="caption description">
							<?php echo esc_html( $image['caption'] ); ?>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Get gallery settings with defaults.
 *
 * @return array Gallery settings.
 */
function vh360_get_gallery_settings() {
	$defaults = array(
		'enable_galleries'       => true,
		'enable_frontend_upload' => true,
		'max_images_per_gallery' => 50,
		'max_image_size'         => 5, // MB
		'allowed_image_types'    => array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ),
		'default_layout'         => 'grid',
		'default_columns'        => 3,
		'enable_lightbox'        => true,
		'enable_comments'        => true,
		'galleries_per_page'     => 12,
	);

	$settings = get_option( 'vh360_gallery_options', array() );

	return wp_parse_args( $settings, $defaults );
}

/**
 * Check if gallery feature is enabled.
 *
 * @return bool
 */
function vh360_is_gallery_enabled() {
	$settings = vh360_get_gallery_settings();
	return ! empty( $settings['enable_galleries'] );
}

/**
 * Check if frontend gallery upload is enabled.
 *
 * @return bool
 */
function vh360_is_gallery_frontend_upload_enabled() {
	$settings = vh360_get_gallery_settings();
	return ! empty( $settings['enable_frontend_upload'] );
}

/**
 * Get allowed image types for gallery upload.
 *
 * @return array Array of allowed extensions.
 */
function vh360_get_gallery_allowed_image_types() {
	$settings = vh360_get_gallery_settings();
	return isset( $settings['allowed_image_types'] ) ? $settings['allowed_image_types'] : array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
}

/**
 * Get maximum gallery image size in bytes.
 *
 * @return int Size in bytes.
 */
function vh360_get_gallery_max_image_size() {
	$settings = vh360_get_gallery_settings();
	$mb       = isset( $settings['max_image_size'] ) ? absint( $settings['max_image_size'] ) : 5;
	return $mb * 1024 * 1024;
}

/**
 * Get maximum images per gallery.
 *
 * @return int Maximum number of images.
 */
function vh360_get_gallery_max_images() {
	$settings = vh360_get_gallery_settings();
	return isset( $settings['max_images_per_gallery'] ) ? absint( $settings['max_images_per_gallery'] ) : 50;
}

/**
 * Validate gallery image upload.
 *
 * @param array $file File array from $_FILES.
 * @return array Array with 'valid' (bool) and 'message' (string) keys.
 */
function vh360_validate_gallery_image( $file ) {
	$result = array(
		'valid'   => false,
		'message' => '',
	);

	// Check file type.
	$allowed_types = vh360_get_gallery_allowed_image_types();
	$file_info     = wp_check_filetype( $file['name'] );
	$extension     = strtolower( $file_info['ext'] );

	if ( ! $extension || ! in_array( $extension, $allowed_types, true ) ) {
		$result['message'] = sprintf(
			/* translators: %s: comma-separated list of allowed file types */
			__( 'Invalid file type. Allowed types: %s', 'videohub360-theme' ),
			implode( ', ', $allowed_types )
		);
		return $result;
	}

	// Check file size.
	$max_size = vh360_get_gallery_max_image_size();
	if ( $file['size'] > $max_size ) {
		$settings = vh360_get_gallery_settings();
		$result['message'] = sprintf(
			/* translators: %d: maximum file size in MB */
			__( 'File too large. Maximum size: %d MB', 'videohub360-theme' ),
			$settings['max_image_size']
		);
		return $result;
	}

	// Verify it's actually an image.
	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	if ( ! $check['type'] || strpos( $check['type'], 'image/' ) !== 0 ) {
		$result['message'] = __( 'File is not a valid image.', 'videohub360-theme' );
		return $result;
	}

	$result['valid'] = true;
	return $result;
}

/**
 * Get gallery URL.
 *
 * @param int $gallery_id Gallery post ID.
 * @return string Gallery permalink.
 */
function vh360_get_gallery_url( $gallery_id ) {
	return get_permalink( $gallery_id );
}

/**
 * Get gallery archive URL.
 *
 * @return string Archive permalink.
 */
function vh360_get_gallery_archive_url() {
	return get_post_type_archive_link( 'vh360_gallery' );
}

/**
 * Format gallery data for JSON/API.
 *
 * @param int $gallery_id Gallery post ID.
 * @return array Gallery data array.
 */
function vh360_format_gallery_data( $gallery_id ) {
	$gallery = get_post( $gallery_id );
	if ( ! $gallery || 'vh360_gallery' !== $gallery->post_type ) {
		return array();
	}

	$images     = vh360_get_gallery_images( $gallery_id, 'medium' );
	$categories = wp_get_post_terms( $gallery_id, 'vh360_gallery_category', array( 'fields' => 'names' ) );
	$tags       = wp_get_post_terms( $gallery_id, 'vh360_gallery_tag', array( 'fields' => 'names' ) );

	return array(
		'id'          => $gallery_id,
		'title'       => get_the_title( $gallery_id ),
		'excerpt'     => get_the_excerpt( $gallery_id ),
		'url'         => get_permalink( $gallery_id ),
		'cover'       => get_the_post_thumbnail_url( $gallery_id, 'medium' ),
		'author'      => array(
			'id'   => $gallery->post_author,
			'name' => get_the_author_meta( 'display_name', $gallery->post_author ),
		),
		'date'        => get_the_date( 'c', $gallery_id ),
		'modified'    => get_the_modified_date( 'c', $gallery_id ),
		'image_count' => count( $images ),
		'images'      => $images,
		'layout'      => vh360_get_gallery_layout( $gallery_id ),
		'columns'     => vh360_get_gallery_columns( $gallery_id ),
		'lightbox'    => vh360_gallery_has_lightbox( $gallery_id ),
		'categories'  => is_array( $categories ) ? $categories : array(),
		'tags'        => is_array( $tags ) ? $tags : array(),
		'status'      => $gallery->post_status,
	);
}
