<?php
/**
 * Profile Card Component
 *
 * Reusable user profile card component displaying avatar,
 * username, bio excerpt, and user statistics.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get args with defaults
$args = wp_parse_args($args, array(
    'user_id' => get_current_user_id(),
    'show_avatar' => true,
    'show_bio' => true,
    'show_stats' => true,
    'show_follow_button' => true,
    'avatar_size' => 80,
    'stats_context' => 'default',
));

$user_id = $args['user_id'];

if (!$user_id) {
    return;
}

// Get user data
$user = get_userdata($user_id);
if (!$user) {
    return;
}

$profile_url = vh360_get_profile_url($user_id);
$user_name = $user->display_name;
$user_bio = get_user_meta($user_id, 'description', true);
$user_stats = vh360_get_user_stats($user_id);

$stats_context = isset($args['stats_context']) ? sanitize_key($args['stats_context']) : 'default';
$show_course_stats = (
    'course' === $stats_context
    && function_exists('vh360_user_is_course_instructor')
    && vh360_user_is_course_instructor($user_id)
);

$course_card_stats = array(
    'courses' => 0,
    'lessons' => 0,
    'followers' => isset($user_stats['followers']) ? (int) $user_stats['followers'] : 0,
);

if ($show_course_stats) {
    $courses = function_exists('vh360_get_user_courses') ? vh360_get_user_courses($user_id) : array();

    $course_card_stats['courses'] = is_array($courses) ? count($courses) : 0;

    if (!empty($courses) && function_exists('vh360_get_course_lesson_count')) {
        foreach ($courses as $course) {
            if (isset($course->term_id)) {
                $course_card_stats['lessons'] += (int) vh360_get_course_lesson_count($course->term_id);
            }
        }
    }
}

$course_label_singular = function_exists('vh360_get_course_label') ? vh360_get_course_label(false) : __('Course', 'videohub360-theme');
$course_label_plural = function_exists('vh360_get_course_label') ? vh360_get_course_label(true) : __('Courses', 'videohub360-theme');
$lesson_label_singular = function_exists('vh360_get_lesson_label') ? vh360_get_lesson_label(false) : __('Lesson', 'videohub360-theme');
$lesson_label_plural = function_exists('vh360_get_lesson_label') ? vh360_get_lesson_label(true) : __('Lessons', 'videohub360-theme');
?>

<article class="vh360-profile-card" data-user-id="<?php echo esc_attr($user_id); ?>">
    <a href="<?php echo esc_url($profile_url); ?>" class="vh360-profile-card-link">
        <?php if ($args['show_avatar']) : ?>
            <div class="vh360-profile-card-avatar">
                <?php echo get_avatar($user_id, $args['avatar_size'], '', $user_name, array('class' => 'vh360-avatar-image')); ?>
            </div>
        <?php endif; ?>

        <div class="vh360-profile-card-body">
            <h3 class="vh360-profile-card-name"><?php echo esc_html($user_name); ?></h3>

            <p class="vh360-profile-card-username">@<?php echo esc_html($user->user_login); ?></p>

            <?php
            // Display member category if enabled and assigned
            $members_options = get_option('vh360_members_options', array());
            if (!empty($members_options['enable_category_filter'])) {
                $member_category = get_user_meta($user_id, '_vh360_member_category', true);
                if (!empty($member_category)) {
                    $category_label = function_exists('vh360_get_member_category_label')
                        ? vh360_get_member_category_label($member_category)
                        : '';

                    if (!empty($category_label)) :
            ?>
                <p class="vh360-profile-card-category">
                    <span class="vh360-category-badge"><?php echo esc_html($category_label); ?></span>
                </p>
            <?php
                    endif;
                }
            }
            ?>

            <?php if ($args['show_bio'] && $user_bio) : ?>
                <p class="vh360-profile-card-bio"><?php echo esc_html(wp_trim_words($user_bio, 15, '...')); ?></p>
            <?php endif; ?>

            <?php if ($args['show_stats']) : ?>
                <div class="vh360-profile-card-stats">
                    <?php if ($show_course_stats) : ?>
                        <div class="vh360-profile-card-stat">
                            <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($course_card_stats['courses'])); ?></span>
                            <span class="vh360-profile-card-stat-label">
                                <?php echo esc_html(1 === $course_card_stats['courses'] ? $course_label_singular : $course_label_plural); ?>
                            </span>
                        </div>

                        <div class="vh360-profile-card-stat">
                            <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($course_card_stats['lessons'])); ?></span>
                            <span class="vh360-profile-card-stat-label">
                                <?php echo esc_html(1 === $course_card_stats['lessons'] ? $lesson_label_singular : $lesson_label_plural); ?>
                            </span>
                        </div>

                        <div class="vh360-profile-card-stat">
                            <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($course_card_stats['followers'])); ?></span>
                            <span class="vh360-profile-card-stat-label">
                                <?php echo esc_html(_n('Follower', 'Followers', $course_card_stats['followers'], 'videohub360-theme')); ?>
                            </span>
                        </div>
                    <?php else : ?>
                        <div class="vh360-profile-card-stat">
                            <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($user_stats['videos'])); ?></span>
                            <span class="vh360-profile-card-stat-label">
                                <?php echo esc_html(_n('Video', 'Videos', $user_stats['videos'], 'videohub360-theme')); ?>
                            </span>
                        </div>

                        <div class="vh360-profile-card-stat">
                            <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($user_stats['followers'])); ?></span>
                            <span class="vh360-profile-card-stat-label">
                                <?php echo esc_html(_n('Follower', 'Followers', $user_stats['followers'], 'videohub360-theme')); ?>
                            </span>
                        </div>

                        <div class="vh360-profile-card-stat">
                            <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($user_stats['views'])); ?></span>
                            <span class="vh360-profile-card-stat-label">
                                <?php echo esc_html(_n('View', 'Views', $user_stats['views'], 'videohub360-theme')); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </a>

    <?php
    // Show follow button if enabled, user is logged in, and not viewing own card
    if ($args['show_follow_button'] && is_user_logged_in() && get_current_user_id() !== $user_id && function_exists('vh360_follow_button')) :
    ?>
        <div class="vh360-profile-card-footer">
            <?php vh360_follow_button($user_id, 'vh360-profile-card-follow-btn'); ?>
        </div>
    <?php endif; ?>
</article>

<style>
/* Profile Card Styles - See profiles.css for full styles */
/* This is a minimal inline style to ensure the component works standalone */
.vh360-profile-card {
    background: var(--bg-color, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: var(--border-radius, 8px);
    overflow: hidden;
    transition: var(--transition, all 0.3s ease);
    display: flex;
    flex-direction: column;
}

.vh360-profile-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-4px);
}

