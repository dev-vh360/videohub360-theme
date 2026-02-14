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
            <ul class="vh360-dashboard-nav">
            
            <li class="vh360-dashboard-nav-item">
                <a href="#overview" class="vh360-dashboard-nav-link vh360-dashboard-tab active" data-tab="overview">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Overview', 'videohub360-theme'); ?></span>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#create-video" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="create-video">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('+ Create', 'videohub360-theme'); ?></span>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#videos" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="videos">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('My Videos', 'videohub360-theme'); ?></span>
                    <?php if ($stats['videos'] > 0) : ?>
                        <span class="vh360-dashboard-nav-badge"><?php echo esc_html($stats['videos']); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="vh360-dashboard-nav-item">
                <a href="#live-rooms" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="live-rooms">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <circle cx="12" cy="12" r="8"></circle>
                        <path d="M2 12a10 10 0 0 1 20 0"></path>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Live Rooms', 'videohub360-theme'); ?></span>
                </a>
            </li>

            
            <li class="vh360-dashboard-nav-item">
                <a href="#messages" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="messages">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Messages', 'videohub360-theme'); ?></span>
                    <?php
                    if (function_exists('vh360_get_unread_messages_count')) {
                        $unread_dm_count = vh360_get_unread_messages_count($current_user_id);
                        if ($unread_dm_count > 0) :
                        ?>
                            <span class="vh360-dashboard-nav-badge vh360-dm-unread-badge-nav"><?php echo esc_html($unread_dm_count); ?></span>
                        <?php
                        endif;
                    }
                    ?>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#notifications" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="notifications">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Notifications', 'videohub360-theme'); ?></span>
                    <?php
                    $unread_count = vh360_get_unread_notification_count($current_user_id);
                    if ($unread_count > 0) :
                    ?>
                        <span class="vh360-dashboard-nav-badge"><?php echo esc_html($unread_count); ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <?php if ( current_user_can( 'vh360_send_push' ) ) : ?>
            <li class="vh360-dashboard-nav-item">
                <a href="#push-notifications" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="push-notifications">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13"></path>
                        <path d="M22 2L15 22l-4-9-9-4 20-7z"></path>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Push Notifications', 'videohub360-theme'); ?></span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#liked-videos" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="liked-videos">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Liked Videos', 'videohub360-theme'); ?></span>
                    <?php
                    $liked_count = VideoHub360_Video_Reactions::get_liked_videos_count($current_user_id);
                    if ($liked_count > 0) :
                    ?>
                        <span class="vh360-dashboard-nav-badge"><?php echo esc_html($liked_count); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#playlists" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="playlists">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="9" x2="15" y2="9"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('My Playlists', 'videohub360-theme'); ?></span>
                    <?php
                    $playlists = VideoHub360_Playlists::get_user_playlists($current_user_id);
                    $playlist_count = count($playlists);
                    if ($playlist_count > 0) :
                    ?>
                        <span class="vh360-dashboard-nav-badge"><?php echo esc_html($playlist_count); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#create-post" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="create-post">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('+ Blog Posts', 'videohub360-theme'); ?></span>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#profile" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="profile">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Edit Profile', 'videohub360-theme'); ?></span>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#galleries" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="galleries">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Galleries', 'videohub360-theme'); ?></span>
                    <?php
                    $gallery_count = vh360_get_user_gallery_count($current_user_id);
                    if ($gallery_count > 0) :
                    ?>
                        <span class="vh360-dashboard-nav-badge"><?php echo esc_html($gallery_count); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#events" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="events">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Events', 'videohub360-theme'); ?></span>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#bulletins" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="bulletins">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Bulletins', 'videohub360-theme'); ?></span>
                </a>
            </li>
            
            <li class="vh360-dashboard-nav-item">
                <a href="#settings" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="settings">
                    <svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m8.66-7.66l-5.2 3M8.54 14l-5.2 3m13.32-10.34l-5.2 3M8.54 10l-5.2-3"></path>
                    </svg>
                    <span class="vh360-dashboard-nav-text"><?php esc_html_e('Settings', 'videohub360-theme'); ?></span>
                </a>
            </li>
            
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
