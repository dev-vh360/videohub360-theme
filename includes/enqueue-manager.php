<?php
/**
 * Smart Asset Enqueue Manager
 *
 * Implements conditional loading of CSS and JavaScript assets
 * to improve performance by loading only what's needed.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


/**
 * Check whether the current request is the Activity Feed surface.
 *
 * @return bool
 */
if (!function_exists('vh360_is_activity_feed_surface')) {
    function vh360_is_activity_feed_surface() {
        return is_page_template('template-activity-feed.php');
    }
}

/**
 * Check whether the current request displays community posts or composer/media UI.
 *
 * @return bool
 */
if (!function_exists('vh360_is_community_post_surface')) {
    function vh360_is_community_post_surface() {
        return is_page_template('template-activity-feed.php')
            || is_singular('vh360_post')
            || is_author()
            || is_singular('vh360_profile');
    }
}

/**
 * Check whether the current request can display follow/unfollow buttons.
 *
 * @return bool
 */
if (!function_exists('vh360_is_follow_surface')) {
    function vh360_is_follow_surface() {
        return is_page_template('template-activity-feed.php')
            || is_author()
            || is_singular('vh360_profile')
            || is_page_template('template-members-directory.php');
    }
}

/**
 * Check whether the current request can display public/editable profile fields.
 *
 * @return bool
 */
if (!function_exists('vh360_is_profile_fields_surface')) {
    function vh360_is_profile_fields_surface() {
        return is_author()
            || is_singular('vh360_profile')
            || is_page_template('template-dashboard.php')
            || is_page_template('templates/dashboard.php');
    }
}

/**
 * Conditionally enqueue profile page assets
 */
