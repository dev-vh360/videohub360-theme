<?php
/**
 * Gallery Post Type Registration
 *
 * Registers the vh360_gallery custom post type, category and tag taxonomies.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_Gallery_Post_Type
 *
 * Handles gallery post type and taxonomy registration.
 */
class VH360_Gallery_Post_Type {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'vh360_gallery';

	/**
	 * Category taxonomy slug.
	 *
	 * @var string
	 */
	const TAXONOMY_CATEGORY = 'vh360_gallery_category';

	/**
	 * Tag taxonomy slug.
	 *
	 * @var string
	 */
	const TAXONOMY_TAG = 'vh360_gallery_tag';

	/**
	 * Singleton instance.
	 *
	 * @var VH360_Gallery_Post_Type|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return VH360_Gallery_Post_Type
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
		add_action( 'init', array( $this, 'register_post_type' ), 5 );
		add_action( 'init', array( $this, 'register_taxonomies' ), 5 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_gallery_meta' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue admin scripts for gallery management.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		global $post_type;

		// Only load on gallery post type edit screens.
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		if ( self::POST_TYPE !== $post_type ) {
			return;
		}

		// Enqueue WordPress media library scripts.
		wp_enqueue_media();

		// Enqueue jQuery UI sortable as a fallback.
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Enqueue gallery admin script.
		wp_enqueue_script(
			'vh360-gallery-admin',
			VH360_THEME_URI . '/assets/js/admin/gallery-admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			vh360_theme_asset_version('assets/js/admin/gallery-admin.js'),
			true
		);

		// Localize script.
		wp_localize_script( 'vh360-gallery-admin', 'vh360GalleryAdmin', array(
			'i18n' => array(
				'selectImages' => __( 'Select Gallery Images', 'videohub360-theme' ),
				'addToGallery' => __( 'Add to Gallery', 'videohub360-theme' ),
				'removeImage'  => __( 'Remove image', 'videohub360-theme' ),
			),
		) );
	}

	/**
	 * Register gallery post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Galleries', 'Post Type General Name', 'videohub360-theme' ),
			'singular_name'         => _x( 'Gallery', 'Post Type Singular Name', 'videohub360-theme' ),
			'menu_name'             => __( 'Galleries', 'videohub360-theme' ),
			'name_admin_bar'        => __( 'Gallery', 'videohub360-theme' ),
			'archives'              => __( 'Gallery Archives', 'videohub360-theme' ),
			'attributes'            => __( 'Gallery Attributes', 'videohub360-theme' ),
			'parent_item_colon'     => __( 'Parent Gallery:', 'videohub360-theme' ),
			'all_items'             => __( 'All Galleries', 'videohub360-theme' ),
			'add_new_item'          => __( 'Add New Gallery', 'videohub360-theme' ),
			'add_new'               => __( 'Add New', 'videohub360-theme' ),
			'new_item'              => __( 'New Gallery', 'videohub360-theme' ),
			'edit_item'             => __( 'Edit Gallery', 'videohub360-theme' ),
			'update_item'           => __( 'Update Gallery', 'videohub360-theme' ),
			'view_item'             => __( 'View Gallery', 'videohub360-theme' ),
			'view_items'            => __( 'View Galleries', 'videohub360-theme' ),
			'search_items'          => __( 'Search Gallery', 'videohub360-theme' ),
			'not_found'             => __( 'Not found', 'videohub360-theme' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'videohub360-theme' ),
			'featured_image'        => __( 'Cover Image', 'videohub360-theme' ),
			'set_featured_image'    => __( 'Set cover image', 'videohub360-theme' ),
			'remove_featured_image' => __( 'Remove cover image', 'videohub360-theme' ),
			'use_featured_image'    => __( 'Use as cover image', 'videohub360-theme' ),
			'insert_into_item'      => __( 'Insert into gallery', 'videohub360-theme' ),
			'uploaded_to_this_item' => __( 'Uploaded to this gallery', 'videohub360-theme' ),
			'items_list'            => __( 'Galleries list', 'videohub360-theme' ),
			'items_list_navigation' => __( 'Galleries list navigation', 'videohub360-theme' ),
			'filter_items_list'     => __( 'Filter galleries list', 'videohub360-theme' ),
		);

		$args = array(
			'label'               => __( 'Gallery', 'videohub360-theme' ),
			'description'         => __( 'Photo galleries for VideoHub360', 'videohub360-theme' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 26,
			'menu_icon'           => 'dashicons-format-gallery',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => array( 'vh360_gallery', 'vh360_galleries' ),
			'map_meta_cap'        => true,
			'rewrite'             => array(
				'slug'       => 'galleries',
				'with_front' => false,
			),
			'show_in_rest'        => true,
			'rest_base'           => 'vh360-galleries',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register gallery taxonomies.
	 */
	public function register_taxonomies() {
		// Category taxonomy.
		$cat_labels = array(
			'name'                       => _x( 'Gallery Categories', 'Taxonomy General Name', 'videohub360-theme' ),
			'singular_name'              => _x( 'Gallery Category', 'Taxonomy Singular Name', 'videohub360-theme' ),
			'menu_name'                  => __( 'Categories', 'videohub360-theme' ),
			'all_items'                  => __( 'All Categories', 'videohub360-theme' ),
			'parent_item'                => __( 'Parent Category', 'videohub360-theme' ),
			'parent_item_colon'          => __( 'Parent Category:', 'videohub360-theme' ),
			'new_item_name'              => __( 'New Category Name', 'videohub360-theme' ),
			'add_new_item'               => __( 'Add New Category', 'videohub360-theme' ),
			'edit_item'                  => __( 'Edit Category', 'videohub360-theme' ),
			'update_item'                => __( 'Update Category', 'videohub360-theme' ),
			'view_item'                  => __( 'View Category', 'videohub360-theme' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'videohub360-theme' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'videohub360-theme' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'videohub360-theme' ),
			'popular_items'              => __( 'Popular Categories', 'videohub360-theme' ),
			'search_items'               => __( 'Search Categories', 'videohub360-theme' ),
			'not_found'                  => __( 'Not Found', 'videohub360-theme' ),
			'no_terms'                   => __( 'No categories', 'videohub360-theme' ),
			'items_list'                 => __( 'Categories list', 'videohub360-theme' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'videohub360-theme' ),
		);

		$cat_args = array(
			'labels'            => $cat_labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'gallery-category',
				'with_front' => false,
			),
		);

