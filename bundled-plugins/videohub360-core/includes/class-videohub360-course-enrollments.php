<?php
/**
 * VideoHub360 Course Enrollments
 *
 * Manages the learner enrollment model using wp_vh360_course_enrollments and
 * wp_vh360_lesson_progress tables. Enrollment records are distinct from paid
 * access entitlements – they track learning activity and do NOT grant access.
 *
 * Access control continues to be governed by:
 *   vh360_user_can_access_course()
 *   videohub360_user_can_access_lesson()
 *   vh360_user_has_course_entitlement()
 *   vh360_user_has_active_membership()
 *
 * @package VideoHub360_Core
 * @since   2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class VideoHub360_Course_Enrollments {

    /**
     * Singleton instance.
     *
     * @var VideoHub360_Course_Enrollments
     */
    private static $instance = null;

    /**
     * Enrollment DB version option key.
     */
    const DB_VERSION_OPTION = 'videohub360_course_enrollment_db_version';

    /**
     * Current DB version.
     */
    const DB_VERSION = '1.0';

    /**
     * Valid enrollment statuses.
     */
    const VALID_ENROLLMENT_STATUSES = array( 'active', 'completed', 'archived', 'cancelled', 'access_lost' );

    /**
     * Valid enrollment sources.
     */
    const VALID_SOURCES = array( 'public_start', 'membership_access', 'product_purchase', 'manual', 'admin', 'import' );

    /**
     * Valid lesson progress statuses.
     */
    const VALID_LESSON_STATUSES = array( 'started', 'completed' );

    /**
     * Get singleton instance.
     *
     * @return VideoHub360_Course_Enrollments
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor – register hooks.
     */
    private function __construct() {
        // DB installation / upgrade.
        add_action( 'init', array( $this, 'maybe_install' ), 5 );

        // Auto-enroll on paid course purchase.
        add_action( 'vh360_course_entitlement_granted', array( $this, 'enroll_after_entitlement' ), 10, 4 );

        // Update enrollment status when entitlements are revoked.
        add_action( 'vh360_course_entitlements_revoked_for_order', array( $this, 'handle_entitlements_revoked' ), 10, 2 );
    }

    /* ------------------------------------------------------------------ */
    /*  Database                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Install tables if the stored DB version is outdated.
     */
    public function maybe_install() {
        $installed = get_option( self::DB_VERSION_OPTION, '0' );
        if ( version_compare( $installed, self::DB_VERSION, '<' ) ) {
            $this->install();
        }
    }

    /**
     * Create / upgrade database tables using dbDelta.
     */
    public function install() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // ---- Course enrollments ------------------------------------------
        $enrollments_table = $wpdb->prefix . 'vh360_course_enrollments';
        $sql_enrollments   = "CREATE TABLE {$enrollments_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            course_term_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            source varchar(30) NOT NULL DEFAULT 'manual',
            access_source varchar(30) DEFAULT NULL,
            source_order_id bigint(20) unsigned DEFAULT NULL,
            product_id bigint(20) unsigned DEFAULT NULL,
            first_lesson_id bigint(20) unsigned DEFAULT NULL,
            last_lesson_id bigint(20) unsigned DEFAULT NULL,
            lesson_count int(10) unsigned NOT NULL DEFAULT 0,
            completed_lessons int(10) unsigned NOT NULL DEFAULT 0,
            progress_percent decimal(5,2) NOT NULL DEFAULT 0.00,
            enrolled_at datetime NOT NULL,
            last_activity_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_course (user_id, course_term_id),
            KEY user_id (user_id),
            KEY course_term_id (course_term_id),
            KEY status (status),
            KEY source (source),
            KEY last_activity_at (last_activity_at)
        ) {$charset_collate};";

        dbDelta( $sql_enrollments );

        // ---- Lesson progress ---------------------------------------------
        $progress_table = $wpdb->prefix . 'vh360_lesson_progress';
        $sql_progress   = "CREATE TABLE {$progress_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            course_term_id bigint(20) unsigned NOT NULL,
            lesson_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'started',
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            last_activity_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY user_course_status (user_id, course_term_id, status),
            KEY lesson_id (lesson_id),
            KEY course_term_id (course_term_id)
        ) {$charset_collate};";

        dbDelta( $sql_progress );

        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Return the full name of the course enrollments table.
     *
     * @return string
     */
    public static function get_enrollments_table() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_course_enrollments';
    }

    /**
     * Return the full name of the lesson progress table.
     *
     * @return string
     */
    public static function get_lesson_progress_table() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_lesson_progress';
    }

    /* ------------------------------------------------------------------ */
    /*  Hooks                                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Auto-enroll a user when a paid course entitlement is granted.
     *
     * @param int $user_id       User ID.
     * @param int $course_term_id Course term ID.
     * @param int $product_id    WooCommerce product ID.
     * @param int $order_id      WooCommerce order ID.
     */
    public function enroll_after_entitlement( $user_id, $course_term_id, $product_id, $order_id ) {
        vh360_enroll_user_in_course(
            $user_id,
            $course_term_id,
            array(
                'source'          => 'product_purchase',
                'access_source'   => 'entitlement',
                'product_id'      => $product_id,
                'source_order_id' => $order_id,
            )
        );
    }

    /**
     * Mark enrollments as access_lost when paid entitlements are revoked.
     *
     * @param int $order_id      WooCommerce order ID.
     * @param int $revoked_count Number of entitlements revoked.
     */
    public function handle_entitlements_revoked( $order_id, $revoked_count ) {
        if ( ! $order_id || ! $revoked_count ) {
            return;
        }

        if ( ! class_exists( 'VH360_Membership_Database' ) ) {
            return;
        }

        global $wpdb;

        $entitlements_table = VH360_Membership_Database::get_course_entitlements_table();

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, course_term_id FROM {$entitlements_table} WHERE source_order_id = %d AND status = 'cancelled'",
            $order_id
        ) );

        if ( empty( $rows ) ) {
            return;
        }

        $now              = current_time( 'mysql' );
        $enrollments_table = self::get_enrollments_table();

        foreach ( $rows as $row ) {
            $wpdb->update(
                $enrollments_table,
                array(
                    'status'     => 'access_lost',
                    'updated_at' => $now,
                ),
                array(
                    'user_id'       => absint( $row->user_id ),
                    'course_term_id' => absint( $row->course_term_id ),
                    'source'        => 'product_purchase',
                    'status'        => 'active',
                ),
                array( '%s', '%s' ),
                array( '%d', '%d', '%s', '%s' )
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Progress recalculation                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Recalculate and persist progress metrics for a learner's course enrollment.
     *
     * @param int $user_id       User ID.
     * @param int $course_term_id Course term ID.
     */
    public static function recalculate_progress( $user_id, $course_term_id ) {
        $user_id       = absint( $user_id );
        $course_term_id = absint( $course_term_id );

        if ( ! $user_id || ! $course_term_id ) {
            return;
        }

        global $wpdb;

        $progress_table    = self::get_lesson_progress_table();
        $enrollments_table = self::get_enrollments_table();

        // Count total lessons in the course.
        $lessons = function_exists( 'videohub360_get_course_lessons' )
            ? videohub360_get_course_lessons( $course_term_id )
            : array();

        $lesson_count = count( $lessons );

        $completed_lessons = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$progress_table}
             WHERE user_id = %d AND course_term_id = %d AND status = 'completed'",
            $user_id,
            $course_term_id
        ) );

        $progress_percent = ( $lesson_count > 0 )
            ? round( ( $completed_lessons / $lesson_count ) * 100, 2 )
            : 0.00;

        $completed_at = null;
        if ( $lesson_count > 0 && $completed_lessons >= $lesson_count ) {
            $existing_completed_at = $wpdb->get_var( $wpdb->prepare(
                "SELECT completed_at FROM {$enrollments_table}
                 WHERE user_id = %d AND course_term_id = %d",
                $user_id,
                $course_term_id
            ) );
            $completed_at = $existing_completed_at ?: current_time( 'mysql' );
        }

        $update_data   = array(
            'lesson_count'      => $lesson_count,
            'completed_lessons' => $completed_lessons,
            'progress_percent'  => $progress_percent,
            'updated_at'        => current_time( 'mysql' ),
        );
        $update_format = array( '%d', '%d', '%f', '%s' );

        if ( null !== $completed_at ) {
            $update_data['completed_at'] = $completed_at;
            $update_data['status']       = 'completed';
            $update_format[]             = '%s';
            $update_format[]             = '%s';
        }

        $wpdb->update(
            $enrollments_table,
            $update_data,
            array(
                'user_id'       => $user_id,
                'course_term_id' => $course_term_id,
            ),
            $update_format,
            array( '%d', '%d' )
        );
    }
}

