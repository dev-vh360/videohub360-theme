<?php
/**
 * Dashboard My Courses Tab
 *
 * Allows frontend users to create, edit, and delete their own
 * videohub360_series terms (Courses / Learning Tracks).
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() ) {
    echo '<p>' . esc_html__( 'You must be logged in to manage courses.', 'videohub360-theme' ) . '</p>';
    return;
}

if ( ! function_exists( 'videohub360_course_features_enabled' ) || ! videohub360_course_features_enabled() ) {
    echo '<p>' . esc_html__( 'Course features are not enabled.', 'videohub360-theme' ) . '</p>';
    return;
}

$current_user_id = get_current_user_id();

$can_manage_courses = function_exists( 'vh360_user_can_create_videos' )
    ? vh360_user_can_create_videos( $current_user_id )
    : ( current_user_can( 'manage_options' ) || current_user_can( 'vh360_create_videos' ) );

if ( ! $can_manage_courses ) {
    echo '<p>' . esc_html__( 'You do not have permission to manage courses.', 'videohub360-theme' ) . '</p>';
    return;
}

$is_admin = current_user_can( 'manage_options' );

// Query courses owned by current user (admins see all).
if ( $is_admin ) {
    $courses = get_terms( array(
        'taxonomy'   => 'videohub360_series',
        'hide_empty' => false,
    ) );
} else {
    $courses = get_terms( array(
        'taxonomy'   => 'videohub360_series',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'     => '_vh360_course_owner_user_id',
                'value'   => $current_user_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
        ),
    ) );

    // Prefer explicit owner meta for scalable queries. Fall back only for legacy/imported courses.
    if ( ( is_wp_error( $courses ) || empty( $courses ) ) && function_exists( 'vh360_user_can_manage_course' ) ) {
        $all_courses = get_terms( array(
            'taxonomy'   => 'videohub360_series',
            'hide_empty' => false,
        ) );

        $courses = is_wp_error( $all_courses ) ? array() : array_filter( $all_courses, function( $course ) use ( $current_user_id ) {
            return vh360_user_can_manage_course( $current_user_id, $course->term_id );
        } );
    }
}

if ( is_wp_error( $courses ) ) {
    $courses = array();
}

if ( ! $is_admin && function_exists( 'vh360_user_can_manage_course' ) ) {
    $courses = array_filter( $courses, function( $course ) use ( $current_user_id ) {
        return vh360_user_can_manage_course( $current_user_id, $course->term_id );
    } );
}

$courses = array_values( $courses );

$course_label          = function_exists( 'videohub360_get_course_label' ) ? videohub360_get_course_label() : __( 'Course', 'videohub360-theme' );
$courses_label         = function_exists( 'videohub360_get_course_label' ) ? videohub360_get_course_label( true ) : __( 'Courses', 'videohub360-theme' );

$level_options = array(
    ''             => __( '— Select —', 'videohub360-theme' ),
    'beginner'     => __( 'Beginner', 'videohub360-theme' ),
    'intermediate' => __( 'Intermediate', 'videohub360-theme' ),
    'advanced'     => __( 'Advanced', 'videohub360-theme' ),
    'all'          => __( 'All Levels', 'videohub360-theme' ),
);
?>

<div class="vh360-dashboard-courses">

    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title">
            <?php
            /* translators: %s = plural course label */
            printf( esc_html__( 'My %s', 'videohub360-theme' ), esc_html( $courses_label ) );
            ?>
        </h1>
        <button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-primary" id="vh360-course-create-toggle">
            <?php
            /* translators: %s = singular course label */
            printf( esc_html__( '+ New %s', 'videohub360-theme' ), esc_html( $course_label ) );
            ?>
        </button>
    </div>

    <!-- Notification area -->
    <div id="vh360-course-form-message" class="vh360-form-message" style="display:none;"></div>

    <!-- Create / Edit Course Form -->
    <div id="vh360-course-form-wrap" class="vh360-course-form-wrap" style="display:none;">
        <form id="vh360-course-form" class="vh360-course-dashboard-form" enctype="multipart/form-data">
            <?php wp_nonce_field( 'vh360_dashboard_nonce', 'nonce' ); ?>
            <input type="hidden" name="course_id" id="vh360_course_id" value="">
            <input type="hidden" name="vh360_remove_course_image" id="vh360_remove_course_image" value="">

            <h3 class="vh360-form-section-title" id="vh360-course-form-heading">
                <?php
                /* translators: %s = singular course label */
                printf( esc_html__( 'Create %s', 'videohub360-theme' ), esc_html( $course_label ) );
                ?>
            </h3>

            <!-- Course Name -->
            <div class="vh360-form-field">
                <label for="vh360_course_name" class="vh360-form-label">
                    <?php esc_html_e( 'Course Name', 'videohub360-theme' ); ?>
                    <span class="vh360-required">*</span>
                </label>
                <input type="text" id="vh360_course_name" name="vh360_course_name" class="vh360-input" required>
            </div>

            <!-- Course Description -->
            <div class="vh360-form-field">
                <label for="vh360_course_description" class="vh360-form-label">
                    <?php esc_html_e( 'Course Description', 'videohub360-theme' ); ?>
                </label>
                <textarea id="vh360_course_description" name="vh360_course_description" class="vh360-textarea" rows="5"></textarea>
            </div>

            <!-- Course Subtitle -->
            <div class="vh360-form-field">
                <label for="vh360_course_subtitle" class="vh360-form-label">
                    <?php esc_html_e( 'Course Subtitle', 'videohub360-theme' ); ?>
                </label>
                <input type="text" id="vh360_course_subtitle" name="_vh360_course_subtitle" class="vh360-input">
            </div>

            <!-- Short Description -->
            <div class="vh360-form-field">
                <label for="vh360_course_short_description" class="vh360-form-label">
                    <?php esc_html_e( 'Short Description', 'videohub360-theme' ); ?>
                </label>
                <textarea id="vh360_course_short_description" name="_vh360_course_short_description" class="vh360-textarea" rows="3"></textarea>
                <p class="vh360-form-help"><?php esc_html_e( 'Used in course catalog cards and headers.', 'videohub360-theme' ); ?></p>
            </div>

            <!-- Course Level -->
            <div class="vh360-form-field">
                <label for="vh360_course_level" class="vh360-form-label">
                    <?php esc_html_e( 'Course Level', 'videohub360-theme' ); ?>
                </label>
                <select id="vh360_course_level" name="_vh360_course_level" class="vh360-select">
                    <?php foreach ( $level_options as $opt_val => $opt_label ) : ?>
                        <option value="<?php echo esc_attr( $opt_val ); ?>"><?php echo esc_html( $opt_label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Course Duration -->
            <div class="vh360-form-field">
                <label for="vh360_course_duration" class="vh360-form-label">
                    <?php esc_html_e( 'Course Duration', 'videohub360-theme' ); ?>
                </label>
                <input type="text" id="vh360_course_duration" name="_vh360_course_duration" class="vh360-input" placeholder="<?php esc_attr_e( 'e.g. 6 modules / 18 lessons', 'videohub360-theme' ); ?>">
            </div>

            <!-- Course Featured Image -->
            <div class="vh360-form-field">
                <label for="vh360_course_featured_image" class="vh360-form-label">
                    <?php esc_html_e( 'Course Featured Image', 'videohub360-theme' ); ?>
                </label>
                <input type="file" id="vh360_course_featured_image" name="vh360_course_featured_image" accept="image/jpeg,image/png,image/gif,image/webp">
                <div id="vh360-course-image-preview" class="vh360-course-image-preview" style="display:none;">
                    <img src="" alt="" id="vh360-course-preview-img">
                    <button type="button" id="vh360-remove-course-image" class="vh360-dashboard-btn vh360-dashboard-btn-secondary">
                        <?php esc_html_e( 'Remove Image', 'videohub360-theme' ); ?>
                    </button>
                </div>
                <!-- Existing image shown in edit mode -->
                <div id="vh360-course-existing-image" class="vh360-course-image-preview" style="display:none;">
                    <img src="" alt="" id="vh360-course-existing-img" style="max-width:200px;height:auto;border-radius:4px;">
                    <button type="button" id="vh360-remove-existing-course-image" class="vh360-dashboard-btn vh360-dashboard-btn-secondary">
                        <?php esc_html_e( 'Remove Image', 'videohub360-theme' ); ?>
                    </button>
                </div>
            </div>

            <!-- Course Access Type -->
            <div class="vh360-form-field">
                <label for="vh360_course_purchase_mode" class="vh360-form-label">
                    <?php esc_html_e( 'Course Access Type', 'videohub360-theme' ); ?>
                </label>
                <select id="vh360_course_purchase_mode" name="_vh360_course_purchase_mode" class="vh360-input">
                    <option value="none"><?php esc_html_e( 'Public', 'videohub360-theme' ); ?></option>
                    <option value="membership"><?php esc_html_e( 'Membership Required', 'videohub360-theme' ); ?></option>
                    <option value="product"><?php esc_html_e( 'Individual Product Purchase', 'videohub360-theme' ); ?></option>
                    <option value="both"><?php esc_html_e( 'Product Purchase or Membership', 'videohub360-theme' ); ?></option>
                </select>
                <p class="vh360-form-help"><?php esc_html_e( 'Choose whether this course is public, membership-gated, sold as a WooCommerce product, or accessible either way.', 'videohub360-theme' ); ?></p>
            </div>

            <!-- Required Membership -->
            <div class="vh360-form-field">
                <label for="vh360_course_required_membership" class="vh360-form-label">
                    <?php esc_html_e( 'Required Membership', 'videohub360-theme' ); ?>
                </label>
                <input type="text" id="vh360_course_required_membership" name="_vh360_course_required_membership" class="vh360-input" placeholder="<?php esc_attr_e( 'e.g. any, or a membership plan key', 'videohub360-theme' ); ?>">
                <p class="vh360-form-help"><?php esc_html_e( 'Needed only for membership-based access. Use "any" for any active plan.', 'videohub360-theme' ); ?></p>
            </div>

            <!-- Linked Product ID -->
            <div class="vh360-form-field">
                <label for="vh360_course_product_id" class="vh360-form-label">
                    <?php esc_html_e( 'Linked Product ID', 'videohub360-theme' ); ?>
                </label>
                <input type="number" id="vh360_course_product_id" name="_vh360_course_product_id" class="vh360-input" min="0" step="1" placeholder="<?php esc_attr_e( 'WooCommerce product ID', 'videohub360-theme' ); ?>">
                <p class="vh360-form-help"><?php esc_html_e( 'Needed only for individual product purchase access.', 'videohub360-theme' ); ?></p>
            </div>

            <!-- CTA Button Text -->
            <div class="vh360-form-field">
                <label for="vh360_course_cta_text" class="vh360-form-label">
                    <?php esc_html_e( 'CTA Button Text', 'videohub360-theme' ); ?>
                </label>
                <input type="text" id="vh360_course_cta_text" name="_vh360_course_cta_text" class="vh360-input" placeholder="<?php esc_attr_e( 'e.g. Start Learning', 'videohub360-theme' ); ?>">
            </div>

            <!-- CTA URL -->
            <div class="vh360-form-field">
                <label for="vh360_course_cta_url" class="vh360-form-label">
                    <?php esc_html_e( 'CTA URL', 'videohub360-theme' ); ?>
                </label>
                <input type="url" id="vh360_course_cta_url" name="_vh360_course_cta_url" class="vh360-input" placeholder="https://">
                <p class="vh360-form-help"><?php esc_html_e( 'Optional. Leave empty to use the course page URL.', 'videohub360-theme' ); ?></p>
            </div>

            <!-- Course Order -->
            <div class="vh360-form-field">
                <label for="vh360_course_order" class="vh360-form-label">
                    <?php esc_html_e( 'Course Order', 'videohub360-theme' ); ?>
                </label>
                <input type="number" id="vh360_course_order" name="_vh360_course_order" class="vh360-input" min="0" value="0" style="max-width:100px;">
                <p class="vh360-form-help"><?php esc_html_e( 'Manual ordering for course catalog output.', 'videohub360-theme' ); ?></p>
            </div>

            <!-- Form actions -->
            <div class="vh360-form-actions">
                <button type="submit" class="vh360-dashboard-btn vh360-dashboard-btn-primary" id="vh360-save-course-btn">
                    <?php esc_html_e( 'Save Course', 'videohub360-theme' ); ?>
                </button>
                <button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-secondary" id="vh360-course-form-cancel">
                    <?php esc_html_e( 'Cancel', 'videohub360-theme' ); ?>
                </button>
            </div>
        </form>
    </div><!-- .vh360-course-form-wrap -->

    <!-- Courses Grid -->
    <?php if ( ! empty( $courses ) ) : ?>
        <div class="vh360-course-dashboard-grid">
            <?php foreach ( $courses as $course ) :
                $image_id  = (int) get_term_meta( $course->term_id, '_vh360_course_featured_image_id', true );
                $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
                $subtitle  = get_term_meta( $course->term_id, '_vh360_course_subtitle', true );
                $level     = get_term_meta( $course->term_id, '_vh360_course_level', true );
                $duration  = get_term_meta( $course->term_id, '_vh360_course_duration', true );
                $short_desc = get_term_meta( $course->term_id, '_vh360_course_short_description', true );
                $cta_text  = get_term_meta( $course->term_id, '_vh360_course_cta_text', true );
                $cta_url   = get_term_meta( $course->term_id, '_vh360_course_cta_url', true );
                $order     = (int) get_term_meta( $course->term_id, '_vh360_course_order', true );
                $membership = get_term_meta( $course->term_id, '_vh360_course_required_membership', true );
                $purchase_mode = get_term_meta( $course->term_id, '_vh360_course_purchase_mode', true );
                if ( '' === $purchase_mode && function_exists( 'videohub360_get_course_purchase_mode' ) ) {
                    $purchase_mode = videohub360_get_course_purchase_mode( $course->term_id );
                }
                $product_id = (int) get_term_meta( $course->term_id, '_vh360_course_product_id', true );
                $term_url  = get_term_link( $course );

                // Build data attributes for edit prefill (JSON-encoded for JS consumption).
                $course_data = array(
                    'id'           => $course->term_id,
                    'name'         => $course->name,
                    'description'  => $course->description,
                    'subtitle'     => $subtitle,
                    'short_desc'   => $short_desc,
                    'level'        => $level,
                    'duration'     => $duration,
                    'image_id'     => $image_id,
                    'image_url'    => $image_url,
                    'membership'   => $membership,
                    'purchase_mode'=> $purchase_mode,
                    'product_id'   => $product_id,
                    'cta_text'     => $cta_text,
                    'cta_url'      => $cta_url,
                    'order'        => $order,
                );
            ?>
                <div class="vh360-course-dashboard-card" data-course-id="<?php echo esc_attr( $course->term_id ); ?>">
                    <?php if ( $image_url ) : ?>
                        <div class="vh360-course-dashboard-image">
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $course->name ); ?>" loading="lazy">
                        </div>
                    <?php else : ?>
                        <div class="vh360-course-dashboard-image vh360-course-dashboard-image-placeholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"></path>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <div class="vh360-course-dashboard-meta">
                        <h3 class="vh360-course-dashboard-title"><?php echo esc_html( $course->name ); ?></h3>
                        <?php if ( $subtitle ) : ?>
                            <p class="vh360-course-dashboard-subtitle"><?php echo esc_html( $subtitle ); ?></p>
                        <?php endif; ?>
                        <div class="vh360-course-dashboard-info">
                            <?php if ( $level ) : ?>
                                <span class="vh360-course-dashboard-level"><?php echo esc_html( ucfirst( $level ) ); ?></span>
                            <?php endif; ?>
                            <?php if ( $duration ) : ?>
                                <span class="vh360-course-dashboard-duration"><?php echo esc_html( $duration ); ?></span>
                            <?php endif; ?>
                            <span class="vh360-course-dashboard-count">
                                <?php
                                $lesson_count = $course->count;
                                printf(
                                    /* translators: %d = number of lessons */
                                    esc_html( _n( '%d lesson', '%d lessons', $lesson_count, 'videohub360-theme' ) ),
                                    absint( $lesson_count )
                                );
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="vh360-course-dashboard-actions">
                        <?php if ( ! is_wp_error( $term_url ) ) : ?>
                            <a href="<?php echo esc_url( $term_url ); ?>" class="vh360-dashboard-btn vh360-dashboard-btn-secondary" target="_blank">
                                <?php esc_html_e( 'View', 'videohub360-theme' ); ?>
                            </a>
                        <?php endif; ?>
                        <button type="button"
                            class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-course-edit-btn"
                            data-course='<?php echo esc_attr( wp_json_encode( $course_data ) ); ?>'>
                            <?php esc_html_e( 'Edit', 'videohub360-theme' ); ?>
                        </button>
                        <button type="button"
                            class="vh360-dashboard-btn vh360-danger-btn vh360-course-delete-btn"
                            data-course-id="<?php echo esc_attr( $course->term_id ); ?>"
                            data-course-name="<?php echo esc_attr( $course->name ); ?>">
                            <?php esc_html_e( 'Delete', 'videohub360-theme' ); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="vh360-dashboard-empty">
            <div class="vh360-dashboard-empty-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"></path>
                </svg>
            </div>
            <p class="vh360-dashboard-empty-title">
                <?php
                /* translators: %s = plural course label */
                printf( esc_html__( 'No %s yet', 'videohub360-theme' ), esc_html( strtolower( $courses_label ) ) );
                ?>
            </p>
            <p class="vh360-dashboard-empty-text">
                <?php
                /* translators: %s = singular course label */
                printf( esc_html__( 'Create your first %s to get started!', 'videohub360-theme' ), esc_html( strtolower( $course_label ) ) );
                ?>
            </p>
        </div>
    <?php endif; ?>

</div><!-- .vh360-dashboard-courses -->
