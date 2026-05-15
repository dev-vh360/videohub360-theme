<?php
/**
 * Course Author – Learner Header
 *
 * Clean header for non-instructor users in Course Mode: avatar, display
 * name, bio, and follow/message buttons where supported.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id       = get_queried_object_id();
$author          = get_userdata( $author_id );

if ( ! $author ) {
    return;
}

$avatar_url      = vh360_get_user_avatar_url( $author_id, 150 );
$display_name    = $author->display_name;
$description     = get_the_author_meta( 'description', $author_id );
$current_user_id = get_current_user_id();
?>

<div class="vh360-course-author-header vh360-course-author-learner-header">
    <div class="container">
        <div class="vh360-course-author-learner-info">

            <!-- Avatar -->
            <div class="vh360-course-author-avatar">
                <?php if ( $avatar_url ) : ?>
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>">
                <?php else : ?>
                    <img src="<?php echo esc_url( get_avatar_url( $author_id, array( 'size' => 150 ) ) ); ?>" alt="<?php echo esc_attr( $display_name ); ?>">
                <?php endif; ?>
            </div>

            <!-- Details -->
            <div class="vh360-course-author-details">
                <h1 class="vh360-course-author-name"><?php echo esc_html( $display_name ); ?></h1>

                <?php if ( $description ) : ?>
                    <p class="vh360-course-author-bio"><?php echo esc_html( wp_trim_words( $description, 25 ) ); ?></p>
                <?php endif; ?>

                <!-- Actions -->
                <div class="vh360-course-author-actions">
                    <?php if ( $current_user_id && $current_user_id !== $author_id && function_exists( 'vh360_follow_button' ) ) :
                        vh360_follow_button( $author_id, 'vh360-course-author-follow-btn' );
                    endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
