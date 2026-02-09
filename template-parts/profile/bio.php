<?php
/**
 * Profile Bio Template Part
 *
 * Displays user bio/description with proper escaping and placeholder
 * if bio is empty.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the author being displayed
$author_id = get_queried_object_id();

if (!$author_id) {
    return;
}

// Get user bio
$bio = vh360_get_user_bio($author_id);
$has_bio = !empty($bio);
?>

<div class="vh360-profile-bio">
    <h2 class="vh360-profile-section-title"><?php esc_html_e('About', 'videohub360-theme'); ?></h2>
    
    <?php if ($has_bio) : ?>
        <div class="vh360-profile-bio-content">
            <?php echo wp_kses_post(wpautop($bio)); ?>
        </div>
    <?php else : ?>
        <div class="vh360-profile-bio-content vh360-profile-bio-empty">
            <p class="vh360-empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <?php
                if (vh360_user_can_edit_profile($author_id)) {
                    esc_html_e('Tell the community about yourself! Click "Edit Profile" to add your bio.', 'videohub360-theme');
                } else {
                    /* translators: Message shown when viewing another user's profile with no bio */
                    esc_html_e('This user has not added a bio yet.', 'videohub360-theme');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
/* Bio empty state styles */
.vh360-profile-bio-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-light);
}

.vh360-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    max-width: 400px;
    margin: 0 auto;
}

.vh360-empty-state svg {
    color: var(--border-color);
}
</style>
