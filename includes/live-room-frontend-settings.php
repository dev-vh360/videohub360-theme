<?php
/**
 * Frontend Live Room Settings + Stream Controls
 *
 * Allows Live Room creators (and admins) to manage the same settings available in wp-admin
 * directly from the Live Room page.
 *
 * NOTE: Live status fields (_vh360_is_live, _vh360_live_start_time) remain system-controlled
 * and are NOT user-editable here.
 *
 * @package Videohub360_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Determine if current user can manage a given Live Room.
 */
function vh360_user_can_manage_live_room($post_id) {
    if (!is_user_logged_in()) {
        return false;
    }
    
    // Get the post to check authorship
    $post = get_post($post_id);
    if (empty($post)) {
        return false;
    }
    
    // Check if user is the post author
    $is_owner = ((int) $post->post_author === (int) get_current_user_id());
    
    // Allow if: admin OR can edit the post OR is the post author
    return current_user_can('manage_options') 
        || current_user_can('edit_post', $post_id) 
        || $is_owner;
}

/**
 * Handle frontend live room settings save.
 */
function vh360_handle_frontend_live_room_settings_save() {
    if (!is_user_logged_in()) {
        return;
    }

    if (empty($_POST['vh360_action']) || $_POST['vh360_action'] !== 'update_live_room_settings') {
        return;
    }

    $post_id = isset($_POST['vh360_live_room_id']) ? absint($_POST['vh360_live_room_id']) : 0;
    if (!$post_id || get_post_type($post_id) !== 'videohub360') {
        return;
    }

    if (!vh360_user_can_manage_live_room($post_id)) {
        return;
    }

    if (empty($_POST['vh360_live_room_settings_nonce']) || !wp_verify_nonce($_POST['vh360_live_room_settings_nonce'], 'vh360_live_room_settings')) {
        return;
    }

    // Only allow settings update for Live Rooms
    $context = get_post_meta($post_id, '_vh360_context', true);
    if ($context !== 'live_room') {
        return;
    }

    $everyone_is_host = isset($_POST['vh360_agora_everyone_is_host']) ? 'yes' : 'no';
    $require_passcode = isset($_POST['vh360_require_passcode']) ? 'yes' : 'no';
    $new_passcode     = ($require_passcode === 'yes') ? sanitize_text_field($_POST['vh360_host_passcode'] ?? '') : '';

    $viewer_count   = isset($_POST['vh360_viewer_count']) ? 'yes' : 'no';
    $chat_enabled   = isset($_POST['vh360_chat_enabled']) ? 'yes' : 'no';
    $chat_placement = isset($_POST['vh360_chat_placement']) ? sanitize_text_field($_POST['vh360_chat_placement']) : '';

    // Enforce mutual exclusivity
    if ($everyone_is_host === 'yes' && $require_passcode === 'yes') {
        // Prefer passcode; disable everyone-is-host
        $everyone_is_host = 'no';
    }

    update_post_meta($post_id, '_vh360_agora_everyone_is_host', $everyone_is_host);
    update_post_meta($post_id, '_vh360_viewer_count', $viewer_count);
    update_post_meta($post_id, '_vh360_chat_enabled', $chat_enabled);
    update_post_meta($post_id, '_vh360_chat_placement', $chat_placement);

    // Handle passcode: hash new values, keep existing when blank, clear when unchecked.
    if ($require_passcode !== 'yes') {
        update_post_meta($post_id, '_vh360_host_passcode', '');
    } elseif ($new_passcode !== '') {
        update_post_meta($post_id, '_vh360_host_passcode', wp_hash_password($new_passcode));
    }
    // If require_passcode=yes but new_passcode is empty, keep existing passcode.

    // Redirect back to avoid resubmission
    $url = get_permalink($post_id);
    if ($url) {
        wp_safe_redirect($url . '#vh360-live-room-settings');
        exit;
    }
}
add_action('template_redirect', 'vh360_handle_frontend_live_room_settings_save');

/**
 * Render settings + stream controls below the Live Room content.
 */
