<?php
/**
 * Frontend actions for native WordPress comments.
 *
 * @package Videohub360_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether the current user may edit or delete a native comment.
 *
 * Guest comments are never considered owned by a logged-in user.
 *
 * @param WP_Comment $comment Comment to authorize.
 * @return bool
 */
function vh360_current_user_can_manage_native_comment( $comment ) {
	if ( ! is_user_logged_in() || ! ( $comment instanceof WP_Comment ) ) {
		return false;
	}

	$user_id = get_current_user_id();
	$is_registered_author = (int) $comment->user_id > 0 && (int) $comment->user_id === $user_id;

	return $is_registered_author || current_user_can( 'moderate_comments' );
}

/**
 * Validate common native-comment AJAX request data.
 *
 * @param string $operation Operation used in authentication and authorization errors.
 * @return WP_Comment|null A valid comment, or null after sending an error response.
 */
function vh360_validate_native_comment_action( $operation ) {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => sprintf( __( 'You must be logged in to %s comments.', 'videohub360-theme' ), $operation ) ), 401 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'vh360_comment_actions' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'videohub360-theme' ) ), 403 );
	}

	$comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
	if ( ! $comment_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid comment.', 'videohub360-theme' ) ), 400 );
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment ) {
		wp_send_json_error( array( 'message' => __( 'Invalid comment.', 'videohub360-theme' ) ), 404 );
	}

	if ( ! get_post( (int) $comment->comment_post_ID ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid comment context.', 'videohub360-theme' ) ), 404 );
	}

	if ( ! vh360_current_user_can_manage_native_comment( $comment ) ) {
		wp_send_json_error( array( 'message' => sprintf( __( 'You are not allowed to %s this comment.', 'videohub360-theme' ), $operation ) ), 403 );
	}

	return $comment;
}

/** Delete a native comment from the frontend. */
function vh360_ajax_delete_native_comment() {
	$comment = vh360_validate_native_comment_action( 'delete' );

	if ( ! wp_delete_comment( (int) $comment->comment_ID, true ) ) {
		wp_send_json_error( array( 'message' => __( 'Could not delete comment. Please try again.', 'videohub360-theme' ) ), 500 );
	}

	wp_send_json_success( array( 'message' => __( 'Comment deleted.', 'videohub360-theme' ) ) );
}
add_action( 'wp_ajax_vh360_delete_native_comment', 'vh360_ajax_delete_native_comment' );

/** Update a native comment from the frontend. */
function vh360_ajax_update_native_comment() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in to edit comments.', 'videohub360-theme' ) ), 401 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'vh360_comment_actions' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'videohub360-theme' ) ), 403 );
	}

	$comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
	if ( ! $comment_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid comment.', 'videohub360-theme' ) ), 400 );
	}

	$content = isset( $_POST['content'] ) ? wp_kses_post( trim( wp_unslash( $_POST['content'] ) ) ) : '';
	if ( '' === $content ) {
		wp_send_json_error( array( 'message' => __( 'Comment content cannot be empty.', 'videohub360-theme' ) ), 400 );
	}

	$comment = vh360_validate_native_comment_action( 'edit' );
	$updated = wp_update_comment(
		array(
			'comment_ID'      => (int) $comment->comment_ID,
			'comment_content' => $content,
		),
		true
	);

	if ( is_wp_error( $updated ) || ! $updated ) {
		wp_send_json_error( array( 'message' => __( 'Could not update comment. Please try again.', 'videohub360-theme' ) ), 500 );
	}

	$updated_comment = get_comment( (int) $comment->comment_ID );
	if ( ! $updated_comment ) {
		wp_send_json_error( array( 'message' => __( 'Could not load the updated comment.', 'videohub360-theme' ) ), 500 );
	}

	ob_start();
	comment_text( $updated_comment );
	$html = ob_get_clean();

	wp_send_json_success(
		array(
			'message' => __( 'Comment updated.', 'videohub360-theme' ),
			'html'    => $html,
		)
	);
}
add_action( 'wp_ajax_vh360_update_native_comment', 'vh360_ajax_update_native_comment' );
