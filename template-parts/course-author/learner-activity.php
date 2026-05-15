<?php
/**
 * Course Author – Learner Activity Tab
 *
 * Shows recent published videohub360 content by this user (non-instructor
 * activity). Falls back gracefully when no activity exists.
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

$paged = max( 1, (int) get_query_var( 'paged' ) );

$activity_query = new WP_Query( array(
    'post_type'      => 'videohub360',
    'author'         => $author_id,
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        'relation' => 'OR',
        array( 'key' => '_vh360_context', 'compare' => 'NOT EXISTS' ),
        array( 'key' => '_vh360_context', 'value' => 'live_room', 'compare' => '!=' ),
    ),
) );
?>

<div class="vh360-course-author-learner-activity">

    <h2 class="vh360-course-author-section-title"><?php esc_html_e( 'Activity', 'videohub360-theme' ); ?></h2>

    <?php if ( $activity_query->have_posts() ) : ?>
        <div class="vh360-course-author-activity-grid">
            <?php while ( $activity_query->have_posts() ) :
                $activity_query->the_post();
            ?>
                <article class="vh360-course-author-activity-item">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>" class="vh360-course-author-activity-thumb">
                            <?php the_post_thumbnail( 'videohub360-video-thumb' ); ?>
                        </a>
                    <?php endif; ?>
                    <div class="vh360-course-author-activity-info">
                        <a href="<?php the_permalink(); ?>" class="vh360-course-author-activity-title">
                            <?php the_title(); ?>
                        </a>
                        <span class="vh360-course-author-activity-date"><?php echo esc_html( get_the_date() ); ?></span>
                    </div>
                </article>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </div>

        <?php if ( $activity_query->max_num_pages > 1 ) : ?>
            <div class="vh360-course-author-pagination">
                <?php
                echo paginate_links( array(
                    'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                    'format'    => '?paged=%#%',
                    'current'   => $paged,
                    'total'     => $activity_query->max_num_pages,
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
            <div class="vh360-empty-icon">📋</div>
            <h3 class="vh360-empty-title"><?php esc_html_e( 'No activity yet', 'videohub360-theme' ); ?></h3>
            <p class="vh360-empty-description"><?php esc_html_e( 'This member hasn\'t posted any content yet.', 'videohub360-theme' ); ?></p>
        </div>
    <?php endif; ?>

</div>
