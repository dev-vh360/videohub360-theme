<?php
/**
 * VH360 PWA Push Theme Notification Bridge
 *
 * Connects the theme notification system with PWA push notifications.
 * Listens to theme notification events and sends targeted push notifications.
 *
 * @package VH360_PWA_App
 * @since 1.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_PWA_Push_Theme_Notification_Bridge
 */
class VH360_PWA_Push_Theme_Notification_Bridge {
	
	/**
	 * Push manager instance
	 *
	 * @var VH360_PWA_Push_Manager
	 */
	private $push_manager;
	
	/**
	 * Types that should trigger push notifications
	 *
	 * @var array
	 */
	private $push_enabled_types = array(
		'follow',
		'mention',
		'reply',
		'message',
		// Optional (can be noisy):
		// 'like',
		// 'comment',
	);
	
	/**
	 * Constructor
	 *
	 * @param VH360_PWA_Push_Manager $push_manager Push manager instance
	 */
	public function __construct( $push_manager ) {
		$this->push_manager = $push_manager;
		
		// Listen to theme notification created hook
		add_action( 'vh360_notification_created', array( $this, 'maybe_send_push' ), 10, 2 );
		
		// Allow filtering push enabled types
		$this->push_enabled_types = apply_filters( 'vh360_pwa_push_enabled_types', $this->push_enabled_types );
	}
	
	/**
	 * Maybe send push notification when a theme notification is created
	 *
	 * @param int   $notification_id Notification ID
	 * @param array $payload         Notification data payload
	 */
	public function maybe_send_push( $notification_id, $payload ) {
		// Validate payload
		if ( empty( $payload['user_id'] ) || empty( $payload['type'] ) || empty( $payload['actor_id'] ) ) {
			return;
		}
		
		// Check if this notification type should trigger push
		if ( ! in_array( $payload['type'], $this->push_enabled_types, true ) ) {
			return;
		}
		
		// Check if push system is available and configured
		if ( ! $this->push_manager ) {
			return;
		}
		
		$settings = $this->push_manager->get_settings();
		if ( empty( $settings['mode'] ) || 'disabled' === $settings['mode'] ) {
			return;
		}
		
		// Build push message
		$push_message = $this->format_push_message( $payload );
		if ( ! $push_message ) {
			return;
		}
		
		// Get click URL
		$click_url = $this->get_notification_url( $payload );
		if ( $click_url ) {
			$push_message['click_url'] = $click_url;
		}
		
		// Target specific user
		$audience = array(
			'user_ids' => array( $payload['user_id'] ),
		);
		
		// Send push notification
		try {
			$result = $this->push_manager->send( $push_message, $audience );
			
			// Log result for debugging
			if ( $result['success'] ) {
				vh360_pwa_debug_log( sprintf(
					'[VH360 Push Bridge] Push sent for notification #%d (type: %s, user: %d)',
					$notification_id,
					$payload['type'],
					$payload['user_id']
				) );
			} else {
				vh360_pwa_debug_log( sprintf(
					'[VH360 Push Bridge] Push failed for notification #%d: %s',
					$notification_id,
					$result['message'] ?? 'Unknown error'
				) );
			}
		} catch ( Exception $e ) {
			// Silently fail - don't break notification creation
			vh360_pwa_debug_log( sprintf(
				'[VH360 Push Bridge] Exception sending push: %s',
				$e->getMessage()
			) );
		}
	}
	
