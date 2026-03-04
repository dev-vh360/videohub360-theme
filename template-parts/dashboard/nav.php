<?php
/**
 * Dashboard Navigation
 *
 * Sidebar navigation menu with icons for dashboard tabs.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$user_account_type = vh360_get_user_account_type($current_user_id);
$is_approved_professional = vh360_is_professional_approved($current_user_id);
$stats = vh360_get_user_stats($current_user_id);
?>

<aside class="vh360-dashboard-sidebar">
    
    <!-- User Profile Summary -->
    <div class="vh360-dashboard-user-summary">
        <div class="vh360-dashboard-user-avatar">
            <?php echo get_avatar($current_user_id, 60); ?>
        </div>
        <div class="vh360-dashboard-user-info">
            <h3 class="vh360-dashboard-user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></h3>
            <p class="vh360-dashboard-user-email"><?php echo esc_html(wp_get_current_user()->user_email); ?></p>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="vh360-dashboard-navigation">
        <?php if ( has_nav_menu('dashboard') ) : ?>
            <?php
                wp_nav_menu(array(
                    'theme_location' => 'dashboard',
                    'container'      => false,
                    'menu_class'     => 'vh360-dashboard-nav',
                    'fallback_cb'    => false,
                    'depth'          => 1,
                    'walker'         => new VH360_Dashboard_Menu_Walker(),
                    'items_wrap'     => '<ul class="vh360-dashboard-nav">%3$s</ul>',
                ));
            ?>
        <?php else : ?>
            <?php
            // Fallback navigation built from registry (eliminates drift)
            $registry = vh360_get_dashboard_tabs_registry( $current_user_id );
            $first_tab = true;
            ?>
            <ul class="vh360-dashboard-nav">
            <?php foreach ( $registry as $tab_id => $tab_config ) :
                // Apply visibility rules
                $show_callback = $tab_config['show_callback'];
                $should_show = is_callable( $show_callback ) ? call_user_func( $show_callback, $current_user_id ) : true;
                
                if ( ! $should_show ) {
                    continue;
                }
                
                // Skip go-live from main nav (it's in Quick Actions)
                if ( $tab_id === 'go-live' ) {
                    continue;
                }
                
                // Get label (use callback if available)
                $label = $tab_config['label'];
                if ( $tab_config['label_callback'] && is_callable( $tab_config['label_callback'] ) ) {
                    $label = call_user_func( $tab_config['label_callback'], $current_user_id );
                }
                
                // Get icon
                $icon_svg = ! empty( $tab_config['icon_svg'] ) ? $tab_config['icon_svg'] : '';
                
                // Add active class to first visible tab
                $active_class = $first_tab ? ' active' : '';
                $first_tab = false;
                ?>
                <li class="vh360-dashboard-nav-item">
                    <a href="#<?php echo esc_attr( $tab_id ); ?>" class="vh360-dashboard-nav-link vh360-dashboard-tab<?php echo $active_class; ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>">
                        <?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <span class="vh360-dashboard-nav-text"><?php echo esc_html( $label ); ?></span>
                        <?php
                        // Add badges for specific tabs
                        if ( $tab_id === 'videos' && $stats['videos'] > 0 ) :
                        ?>
                            <span class="vh360-dashboard-nav-badge"><?php echo esc_html( $stats['videos'] ); ?></span>
                        <?php
                        endif;
                        
                        if ( $tab_id === 'galleries' ) :
                            $gallery_count = vh360_get_user_gallery_count( $current_user_id );
                            if ( $gallery_count > 0 ) :
                        ?>
                            <span class="vh360-dashboard-nav-badge"><?php echo esc_html( $gallery_count ); ?></span>
                        <?php
                            endif;
                        endif;
                        
                        if ( $tab_id === 'messages' ) :
                            $unread_dm = function_exists( 'vh360_get_unread_dm_count' ) ? vh360_get_unread_dm_count( $current_user_id ) : 0;
                            if ( $unread_dm > 0 ) :
                        ?>
                            <span class="vh360-dashboard-nav-badge"><?php echo esc_html( $unread_dm > 99 ? '99+' : $unread_dm ); ?></span>
                        <?php
                            endif;
                        endif;
                        
                        if ( $tab_id === 'notifications' ) :
                            $unread_notif = function_exists( 'vh360_get_unread_notification_count' ) ? (int) vh360_get_unread_notification_count( $current_user_id ) : 0;
                            if ( $unread_notif > 0 ) :
                        ?>
                            <span class="vh360-dashboard-nav-badge"><?php echo esc_html( $unread_notif > 99 ? '99+' : $unread_notif ); ?></span>
                        <?php
                            endif;
                        endif;
                        ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
            </nav>
    
    <!-- Quick Actions -->
    <div class="vh360-dashboard-quick-actions">
        <a href="#go-live" class="vh360-dashboard-btn vh360-dashboard-btn-go-live vh360-dashboard-tab" data-tab="go-live">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <?php esc_html_e('Go Live', 'videohub360-theme'); ?>
        </a>
    </div>
    
    <!-- Logout Link -->
    <div class="vh360-dashboard-logout">
        <a href="<?php echo esc_url(vh360_get_logout_url(home_url())); ?>" class="vh360-dashboard-logout-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <?php esc_html_e('Logout', 'videohub360-theme'); ?>
        </a>
    </div>
    
</aside>

<style>
/* Dashboard User Summary */
.vh360-dashboard-user-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.vh360-dashboard-user-avatar img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
}

.vh360-dashboard-user-info {
    flex: 1;
    min-width: 0;
}

.vh360-dashboard-user-name {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: var(--text-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vh360-dashboard-user-email {
    font-size: 0.75rem;
    color: var(--text-light);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Quick Actions */
.vh360-dashboard-quick-actions {
    padding: 1.5rem 0;
    border-top: 1px solid var(--border-color);
    margin-top: 1.5rem;
}

.vh360-dashboard-quick-actions .vh360-dashboard-btn {
    width: 100%;
    justify-content: center;
}

/* Go Live Button - Red background with white text/icon */
.vh360-dashboard-btn-go-live {
    background-color: #e53935 !important;
    color: #ffffff !important;
    border-color: #e53935 !important;
}

.vh360-dashboard-btn-go-live:hover {
    background-color: #c62828 !important;
    border-color: #c62828 !important;
}

.vh360-dashboard-btn-go-live svg {
    stroke: #ffffff !important;
}

/* Logout Link */
.vh360-dashboard-logout {
    padding-top: 1rem;
}

.vh360-dashboard-logout-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: var(--text-light);
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: var(--transition);
    font-size: 0.875rem;
}

.vh360-dashboard-logout-link:hover {
    background: var(--bg-light);
    color: var(--error-color);
}
</style>
