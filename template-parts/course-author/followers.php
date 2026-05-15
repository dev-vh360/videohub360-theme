<?php
/**
 * Course Author – Followers Tab
 *
 * Reuses the same follower grid pattern used across other author templates.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id = get_queried_object_id();
$author    = get_userdata( $author_id );

if ( ! $author ) {
    return;
}

$followers      = function_exists( 'vh360_get_followers' ) ? vh360_get_followers( $author_id ) : array();
$paged          = max( 1, (int) get_query_var( 'paged' ) );
$per_page       = 24;
$total          = count( $followers );
$total_pages    = ceil( $total / $per_page );
$offset         = ( $paged - 1 ) * $per_page;
$followers_page = array_slice( $followers, $offset, $per_page );
?>

<div class="vh360-course-author-followers" id="vh360-course-tab-followers">

    <div class="vh360-course-author-section-header">
        <div class="vh360-course-author-section-heading">
            <span class="vh360-course-author-section-kicker"><?php esc_html_e( 'Community', 'videohub360-theme' ); ?></span>
            <h2 class="vh360-course-author-section-title">
                <?php
                /* translators: %s: number of followers */
                printf( esc_html( _n( '%s Follower', '%s Followers', $total, 'videohub360-theme' ) ), esc_html( number_format_i18n( $total ) ) );
                ?>
            </h2>
            <p class="vh360-course-author-section-description"><?php esc_html_e( 'People following this instructor.', 'videohub360-theme' ); ?></p>
        </div>
    </div>

    <?php if ( ! empty( $followers_page ) ) : ?>
        <div class="vh360-users-grid">
            <?php foreach ( $followers_page as $follower_id ) :
                get_template_part( 'template-parts/components/card-user', null, array(
                    'user_id'          => $follower_id,
                    'show_avatar'      => true,
                    'show_bio'         => true,
                    'show_follow_button' => true,
                    'avatar_size'      => 64,
                ) );
            endforeach; ?>
        </div>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="vh360-course-author-pagination">
                <?php
                echo paginate_links( array(
                    'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                    'format'    => '?paged=%#%',
                    'current'   => $paged,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'videohub360-theme' ),
                    'next_text' => esc_html__( 'Next', 'videohub360-theme' ) . ' &raquo;',
                    'type'      => 'list',
                    'end_size'  => 2,
                    'mid_size'  => 2,
                ) );
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="vh360-course-author-empty-state">
            <div class="vh360-empty-icon">👥</div>
            <h3><?php esc_html_e( 'No followers yet', 'videohub360-theme' ); ?></h3>
            <p><?php esc_html_e( 'This instructor doesn\'t have any followers yet.', 'videohub360-theme' ); ?></p>
        </div>
    <?php endif; ?>

</div>
