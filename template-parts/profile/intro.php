<?php
/**
 * Profile Intro Card Template Part
 *
 * Displays user intro card with bio and social links (without join date).
 * Used on mobile devices in the "About" tab.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the author being displayed
$author_id = isset( $author_id ) ? absint( $author_id ) : absint( get_queried_object_id() );

if ( ! $author_id ) {
    $author_id = absint( get_the_author_meta( 'ID' ) );
}

if ( ! $author_id ) {
    return;
}

$author = get_userdata($author_id);
if (!$author) {
    return;
}

// Get user data
$bio = vh360_get_user_bio($author_id);
$website = $author->user_url;
$social_links = vh360_get_user_social_links($author_id);

// Get profile options
$profile_options = get_option('vh360_profile_options', array());
$profile_defaults = array(
    'enable_profiles' => true,
    'show_social' => true,
);
$profile_options = wp_parse_args($profile_options, $profile_defaults);
?>

<!-- Intro Card -->
<div class="vh360-profile-card vh360-profile-intro-card">
    <h3 class="vh360-profile-card-title"><?php esc_html_e('Intro', 'videohub360-theme'); ?></h3>
    
    <?php if (!empty($bio)) : ?>
        <div class="vh360-profile-card-content">
            <?php echo wp_kses_post(wpautop($bio)); ?>
        </div>
    <?php endif; ?>
    
    <div class="vh360-profile-meta-list">
        <?php if ($profile_options['show_social'] && (!empty($social_links) || $website)) : ?>
            <?php if ($website) : ?>
                <div class="vh360-profile-meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer">
                        <?php 
                        $parsed_url = parse_url($website);
                        $host = isset($parsed_url['host']) ? $parsed_url['host'] : $website;
                        echo esc_html($host);
                        ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php foreach ($social_links as $platform => $url) : ?>
                <?php if ($url) : ?>
                    <div class="vh360-profile-meta-item">
                        <span><?php echo esc_html(ucfirst($platform)); ?>:</span>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
                            <?php 
                            $parsed_url = parse_url($url);
                            if (isset($parsed_url['path']) && !empty($parsed_url['path'])) {
                                echo esc_html(basename($parsed_url['path']));
                            } else {
                                echo esc_html(ucfirst($platform));
                            }
                            ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php
    if ( $author_id && function_exists( 'vh360_render_public_profile_fields' ) ) {
        vh360_render_public_profile_fields( $author_id );
    }
    ?>
</div>
