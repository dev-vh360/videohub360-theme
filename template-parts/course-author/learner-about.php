<?php
/**
 * Course Author – Learner About Tab
 *
 * Minimal about section for non-instructor users in Course Mode.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id    = get_queried_object_id();
$author       = get_userdata( $author_id );

if ( ! $author ) {
    return;
}

$description  = get_the_author_meta( 'description', $author_id );
$website      = $author->user_url;
$join_date    = vh360_get_user_join_date( $author_id, 'F j, Y' );
$social_links = function_exists( 'vh360_get_user_social_links' ) ? vh360_get_user_social_links( $author_id ) : array();
?>

<div class="vh360-course-author-learner-about">

    <h2 class="vh360-course-author-section-title"><?php esc_html_e( 'About', 'videohub360-theme' ); ?></h2>

    <?php if ( $description ) : ?>
        <div class="vh360-course-author-bio-full">
            <?php echo wpautop( wp_kses_post( $description ) ); ?>
        </div>
    <?php else : ?>
        <div class="vh360-course-author-bio-full vh360-course-author-bio-empty">
            <p><?php esc_html_e( 'No bio yet.', 'videohub360-theme' ); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ( function_exists( 'vh360_render_public_profile_fields' ) ) : ?>
        <?php vh360_render_public_profile_fields( $author_id ); ?>
    <?php endif; ?>

    <?php if ( $website || ! empty( $social_links ) ) : ?>
        <div class="vh360-course-author-links">
            <h3 class="vh360-course-author-subsection-title"><?php esc_html_e( 'Links', 'videohub360-theme' ); ?></h3>
            <ul class="vh360-course-author-links-list">
                <?php if ( $website ) : ?>
                    <li>
                        <a href="<?php echo esc_url( $website ); ?>" class="vh360-course-author-link" target="_blank" rel="noopener noreferrer">
                            <?php
                            $host = wp_parse_url( $website, PHP_URL_HOST );
                            echo esc_html( $host ? $host : $website );
                            ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php foreach ( $social_links as $platform => $url ) :
                    if ( $url ) : ?>
                        <li>
                            <a href="<?php echo esc_url( $url ); ?>" class="vh360-course-author-link" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html( ucfirst( $platform ) ); ?>
                            </a>
                        </li>
                    <?php endif;
                endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="vh360-course-author-stats-box">
        <div class="vh360-course-author-stat-item">
            <span class="vh360-course-author-stat-label"><?php esc_html_e( 'Joined', 'videohub360-theme' ); ?></span>
            <span class="vh360-course-author-stat-value"><?php echo esc_html( $join_date ); ?></span>
        </div>
    </div>

</div>