function vh360_render_frontend_live_room_settings_panel($content) {
    if (!is_singular('videohub360')) {
        return $content;
    }

    $post_id = get_the_ID();
    if (!$post_id) {
        return $content;
    }

    $context = get_post_meta($post_id, '_vh360_context', true);
    if ($context !== 'live_room') {
        return $content;
    }

    if (!vh360_user_can_manage_live_room($post_id)) {
        return $content;
    }

    $everyone_is_host = get_post_meta($post_id, '_vh360_agora_everyone_is_host', true) ?: 'no';
    $host_passcode    = get_post_meta($post_id, '_vh360_host_passcode', true);
    $viewer_count     = get_post_meta($post_id, '_vh360_viewer_count', true) ?: 'yes';
    $chat_enabled     = get_post_meta($post_id, '_vh360_chat_enabled', true) ?: 'yes';
    $chat_placement   = get_post_meta($post_id, '_vh360_chat_placement', true);

    $require_passcode = !empty($host_passcode) ? 'yes' : 'no';

    $ajax_url = admin_url('admin-ajax.php');
    $control_nonce = wp_create_nonce('vh360_stream_control');

    ob_start();
    ?>
    <div id="vh360-live-room-settings" class="vh360-card" style="margin-top:16px;">
        <h3 style="margin:0 0 10px 0;"><?php esc_html_e('Live Room Settings', 'videohub360-theme'); ?></h3>

        <form method="post" class="vh360-live-room-settings-form" style="margin-bottom: 14px;">
            <?php wp_nonce_field('vh360_live_room_settings', 'vh360_live_room_settings_nonce'); ?>
            <input type="hidden" name="vh360_action" value="update_live_room_settings" />
            <input type="hidden" name="vh360_live_room_id" value="<?php echo esc_attr($post_id); ?>" />

            <label style="display:block; margin-bottom:8px;">
                <input type="checkbox" name="vh360_agora_everyone_is_host" id="vh360_agora_everyone_is_host" value="yes" <?php checked($everyone_is_host, 'yes'); ?> />
                <?php esc_html_e('Allow Everyone to be Host', 'videohub360-theme'); ?>
            </label>
            <p class="vh360-form-help" style="margin-top:-4px;">
                <?php esc_html_e('When enabled, all viewers can directly join as hosts. Cannot be used with passcode requirement.', 'videohub360-theme'); ?>
            </p>

            <div style="margin: 12px 0 0 0; border-left: 3px solid rgba(0,0,0,.15); padding-left: 12px;">
                <label style="display:block; margin-bottom:8px;">
                    <input type="checkbox" name="vh360_require_passcode" id="vh360_require_passcode" value="yes" <?php checked($require_passcode, 'yes'); ?> />
                    <?php esc_html_e('Require Passcode To Join', 'videohub360-theme'); ?>
                </label>
                <div id="vh360_passcode_field" style="<?php echo ($require_passcode === 'yes') ? '' : 'display:none;'; ?> margin-top: 8px;">
                    <?php if ($require_passcode === 'yes') : ?>
                        <p style="margin-bottom:4px;"><em><?php esc_html_e('A host passcode is currently set.', 'videohub360-theme'); ?></em></p>
                    <?php endif; ?>
                    <label for="vh360_host_passcode"><?php esc_html_e('Set New Host Passcode', 'videohub360-theme'); ?></label>
                    <input type="password" id="vh360_host_passcode" name="vh360_host_passcode" class="vh360-input" value="" placeholder="<?php echo ($require_passcode === 'yes') ? esc_attr__('Leave blank to keep existing passcode', 'videohub360-theme') : esc_attr__('Enter a passcode', 'videohub360-theme'); ?>" autocomplete="new-password" />
                </div>
                <p class="vh360-form-help" style="margin-top:6px;">
                    <?php esc_html_e('When enabled, viewers must enter a passcode to join as presenters. Cannot be used with "Allow Everyone to be Host".', 'videohub360-theme'); ?>
                </p>
            </div>

            <label style="display:block; margin-top: 14px; margin-bottom:8px;">
                <input type="checkbox" name="vh360_viewer_count" id="vh360_viewer_count" value="yes" <?php checked($viewer_count, 'yes'); ?> />
                <?php esc_html_e('Show Viewer Count', 'videohub360-theme'); ?>
            </label>

            <label style="display:block; margin-bottom:8px;">
                <input type="checkbox" name="vh360_chat_enabled" id="vh360_chat_enabled" value="yes" <?php checked($chat_enabled, 'yes'); ?> />
                <?php esc_html_e('Enable live chat for this video', 'videohub360-theme'); ?>
            </label>

            <div style="margin-top: 10px;">
                <label for="vh360_chat_placement"><?php esc_html_e('Chat Placement', 'videohub360-theme'); ?></label>
                <select name="vh360_chat_placement" id="vh360_chat_placement" class="vh360-input">
                    <option value="" <?php selected($chat_placement, ''); ?>><?php esc_html_e('Use global setting', 'videohub360-theme'); ?></option>
                    <option value="right" <?php selected($chat_placement, 'right'); ?>><?php esc_html_e('Right', 'videohub360-theme'); ?></option>
                    <option value="left" <?php selected($chat_placement, 'left'); ?>><?php esc_html_e('Left', 'videohub360-theme'); ?></option>
                    <option value="bottom" <?php selected($chat_placement, 'bottom'); ?>><?php esc_html_e('Bottom', 'videohub360-theme'); ?></option>
                </select>
            </div>

            <div style="margin-top: 12px;">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'videohub360-theme'); ?></button>
            </div>
        </form>

        <h4 style="margin:0 0 10px 0;"><?php esc_html_e('Stream Control', 'videohub360-theme'); ?></h4>
        <div class="vh360-stream-controls" style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="button" id="vh360-stop-stream"><?php esc_html_e('Stop Stream', 'videohub360-theme'); ?></button>
            <button type="button" class="button" id="vh360-restart-stream"><?php esc_html_e('Restart Stream', 'videohub360-theme'); ?></button>
        </div>
        <p class="vh360-form-help" style="margin-top:8px;">
            <?php esc_html_e('Stop Stream will mark the room offline and update activity. Restart Stream clears the stop flag; start streaming again to go live.', 'videohub360-theme'); ?>
        </p>

        <script>
        (function(){
            var everyoneHost = document.getElementById('vh360_agora_everyone_is_host');
            var requirePass = document.getElementById('vh360_require_passcode');
            var passcodeField = document.getElementById('vh360_passcode_field');

            function sync() {
                if (requirePass && passcodeField) {
                    passcodeField.style.display = requirePass.checked ? 'block' : 'none';
                }
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

            function postControl(action) {
                var fd = new FormData();
                fd.append('action', action);
                fd.append('nonce', '<?php echo esc_js($control_nonce); ?>');
                fd.append('post_id', '<?php echo esc_js((string) $post_id); ?>');

                return fetch('<?php echo esc_url($ajax_url); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                }).then(function(r){ return r.json(); });
            }

            var stopBtn = document.getElementById('vh360-stop-stream');
            var restartBtn = document.getElementById('vh360-restart-stream');

            if (stopBtn) stopBtn.addEventListener('click', function(){
                stopBtn.disabled = true;
                postControl('vh360_stop_stream').then(function(resp){
                    stopBtn.disabled = false;
                    if (resp && resp.success) {
                        window.location.reload();
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to stop stream.');
                    }
                }).catch(function(){ stopBtn.disabled = false; alert('Failed to stop stream.'); });
            });

            if (restartBtn) restartBtn.addEventListener('click', function(){
                restartBtn.disabled = true;
                postControl('vh360_restart_stream').then(function(resp){
                    restartBtn.disabled = false;
                    if (resp && resp.success) {
                        window.location.reload();
                    } else {
                        alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to restart stream.');
                    }
                }).catch(function(){ restartBtn.disabled = false; alert('Failed to restart stream.'); });
            });
        })();
        </script>
    </div>
    <?php

    $panel = ob_get_clean();
    return $content . $panel;
}
add_filter('the_content', 'vh360_render_frontend_live_room_settings_panel', 50);

