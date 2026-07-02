<?php
/**
 * Uninstall cleanup for VH360 Studio.
 *
 * Recording job rows and replay/upload references are intentionally preserved by
 * default so site owners do not lose recording history, attachment links,
 * VideoPress GUIDs, Publitio IDs, or playback metadata by removing the plugin.
 * A future full-data-removal setting/filter can opt into dropping the table.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'vh360_studio_db_version' );