.vh360-profile-card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem;
}

.vh360-profile-card-avatar {
    margin-bottom: 1rem;
}

.vh360-avatar-image {
    border-radius: 50%;
    border: 3px solid var(--border-color, #e5e7eb);
    transition: var(--transition, all 0.3s ease);
}

.vh360-profile-card:hover .vh360-avatar-image {
    border-color: var(--primary-color, #2563eb);
}

.vh360-profile-card-body {
    text-align: center;
    width: 100%;
}

.vh360-profile-card-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
    color: var(--text-color, #1f2937);
}

.vh360-profile-card-username {
    font-size: 0.875rem;
    color: var(--text-light, #6b7280);
    margin: 0 0 0.75rem;
}

.vh360-profile-card-bio {
    font-size: 0.875rem;
    color: var(--text-color, #1f2937);
    line-height: 1.5;
    margin: 0 0 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.vh360-profile-card-stats {
    display: flex;
    justify-content: space-around;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
    gap: 1rem;
}

.vh360-profile-card-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}

.vh360-profile-card-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-color, #1f2937);
    margin-bottom: 0.25rem;
}

.vh360-profile-card-stat-label {
    font-size: 0.75rem;
    color: var(--text-light, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.vh360-profile-card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
    background: var(--bg-light, #f9fafb);
}

.vh360-follow-btn {
    display: block;
    width: 100%;
    padding: 0.75rem 1.5rem;
    background: var(--primary-color, #3b82f6);
    color: #ffffff;
    border: none;
    border-radius: var(--border-radius, 8px);
    font-size: 0.875rem;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: var(--transition, all 0.3s ease);
}

.vh360-follow-btn:hover {
    background: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.vh360-follow-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.vh360-follow-btn.vh360-follow-btn--following,
.vh360-unfollow-btn {
    background: var(--bg-color, #ffffff);
    color: var(--text-color, #1f2937);
    border: 1px solid var(--border-color, #e5e7eb);
}

.vh360-follow-btn.vh360-follow-btn--following:hover,
.vh360-unfollow-btn:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #ef4444;
}

@media (max-width: 480px) {
    .vh360-profile-card-stats {
        flex-direction: column;
    }

    .vh360-profile-card-stat {
        flex-direction: row;
        justify-content: space-between;
        width: 100%;
    }
}
</style>
