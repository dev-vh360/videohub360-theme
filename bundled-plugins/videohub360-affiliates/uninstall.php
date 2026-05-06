<?php
/**
 * Uninstall handler for VideoHub360 Affiliates.
 *
 * Called by WordPress when the plugin is deleted from the admin UI.
 * Removes all plugin options. Tables are NOT dropped here because they
 * hold financial/audit records that should be preserved unless the site
 * owner explicitly removes them.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('vh360_affiliates_settings');
delete_option('vh360_affiliates_db_version');
