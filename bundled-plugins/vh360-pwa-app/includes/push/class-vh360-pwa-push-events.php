<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Push Notification Events
 * 
 * Handles automated push notification triggers for various WordPress events.
 */
class VH360_PWA_Push_Events {
	/** @var VH360_PWA_Push_Manager */
	private $push_manager;

	/**
	 * Constructor
	 */
	public function __construct( VH360_PWA_Push_Manager $push_manager ) {
		$this->push_manager = $push_manager;
	}

	/**
	 * Register hooks
	 */
	public function register() : void {
		// Post publish hooks
		add_action( 'transition_post_status', array( $this, 'on_post_publish' ), 10, 3 );
		
		// Comment hooks
		add_action( 'wp_insert_comment', array( $this, 'on_comment_insert' ), 10, 2 );
		
		// Add meta box for posts
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
	}

	/**
	 * Get event settings
	 * 
	 * @return array
	 */
	public function get_settings() : array {
		$defaults = array(
			'new_post_enabled'      => false,
			'new_post_template'     => array(
				'title' => __( 'New Post: {post_title}', 'vh360-pwa-app' ),
				'body'  => __( '{post_excerpt}', 'vh360-pwa-app' ),
			),
			'livestream_enabled'    => false,
			'livestream_template'   => array(
				'title' => __( 'Live Now: {post_title}', 'vh360-pwa-app' ),
				'body'  => __( 'Join us for a live stream!', 'vh360-pwa-app' ),
			),
			'comment_enabled'       => false,
			'comment_template'      => array(
				'title' => __( 'New Comment on: {post_title}', 'vh360-pwa-app' ),
				'body'  => __( '{comment_author} commented: {comment_excerpt}', 'vh360-pwa-app' ),
			),
			'post_types'            => array( 'post' ),
		);

		$settings = get_option( 'vh360_pwa_push_events_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_merge( $defaults, $settings );
	}

	/**
	 * Update event settings
	 * 
	 * @param array $new_settings
	 * @return bool
	 */
	public function update_settings( array $new_settings ) : bool {
		return update_option( 'vh360_pwa_push_events_settings', $new_settings );
	}

	/**
	 * Handle post publish transition
	 * 
	 * @param string $new_status New status
	 * @param string $old_status Old status
	 * @param WP_Post $post Post object
	 */
	public function on_post_publish( string $new_status, string $old_status, $post ) : void {
		// Only trigger on transition to publish
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		$settings = $this->get_settings();
		
		// Check if post type is enabled
		$enabled_post_types = $settings['post_types'] ?? array( 'post' );
		if ( ! in_array( $post->post_type, $enabled_post_types, true ) ) {
			return;
		}

		// Check if event is for livestream
		$is_livestream = 'livestream' === $post->post_type || has_tag( 'livestream', $post );
		
		// Determine which event to trigger
		if ( $is_livestream && ! empty( $settings['livestream_enabled'] ) ) {
			$template = $settings['livestream_template'] ?? array();
		} elseif ( ! empty( $settings['new_post_enabled'] ) ) {
			$template = $settings['new_post_template'] ?? array();
		} else {
			return;
		}

		// Check if push notification is enabled for this post
		$send_push = get_post_meta( $post->ID, '_vh360_send_push_notification', true );
		if ( '1' !== $send_push ) {
			// Default to enabled if meta doesn't exist (for automatic posts)
			// Only skip if explicitly set to 0
			if ( metadata_exists( 'post', $post->ID, '_vh360_send_push_notification' ) ) {
				return;
			}
		}

		// Process template
		$message = $this->process_template( $template, $post );

		// Send notification
		$this->push_manager->send( $message );
	}

	/**
	 * Handle comment insert
	 * 
	 * @param int $comment_id Comment ID
	 * @param WP_Comment|array $comment Comment object or array
	 */
	public function on_comment_insert( $comment_id, $comment ) : void {
		$settings = $this->get_settings();

		if ( empty( $settings['comment_enabled'] ) ) {
			return;
		}

		// Get comment object if array passed
		if ( is_array( $comment ) ) {
			$comment = get_comment( $comment_id );
		}

		if ( ! $comment || 1 !== (int) $comment->comment_approved ) {
			return;
		}

		// Get post
		$post = get_post( $comment->comment_post_ID );
		if ( ! $post ) {
			return;
		}

		// Process template
		$template = $settings['comment_template'] ?? array();
		$message = $this->process_template( $template, $post, $comment );

		// Send notification
		$this->push_manager->send( $message );
	}

	/**
	 * Process template with variables
	 * 
	 * @param array $template Template data
	 * @param WP_Post $post Post object
	 * @param WP_Comment|null $comment Optional comment object
	 * @return array Processed message
	 */
	private function process_template( array $template, $post, $comment = null ) : array {
		$title = $template['title'] ?? '';
		$body = $template['body'] ?? '';

		// Get excerpt
		$excerpt = ! empty( $post->post_excerpt ) 
			? $post->post_excerpt 
			: wp_trim_words( strip_shortcodes( $post->post_content ), 20, '...' );

		// Post variables
		$vars = array(
			'{post_title}'   => get_the_title( $post ),
			'{post_excerpt}' => $excerpt,
			'{site_name}'    => get_bloginfo( 'name' ),
			'{author_name}'  => get_the_author_meta( 'display_name', $post->post_author ),
		);

		// Comment variables
		if ( $comment ) {
			$comment_excerpt = wp_trim_words( strip_tags( $comment->comment_content ), 15, '...' );
			$vars['{comment_author}'] = $comment->comment_author;
			$vars['{comment_excerpt}'] = $comment_excerpt;
		}

		// Replace variables
		$title = str_replace( array_keys( $vars ), array_values( $vars ), $title );
		$body = str_replace( array_keys( $vars ), array_values( $vars ), $body );

		return array(
			'title'     => $title,
			'body'      => $body,
			'click_url' => get_permalink( $post ),
		);
	}

	/**
	 * Add meta box to post editor
	 */
	public function add_meta_box() : void {
		$settings = $this->get_settings();
		$post_types = $settings['post_types'] ?? array( 'post' );

		add_meta_box(
			'vh360_push_notification',
			__( 'Push Notification', 'vh360-pwa-app' ),
			array( $this, 'render_meta_box' ),
			$post_types,
			'side',
			'default'
		);
	}

	/**
	 * Render meta box
	 * 
	 * @param WP_Post $post Post object
	 */
	public function render_meta_box( $post ) : void {
		wp_nonce_field( 'vh360_push_notification_meta', 'vh360_push_notification_nonce' );

		$send_push = get_post_meta( $post->ID, '_vh360_send_push_notification', true );
		$checked = ( '1' === $send_push );

		echo '<p>';
		echo '<label>';
		echo '<input type="checkbox" name="vh360_send_push_notification" value="1"' . checked( $checked, true, false ) . '> ';
		echo esc_html__( 'Send push notification when published', 'vh360-pwa-app' );
		echo '</label>';
		echo '</p>';
		echo '<p class="description">';
		echo esc_html__( 'If enabled, a push notification will be sent to all subscribers when this post is published.', 'vh360-pwa-app' );
		echo '</p>';
	}

	/**
	 * Save meta box
	 * 
	 * @param int $post_id Post ID
	 */
	public function save_meta_box( $post_id ) : void {
		// Check nonce
		if ( ! isset( $_POST['vh360_push_notification_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['vh360_push_notification_nonce'], 'vh360_push_notification_meta' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save value
		if ( isset( $_POST['vh360_send_push_notification'] ) && '1' === $_POST['vh360_send_push_notification'] ) {
			update_post_meta( $post_id, '_vh360_send_push_notification', '1' );
		} else {
			update_post_meta( $post_id, '_vh360_send_push_notification', '0' );
		}
	}
}
