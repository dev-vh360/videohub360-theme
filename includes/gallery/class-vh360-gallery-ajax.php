<?php
/**
 * Gallery AJAX Handler
 *
 * Handles all AJAX requests for gallery operations.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_Gallery_Ajax
 *
 * AJAX endpoints for gallery management.
 */
class VH360_Gallery_Ajax {

	/**
	 * Singleton instance.
	 *
	 * @var VH360_Gallery_Ajax|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return VH360_Gallery_Ajax
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
		// Gallery CRUD operations.
		add_action( 'wp_ajax_vh360_create_gallery', array( $this, 'create_gallery' ) );
		add_action( 'wp_ajax_vh360_update_gallery', array( $this, 'update_gallery' ) );
		add_action( 'wp_ajax_vh360_delete_gallery', array( $this, 'delete_gallery' ) );
		add_action( 'wp_ajax_vh360_get_gallery', array( $this, 'get_gallery' ) );
		add_action( 'wp_ajax_vh360_get_user_galleries', array( $this, 'get_user_galleries' ) );

		// Image operations.
		add_action( 'wp_ajax_vh360_upload_gallery_images', array( $this, 'upload_images' ) );
		add_action( 'wp_ajax_vh360_delete_gallery_image', array( $this, 'delete_image' ) );
		add_action( 'wp_ajax_vh360_reorder_gallery_images', array( $this, 'reorder_images' ) );
		add_action( 'wp_ajax_vh360_set_gallery_cover', array( $this, 'set_cover_image' ) );
	}

	/**
	 * Verify nonce and user login.
	 *
	 * @param string $nonce_action Nonce action name.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function verify_request( $nonce_action = 'vh360_gallery_nonce' ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'not_logged_in', __( 'You must be logged in to perform this action.', 'videohub360-theme' ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Security check failed.', 'videohub360-theme' ) );
		}

		return true;
	}

	/**
	 * Send JSON error response.
	 *
	 * @param string|WP_Error $error Error message or WP_Error object.
	 */
	private function send_error( $error ) {
		$message = is_wp_error( $error ) ? $error->get_error_message() : $error;
		wp_send_json_error( array( 'message' => $message ) );
	}

	/**
	 * Create a new gallery.
	 */
	public function create_gallery() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		// Use centralized membership-aware permission helper.
		if ( ! function_exists( 'vh360_user_can_create_galleries' ) || ! vh360_user_can_create_galleries() ) {
			$this->send_error( __( 'You do not have permission to create galleries.', 'videohub360-theme' ) );
		}

		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$status      = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'publish';

		if ( empty( $title ) ) {
			$this->send_error( __( 'Gallery title is required.', 'videohub360-theme' ) );
		}

		// Validate status.
		$allowed_statuses = array( 'publish', 'draft', 'pending' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'draft';
		}

		// Create gallery post.
		$post_data = array(
			'post_type'    => 'vh360_gallery',
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => $status,
			'post_author'  => get_current_user_id(),
		);