/* ------------------------------------------------------------------ */
/*  Public Helper Functions                                              */
/* ------------------------------------------------------------------ */

if ( ! function_exists( 'vh360_enroll_user_in_course' ) ) {
    /**
     * Enroll a user in a course, or update an existing enrollment.
     *
     * This function does NOT grant course access. Access continues to be
     * controlled by vh360_user_can_access_course() and related helpers.
     *
     * @param int   $user_id       User ID.
     * @param int   $course_term_id Course (series) term ID.
     * @param array $args {
     *     Optional. Override defaults.
     *
     *     @type string $status          Enrollment status. Default 'active'.
     *     @type string $source          Enrollment source. Default 'manual'.
     *     @type string $access_source   What provides access (e.g. 'entitlement'). Default null.
     *     @type int    $source_order_id WooCommerce order ID. Default 0.
     *     @type int    $product_id      WooCommerce product ID. Default 0.
     * }
     * @return int|false Enrollment row ID, or false on failure.
     */
    function vh360_enroll_user_in_course( $user_id, $course_term_id, $args = array() ) {
        $user_id       = absint( $user_id );
        $course_term_id = absint( $course_term_id );

        if ( ! $user_id || ! $course_term_id ) {
            return false;
        }

        // Never auto-enroll guest or non-existent users.
        if ( ! get_userdata( $user_id ) ) {
            return false;
        }

        $defaults = array(
            'status'          => 'active',
            'source'          => 'manual',
            'access_source'   => null,
            'source_order_id' => 0,
            'product_id'      => 0,
        );
        $args = wp_parse_args( $args, $defaults );

        // Sanitize.
        $status          = in_array( $args['status'], VideoHub360_Course_Enrollments::VALID_ENROLLMENT_STATUSES, true )
            ? $args['status'] : 'active';
        $source          = in_array( $args['source'], VideoHub360_Course_Enrollments::VALID_SOURCES, true )
            ? $args['source'] : 'manual';
        $access_source   = ! empty( $args['access_source'] ) ? sanitize_key( $args['access_source'] ) : null;
        $source_order_id = absint( $args['source_order_id'] );
        $product_id      = absint( $args['product_id'] );

        global $wpdb;
        $table = VideoHub360_Course_Enrollments::get_enrollments_table();
        $now   = current_time( 'mysql' );

        // Upsert: if row exists, leave it unchanged (idempotent start).
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, status FROM {$table} WHERE user_id = %d AND course_term_id = %d LIMIT 1",
            $user_id,
            $course_term_id
        ) );

        if ( $existing ) {
            // Only update if the current status is a terminal state and new enrollment is active.
            if ( 'active' === $status && in_array( $existing->status, array( 'access_lost', 'cancelled' ), true ) ) {
                $wpdb->update(
                    $table,
                    array(
                        'status'        => 'active',
                        'source'        => $source,
                        'access_source' => $access_source,
                        'enrolled_at'   => $now,
                        'updated_at'    => $now,
                    ),
                    array( 'id' => (int) $existing->id ),
                    array( '%s', '%s', '%s', '%s', '%s' ),
                    array( '%d' )
                );
            }
            return (int) $existing->id;
        }

        // Determine first lesson.
        $first_lesson_id = null;
        if ( function_exists( 'videohub360_get_course_lessons' ) ) {
            $lessons = videohub360_get_course_lessons( $course_term_id );
            if ( ! empty( $lessons ) ) {
                $first_lesson_id = (int) $lessons[0]->ID;
            }
        }

        // Count total lessons.
        $lesson_count = function_exists( 'videohub360_get_course_lessons' )
            ? count( videohub360_get_course_lessons( $course_term_id ) )
            : 0;

        $inserted = $wpdb->insert(
            $table,
            array(
                'user_id'         => $user_id,
                'course_term_id'  => $course_term_id,
                'status'          => $status,
                'source'          => $source,
                'access_source'   => $access_source,
                'source_order_id' => $source_order_id ?: null,
                'product_id'      => $product_id ?: null,
                'first_lesson_id' => $first_lesson_id,
                'lesson_count'    => $lesson_count,
                'enrolled_at'     => $now,
                'created_at'      => $now,
                'updated_at'      => $now,
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return false;
        }

        $enrollment_id = (int) $wpdb->insert_id;

        /**
         * Fires after a user is enrolled in a course.
         *
         * @param int   $user_id       User ID.
         * @param int   $course_term_id Course term ID.
         * @param int   $enrollment_id  Enrollment row ID.
         * @param array $args           Enrollment arguments.
         */
        do_action( 'vh360_user_enrolled_in_course', $user_id, $course_term_id, $enrollment_id, $args );

        return $enrollment_id;
    }
}