		register_taxonomy( self::TAXONOMY_CATEGORY, array( self::POST_TYPE ), $cat_args );

		// Tag taxonomy.
		$tag_labels = array(
			'name'                       => _x( 'Gallery Tags', 'Taxonomy General Name', 'videohub360-theme' ),
			'singular_name'              => _x( 'Gallery Tag', 'Taxonomy Singular Name', 'videohub360-theme' ),
			'menu_name'                  => __( 'Tags', 'videohub360-theme' ),
			'all_items'                  => __( 'All Tags', 'videohub360-theme' ),
			'new_item_name'              => __( 'New Tag Name', 'videohub360-theme' ),
			'add_new_item'               => __( 'Add New Tag', 'videohub360-theme' ),
			'edit_item'                  => __( 'Edit Tag', 'videohub360-theme' ),
			'update_item'                => __( 'Update Tag', 'videohub360-theme' ),
			'view_item'                  => __( 'View Tag', 'videohub360-theme' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'videohub360-theme' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'videohub360-theme' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'videohub360-theme' ),
			'popular_items'              => __( 'Popular Tags', 'videohub360-theme' ),
			'search_items'               => __( 'Search Tags', 'videohub360-theme' ),
			'not_found'                  => __( 'Not Found', 'videohub360-theme' ),
			'no_terms'                   => __( 'No tags', 'videohub360-theme' ),
			'items_list'                 => __( 'Tags list', 'videohub360-theme' ),
			'items_list_navigation'      => __( 'Tags list navigation', 'videohub360-theme' ),
		);

