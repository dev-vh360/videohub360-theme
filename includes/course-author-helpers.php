<?php
/**
 * Course Author Helper Functions
 *
 * Provides instructor detection and course retrieval helpers used by the
 * Course Mode author template (author-course.php) and its template parts.
 *
 * Relies on the existing videohub360_series taxonomy and the course term meta
 * fields registered by VideoHub360_Course_Foundation:
 *   _vh360_course_instructor_user_id
 *   _vh360_course_owner_user_id
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================================
   Feature flag wrapper
   ========================================================================= */

/**
 * Check whether Course / Lesson Features are enabled in the core plugin.
 *
 * Wraps the plugin class method so theme code only calls this thin helper.
 *
 * @return bool
 */
function vh360_course_features_enabled() {
    if ( class_exists( 'VideoHub360_Course_Foundation' ) ) {
        return VideoHub360_Course_Foundation::is_enabled();
    }
    // Fallback: read the option directly
    return (bool) get_option( 'videohub360_enable_course_features', 0 );
}

/* =========================================================================
   Label helpers (thin wrappers with safe fallbacks)
   ========================================================================= */

/**
 * Get the localised label for "Course" or "Courses".
 *
 * Defers to the plugin helper when available.
 *
 * @param bool $plural Whether to return the plural form.
 * @return string
 */
function vh360_get_course_label( $plural = false ) {
    if ( function_exists( 'videohub360_get_course_label' ) ) {
        return videohub360_get_course_label( $plural );
    }
    return $plural ? __( 'Courses', 'videohub360-theme' ) : __( 'Course', 'videohub360-theme' );
}

/**
 * Get the localised label for "Lesson" or "Lessons".
 *
 * @param bool $plural Whether to return the plural form.
 * @return string
 */
function vh360_get_lesson_label( $plural = false ) {
    if ( function_exists( 'videohub360_get_lesson_label' ) ) {
        return videohub360_get_lesson_label( $plural );
    }
    return $plural ? __( 'Lessons', 'videohub360-theme' ) : __( 'Lesson', 'videohub360-theme' );
}

/**
 * Get the localised label for "Instructor".
 *
 * @return string
 */
function vh360_get_instructor_label() {
    if ( function_exists( 'videohub360_get_instructor_label' ) ) {
        return videohub360_get_instructor_label();
    }
    return __( 'Instructor', 'videohub360-theme' );
}

/* =========================================================================
   Instructor detection
   ========================================================================= */

/**
 * Determine whether a user is a course instructor/owner.
 *
 * Returns true when Course / Lesson Features are enabled and at least one
 * of the following is true:
 *  1. The user is set as _vh360_course_instructor_user_id on a series term.
 *  2. The user is set as _vh360_course_owner_user_id on a series term.
 *  3. The user has published videohub360 lessons assigned to a series term
 *     (fallback when no direct owner/instructor meta exists).
 *
 * Results are cached per user for the duration of the request.
 *
 * @param int $user_id WordPress user ID.
 * @return bool
 */
function vh360_user_is_course_instructor( $user_id ) {
    $user_id = absint( $user_id );
    if ( ! $user_id ) {
        return false;
    }

    // Must have course features enabled.
    if ( ! vh360_course_features_enabled() ) {
        return false;
    }

    // Runtime cache (per request).
    static $cache = array();
    if ( isset( $cache[ $user_id ] ) ) {
        return $cache[ $user_id ];
    }

    $cache_key = 'vh360_instructor_' . $user_id;
    $cached    = wp_cache_get( $cache_key, 'vh360_course_author' );
    if ( false !== $cached ) {
        $cache[ $user_id ] = (bool) $cached;
        return $cache[ $user_id ];
    }

    $is_instructor = false;

    // Check 1: instructor meta on any series term.
    if ( ! $is_instructor ) {
        $terms_as_instructor = get_terms( array(
            'taxonomy'   => 'videohub360_series',
            'hide_empty' => false,
            'number'     => 1,
            'fields'     => 'ids',
            'meta_query' => array(
                array(
                    'key'   => '_vh360_course_instructor_user_id',
                    'value' => $user_id,
                    'type'  => 'NUMERIC',
                ),
            ),
        ) );
        if ( ! empty( $terms_as_instructor ) && ! is_wp_error( $terms_as_instructor ) ) {
            $is_instructor = true;
        }
    }

    // Check 2: owner meta on any series term.
    if ( ! $is_instructor ) {
        $terms_as_owner = get_terms( array(
            'taxonomy'   => 'videohub360_series',
            'hide_empty' => false,
            'number'     => 1,
            'fields'     => 'ids',
            'meta_query' => array(
                array(
                    'key'   => '_vh360_course_owner_user_id',
                    'value' => $user_id,
                    'type'  => 'NUMERIC',
                ),
            ),
        ) );
        if ( ! empty( $terms_as_owner ) && ! is_wp_error( $terms_as_owner ) ) {
            $is_instructor = true;
        }
    }

    // Check 3 (fallback): authored lessons assigned to a series.
    if ( ! $is_instructor ) {
        $lesson_ids = get_posts( array(
            'post_type'      => 'videohub360',
            'author'         => $user_id,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'videohub360_series',
                    'operator' => 'EXISTS',
                ),
            ),
        ) );
        if ( ! empty( $lesson_ids ) ) {
            $is_instructor = true;
        }
    }

    wp_cache_set( $cache_key, (int) $is_instructor, 'vh360_course_author', 5 * MINUTE_IN_SECONDS );
    $cache[ $user_id ] = $is_instructor;

    return $is_instructor;
}

