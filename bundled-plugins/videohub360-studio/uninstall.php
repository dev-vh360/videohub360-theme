<?php
/**
 * Uninstall cleanup for VH360 Studio.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'vh360_studio_db_version' );
