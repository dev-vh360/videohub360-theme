<?php
/**
 * YouTube Live Auto-Broadcast monitor.
 *
 * Feeds detected YouTube livestream state into the existing VideoHub360 livestream meta/rendering system.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_YouTube_Live_Monitor {
    const CRON_HOOK = 'vh360_youtube_live_check';
    const CRON_INTERVAL = 'vh360_five_minutes';

    private $days = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
    private $detection_modes = array('scheduled', 'always', 'manual');
    private $post_behaviors = array('update_selected', 'auto_create');
    private $image_behaviors = array('keep_manual', 'youtube_if_empty', 'youtube_always', 'default_image');
    private $replay_behaviors = array('mark_ended', 'convert_replay_embed', 'keep_replay');

    public function __construct() {
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
        add_action(self::CRON_HOOK, array($this, 'cron_check'));
        add_action('admin_init', array($this, 'maybe_reschedule'));
        add_action('update_option_vh360_youtube_live_enabled', array($this, 'reschedule'), 10, 0);
        add_action('vh360_youtube_live_settings_saved', array($this, 'reschedule'));
        add_action('wp_ajax_vh360_youtube_check_now', array($this, 'ajax_check_now'));
    }

    public function add_cron_schedules($schedules) {
        $schedules[self::CRON_INTERVAL] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __('Every 5 minutes (VideoHub360 YouTube Live)', 'videohub360'),
        );
        return $schedules;
    }

    public function maybe_reschedule() {
        $state = (int) get_option('vh360_youtube_live_enabled', 0) . ':' . (string) wp_next_scheduled(self::CRON_HOOK);
        if (get_transient('vh360_youtube_cron_state') === $state) {
            return;
        }
        $this->reschedule();
        set_transient('vh360_youtube_cron_state', (int) get_option('vh360_youtube_live_enabled', 0) . ':' . (string) wp_next_scheduled(self::CRON_HOOK), HOUR_IN_SECONDS);
    }

    public function reschedule() {
        if ((int) get_option('vh360_youtube_live_enabled', 0) && 'manual' !== get_option('vh360_youtube_detection_mode', 'scheduled')) {
            if (!wp_next_scheduled(self::CRON_HOOK)) {
                wp_schedule_event(time() + MINUTE_IN_SECONDS, self::CRON_INTERVAL, self::CRON_HOOK);
            }
        } else {
            wp_clear_scheduled_hook(self::CRON_HOOK);
        }
    }

    public function cron_check() {
        update_option('vh360_youtube_last_heartbeat_at', current_time('mysql'), false);
        $this->check(false);
    }

    public function ajax_check_now() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'videohub360')), 403);
        }
        check_ajax_referer('vh360_youtube_check_now', 'nonce');
        $result = $this->check(true);
        wp_send_json_success($result);
    }

    public function check($manual = false) {
        update_option('vh360_youtube_last_check_at', current_time('mysql'), false);

        $api_key = sanitize_text_field(get_option('vh360_youtube_api_key', ''));
        $channel_id = sanitize_text_field(get_option('vh360_youtube_channel_id', ''));
        $mode = $this->sanitize_choice(get_option('vh360_youtube_detection_mode', 'scheduled'), $this->detection_modes, 'scheduled');
        $window = $this->get_active_window();

        if (!$manual && (!(int) get_option('vh360_youtube_live_enabled', 0) || 'manual' === $mode)) {
            return $this->status('disabled', null, 0, '', __('YouTube live monitor is disabled.', 'videohub360'));
        }
        if (!$manual && 'scheduled' === $mode && !$window['inside']) {
            $this->handle_missing_live();
            return $this->status('outside_schedule_window', null, 0, '', __('Current site time is outside configured YouTube detection windows.', 'videohub360'));
        }
        if (empty($api_key) || empty($channel_id)) {
            return $this->status('api_error', null, 0, '', __('Missing YouTube API key or channel ID.', 'videohub360'));
        }

        $live = $this->fetch_youtube_video('live', $api_key, $channel_id);
        if (is_wp_error($live)) {
            return $this->status('api_error', null, 0, '', $live->get_error_message());
        }
        if ($live) {
            $post_id = $this->upsert_post($live, 'live', $window);
            return $this->status('success', $live['video_id'], $post_id, get_post_meta($post_id, '_vh360_youtube_featured_image_result', true), '');
        }

        if ($manual || 'always' === $mode || ($window['inside'] && $window['phase'] === 'precheck')) {
            $upcoming = $this->fetch_youtube_video('upcoming', $api_key, $channel_id);
            if (is_wp_error($upcoming)) {
                return $this->status('api_error', null, 0, '', $upcoming->get_error_message());
            }
            if ($upcoming) {
                $post_id = $this->upsert_post($upcoming, 'upcoming', $window);
                return $this->status('upcoming_prepared', $upcoming['video_id'], $post_id, get_post_meta($post_id, '_vh360_youtube_featured_image_result', true), '');
            }
        }

        $this->handle_missing_live();
        return $this->status('no_live_found', null, 0, '', '');
    }

    private function fetch_youtube_video($event_type, $api_key, $channel_id) {
        $url = add_query_arg(array(
            'part' => 'snippet', 'channelId' => $channel_id, 'eventType' => $event_type,
            'type' => 'video', 'order' => 'date', 'maxResults' => 1, 'key' => $api_key,
        ), 'https://www.googleapis.com/youtube/v3/search');
        $response = wp_remote_get($url, array('timeout' => 15));
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) {
            $message = isset($body['error']['message']) ? sanitize_text_field($body['error']['message']) : __('YouTube API request failed.', 'videohub360');
            return new WP_Error('youtube_api_error', $message);
        }
        if (empty($body['items'][0]['id']['videoId'])) return null;
        $item = $body['items'][0];
        $thumbs = isset($item['snippet']['thumbnails']) ? $item['snippet']['thumbnails'] : array();
        $thumb = $thumbs['maxres']['url'] ?? $thumbs['high']['url'] ?? $thumbs['medium']['url'] ?? $thumbs['default']['url'] ?? '';
        return array(
            'video_id' => sanitize_text_field($item['id']['videoId']),
            'title' => sanitize_text_field($item['snippet']['title'] ?? ''),
            'description' => wp_kses_post($item['snippet']['description'] ?? ''),
            'thumbnail_url' => esc_url_raw($thumb),
            'published_at' => sanitize_text_field($item['snippet']['publishedAt'] ?? ''),
        );
    }

    private function upsert_post($video, $status, $window) {
        $post_id = $this->get_target_post_id($video['video_id']);
        $title_prefix = !empty($window['schedule']['title_prefix']) ? $window['schedule']['title_prefix'] . ': ' : '';
        $postarr = array(
            'post_type' => 'videohub360', 'post_status' => 'publish',
            'post_title' => $title_prefix . ($video['title'] ?: __('YouTube Livestream', 'videohub360')),
            'post_content' => $video['description'],
            'post_author' => absint(get_option('vh360_youtube_default_author_id', get_current_user_id())) ?: 1,
        );
        if ($post_id) { $postarr['ID'] = $post_id; wp_update_post(wp_slash($postarr)); } else { $post_id = wp_insert_post(wp_slash($postarr)); }
        if (is_wp_error($post_id) || !$post_id) return 0;

        $channel_id = sanitize_text_field(get_option('vh360_youtube_channel_id', ''));
        update_post_meta($post_id, '_vh360_youtube_video_id', $video['video_id']);
        update_post_meta($post_id, '_vh360_youtube_auto_managed', 'yes');
        update_post_meta($post_id, '_vh360_youtube_channel_id', $channel_id);
        update_post_meta($post_id, '_vh360_youtube_status', $status);
        update_post_meta($post_id, '_vh360_type', 'embed');
        update_post_meta($post_id, '_vh360_embed_code', $this->build_iframe($video['video_id']));

        if ('live' === $status) {
            update_post_meta($post_id, '_vh360_is_live', 'yes');
            update_post_meta($post_id, '_vh360_stream_stopped', 'no');
            update_post_meta($post_id, '_vh360_live_start_time', current_time('mysql'));
            update_post_meta($post_id, '_vh360_live_badge', 'yes');
            update_post_meta($post_id, '_vh360_badge_text', 'LIVE');
            update_post_meta($post_id, '_vh360_youtube_last_seen_live_at', current_time('mysql'));
            update_post_meta($post_id, '_vh360_youtube_last_seen_live_ts', time());
        } else {
            update_post_meta($post_id, '_vh360_is_live', 'no');
            update_post_meta($post_id, '_vh360_youtube_scheduled_start', $video['published_at']);
        }

        $this->assign_category($post_id, $window);
        $this->maybe_set_featured_image($post_id, $video['thumbnail_url']);
        return (int) $post_id;
    }

    private function build_iframe($video_id) {
        $embed_url = sprintf('https://www.youtube-nocookie.com/embed/%s?autoplay=0&enablejsapi=1&origin=%s', rawurlencode($video_id), rawurlencode(home_url()));
        return '<iframe src="' . esc_url($embed_url) . '" width="1280" height="720" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
    }

    private function get_target_post_id($video_id) {
        if ('update_selected' === get_option('vh360_youtube_post_behavior', 'auto_create')) {
            $target = absint(get_option('vh360_youtube_target_post_id', 0));
            if ($target && 'videohub360' === get_post_type($target)) return $target;
        }
        $posts = get_posts(array('post_type'=>'videohub360','posts_per_page'=>1,'fields'=>'ids','meta_query'=>array('relation'=>'AND',array('key'=>'_vh360_youtube_video_id','value'=>$video_id),array('key'=>'_vh360_youtube_auto_managed','value'=>'yes'))));
        return !empty($posts[0]) ? (int) $posts[0] : 0;
    }

    private function maybe_set_featured_image($post_id, $thumbnail_url) {
        $behavior = $this->sanitize_choice(get_option('vh360_youtube_featured_image_behavior', 'youtube_if_empty'), $this->image_behaviors, 'youtube_if_empty');
        if ('keep_manual' === $behavior || ('youtube_if_empty' === $behavior && has_post_thumbnail($post_id))) {
            update_post_meta($post_id, '_vh360_youtube_featured_image_result', 'kept_existing'); return;
        }
        if ('default_image' === $behavior) {
            $default_id = absint(get_option('vh360_youtube_default_featured_image_id', 0));
            if ($default_id) { set_post_thumbnail($post_id, $default_id); update_post_meta($post_id, '_vh360_youtube_featured_image_result', 'default_image'); }
            return;
        }
        if (!$thumbnail_url) { update_post_meta($post_id, '_vh360_youtube_featured_image_result', 'no_thumbnail'); return; }
        if (get_post_meta($post_id, '_vh360_youtube_thumbnail_url', true) === $thumbnail_url && has_post_thumbnail($post_id)) {
            update_post_meta($post_id, '_vh360_youtube_featured_image_result', 'thumbnail_reused'); return;
        }
        require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_id = media_sideload_image($thumbnail_url, $post_id, null, 'id');
        if (is_wp_error($attachment_id)) { update_post_meta($post_id, '_vh360_youtube_featured_image_result', 'thumbnail_error'); return; }
        set_post_thumbnail($post_id, $attachment_id);
        update_post_meta($post_id, '_vh360_youtube_thumbnail_url', esc_url_raw($thumbnail_url));
        update_post_meta($post_id, '_vh360_youtube_thumbnail_attachment_id', absint($attachment_id));
        update_post_meta($post_id, '_vh360_youtube_featured_image_result', 'youtube_thumbnail');
    }

    private function handle_missing_live() {
        $grace = max(1, min(240, absint(get_option('vh360_youtube_grace_minutes', 20))));
        $posts = get_posts(array('post_type'=>'videohub360','posts_per_page'=>20,'fields'=>'ids','meta_query'=>array(array('key'=>'_vh360_youtube_auto_managed','value'=>'yes'),array('key'=>'_vh360_youtube_status','value'=>'live'))));
        foreach ($posts as $post_id) {
            $last_seen_ts = absint(get_post_meta($post_id, '_vh360_youtube_last_seen_live_ts', true));
            if (!$last_seen_ts) {
                $last_seen_ts = $this->get_local_mysql_timestamp(get_post_meta($post_id, '_vh360_youtube_last_seen_live_at', true));
            }
            if ($last_seen_ts && (time() - $last_seen_ts) < $grace * MINUTE_IN_SECONDS) continue;
            $behavior = $this->sanitize_choice(get_option('vh360_youtube_replay_behavior', 'mark_ended'), $this->replay_behaviors, 'mark_ended');
            $this->mark_youtube_stream_ended($post_id, $behavior);
        }
    }

    private function mark_youtube_stream_ended($post_id, $behavior) {
        $status = in_array($behavior, array('convert_replay_embed', 'keep_replay'), true) ? 'replay' : 'ended';
        $embed_code = get_post_meta($post_id, '_vh360_embed_code', true);

        update_post_meta($post_id, '_vh360_is_live', 'no');
        update_post_meta($post_id, '_vh360_stream_stopped', 'no');
        update_post_meta($post_id, '_vh360_live_badge', 'no');
        update_post_meta($post_id, '_vh360_youtube_status', $status);
        update_post_meta($post_id, '_vh360_youtube_ended_at', current_time('mysql'));
        update_post_meta($post_id, '_vh360_youtube_ended_ts', time());

        if (!empty($embed_code)) {
            update_post_meta($post_id, 'videohub360_custom_html', $embed_code);
        }
    }

    private function assign_category($post_id, $window) {
        $term_id = !empty($window['schedule']['category']) ? absint($window['schedule']['category']) : absint(get_option('vh360_youtube_default_category', 0));
        if ($term_id) wp_set_object_terms($post_id, array($term_id), 'videohub360_category', false);
    }

    private function get_active_window() {
        if ('always' === get_option('vh360_youtube_detection_mode', 'scheduled')) return array('inside'=>true,'phase'=>'live','schedule'=>array());

        $timezone = wp_timezone();
        $now = new DateTimeImmutable('now', $timezone);
        $now_ts = $now->getTimestamp();
        $candidate_days = array(
            $now->modify('-1 day'),
            $now,
            $now->modify('+1 day'),
        );
        $active_matches = array();

        foreach (self::sanitize_schedules(get_option('vh360_youtube_schedules', array())) as $schedule) {
            if (empty($schedule['enabled'])) continue;

            list($hour, $minute) = array_map('intval', explode(':', $schedule['start_time']));

            foreach ($candidate_days as $candidate_day) {
                if ($schedule['day'] !== strtolower($candidate_day->format('l'))) continue;

                $start = $candidate_day->setTime($hour, $minute, 0);
                $start_ts = $start->getTimestamp();
                $from_ts = $start_ts - $schedule['precheck_minutes'] * MINUTE_IN_SECONDS;
                $to_ts = $start_ts + ($schedule['expected_duration_minutes'] + $schedule['grace_minutes']) * MINUTE_IN_SECONDS;

                if ($now_ts >= $from_ts && $now_ts <= $to_ts) {
                    $phase = $now_ts < $start_ts ? 'precheck' : 'live';
                    $active_matches[] = array(
                        'phase' => $phase,
                        'schedule' => $schedule,
                        'start_delta' => abs($start_ts - $now_ts),
                    );
                }
            }
        }

        if (!empty($active_matches)) {
            usort($active_matches, function($a, $b) {
                if ($a['phase'] !== $b['phase']) {
                    return 'live' === $a['phase'] ? -1 : 1;
                }
                return $a['start_delta'] <=> $b['start_delta'];
            });

            return array('inside'=>true,'phase'=>$active_matches[0]['phase'],'schedule'=>$active_matches[0]['schedule']);
        }

        return array('inside'=>false,'phase'=>'none','schedule'=>array());
    }

    private function get_local_mysql_timestamp($mysql_datetime) {
        if (empty($mysql_datetime)) return 0;

        try {
            $date = new DateTimeImmutable($mysql_datetime, wp_timezone());
            return $date->getTimestamp();
        } catch (Exception $e) {
            return 0;
        }
    }

    private function status($result, $video_id, $post_id, $featured_image_result, $error) {
        update_option('vh360_youtube_last_result', sanitize_text_field($result), false);
        update_option('vh360_youtube_last_error', sanitize_text_field($error), false);
        update_option('vh360_youtube_last_detected_video_id', sanitize_text_field($video_id ?: ''), false);
        return array('checked'=>true,'active_live_found'=>('success' === $result),'video_id'=>$video_id ?: '','post_id'=>absint($post_id),'featured_image_result'=>$featured_image_result,'result'=>$result,'error_message'=>$error);
    }

    public static function sanitize_schedules($schedules) {
        if (!is_array($schedules)) return array();
        $clean = array();
        foreach ($schedules as $schedule) {
            $day = isset($schedule['day']) ? strtolower(sanitize_text_field($schedule['day'])) : 'sunday';
            $time = isset($schedule['start_time']) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $schedule['start_time']) ? $schedule['start_time'] : '10:00';
            $allowed_days = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
            if (!in_array($day, $allowed_days, true)) $day = 'sunday';
            $clean[] = array(
                'enabled' => empty($schedule['enabled']) ? 0 : 1,
                'day' => $day,
                'start_time' => $time,
                'expected_duration_minutes' => max(1, min(720, absint($schedule['expected_duration_minutes'] ?? get_option('vh360_youtube_expected_duration_minutes', 120)))),
                'precheck_minutes' => max(0, min(240, absint($schedule['precheck_minutes'] ?? get_option('vh360_youtube_precheck_minutes', 30)))),
                'grace_minutes' => max(1, min(240, absint($schedule['grace_minutes'] ?? get_option('vh360_youtube_grace_minutes', 20)))),
                'title_prefix' => sanitize_text_field($schedule['title_prefix'] ?? ''),
                'category' => absint($schedule['category'] ?? 0),
            );
        }
        return $clean;
    }

    private function sanitize_choice($value, $allowed, $default) {
        $value = sanitize_key($value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function sanitize_option_choice($value, $allowed, $default) {
        $value = sanitize_key($value);
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
