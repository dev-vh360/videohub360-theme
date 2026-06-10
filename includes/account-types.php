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
 * - professional/organization → course when Course Mode is active, otherwise business
 * - client → course when Course Mode is active, otherwise client
 * - creator → respects existing site-wide Profile/Channel/Course customizer setting
 * 
 * @param int $author_id Author user ID
 * @return string Display mode: 'business', 'client', 'course', 'channel', or 'profile'
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
            if (function_exists('vh360_get_author_template_mode') && 'course' === vh360_get_author_template_mode()) {
                return 'course';
            }

            return 'business';
            
        case 'client':
            if (function_exists('vh360_get_author_template_mode') && 'course' === vh360_get_author_template_mode()) {
                return 'course';
            }

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

/**
 * Check if a professional user is approved
 * 
 * Determines if a professional has been approved to access professional features.
 * Admins are always considered approved. For others, checks _vh360_professional_status meta.
 * 
 * @param int $user_id User ID to check
 * @return bool True if approved (or admin), false if pending/rejected or not professional
 */
function vh360_is_professional_approved($user_id) {
    if (!$user_id) {
        return false;
    }
    
    // Admins are always approved
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Check if user is a professional
    $account_type = vh360_get_user_account_type($user_id);
    if ($account_type !== 'professional') {
        return true; // Non-professionals don't need approval
    }
    
    // Check professional status
    $status = get_user_meta($user_id, '_vh360_professional_status', true);
    
    // If no status is set, assume approved (for backwards compatibility with existing accounts)
    if (empty($status)) {
        return true;
    }
    
    return ($status === 'approved');
}
