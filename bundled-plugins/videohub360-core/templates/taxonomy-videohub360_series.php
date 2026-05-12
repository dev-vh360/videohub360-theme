<?php
/**
 * Course Landing Page — videohub360_series taxonomy template.
 *
 * Loaded by the plugin's template_include filter when:
 *   - The current page is a videohub360_series taxonomy archive.
 *   - Course / Lesson Features are enabled (videohub360_course_features_enabled()).
 *
 * When Course / Lesson Features are disabled the filter returns early so this
 * file is never loaded; WordPress uses its normal taxonomy/archive template.
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Retrieve the queried term.
$term = get_queried_object();
if ( ! ( $term instanceof WP_Term ) ) {
    get_header();
    get_footer();
    return;
}

$term_id = $term->term_id;

// ---- Course meta -------------------------------------------------------

$subtitle       = get_term_meta( $term_id, '_vh360_course_subtitle',           true );
$short_desc     = get_term_meta( $term_id, '_vh360_course_short_description',  true );
$level          = get_term_meta( $term_id, '_vh360_course_level',              true );
$duration       = get_term_meta( $term_id, '_vh360_course_duration',           true );
$featured_image = (int) get_term_meta( $term_id, '_vh360_course_featured_image_id', true );
$cta_text       = get_term_meta( $term_id, '_vh360_course_cta_text',           true );
$cta_url        = get_term_meta( $term_id, '_vh360_course_cta_url',            true );

// ---- Helpers -----------------------------------------------------------

$lessons    = function_exists( 'videohub360_get_course_lessons' )    ? videohub360_get_course_lessons( $term_id )    : array();
$instructor = function_exists( 'videohub360_get_course_instructor' ) ? videohub360_get_course_instructor( $term_id ) : false;

// ---- Safe fallbacks ----------------------------------------------------

// Subtitle: fall back to term description.
if ( empty( $subtitle ) ) {
    $subtitle = $term->description;
}

// CTA text.
if ( empty( $cta_text ) ) {
    $cta_text = __( 'Start Learning', 'videohub360' );
}

// CTA URL: explicit meta → first lesson URL → current term URL.
if ( empty( $cta_url ) ) {
    if ( ! empty( $lessons ) ) {
        $cta_url = get_permalink( $lessons[0]->ID );
    } else {
        $cta_url = get_term_link( $term );
    }
}

// ---- Page title (SEO / <title> tag) ------------------------------------

$page_title = ! empty( $term->name )
    ? $term->name
    : ( function_exists( 'videohub360_get_course_label' ) ? videohub360_get_course_label() : __( 'Course', 'videohub360' ) );

// Allow themes / plugins to filter the title if needed.
$page_title = apply_filters( 'videohub360_course_page_title', $page_title, $term );

// ---- Template ----------------------------------------------------------

get_header();
?>

<main id="vh360-course-main" class="vh360-course-page" role="main">

    <?php
    // Course Hero (title, subtitle, meta, access badge, CTA, image).
    include VIDEOHUB360_PLUGIN_DIR . 'templates/course/course-hero.php';
    ?>

    <div class="vh360-course-body">

        <?php
        // Course Curriculum (lesson list grouped by module).
        include VIDEOHUB360_PLUGIN_DIR . 'templates/course/course-curriculum.php';

        // Instructor section (hidden when no instructor is found).
        include VIDEOHUB360_PLUGIN_DIR . 'templates/course/course-instructor.php';

        // Related courses.
        include VIDEOHUB360_PLUGIN_DIR . 'templates/course/course-related.php';
        ?>

        <?php
        // CTA section.
        if ( ! empty( $cta_url ) ) :
            $cta_label = function_exists( 'videohub360_get_course_label' ) ? videohub360_get_course_label() : __( 'Course', 'videohub360' );
        ?>
        <section class="vh360-course-cta-section">
            <div class="vh360-course-cta-inner">
                <p class="vh360-course-cta-prompt">
                    <?php
                    /* translators: %s: singular course label */
                    printf( esc_html__( 'Ready to start this %s?', 'videohub360' ), esc_html( strtolower( $cta_label ) ) );
                    ?>
                </p>
                <a href="<?php echo esc_url( $cta_url ); ?>" class="vh360-course-cta-btn">
                    <?php echo esc_html( $cta_text ); ?>
                </a>
            </div>
        </section>
        <?php endif; ?>

    </div><!-- .vh360-course-body -->

</main><!-- #vh360-course-main -->

<?php get_footer();
