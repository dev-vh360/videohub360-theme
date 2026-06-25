<?php
/**
 * Video ad helper functions.
 *
 * @package VideoHub360
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper function to validate if an ad URL is properly configured
if (!function_exists('videohub360_has_valid_ad_url')) {
    function videohub360_has_valid_ad_url($url) {
        return !empty($url) && is_string($url) && trim($url) !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

// Helper function to determine which ad types should be active for the current video
if (!function_exists('videohub360_get_active_ads')) {
    function videohub360_get_active_ads($post_id) {
        $active_ads = array();

        // Check preroll ad
        $preroll_url = get_post_meta($post_id, 'ad_video_url', true);
        if (empty($preroll_url)) {
            $preroll_url = get_option('videohub360_global_ad_url', '');
        }
        if (videohub360_has_valid_ad_url($preroll_url)) {
            $active_ads['preroll'] = $preroll_url;
        }

        // Check midroll ad
        $midroll_url = get_post_meta($post_id, 'midroll_ad_video_url', true);
        if (empty($midroll_url)) {
            $midroll_url = get_option('videohub360_global_midroll_ad_url', '');
        }
        if (videohub360_has_valid_ad_url($midroll_url)) {
            $active_ads['midroll'] = $midroll_url;

            // Also get timing for midroll ads
            $midroll_timing = get_post_meta($post_id, 'midroll_ad_timing', true);
            if (empty($midroll_timing)) {
                $midroll_timing = get_option('videohub360_global_midroll_timing', '30,60,120');
            }
            $active_ads['midroll_timing'] = $midroll_timing;
        }

        // Check postroll ad
        $postroll_url = get_post_meta($post_id, 'postroll_ad_video_url', true);
        $postroll_enabled_meta = get_post_meta($post_id, 'postroll_ad_enabled', true);

        // Determine which URL to use: per-video or global
        $using_per_video_url = !empty($postroll_url);
        if (!$using_per_video_url) {
            // No per-video URL, fall back to global
            $postroll_url = get_option('videohub360_global_postroll_ad_url', '');
        }

        if (videohub360_has_valid_ad_url($postroll_url)) {
            // Determine enabled status
            if ($using_per_video_url) {
                // Has per-video URL - check per-video enabled setting (with global fallback)
                if (empty($postroll_enabled_meta)) {
                    // No explicit per-video choice, use global enabled setting
                    $postroll_enabled = get_option('videohub360_global_postroll_enabled', 0) ? 'yes' : 'no';
                } else {
                    // Use explicit per-video choice
                    $postroll_enabled = $postroll_enabled_meta;
                }
            } else {
                // Using global URL - use global enabled setting
                // (per-video enabled setting doesn't apply when there's no per-video URL)
                $postroll_enabled = get_option('videohub360_global_postroll_enabled', 0) ? 'yes' : 'no';
            }

            // Only add postroll if it's enabled
            if ($postroll_enabled === 'yes') {
                $active_ads['postroll'] = $postroll_url;
                $active_ads['postroll_enabled'] = $postroll_enabled;
            }
        }

        return $active_ads;
    }
}

// Helper function to get ad click-through URLs with proper hierarchy
if (!function_exists('videohub360_get_ad_click_urls')) {
    function videohub360_get_ad_click_urls($post_id) {
        $click_urls = array();

        // Get global settings
        $global_click_url = get_option('vh360_global_ad_click_url', '');
        $click_tracking_enabled = get_option('vh360_ad_click_tracking_enabled', 0);
        $click_new_tab = get_option('vh360_ad_click_new_tab', 1);

        // Get per-video click URLs
        $preroll_click_url = get_post_meta($post_id, '_vh360_ad_click_url', true);
        $midroll_click_url = get_post_meta($post_id, '_vh360_midroll_ad_click_url', true);
        $postroll_click_url = get_post_meta($post_id, '_vh360_postroll_ad_click_url', true);

        // Determine effective click URLs using hierarchy:
        // 1. Per-video URL (if set)
        // 2. Global URL (if set)
        // 3. Empty (non-clickable)

        // Preroll click URL
        $click_urls['preroll'] = !empty($preroll_click_url) ? $preroll_click_url : $global_click_url;

        // Midroll click URL (falls back to preroll, then global)
        if (!empty($midroll_click_url)) {
            $click_urls['midroll'] = $midroll_click_url;
        } elseif (!empty($preroll_click_url)) {
            $click_urls['midroll'] = $preroll_click_url;
        } else {
            $click_urls['midroll'] = $global_click_url;
        }

        // Postroll click URL (falls back to preroll, then global)
        if (!empty($postroll_click_url)) {
            $click_urls['postroll'] = $postroll_click_url;
        } elseif (!empty($preroll_click_url)) {
            $click_urls['postroll'] = $preroll_click_url;
        } else {
            $click_urls['postroll'] = $global_click_url;
        }

        // Add global settings
        $click_urls['tracking_enabled'] = $click_tracking_enabled;
        $click_urls['new_tab'] = $click_new_tab;

        return $click_urls;
    }
}
