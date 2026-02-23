<?php
/**
 * Account Type Helpers
 *
 * Provides helper functions to determine user account types and appropriate
 * display modes for author pages based on account types.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get user account type
 * 
 * Reads the _vh360_account_type user meta and validates against whitelist.
 * Valid values: creator, professional, client, organization
 * 
 * @param int $user_id User ID
 * @return string Account type (defaults to 'creator' if missing/invalid)
 */
function vh360_get_user_account_type($user_id) {
    if (!$user_id) {
        return 'creator';
    }
    
    $account_type = get_user_meta($user_id, '_vh360_account_type', true);
    
    // Whitelist of valid account types
    $valid_types = array('creator', 'professional', 'client', 'organization');
    
    // Return validated type or default to creator
    if ($account_type && in_array($account_type, $valid_types, true)) {
        return $account_type;
    }
    
    return 'creator';
}

/**
 * Get author display mode based on account type
 * 
 * Determines which author template should be displayed based on the user's account type:
 * - professional/organization → business
 * - client → client
 * - creator → respects existing site-wide Profile/Channel customizer setting
 * 
 * @param int $author_id Author user ID
 * @return string Display mode: 'business', 'client', 'channel', or 'profile'
 */
function vh360_get_author_display_mode($author_id) {
    if (!$author_id) {
        return 'profile';
    }
    
    $account_type = vh360_get_user_account_type($author_id);
    
    // Route based on account type
    switch ($account_type) {
        case 'professional':
        case 'organization':
            return 'business';
            
        case 'client':
            return 'client';
            
        case 'creator':
        default:
            // For creators, use existing site-wide customizer setting
            if (function_exists('vh360_get_author_template_mode')) {
                return vh360_get_author_template_mode();
            }
            return 'profile';
    }
}