/**
 * Invalidate the instructor cache for a user.
 *
 * Hook into lesson saves and course term meta updates so the cached result
 * stays accurate.
 *
 * @param int $user_id
 */
function vh360_invalidate_instructor_cache( $user_id ) {
    $user_id = absint( $user_id );
    if ( $user_id ) {
        wp_cache_delete( 'vh360_instructor_' . $user_id, 'vh360_course_author' );
    }
}

/**
 * Clear instructor cache when a videohub360 lesson is saved.
 *
 * @param int $post_id Post ID.
 */
function vh360_maybe_invalidate_instructor_cache_on_save( $post_id ) {
    if ( get_post_type( $post_id ) !== 'videohub360' ) {
        return;
    }
    $author_id = (int) get_post_field( 'post_author', $post_id );
    vh360_invalidate_instructor_cache( $author_id );
}
add_action( 'save_post', 'vh360_maybe_invalidate_instructor_cache_on_save', 20 );

/**
 * Invalidate instructor cache when a course/series term is edited.
 *
 * The edited_{$taxonomy} hook passes ( $term_id, $tt_id ), not
 * ( $term_id, $taxonomy ), so no taxonomy-name check is needed here.
 *
 * @param int $term_id Term ID.
 * @param int $tt_id   Term taxonomy ID (unused but declared for hook signature).
 */
function vh360_maybe_invalidate_instructor_cache_on_series_edit( $term_id, $tt_id = 0 ) {
    foreach ( array( '_vh360_course_instructor_user_id', '_vh360_course_owner_user_id' ) as $meta_key ) {
        $user_id = (int) get_term_meta( $term_id, $meta_key, true );
        if ( $user_id ) {
            vh360_invalidate_instructor_cache( $user_id );
        }
    }
}
add_action( 'edited_videohub360_series', 'vh360_maybe_invalidate_instructor_cache_on_series_edit', 20, 2 );

/**
 * Invalidate course author caches when course instructor/owner term meta changes.
 *
 * Covers programmatic meta updates (not only the admin term-edit form), so
 * the instructor detection cache stays accurate when meta is set via REST,
 * importer, or plugin code.
 *
 * @param int    $meta_id     Meta ID.
 * @param int    $object_id   Term ID.
 * @param string $meta_key    Meta key.
 * @param mixed  $_meta_value New or deleted meta value.
 */
function vh360_invalidate_course_author_cache_on_term_meta_change( $meta_id, $object_id, $meta_key, $_meta_value ) {
    if ( ! in_array( $meta_key, array( '_vh360_course_instructor_user_id', '_vh360_course_owner_user_id' ), true ) ) {
        return;
    }

    $term = get_term( (int) $object_id, 'videohub360_series' );
    if ( ! $term || is_wp_error( $term ) ) {
        return;
    }

    $new_user_id = (int) $_meta_value;
    if ( $new_user_id ) {
        vh360_invalidate_instructor_cache( $new_user_id );
    }
}
add_action( 'added_term_meta',   'vh360_invalidate_course_author_cache_on_term_meta_change', 20, 4 );
add_action( 'updated_term_meta', 'vh360_invalidate_course_author_cache_on_term_meta_change', 20, 4 );
add_action( 'deleted_term_meta', 'vh360_invalidate_course_author_cache_on_term_meta_change', 20, 4 );

/* =========================================================================
   Course retrieval for author pages
   ========================================================================= */

/**
 * Get courses (videohub360_series terms) where the user is owner or instructor.
 *
 * Returns terms ordered by _vh360_course_order (ASC), then name (ASC).
 * Falls back to inferring courses from authored lessons when no direct
 * owner/instructor meta is found.
 *
 * @param int   $user_id WordPress user ID.
 * @param array $args {
 *     Optional arguments.
 *     @type bool $fallback_to_lesson_author  Include courses inferred from authored lessons when no
 *                                            owner/instructor meta matches. Default true.
 *     @type bool $hide_empty                 Exclude terms with no posts. Default false.
 *     @type int  $number                     Maximum number of terms to return. Default 0 (all).
 * }
 * @return WP_Term[] Array of WP_Term objects, or an empty array on failure.
 */