	/**
	 * Format push message from notification payload
	 *
	 * @param array $payload Notification payload
	 * @return array|null Push message array or null on failure
	 */
	private function format_push_message( $payload ) {
		// Get actor information
		$actor = get_userdata( $payload['actor_id'] );
		if ( ! $actor ) {
			return null;
		}
		
		$actor_name = $actor->display_name;
		
		// Build title and body based on notification type
		$title = '';
		$body = '';
		
		switch ( $payload['type'] ) {
			case 'follow':
				$title = __( 'New Follower', 'vh360-pwa-app' );
				$body = sprintf(
					/* translators: %s: actor name */
					__( '%s started following you', 'vh360-pwa-app' ),
					$actor_name
				);
				break;
				
			case 'mention':
				$title = __( 'Mention', 'vh360-pwa-app' );
				$body = sprintf(
					/* translators: %s: actor name */
					__( '%s mentioned you', 'vh360-pwa-app' ),
					$actor_name
				);
				break;
				
			case 'reply':
				$title = __( 'New Reply', 'vh360-pwa-app' );
				$body = sprintf(
					/* translators: %s: actor name */
					__( '%s replied to your comment', 'vh360-pwa-app' ),
					$actor_name
				);
				break;
				
			case 'message':
				$title = __( 'New Message', 'vh360-pwa-app' );
				// For messages, use the content preview (already sanitized by DM system)
				if ( ! empty( $payload['content'] ) ) {
					// Strip any HTML tags that might be in the content
					$body = wp_strip_all_tags( $payload['content'] );
				} else {
					$body = sprintf(
						/* translators: %s: actor name */
						__( '%s sent you a message', 'vh360-pwa-app' ),
						$actor_name
					);
				}
				break;
				
			case 'like':
				$title = __( 'New Like', 'vh360-pwa-app' );
				$body = sprintf(
					/* translators: %s: actor name */
					__( '%s liked your post', 'vh360-pwa-app' ),
					$actor_name
				);
				break;
				
			case 'comment':
				$title = __( 'New Comment', 'vh360-pwa-app' );
				$body = sprintf(
					/* translators: %s: actor name */
					__( '%s commented on your post', 'vh360-pwa-app' ),
					$actor_name
				);
				break;
				
			default:
				// Use content if available, otherwise generic message
				if ( ! empty( $payload['content'] ) ) {
					$title = __( 'New Notification', 'vh360-pwa-app' );
					$body = wp_strip_all_tags( $payload['content'] );
				} else {
					return null; // Can't create meaningful push
				}
				break;
		}
		
		// Ensure body is plain text (strip any remaining HTML)
		$body = wp_strip_all_tags( $body );
		
		// Get actor avatar for icon
		$icon_url = get_avatar_url( $payload['actor_id'], array( 'size' => 192 ) );
		
		return array(
			'title'    => $title,
			'body'     => $body,
			'icon_url' => $icon_url,
		);
	}
	
	/**
	 * Get notification URL based on object type and ID
	 *
	 * @param array $payload Notification payload
	 * @return string|null URL or null if not applicable
	 */
	private function get_notification_url( $payload ) {
		$url = null;
		
		switch ( $payload['type'] ) {
			case 'follow':
				// Link to actor profile
				if ( function_exists( 'vh360_get_profile_url' ) ) {
					$url = vh360_get_profile_url( $payload['actor_id'] );
				} else {
					$url = get_author_posts_url( $payload['actor_id'] );
				}
				break;
				
			case 'message':
				// Link to messages page
				if ( function_exists( 'vh360_get_messages_url' ) ) {
					$url = vh360_get_messages_url();
				} else {
					$url = home_url( '/messages/' );
				}
				break;
				
			case 'like':
			case 'comment':
			case 'mention':
			case 'reply':
				// Link to the post/object
				if ( 'post' === $payload['object_type'] ) {
					$url = get_permalink( $payload['object_id'] );
				} elseif ( 'comment' === $payload['object_type'] || 'reply' === $payload['type'] ) {
					// For comments and replies, link to the post
					$comment = get_comment( $payload['object_id'] );
					if ( $comment ) {
						$url = get_permalink( $comment->comment_post_ID );
					}
				}
				break;
				
			default:
				// Use home URL as fallback
				$url = home_url( '/' );
				break;
		}
		
		return $url;
	}
}