if ( ! function_exists( 'vh360_user_is_enrolled_in_course' ) ) {
    /**
     * Check whether a user has an enrollment record for a course.
     *
     * This does NOT check access rights – use vh360_user_can_access_course() for that.
     *
     * @param int $user_id       User ID.
     * @param int $course_term_id Course (series) term ID.
     * @return bool
     */
    function vh360_user_is_enrolled_in_course( $user_id, $course_term_id ) {
        $user_id       = absint( $user_id );
        $course_term_id = absint( $course_term_id );

        if ( ! $user_id || ! $course_term_id ) {
            return false;
        }

        global $wpdb;
        $table = VideoHub360_Course_Enrollments::get_enrollments_table();

        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND course_term_id = %d",
            $user_id,
            $course_term_id
        ) );

        return apply_filters( 'vh360_user_is_enrolled_in_course', $count > 0, $user_id, $course_term_id );
    }
}

if ( ! function_exists( 'vh360_get_course_enrollment' ) ) {
    /**
     * Get the enrollment record for a user/course pair.
     *
     * @param int $user_id       User ID.
     * @param int $course_term_id Course (series) term ID.
     * @return object|null Row object or null if not enrolled.
     */
    function vh360_get_course_enrollment( $user_id, $course_term_id ) {
        $user_id       = absint( $user_id );
        $course_term_id = absint( $course_term_id );

        if ( ! $user_id || ! $course_term_id ) {
            return null;
        }

        global $wpdb;
        $table = VideoHub360_Course_Enrollments::get_enrollments_table();

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d AND course_term_id = %d LIMIT 1",
            $user_id,
            $course_term_id
        ) );
    }
}

