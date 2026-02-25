<?php
/**
 * Dashboard Availability Settings
 *
 * Template for professionals to manage their appointment availability.
 *
 * @package Videohub360_Theme
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only for logged-in users
if (!is_user_logged_in()) {
    return;
}

$current_user_id = get_current_user_id();

// Only for professionals/organizations
$account_type = vh360_get_user_account_type($current_user_id);
if (!in_array($account_type, array('professional', 'organization'), true)) {
    return;
}

// Get current settings
$settings = vh360_get_availability_settings($current_user_id);
$days = array(
    'mon' => __('Monday', 'videohub360-theme'),
    'tue' => __('Tuesday', 'videohub360-theme'),
    'wed' => __('Wednesday', 'videohub360-theme'),
    'thu' => __('Thursday', 'videohub360-theme'),
    'fri' => __('Friday', 'videohub360-theme'),
    'sat' => __('Saturday', 'videohub360-theme'),
    'sun' => __('Sunday', 'videohub360-theme'),
);
?>

<div class="vh360-dashboard-availability">
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php esc_html_e('Availability Settings', 'videohub360-theme'); ?></h1>
        <p class="vh360-dashboard-description">
            <?php esc_html_e('Set your weekly availability schedule for appointment bookings. Clients will be able to book appointments during your available times.', 'videohub360-theme'); ?>
        </p>
    </div>
    
    <div class="vh360-dashboard-card">
        <form id="vh360-availability-form" class="vh360-form">
            
            <!-- General Settings -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('General Settings', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="slot_minutes" class="vh360-form-label">
                        <?php esc_html_e('Appointment Duration (minutes)', 'videohub360-theme'); ?>
                    </label>
                    <select id="slot_minutes" name="slot_minutes" class="vh360-form-control">
                        <option value="15" <?php selected($settings['slot_minutes'], 15); ?>>15 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="30" <?php selected($settings['slot_minutes'], 30); ?>>30 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="45" <?php selected($settings['slot_minutes'], 45); ?>>45 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="60" <?php selected($settings['slot_minutes'], 60); ?>>1 <?php esc_html_e('hour', 'videohub360-theme'); ?></option>
                        <option value="90" <?php selected($settings['slot_minutes'], 90); ?>>1.5 <?php esc_html_e('hours', 'videohub360-theme'); ?></option>
                        <option value="120" <?php selected($settings['slot_minutes'], 120); ?>>2 <?php esc_html_e('hours', 'videohub360-theme'); ?></option>
                    </select>
                </div>
                
                <div class="vh360-form-group">
                    <label for="buffer_minutes" class="vh360-form-label">
                        <?php esc_html_e('Buffer Time Between Appointments (minutes)', 'videohub360-theme'); ?>
                    </label>
                    <select id="buffer_minutes" name="buffer_minutes" class="vh360-form-control">
                        <option value="0" <?php selected($settings['buffer_minutes'], 0); ?>><?php esc_html_e('No buffer', 'videohub360-theme'); ?></option>
                        <option value="5" <?php selected($settings['buffer_minutes'], 5); ?>>5 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="10" <?php selected($settings['buffer_minutes'], 10); ?>>10 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="15" <?php selected($settings['buffer_minutes'], 15); ?>>15 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="30" <?php selected($settings['buffer_minutes'], 30); ?>>30 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                    </select>
                    <small class="vh360-form-help">
                        <?php esc_html_e('Buffer time prevents back-to-back bookings and gives you a break between appointments.', 'videohub360-theme'); ?>
                    </small>
                </div>
            </div>
            
            <!-- Weekly Schedule -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Weekly Schedule', 'videohub360-theme'); ?></h3>
                <p class="vh360-form-help">
                    <?php esc_html_e('Set your available hours for each day of the week. You can add multiple time blocks per day.', 'videohub360-theme'); ?>
                </p>
                
                <div id="vh360-weekly-schedule" class="vh360-weekly-schedule">
                    <?php foreach ($days as $day_key => $day_label) : 
                        $day_slots = isset($settings['weekly'][$day_key]) ? $settings['weekly'][$day_key] : array();
                    ?>
                    <div class="vh360-day-schedule" data-day="<?php echo esc_attr($day_key); ?>">
                        <h4 class="vh360-day-label"><?php echo esc_html($day_label); ?></h4>
                        <div class="vh360-day-slots" data-day="<?php echo esc_attr($day_key); ?>">
                            <?php if (empty($day_slots)) : ?>
                                <div class="vh360-no-slots">
                                    <p><?php esc_html_e('No availability set', 'videohub360-theme'); ?></p>
                                </div>
                            <?php else : ?>
                                <?php foreach ($day_slots as $index => $slot) : ?>
                                    <div class="vh360-time-slot">
                                        <input type="time" class="vh360-time-input" name="<?php echo esc_attr($day_key); ?>_start[]" value="<?php echo esc_attr($slot['start']); ?>" required>
                                        <span class="vh360-time-separator">-</span>
                                        <input type="time" class="vh360-time-input" name="<?php echo esc_attr($day_key); ?>_end[]" value="<?php echo esc_attr($slot['end']); ?>" required>
                                        <button type="button" class="vh360-btn-remove-slot" data-day="<?php echo esc_attr($day_key); ?>">
                                            <?php esc_html_e('Remove', 'videohub360-theme'); ?>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="vh360-btn-add-slot vh360-btn-secondary" data-day="<?php echo esc_attr($day_key); ?>">
                            + <?php esc_html_e('Add Time Block', 'videohub360-theme'); ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Submit -->
            <div class="vh360-form-actions">
                <button type="submit" class="vh360-btn vh360-btn-primary" id="vh360-save-availability">
                    <span class="vh360-btn-text"><?php esc_html_e('Save Availability', 'videohub360-theme'); ?></span>
                    <span class="vh360-btn-loading" style="display: none;">
                        <svg class="vh360-spinner" width="20" height="20" viewBox="0 0 50 50">
                            <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                        </svg>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.vh360-weekly-schedule {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.vh360-day-schedule {
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f9fafb;
}

.vh360-day-label {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.vh360-day-slots {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.vh360-time-slot {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vh360-time-input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.vh360-time-separator {
    color: #6b7280;
    font-weight: 500;
}

.vh360-btn-remove-slot {
    padding: 0.5rem 1rem;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    cursor: pointer;
}

.vh360-btn-remove-slot:hover {
    background: #dc2626;
}

.vh360-btn-add-slot {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    cursor: pointer;
}

.vh360-btn-add-slot:hover {
    background: #e5e7eb;
}

.vh360-no-slots {
    color: #6b7280;
    font-style: italic;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.vh360-spinner {
    animation: spin 1s linear infinite;
}
</style>

<script>
(function($) {
    'use strict';
    
    // Add time slot
    $(document).on('click', '.vh360-btn-add-slot', function() {
        var day = $(this).data('day');
        var $container = $('.vh360-day-slots[data-day="' + day + '"]');
        
        // Remove "no slots" message if exists
        $container.find('.vh360-no-slots').remove();
        
        // Add new slot
        var $slot = $('<div class="vh360-time-slot"></div>');
        $slot.append('<input type="time" class="vh360-time-input" name="' + day + '_start[]" value="09:00" required>');
        $slot.append('<span class="vh360-time-separator">-</span>');
        $slot.append('<input type="time" class="vh360-time-input" name="' + day + '_end[]" value="17:00" required>');
        $slot.append('<button type="button" class="vh360-btn-remove-slot" data-day="' + day + '">' + '<?php esc_html_e("Remove", "videohub360-theme"); ?>' + '</button>');
        
        $container.append($slot);
    });
    
    // Remove time slot
    $(document).on('click', '.vh360-btn-remove-slot', function() {
        var $slot = $(this).closest('.vh360-time-slot');
        var day = $(this).data('day');
        var $container = $('.vh360-day-slots[data-day="' + day + '"]');
        
        $slot.remove();
        
        // Show "no slots" message if no slots left
        if ($container.find('.vh360-time-slot').length === 0) {
            $container.append('<div class="vh360-no-slots"><p><?php esc_html_e("No availability set", "videohub360-theme"); ?></p></div>');
        }
    });
    
    // Submit form
    $('#vh360-availability-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#vh360-save-availability');
        
        // Collect weekly data
        var weekly = {
            mon: [],
            tue: [],
            wed: [],
            thu: [],
            fri: [],
            sat: [],
            sun: []
        };
        
        $('.vh360-time-slot').each(function() {
            var $slot = $(this);
            var day = $slot.closest('.vh360-day-slots').data('day');
            var start = $slot.find('input[name="' + day + '_start[]"]').val();
            var end = $slot.find('input[name="' + day + '_end[]"]').val();
            
            if (start && end) {
                weekly[day].push({ start: start, end: end });
            }
        });
        
        // Show loading
        $submitBtn.prop('disabled', true);
        $submitBtn.find('.vh360-btn-text').hide();
        $submitBtn.find('.vh360-btn-loading').show();
        
        $.ajax({
            url: vh360Ajax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vh360_save_availability_settings',
                nonce: vh360Ajax.nonce,
                slot_minutes: $('#slot_minutes').val(),
                buffer_minutes: $('#buffer_minutes').val(),
                weekly: JSON.stringify(weekly)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message || '<?php esc_html_e("Error saving settings", "videohub360-theme"); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e("Network error", "videohub360-theme"); ?>');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $submitBtn.find('.vh360-btn-text').show();
                $submitBtn.find('.vh360-btn-loading').hide();
            }
        });
    });
    
})(jQuery);
</script>
<?php
