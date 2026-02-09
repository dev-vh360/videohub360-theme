<?php
/**
 * Template for Single Community Post
 * 
 * Displays individual community posts with activity feed styling
 * Used when clicking notifications, shared posts, or direct links
 * 
 * @package VideoHub360_Theme
 */

get_header();

?>

<div class="vh360-single-post-page">
    
    <!-- Back to Feed Button -->
    <div class="vh360-single-post-header">
        <div class="vh360-back-button-wrapper">
            <?php
            // Try to find the activity feed page dynamically
            $activity_feed_url = home_url(); // Default to home
            
            // Check if there's a page with the activity feed template
            $pages = get_pages(array(
                'meta_key' => '_wp_page_template',
                'meta_value' => 'template-activity-feed.php',
                'post_status' => 'publish'
            ));
            
            if (!empty($pages)) {
                $activity_feed_url = get_permalink($pages[0]->ID);
            } else {
                // Fallback: try common URL patterns
                $activity_feed_url = home_url('/activity-feed');
            }
            
            // Allow themes/plugins to filter the activity feed URL
            $activity_feed_url = apply_filters('vh360_activity_feed_url', $activity_feed_url);
            ?>
            <a href="<?php echo esc_url($activity_feed_url); ?>" class="vh360-back-to-feed">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
                <span><?php esc_html_e('Back to Feed', 'videohub360-theme'); ?></span>
            </a>
        </div>
    </div>
    
    <!-- Single Post Container -->
    <div class="vh360-single-post-container">
        <div class="vh360-single-post-wrapper">
            
            <?php
            while (have_posts()) : the_post();
                
                global $post;
                
                // Check if vh360_render_community_post function exists
                if (function_exists('vh360_render_community_post')) {
                    
                    // Use activity feed rendering function
                    vh360_render_community_post($post);
                    
                } else {
                    
                    // Fallback: render manually with activity feed structure
                    $post_id = get_the_ID();
                    $author = get_userdata($post->post_author);
                    $post_date = human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'videohub360-theme');
                    
                    ?>
                    <div class="vh360-community-post" data-post-id="<?php echo esc_attr($post_id); ?>">
                        
                        <!-- Post Header -->
                        <div class="vh360-community-header">
                            <?php echo get_avatar($author->ID, 48); ?>
                            <div class="vh360-post-header-info">
                                <strong><?php echo esc_html($author->display_name); ?></strong>
                                <span class="vh360-community-time"><?php echo esc_html($post_date); ?></span>
                            </div>
                        </div>
                        
                        <!-- Post Content -->
                        <div class="vh360-community-content">
                            <div class="vh360-community-body">
                                <?php the_content(); ?>
                            </div>
                        </div>
                        
                        <!-- Post Media (if any) -->
                        <?php
                        $attachments = get_post_meta($post_id, 'vh360_post_attachments', true);
                        if (!empty($attachments) && function_exists('vh360_render_post_media')) {
                            vh360_render_post_media($attachments, $post_id);
                        }
                        ?>
                        
                        <!-- Stats Row -->
                        <?php
                        if (function_exists('vh360_render_interactive_stats_row')) {
                            vh360_render_interactive_stats_row($post_id);
                        }
                        ?>
                        
                        <!-- Comments Section -->
                        <?php
                        if (function_exists('vh360_render_comments_section')) {
                            vh360_render_comments_section($post_id);
                        }
                        ?>
                        
                    </div>
                    <?php
                }
                
            endwhile;
            ?>
            
        </div>
    </div>
    
</div>

<?php
get_footer();
?>
