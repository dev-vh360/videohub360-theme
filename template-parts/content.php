<?php
/**
 * Template part for displaying posts
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php
        if (is_singular()) :
            the_title('<h1 class="entry-title">', '</h1>');
        else :
            the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
        endif;
        ?>

        <div class="entry-meta">
            <?php
            videohub360_theme_posted_on();
            videohub360_theme_posted_by();
            ?>
        </div><!-- .entry-meta -->
    </header><!-- .entry-header -->

    <?php if (has_post_thumbnail() && !is_singular()) : ?>
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('large'); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php
        if (is_singular()) {
            the_content();
        } else {
            the_excerpt();
        }

        wp_link_pages(array(
            'before' => '<div class="page-links">' . esc_html__('Pages:', 'videohub360-theme'),
            'after'  => '</div>',
        ));
        ?>
    </div><!-- .entry-content -->

    <?php if (!is_singular()) : ?>
        <footer class="entry-footer">
            <?php videohub360_theme_entry_footer(); ?>
        </footer><!-- .entry-footer -->
    <?php endif; ?>
</article><!-- #post-<?php the_ID(); ?> -->