		$tag_args = array(
			'labels'            => $tag_labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'gallery-tag',
				'with_front' => false,
			),
		);

		register_taxonomy( self::TAXONOMY_TAG, array( self::POST_TYPE ), $tag_args );
	}

	/**
	 * Add meta boxes for gallery settings.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'vh360_gallery_images',
			__( 'Gallery Images', 'videohub360-theme' ),
			array( $this, 'render_images_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'vh360_gallery_settings',
			__( 'Gallery Settings', 'videohub360-theme' ),
			array( $this, 'render_settings_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render the gallery images meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_images_meta_box( $post ) {
		wp_nonce_field( 'vh360_save_gallery_meta', 'vh360_gallery_meta_nonce' );

		$images = get_post_meta( $post->ID, '_vh360_gallery_images', true );
		if ( ! is_array( $images ) ) {
			$images = array();
		}
		?>
		<div class="vh360-gallery-images-admin">
			<p class="description"><?php esc_html_e( 'Add, remove, and reorder images in your gallery.', 'videohub360-theme' ); ?></p>
			
			<div id="vh360-gallery-images-container" class="vh360-gallery-images-list">
				<?php foreach ( $images as $attachment_id ) : ?>
					<?php
					$attachment_id = absint( $attachment_id );
					if ( ! $attachment_id ) {
						continue;
					}
					$thumb = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
					if ( ! $thumb ) {
						continue;
					}
					?>
					<div class="vh360-gallery-image-item" data-id="<?php echo esc_attr( $attachment_id ); ?>">
						<img src="<?php echo esc_url( $thumb[0] ); ?>" alt="">
						<button type="button" class="vh360-gallery-image-remove" aria-label="<?php esc_attr_e( 'Remove image', 'videohub360-theme' ); ?>">&times;</button>
						<input type="hidden" name="vh360_gallery_images[]" value="<?php echo esc_attr( $attachment_id ); ?>">
					</div>
				<?php endforeach; ?>
			</div>
			
			<p>
				<button type="button" class="button vh360-gallery-add-images" id="vh360-gallery-add-images">
					<?php esc_html_e( 'Add Images', 'videohub360-theme' ); ?>
				</button>
			</p>
		</div>
		
		<style>
			.vh360-gallery-images-list {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				margin: 15px 0;
				min-height: 100px;
				padding: 15px;
				background: #f9f9f9;
				border: 1px dashed #ccc;
				border-radius: 4px;
			}
			.vh360-gallery-image-item {
				position: relative;
				width: 100px;
				height: 100px;
				cursor: move;
			}
			.vh360-gallery-image-item img {
				width: 100%;
				height: 100%;
				object-fit: cover;
				border-radius: 4px;
			}
			.vh360-gallery-image-remove {
				position: absolute;
				top: -8px;
				right: -8px;
				width: 20px;
				height: 20px;
				border-radius: 50%;
				background: #dc3545;
				color: #fff;
				border: none;
				cursor: pointer;
				font-size: 14px;
				line-height: 1;
				padding: 0;
			}
			.vh360-gallery-image-item.sortable-ghost {
				opacity: 0.4;
			}
		</style>
		<?php
	}

	/**
	 * Render the gallery settings meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_settings_meta_box( $post ) {
		$layout      = get_post_meta( $post->ID, '_vh360_gallery_layout', true );
		$columns     = get_post_meta( $post->ID, '_vh360_gallery_columns', true );
		$lightbox    = get_post_meta( $post->ID, '_vh360_gallery_lightbox', true );
		$image_size  = get_post_meta( $post->ID, '_vh360_gallery_image_size', true );

		// Set defaults.
		$layout     = $layout ? $layout : 'grid';
		$columns    = $columns ? absint( $columns ) : 3;
		$lightbox   = '' === $lightbox ? '1' : $lightbox;
		$image_size = $image_size ? $image_size : 'medium';
		?>
		<p>
			<label for="vh360_gallery_layout"><strong><?php esc_html_e( 'Layout', 'videohub360-theme' ); ?></strong></label><br>
			<select name="vh360_gallery_layout" id="vh360_gallery_layout" style="width: 100%;">
				<option value="grid" <?php selected( $layout, 'grid' ); ?>><?php esc_html_e( 'Grid', 'videohub360-theme' ); ?></option>
				<option value="masonry" <?php selected( $layout, 'masonry' ); ?>><?php esc_html_e( 'Masonry', 'videohub360-theme' ); ?></option>
				<option value="justified" <?php selected( $layout, 'justified' ); ?>><?php esc_html_e( 'Justified', 'videohub360-theme' ); ?></option>
			</select>
		</p>
		
		<p>
			<label for="vh360_gallery_columns"><strong><?php esc_html_e( 'Columns', 'videohub360-theme' ); ?></strong></label><br>
			<select name="vh360_gallery_columns" id="vh360_gallery_columns" style="width: 100%;">
				<?php for ( $i = 1; $i <= 6; $i++ ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $columns, $i ); ?>><?php echo esc_html( $i ); ?></option>
				<?php endfor; ?>
			</select>
		</p>
		
		<p>
			<label for="vh360_gallery_image_size"><strong><?php esc_html_e( 'Image Size', 'videohub360-theme' ); ?></strong></label><br>
			<select name="vh360_gallery_image_size" id="vh360_gallery_image_size" style="width: 100%;">
				<option value="thumbnail" <?php selected( $image_size, 'thumbnail' ); ?>><?php esc_html_e( 'Thumbnail', 'videohub360-theme' ); ?></option>
				<option value="medium" <?php selected( $image_size, 'medium' ); ?>><?php esc_html_e( 'Medium', 'videohub360-theme' ); ?></option>
				<option value="large" <?php selected( $image_size, 'large' ); ?>><?php esc_html_e( 'Large', 'videohub360-theme' ); ?></option>
				<option value="full" <?php selected( $image_size, 'full' ); ?>><?php esc_html_e( 'Full', 'videohub360-theme' ); ?></option>
				<option value="vh360-gallery-thumb" <?php selected( $image_size, 'vh360-gallery-thumb' ); ?>><?php esc_html_e( 'Gallery Thumb (400x400)', 'videohub360-theme' ); ?></option>
			</select>
		</p>
		
		<p>
			<label>
				<input type="checkbox" name="vh360_gallery_lightbox" value="1" <?php checked( $lightbox, '1' ); ?>>
				<strong><?php esc_html_e( 'Enable Lightbox', 'videohub360-theme' ); ?></strong>
			</label>
		</p>
		<?php
	}

	/**
	 * Save gallery meta data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_gallery_meta( $post_id, $post ) {
		// Check nonce.
		if ( ! isset( $_POST['vh360_gallery_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vh360_gallery_meta_nonce'] ) ), 'vh360_save_gallery_meta' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_vh360_gallery', $post_id ) ) {
			return;
		}

		// Save images.
		if ( isset( $_POST['vh360_gallery_images'] ) && is_array( $_POST['vh360_gallery_images'] ) ) {
			$images = array_map( 'absint', $_POST['vh360_gallery_images'] );
			$images = array_filter( $images );
			update_post_meta( $post_id, '_vh360_gallery_images', $images );
		} else {
			delete_post_meta( $post_id, '_vh360_gallery_images' );
		}

		// Save layout.
		if ( isset( $_POST['vh360_gallery_layout'] ) ) {
			$layout = sanitize_text_field( wp_unslash( $_POST['vh360_gallery_layout'] ) );
			if ( in_array( $layout, array( 'grid', 'masonry', 'justified' ), true ) ) {
				update_post_meta( $post_id, '_vh360_gallery_layout', $layout );
			}
		}

		// Save columns.
		if ( isset( $_POST['vh360_gallery_columns'] ) ) {
			$columns = absint( $_POST['vh360_gallery_columns'] );
			if ( $columns >= 1 && $columns <= 6 ) {
				update_post_meta( $post_id, '_vh360_gallery_columns', $columns );
			}
		}

		// Save image size.
		if ( isset( $_POST['vh360_gallery_image_size'] ) ) {
			$size = sanitize_text_field( wp_unslash( $_POST['vh360_gallery_image_size'] ) );
			$valid_sizes = array( 'thumbnail', 'medium', 'large', 'full', 'vh360-gallery-thumb' );
			if ( in_array( $size, $valid_sizes, true ) ) {
				update_post_meta( $post_id, '_vh360_gallery_image_size', $size );
			}
		}

		// Save lightbox.
		$lightbox = isset( $_POST['vh360_gallery_lightbox'] ) ? '1' : '0';
		update_post_meta( $post_id, '_vh360_gallery_lightbox', $lightbox );
	}

	/**
	 * Add custom admin columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['gallery_cover']  = __( 'Cover', 'videohub360-theme' );
				$new_columns['gallery_images'] = __( 'Images', 'videohub360-theme' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render custom admin columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'gallery_cover':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 60, 60 ) );
				} else {
					echo '<span class="dashicons dashicons-format-image" style="font-size: 40px; color: #ccc;"></span>';
				}
				break;

			case 'gallery_images':
				$images = get_post_meta( $post_id, '_vh360_gallery_images', true );
				$count  = is_array( $images ) ? count( $images ) : 0;
				/* translators: %d: Number of images in gallery */
				echo esc_html( sprintf( _n( '%d image', '%d images', $count, 'videohub360-theme' ), $count ) );
				break;
		}
	}
}

// Initialize.
VH360_Gallery_Post_Type::get_instance();