		$gallery_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $gallery_id ) ) {
			$this->send_error( $gallery_id );
		}

		// Set default meta values.
		update_post_meta( $gallery_id, '_vh360_gallery_layout', 'grid' );
		update_post_meta( $gallery_id, '_vh360_gallery_columns', 3 );
		update_post_meta( $gallery_id, '_vh360_gallery_lightbox', '1' );
		update_post_meta( $gallery_id, '_vh360_gallery_image_size', 'medium' );
		update_post_meta( $gallery_id, '_vh360_gallery_images', array() );

		// Handle categories.
		if ( isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) {
			$categories = array_map( 'absint', $_POST['categories'] );
			wp_set_post_terms( $gallery_id, $categories, 'vh360_gallery_category' );
		}

		// Handle tags.
		if ( isset( $_POST['tags'] ) ) {
			$tags = sanitize_text_field( wp_unslash( $_POST['tags'] ) );
			$tags = array_map( 'trim', explode( ',', $tags ) );
			$tags = array_filter( $tags );
			if ( ! empty( $tags ) ) {
				wp_set_post_terms( $gallery_id, $tags, 'vh360_gallery_tag' );
			}
		}

		wp_send_json_success( array(
			'message'    => __( 'Gallery created successfully.', 'videohub360-theme' ),
			'gallery_id' => $gallery_id,
			'gallery'    => vh360_format_gallery_data( $gallery_id ),
		) );
	}

	/**
	 * Update an existing gallery.
	 */
	public function update_gallery() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		$gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;

		if ( ! $gallery_id ) {
			$this->send_error( __( 'Invalid gallery ID.', 'videohub360-theme' ) );
		}

		if ( ! VH360_Gallery_Capabilities::can_edit_gallery( $gallery_id ) ) {
			$this->send_error( __( 'You do not have permission to edit this gallery.', 'videohub360-theme' ) );
		}

		$post_data = array( 'ID' => $gallery_id );

		// Update title if provided.
		if ( isset( $_POST['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		}

		// Update description if provided.
		if ( isset( $_POST['description'] ) ) {
			$post_data['post_content'] = wp_kses_post( wp_unslash( $_POST['description'] ) );
		}

		// Update status if provided.
		if ( isset( $_POST['status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_POST['status'] ) );
			$allowed_statuses = array( 'publish', 'draft', 'pending' );
			if ( in_array( $status, $allowed_statuses, true ) ) {
				$post_data['post_status'] = $status;
			}
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result );
		}

		// Update layout settings.
		if ( isset( $_POST['layout'] ) ) {
			$layout = sanitize_text_field( wp_unslash( $_POST['layout'] ) );
			if ( in_array( $layout, array( 'grid', 'masonry', 'justified' ), true ) ) {
				update_post_meta( $gallery_id, '_vh360_gallery_layout', $layout );
			}
		}

		if ( isset( $_POST['columns'] ) ) {
			$columns = absint( $_POST['columns'] );
			if ( $columns >= 1 && $columns <= 6 ) {
				update_post_meta( $gallery_id, '_vh360_gallery_columns', $columns );
			}
		}

		if ( isset( $_POST['lightbox'] ) ) {
			$lightbox = filter_var( $_POST['lightbox'], FILTER_VALIDATE_BOOLEAN ) ? '1' : '0';
			update_post_meta( $gallery_id, '_vh360_gallery_lightbox', $lightbox );
		}

		if ( isset( $_POST['image_size'] ) ) {
			$size = sanitize_text_field( wp_unslash( $_POST['image_size'] ) );
			$valid_sizes = array( 'thumbnail', 'medium', 'large', 'full', 'vh360-gallery-thumb' );
			if ( in_array( $size, $valid_sizes, true ) ) {
				update_post_meta( $gallery_id, '_vh360_gallery_image_size', $size );
			}
		}

		// Handle categories.
		if ( isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) {
			$categories = array_map( 'absint', $_POST['categories'] );
			wp_set_post_terms( $gallery_id, $categories, 'vh360_gallery_category' );
		}

		// Handle tags.
		if ( isset( $_POST['tags'] ) ) {
			$tags = sanitize_text_field( wp_unslash( $_POST['tags'] ) );
			$tags = array_map( 'trim', explode( ',', $tags ) );
			$tags = array_filter( $tags );
			wp_set_post_terms( $gallery_id, $tags, 'vh360_gallery_tag' );
		}

		wp_send_json_success( array(
			'message' => __( 'Gallery updated successfully.', 'videohub360-theme' ),
			'gallery' => vh360_format_gallery_data( $gallery_id ),
		) );
	}

	/**
	 * Delete a gallery.
	 */
	public function delete_gallery() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		$gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;

		if ( ! $gallery_id ) {
			$this->send_error( __( 'Invalid gallery ID.', 'videohub360-theme' ) );
		}

		if ( ! VH360_Gallery_Capabilities::can_delete_gallery( $gallery_id ) ) {
			$this->send_error( __( 'You do not have permission to delete this gallery.', 'videohub360-theme' ) );
		}

		// Move to trash instead of permanent delete.
		$result = wp_trash_post( $gallery_id );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to delete gallery.', 'videohub360-theme' ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Gallery deleted successfully.', 'videohub360-theme' ),
		) );
	}

	/**
	 * Get gallery data.
	 */
	public function get_gallery() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		$gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;

		if ( ! $gallery_id ) {
			$this->send_error( __( 'Invalid gallery ID.', 'videohub360-theme' ) );
		}

		$gallery = get_post( $gallery_id );
		if ( ! $gallery || 'vh360_gallery' !== $gallery->post_type ) {
			$this->send_error( __( 'Gallery not found.', 'videohub360-theme' ) );
		}

		// Check if user can view this gallery.
		if ( 'publish' !== $gallery->post_status && ! VH360_Gallery_Capabilities::can_edit_gallery( $gallery_id ) ) {
			$this->send_error( __( 'You do not have permission to view this gallery.', 'videohub360-theme' ) );
		}

		wp_send_json_success( array(
			'gallery' => vh360_format_gallery_data( $gallery_id ),
		) );
	}

	/**
	 * Get user's galleries.
	 */
	public function get_user_galleries() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		$user_id = get_current_user_id();
		$page    = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 12;
		$status  = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'any';

		$args = array(
			'post_type'      => 'vh360_gallery',
			'author'         => $user_id,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Handle status filter.
		if ( 'any' === $status ) {
			$args['post_status'] = array( 'publish', 'pending', 'draft' );
		} else {
			$args['post_status'] = $status;
		}

		$query = new WP_Query( $args );
		$galleries = array();

		foreach ( $query->posts as $gallery ) {
			$galleries[] = vh360_format_gallery_data( $gallery->ID );
		}

		wp_send_json_success( array(
			'galleries'  => $galleries,
			'total'      => $query->found_posts,
			'pages'      => $query->max_num_pages,
			'page'       => $page,
		) );
	}

	/**
	 * Upload images to a gallery.
	 */
	public function upload_images() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		// Check if user has permission to upload files
		if ( ! current_user_can( 'upload_files' ) ) {
			$this->send_error( __( 'You do not have permission to upload files.', 'videohub360-theme' ) );
		}

		$gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;

		if ( ! $gallery_id ) {
			$this->send_error( __( 'Invalid gallery ID.', 'videohub360-theme' ) );
		}

		if ( ! VH360_Gallery_Capabilities::can_manage_gallery_images( $gallery_id ) ) {
			$this->send_error( __( 'You do not have permission to add images to this gallery.', 'videohub360-theme' ) );
		}

		if ( empty( $_FILES['images'] ) ) {
			$this->send_error( __( 'No images uploaded.', 'videohub360-theme' ) );
		}

		// Check max images limit.
		$current_images = get_post_meta( $gallery_id, '_vh360_gallery_images', true );
		if ( ! is_array( $current_images ) ) {
			$current_images = array();
		}

		$max_images = vh360_get_gallery_max_images();
		$files_count = is_array( $_FILES['images']['name'] ) ? count( $_FILES['images']['name'] ) : 1;

		if ( count( $current_images ) + $files_count > $max_images ) {
			$this->send_error( sprintf(
				/* translators: %d: maximum number of images allowed */
				__( 'Maximum %d images allowed per gallery.', 'videohub360-theme' ),
				$max_images
			) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$uploaded_images = array();
		$errors = array();

		// Handle multiple files.
		if ( is_array( $_FILES['images']['name'] ) ) {
			$file_count = count( $_FILES['images']['name'] );
			for ( $i = 0; $i < $file_count; $i++ ) {
				$file = array(
					'name'     => sanitize_file_name( $_FILES['images']['name'][ $i ] ),
					'type'     => $_FILES['images']['type'][ $i ],
					'tmp_name' => $_FILES['images']['tmp_name'][ $i ],
					'error'    => $_FILES['images']['error'][ $i ],
					'size'     => $_FILES['images']['size'][ $i ],
				);

				$result = $this->process_image_upload( $file, $gallery_id );
				if ( is_wp_error( $result ) ) {
					$errors[] = $file['name'] . ': ' . $result->get_error_message();
				} else {
					$uploaded_images[] = $result;
					$current_images[] = $result['id'];
				}
			}
		} else {
			$file = array(
				'name'     => sanitize_file_name( $_FILES['images']['name'] ),
				'type'     => $_FILES['images']['type'],
				'tmp_name' => $_FILES['images']['tmp_name'],
				'error'    => $_FILES['images']['error'],
				'size'     => $_FILES['images']['size'],
			);

			$result = $this->process_image_upload( $file, $gallery_id );
			if ( is_wp_error( $result ) ) {
				$errors[] = $file['name'] . ': ' . $result->get_error_message();
			} else {
				$uploaded_images[] = $result;
				$current_images[] = $result['id'];
			}
		}

		// Update gallery images.
		update_post_meta( $gallery_id, '_vh360_gallery_images', $current_images );

		// Set first image as cover if no cover exists.
		if ( ! has_post_thumbnail( $gallery_id ) && ! empty( $current_images ) ) {
			set_post_thumbnail( $gallery_id, $current_images[0] );
		}

		$response = array(
			'message'     => __( 'Images uploaded successfully.', 'videohub360-theme' ),
			'images'      => $uploaded_images,
			'total_count' => count( $current_images ),
		);

		if ( ! empty( $errors ) ) {
			$response['errors'] = $errors;
		}

		wp_send_json_success( $response );
	}

	/**
	 * Process a single image upload.
	 *
	 * @param array $file    File data from $_FILES.
	 * @param int   $gallery_id Gallery post ID.
	 * @return array|WP_Error Image data array or error.
	 */
	private function process_image_upload( $file, $gallery_id ) {
		// Validate the file.
		$validation = vh360_validate_gallery_image( $file );
		if ( ! $validation['valid'] ) {
			return new WP_Error( 'invalid_file', $validation['message'] );
		}

		// Upload the file.
		$_FILES['vh360_gallery_image'] = $file;
		$attachment_id = media_handle_upload( 'vh360_gallery_image', $gallery_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Get image data.
		$thumb_src = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
		$full_src  = wp_get_attachment_image_src( $attachment_id, 'full' );

		return array(
			'id'     => $attachment_id,
			'src'    => $thumb_src ? $thumb_src[0] : '',
			'full'   => $full_src ? $full_src[0] : '',
			'width'  => $full_src ? $full_src[1] : 0,
			'height' => $full_src ? $full_src[2] : 0,
		);
	}

	/**
	 * Delete an image from a gallery.
	 */
	public function delete_image() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		$gallery_id    = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( ! $gallery_id || ! $attachment_id ) {
			$this->send_error( __( 'Invalid gallery or image ID.', 'videohub360-theme' ) );
		}

		if ( ! VH360_Gallery_Capabilities::can_manage_gallery_images( $gallery_id ) ) {
			$this->send_error( __( 'You do not have permission to remove images from this gallery.', 'videohub360-theme' ) );
		}

		// Get current images.
		$images = get_post_meta( $gallery_id, '_vh360_gallery_images', true );
		if ( ! is_array( $images ) ) {
			$images = array();
		}

		// Remove the image from the array.
		$key = array_search( $attachment_id, $images );
		if ( false !== $key ) {
			unset( $images[ $key ] );
			$images = array_values( $images ); // Re-index.
			update_post_meta( $gallery_id, '_vh360_gallery_images', $images );
		}

		// Optionally delete the attachment (if checkbox is checked).
		$delete_file = isset( $_POST['delete_file'] ) && filter_var( $_POST['delete_file'], FILTER_VALIDATE_BOOLEAN );
		if ( $delete_file ) {
			// Only delete if image is not used elsewhere.
			$attachment_author = get_post_field( 'post_author', $attachment_id );
			if ( (int) $attachment_author === get_current_user_id() || current_user_can( 'delete_others_posts' ) ) {
				wp_delete_attachment( $attachment_id, true );
			}
		}

		// If deleted image was the cover, set a new cover.
		if ( (int) get_post_thumbnail_id( $gallery_id ) === $attachment_id ) {
			delete_post_thumbnail( $gallery_id );
			if ( ! empty( $images ) ) {
				set_post_thumbnail( $gallery_id, $images[0] );
			}
		}

		wp_send_json_success( array(
			'message'     => __( 'Image removed from gallery.', 'videohub360-theme' ),
			'total_count' => count( $images ),
		) );
	}

	/**
	 * Reorder gallery images.
	 */
	public function reorder_images() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		$gallery_id = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;
		$order      = isset( $_POST['order'] ) ? $_POST['order'] : array();

		if ( ! $gallery_id ) {
			$this->send_error( __( 'Invalid gallery ID.', 'videohub360-theme' ) );
		}

		if ( ! VH360_Gallery_Capabilities::can_manage_gallery_images( $gallery_id ) ) {
			$this->send_error( __( 'You do not have permission to reorder images in this gallery.', 'videohub360-theme' ) );
		}

		if ( ! is_array( $order ) ) {
			$this->send_error( __( 'Invalid image order data.', 'videohub360-theme' ) );
		}

		// Sanitize and validate the order.
		$new_order = array_map( 'absint', $order );
		$new_order = array_filter( $new_order );

		// Verify all IDs are valid attachments.
		$current_images = get_post_meta( $gallery_id, '_vh360_gallery_images', true );
		if ( ! is_array( $current_images ) ) {
			$current_images = array();
		}

		// Only include IDs that are in the current images.
		$valid_order = array();
		foreach ( $new_order as $id ) {
			if ( in_array( $id, $current_images ) ) {
				$valid_order[] = $id;
			}
		}

		// Update the order.
		update_post_meta( $gallery_id, '_vh360_gallery_images', $valid_order );

		wp_send_json_success( array(
			'message' => __( 'Image order updated.', 'videohub360-theme' ),
		) );
	}

	/**
	 * Set gallery cover image.
	 */
	public function set_cover_image() {
		$verify = $this->verify_request();
		if ( is_wp_error( $verify ) ) {
			$this->send_error( $verify );
		}

		$gallery_id    = isset( $_POST['gallery_id'] ) ? absint( $_POST['gallery_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( ! $gallery_id || ! $attachment_id ) {
			$this->send_error( __( 'Invalid gallery or image ID.', 'videohub360-theme' ) );
		}

		if ( ! VH360_Gallery_Capabilities::can_edit_gallery( $gallery_id ) ) {
			$this->send_error( __( 'You do not have permission to edit this gallery.', 'videohub360-theme' ) );
		}

		// Verify the attachment exists and is an image.
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			$this->send_error( __( 'Invalid image.', 'videohub360-theme' ) );
		}

		// Set as featured image.
		set_post_thumbnail( $gallery_id, $attachment_id );

		$cover_url = get_the_post_thumbnail_url( $gallery_id, 'medium' );

		wp_send_json_success( array(
			'message'   => __( 'Cover image updated.', 'videohub360-theme' ),
			'cover_url' => $cover_url,
		) );
	}
}

// Initialize.
VH360_Gallery_Ajax::get_instance();
