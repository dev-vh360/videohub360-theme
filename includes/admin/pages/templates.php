<?php
/**
 * Page Templates Guide Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Page Templates Guide', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

// Define available templates
$templates = array(
    array(
        'name' => __('Dashboard Template', 'videohub360-theme'),
        'file' => 'template-dashboard.php',
        'slug' => 'dashboard',
        'description' => __('User dashboard showing profile overview, recent videos, activity feed, and statistics. This is the main hub for logged-in users.', 'videohub360-theme'),
        'icon' => 'dashicons-dashboard',
    ),
    array(
        'name' => __('Profile Edit Template', 'videohub360-theme'),
        'file' => 'template-profile-edit.php',
        'slug' => 'profile-edit',
        'description' => __('Allows users to edit their profile information, upload avatar and cover images, and manage social media links.', 'videohub360-theme'),
        'icon' => 'dashicons-admin-users',
    ),
    array(
        'name' => __('Login Template', 'videohub360-theme'),
        'file' => 'template-login.php',
        'slug' => 'login',
        'description' => __('Custom login page with theme styling. Redirects logged-in users to the dashboard.', 'videohub360-theme'),
        'icon' => 'dashicons-lock',
    ),
    array(
        'name' => __('Register Template', 'videohub360-theme'),
        'file' => 'template-register.php',
        'slug' => 'register',
        'description' => __('User registration page with custom fields and validation. Integrates with WordPress user system.', 'videohub360-theme'),
        'icon' => 'dashicons-admin-users',
    ),
    array(
        'name' => __('Members Directory Template', 'videohub360-theme'),
        'file' => 'template-members-directory.php',
        'slug' => 'members',
        'description' => __('Searchable directory of all site members with filtering and sorting options. Displays member cards with avatars and stats.', 'videohub360-theme'),
        'icon' => 'dashicons-groups',
    ),
    array(
        'name' => __('Activity Feed Template', 'videohub360-theme'),
        'file' => 'template-activity-feed.php',
        'slug' => 'activity',
        'description' => __('Displays recent site activities including video uploads, new members, profile updates, and milestones.', 'videohub360-theme'),
        'icon' => 'dashicons-chart-line',
    ),
    array(
        'name' => __('Bulletins Template', 'videohub360-theme'),
        'file' => 'template-bulletins.php',
        'slug' => 'bulletins',
        'description' => __('Display all bulletins (announcements) with filtering by category and type. Shows bulletin cards with status indicators.', 'videohub360-theme'),
        'icon' => 'dashicons-megaphone',
    ),
);

// Check which pages exist
$pages_status = array();
foreach ($templates as $template) {
    $page = get_page_by_path($template['slug']);
    $pages_status[$template['slug']] = $page ? true : false;
}
?>

<div class="vh360-admin-templates">
    
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Available Page Templates', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('The Videohub360 Theme includes several custom page templates. Below is a guide to each template and how to set them up.', 'videohub360-theme'); ?></p>
    </div>
    
    <?php foreach ($templates as $template) : ?>
        <div class="vh360-admin-card vh360-template-card">
            <div class="vh360-template-header">
                <span class="dashicons <?php echo esc_attr($template['icon']); ?>"></span>
                <h3><?php echo esc_html($template['name']); ?></h3>
                <?php if ($pages_status[$template['slug']]) : ?>
                    <span class="vh360-template-status vh360-status-active">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Active', 'videohub360-theme'); ?>
                    </span>
                <?php else : ?>
                    <span class="vh360-template-status vh360-status-missing">
                        <span class="dashicons dashicons-warning"></span>
                        <?php esc_html_e('Not Created', 'videohub360-theme'); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="vh360-template-body">
                <p><strong><?php esc_html_e('Template File:', 'videohub360-theme'); ?></strong> <code><?php echo esc_html($template['file']); ?></code></p>
                <p><strong><?php esc_html_e('Suggested Slug:', 'videohub360-theme'); ?></strong> <code><?php echo esc_html($template['slug']); ?></code></p>
                <p><?php echo esc_html($template['description']); ?></p>
                
                <div class="vh360-template-setup">
                    <h4><?php esc_html_e('Setup Instructions:', 'videohub360-theme'); ?></h4>
                    <ol>
                        <li><?php esc_html_e('Go to Pages → Add New', 'videohub360-theme'); ?></li>
                        <li><?php printf(esc_html__('Create a page with the title "%s"', 'videohub360-theme'), esc_html($template['name'])); ?></li>
                        <li><?php printf(esc_html__('Set the page slug to "%s"', 'videohub360-theme'), '<code>' . esc_html($template['slug']) . '</code>'); ?></li>
                        <li><?php printf(esc_html__('Select the "%s" template from the Template dropdown', 'videohub360-theme'), esc_html($template['name'])); ?></li>
                        <li><?php esc_html_e('Publish the page', 'videohub360-theme'); ?></li>
                    </ol>
                </div>
                
                <div class="vh360-template-actions">
                    <?php if ($pages_status[$template['slug']]) : ?>
                        <?php $page = get_page_by_path($template['slug']); ?>
                        <a href="<?php echo esc_url(get_edit_post_link($page->ID)); ?>" class="button button-secondary">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e('Edit Page', 'videohub360-theme'); ?>
                        </a>
                        <a href="<?php echo esc_url(get_permalink($page->ID)); ?>" class="button button-secondary" target="_blank">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('View Page', 'videohub360-theme'); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Create Page', 'videohub360-theme'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Shortcodes Documentation -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Available Shortcodes', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('You can use the following shortcodes in your content:', 'videohub360-theme'); ?></p>
        
        <table class="widefat vh360-shortcodes-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Shortcode', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Description', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Example', 'videohub360-theme'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[vh360_dashboard]</code></td>
                    <td><?php esc_html_e('Display user dashboard', 'videohub360-theme'); ?></td>
                    <td><button class="button button-small vh360-copy-shortcode" data-shortcode="[vh360_dashboard]"><?php esc_html_e('Copy', 'videohub360-theme'); ?></button></td>
                </tr>
                <tr>
                    <td><code>[vh360_profile_edit]</code></td>
                    <td><?php esc_html_e('Display profile edit form', 'videohub360-theme'); ?></td>
                    <td><button class="button button-small vh360-copy-shortcode" data-shortcode="[vh360_profile_edit]"><?php esc_html_e('Copy', 'videohub360-theme'); ?></button></td>
                </tr>
                <tr>
                    <td><code>[vh360_members]</code></td>
                    <td><?php esc_html_e('Display members directory', 'videohub360-theme'); ?></td>
                    <td><button class="button button-small vh360-copy-shortcode" data-shortcode="[vh360_members]"><?php esc_html_e('Copy', 'videohub360-theme'); ?></button></td>
                </tr>
                <tr>
                    <td><code>[vh360_activity]</code></td>
                    <td><?php esc_html_e('Display activity feed', 'videohub360-theme'); ?></td>
                    <td><button class="button button-small vh360-copy-shortcode" data-shortcode="[vh360_activity]"><?php esc_html_e('Copy', 'videohub360-theme'); ?></button></td>
                </tr>
                <tr>
                    <td><code>[vh360_bulletins]</code></td>
                    <td><?php esc_html_e('Display bulletins list', 'videohub360-theme'); ?></td>
                    <td><button class="button button-small vh360-copy-shortcode" data-shortcode="[vh360_bulletins]"><?php esc_html_e('Copy', 'videohub360-theme'); ?></button></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Template Usage Examples -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Template Usage Examples', 'videohub360-theme'); ?></h2>
        
        <h3><?php esc_html_e('Basic Setup', 'videohub360-theme'); ?></h3>
        <p><?php esc_html_e('For a typical site, you should create at least these pages:', 'videohub360-theme'); ?></p>
        <ul>
            <li><?php esc_html_e('Dashboard (for logged-in user homepage)', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Members Directory (to showcase your community)', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Activity Feed (to show site activity)', 'videohub360-theme'); ?></li>
        </ul>
        
        <h3><?php esc_html_e('Full Featured Setup', 'videohub360-theme'); ?></h3>
        <p><?php esc_html_e('For maximum functionality, create all available templates:', 'videohub360-theme'); ?></p>
        <ul>
            <li><?php esc_html_e('Dashboard, Profile Edit, Login, Register (for user management)', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Members Directory, Activity Feed (for community features)', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Bulletins (for announcements)', 'videohub360-theme'); ?></li>
        </ul>
        
        <h3><?php esc_html_e('Template Visibility', 'videohub360-theme'); ?></h3>
        <p><?php esc_html_e('You can control which templates require login from the Template Visibility settings page:', 'videohub360-theme'); ?></p>
        <ul>
            <li><?php esc_html_e('Dashboard and Profile Edit are typically private (login required)', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Members Directory can be public or private depending on your community strategy', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Activity Feed can be public or private based on your preferences', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Public Profile Pages can be public (for discoverability) or private', 'videohub360-theme'); ?></li>
        </ul>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-access')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-lock"></span>
                <?php esc_html_e('Configure Template Visibility', 'videohub360-theme'); ?>
            </a>
        </p>
    </div>
    
</div>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