function vh360_enqueue_profile_assets() {
    // Load on profile post type pages or author archive pages
    if (is_singular('vh360_profile') || is_post_type_archive('vh360_profile') || is_author()) {

        // For author pages, determine mode based on the viewed author's account type
        if (is_author()) {
            $author_id = get_queried_object_id();
            $template_mode = function_exists('vh360_get_author_display_mode') ? vh360_get_author_display_mode($author_id) : 'profile';
        } else {
            // For non-author pages, use global setting
            $template_mode = function_exists('vh360_get_author_template_mode') ? vh360_get_author_template_mode() : 'profile';
        }

        // Load assets based on display mode
        if ($template_mode === 'business') {
            // Load business CSS for business mode
            wp_enqueue_style(
                'vh360-business',
                VH360_THEME_URI . '/assets/css/business.css',
                array('videohub360-theme-style'),
                vh360_theme_asset_version('assets/css/business.css')
            );

            // Load business booking JS for appointment functionality
            wp_enqueue_script(
                'vh360-business-booking',
                VH360_THEME_URI . '/assets/js/business-booking.js',
                array('jquery'),
                vh360_theme_asset_version('assets/js/business-booking.js'),
                true
            );

            // Get professional's availability settings
            $availability_settings = array();
            if (function_exists('vh360_get_availability_settings')) {
                $availability_settings = vh360_get_availability_settings($author_id);
            }

            // Localize script with necessary data
            wp_localize_script(
                'vh360-business-booking',
                'vh360BusinessBooking',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('vh360_dashboard_nonce'),
                    'professionalId' => $author_id,
                    'slotDuration' => isset($availability_settings['slot_minutes']) ? $availability_settings['slot_minutes'] : 30,
                    'isLoggedIn' => is_user_logged_in(),
                    'loginUrl' => vh360_get_login_page_url_with_redirect(get_permalink()),
                    'i18n' => array(
                        'noSlots' => __('No appointment slots available for this date.', 'videohub360-theme'),
                        'loadError' => __('Error loading available times. Please try again.', 'videohub360-theme'),
                        'invalidSlot' => __('Invalid slot selected.', 'videohub360-theme'),
                        'bookButton' => __('Book', 'videohub360-theme'),
                        'booking' => __('Booking...', 'videohub360-theme'),
                        'booked' => __('Booked', 'videohub360-theme'),
                        'bookingSuccess' => __('Appointment booked successfully!', 'videohub360-theme'),
                        'bookingError' => __('Error booking appointment. Please try again.', 'videohub360-theme'),
                        'loginToBook' => __('Login to Book', 'videohub360-theme'),
                    ),
                )
            );
        } elseif ($template_mode === 'client') {
            // Load client CSS for client mode
            wp_enqueue_style(
                'vh360-client',
                VH360_THEME_URI . '/assets/css/client.css',
                array('videohub360-theme-style'),
                vh360_theme_asset_version('assets/css/client.css')
            );
        } elseif ($template_mode === 'course') {
            // Load course author CSS for course mode
            wp_enqueue_style(
                'vh360-course-author',
                VH360_THEME_URI . '/assets/css/course-author.css',
                array('videohub360-theme-style'),
                vh360_theme_asset_version('assets/css/course-author.css')
            );
        } elseif ($template_mode === 'channel') {
            // Load channel CSS for channel mode
            wp_enqueue_style(
                'vh360-channel',
                VH360_THEME_URI . '/assets/css/channel.css',
                array('videohub360-theme-style'),
                vh360_theme_asset_version('assets/css/channel.css')
            );
        } else {
            // Load profile CSS for profile mode (default)
            wp_enqueue_style(
                'vh360-profiles',
                VH360_THEME_URI . '/assets/css/profiles.css',
                array('videohub360-theme-style'),
                vh360_theme_asset_version('assets/css/profiles.css')
            );

            // Also load activity feed styles for community posts on profile
            wp_enqueue_style(
                'vh360-activity-feed',
                VH360_THEME_URI . '/assets/css/activity-feed.css',
                array('videohub360-theme-style'),
                vh360_theme_asset_version('assets/css/activity-feed.css')
            );

            // Load events styles if viewing events tab
            $current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'posts';
            if ($current_tab === 'events') {
                wp_enqueue_style(
                    'vh360-events',
                    VH360_THEME_URI . '/assets/css/events.css',
                    array('videohub360-theme-style'),
                    vh360_theme_asset_version('assets/css/events.css')
                );
            }

            // Load profile JavaScript for video sorting and other interactions
            wp_enqueue_script(
                'vh360-profile-js',
                VH360_THEME_URI . '/assets/js/profile.js',
                array(),
                vh360_theme_asset_version('assets/js/profile.js'),
                true
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_profile_assets', 20);

/**
 * Conditionally enqueue dashboard assets
 */
function vh360_enqueue_dashboard_assets() {
    // Load on dashboard template pages
    if (is_page_template('template-dashboard.php') || is_page_template('templates/dashboard.php')) {
        wp_enqueue_style(
            'vh360-dashboard',
            VH360_THEME_URI . '/assets/css/dashboard.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/dashboard.css')
        );

        wp_enqueue_script(
            'vh360-dashboard-script',
            VH360_THEME_URI . '/assets/js/dashboard.js',
            array('jquery'),
            vh360_theme_asset_version('assets/js/dashboard.js'),
            true
        );

        $vh360_create_context_is_lesson = function_exists('vh360_dashboard_uses_lesson_labels') && vh360_dashboard_uses_lesson_labels(get_current_user_id());

        // Localize script with AJAX data
        wp_localize_script('vh360-dashboard-script', 'vh360Dashboard', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_dashboard_nonce'),
            'bulletin_nonce' => wp_create_nonce('vh360_bulletin_nonce'),
            'videoUploadNonce' => wp_create_nonce('vh360_video_upload'),
            'playlistNonce' => wp_create_nonce('vh360_playlist'),
            'strings' => array(
                'loading' => esc_html__('Loading...', 'videohub360-theme'),
                'error' => esc_html__('An error occurred. Please try again.', 'videohub360-theme'),
                'success' => esc_html__('Success!', 'videohub360-theme'),
            ),
            'createForm' => array(
                'isLessonContext' => $vh360_create_context_is_lesson,
                'titleRequired' => $vh360_create_context_is_lesson ? esc_html__('Please provide a lesson title.', 'videohub360-theme') : esc_html__('Please provide a video title.', 'videohub360-theme'),
                'viewItem' => $vh360_create_context_is_lesson ? esc_html__('View Lesson', 'videohub360-theme') : esc_html__('View Video', 'videohub360-theme'),
                'publishing' => $vh360_create_context_is_lesson ? esc_html__('Publishing Lesson...', 'videohub360-theme') : esc_html__('Publishing Video...', 'videohub360-theme'),
                'updating' => $vh360_create_context_is_lesson ? esc_html__('Updating Lesson...', 'videohub360-theme') : esc_html__('Updating Video...', 'videohub360-theme'),
                'uploadSuccess' => $vh360_create_context_is_lesson ? esc_html__('Lesson video uploaded successfully!', 'videohub360-theme') : esc_html__('Video uploaded successfully!', 'videohub360-theme'),
            ),
            'tabUrls' => array(
                'overview'           => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('overview') : add_query_arg('tab', 'overview'),
                'videos'             => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('videos') : add_query_arg('tab', 'videos'),
                'create-video'       => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('create-video') : add_query_arg('tab', 'create-video'),
                'live-rooms'         => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('live-rooms') : add_query_arg('tab', 'live-rooms'),
                'create-post'        => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('create-post') : add_query_arg('tab', 'create-post'),
                'galleries'          => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('galleries') : add_query_arg('tab', 'galleries'),
                'events'             => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('events') : add_query_arg('tab', 'events'),
                'bulletins'          => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('bulletins') : add_query_arg('tab', 'bulletins'),
                'messages'           => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('messages') : add_query_arg('tab', 'messages'),
                'notifications'      => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('notifications') : add_query_arg('tab', 'notifications'),
                'push-notifications' => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('push-notifications') : add_query_arg('tab', 'push-notifications'),
                'settings'           => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('settings') : add_query_arg('tab', 'settings'),
                'giving'             => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('giving') : add_query_arg('tab', 'giving'),
                'membership'         => function_exists('vh360_get_dashboard_tab_url') ? vh360_get_dashboard_tab_url('membership') : add_query_arg('tab', 'membership'),
            ),
            'contentLabels' => array(
                'isLessonContext' => $vh360_create_context_is_lesson,
                'deleteConfirm' => $vh360_create_context_is_lesson
                    ? esc_html__( 'Are you sure you want to delete this lesson? This action cannot be undone.', 'videohub360-theme' )
                    : esc_html__( 'Are you sure you want to delete this video? This action cannot be undone.', 'videohub360-theme' ),
                'deletedSuccess' => $vh360_create_context_is_lesson
                    ? esc_html__( 'Lesson deleted successfully.', 'videohub360-theme' )
                    : esc_html__( 'Video deleted successfully.', 'videohub360-theme' ),
                'deleteFailed' => $vh360_create_context_is_lesson
                    ? esc_html__( 'Failed to delete lesson.', 'videohub360-theme' )
                    : esc_html__( 'Failed to delete video.', 'videohub360-theme' ),
                'emptyTitle' => $vh360_create_context_is_lesson
                    ? esc_html__( 'No lessons yet', 'videohub360-theme' )
                    : esc_html__( 'No videos yet', 'videohub360-theme' ),
                'emptyText' => $vh360_create_context_is_lesson
                    ? esc_html__( 'Create your first lesson to get started!', 'videohub360-theme' )
                    : esc_html__( 'Upload your first video to get started!', 'videohub360-theme' ),
                'loadFailed' => $vh360_create_context_is_lesson
                    ? esc_html__( 'Failed to load lessons.', 'videohub360-theme' )
                    : esc_html__( 'Failed to load videos.', 'videohub360-theme' ),
                'loadError' => $vh360_create_context_is_lesson
                    ? esc_html__( 'An error occurred while loading lessons.', 'videohub360-theme' )
                    : esc_html__( 'An error occurred while loading videos.', 'videohub360-theme' ),
            ),
        ));

        // Add availability-specific localization
        wp_localize_script('vh360-dashboard-script', 'vh360Ajax', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_dashboard_nonce'),
        ));

        if (vh360_is_dashboard_tab('live-rooms')) {
            wp_enqueue_style(
                'vh360-live-rooms',
                VH360_THEME_URI . '/assets/css/live-rooms.css',
                array('vh360-dashboard'),
                vh360_theme_asset_version('assets/css/live-rooms.css')
            );

            wp_enqueue_script(
                'vh360-live-rooms-script',
                VH360_THEME_URI . '/assets/js/live-rooms.js',
                array('jquery', 'vh360-dashboard-script'),
                vh360_theme_asset_version('assets/js/live-rooms.js'),
                true
            );
        }

        if (vh360_is_dashboard_tab('create-post')) {
            wp_enqueue_script(
                'vh360-create-post-script',
                VH360_THEME_URI . '/assets/js/create-post.js',
                array('jquery', 'vh360-dashboard-script'),
                vh360_theme_asset_version('assets/js/create-post.js'),
                true
            );

            wp_localize_script('vh360-create-post-script', 'vh360CreatePost', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('vh360_edit_post_nonce')
            ));
        }

        if (vh360_is_dashboard_tab('events')) {
            wp_enqueue_style(
                'vh360-events-dashboard',
                VH360_THEME_URI . '/assets/css/events-dashboard.css',
                array('vh360-dashboard'),
                vh360_theme_asset_version('assets/css/events-dashboard.css')
            );

            wp_enqueue_script(
                'vh360-events-dashboard-script',
                VH360_THEME_URI . '/assets/js/events-dashboard.js',
                array('jquery', 'vh360-dashboard-script'),
                vh360_theme_asset_version('assets/js/events-dashboard.js'),
                true
            );

            wp_localize_script('vh360-events-dashboard-script', 'vh360EventsDashboard', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('vh360_event_nonce'),
                'i18n'    => array(
                    'createEvent'            => __('Create Event', 'videohub360-theme'),
                    'editEvent'              => __('Edit Event', 'videohub360-theme'),
                    'updateEvent'            => __('Update Event', 'videohub360-theme'),
                    'confirmDelete'          => __('Are you sure you want to delete this event? This action cannot be undone.', 'videohub360-theme'),
                    'error'                  => __('An error occurred. Please try again.', 'videohub360-theme'),
                    'invalidFileType'        => __('Invalid file type. Please upload JPG, PNG, GIF, or WebP.', 'videohub360-theme'),
                    'fileTooLarge'           => __('File size too large. Maximum 5MB allowed.', 'videohub360-theme'),
                    'galleryMaxImages'       => __('You can add up to 5 gallery images.', 'videohub360-theme'),
                    'galleryUploadFailed'    => __('One or more gallery images could not be uploaded.', 'videohub360-theme'),
                    'galleryRemoveImage'     => __('Remove image', 'videohub360-theme'),
                    'galleryImagesSelected'  => __('%d / 5 images selected', 'videohub360-theme'),
                    'galleryAddImages'       => __('Add Gallery Images', 'videohub360-theme'),
                ),
            ));
        }

        if (vh360_is_dashboard_tab('bulletins')) {
            wp_enqueue_style(
                'vh360-bulletin-dashboard',
                VH360_THEME_URI . '/assets/css/bulletin-dashboard.css',
                array('vh360-dashboard'),
                vh360_theme_asset_version('assets/css/bulletin-dashboard.css')
            );

            wp_enqueue_script(
                'vh360-bulletin-dashboard-script',
                VH360_THEME_URI . '/assets/js/bulletin-dashboard.js',
                array('jquery', 'vh360-dashboard-script'),
                vh360_theme_asset_version('assets/js/bulletin-dashboard.js'),
                true
            );

            wp_localize_script('vh360-bulletin-dashboard-script', 'vh360BulletinDashboard', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('vh360_bulletin_nonce'),
                'i18n'    => array(
                    'createBulletin'   => __('Create Bulletin', 'videohub360-theme'),
                    'editBulletin'     => __('Edit Bulletin', 'videohub360-theme'),
                    'updateBulletin'   => __('Update Bulletin', 'videohub360-theme'),
                    'confirmDelete'    => __('Are you sure you want to delete this bulletin?', 'videohub360-theme'),
                    'fileTooLarge'     => __('File size too large. Maximum 5MB allowed.', 'videohub360-theme'),
                    'invalidFileType'  => __('Invalid file type. Please use JPEG, PNG, GIF, or WebP.', 'videohub360-theme'),
                    'uploadError'      => __('Error uploading image. Please try again.', 'videohub360-theme'),
                    'saveError'        => __('Error saving bulletin. Please try again.', 'videohub360-theme'),
                    'deleteError'      => __('Error deleting bulletin. Please try again.', 'videohub360-theme'),
                    'deleteSuccess'    => __('Bulletin deleted successfully', 'videohub360-theme'),
                    'loadError'        => __('Error loading bulletin. Please try again.', 'videohub360-theme'),
                    'success'          => __('Success!', 'videohub360-theme'),
                    'loading'          => __('Loading...', 'videohub360-theme'),
                    'titleRequired'    => __('Title is required', 'videohub360-theme'),
                )
            ));
        }

        if (vh360_is_dashboard_tab('notifications')) {
            wp_enqueue_style(
                'vh360-notifications-dashboard',
                VH360_THEME_URI . '/assets/css/notifications-dashboard.css',
                array('vh360-dashboard'),
                vh360_theme_asset_version('assets/css/notifications-dashboard.css')
            );

            wp_enqueue_script(
                'vh360-notifications-dashboard-script',
                VH360_THEME_URI . '/assets/js/notifications-dashboard.js',
                array('jquery', 'vh360-dashboard-script'),
                vh360_theme_asset_version('assets/js/notifications-dashboard.js'),
                true
            );

            wp_localize_script('vh360-notifications-dashboard-script', 'vh360NotificationsDashboard', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vh360_notifications'),
                'strings' => array(
                    'loading' => esc_html__('Loading...', 'videohub360-theme'),
                    'error' => esc_html__('An error occurred. Please try again.', 'videohub360-theme'),
                    'success' => esc_html__('Success!', 'videohub360-theme'),
                    'markedAllRead' => esc_html__('All notifications marked as read.', 'videohub360-theme'),
                    'deletedRead' => esc_html__('Read notifications deleted.', 'videohub360-theme'),
                    'clearedAll' => esc_html__('All notifications cleared.', 'videohub360-theme'),
                    'deleteNotification' => esc_html__('Delete notification', 'videohub360-theme'),
                    'confirmDeleteRead' => esc_html__('Are you sure you want to delete all read notifications? This cannot be undone.', 'videohub360-theme'),
                    'confirmClearAll' => esc_html__('Are you sure you want to clear all notifications? This cannot be undone.', 'videohub360-theme'),
                ),
            ));

        }

        if (vh360_is_dashboard_tab('settings')) {
            wp_enqueue_script(
                'vh360-notification-preferences-script',
                VH360_THEME_URI . '/assets/js/notification-preferences.js',
                array('jquery', 'vh360-dashboard-script'),
                vh360_theme_asset_version('assets/js/notification-preferences.js'),
                true
            );

            wp_localize_script('vh360-notification-preferences-script', 'vh360NotificationPreferences', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vh360_notifications'),
                'strings' => array(
                    'enableNotifications' => esc_html__('Enable in-app notifications', 'videohub360-theme'),
                    'enableHelp' => esc_html__('Toggle all in-app notifications on or off.', 'videohub360-theme'),
                    'frequency' => esc_html__('Notification Frequency', 'videohub360-theme'),
                    'frequencyAll' => esc_html__('All notifications', 'videohub360-theme'),
                    'frequencyImportant' => esc_html__('Important only (follows, mentions, replies)', 'videohub360-theme'),
                    'frequencyDigest' => esc_html__('Digest only (no immediate notifications)', 'videohub360-theme'),
                    'frequencyHelp' => esc_html__('Choose how often you receive notifications.', 'videohub360-theme'),
                    'notificationTypes' => esc_html__('Notification Types', 'videohub360-theme'),
                    'displaySettings' => esc_html__('Display Settings', 'videohub360-theme'),
                    'soundEnabled' => esc_html__('Play sound for new notifications', 'videohub360-theme'),
                    'desktopEnabled' => esc_html__('Show push notifications (requires browser permission)', 'videohub360-theme'),
                    'maxKeep' => esc_html__('Maximum notifications to keep', 'videohub360-theme'),
                    'maxKeepHelp' => esc_html__('Older notifications will be automatically deleted.', 'videohub360-theme'),
                    'typeFollow' => esc_html__('New followers', 'videohub360-theme'),
                    'typeLike' => esc_html__('Likes on posts', 'videohub360-theme'),
                    'typeComment' => esc_html__('Comments on posts', 'videohub360-theme'),
                    'typeReply' => esc_html__('Replies to comments', 'videohub360-theme'),
                    'typeMention' => esc_html__('Mentions in posts or comments', 'videohub360-theme'),
                    'typeShare' => esc_html__('Shares of content', 'videohub360-theme'),
                    'savePreferences' => esc_html__('Save Preferences', 'videohub360-theme'),
                    'saving' => esc_html__('Saving...', 'videohub360-theme'),
                    'preferencesSaved' => esc_html__('Preferences saved successfully!', 'videohub360-theme'),
                    'error' => esc_html__('An error occurred. Please try again.', 'videohub360-theme'),
                    'loadError' => esc_html__('Could not load preferences. Please refresh the page.', 'videohub360-theme'),
                ),
            ));

        }
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_dashboard_assets', 20);

