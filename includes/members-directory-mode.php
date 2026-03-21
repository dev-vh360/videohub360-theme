<?php
/**
 * Members Directory Mode Resolver
 *
 * Provides functions to determine the effective directory mode for any given page,
 * combining global settings with page-level overrides. This is the single source of
 * truth for directory audience configuration.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get the effective members directory mode for a page
 * 
 * Resolves the directory configuration by checking page-level overrides first,
 * then falling back to global settings. Returns a complete configuration array
 * including audience, account types, and approval requirements.
 * 
 * @param int $page_id Optional page ID. If not provided, uses current queried object.
 * @return array Configuration array with keys:
 *   - audience: 'all_members' or 'professionals_only'
 *   - professionals_account_types: array of account type strings
 *   - professionals_require_approval: boolean
 *   - show_card_stats: boolean
 *   - source: 'page_override' or 'global'
 */
function vh360_get_members_directory_effective_mode($page_id = 0) {
    // Determine page ID
    if (!$page_id && function_exists('get_queried_object_id')) {
        $page_id = get_queried_object_id();
    }
    
    // Get global settings
    $global_options = get_option('vh360_members_options', array());
    $defaults = array(
        'directory_audience' => 'all_members',
        'professionals_account_types' => array('professional', 'organization'),
        'professionals_require_approval' => true,
        'show_card_stats' => true,
        'show_card_follow_button' => true,
    );
    $global_options = wp_parse_args($global_options, $defaults);
    
    // Start with global settings
    $mode = array(
        'audience' => $global_options['directory_audience'],
        'professionals_account_types' => $global_options['professionals_account_types'],
        'professionals_require_approval' => $global_options['professionals_require_approval'],
        'show_card_stats' => $global_options['show_card_stats'],
        'show_card_follow_button' => $global_options['show_card_follow_button'],
        'source' => 'global',
    );
    
    // Check for page-level overrides if we have a valid page ID
    if ($page_id > 0) {
        // Verify this page uses the members directory template
        $template = get_page_template_slug($page_id);
        if ($template === 'template-members-directory.php') {
            // Check audience override
            $audience_override = get_post_meta($page_id, '_vh360_members_directory_audience_override', true);
            if ($audience_override && $audience_override !== 'inherit') {
                $allowed_audiences = array('all_members', 'professionals_only');
                if (in_array($audience_override, $allowed_audiences, true)) {
                    $mode['audience'] = $audience_override;
                    $mode['source'] = 'page_override';
                }
            }
            
            // Check approval override
            $approval_override = get_post_meta($page_id, '_vh360_members_directory_require_approval_override', true);
            if ($approval_override !== '' && $approval_override !== 'inherit') {
                // Meta value is stored as string '1' or '0'
                $mode['professionals_require_approval'] = ($approval_override === '1');
                $mode['source'] = 'page_override';
            }
            
            // Check show_card_stats override
            $show_card_stats_override = get_post_meta($page_id, '_vh360_members_directory_show_card_stats_override', true);
            if ($show_card_stats_override !== '' && $show_card_stats_override !== 'inherit') {
                // Meta value is stored as string '1' or '0'
                $mode['show_card_stats'] = ($show_card_stats_override === '1');
                $mode['source'] = 'page_override';
            }
            
            // Check show_card_follow_button override
            $show_card_follow_button_override = get_post_meta($page_id, '_vh360_members_directory_show_card_follow_button_override', true);
            if ($show_card_follow_button_override !== '' && $show_card_follow_button_override !== 'inherit') {
                // Meta value is stored as string '1' or '0'
                $mode['show_card_follow_button'] = ($show_card_follow_button_override === '1');
                $mode['source'] = 'page_override';
            }
            
            // Check account types override
            $account_types_override = get_post_meta($page_id, '_vh360_members_directory_account_types_override', true);
            if (is_array($account_types_override) && !empty($account_types_override)) {
                $allowed_account_types = array('professional', 'organization');
                $filtered_types = array_intersect($account_types_override, $allowed_account_types);
                if (!empty($filtered_types)) {
                    $mode['professionals_account_types'] = array_values($filtered_types);
                    $mode['source'] = 'page_override';
                }
            }
        }
    }
    
    // SECURITY: Fail-closed enforcement for professionals_only mode
    // If professionals_only is set but account_types is empty, apply safe defaults
    // This prevents data leaks from misconfigured settings
    if ($mode['audience'] === 'professionals_only' && empty($mode['professionals_account_types'])) {
        $mode['professionals_account_types'] = array('professional', 'organization');
    }
    
    return $mode;
}