function vh360_get_user_courses( $user_id, $args = array() ) {
    $user_id = absint( $user_id );
    if ( ! $user_id ) {
        return array();
    }

    $defaults = array(
        'fallback_to_lesson_author' => true,
        'hide_empty'                => false,
        'number'                    => 0,
    );
    $args = wp_parse_args( $args, $defaults );

    $course_term_ids = array();

    // Collect terms where user is the instructor.
    $instructor_terms = get_terms( array(
        'taxonomy'   => 'videohub360_series',
        'hide_empty' => $args['hide_empty'],
        'number'     => 0,
        'fields'     => 'ids',
        'meta_query' => array(
            array(
                'key'   => '_vh360_course_instructor_user_id',
                'value' => $user_id,
                'type'  => 'NUMERIC',
            ),
        ),
    ) );
    if ( ! is_wp_error( $instructor_terms ) ) {
        $course_term_ids = array_merge( $course_term_ids, $instructor_terms );
    }

    // Collect terms where user is the owner.
    $owner_terms = get_terms( array(
        'taxonomy'   => 'videohub360_series',
        'hide_empty' => $args['hide_empty'],
        'number'     => 0,
        'fields'     => 'ids',
        'meta_query' => array(
            array(
                'key'   => '_vh360_course_owner_user_id',
                'value' => $user_id,
                'type'  => 'NUMERIC',
            ),
        ),
    ) );
    if ( ! is_wp_error( $owner_terms ) ) {
        $course_term_ids = array_merge( $course_term_ids, $owner_terms );
    }

    // Fallback: infer from authored lessons.
    if ( empty( $course_term_ids ) && $args['fallback_to_lesson_author'] ) {
        $lesson_ids = get_posts( array(
            'post_type'      => 'videohub360',
            'author'         => $user_id,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );
        if ( ! empty( $lesson_ids ) ) {
            foreach ( $lesson_ids as $lesson_id ) {
                $terms = wp_get_post_terms( $lesson_id, 'videohub360_series', array( 'fields' => 'ids' ) );
                if ( ! is_wp_error( $terms ) ) {
                    $course_term_ids = array_merge( $course_term_ids, $terms );
                }
            }
        }
    }

    $course_term_ids = array_unique( array_map( 'absint', $course_term_ids ) );

    if ( empty( $course_term_ids ) ) {
        return array();
    }

    // Fetch full term objects and sort by _vh360_course_order, then name.
    $query_args = array(
        'taxonomy'   => 'videohub360_series',
        'hide_empty' => $args['hide_empty'],
        'include'    => $course_term_ids,
        'orderby'    => 'meta_value_num name',
        'order'      => 'ASC',
        'meta_key'   => '_vh360_course_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'relation' => 'OR',
            array(
                'key'     => '_vh360_course_order',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => '_vh360_course_order',
                'compare' => 'NOT EXISTS',
            ),
        ),
    );
    if ( $args['number'] > 0 ) {
        $query_args['number'] = absint( $args['number'] );
    }

    $terms = get_terms( $query_args );

    if ( is_wp_error( $terms ) ) {
        $terms = array();
    }

    return $terms;
}

/**
 * Get the number of lessons (videohub360 posts) that belong to a course term.
 *
 * Wraps the plugin function when available, otherwise falls back to a direct query.
 *
 * @param int $term_id videohub360_series term ID.
 * @return int
 */
function vh360_get_course_lesson_count( $term_id ) {
    $term_id = absint( $term_id );
    if ( ! $term_id ) {
        return 0;
    }

    // Use the plugin helper when available.
    if ( function_exists( 'videohub360_get_course_lessons' ) ) {
        $lessons = videohub360_get_course_lessons( $term_id );
        return is_array( $lessons ) ? count( $lessons ) : 0;
    }

    // Direct query fallback.
    $count = wp_cache_get( 'vh360_lesson_count_' . $term_id, 'vh360_course_author' );
    if ( false !== $count ) {
        return (int) $count;
    }

    $posts = get_posts( array(
        'post_type'      => 'videohub360',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'videohub360_series',
                'field'    => 'term_id',
                'terms'    => $term_id,
            ),
        ),
    ) );

    $count = is_array( $posts ) ? count( $posts ) : 0;
    wp_cache_set( 'vh360_lesson_count_' . $term_id, $count, 'vh360_course_author', 5 * MINUTE_IN_SECONDS );

    return $count;
}
