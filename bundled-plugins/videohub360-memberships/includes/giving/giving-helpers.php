<?php
if (!defined('ABSPATH')) exit;
function vh360_giving_default_options(){ return array('enable_giving'=>0,'dashboard_tab_label'=>'My Giving','default_currency'=>'usd','minimum_amount'=>'1','suggested_amounts'=>'10,25,50,100','enable_anonymous'=>1,'enable_notes'=>1,'success_message'=>'Thank you for your gift.','cancel_message'=>'Your giving checkout was canceled.'); }
function vh360_giving_options(){ return wp_parse_args(get_option('vh360_giving_options',array()), vh360_giving_default_options()); }
function vh360_giving_is_enabled(){ $o=vh360_giving_options(); return !empty($o['enable_giving']); }
function vh360_giving_format_amount($amount,$currency='usd'){ return strtoupper($currency).' '.number_format((float)$amount,2); }
add_filter('vh360_dashboard_tabs_registry', function($tabs,$user_id){ if(!function_exists('vh360_giving_is_enabled')||!vh360_giving_is_enabled()) return $tabs; $o=vh360_giving_options(); $tabs['giving']=array('label'=>$o['dashboard_tab_label']?:'My Giving','label_callback'=>null,'show_callback'=>'__return_true','icon_svg'=>'<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21.2l8.8-8.8a5.5 5.5 0 0 0 0-7.8z"></path></svg>'); return $tabs; },10,2);
add_filter('wp_nav_menu_items', function($items, $args){
    if (empty($args->theme_location) || 'dashboard' !== $args->theme_location || !vh360_giving_is_enabled()) {
        return $items;
    }
    if (false !== strpos($items, '#giving')) {
        return $items;
    }
    $options = vh360_giving_options();
    $label = !empty($options['dashboard_tab_label']) ? $options['dashboard_tab_label'] : __('My Giving', 'videohub360-memberships');
    $items .= '<li class="vh360-dashboard-nav-item"><a href="#giving" class="vh360-dashboard-nav-link" data-tab="giving">'
        . '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21.2l8.8-8.8a5.5 5.5 0 0 0 0-7.8z"></path></svg>'
        . '<span class="vh360-dashboard-nav-label">' . esc_html($label) . '</span></a></li>';
    return $items;
}, 20, 2);
