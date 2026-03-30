<?php
/**
 * WordPress Importer Bootstrap for Starter Sites
 * 
 * Bundled version of WordPress Importer v0.9.5
 * 
 * This file loads the WordPress Importer classes for use with the Starter Sites plugin.
 * Based on the official WordPress Importer plugin:
 * https://wordpress.org/plugins/wordpress-importer/
 * 
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 * @version 0.9.5
 * 
 * Original WordPress Importer credits:
 * - Plugin URI: https://wordpress.org/plugins/wordpress-importer/
 * - Author: wordpressdotorg
 * - License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Display verbose errors */
if (!defined('IMPORT_DEBUG')) {
    define('IMPORT_DEBUG', false);
}

/** WordPress Import Administration API */
if (!function_exists('get_file_description')) {
    $file_path = ABSPATH . 'wp-admin/includes/file.php';
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

if (!function_exists('wp_importers')) {
    $import_path = ABSPATH . 'wp-admin/includes/import.php';
    if (file_exists($import_path)) {
        require_once $import_path;
    }
}

/** Load WP_Importer base class if not already available */
if (!class_exists('WP_Importer')) {
    $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
    if (file_exists($class_wp_importer)) {
        require_once $class_wp_importer;
    }
}

/** Functions missing in older WordPress versions */
require_once __DIR__ . '/compat.php';

/** Load PHP XML Toolkit if needed */
if (!class_exists('WordPress\XML\XMLProcessor')) {
    require_once __DIR__ . '/php-toolkit/load.php';
}

/** WXR_Parser class - Base parser interface */
require_once __DIR__ . '/parsers/class-wxr-parser.php';

/** WXR_Parser_SimpleXML class - SimpleXML-based parser */
require_once __DIR__ . '/parsers/class-wxr-parser-simplexml.php';

/** WXR_Parser_XML class - XML Reader-based parser */
require_once __DIR__ . '/parsers/class-wxr-parser-xml.php';

/**
 * WXR_Parser_Regex class - Legacy regex-based parser
 * @deprecated 0.9.0 Use WXR_Parser_XML_Processor instead
 */
require_once __DIR__ . '/parsers/class-wxr-parser-regex.php';

/** WXR_Parser_XML_Processor class - Modern XML processor parser */
require_once __DIR__ . '/parsers/class-wxr-parser-xml-processor.php';

/** WP_Import class - Main importer class */
require_once __DIR__ . '/class-wp-import.php';

/**
 * The WP_Import class is now available for use.
 * 
 * Usage example:
 * 
 * $importer = new WP_Import();
 * $importer->fetch_attachments = true;
 * $result = $importer->import($file_path);
 */
