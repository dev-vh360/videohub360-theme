<?php
/**
 * Shared operations for native and Activity Feed comment trees.
 *
 * @package Videohub360_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permanently delete a comment and its complete descendant branch.
 *
 * The complete tree is collected before deletion begins. IDs are appended in
 * post-order so descendants are deleted before their parents and the selected
 * root is deleted last.
 *
 * @param int $comment_id Root comment ID.
 * @return true|WP_Error True on success, or an error if validation/deletion fails.
 */
function vh360_delete_comment_branch( $comment_id ) {
	$comment_id = absint( $comment_id );
	if ( ! $comment_id || ! get_comment( $comment_id ) ) {
		return new WP_Error(
			'vh360_invalid_comment_branch',
			__( 'The comment thread could not be deleted.', 'videohub360-theme' )
		);
	}

	$visited       = array();
	$deletion_order = array();

	/**
	 * Collect a branch in deepest-first order without modifying the tree.
	 *
	 * @param int $current_id Current comment ID.
	 */
	$collect_branch = function ( $current_id ) use ( &$collect_branch, &$visited, &$deletion_order ) {
		$current_id = absint( $current_id );
		if ( ! $current_id || isset( $visited[ $current_id ] ) ) {
			return;
		}

		$visited[ $current_id ] = true;
		$child_ids = get_comments(
			array(
				'parent' => $current_id,
				'status' => 'all',
				'number' => 0,
				'fields' => 'ids',
			)
		);

		foreach ( $child_ids as $child_id ) {
			$collect_branch( (int) $child_id );
		}

		$deletion_order[] = $current_id;
	};

	$collect_branch( $comment_id );

	foreach ( $deletion_order as $branch_comment_id ) {
		if ( ! wp_delete_comment( $branch_comment_id, true ) ) {
			return new WP_Error(
				'vh360_comment_branch_delete_failed',
				__( 'The comment thread could not be completely deleted.', 'videohub360-theme' )
			);
		}
	}

	return true;
}
