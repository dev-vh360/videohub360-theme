<?php
/**
 * Course Author – Following Tab
 *
 * Displays users this instructor is following.
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

$following      = function_exists( 'vh360_get_following_user_ids' ) ? vh360_get_following_user_ids( $author_id ) : array();
$paged          = max( 1, (int) get_query_var( 'paged' ) );
$per_page       = 24;
$total          = count( $following );
$total_pages    = ceil( $total / $per_page );
$offset         = ( $paged - 1 ) * $per_page;
$following_page = array_slice( $following, $offset, $per_page );
?>

<div class="vh360-course-author-following" id="vh360-course-tab-following">

    <div class="vh360-course-author-section-header">
        <h2 class="vh360-course-author-section-title">
            <?php
            /* translators: %s: number of users being followed */
            printf( esc_html__( 'Following %s', 'videohub360-theme' ), esc_html( number_format_i18n( $total ) ) );
            ?>
        </h2>
    </div>

    <?php if ( ! empty( $following_page ) ) : ?>
        <div class="vh360-users-grid">
            <?php foreach ( $following_page as $following_id ) :
                get_template_part( 'template-parts/components/card-user', null, array(
                    'user_id'          => $following_id,
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
            <h3 class="vh360-empty-title"><?php esc_html_e( 'Not following anyone yet', 'videohub360-theme' ); ?></h3>
            <p class="vh360-empty-description"><?php esc_html_e( 'This instructor isn\'t following anyone yet.', 'videohub360-theme' ); ?></p>
        </div>
    <?php endif; ?>

</div>
