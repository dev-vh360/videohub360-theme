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
            // Show admin-only notice when menu is not assigned
            if ( function_exists( 'vh360_render_menu_admin_notice' ) ) {
                vh360_render_menu_admin_notice( 'dashboard', __( 'Dashboard Menu', 'videohub360-theme' ) );
            }
            ?>
        <?php endif; ?>
            </nav>
    
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
