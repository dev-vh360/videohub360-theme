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

$first_lesson_url = ! empty( $lessons ) ? get_permalink( $lessons[0]->ID ) : get_term_link( $term );
$purchase_mode    = function_exists( 'videohub360_get_course_purchase_mode' ) ? videohub360_get_course_purchase_mode( $term_id ) : 'none';
$product_id       = absint( get_term_meta( $term_id, '_vh360_course_product_id', true ) );
$required_plan    = function_exists( 'videohub360_get_course_required_membership' ) ? videohub360_get_course_required_membership( $term_id ) : false;
$user_has_access  = function_exists( 'vh360_user_can_access_course' )
    ? vh360_user_can_access_course( get_current_user_id(), $term_id )
    : ( 'none' === $purchase_mode && empty( $required_plan ) );
$purchase_url     = function_exists( 'vh360_get_course_purchase_url' ) ? vh360_get_course_purchase_url( $term_id ) : '';
$explicit_cta_url = $cta_url;
$course_purchase_unavailable = false;

if ( $user_has_access ) {
    $cta_text = empty( $cta_text ) ? ( 'none' === $purchase_mode ? __( 'Start Learning', 'videohub360' ) : __( 'Continue Learning', 'videohub360' ) ) : $cta_text;
    $cta_url  = $first_lesson_url;
} elseif ( in_array( $purchase_mode, array( 'product', 'both' ), true ) && ! empty( $purchase_url ) ) {
    $cta_text = __( 'Buy Course', 'videohub360' );
    $cta_url  = $purchase_url;
} elseif ( 'product' === $purchase_mode ) {
    $cta_text = __( 'Buy Course', 'videohub360' );
    $cta_url  = '';
    $course_purchase_unavailable = true;
} elseif ( 'membership' === $purchase_mode || ( 'both' === $purchase_mode && ! empty( $required_plan ) ) ) {
    $cta_text = empty( $cta_text ) ? __( 'Join to Access', 'videohub360' ) : $cta_text;
    $cta_url  = empty( $explicit_cta_url ) ? $first_lesson_url : $explicit_cta_url;
} elseif ( 'both' === $purchase_mode ) {
    $cta_text = __( 'Buy Course', 'videohub360' );
    $cta_url  = '';
    $course_purchase_unavailable = true;
} else {
    $cta_text = empty( $cta_text ) ? __( 'Start Learning', 'videohub360' ) : $cta_text;
    $cta_url  = empty( $explicit_cta_url ) ? $first_lesson_url : $explicit_cta_url;
}

$cta_url  = apply_filters( 'videohub360_course_cta_url', $cta_url, $term, $user_has_access );
$cta_text = apply_filters( 'videohub360_course_cta_text', $cta_text, $term, $user_has_access );

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

        <?php if ( ! empty( $course_purchase_unavailable ) ) : ?>
        <section class="vh360-course-cta-section vh360-course-purchase-unavailable">
            <div class="vh360-course-cta-inner">
                <p class="vh360-course-cta-prompt">
                    <?php
                    if ( current_user_can( 'manage_options' ) ) {
                        esc_html_e( 'Course purchase product is not configured correctly. Check the linked WooCommerce product ID, product status, and WooCommerce activation.', 'videohub360' );
                    } else {
                        esc_html_e( 'Course purchase is not available yet.', 'videohub360' );
                    }
                    ?>
                </p>
            </div>
        </section>
        <?php endif; ?>

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

<?php get_footer(); ?>
