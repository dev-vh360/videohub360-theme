<?php
/**
 * Server-authoritative Agora participant identity registry.
 *
 * @package VideoHub360
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Agora_Participant_Registry {
    const TABLE = 'vh360_agora_participants';
    const CLEANUP_INTERVAL = 300;

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    public static function session_key($post_id, $channel_name, $agora_uid) {
        return hash('sha256', absint($post_id) . '|' . sanitize_text_field($channel_name) . '|' . absint($agora_uid));
    }

    public static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_key varchar(64) NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            channel_name varchar(191) NOT NULL,
            channel_hash varchar(64) NOT NULL,
            agora_uid bigint(20) unsigned NOT NULL,
            wordpress_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            guest_identifier varchar(191) NOT NULL DEFAULT '',
            display_name varchar(191) NOT NULL DEFAULT '',
            avatar_url text NULL,
            is_guest tinyint(1) NOT NULL DEFAULT 0,
            is_studio_host tinyint(1) NOT NULL DEFAULT 0,
            is_original_host tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            last_seen_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_uid (session_key, agora_uid),
            KEY post_channel_uid (post_id, channel_hash, agora_uid),
            KEY wordpress_user_id (wordpress_user_id),
            KEY is_original_host (is_original_host),
            KEY expires_at (expires_at)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    public static function cleanup_expired($force = false) {
        $last = (int) get_transient('vh360_agora_participant_cleanup_last');
        if (!$force && $last && (time() - $last) < self::CLEANUP_INTERVAL) return;
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table_name() . ' WHERE expires_at < %s', current_time('mysql', true)));
        set_transient('vh360_agora_participant_cleanup_last', time(), self::CLEANUP_INTERVAL);
    }

    public static function register($args) {
        global $wpdb;
        self::cleanup_expired();
        $post_id = absint($args['post_id'] ?? 0);
        $channel = sanitize_text_field($args['channel_name'] ?? '');
        $uid = absint($args['agora_uid'] ?? 0);
        if (!$post_id || '' === $channel || !$uid) return false;
        $user_id = absint($args['wordpress_user_id'] ?? 0);
        $now = current_time('mysql', true);
        $lifetime = absint($args['lifetime'] ?? (12 * HOUR_IN_SECONDS));
        $expires = gmdate('Y-m-d H:i:s', current_time('timestamp', true) + max(HOUR_IN_SECONDS, $lifetime) + HOUR_IN_SECONDS);
        $data = array(
            'session_key' => self::session_key($post_id, $channel, $uid),
            'post_id' => $post_id,
            'channel_name' => $channel,
            'channel_hash' => hash('sha256', $channel),
            'agora_uid' => $uid,
            'wordpress_user_id' => $user_id,
            'guest_identifier' => sanitize_text_field($args['guest_identifier'] ?? ''),
            'display_name' => sanitize_text_field($args['display_name'] ?? ($user_id ? get_the_author_meta('display_name', $user_id) : __('Guest', 'videohub360'))),
            'avatar_url' => esc_url_raw($args['avatar_url'] ?? ($user_id ? get_avatar_url($user_id) : '')),
            'is_guest' => empty($user_id) ? 1 : absint($args['is_guest'] ?? 0),
            'is_studio_host' => absint($args['is_studio_host'] ?? 0),
            'is_original_host' => absint($args['is_original_host'] ?? 0),
            'created_at' => $now,
            'last_seen_at' => $now,
            'expires_at' => $expires,
        );
        $existing = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . self::table_name() . ' WHERE session_key = %s AND agora_uid = %d', $data['session_key'], $uid));
        if ($existing) {
            unset($data['created_at']);
            return false !== $wpdb->update(self::table_name(), $data, array('id' => absint($existing)));
        }
        return false !== $wpdb->insert(self::table_name(), $data);
    }

    public static function get_identities($post_id, $channel_name, $uids, $include_user_id = false) {
        global $wpdb;
        self::cleanup_expired();
        $post_id = absint($post_id);
        $channel_hash = hash('sha256', sanitize_text_field($channel_name));
        $uids = array_values(array_unique(array_filter(array_map('absint', (array) $uids))));
        if (!$post_id || empty($uids)) return array();
        $placeholders = implode(',', array_fill(0, count($uids), '%d'));
        $params = array_merge(array($post_id, $channel_hash, current_time('mysql', true)), $uids);
        $rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table_name() . " WHERE post_id = %d AND channel_hash = %s AND expires_at >= %s AND agora_uid IN ({$placeholders})", $params), ARRAY_A);
        $out = array();
        foreach ((array) $rows as $row) {
            $out[(string) $row['agora_uid']] = self::format_identity($row, $include_user_id);
        }
        return $out;
    }

    public static function format_identity($row, $include_user_id = false) {
        $identity = array(
            'uid' => absint($row['agora_uid']),
            'display_name' => sanitize_text_field($row['display_name'] ?: __('Participant', 'videohub360')),
            'avatar_url' => esc_url_raw($row['avatar_url'] ?? ''),
            'is_guest' => !empty($row['is_guest']),
            'is_studio_host' => !empty($row['is_studio_host']),
            'is_original_host' => !empty($row['is_original_host']),
            'source' => 'registry',
        );
        if ($include_user_id) $identity['wordpress_user_id'] = absint($row['wordpress_user_id']);
        return $identity;
    }
}
