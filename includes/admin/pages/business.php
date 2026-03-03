<?php
/**
 * Business Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Business Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_business_options', array());
$defaults = array(
    'require_professional_approval' => false,
);
$options = wp_parse_args($options, $defaults);

// Get pending professionals for the queue
$pending_professionals = get_users(array(
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => '_vh360_account_type',
            'value' => 'professional',
            'compare' => '='
        ),
        array(
            'key' => '_vh360_professional_status',
            'value' => 'pending',
            'compare' => '='
        )
    ),
    'orderby' => 'registered',
    'order' => 'DESC'
));
?>

<div class="vh360-admin-settings">
    
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Professional Approval Settings', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Configure approval workflow for professional accounts. When approval is required, new professional registrations will be set to "pending" status and will not receive professional capabilities until approved.', 'videohub360-theme'); ?></p>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_business_settings'); ?>
        
        <div class="vh360-admin-card">
            <h3><?php esc_html_e('Approval Workflow', 'videohub360-theme'); ?></h3>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Require Professional Approval', 'videohub360-theme'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="vh360_business_options[require_professional_approval]" 
                                       value="1" 
                                       <?php checked($options['require_professional_approval'], 1); ?>>
                                <?php esc_html_e('New professional registrations require admin approval before gaining access to professional features', 'videohub360-theme'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, professionals will be created with "subscriber" role and "pending" status. They must be approved before receiving the "vh360_professional" role and "vh360_create_events" capability.', 'videohub360-theme'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(__('Save Settings', 'videohub360-theme')); ?>
    </form>
    
    <!-- Pending Professionals Queue -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Pending Professional Approvals', 'videohub360-theme'); ?></h2>
        
        <?php if (empty($pending_professionals)) : ?>
            <div class="notice notice-info inline">
                <p><?php esc_html_e('No pending professional approvals at this time.', 'videohub360-theme'); ?></p>
            </div>
        <?php else : ?>
            <p><?php printf(esc_html(_n('%d professional awaiting approval.', '%d professionals awaiting approval.', count($pending_professionals), 'videohub360-theme')), count($pending_professionals)); ?></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('User', 'videohub360-theme'); ?></th>
                        <th><?php esc_html_e('Email', 'videohub360-theme'); ?></th>
                        <th><?php esc_html_e('Registered', 'videohub360-theme'); ?></th>
                        <th><?php esc_html_e('Business Name', 'videohub360-theme'); ?></th>
                        <th><?php esc_html_e('Actions', 'videohub360-theme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_professionals as $user) : 
                        $business_name = get_user_meta($user->ID, '_vh360_business_name', true);
                        $registered_date = get_date_from_gmt($user->user_registered, get_option('date_format') . ' ' . get_option('time_format'));
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($user->display_name); ?></strong><br>
                            <small><?php echo esc_html($user->user_login); ?></small>
                        </td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td><?php echo esc_html($registered_date); ?></td>
                        <td><?php echo $business_name ? esc_html($business_name) : '<em>' . esc_html__('Not set', 'videohub360-theme') . '</em>'; ?></td>
                        <td>
                            <button type="button" 
                                    class="button button-primary vh360-approve-professional" 
                                    data-user-id="<?php echo esc_attr($user->ID); ?>"
                                    data-nonce="<?php echo esc_attr(wp_create_nonce('vh360_approve_professional_' . $user->ID)); ?>">
                                <?php esc_html_e('Approve', 'videohub360-theme'); ?>
                            </button>
                            <button type="button" 
                                    class="button vh360-reject-professional" 
                                    data-user-id="<?php echo esc_attr($user->ID); ?>"
                                    data-nonce="<?php echo esc_attr(wp_create_nonce('vh360_reject_professional_' . $user->ID)); ?>">
                                <?php esc_html_e('Reject', 'videohub360-theme'); ?>
                            </button>
                            <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>" class="button">
                                <?php esc_html_e('View Profile', 'videohub360-theme'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle approve action
    $('.vh360-approve-professional').on('click', function() {
        var $button = $(this);
        var userId = $button.data('user-id');
        var nonce = $button.data('nonce');
        
        if (!confirm('<?php echo esc_js(__('Are you sure you want to approve this professional?', 'videohub360-theme')); ?>')) {
            return;
        }
        
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'videohub360-theme')); ?>');
        
        $.post(ajaxurl, {
            action: 'vh360_approve_professional',
            user_id: userId,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                $button.closest('tr').fadeOut(400, function() {
                    $(this).remove();
                    // Reload if no more pending
                    if ($('.vh360-approve-professional').length === 0) {
                        location.reload();
                    }
                });
            } else {
                alert(response.data.message || '<?php echo esc_js(__('An error occurred.', 'videohub360-theme')); ?>');
                $button.prop('disabled', false).text('<?php echo esc_js(__('Approve', 'videohub360-theme')); ?>');
            }
        }).fail(function() {
            alert('<?php echo esc_js(__('Network error. Please try again.', 'videohub360-theme')); ?>');
            $button.prop('disabled', false).text('<?php echo esc_js(__('Approve', 'videohub360-theme')); ?>');
        });
    });
    
    // Handle reject action
    $('.vh360-reject-professional').on('click', function() {
        var $button = $(this);
        var userId = $button.data('user-id');
        var nonce = $button.data('nonce');
        
        if (!confirm('<?php echo esc_js(__('Are you sure you want to reject this professional?', 'videohub360-theme')); ?>')) {
            return;
        }
        
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'videohub360-theme')); ?>');
        
        $.post(ajaxurl, {
            action: 'vh360_reject_professional',
            user_id: userId,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                $button.closest('tr').fadeOut(400, function() {
                    $(this).remove();
                    // Reload if no more pending
                    if ($('.vh360-reject-professional').length === 0) {
                        location.reload();
                    }
                });
            } else {
                alert(response.data.message || '<?php echo esc_js(__('An error occurred.', 'videohub360-theme')); ?>');
                $button.prop('disabled', false).text('<?php echo esc_js(__('Reject', 'videohub360-theme')); ?>');
            }
        }).fail(function() {
            alert('<?php echo esc_js(__('Network error. Please try again.', 'videohub360-theme')); ?>');
            $button.prop('disabled', false).text('<?php echo esc_js(__('Reject', 'videohub360-theme')); ?>');
        });
    });
});
</script>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
