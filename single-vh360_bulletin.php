<?php
/**
 * Single Bulletin Template
 *
 * Full bulletin content display with actions.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    
    $bulletin_id = get_the_ID();
    $user_id = get_current_user_id();
    $priority = vh360_get_bulletin_priority($bulletin_id);
    $type = vh360_get_bulletin_type($bulletin_id);
    $expiry_date = get_post_meta($bulletin_id, '_vh360_bulletin_expiry_date', true);
    $dismissible = get_post_meta($bulletin_id, '_vh360_bulletin_dismissible', true);
    $is_read = vh360_is_bulletin_read($bulletin_id, $user_id);
    
    $priority_colors = array(
        'normal' => '#3b82f6',
        'important' => '#f59e0b',
        'urgent' => '#ef4444'
    );
    
    $priority_labels = array(
        'normal' => __('Normal', 'videohub360-theme'),
        'important' => __('Important', 'videohub360-theme'),
        'urgent' => __('Urgent', 'videohub360-theme')
    );
    
    ?>
    
    <div id="primary" class="content-area vh360-single-bulletin-page">
        <main id="main" class="site-main">
            <div class="container">
                
                <!-- Back Link -->
                <div class="vh360-bulletin-back">
                    <a href="<?php echo esc_url(get_post_type_archive_link('vh360_bulletin')); ?>" 
                       class="vh360-bulletin-back-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        <?php esc_html_e('Back to Bulletins', 'videohub360-theme'); ?>
                    </a>
                </div>
                
                <article class="vh360-single-bulletin" data-bulletin-id="<?php echo esc_attr($bulletin_id); ?>">
                    
                    <!-- Header -->
                    <header class="vh360-bulletin-single-header">
                        
                        <!-- Priority Badge -->
                        <div class="vh360-bulletin-single-meta">
                            <span class="vh360-bulletin-priority-badge-large" 
                                  style="background-color: <?php echo esc_attr($priority_colors[$priority]); ?>;">
                                <?php echo esc_html($priority_labels[$priority]); ?>
                            </span>
                            <?php if (!$is_read && $user_id) : ?>
                                <span class="vh360-bulletin-unread-indicator-large">
                                    <svg width="10" height="10" viewBox="0 0 8 8" fill="currentColor">
                                        <circle cx="4" cy="4" r="4"/>
                                    </svg>
                                    <?php esc_html_e('Unread', 'videohub360-theme'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Title -->
                        <h1 class="vh360-bulletin-single-title"><?php the_title(); ?></h1>
                        
                        <!-- Info -->
                        <div class="vh360-bulletin-single-info">
                            <div class="vh360-bulletin-single-date">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php 
                                /* translators: %s: Time since publication */
                                printf(esc_html__('Posted %s ago', 'videohub360-theme'), 
                                       esc_html(human_time_diff(get_the_time('U'), current_time('timestamp')))); 
                                ?>
                            </div>
                            
                            <div class="vh360-bulletin-single-author">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <?php 
                                /* translators: %s: Author name */
                                printf(esc_html__('By %s', 'videohub360-theme'), 
                                       esc_html(get_the_author())); 
                                ?>
                            </div>
                            
                            <?php if ($expiry_date && is_numeric($expiry_date)) : ?>
                                <div class="vh360-bulletin-single-expiry">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?php 
                                    /* translators: %s: Date when bulletin expires */
                                    printf(esc_html__('Expires on %s', 'videohub360-theme'), 
                                           esc_html(wp_date(get_option('date_format'), $expiry_date))); 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    </header>
                    
                    <!-- Featured Image -->
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="vh360-bulletin-single-thumbnail">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content -->
                    <div class="vh360-bulletin-single-content">
                        <?php the_content(); ?>
                    </div>
                    
                    <!-- Actions -->
                    <?php if ($user_id) : ?>
                        <div class="vh360-bulletin-single-actions">
                            <?php if (!$is_read) : ?>
                                <button class="vh360-bulletin-mark-read vh360-btn-primary">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <?php esc_html_e('Mark as Read', 'videohub360-theme'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($dismissible) : ?>
                                <button class="vh360-bulletin-dismiss vh360-btn-secondary">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    <?php esc_html_e('Dismiss', 'videohub360-theme'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Share Buttons -->
                    <div class="vh360-bulletin-single-share">
                        <h3 class="vh360-bulletin-share-title">
                            <?php esc_html_e('Share this bulletin', 'videohub360-theme'); ?>
                        </h3>
                        <div class="vh360-bulletin-share-buttons">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="vh360-share-btn vh360-share-twitter">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
                                </svg>
                                Twitter
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="vh360-share-btn vh360-share-facebook">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path>
                                </svg>
                                Facebook
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(get_permalink()); ?>&title=<?php echo urlencode(get_the_title()); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="vh360-share-btn vh360-share-linkedin">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"></path>
                                    <circle cx="4" cy="4" r="2"></circle>
                                </svg>
                                LinkedIn
                            </a>
                            <button class="vh360-share-btn vh360-share-copy" 
                                    data-url="<?php echo esc_attr(get_permalink()); ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                                </svg>
                                <?php esc_html_e('Copy Link', 'videohub360-theme'); ?>
                            </button>
                        </div>
                    </div>
                    
                </article>
                
            </div>
        </main>
    </div>
    
    <?php
endwhile;

get_footer();
?>