/**
 * Conditionally enqueue group assets
 */
function vh360_enqueue_group_assets() {
    // Load on group archives and single group pages
    if (is_singular('vh360_group') || is_post_type_archive('vh360_group') || is_tax('vh360_group_category')) {
        wp_enqueue_style(
            'vh360-groups',
            VH360_THEME_URI . '/assets/css/groups.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/groups.css')
        );
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_group_assets', 20);

/**
 * Conditionally enqueue bulletin assets
 */
function vh360_enqueue_bulletin_assets() {
    // Load on bulletin pages, bulletin template pages, or dashboard (for widget)
    $is_bulletin_page = is_singular('vh360_bulletin') || is_post_type_archive('vh360_bulletin');
    $is_bulletin_template = is_page_template('template-bulletins.php');
    $is_dashboard_bulletins_tab = (is_page_template('template-dashboard.php') || is_page_template('templates/dashboard.php'))
        && function_exists('vh360_is_dashboard_tab')
        && vh360_is_dashboard_tab('bulletins');

    if ($is_bulletin_page || $is_bulletin_template || $is_dashboard_bulletins_tab) {
        wp_enqueue_style(
            'vh360-bulletins',
            VH360_THEME_URI . '/assets/css/bulletins.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/bulletins.css')
        );

        wp_enqueue_script(
            'vh360-bulletins-js',
            VH360_THEME_URI . '/assets/js/bulletins.js',
            array('jquery'),
            vh360_theme_asset_version('assets/js/bulletins.js'),
            true
        );

        // Localize script with AJAX data
        wp_localize_script('vh360-bulletins-js', 'vh360Bulletins', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_bulletin_nonce'),
            'strings' => array(
                'marked_read' => esc_html__('Marked as read', 'videohub360-theme'),
                'dismissed' => esc_html__('Dismissed', 'videohub360-theme'),
                'error' => esc_html__('An error occurred. Please try again.', 'videohub360-theme'),
                'no_bulletins' => esc_html__('No bulletins to display', 'videohub360-theme'),
                'copied' => esc_html__('Copied!', 'videohub360-theme'),
            ),
        ));
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_bulletin_assets', 20);

/**
 * Conditionally enqueue gallery assets
 */
function vh360_enqueue_gallery_assets() {
    // Load on gallery pages including taxonomy pages
    if (is_page_template('template-gallery.php') ||
        is_page_template('templates/gallery.php') ||
        is_singular('vh360_gallery') ||
        is_post_type_archive('vh360_gallery') ||
        is_tax('vh360_gallery_category') ||
        is_tax('vh360_gallery_tag')) {

        // Enqueue pre-registered gallery styles
        wp_enqueue_style('vh360-gallery');

        // Enqueue pre-registered gallery script
        wp_enqueue_script('vh360-gallery-script');

        // Localize script with AJAX data
        wp_localize_script('vh360-gallery-script', 'vh360Gallery', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_gallery_nonce'),
        ));

        // Enqueue custom lightbox for gallery viewing
        wp_enqueue_script('vh360-gallery-photoswipe');
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_gallery_assets', 20);

/**
 * Conditionally enqueue members directory assets
 */
function vh360_enqueue_members_directory_assets() {
    // Load on members directory template
    if (is_page_template('template-members-directory.php')) {
        $members_directory_style_version = vh360_theme_asset_version('assets/css/members-directory.css');

        wp_enqueue_style(
            'vh360-members-directory',
            VH360_THEME_URI . '/assets/css/members-directory.css',
            array('videohub360-theme-style'),
            $members_directory_style_version
        );

        wp_enqueue_script(
            'vh360-members-directory-js',
            VH360_THEME_URI . '/assets/js/members-directory.js',
            array('jquery'),
            vh360_theme_asset_version('assets/js/members-directory.js'),
            true
        );

        // Compute effective mode for current page
        $page_id = get_queried_object_id();
        $mode = vh360_get_members_directory_effective_mode($page_id);

        // Get per_page from normalized Members Directory settings
        $members_options = wp_parse_args(
            get_option('vh360_members_options', array()),
            vh360_get_default_members_directory_options()
        );
        $per_page = absint($members_options['per_page']);

        // Localize script with AJAX data
        wp_localize_script('vh360-members-directory-js', 'vh360Members', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_members_nonce'),
            'pageId' => $page_id,
            'perPage' => $per_page,
            'directoryMode' => $mode,
            'strings' => array(
                'loading' => esc_html__('Loading...', 'videohub360-theme'),
                'error' => esc_html__('An error occurred. Please try again.', 'videohub360-theme'),
                'noResults' => esc_html__('No members found. Try adjusting your filters.', 'videohub360-theme'),
            ),
        ));
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_members_directory_assets', 20);

/**
 * Conditionally enqueue activity feed assets
 */
function vh360_enqueue_activity_feed_assets() {
    // Load on activity feed template, author/profile pages, or single community posts (where community posts appear)
    if (vh360_is_community_post_surface()) {
        // Enqueue activity feed CSS on ALL pages where community posts appear (not just activity feed template)
        wp_enqueue_style(
            'vh360-activity-feed',
            VH360_THEME_URI . '/assets/css/activity-feed.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/activity-feed.css')
        );

        // Enqueue community uploads styles for composer/media previews and lightbox UI.
        wp_enqueue_style(
            'vh360-community-uploads',
            VH360_THEME_URI . '/assets/css/community-uploads.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/community-uploads.css')
        );

        // Enqueue community script for posts, likes, comments, shares, and media UI.
        wp_enqueue_script(
            'vh360-community-script',
            VH360_THEME_URI . '/assets/js/community.js',
            array('jquery'),
            vh360_theme_asset_version('assets/js/community.js'),
            true
        );

        // Localize community script with AJAX data and strings.
        wp_localize_script('vh360-community-script', 'vh360Community', array(
            'ajaxurl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('vh360_like_post'),
            'shareNonce'   => wp_create_nonce('vh360_share_post'),
            'commentNonce' => wp_create_nonce('vh360_comment_post'),
            'mentionNonce' => wp_create_nonce('vh360_user_mentions'),
            'postActionsNonce'    => wp_create_nonce('vh360_post_actions'),
            'commentActionsNonce' => wp_create_nonce('vh360_comment_actions'),
            'currentUserAvatar' => get_avatar_url(get_current_user_id(), array('size' => 28)),
            'strings'      => array(
                'error'              => __('An error occurred. Please try again.', 'videohub360-theme'),
                'shared'             => __('Post shared successfully.', 'videohub360-theme'),
                'editPostPrompt'     => __('Edit your post:', 'videohub360-theme'),
                'editCommentPrompt'  => __('Edit your comment:', 'videohub360-theme'),
                'confirmDeletePost'  => __('Are you sure you want to delete this post?', 'videohub360-theme'),
                'confirmDeleteComment' => __('Are you sure you want to delete this comment?', 'videohub360-theme'),
                'photo'              => __('Photo', 'videohub360-theme'),
                'video'              => __('Video', 'videohub360-theme'),
                /* translators: 1: file type (Photo or Video), 2: maximum size in MB */
                'fileTooLarge'       => __('%1$s exceeds maximum size of %2$d MB.', 'videohub360-theme'),
                'close'              => __('Close', 'videohub360-theme'),
            ),
            'i18n' => array(
                'writeReply' => __('Write a reply...', 'videohub360-theme'),
                'sendReply'  => __('Send reply', 'videohub360-theme'),
            ),
        ));

        wp_enqueue_script(
            'vh360-mentions-script',
            VH360_THEME_URI . '/assets/js/vh360-mentions.js',
            array('jquery', 'vh360-community-script'),
            vh360_theme_asset_version('assets/js/vh360-mentions.js'),
            true
        );

        // Always enqueue JavaScript on pages with community posts.
        wp_enqueue_script(
            'vh360-activity-feed-js',
            VH360_THEME_URI . '/assets/js/activity-feed.js',
            array('jquery'),
            vh360_theme_asset_version('assets/js/activity-feed.js'),
            true
        );

        // Localize script with AJAX data.
        wp_localize_script('vh360-activity-feed-js', 'vh360Activity', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_activity_nonce'),
            'commentNonce' => wp_create_nonce('vh360_comment_post'),
            'currentUserId' => get_current_user_id(),
            'currentUserAvatar' => get_avatar_url(get_current_user_id(), array('size' => 32)),
            'currentUserName' => wp_get_current_user()->display_name,
            'strings' => array(
                'loading' => esc_html__('Loading...', 'videohub360-theme'),
                'loadMore' => esc_html__('Load More', 'videohub360-theme'),
                'error' => esc_html__('An error occurred. Please try again.', 'videohub360-theme'),
                'noMore' => esc_html__('No more activities to show.', 'videohub360-theme'),
                'shareSuccess' => esc_html__('Link copied to clipboard!', 'videohub360-theme'),
                'shareError' => esc_html__('Could not copy link', 'videohub360-theme'),
                'share' => esc_html__('share', 'videohub360-theme'),
                'shares' => esc_html__('shares', 'videohub360-theme'),
            ),
        ));
    }

    // Only enqueue additional UI enhancement styles on activity feed template.
    if (vh360_is_activity_feed_surface()) {
        wp_enqueue_style(
            'vh360-feed-ui-enhancements',
            VH360_THEME_URI . '/assets/css/feed-ui-enhancements.css',
            array('videohub360-theme-style', 'vh360-activity-feed'),
            vh360_theme_asset_version('assets/css/feed-ui-enhancements.css')
        );
    }

    // Profile Fields public display styles (About sections + edit form toggles).
    if (vh360_is_profile_fields_surface()) {
        wp_enqueue_style(
            'vh360-profile-fields',
            VH360_THEME_URI . '/assets/css/profile-fields.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/profile-fields.css')
        );
    }

    if (vh360_is_follow_surface()) {
        wp_enqueue_script(
            'vh360-follow-system',
            VH360_THEME_URI . '/assets/js/follow-system.js',
            array('jquery'),
            vh360_theme_asset_version('assets/js/follow-system.js'),
            true
        );

        wp_localize_script('vh360-follow-system', 'vh360Follow', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'followText' => __('Follow', 'videohub360-theme'),
            'unfollowText' => __('Unfollow', 'videohub360-theme'),
            'errorText' => __('An error occurred. Please try again.', 'videohub360-theme'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_activity_feed_assets', 20);

/**
 * Conditionally enqueue events assets
 */
function vh360_enqueue_events_assets() {
    // Load on event archives and single event pages
    if (is_singular('vh360_event') || is_post_type_archive('vh360_event') || is_tax(array('vh360_event_category', 'vh360_event_tag'))) {
        wp_enqueue_style(
            'vh360-events',
            VH360_THEME_URI . '/assets/css/events.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/events.css')
        );

        // Load JS on single event pages (for RSVP/calendar) and archive pages (for filtering/AJAX)
        if (is_singular('vh360_event') || is_post_type_archive('vh360_event') || is_tax(array('vh360_event_category', 'vh360_event_tag'))) {
            wp_enqueue_script(
                'vh360-events',
                VH360_THEME_URI . '/assets/js/events.js',
                array('jquery'),
                vh360_theme_asset_version('assets/js/events.js'),
                true
            );

            wp_localize_script('vh360-events', 'vh360Events', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('vh360_event_nonce'),
                'i18n'    => array(
                    'loading'      => __('Loading events...', 'videohub360-theme'),
                    'error'        => __('An error occurred. Please try again.', 'videohub360-theme'),
                    'rsvp'         => __('RSVP', 'videohub360-theme'),
                    'rsvpd'        => __('RSVP\'d', 'videohub360-theme'),
                    'imagePreview' => __('Event image preview', 'videohub360-theme'),
                    'closeImage'   => __('Close image preview', 'videohub360-theme'),
                ),
            ));
        }
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_events_assets', 20);

/**
 * Conditionally enqueue user menu assets
 */
function vh360_enqueue_user_menu_assets() {
    // User menu styles (always load for logged-out sign-in button)
    wp_enqueue_style(
        'vh360-user-menu',
        VH360_THEME_URI . '/assets/css/user-menu.css',
        array('videohub360-theme-style'),
        vh360_theme_asset_version('assets/css/user-menu.css')
    );

    // User menu script (only if logged in for dropdown functionality)
    if (is_user_logged_in()) {
        $user_menu_script_version = vh360_theme_asset_version('assets/js/user-menu.js');

        wp_enqueue_script(
            'vh360-user-menu',
            VH360_THEME_URI . '/assets/js/user-menu.js',
            array(),
            $user_menu_script_version,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_user_menu_assets', 20);

/**
 * Conditionally enqueue authentication page assets
 */
function vh360_enqueue_auth_assets() {
    // Load on login, register, lost password, reset password, registration landing, professional, instructor, and client registration template pages
    if (is_page_template('template-login.php') ||
        is_page_template('template-register.php') ||
        is_page_template('template-lost-password.php') ||
        is_page_template('template-reset-password.php') ||
        is_page_template('template-register-landing.php') ||
        is_page_template('template-register-professional.php') ||
        is_page_template('template-register-instructor.php') ||
        is_page_template('template-register-client.php')) {
        wp_enqueue_style(
            'vh360-auth-pages',
            VH360_THEME_URI . '/assets/css/auth-pages.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/auth-pages.css')
        );
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_auth_assets', 20);

/**
 * Enqueue avatar cropper assets on profile editing pages
 */
function vh360_enqueue_avatar_cropper_assets() {
    // Load on profile edit template and dashboard profile tabs.
    $is_profile_edit = is_page_template('template-profile-edit.php');
    $is_dashboard_profile_tab = (is_page_template('template-dashboard.php') || is_page_template('templates/dashboard.php'))
        && function_exists('vh360_is_dashboard_tab')
        && vh360_is_dashboard_tab(array('profile', 'business-profile'));

    if (!$is_profile_edit && !$is_dashboard_profile_tab) {
        return;
    }

    // Check if avatar cropper is enabled
    $options = get_option('vh360_profile_options', array());
    $enable_cropper = isset($options['enable_avatar_cropper']) ? $options['enable_avatar_cropper'] : true;

    if (!$enable_cropper) {
        return;
    }

    // Enqueue Cropper.js CSS from CDN
    wp_enqueue_style(
        'cropperjs',
        'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css',
        array(),
        '1.6.2'
    );

    // Enqueue Cropper.js JS from CDN
    wp_enqueue_script(
        'cropperjs',
        'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js',
        array(),
        '1.6.2',
        true
    );

    // Enqueue avatar cropper CSS
    wp_enqueue_style(
        'vh360-avatar-cropper',
        VH360_THEME_URI . '/assets/css/avatar-cropper.css',
        array('cropperjs'),
        vh360_theme_asset_version('assets/css/avatar-cropper.css')
    );

    // Enqueue avatar cropper JS
    wp_enqueue_script(
        'vh360-avatar-cropper',
        VH360_THEME_URI . '/assets/js/avatar-cropper.js',
        array('jquery', 'cropperjs'),
        vh360_theme_asset_version('assets/js/avatar-cropper.js'),
        true
    );

    // Get avatar settings
    $avatar_settings = function_exists('vh360_get_avatar_settings') ? vh360_get_avatar_settings() : array(
        'avatar_max_size' => 2,
        'avatar_output_size' => 300,
        'avatar_quality' => 90,
    );

    // Localize script with settings and translations
    wp_localize_script('vh360-avatar-cropper', 'vh360AvatarCropper', array(
        'maxSize'    => $avatar_settings['avatar_max_size'],
        'outputSize' => $avatar_settings['avatar_output_size'],
        'quality'    => $avatar_settings['avatar_quality'],
        'i18n'       => array(
            'cropTitle'       => __('Crop Your Avatar', 'videohub360-theme'),
            'previewLabel'    => __('Preview', 'videohub360-theme'),
            'apply'           => __('Apply Crop', 'videohub360-theme'),
            'cancel'          => __('Cancel', 'videohub360-theme'),
            'close'           => __('Close', 'videohub360-theme'),
            'cropImageAlt'    => __('Image to crop', 'videohub360-theme'),
            'previewAlt'      => __('Avatar preview', 'videohub360-theme'),
            'invalidFileType' => __('Invalid file type. Please upload a JPG, PNG, GIF, WebP, HEIC, or HEIF image.', 'videohub360-theme'),
            'fileTooLarge'    => sprintf(
                /* translators: %s: maximum file size */
                __('File size exceeds maximum allowed size of %s MB.', 'videohub360-theme'),
                $avatar_settings['avatar_max_size']
            ),
        ),
    ));
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_avatar_cropper_assets', 20);

/**
 * Enqueue admin scripts for better backend experience
 */
function vh360_enqueue_admin_assets($hook) {
    // Only load on post edit screens
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }

    // Add any admin-specific styles or scripts here if needed in the future
}
add_action('admin_enqueue_scripts', 'vh360_enqueue_admin_assets');

/**
 * Enqueue WordPress comments YouTube-style assets
 */
function vh360_enqueue_wp_comments_assets() {
    // Only load on singular pages that support comments
    if (!is_singular() || !comments_open() && !get_comments_number()) {
        return;
    }

    // Enqueue YouTube-style comments CSS
    wp_enqueue_style(
        'vh360-wp-comments',
        VH360_THEME_URI . '/assets/css/comments-youtube-style.css',
        array('videohub360-theme-style'),
        vh360_theme_asset_version('assets/css/comments-youtube-style.css')
    );

    // Enqueue activity feed CSS for comment styling (reuse existing styles)
    wp_enqueue_style(
        'vh360-activity-feed',
        VH360_THEME_URI . '/assets/css/activity-feed.css',
        array('videohub360-theme-style'),
        vh360_theme_asset_version('assets/css/activity-feed.css')
    );

    // Enqueue WordPress comments handler JavaScript
    wp_enqueue_script(
        'vh360-wp-comments-handler',
        VH360_THEME_URI . '/assets/js/wp-comments-handler.js',
        array('jquery', 'comment-reply'),
        vh360_theme_asset_version('assets/js/wp-comments-handler.js'),
        true
    );

    // Localize script with necessary data
    wp_localize_script(
        'vh360-wp-comments-handler',
        'vh360CommentsData',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'activityNonce' => wp_create_nonce('vh360_activity_nonce'),
            'adminUrl' => admin_url(),
            'isUserLoggedIn' => is_user_logged_in(),
            'i18n' => array(
                'likeError' => __('Unable to like comment. Please try again.', 'videohub360-theme'),
                'deleteConfirm' => __('Are you sure you want to delete this comment? This action cannot be undone.', 'videohub360-theme'),
            ),
        )
    );
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_wp_comments_assets', 20);

/**
 * Conditionally enqueue blog archive assets
 */
function vh360_enqueue_blog_archive_assets() {
    // Load on blog archive, category, tag, and search pages for standard posts
    $post_type = get_query_var('post_type');
    $is_post_search = is_search() && (empty($post_type) || $post_type === 'post');

    if (is_home() || is_category() || is_tag() || $is_post_search) {
        wp_enqueue_style(
            'vh360-blog-archive',
            VH360_THEME_URI . '/assets/css/blog-archive.css',
            array('videohub360-theme-style'),
            vh360_theme_asset_version('assets/css/blog-archive.css')
        );

        wp_enqueue_script(
            'vh360-blog-archive',
            VH360_THEME_URI . '/assets/js/blog-archive.js',
            array('jquery'),
            vh360_theme_asset_version('assets/js/blog-archive.js'),
            true
        );

        wp_localize_script('vh360-blog-archive', 'vh360Blog', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('vh360_blog_nonce'),
            'i18n'    => array(
                'loading'      => __('Loading posts...', 'videohub360-theme'),
                'error'        => __('An error occurred. Please try again.', 'videohub360-theme'),
                'oneResult'    => __('1 post found', 'videohub360-theme'),
                'resultsCount' => __('%d posts found', 'videohub360-theme'),
            ),
        ));
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_blog_archive_assets', 20);
