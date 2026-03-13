<?php
/**
 * Blog Archive Pagination
 *
 * Pagination for the blog archive.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get query object
global $wp_query;
$query = isset($args['query']) ? $args['query'] : $wp_query;

if (!$query || $query->max_num_pages <= 1) {
    return;
}

$current_page = max(1, get_query_var('paged'));
?>

<div class="vh360-blog-pagination" id="vh360-blog-pagination">
    <?php
    echo paginate_links(array(
        'total'     => $query->max_num_pages,
        'current'   => $current_page,
        'prev_text' => '&larr; ' . __('Previous', 'videohub360-theme'),
        'next_text' => __('Next', 'videohub360-theme') . ' &rarr;',
        'type'      => 'list',
        'end_size'  => 1,
        'mid_size'  => 2,
    ));
    ?>
</div>
