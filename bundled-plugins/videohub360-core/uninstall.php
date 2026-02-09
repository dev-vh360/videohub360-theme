<?php
/**
 * VideoHub360 Plugin Uninstall Script
 * 
 * This file is called when the plugin is uninstalled.
 * 
 * IMPORTANT: This is a stub file for marketplace compliance.
 * The cleanup code has been intentionally left as comments/reminders
 * for the site administrator to implement based on their specific needs.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/*
 * CLEANUP IMPLEMENTATION:
 *
 * The following code originally deleted all VideoHub360 data on uninstallation.
 * Deleting user data without confirmation is against WordPress and CodeCanyon
 * guidelines, so data removal now only occurs when the site owner has
 * explicitly opted in via a setting.  To enable automatic data deletion on
 * uninstall, set the option `videohub360_delete_on_uninstall` to `yes` in
 * your plugin settings (e.g., via a settings page) before deactivating.
 */

// Check if the site owner has opted in to delete data on uninstall.
$delete_on_uninstall = get_option('videohub360_delete_on_uninstall', false);

if ($delete_on_uninstall) {
    // Remove custom post type data
    $posts = get_posts(array('post_type' => 'videohub360', 'numberposts' => -1));
    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }

    // Remove custom taxonomies
    $taxonomies = array('videohub360_category', 'videohub360_series', 'videohub360_location');
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }

    // Remove plugin options
    delete_option('videohub360_migration_complete');
    delete_option('videohub360_chat_message_limit');
    delete_option('videohub360_global_ad_enabled');
    delete_option('videohub360_global_ad_code');
    delete_option('videohub360_chat_enabled');
    delete_option('videohub360_category_filter_label');
    delete_option('videohub360_series_filter_label');
    delete_option('videohub360_location_filter_label');
    // Remove the flag so deletion does not persist after reinstall
    delete_option('videohub360_delete_on_uninstall');

    // Remove user meta data (if any)
    delete_metadata('user', 0, 'videohub360_user_preference', '', true);

    // Remove post meta data (this may already be handled by post deletion above)
    delete_metadata('post', 0, '_vh360_type', '', true);
    delete_metadata('post', 0, '_vh360_embed_code', '', true);
    delete_metadata('post', 0, '_vh360_stream_url', '', true);
    delete_metadata('post', 0, '_vh360_api_url', '', true);
    delete_metadata('post', 0, '_vh360_poster', '', true);
    delete_metadata('post', 0, '_vh360_viewer_count', '', true);
    delete_metadata('post', 0, '_vh360_live_badge', '', true);
    delete_metadata('post', 0, '_vh360_badge_text', '', true);
    delete_metadata('post', 0, '_vh360_badge_color', '', true);
    delete_metadata('post', 0, '_vh360_is_live', '', true);
    delete_metadata('post', 0, '_vh360_offline_message', '', true);
    delete_metadata('post', 0, '_vh360_live_start_time', '', true);
    delete_metadata('post', 0, '_vh360_agora_app_id', '', true);
    delete_option('vh360_agora_app_id');
    delete_option('vh360_agora_app_certificate');
    delete_metadata('post', 0, '_vh360_agora_channel_name', '', true);
    delete_metadata('post', 0, '_vh360_agora_token', '', true);
    delete_metadata('post', 0, '_vh360_agora_mode', '', true);
    delete_metadata('post', 0, '_vh360_chat_enabled', '', true);
    delete_metadata('post', 0, '_vh360_views', '', true);

    // Remove custom database tables (if any were created)
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}videohub360_chat");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}videohub360_views");

    // Clear any cached data
    wp_cache_flush();
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
}

/*
 * IMPLEMENTATION NOTES:
 * 
 * 1. Data Retention: Consider whether users want to keep their video data
 *    for potential plugin reactivation or migration to another system.
 * 
 * 2. Backup Reminder: Always recommend users create backups before uninstalling.
 * 
 * 3. Selective Cleanup: Consider providing options for partial cleanup
 *    (e.g., keep posts but remove plugin-specific data).
 * 
 * 4. Third-party Data: This uninstall script does not affect external services
 *    like Agora accounts, video hosting platforms, etc.
 * 
 * 5. Multisite Considerations: If supporting multisite, consider network-wide
 *    vs site-specific cleanup requirements.
 * 
 * To implement cleanup:
 * 1. Uncomment the relevant sections above
 * 2. Test thoroughly on a staging site first
 * 3. Modify the code to match your specific data cleanup requirements
 * 4. Consider adding user confirmation mechanisms
 */