if ( ! function_exists( 'vh360_get_user_enrolled_courses' ) ) {
    /**
     * Get all course enrollments for a user.
     *
     * @param int   $user_id User ID.
     * @param array $args {
     *     Optional filters.
     *
     *     @type string $status         Filter by status. Default '' (all).
     *     @type int    $limit          Max rows. Default 50.
     *     @type int    $offset         Row offset. Default 0.
     *     @type string $orderby        Column to sort by. Default 'last_activity_at'.
     *     @type string $order          ASC or DESC. Default 'DESC'.
     * }
     * @return array Array of enrollment row objects.
     */
    function vh360_get_user_enrolled_courses( $user_id, $args = array() ) {
        $user_id = absint( $user_id );

        if ( ! $user_id ) {
            return array();
        }

        $defaults = array(
            'status'  => '',
            'limit'   => 50,
            'offset'  => 0,
            'orderby' => 'last_activity_at',
            'order'   => 'DESC',
        );
        $args = wp_parse_args( $args, $defaults );

        global $wpdb;
        $table = VideoHub360_Course_Enrollments::get_enrollments_table();

        $allowed_orderby = array( 'last_activity_at', 'enrolled_at', 'progress_percent', 'status', 'id' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_activity_at';
        $order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
        $limit   = max( 1, absint( $args['limit'] ) );
        $offset  = absint( $args['offset'] );

        if ( ! empty( $args['status'] ) && in_array( $args['status'], VideoHub360_Course_Enrollments::VALID_ENROLLMENT_STATUSES, true ) ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d AND status = %s
                 ORDER BY {$orderby} {$order}
                 LIMIT %d OFFSET %d",
                $user_id,
                $args['status'],
                $limit,
                $offset
            ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d
                 ORDER BY {$orderby} {$order}
                 LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            ) );
        }

        return $rows ?: array();
    }
}

