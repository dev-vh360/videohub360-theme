<?php
/**
 * Members Directory Implementation Verification Script
 * 
 * Tests the new audience filtering implementation.
 * Run from command line from theme root: php tools/verify-members-directory.php
 * 
 * Note: Requires WordPress to be installed in ../../../ relative to theme directory.
 * If your installation differs, adjust the path to wp-load.php accordingly.
 */

// Attempt to load WordPress - adjust path if needed for your installation
$wp_load_path = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    echo "ERROR: Cannot find wp-load.php at expected path: $wp_load_path\n";
    echo "Please adjust the path in this script to match your WordPress installation.\n";
    exit(1);
}
require_once($wp_load_path);

echo "====================================\n";
echo "Members Directory Verification Test\n";
echo "====================================\n\n";

// Test 1: Check that new settings exist
echo "TEST 1: New Settings Schema\n";
echo "----------------------------\n";
$options = wp_parse_args(
    get_option('vh360_members_options', array()),
    vh360_get_default_members_directory_options()
);
$required_keys = array('per_page', 'directory_audience', 'professionals_account_types', 'professionals_require_approval', 'show_card_stats');
foreach ($required_keys as $key) {
    $exists = array_key_exists($key, $options);
    $value = $exists ? var_export($options[$key], true) : 'NOT SET';
    echo sprintf("✓ %s: %s\n", $key, $value);
}
echo "\n";

// Test 2: Test effective mode resolver with global settings only
echo "TEST 2: Effective Mode Resolver (Global)\n";
echo "-----------------------------------------\n";
$mode = vh360_get_members_directory_effective_mode(0);
echo "Audience: " . $mode['audience'] . "\n";
echo "Account Types: " . implode(', ', $mode['professionals_account_types']) . "\n";
echo "Require Approval: " . ($mode['professionals_require_approval'] ? 'true' : 'false') . "\n";
echo "Show Card Stats: " . ($mode['show_card_stats'] ? 'true' : 'false') . "\n";
echo "Source: " . $mode['source'] . "\n\n";

// Test 3: Test query builder for all_members mode
echo "TEST 3: Query Builder - All Members Mode\n";
echo "-----------------------------------------\n";
$args = vh360_build_members_directory_query_args(array(
    'audience' => 'all_members',
    'number' => 5,
));
echo "Query args keys: " . implode(', ', array_keys($args)) . "\n";
if (isset($args['role__in'])) {
    echo "Role filter: role__in = " . implode(', ', $args['role__in']) . "\n";
} else {
    echo "Role filter: none\n";
}
echo "\n";

// Test 4: Test query builder for professionals_only mode
echo "TEST 4: Query Builder - Professionals Only Mode\n";
echo "------------------------------------------------\n";
$args = vh360_build_members_directory_query_args(array(
    'audience' => 'professionals_only',
    'account_types' => vh360_get_professionals_directory_account_types(),
    'require_professional_approval' => true,
    'number' => 5,
));
echo "Query args keys: " . implode(', ', array_keys($args)) . "\n";
if (isset($args['meta_query'])) {
    echo "Meta query present: YES\n";
    echo "Meta query conditions: " . count($args['meta_query']) - 1 . " (excluding 'relation')\n";
} else {
    echo "Meta query present: NO\n";
}
echo "\n";

// Test 5: Test vh360_get_members with professionals_only
echo "TEST 5: Get Members - Professionals Only\n";
echo "-----------------------------------------\n";
$members = vh360_get_members(array(
    'audience' => 'professionals_only',
    'account_types' => vh360_get_professionals_directory_account_types(),
    'require_professional_approval' => true,
    'number' => 3,
));
echo "Members returned: " . count($members) . "\n";
if (!empty($members)) {
    foreach ($members as $i => $member) {
        $account_type = get_user_meta($member->ID, '_vh360_account_type', true);
        $status = get_user_meta($member->ID, '_vh360_professional_status', true);
        echo sprintf("  %d. %s (ID: %d, Type: %s, Status: %s)\n", 
            $i + 1, 
            $member->display_name, 
            $member->ID, 
            $account_type ?: 'none', 
            $status ?: 'none'
        );
    }
}
echo "\n";

// Test 6: Test vh360_get_member_count with professionals_only
echo "TEST 6: Get Member Count - Professionals Only\n";
echo "----------------------------------------------\n";
$count = vh360_get_member_count(array(
    'audience' => 'professionals_only',
    'account_types' => vh360_get_professionals_directory_account_types(),
    'require_professional_approval' => true,
));
echo "Total professionals count: " . $count . "\n\n";

// Test 7: Test backwards compatibility of vh360_get_member_count with string arg
echo "TEST 7: Backwards Compatibility - vh360_get_member_count(string)\n";
echo "-----------------------------------------------------------------\n";
$count = vh360_get_member_count('subscriber');
echo "vh360_get_member_count('subscriber'): " . $count . "\n";
$count = vh360_get_member_count('');
echo "vh360_get_member_count(''): " . $count . "\n\n";

// Test 8: Find a page with members directory template for page override testing
echo "TEST 8: Page Override Testing\n";
echo "-----------------------------\n";
$pages = get_pages(array(
    'meta_key' => '_wp_page_template',
    'meta_value' => 'template-members-directory.php',
    'number' => 1,
));
if (!empty($pages)) {
    $page = $pages[0];
    echo "Found Members Directory page: " . $page->post_title . " (ID: " . $page->ID . ")\n";
    
    // Test effective mode for this page
    $page_mode = vh360_get_members_directory_effective_mode($page->ID);
    echo "Page Mode:\n";
    echo "  Audience: " . $page_mode['audience'] . "\n";
    echo "  Show Card Stats: " . ($page_mode['show_card_stats'] ? 'true' : 'false') . "\n";
    echo "  Source: " . $page_mode['source'] . "\n";
} else {
    echo "No Members Directory pages found. Skipping page override test.\n";
}
echo "\n";

// Test 9: Test that invalid page IDs fall back to global
echo "TEST 9: Security - Invalid Page ID Fallback\n";
echo "--------------------------------------------\n";
$invalid_mode = vh360_get_members_directory_effective_mode(999999);
echo "Invalid page ID (999999) falls back to global: " . $invalid_mode['source'] . "\n";
echo "Audience: " . $invalid_mode['audience'] . "\n\n";

echo "====================================\n";
echo "All Tests Completed\n";
echo "====================================\n";
