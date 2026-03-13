<?php
/**
 * Permission Helper Functions
 *
 * Centralized permission check functions for consistent enforcement
 * across templates and AJAX handlers.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user can create events.
 *
 * @param int $user_id User ID. Defaults to current user.
 * @return bool True if user can create events.
 */
function vh360_user_can_create_events($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $can_create = user_can($user_id, 'vh360_create_events') || user_can($user_id, 'manage_options');
    
    return apply_filters('vh360_user_can_create_events', $can_create, $user_id);
}

/**
 * Check if user can create bulletins.
 *
 * @param int $user_id User ID. Defaults to current user.
 * @return bool True if user can create bulletins.
 */
function vh360_user_can_create_bulletins($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $can_create = user_can($user_id, 'vh360_create_bulletins') || user_can($user_id, 'manage_options');
    
    return apply_filters('vh360_user_can_create_bulletins', $can_create, $user_id);
}

/**
 * Check if user can edit a specific bulletin.
 * Admins can edit any bulletin. Otherwise, user must have bulletin create permission AND be the author.
 *
 * @param int $bulletin_id Bulletin post ID.
 * @param int $user_id User ID. Defaults to current user.
 * @return bool True if user can edit the bulletin.
 */
function vh360_user_can_edit_bulletin($bulletin_id, $user_id = 0) {
    $bulletin_id = (int) $bulletin_id;

    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $user_id = (int) $user_id;

    if (!$user_id || !$bulletin_id) {
        return false;
    }

    // Admin override
    if (user_can($user_id, 'manage_options')) {
        return true;
    }

    // Must have bulletin permission
    if (!vh360_user_can_create_bulletins($user_id)) {
        return false;
    }

    $post = get_post($bulletin_id);
    if (!$post || $post->post_type !== 'vh360_bulletin') {
        return false;
    }

    // Must be author
    return ((int) $post->post_author === $user_id);
}

/**
 * Check if user can create galleries.
 *
 * @param int $user_id User ID. Defaults to current user.
 * @return bool True if user can create galleries.
 */
function vh360_user_can_create_galleries($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $can_create = user_can($user_id, 'create_vh360_galleries') || user_can($user_id, 'manage_options');
    
    return apply_filters('vh360_user_can_create_galleries', $can_create, $user_id);
}

/**
 * Check if user can publish galleries.
 *
 * @param int $user_id User ID. Defaults to current user.
 * @return bool True if user can publish galleries.
 */
function vh360_user_can_publish_galleries($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $can_publish = user_can($user_id, 'publish_vh360_galleries') || user_can($user_id, 'manage_options');
    
    return apply_filters('vh360_user_can_publish_galleries', $can_publish, $user_id);
}

/**
 * Check if user can manage a specific dashboard blog post.
 * 
 * Determines if the specified user is allowed to edit or delete a blog post
 * from the frontend dashboard. This is ownership-based for business-mode users
 * (professional/organization) and admin-override capable.
 * 
 * @param int $post_id Post ID to check.
 * @param int $user_id User ID. Defaults to current user.
 * @return bool True if user can manage the dashboard post.
 */
function vh360_user_can_manage_dashboard_post($post_id, $user_id = 0) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();
    $post    = get_post($post_id);

    if (!$user_id || !$post || $post->post_type !== 'post') {
        return false;
    }

    // Allow administrators
    if (user_can($user_id, 'manage_options')) {
        return true;
    }

    // Must be post author
    if ((int) $post->post_author !== (int) $user_id) {
        return false;
    }

    // Check account type
    $account_type = function_exists('vh360_get_user_account_type')
        ? vh360_get_user_account_type($user_id)
        : get_user_meta($user_id, '_vh360_account_type', true);

    if (!in_array($account_type, array('professional', 'organization'), true)) {
        return false;
    }

    return apply_filters('vh360_user_can_manage_dashboard_post', true, $post_id, $user_id, $post);
}
