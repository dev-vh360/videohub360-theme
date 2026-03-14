<?php
/**
 * The template for displaying tag archives
 *
 * Uses the blog archive system for a first-class tag browsing experience.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Header visibility for tag archives (always show for tags)
$vh360_show_header  = true;
$vh360_header_title = single_term_title('', false);
$vh360_header_desc  = term_description();
?>

<div id="primary" class="content-area vh360-blog-archive vh360-blog-archive-tag <?php echo $vh360_show_header ? '' : 'vh360-template-header-off'; ?>">
    <main id="main" class="site-main">
        
        <?php
        // Blog header with tag title and description
        get_template_part('template-parts/blog/blog-header', null, array(
            'show_header' => $vh360_show_header,
            'header_title' => $vh360_header_title,
            'header_desc' => $vh360_header_desc,
        ));
        
        // Search, filters, and sort controls
        get_template_part('template-parts/blog/blog-controls');
        
        // Results container (posts grid + pagination)
        get_template_part('template-parts/blog/blog-results');
        ?>
        
    </main>
</div>

<?php
get_footer();
