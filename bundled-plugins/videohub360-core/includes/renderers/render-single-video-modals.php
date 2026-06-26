<?php
/**
 * Single video modal renderer.
 *
 * @package VideoHub360
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('videohub360_render_single_video_modals')) {
    function videohub360_render_single_video_modals($post_id, $permalink, $title, $is_user_logged_in, $user_display_name, $user_login_url, $can_moderate) {
        $facebook_share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($permalink);
        $twitter_share_url = 'https://twitter.com/intent/tweet?url=' . rawurlencode($permalink) . '&text=' . rawurlencode($title);
        $linkedin_share_url = 'https://www.linkedin.com/shareArticle?mini=true&url=' . rawurlencode($permalink) . '&title=' . rawurlencode($title);
        $whatsapp_share_url = 'https://wa.me/?text=' . rawurlencode($title . ' - ' . $permalink);
        $telegram_share_url = 'https://t.me/share/url?url=' . rawurlencode($permalink) . '&text=' . rawurlencode($title);

        ob_start();
        ?>
    <div class="videohub360-modal-overlay" id="videohub360-modal-overlay">
        <div class="videohub360-modal">
            <div class="videohub360-modal-header">
                <h3 class="videohub360-modal-title"><?php esc_html_e('Share this video', 'videohub360'); ?></h3>
                <button class="videohub360-modal-close" id="videohub360-modal-close" aria-label="<?php esc_attr_e('Close share modal', 'videohub360'); ?>">&times;</button>
            </div>
            <div class="videohub360-modal-body">
                <div class="videohub360-modal-section">
                    <h3><?php esc_html_e('Copy link', 'videohub360'); ?></h3>
                    <div class="videohub360-link-copy">
                        <input type="text" class="videohub360-link-input" id="videohub360-link-input" value="<?php echo esc_attr($permalink); ?>" readonly>
                        <button class="videohub360-copy-btn" id="videohub360-copy-btn"><?php esc_html_e('Copy', 'videohub360'); ?></button>
                    </div>
                </div>
                <div class="videohub360-modal-section">
                    <h3><?php esc_html_e('Share on social media', 'videohub360'); ?></h3>
                    <div class="videohub360-social-icons">
                        <a href="<?php echo esc_url($facebook_share_url); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon facebook" title="<?php esc_attr_e('Share on Facebook', 'videohub360'); ?>">
                            <svg viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url($twitter_share_url); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon twitter" title="<?php esc_attr_e('Share on Twitter', 'videohub360'); ?>">
                            <svg viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url($linkedin_share_url); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon linkedin" title="<?php esc_attr_e('Share on LinkedIn', 'videohub360'); ?>">
                            <svg viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url($whatsapp_share_url); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon whatsapp" title="<?php esc_attr_e('Share on WhatsApp', 'videohub360'); ?>">
                            <svg viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url($telegram_share_url); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon telegram" title="<?php esc_attr_e('Share on Telegram', 'videohub360'); ?>">
                            <svg viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="videohub360-modal-section videohub360-email-section">
                    <button type="button" class="videohub360-email-toggle" id="videohub360-email-toggle">
                        <span><?php esc_html_e('Send via email', 'videohub360'); ?></span>
                        <svg class="videohub360-email-toggle-icon" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </button>
                    <div class="videohub360-email-form-container" id="videohub360-email-form-container">
                        <form class="videohub360-email-form" id="videohub360-email-form">
                            <div class="videohub360-form-group">
                                <label for="videohub360-from-name-input"><?php esc_html_e('Your name:', 'videohub360'); ?></label>
                                <input type="text" class="videohub360-form-input" id="videohub360-from-name-input"
                                       placeholder="<?php echo esc_attr__('Enter your name', 'videohub360'); ?>" 
                                       value="<?php echo esc_attr($is_user_logged_in ? $user_display_name : ''); ?>" required>
                            </div>
                            <div class="videohub360-form-group">
                                <label for="videohub360-email-input"><?php esc_html_e('Recipient email:', 'videohub360'); ?></label>
                                <input type="email" class="videohub360-form-input videohub360-email-input" id="videohub360-email-input"
                                       placeholder="<?php echo esc_attr__('Enter email address', 'videohub360'); ?>" required>
                            </div>
                            <div class="videohub360-form-group">
                                <label for="videohub360-message-input"><?php esc_html_e('Message (optional):', 'videohub360'); ?></label>
                                <textarea class="videohub360-form-input" id="videohub360-message-input" rows="3"
                                          placeholder="<?php echo esc_attr__('Add a personal message...', 'videohub360'); ?>"><?php echo esc_textarea(__('Check out this video I thought you might enjoy!', 'videohub360')); ?></textarea>
                            </div>
                            <button type="submit" class="videohub360-send-btn" id="videohub360-send-btn"><?php esc_html_e('Send Link', 'videohub360'); ?></button>
                            <div class="videohub360-email-message" id="videohub360-email-message" style="display: none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save to Playlist Modal -->
    <div class="vh360-playlist-modal-overlay" id="vh360-playlist-modal-overlay" style="display: none;">
        <div class="vh360-playlist-modal">
            <div class="vh360-playlist-modal-header">
                <h3 class="vh360-playlist-modal-title"><?php esc_html_e('Save to Playlist', 'videohub360'); ?></h3>
                <button class="vh360-playlist-modal-close" id="vh360-playlist-modal-close" aria-label="<?php esc_attr_e('Close playlist modal', 'videohub360'); ?>">&times;</button>
            </div>
            <div class="vh360-playlist-modal-body">
                <div class="vh360-playlist-list" id="vh360-playlist-list">
                    <p class="vh360-playlist-loading"><?php esc_html_e('Loading playlists...', 'videohub360'); ?></p>
                </div>
                <div class="vh360-create-playlist-section">
                    <button class="vh360-create-playlist-toggle" id="vh360-create-playlist-toggle">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        <?php esc_html_e('Create New Playlist', 'videohub360'); ?>
                    </button>
                    <div class="vh360-create-playlist-form" id="vh360-create-playlist-form" style="display: none;">
                        <input type="text" 
                               id="vh360-new-playlist-title" 
                               class="vh360-playlist-input" 
                               placeholder="<?php esc_attr_e('Playlist title', 'videohub360'); ?>" 
                               maxlength="255">
                        <div class="vh360-create-playlist-actions">
                            <button class="vh360-btn vh360-btn-cancel" id="vh360-cancel-create-playlist">
                                <?php esc_html_e('Cancel', 'videohub360'); ?>
                            </button>
                            <button class="vh360-btn vh360-btn-primary" id="vh360-submit-create-playlist">
                                <?php esc_html_e('Create', 'videohub360'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="videohub360-login-modal" id="videohub360-login-modal">
        <div class="videohub360-login-modal-content">
            <div class="videohub360-login-modal-header">
                <h3 class="videohub360-login-modal-title"><?php esc_html_e('Login Required', 'videohub360'); ?></h3>
                <button class="videohub360-login-modal-close" id="videohub360-login-modal-close" aria-label="<?php esc_attr_e('Close login modal', 'videohub360'); ?>">&times;</button>
            </div>
            <div class="videohub360-login-modal-body" id="videohub360-login-modal-body">
                <?php
                $login_modal_type = get_option('videohub360_login_modal_type', 'default');
                $login_modal_shortcode = get_option('videohub360_login_modal_shortcode', '');
                
                switch ($login_modal_type) {
                    case 'shortcode':
                        if (!empty($login_modal_shortcode)) {
                            echo '<div class="videohub360-shortcode-login-form">';
                            $shortcode_output = do_shortcode($login_modal_shortcode);
                            if (!empty(trim($shortcode_output))) {
                                echo $shortcode_output;
                            } else {
                                // Shortcode produced no output, show error message and fallback
                                echo '<div class="videohub360-shortcode-error">';
                                echo '<strong>' . esc_html__('Notice:', 'videohub360') . '</strong> ';
                                /* translators: %s: The login shortcode that failed */
                                echo sprintf( esc_html__( 'The login shortcode %s did not produce any output. Please check the shortcode or contact the administrator.', 'videohub360' ), '<code>' . esc_html( $login_modal_shortcode ) . '</code>' );
                                echo '</div>';
                                
                                // Fallback to default login
                                ?>
                                <div class="videohub360-login-modal-message">
                                    <p><?php esc_html_e('Please log in to your account to participate in the live chat.', 'videohub360'); ?></p>
                                </div>
                                <div class="videohub360-login-modal-actions">
                                    <a href="<?php echo esc_url($user_login_url); ?>" class="videohub360-login-modal-btn">
                                        <?php echo esc_html__( 'Log In', 'videohub360' ); ?>
                                    </a>
                                    <?php if ( get_option( 'users_can_register' ) ) : ?>
                                        <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="videohub360-login-modal-btn secondary">
                                            <?php echo esc_html__( 'Register', 'videohub360' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                            echo '</div>';
                        } else {
                            // Fallback to default if shortcode is empty
                            ?>
                            <div class="videohub360-login-modal-message">
                                <p><?php esc_html_e( 'Please log in to your account to participate in the live chat.', 'videohub360' ); ?></p>
                            </div>
                            <div class="videohub360-login-modal-actions">
                                <a href="<?php echo esc_url( $user_login_url ); ?>" class="videohub360-login-modal-btn">
                                    <?php echo esc_html__( 'Log In', 'videohub360' ); ?>
                                </a>
                                <?php if ( get_option( 'users_can_register' ) ) : ?>
                                    <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="videohub360-login-modal-btn secondary">
                                        <?php echo esc_html__( 'Register', 'videohub360' ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                        break;
                        
                    case 'redirect':
                    case 'javascript':
                        // For redirect and javascript types, show minimal content
                        // The actual handling happens in JavaScript
                        ?>
                        <div class="videohub360-login-modal-message">
                            <p><?php esc_html_e( 'Please log in to your account to participate in the live chat.', 'videohub360' ); ?></p>
                        </div>
                        <div class="videohub360-login-modal-actions">
                            <button type="button" class="videohub360-login-modal-btn" id="videohub360-login-action-btn">
                                <?php echo esc_html__( 'Log In', 'videohub360' ); ?>
                            </button>
                            <?php if ( get_option( 'users_can_register' ) ) : ?>
                                <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="videohub360-login-modal-btn secondary">
                                    <?php echo esc_html__( 'Register', 'videohub360' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php
                        break;
                        
                    case 'builtin':
                        // Allow developers to override the entire form HTML
                        if (has_filter('videohub360_builtin_login_form')) {
                            echo apply_filters('videohub360_builtin_login_form', '');
                        } else {
                            // Default built-in login form
                            ?>
                            <div class="videohub360-builtin-login-form">
                                <form id="videohub360-builtin-login-form" method="post" novalidate>
                                    <?php wp_nonce_field('videohub360_login_nonce', 'videohub360_login_nonce'); ?>
                                    
                                    <div class="vh360-form-field">
                                        <label for="vh360-username"><?php esc_html_e('Username or Email', 'videohub360'); ?></label>
                                        <input 
                                            type="text" 
                                            id="vh360-username" 
                                            name="username" 
                                            required 
                                            autocomplete="username" 
                                            aria-required="true"
                                        />
                                    </div>
                                    
                                    <div class="vh360-form-field">
                                        <label for="vh360-password"><?php esc_html_e('Password', 'videohub360'); ?></label>
                                        <input 
                                            type="password" 
                                            id="vh360-password" 
                                            name="password" 
                                            required 
                                            autocomplete="current-password" 
                                            aria-required="true"
                                        />
                                    </div>
                                    
                                    <div class="vh360-form-field vh360-form-checkbox">
                                        <label for="vh360-remember">
                                            <input 
                                                type="checkbox" 
                                                id="vh360-remember" 
                                                name="remember" 
                                                value="1"
                                            />
                                            <?php esc_html_e('Remember Me', 'videohub360'); ?>
                                        </label>
                                    </div>
                                    
                                    <div class="vh360-form-message" id="vh360-login-message" role="alert"></div>
                                    
                                    <div class="vh360-form-actions">
                                        <button type="submit" class="videohub360-login-modal-btn" id="vh360-login-submit">
                                            <?php esc_html_e('Log In', 'videohub360'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="vh360-form-footer">
                                        <a href="<?php echo esc_url(function_exists('vh360_get_lost_password_page_url') ? vh360_get_lost_password_page_url() : wp_lostpassword_url()); ?>" class="vh360-lost-password">
                                            <?php esc_html_e('Lost your password?', 'videohub360'); ?>
                                        </a>
                                        <?php if (get_option('users_can_register')) : ?>
                                            <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="vh360-register-link">
                                                <?php esc_html_e('Register', 'videohub360'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                            <?php
                        }
                        break;
                        
                    default: // 'default'
                        ?>
                        <div class="videohub360-login-modal-message">
                            <p><?php esc_html_e( 'Please log in to your account to participate in the live chat.', 'videohub360' ); ?></p>
                        </div>
                        <div class="videohub360-login-modal-actions">
                            <a href="<?php echo esc_url( $user_login_url ); ?>" class="videohub360-login-modal-btn">
                                <?php echo esc_html__( 'Log In', 'videohub360' ); ?>
                            </a>
                            <?php if ( get_option( 'users_can_register' ) ) : ?>
                                <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="videohub360-login-modal-btn secondary">
                                    <?php echo esc_html__( 'Register', 'videohub360' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php
                        break;
                }
                ?>
            </div>
        </div>
    </div>


    <!-- Moderation Panel Modal -->
    <?php if ($can_moderate): ?>
    <div id="vh360-moderation-modal" class="vh360-moderation-modal" style="display: none;">
        <div class="vh360-moderation-modal-content">
            <div class="vh360-moderation-modal-header">
                <h3><?php esc_html_e('Moderation Panel', 'videohub360'); ?></h3>
                <button type="button" class="vh360-moderation-modal-close" id="vh360-moderation-modal-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                    </svg>
                </button>
            </div>
            <div class="vh360-moderation-modal-body">
                <div class="vh360-moderation-loading" id="vh360-moderation-loading">
                    <p><?php esc_html_e('Loading moderation data...', 'videohub360'); ?></p>
                </div>
                
                <div class="vh360-moderation-content" id="vh360-moderation-content" style="display: none;">
                    <div class="vh360-moderation-section">
                        <h4><?php esc_html_e('💬 Text Chat - Banned Users', 'videohub360'); ?> <span class="vh360-moderation-count" id="vh360-chat-banned-count">0</span></h4>
                        <div class="vh360-moderation-list" id="vh360-chat-banned-users-list">
                            <p class="vh360-no-items"><?php esc_html_e('No chat banned users', 'videohub360'); ?></p>
                        </div>
                    </div>
                    
                    <div class="vh360-moderation-section">
                        <h4><?php esc_html_e('💬 Text Chat - Timed Out Users', 'videohub360'); ?> <span class="vh360-moderation-count" id="vh360-chat-timeout-count">0</span></h4>
                        <div class="vh360-moderation-list" id="vh360-chat-timeout-users-list">
                            <p class="vh360-no-items"><?php esc_html_e('No chat timed out users', 'videohub360'); ?></p>
                        </div>
                    </div>
                    
                    <div class="vh360-moderation-section">
                        <h4><?php esc_html_e('🎥 Video Chat - Banned Users', 'videohub360'); ?> <span class="vh360-moderation-count" id="vh360-agora-banned-count">0</span></h4>
                        <div class="vh360-moderation-list" id="vh360-agora-banned-users-list">
                            <p class="vh360-no-items"><?php esc_html_e('No video banned users', 'videohub360'); ?></p>
                        </div>
                    </div>
                    
                    <div class="vh360-moderation-section">
                        <h4><?php esc_html_e('🎥 Video Chat - Timed Out Users', 'videohub360'); ?> <span class="vh360-moderation-count" id="vh360-agora-timeout-count">0</span></h4>
                        <div class="vh360-moderation-list" id="vh360-agora-timeout-users-list">
                            <p class="vh360-no-items"><?php esc_html_e('No video timed out users', 'videohub360'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="vh360-moderation-error" id="vh360-moderation-error" style="display: none;">
                    <p><?php esc_html_e('Failed to load moderation data. Please try again.', 'videohub360'); ?></p>
                    <button type="button" class="vh360-moderation-retry" id="vh360-moderation-retry"><?php esc_html_e('Retry', 'videohub360'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

        <?php
        return ob_get_clean();
    }
}
