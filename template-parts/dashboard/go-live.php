<?php
/**
 * Dashboard Go Live Tab
 *
 * Frontend Live Room creator so users can create a new live room
 * directly from the dashboard. This form was moved from the Live Rooms
 * tab to provide a dedicated "Go Live" experience.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// License soft-lock context (used by template rendering + POST handler)
$vh360_is_licensed = true;
if ( function_exists('vh360_theme_is_license_valid') ) {
    $vh360_is_licensed = (bool) vh360_theme_is_license_valid();
} elseif ( function_exists('videohub360_license_is_valid') ) {
    $vh360_is_licensed = (bool) videohub360_license_is_valid();
}
$vh360_license_url = admin_url('admin.php?page=videohub360-license');


// Ensure only logged-in users access this tab
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('You must be logged in to go live.', 'videohub360-theme') . '</p>';
    return;
}

$current_user_id = get_current_user_id();

// Determine if current user is allowed to host Live Rooms.
// Use helper function with administrator override, or fall back to custom capability.
// Can be customized via the vh360_can_host_live_rooms filter.
$can_host_live_rooms = function_exists('vh360_user_can_host_live_rooms')
    ? vh360_user_can_host_live_rooms($current_user_id)
    : (current_user_can('manage_options') || current_user_can('vh360_host_live_rooms'));

$can_host_live_rooms = apply_filters('vh360_can_host_live_rooms', $can_host_live_rooms);

$errors = array();
$success_message = '';

/**
 * Handle Live Room creation form submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['vh360_action'])
    && $_POST['vh360_action'] === 'create_live_room') {

 ( function_exists('vh360_theme_is_license_valid') ? vh360_theme_is_license_valid() : ( function_exists('videohub360_license_is_valid') && videohub360_license_is_valid() ) );
    if ( ! $vh360_is_licensed ) {
        $errors[] = esc_html__( 'Your VideoHub360 license is inactive. Activate your license to create a Live Room.', 'videohub360-theme' );
    }
// Only proceed if the current user is allowed to host Live Rooms
    if (!$can_host_live_rooms) {
        $errors[] = esc_html__('You do not have permission to create Live Rooms.', 'videohub360-theme');
    } else {
        // Verify nonce
        if (!isset($_POST['vh360_create_live_room_nonce'])
            || !wp_verify_nonce($_POST['vh360_create_live_room_nonce'], 'vh360_create_live_room')) {
            $errors[] = esc_html__('Security check failed. Please try again.', 'videohub360-theme');
        } else {
            $title        = isset($_POST['vh360_live_room_title']) ? sanitize_text_field($_POST['vh360_live_room_title']) : '';
            $description  = isset($_POST['vh360_live_room_description']) ? wp_kses_post($_POST['vh360_live_room_description']) : '';
            $channel_name = isset($_POST['vh360_live_room_channel']) ? sanitize_text_field($_POST['vh360_live_room_channel']) : '';
            $agora_mode   = isset($_POST['vh360_agora_mode']) ? sanitize_text_field($_POST['vh360_agora_mode']) : 'interactive';

            if (!in_array($agora_mode, array('interactive', 'broadcast'), true)) {
                $agora_mode = 'interactive';
            }


            // Room settings (frontend overrides)
            $everyone_is_host = isset($_POST['vh360_agora_everyone_is_host']) ? 'yes' : 'no';
            $require_passcode = isset($_POST['vh360_require_passcode']) ? 'yes' : 'no';
            $host_passcode    = '';
            if ($require_passcode === 'yes') {
                $host_passcode = isset($_POST['vh360_host_passcode']) ? sanitize_text_field($_POST['vh360_host_passcode']) : '';
            }
            $viewer_count = isset($_POST['vh360_viewer_count']) ? 'yes' : 'no';
            $chat_enabled = isset($_POST['vh360_chat_enabled']) ? 'yes' : 'no';
            $chat_placement = isset($_POST['vh360_chat_placement']) ? sanitize_text_field($_POST['vh360_chat_placement']) : '';

            // Enforce mutual exclusivity: everyone host cannot be combined with passcode requirement
            if ($everyone_is_host === 'yes' && $require_passcode === 'yes') {
                $errors[] = esc_html__('You cannot enable "Allow Everyone to be Host" while requiring a passcode. Please choose one.', 'videohub360-theme');
            }
            if ($require_passcode === 'yes' && $host_passcode === '') {
                $errors[] = esc_html__('Please enter a passcode or disable the passcode requirement.', 'videohub360-theme');
            }

            if (empty($title)) {
                $errors[] = esc_html__('Please provide a title for your Live Room.', 'videohub360-theme');
            }

            // Auto-generate an Agora channel name if not provided
            if (empty($channel_name)) {
                $channel_name = 'live-room-' . $current_user_id . '-' . time();
            }

            if (empty($errors)) {
                $post_id = wp_insert_post(array(
                    'post_type'   => 'videohub360',
                    'post_status' => 'publish',
                    'post_title'  => $title,
                    'post_content' => $description,
                    'post_author' => $current_user_id,
                ));

                if (!is_wp_error($post_id) && $post_id) {
                    // Mark as livestream + Live Room context
                    // New Live Rooms should default to "Yes - Livestream Mode" so the frontend player
                    // and backend fields reflect a live-ready room immediately after creation.
                    // The actual Agora stream status is still controlled separately via _vh360_agora_stream_live.
                    update_post_meta($post_id, '_vh360_is_live', 'yes');
                    update_post_meta($post_id, '_vh360_live_start_time', current_time('mysql'));
                    update_post_meta($post_id, '_vh360_context', 'live_room');

                    // Force type to Agora; use chosen mode (interactive or broadcast)
                    update_post_meta($post_id, '_vh360_type', 'agora');
                    update_post_meta($post_id, '_vh360_agora_channel_name', $channel_name);
                    update_post_meta($post_id, '_vh360_agora_mode', $agora_mode);

                    // Sensible defaults for visuals and chat
                    update_post_meta($post_id, '_vh360_live_badge', 'yes');
                    update_post_meta($post_id, '_vh360_badge_text', __('LIVE', 'videohub360-theme'));
                    update_post_meta($post_id, '_vh360_badge_color', '#e53935');
                    update_post_meta($post_id, '_vh360_viewer_count', $viewer_count);
                    update_post_meta($post_id, '_vh360_chat_enabled', $chat_enabled);
                    update_post_meta($post_id, '_vh360_chat_placement', $chat_placement);
                    update_post_meta($post_id, '_vh360_agora_everyone_is_host', $everyone_is_host);
                    update_post_meta($post_id, '_vh360_host_passcode', ($require_passcode === 'yes') ? $host_passcode : '');
                    update_post_meta($post_id, '_vh360_stream_stopped', 'no');
                    // Ensure the room doesn't inherit stale mappings/status from any templates/copies
                    delete_post_meta($post_id, '_vh360_went_live_post_id');
                    update_post_meta($post_id, '_vh360_agora_stream_live', 'no');


                    // Handle featured image upload
                    if (!empty($_FILES['vh360_featured_image']['name'])) {
                        // Check user capability
                        if (current_user_can('upload_files')) {
                            // Load required WordPress files
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                            require_once ABSPATH . 'wp-admin/includes/media.php';
                            require_once ABSPATH . 'wp-admin/includes/image.php';

                            // Handle the upload
                            $attachment_id = media_handle_upload('vh360_featured_image', $post_id);

                            if (!is_wp_error($attachment_id)) {
                                // Set as featured image
                                set_post_thumbnail($post_id, $attachment_id);
                            }
                            // Silently ignore upload errors to not block live room creation
                        }
                    }

                    // Redirect directly into the Live Room
                    $live_room_url = get_permalink($post_id);
                    if ($live_room_url) {
                        wp_safe_redirect($live_room_url);
                        exit;
                    } else {
                        $success_message = esc_html__('Live Room created successfully.', 'videohub360-theme');
                    }
                } else {
                    $errors[] = esc_html__('Could not create Live Room. Please try again.', 'videohub360-theme');
                }
            }
        }
    }
}

?>
<div class="vh360-dashboard-section vh360-dashboard-go-live">
    <header class="vh360-dashboard-section-header">
        <h2 class="vh360-dashboard-section-title"><?php esc_html_e('Go Live', 'videohub360-theme'); ?></h2>
        <p class="vh360-dashboard-section-subtitle">
            <?php esc_html_e('Create a new Live Room to start streaming to your community.', 'videohub360-theme'); ?>
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

    <?php if ($can_host_live_rooms) : ?>
    <div class="vh360-dashboard-card vh360-live-room-create-card">
        <h3 class="vh360-dashboard-card-title"><?php esc_html_e('Create a Live Room', 'videohub360-theme'); ?></h3>
        <p class="vh360-dashboard-card-text">
            <?php esc_html_e('Set up a new Live Room for your community. You can configure the stream source and advanced options from the Live Room itself.', 'videohub360-theme'); ?>
        </p>
        <?php if ( ! $vh360_is_licensed ) : ?>
            <div class="vh360-dashboard-notice vh360-dashboard-notice-warning vh360-license-softlock-notice">
                <?php echo esc_html__( 'Your VideoHub360 license is inactive. Activate your license to create a Live Room.', 'videohub360-theme' ); ?>
                <a href="<?php echo esc_url( $vh360_license_url ); ?>" style="margin-left:8px;">
                    <?php esc_html_e( 'Activate License', 'videohub360-theme' ); ?>
                </a>
            </div>
        <?php endif; ?>


        <form method="post" class="vh360-live-room-form" enctype="multipart/form-data">
            <?php wp_nonce_field('vh360_create_live_room', 'vh360_create_live_room_nonce'); ?>
            <input type="hidden" name="vh360_action" value="create_live_room" />

            <div class="vh360-form-group">
                <label for="vh360_live_room_title"><?php esc_html_e('Live Room Title', 'videohub360-theme'); ?></label>
                <input type="text" id="vh360_live_room_title" name="vh360_live_room_title" class="vh360-input" required placeholder="<?php esc_attr_e('Ask Me Anything, Office Hours, Product Walkthrough', 'videohub360-theme'); ?>">
            </div>

            <div class="vh360-form-group">
                <label for="vh360_live_room_description"><?php esc_html_e('Description (optional)', 'videohub360-theme'); ?></label>
                <textarea id="vh360_live_room_description" name="vh360_live_room_description" class="vh360-textarea" rows="4" placeholder="<?php esc_attr_e('Let your community know what to expect in this Live Room.', 'videohub360-theme'); ?>"></textarea>
            </div>

            <div class="vh360-form-group">
                <label for="vh360_featured_image"><?php esc_html_e('Featured Image', 'videohub360-theme'); ?></label>
                <input type="file" id="vh360_featured_image" name="vh360_featured_image" class="vh360-file-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                <button type="button" class="vh360-upload-button" id="vh360-upload-trigger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <?php esc_html_e('Upload', 'videohub360-theme'); ?>
                </button>
                <div id="vh360-image-preview" class="vh360-image-preview" style="display: none;">
                    <img src="" alt="<?php esc_attr_e('Preview', 'videohub360-theme'); ?>" id="vh360-preview-img">
                    <button type="button" class="vh360-remove-image" id="vh360-remove-image" aria-label="<?php esc_attr_e('Remove image', 'videohub360-theme'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <p class="vh360-form-help">
                    <?php esc_html_e('Upload a thumbnail image for your Live Room. Supported formats: JPG, PNG, GIF, WebP (Max 5MB)', 'videohub360-theme'); ?>
                </p>
            </div>

            <div class="vh360-form-group">
                <label for="vh360_agora_mode"><?php esc_html_e('Livestream Mode', 'videohub360-theme'); ?></label>
                <select id="vh360_agora_mode" name="vh360_agora_mode" class="vh360-input">
                    <option value="interactive"><?php esc_html_e('Interactive (host + guests can speak)', 'videohub360-theme'); ?></option>
                    <option value="broadcast"><?php esc_html_e('Broadcast (host speaks, audience watches)', 'videohub360-theme'); ?></option>
                </select>
                <p class="vh360-form-help">
                    <?php esc_html_e('These options map directly to the Agora interactive/broadcast modes used on single video pages.', 'videohub360-theme'); ?>
                </p>
            
            <div class="vh360-form-group">
                <h4 style="margin: 0 0 10px 0;"><?php esc_html_e('Live Room Settings', 'videohub360-theme'); ?></h4>

                <label style="display:block; margin-bottom:8px;">
                    <input type="checkbox" name="vh360_agora_everyone_is_host" id="vh360_agora_everyone_is_host" value="yes" />
                    <?php esc_html_e('Allow Everyone to be Host', 'videohub360-theme'); ?>
                </label>
                <p class="vh360-form-help" style="margin-top:-4px;">
                    <?php esc_html_e('When enabled, all viewers can directly join as hosts. Cannot be used with passcode requirement.', 'videohub360-theme'); ?>
                </p>

                <div style="margin: 12px 0 0 0; border-left: 3px solid rgba(0,0,0,.15); padding-left: 12px;">
                    <label style="display:block; margin-bottom:8px;">
                        <input type="checkbox" name="vh360_require_passcode" id="vh360_require_passcode" value="yes" />
                        <?php esc_html_e('Require Passcode To Join', 'videohub360-theme'); ?>
                    </label>
                    <div id="vh360_passcode_field" style="display:none; margin-top: 8px;">
                        <label for="vh360_host_passcode"><?php esc_html_e('Host Passcode', 'videohub360-theme'); ?></label>
                        <input type="text" id="vh360_host_passcode" name="vh360_host_passcode" class="vh360-input" placeholder="<?php esc_attr_e('Enter a passcode', 'videohub360-theme'); ?>" />
                    </div>
                    <p class="vh360-form-help" style="margin-top:6px;">
                        <?php esc_html_e('When enabled, viewers must enter a passcode to join as presenters. Cannot be used with "Allow Everyone to be Host".', 'videohub360-theme'); ?>
                    </p>
                </div>

                <label style="display:block; margin-top: 14px; margin-bottom:8px;">
                    <input type="checkbox" name="vh360_viewer_count" id="vh360_viewer_count" value="yes" checked />
                    <?php esc_html_e('Show Viewer Count', 'videohub360-theme'); ?>
                </label>

                <label style="display:block; margin-bottom:8px;">
                    <input type="checkbox" name="vh360_chat_enabled" id="vh360_chat_enabled" value="yes" checked />
                    <?php esc_html_e('Enable live chat for this video', 'videohub360-theme'); ?>
                </label>

                <div style="margin-top: 10px;">
                    <label for="vh360_chat_placement"><?php esc_html_e('Chat Placement', 'videohub360-theme'); ?></label>
                    <select name="vh360_chat_placement" id="vh360_chat_placement" class="vh360-input">
                        <option value=""><?php esc_html_e('Use global setting', 'videohub360-theme'); ?></option>
                        <option value="right"><?php esc_html_e('Right', 'videohub360-theme'); ?></option>
                        <option value="left"><?php esc_html_e('Left', 'videohub360-theme'); ?></option>
                        <option value="bottom"><?php esc_html_e('Bottom', 'videohub360-theme'); ?></option>
                    </select>
                </div>
            </div>

            <script>
            (function(){
                var everyoneHost = document.getElementById('vh360_agora_everyone_is_host');
                var requirePass = document.getElementById('vh360_require_passcode');
                var passcodeField = document.getElementById('vh360_passcode_field');

                function sync() {
                    if (requirePass && passcodeField) {
                        passcodeField.style.display = requirePass.checked ? 'block' : 'none';
                    }
                    // Mutual exclusivity (UI-level)
                    if (everyoneHost && requirePass) {
                        if (everyoneHost.checked) {
                            requirePass.disabled = true;
                            requirePass.checked = false;
                            if (passcodeField) passcodeField.style.display = 'none';
                        } else {
                            requirePass.disabled = false;
                        }
                        if (requirePass.checked) {
                            everyoneHost.disabled = true;
                            everyoneHost.checked = false;
                        } else {
                            everyoneHost.disabled = false;
                        }
                    }
                }

                if (everyoneHost) everyoneHost.addEventListener('change', sync);
                if (requirePass) requirePass.addEventListener('change', sync);
                sync();
            })();
            </script>
</div>

            <div class="vh360-form-group">
                <label for="vh360_live_room_channel"><?php esc_html_e('Agora Channel Name (optional)', 'videohub360-theme'); ?></label>
                <input type="text" id="vh360_live_room_channel" name="vh360_live_room_channel" class="vh360-input" placeholder="<?php esc_attr_e('Leave blank to auto-generate a secure channel name.', 'videohub360-theme'); ?>">
                <p class="vh360-form-help">
                    <?php esc_html_e('Requires Agora to be configured in the Videohub360 plugin settings. Channel name identifies this Live Room session.', 'videohub360-theme'); ?>
                </p>
            </div>

            <div class="vh360-form-actions">
                <button type="submit" class="vh360-dashboard-btn <?php echo !$vh360_is_licensed ? 'vh360-locked' : ''; ?>" <?php echo !$vh360_is_licensed ? 'disabled="disabled" aria-disabled="true"' : ''; ?> title="<?php echo !$vh360_is_licensed ? esc_attr__('Activate your license to create a Live Room.', 'videohub360-theme') : ''; ?>">
                    <?php esc_html_e('Create Live Room', 'videohub360-theme'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php else : ?>
    <div class="vh360-dashboard-card">
        <div class="vh360-dashboard-empty">
            <div class="vh360-dashboard-empty-icon">🔒</div>
            <p class="vh360-dashboard-empty-title">
                <?php esc_html_e('Permission Required', 'videohub360-theme'); ?>
            </p>
            <p class="vh360-dashboard-empty-text">
                <?php esc_html_e('You do not have permission to create Live Rooms. Please contact an administrator.', 'videohub360-theme'); ?>
            </p>
        </div>
    </div>
    <?php endif; ?>
</div><!-- .vh360-dashboard-go-live -->