if ( ! function_exists( 'vh360_update_course_enrollment_activity' ) ) {
    /**
     * Record lesson activity against an enrollment.
     *
     * Creates the enrollment if it does not yet exist (idempotent).
     * Does NOT update lesson progress status – call vh360_mark_lesson_started()
     * or vh360_mark_lesson_complete() for that.
     *
     * @param int $user_id       User ID.
     * @param int $course_term_id Course (series) term ID.
     * @param int $lesson_id     Lesson post ID (optional).
     */
    function vh360_update_course_enrollment_activity( $user_id, $course_term_id, $lesson_id = 0 ) {
        $user_id       = absint( $user_id );
        $course_term_id = absint( $course_term_id );
        $lesson_id     = absint( $lesson_id );

        if ( ! $user_id || ! $course_term_id ) {
            return;
        }

        // Determine source from course purchase mode.
        $source = 'public_start';
        if ( function_exists( 'videohub360_get_course_purchase_mode' ) ) {
            $mode = videohub360_get_course_purchase_mode( $course_term_id );
            if ( 'product' === $mode ) {
                $source = 'product_purchase';
            } elseif ( 'membership' === $mode ) {
                $source = 'membership_access';
            } elseif ( 'both' === $mode ) {
                // Prefer entitlement source when both are allowed and user has entitlement.
                $has_entitlement = function_exists( 'vh360_user_has_course_entitlement' )
                    ? vh360_user_has_course_entitlement( $user_id, $course_term_id )
                    : false;
                $source = $has_entitlement ? 'product_purchase' : 'membership_access';
            }
        }

        // Ensure enrollment row exists.
        vh360_enroll_user_in_course( $user_id, $course_term_id, array( 'source' => $source ) );

        global $wpdb;
        $table = VideoHub360_Course_Enrollments::get_enrollments_table();
        $now   = current_time( 'mysql' );

        $update_data   = array( 'last_activity_at' => $now, 'updated_at' => $now );
        $update_format = array( '%s', '%s' );

        if ( $lesson_id ) {
            $update_data['last_lesson_id'] = $lesson_id;
            $update_format[]               = '%d';
        }

        $wpdb->update(
            $table,
            $update_data,
            array(
                'user_id'       => $user_id,
                'course_term_id' => $course_term_id,
            ),
            $update_format,
            array( '%d', '%d' )
        );
    }
}

