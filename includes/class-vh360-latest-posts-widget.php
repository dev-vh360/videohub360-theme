<?php
/**
 * VH360 Latest Posts Widget
 *
 * A custom WordPress widget to display latest posts with thumbnails
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Latest Posts Widget Class
 *
 * Displays a list of recent posts with featured images, titles, and optional metadata
 */
class VH360_Latest_Posts_Widget extends WP_Widget {

    /**
     * Constructor
     *
     * Sets up the widget name, description, and options
     */
    public function __construct() {
        parent::__construct(
            'vh360_latest_posts_widget',
            esc_html__('VH360 Latest Posts', 'videohub360-theme'),
            array(
                'description' => esc_html__('Display latest posts with thumbnails', 'videohub360-theme'),
                'classname'   => 'vh360-latest-posts-widget',
            )
        );
    }

    /**
     * Front-end display of widget
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance) {
        // Extract widget arguments
        echo $args['before_widget'];

        // Get widget settings
        $title = !empty($instance['title']) ? $instance['title'] : esc_html__('Latest Posts', 'videohub360-theme');
        $number_posts = !empty($instance['number_posts']) ? absint($instance['number_posts']) : 5;
        $show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : true;
        $show_excerpt = isset($instance['show_excerpt']) ? (bool) $instance['show_excerpt'] : false;

        // Display widget title
        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        // Query latest posts
        $query_args = array(
            'post_type'           => 'post',
            'posts_per_page'      => $number_posts,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true, // Performance optimization
        );

        $latest_posts = new WP_Query($query_args);

        if ($latest_posts->have_posts()) {
            echo '<ul class="vh360-latest-posts-list">';

            while ($latest_posts->have_posts()) {
                $latest_posts->the_post();
                ?>
                <li class="vh360-latest-post-item">
                    <div class="vh360-latest-post-content">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="vh360-latest-post-thumbnail">
                                <a href="<?php echo esc_url(get_permalink()); ?>" title="<?php echo esc_attr(get_the_title()); ?>">
                                    <?php the_post_thumbnail('thumbnail'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="vh360-latest-post-details">
                            <h3 class="vh360-latest-post-title">
                                <a href="<?php echo esc_url(get_permalink()); ?>">
                                    <?php echo esc_html(get_the_title()); ?>
                                </a>
                            </h3>

                            <?php if ($show_date) : ?>
                                <time class="vh360-latest-post-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                    <?php echo esc_html(get_the_date()); ?>
                                </time>
                            <?php endif; ?>

                            <?php if ($show_excerpt) : ?>
                                <div class="vh360-latest-post-excerpt">
                                    <?php
                                    $excerpt = get_the_excerpt();
                                    $trimmed_excerpt = wp_trim_words($excerpt, 15, '...');
                                    echo esc_html($trimmed_excerpt);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php
            }

            echo '</ul>';

            // Reset post data
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__('No posts found.', 'videohub360-theme') . '</p>';
        }

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance) {
        // Set default values
        $title = !empty($instance['title']) ? $instance['title'] : esc_html__('Latest Posts', 'videohub360-theme');
        $number_posts = !empty($instance['number_posts']) ? absint($instance['number_posts']) : 5;
        $show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : true;
        $show_excerpt = isset($instance['show_excerpt']) ? (bool) $instance['show_excerpt'] : false;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'videohub360-theme'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number_posts')); ?>">
                <?php esc_html_e('Number of posts to show:', 'videohub360-theme'); ?>
            </label>
            <input class="tiny-text" 
                   id="<?php echo esc_attr($this->get_field_id('number_posts')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('number_posts')); ?>" 
                   type="number" 
                   step="1" 
                   min="1" 
                   value="<?php echo esc_attr($number_posts); ?>" 
                   size="3">
        </p>

        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   <?php checked($show_date); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_date')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_date')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>">
                <?php esc_html_e('Display post date?', 'videohub360-theme'); ?>
            </label>
        </p>

        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   <?php checked($show_excerpt); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_excerpt')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_excerpt')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_excerpt')); ?>">
                <?php esc_html_e('Display post excerpt?', 'videohub360-theme'); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['number_posts'] = !empty($new_instance['number_posts']) ? absint($new_instance['number_posts']) : 5;
        $instance['show_date'] = !empty($new_instance['show_date']) ? 1 : 0;
        $instance['show_excerpt'] = !empty($new_instance['show_excerpt']) ? 1 : 0;

        return $instance;
    }
}

/**
 * Register the widget
 */
function vh360_register_latest_posts_widget() {
    register_widget('VH360_Latest_Posts_Widget');
}
add_action('widgets_init', 'vh360_register_latest_posts_widget');
