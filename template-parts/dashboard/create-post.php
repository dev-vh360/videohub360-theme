<?php
/**
 * Dashboard Create Post Tab
 *
 * Frontend post creator so users can create new blog posts
 * directly from the dashboard without accessing the WordPress backend.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure only logged-in users access this tab
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('You must be logged in to create posts.', 'videohub360-theme') . '</p>';
    return;
}

$current_user_id = get_current_user_id();


$vh360_is_licensed = ( function_exists('vh360_theme_is_license_valid') ? vh360_theme_is_license_valid() : ( function_exists('videohub360_license_is_valid') && videohub360_license_is_valid() ) );
$vh360_license_url = function_exists('vh360_theme_get_license_admin_url') ? vh360_theme_get_license_admin_url() : admin_url('admin.php?page=videohub360-license');
// Determine if current user is allowed to create posts.
// By default, this uses the vh360_create_posts capability,
// but it can be customized via the vh360_can_create_posts filter.
$can_create_posts = apply_filters(
    'vh360_can_create_posts',
    current_user_can('vh360_create_posts')
);

$errors = array();
$success_message = '';

/**
 * Handle post creation form submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['vh360_action'])
    && $_POST['vh360_action'] === 'vh360_create_post') {

    
    // vh360_license_softlock_checked
    $vh360_is_licensed = ( function_exists('vh360_theme_is_license_valid') ? vh360_theme_is_license_valid() : ( function_exists('videohub360_license_is_valid') && videohub360_license_is_valid() ) );
    if ( ! $vh360_is_licensed ) {
        $errors[] = esc_html__( 'Your VideoHub360 license is inactive. Activate your license to create a new post.', 'videohub360-theme' );
    }
// Only proceed if the current user is allowed to create posts
    if (!$can_create_posts) {
        $errors[] = esc_html__('You do not have permission to create posts.', 'videohub360-theme');
    } else {
        // Verify nonce
        if (!isset($_POST['vh360_create_post_nonce'])
            || !wp_verify_nonce($_POST['vh360_create_post_nonce'], 'vh360_create_post')) {
            $errors[] = esc_html__('Security check failed. Please try again.', 'videohub360-theme');
        } else {
            $title       = isset($_POST['vh360_post_title']) ? sanitize_text_field($_POST['vh360_post_title']) : '';
            $content     = isset($_POST['vh360_post_content']) ? wp_kses_post($_POST['vh360_post_content']) : '';
            $excerpt     = isset($_POST['vh360_post_excerpt']) ? sanitize_textarea_field($_POST['vh360_post_excerpt']) : '';
            $post_status = isset($_POST['vh360_post_status']) ? sanitize_text_field($_POST['vh360_post_status']) : 'draft';

            // Validate post status (whitelist)
            if (!in_array($post_status, array('publish', 'draft'), true)) {
                $post_status = 'draft';
            }

            // Validate required fields
            if (empty($title)) {
                $errors[] = esc_html__('Please provide a title for your post.', 'videohub360-theme');
            }

            if (empty($errors)) {
                $post_id = wp_insert_post(array(
                    'post_type'    => 'post',
                    'post_status'  => $post_status,
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_excerpt' => $excerpt,
                    'post_author'  => $current_user_id,
                ));

                if (!is_wp_error($post_id) && $post_id) {
                    // Handle featured image upload
                    if (!empty($_FILES['vh360_featured_image']['name'])) {
                        // Check user capability
                        if (current_user_can('upload_files')) {
                            // Validate file upload error
                            if ($_FILES['vh360_featured_image']['error'] !== UPLOAD_ERR_OK) {
                                $upload_errors = array(
                                    UPLOAD_ERR_INI_SIZE   => __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'videohub360-theme'),
                                    UPLOAD_ERR_FORM_SIZE  => __('The uploaded file exceeds the MAX_FILE_SIZE directive', 'videohub360-theme'),
                                    UPLOAD_ERR_PARTIAL    => __('The uploaded file was only partially uploaded', 'videohub360-theme'),
                                    UPLOAD_ERR_NO_FILE    => __('No file was uploaded', 'videohub360-theme'),
                                    UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder', 'videohub360-theme'),
                                    UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk', 'videohub360-theme'),
                                    UPLOAD_ERR_EXTENSION  => __('File upload stopped by extension', 'videohub360-theme'),
                                );
                                $error_code = $_FILES['vh360_featured_image']['error'];
                                if (isset($upload_errors[$error_code])) {
                                    $errors[] = $upload_errors[$error_code];
                                } else {
                                    // Handle unknown error codes
                                    $errors[] = sprintf(
                                        __('Image upload failed with error code: %d', 'videohub360-theme'),
                                        $error_code
                                    );
                                }
                            } else {
                                // Load required WordPress files
                                require_once ABSPATH . 'wp-admin/includes/file.php';
                                require_once ABSPATH . 'wp-admin/includes/media.php';
                                require_once ABSPATH . 'wp-admin/includes/image.php';

                                // Handle the upload
                                $attachment_id = media_handle_upload('vh360_featured_image', $post_id);

                                if (!is_wp_error($attachment_id)) {
                                    // Set as featured image
                                    set_post_thumbnail($post_id, $attachment_id);
                                } else {
                                    // Display the error to user (post is still created)
                                    $errors[] = sprintf(
                                        __('Post created but image upload failed: %s', 'videohub360-theme'),
                                        $attachment_id->get_error_message()
                                    );
                                }
                            }
                        }
                    }

                    // Handle categories if provided
                    if (isset($_POST['vh360_post_categories']) && is_array($_POST['vh360_post_categories'])) {
                        $categories = array_map('absint', $_POST['vh360_post_categories']);
                        wp_set_post_categories($post_id, $categories);
                    }

                    // Handle tags if provided
                    if (isset($_POST['vh360_post_tags']) && !empty($_POST['vh360_post_tags'])) {
                        $tags = sanitize_text_field($_POST['vh360_post_tags']);
                        wp_set_post_tags($post_id, $tags);
                    }

                    // Success! Decide whether to redirect or show message
                    // Only redirect if there are no errors (especially image upload errors)
                    $redirect_to_post = isset($_POST['vh360_redirect_to_post']) && $_POST['vh360_redirect_to_post'] === '1';
                    
                    if ($redirect_to_post && empty($errors)) {
                        $post_url = get_permalink($post_id);
                        if ($post_url) {
                            wp_safe_redirect($post_url);
                            exit;
                        }
                    }
                    
                    if (empty($errors)) {
                        $success_message = esc_html__('Post created successfully!', 'videohub360-theme');
                    }
                } else {
                    $errors[] = esc_html__('Could not create post. Please try again.', 'videohub360-theme');
                }
            }
        }
    }
}

?>
<div class="vh360-dashboard-section vh360-dashboard-create-post">
    <header class="vh360-dashboard-section-header">
        <h2 class="vh360-dashboard-section-title"><?php esc_html_e('Create Post', 'videohub360-theme'); ?></h2>
        <p class="vh360-dashboard-section-subtitle">
            <?php esc_html_e('Create a new post to share with your community.', 'videohub360-theme'); ?>
        </p>
    </header>

    <?php if (!empty($errors)) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-error">
            <ul>
                <?php foreach ($errors as $error) : ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-success">
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($can_create_posts) : ?>
        <?php
        // Get categories for the form (only when user can create posts)
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));
        ?>
    <div class="vh360-dashboard-card vh360-post-create-card">
        <h3 class="vh360-dashboard-card-title"><?php esc_html_e('New Post', 'videohub360-theme'); ?></h3>
        <p class="vh360-dashboard-card-text">
            <?php esc_html_e('Fill in the details below to create a new post. All fields except title are optional.', 'videohub360-theme'); ?>
        </p>

        <form method="post" class="vh360-post-create-form" enctype="multipart/form-data">
            <?php wp_nonce_field('vh360_create_post', 'vh360_create_post_nonce'); ?>
            <input type="hidden" name="vh360_action" value="vh360_create_post" />

            <div class="vh360-form-group">
                <label for="vh360_post_title"><?php esc_html_e('Post Title', 'videohub360-theme'); ?> <span class="vh360-required">*</span></label>
                <input type="text" id="vh360_post_title" name="vh360_post_title" class="vh360-input" required placeholder="<?php esc_attr_e('Enter your post title', 'videohub360-theme'); ?>" value="<?php echo isset($_POST['vh360_post_title']) ? esc_attr($_POST['vh360_post_title']) : ''; ?>">
                <p class="vh360-form-help">
                    <?php esc_html_e('Give your post a clear and descriptive title.', 'videohub360-theme'); ?>
                </p>
            </div>

            <div class="vh360-form-group">
                <label for="vh360_post_content"><?php esc_html_e('Post Content', 'videohub360-theme'); ?></label>
                <textarea id="vh360_post_content" name="vh360_post_content" class="vh360-textarea" rows="8" placeholder="<?php esc_attr_e('Write your post content here', 'videohub360-theme'); ?>"><?php echo isset($_POST['vh360_post_content']) ? esc_textarea($_POST['vh360_post_content']) : ''; ?></textarea>
                <p class="vh360-form-help">
                    <?php esc_html_e('The main content of your post. You can include text, links, and formatting.', 'videohub360-theme'); ?>
                </p>
            </div>

            <div class="vh360-form-group">
                <label for="vh360_post_excerpt"><?php esc_html_e('Excerpt', 'videohub360-theme'); ?></label>
                <textarea id="vh360_post_excerpt" name="vh360_post_excerpt" class="vh360-textarea" rows="3" placeholder="<?php esc_attr_e('Brief summary or excerpt', 'videohub360-theme'); ?>" maxlength="500"><?php echo isset($_POST['vh360_post_excerpt']) ? esc_textarea($_POST['vh360_post_excerpt']) : ''; ?></textarea>
                <div class="vh360-character-counter">
                    <span class="vh360-char-count">0</span> / 500 <?php esc_html_e('characters', 'videohub360-theme'); ?>
                </div>
                <p class="vh360-form-help">
                    <?php esc_html_e('A short summary that appears in post previews and listings (optional, max 500 characters).', 'videohub360-theme'); ?>
                </p>
            </div>

            <div class="vh360-form-group">
                <label for="vh360_post_featured_image"><?php esc_html_e('Featured Image', 'videohub360-theme'); ?></label>
                <input type="file" id="vh360_post_featured_image" name="vh360_featured_image" class="vh360-file-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                <button type="button" class="vh360-upload-button" id="vh360-post-upload-trigger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <?php esc_html_e('Upload Image', 'videohub360-theme'); ?>
                </button>
                <div id="vh360-post-image-preview" class="vh360-image-preview" style="display: none;">
                    <img src="" alt="<?php esc_attr_e('Preview', 'videohub360-theme'); ?>" id="vh360-post-preview-img">
                    <button type="button" class="vh360-remove-image" id="vh360-post-remove-image" aria-label="<?php esc_attr_e('Remove image', 'videohub360-theme'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <p class="vh360-form-help">
                    <?php esc_html_e('Upload a featured image for your post. Supported formats: JPG, PNG, GIF, WebP (Max 5MB)', 'videohub360-theme'); ?>
                </p>
            </div>

            <?php if (!empty($categories)) : ?>
            <div class="vh360-form-group">
                <label for="vh360_post_categories"><?php esc_html_e('Categories', 'videohub360-theme'); ?></label>
                <select id="vh360_post_categories" name="vh360_post_categories[]" class="vh360-input" multiple size="5" aria-describedby="vh360_post_categories_help">
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>">
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="vh360-form-help" id="vh360_post_categories_help">
                    <?php esc_html_e('Select one or more categories for your post. Hold Ctrl (Cmd on Mac) to select multiple.', 'videohub360-theme'); ?>
                </p>
            </div>
            <?php endif; ?>

            <div class="vh360-form-group">
                <label for="vh360_post_tags"><?php esc_html_e('Tags', 'videohub360-theme'); ?></label>
                <input type="text" id="vh360_post_tags" name="vh360_post_tags" class="vh360-input" placeholder="<?php esc_attr_e('tag1, tag2, tag3', 'videohub360-theme'); ?>" value="<?php echo isset($_POST['vh360_post_tags']) ? esc_attr($_POST['vh360_post_tags']) : ''; ?>">
                <p class="vh360-form-help">
                    <?php esc_html_e('Add tags separated by commas to help organize and categorize your post.', 'videohub360-theme'); ?>
                </p>
            </div>

            <div class="vh360-form-group">
                <label for="vh360_post_status"><?php esc_html_e('Post Status', 'videohub360-theme'); ?></label>
                <select id="vh360_post_status" name="vh360_post_status" class="vh360-input">
                    <option value="draft"><?php esc_html_e('Draft', 'videohub360-theme'); ?></option>
                    <option value="publish"><?php esc_html_e('Publish', 'videohub360-theme'); ?></option>
                </select>
                <p class="vh360-form-help">
                    <?php esc_html_e('Choose "Draft" to save without publishing, or "Publish" to make it live immediately.', 'videohub360-theme'); ?>
                </p>
            </div>

            <div class="vh360-form-group">
                <label class="vh360-checkbox-label">
                    <input type="checkbox" name="vh360_redirect_to_post" value="1" id="vh360_redirect_to_post">
                    <span><?php esc_html_e('View post after creation', 'videohub360-theme'); ?></span>
                </label>
                <p class="vh360-form-help">
                    <?php esc_html_e('Redirect to the post page after creating it, or stay on this page to create another.', 'videohub360-theme'); ?>
                </p>
            </div>

            <div class="vh360-form-actions">
                <button type="submit" class="vh360-dashboard-btn <?php echo !$vh360_is_licensed ? 'vh360-locked' : ''; ?>" <?php echo !$vh360_is_licensed ? 'disabled="disabled" aria-disabled="true"' : ''; ?> title="<?php echo !$vh360_is_licensed ? esc_attr__('Activate your license to create a new post.', 'videohub360-theme') : ''; ?>">
                    <?php esc_html_e('Create Post', 'videohub360-theme'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- My Posts Section -->
    <div class="vh360-dashboard-section" style="margin-top: 2rem;">
        <header class="vh360-dashboard-section-header">
            <h2 class="vh360-dashboard-section-title"><?php esc_html_e('My Posts', 'videohub360-theme'); ?></h2>
            <p class="vh360-dashboard-section-subtitle">
                <?php esc_html_e('Manage and edit your posts.', 'videohub360-theme'); ?>
            </p>
        </header>

        <?php
        // Get user's posts
        $user_posts = get_posts(array(
            'author'           => $current_user_id,
            'post_type'        => 'post',
            'posts_per_page'   => 10,
            'post_status'      => array('publish', 'draft', 'pending'),
            'orderby'          => 'date',
            'order'            => 'DESC',
            'suppress_filters' => true
        ));

        if (!empty($user_posts)) :
        ?>
        <div class="vh360-posts-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
            <?php foreach ($user_posts as $user_post) : 
                setup_postdata($user_post);
                $post_id = $user_post->ID;
                $edit_link = get_edit_post_link($post_id);
                $view_link = get_permalink($post_id);
                $status = get_post_status($post_id);
                $status_label = ucfirst($status);
                $thumbnail_id = get_post_thumbnail_id($post_id);
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
            ?>
            <div class="vh360-post-card" style="background: var(--bg-light); border-radius: var(--border-radius); overflow: hidden; border: 1px solid var(--border-color);">
                <?php if ($thumbnail_url) : ?>
                <div class="vh360-post-card-image" style="aspect-ratio: 16/9; overflow: hidden; position: relative;">
                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php if ($status !== 'publish') : ?>
                    <span class="vh360-post-status" style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.7); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                        <?php echo esc_html($status_label); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="vh360-post-card-content" style="padding: 1rem;">
                    <h3 class="vh360-post-card-title" style="margin: 0 0 0.5rem; font-size: 1.125rem; font-weight: 600;">
                        <?php echo esc_html(get_the_title($post_id)); ?>
                    </h3>
                    <p class="vh360-post-card-date" style="color: var(--text-light); font-size: 0.875rem; margin: 0 0 1rem;">
                        <?php echo esc_html(get_the_date('', $post_id)); ?>
                    </p>
                    
                    <div class="vh360-post-card-actions" style="display: flex; gap: 0.5rem;">
                        <?php if (vh360_user_can_manage_dashboard_post($post_id, $current_user_id)) : ?>
                        <button type="button" class="vh360-post-edit vh360-btn-secondary" data-post-id="<?php echo esc_attr($post_id); ?>" style="flex: 1; text-align: center; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px; background: transparent; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.25rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            <?php esc_html_e('Edit', 'videohub360-theme'); ?>
                        </button>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($view_link); ?>" target="_blank" class="vh360-btn-secondary" style="flex: 1; text-align: center; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.25rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <?php esc_html_e('View', 'videohub360-theme'); ?>
                        </a>
                        <?php if (vh360_user_can_manage_dashboard_post($post_id, $current_user_id)) : ?>
                        <button type="button" class="vh360-post-delete vh360-btn-secondary" data-post-id="<?php echo esc_attr($post_id); ?>" style="flex: 1; text-align: center; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px; background: transparent; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.25rem; color: #dc2626;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            <?php esc_html_e('Delete', 'videohub360-theme'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; 
            wp_reset_postdata();
            ?>
        </div>
        <?php else : ?>
        <div class="vh360-dashboard-card" style="margin-top: 1.5rem;">
            <div class="vh360-dashboard-empty" style="text-align: center; padding: 3rem 1.5rem;">
                <div class="vh360-dashboard-empty-icon" style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                <p class="vh360-dashboard-empty-title" style="font-size: 1.25rem; font-weight: 600; margin: 0 0 0.5rem;">
                    <?php esc_html_e('No posts yet', 'videohub360-theme'); ?>
                </p>
                <p class="vh360-dashboard-empty-text" style="color: var(--text-light); margin: 0;">
                    <?php esc_html_e('Create your first post using the form above.', 'videohub360-theme'); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php else : ?>
    <div class="vh360-dashboard-card">
        <div class="vh360-dashboard-empty">
            <div class="vh360-dashboard-empty-icon">🔒</div>
            <p class="vh360-dashboard-empty-title">
                <?php esc_html_e('Permission Required', 'videohub360-theme'); ?>
            </p>
            <p class="vh360-dashboard-empty-text">
                <?php esc_html_e('You do not have permission to create posts. Please contact an administrator.', 'videohub360-theme'); ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Post Modal -->
    <div class="vh360-modal-overlay" id="vh360-post-edit-modal">
        <div class="vh360-modal">
            <button type="button" class="vh360-modal-close" aria-label="<?php esc_attr_e('Close', 'videohub360-theme'); ?>">&times;</button>
            
            <div class="vh360-modal-header">
                <h2><?php esc_html_e('Edit Post', 'videohub360-theme'); ?></h2>
            </div>
            
            <form id="vh360-post-edit-form" class="vh360-post-edit-form">
                <input type="hidden" name="post_id" id="vh360-edit-post-id" value="0">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('vh360_edit_post_nonce')); ?>">
                
                <div class="vh360-form-group">
                    <label for="vh360-edit-post-title"><?php esc_html_e('Post Title', 'videohub360-theme'); ?> <span class="vh360-required">*</span></label>
                    <input type="text" id="vh360-edit-post-title" name="title" class="vh360-input" required>
                </div>
                
                <div class="vh360-form-group">
                    <label for="vh360-edit-post-content"><?php esc_html_e('Post Content', 'videohub360-theme'); ?></label>
                    <textarea id="vh360-edit-post-content" name="content" class="vh360-textarea" rows="8"></textarea>
                </div>
                
                <div class="vh360-form-group">
                    <label for="vh360-edit-post-excerpt"><?php esc_html_e('Excerpt', 'videohub360-theme'); ?></label>
                    <textarea id="vh360-edit-post-excerpt" name="excerpt" class="vh360-textarea" rows="3" maxlength="500"></textarea>
                    <div class="vh360-character-counter">
                        <span class="vh360-char-count" id="vh360-edit-char-count">0</span> / 500 <?php esc_html_e('characters', 'videohub360-theme'); ?>
                    </div>
                </div>

                <?php
                // Get categories for the edit form
                $edit_categories = get_categories(array(
                    'hide_empty' => false,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ));
                ?>
                
                <?php if (!empty($edit_categories)) : ?>
                <div class="vh360-form-group">
                    <label for="vh360-edit-post-categories"><?php esc_html_e('Categories', 'videohub360-theme'); ?></label>
                    <select id="vh360-edit-post-categories" name="categories[]" class="vh360-input" multiple size="5">
                        <?php foreach ($edit_categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>">
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="vh360-form-group">
                    <label for="vh360-edit-post-tags"><?php esc_html_e('Tags', 'videohub360-theme'); ?></label>
                    <input type="text" id="vh360-edit-post-tags" name="tags" class="vh360-input" placeholder="<?php esc_attr_e('tag1, tag2, tag3', 'videohub360-theme'); ?>">
                </div>
                
                <div class="vh360-form-group">
                    <label for="vh360-edit-featured-image"><?php esc_html_e('Featured Image', 'videohub360-theme'); ?></label>
                    <input type="file" id="vh360-edit-featured-image" name="featured_image" class="vh360-file-input" accept="image/*" style="display: none;">
                    <input type="hidden" id="vh360-edit-featured-image-id" name="featured_image_id" value="0">
                    
                    <div class="vh360-upload-area">
                        <button type="button" id="vh360-edit-upload-trigger" class="vh360-dashboard-btn vh360-btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 0.5rem;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <?php esc_html_e('Upload Image', 'videohub360-theme'); ?>
                        </button>
                        
                        <!-- Image Preview -->
                        <div id="vh360-edit-image-preview" class="vh360-image-preview" style="display: none; margin-top: 1rem; position: relative;">
                            <img id="vh360-edit-preview-img" src="" alt="<?php esc_attr_e('Featured image preview', 'videohub360-theme'); ?>" style="max-width: 100%; height: auto; border-radius: 8px; display: block;">
                            <button type="button" id="vh360-edit-remove-image" class="vh360-remove-image" style="position: absolute; top: 8px; right: 8px; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="vh360-form-group">
                    <label for="vh360-edit-post-status"><?php esc_html_e('Post Status', 'videohub360-theme'); ?></label>
                    <select id="vh360-edit-post-status" name="status" class="vh360-input">
                        <option value="draft"><?php esc_html_e('Draft', 'videohub360-theme'); ?></option>
                        <option value="publish"><?php esc_html_e('Publish', 'videohub360-theme'); ?></option>
                    </select>
                </div>
                
                <div class="vh360-form-actions">
                    <button type="button" class="vh360-dashboard-btn vh360-btn-secondary vh360-modal-cancel">
                        <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
                    </button>
                    <button type="submit" class="vh360-dashboard-btn">
                        <?php esc_html_e('Update Post', 'videohub360-theme'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="vh360-modal-overlay" id="vh360-post-delete-modal">
        <div class="vh360-modal" style="max-width: 500px;">
            <button type="button" class="vh360-modal-close" aria-label="<?php esc_attr_e('Close', 'videohub360-theme'); ?>">&times;</button>
            
            <div class="vh360-modal-header">
                <h2><?php esc_html_e('Delete Post', 'videohub360-theme'); ?></h2>
            </div>
            
            <div class="vh360-modal-content">
                <p><?php esc_html_e('Are you sure you want to delete this post? This action cannot be undone.', 'videohub360-theme'); ?></p>
            </div>
            
            <div class="vh360-form-actions">
                <button type="button" class="vh360-dashboard-btn vh360-btn-secondary vh360-modal-cancel">
                    <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
                </button>
                <button type="button" class="vh360-dashboard-btn" id="vh360-post-confirm-delete" style="background-color: #dc2626;">
                    <?php esc_html_e('Delete Post', 'videohub360-theme'); ?>
                </button>
            </div>
        </div>
    </div>

</div><!-- .vh360-dashboard-create-post -->