if ( ! function_exists( 'vh360_mark_lesson_started' ) ) {
    /**
     * Record that a user has started a lesson.
     *
     * Idempotent – upgrading from 'started' to 'started' is a no-op,
     * and will not downgrade a 'completed' row.
     *
     * @param int $user_id   User ID.
     * @param int $lesson_id Lesson post ID.
     */
    function vh360_mark_lesson_started( $user_id, $lesson_id ) {
        $user_id   = absint( $user_id );
        $lesson_id = absint( $lesson_id );

        if ( ! $user_id || ! $lesson_id ) {
            return;
        }

        // Resolve course term.
        $course = function_exists( 'videohub360_get_lesson_course' )
            ? videohub360_get_lesson_course( $lesson_id )
            : false;

        $course_term_id = $course ? (int) $course->term_id : 0;

        if ( ! $course_term_id ) {
            return;
        }

        global $wpdb;
        $table = VideoHub360_Course_Enrollments::get_lesson_progress_table();
        $now   = current_time( 'mysql' );

        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, status FROM {$table} WHERE user_id = %d AND lesson_id = %d LIMIT 1",
            $user_id,
            $lesson_id
        ) );

        if ( $existing ) {
            // Do not downgrade completed → started.
            if ( 'completed' !== $existing->status ) {
                $wpdb->update(
                    $table,
                    array( 'last_activity_at' => $now, 'updated_at' => $now ),
                    array( 'id' => (int) $existing->id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
            }
            return;
        }

        $wpdb->insert(
            $table,
            array(
                'user_id'          => $user_id,
                'course_term_id'   => $course_term_id,
                'lesson_id'        => $lesson_id,
                'status'           => 'started',
                'started_at'       => $now,
                'last_activity_at' => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ),
            array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }
}

if ( ! function_exists( 'vh360_mark_lesson_complete' ) ) {
    /**
     * Record that a user has completed a lesson and update course progress.
     *
     * @param int $user_id   User ID.
     * @param int $lesson_id Lesson post ID.
     */
    function vh360_mark_lesson_complete( $user_id, $lesson_id ) {
        $user_id   = absint( $user_id );
        $lesson_id = absint( $lesson_id );

        if ( ! $user_id || ! $lesson_id ) {
            return;
        }

        // Resolve course term.
        $course = function_exists( 'videohub360_get_lesson_course' )
            ? videohub360_get_lesson_course( $lesson_id )
            : false;

        $course_term_id = $course ? (int) $course->term_id : 0;

        if ( ! $course_term_id ) {
            return;
        }

        global $wpdb;
        $table = VideoHub360_Course_Enrollments::get_lesson_progress_table();
        $now   = current_time( 'mysql' );

        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, status FROM {$table} WHERE user_id = %d AND lesson_id = %d LIMIT 1",
            $user_id,
            $lesson_id
        ) );

        if ( $existing ) {
            if ( 'completed' !== $existing->status ) {
                $wpdb->update(
                    $table,
                    array(
                        'status'           => 'completed',
                        'completed_at'     => $now,
                        'last_activity_at' => $now,
                        'updated_at'       => $now,
                    ),
                    array( 'id' => (int) $existing->id ),
                    array( '%s', '%s', '%s', '%s' ),
                    array( '%d' )
                );
            }
        } else {
            $wpdb->insert(
                $table,
                array(
                    'user_id'          => $user_id,
                    'course_term_id'   => $course_term_id,
                    'lesson_id'        => $lesson_id,
                    'status'           => 'completed',
                    'started_at'       => $now,
                    'completed_at'     => $now,
                    'last_activity_at' => $now,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ),
                array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
            );
        }

        // Recalculate overall course progress.
        VideoHub360_Course_Enrollments::recalculate_progress( $user_id, $course_term_id );

        /**
         * Fires after a lesson is marked complete.
         *
         * @param int $user_id       User ID.
         * @param int $lesson_id     Lesson post ID.
         * @param int $course_term_id Course term ID.
         */
        do_action( 'vh360_lesson_completed', $user_id, $lesson_id, $course_term_id );
    }
}

if ( ! function_exists( 'vh360_get_course_progress' ) ) {
    /**
     * Get the current progress percentage for a user in a course.
     *
     * @param int $user_id       User ID.
     * @param int $course_term_id Course (series) term ID.
     * @return float Progress percentage 0–100, or 0 if not enrolled.
     */
    function vh360_get_course_progress( $user_id, $course_term_id ) {
        $enrollment = vh360_get_course_enrollment( $user_id, $course_term_id );
        if ( ! $enrollment ) {
            return 0.0;
        }
        return (float) $enrollment->progress_percent;
    }
}
