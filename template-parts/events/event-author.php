<?php
if (!defined('ABSPATH')) { exit; }

$event_id = !empty($args['event_id']) ? (int) $args['event_id'] : (int) get_the_ID();
if (!$event_id) { return; }

$author_id = (int) get_post_field('post_author', $event_id);
$author_id = (int) apply_filters('vh360_event_display_author_id', $author_id, $event_id);

if (!$author_id) { return; }

$author = get_userdata($author_id);
if (!$author) { return; }

$profile_url = vh360_get_profile_url($author_id);
if (!$profile_url) {
    $profile_url = get_author_posts_url($author_id);
}
?>
<div class="vh360-event-author">
  <a class="vh360-event-author-link" href="<?php echo esc_url($profile_url); ?>">
    <span class="vh360-event-author-avatar">
      <?php echo get_avatar($author_id, 44); ?>
    </span>
    <span class="vh360-event-author-meta">
      <span class="vh360-event-author-label"><?php esc_html_e('Posted by', 'videohub360-theme'); ?></span>
      <span class="vh360-event-author-name"><?php echo esc_html($author->display_name); ?></span>
    </span>
  </a>
</div>
