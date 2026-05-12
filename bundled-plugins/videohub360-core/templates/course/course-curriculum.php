<?php
/**
 * Course Curriculum template part.
 *
 * Lists all lessons assigned to the course, grouped by module.
 *
 * Variables available in scope (set by taxonomy-videohub360_series.php):
 *   $term_id  int       – course (series) term ID.
 *   $lessons  WP_Post[] – lessons sorted by module_number → lesson_number → date.
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$lesson_label_single = function_exists( 'videohub360_get_lesson_label' ) ? videohub360_get_lesson_label()       : __( 'Lesson', 'videohub360' );
$lesson_label_plural = function_exists( 'videohub360_get_lesson_label' ) ? videohub360_get_lesson_label( true ) : __( 'Lessons', 'videohub360' );
$lesson_count        = is_array( $lessons ) ? count( $lessons ) : 0;
?>
<section class="vh360-course-curriculum">

    <h2 class="vh360-course-curriculum-heading">
        <?php
        echo esc_html(
            sprintf(
                /* translators: %1$s: lesson count, %2$s: lesson label (plural/singular) */
                _n(
                    '%1$s %2$s',
                    '%1$s %2$s',
                    $lesson_count,
                    'videohub360'
                ),
                number_format_i18n( $lesson_count ),
                ( 1 === $lesson_count ? $lesson_label_single : $lesson_label_plural )
            )
        );
        ?>
    </h2>

    <?php if ( empty( $lessons ) ) : ?>
        <p class="vh360-course-empty"><?php esc_html_e( 'No lessons have been added to this course yet.', 'videohub360' ); ?></p>
    <?php else : ?>

        <?php
        // Group lessons by module number.
        $modules = array();
        foreach ( $lessons as $lesson ) {
            $mod_num   = (int) get_post_meta( $lesson->ID, '_vh360_lesson_module_number', true );
            $mod_title = get_post_meta( $lesson->ID, '_vh360_lesson_module_title', true );
            $modules[ $mod_num ][] = array(
                'post'      => $lesson,
                'mod_title' => $mod_title,
            );
        }
        ksort( $modules );

        $global_index = 0; // Running lesson counter across all modules.
        $use_modules  = ! ( count( $modules ) === 1 && array_key_exists( 0, $modules ) );

        foreach ( $modules as $mod_num => $mod_lessons ) :

            // Determine a consistent module title from the first lesson in this group.
            $mod_title = '';
            foreach ( $mod_lessons as $item ) {
                if ( ! empty( $item['mod_title'] ) ) {
                    $mod_title = $item['mod_title'];
                    break;
                }
            }

            $show_module_header = $use_modules && ( $mod_num > 0 || ! empty( $mod_title ) );
        ?>

            <div class="vh360-course-module">

                <?php if ( $show_module_header ) : ?>
                    <h3 class="vh360-course-module-title">
                        <?php if ( $mod_num > 0 && ! empty( $mod_title ) ) : ?>
                            <?php
                            /* translators: 1: module number, 2: module title */
                            printf( esc_html__( 'Module %1$s: %2$s', 'videohub360' ), esc_html( $mod_num ), esc_html( $mod_title ) );
                            ?>
                        <?php elseif ( $mod_num > 0 ) : ?>
                            <?php
                            /* translators: %s: module number */
                            printf( esc_html__( 'Module %s', 'videohub360' ), esc_html( $mod_num ) );
                            ?>
                        <?php else : ?>
                            <?php echo esc_html( $mod_title ); ?>
                        <?php endif; ?>
                    </h3>
                <?php endif; ?>

                <ul class="vh360-course-lesson-list">
                    <?php foreach ( $mod_lessons as $item ) :
                        $lesson       = $item['post'];
                        $global_index++;

                        $les_num   = (int) get_post_meta( $lesson->ID, '_vh360_lesson_number', true );
                        $duration  = get_post_meta( $lesson->ID, '_vh360_lesson_duration',    true );
                        $is_preview = get_post_meta( $lesson->ID, '_vh360_lesson_is_preview', true );

                        // Display number: prefer explicit lesson number, fall back to running index.
                        $display_num = $les_num > 0 ? $les_num : $global_index;
                    ?>
                    <li class="vh360-course-lesson-row" data-lesson-id="<?php echo esc_attr( $lesson->ID ); ?>">
                        <span class="vh360-lesson-number">
                            <?php echo esc_html( sprintf( '%02d', $display_num ) ); ?>
                        </span>
                        <a class="vh360-lesson-title" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
                            <?php echo esc_html( $lesson->post_title ); ?>
                        </a>
                        <span class="vh360-lesson-meta">
                            <?php if ( ! empty( $duration ) ) : ?>
                                <span class="vh360-lesson-duration"><?php echo esc_html( $duration ); ?></span>
                            <?php endif; ?>
                            <?php if ( 'yes' === $is_preview ) : ?>
                                <span class="vh360-lesson-preview-badge"><?php esc_html_e( 'Free Preview', 'videohub360' ); ?></span>
                            <?php else :
                                // Inline badge for restricted lessons (no extra DB call – use effective helper).
                                $post_id = $lesson->ID;
                                $plan    = function_exists( 'videohub360_get_effective_lesson_required_membership' )
                                    ? videohub360_get_effective_lesson_required_membership( $post_id )
                                    : false;
                                if ( $plan ) : ?>
                                    <span class="vh360-lesson-access-badge">
                                        <?php
                                        if ( 'any' === $plan ) {
                                            esc_html_e( 'Member Access', 'videohub360' );
                                        } else {
                                            echo esc_html( ucwords( str_replace( array( '-', '_' ), ' ', $plan ) ) );
                                        }
                                        ?>
                                    </span>
                                <?php endif;
                            endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>

            </div><!-- .vh360-course-module -->

        <?php endforeach; ?>

    <?php endif; ?>

</section><!-- .vh360-course-curriculum -->
