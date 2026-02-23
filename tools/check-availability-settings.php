<?php
/**
 * Availability Settings Checker
 *
 * Run this file from wp-admin to check a professional's availability settings.
 * URL: /wp-content/themes/videohub360-theme/check-availability-settings.php?user_id=X
 *
 * @package Videohub360_Theme
 */

// Load WordPress
require_once('../../../wp-load.php');

// Only admins can run this
if (!current_user_can('manage_options')) {
    wp_die('You must be an administrator to run this diagnostic tool.');
}

// Get user ID from query string
$user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;

if (!$user_id) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Availability Settings Checker</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; max-width: 1000px; margin: 0 auto; }
            h1 { color: #1e40af; }
            .form { background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; }
            input { padding: 8px; font-size: 14px; margin: 0 10px; }
            button { padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #1d4ed8; }
        </style>
    </head>
    <body>
        <h1>Appointment Booking - Availability Settings Checker</h1>
        <p>Enter a professional's user ID to check their availability settings.</p>
        
        <div class="form">
            <form method="get">
                <label for="user_id">User ID:</label>
                <input type="number" name="user_id" id="user_id" required>
                <button type="submit">Check Settings</button>
            </form>
        </div>
        
        <h2>How to find User ID:</h2>
        <ol>
            <li>Go to wp-admin → Users</li>
            <li>Hover over a user name</li>
            <li>Look at the URL in bottom left: ...user_id=<strong>123</strong></li>
        </ol>
    </body>
    </html>
    <?php
    exit;
}

// Get user data
$user = get_userdata($user_id);
if (!$user) {
    wp_die('User not found.');
}

// Get account type
$account_type = get_user_meta($user_id, '_vh360_account_type', true);

// Get availability settings
$timezone = get_user_meta($user_id, '_vh360_availability_timezone', true);
$slot_minutes = get_user_meta($user_id, '_vh360_availability_slot_minutes', true);
$buffer_minutes = get_user_meta($user_id, '_vh360_availability_buffer_minutes', true);
$weekly = get_user_meta($user_id, '_vh360_availability_weekly', true);

// Get via helper function
if (function_exists('vh360_get_availability_settings')) {
    $settings = vh360_get_availability_settings($user_id);
} else {
    $settings = null;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Availability Settings for <?php echo esc_html($user->display_name); ?></title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            padding: 20px; 
            max-width: 1000px; 
            margin: 0 auto;
            background: #f9fafb;
        }
        h1 { color: #1e40af; }
        h2 { color: #374151; margin-top: 30px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .card { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .info-row { display: flex; margin: 10px 0; }
        .info-label { font-weight: 600; width: 200px; color: #6b7280; }
        .info-value { color: #1f2937; }
        .success { color: #059669; font-weight: 600; }
        .error { color: #dc2626; font-weight: 600; }
        .warning { color: #d97706; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; font-weight: 600; color: #374151; }
        .day-name { font-weight: 600; }
        .time-block { background: #dbeafe; padding: 4px 8px; border-radius: 4px; margin: 2px; display: inline-block; }
        .empty { color: #9ca3af; font-style: italic; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px; margin: 10px 10px 10px 0; }
        .btn:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
    </style>
</head>
<body>
    <h1>Availability Settings Diagnostic</h1>
    
    <div class="card">
        <h2>User Information</h2>
        <div class="info-row">
            <div class="info-label">User ID:</div>
            <div class="info-value"><?php echo esc_html($user_id); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Display Name:</div>
            <div class="info-value"><?php echo esc_html($user->display_name); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Username:</div>
            <div class="info-value"><?php echo esc_html($user->user_login); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value"><?php echo esc_html($user->user_email); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Account Type:</div>
            <div class="info-value">
                <?php 
                if ($account_type) {
                    echo '<span class="success">' . esc_html($account_type) . '</span>';
                    if (!in_array($account_type, array('professional', 'organization'))) {
                        echo ' <span class="error">⚠️ Not a professional account!</span>';
                    }
                } else {
                    echo '<span class="error">Not set</span>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h2>Availability Settings (Raw Meta)</h2>
        <div class="info-row">
            <div class="info-label">Timezone:</div>
            <div class="info-value"><?php echo $timezone ? esc_html($timezone) : '<span class="empty">Not set (will use site default)</span>'; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Slot Duration:</div>
            <div class="info-value"><?php echo $slot_minutes ? esc_html($slot_minutes) . ' minutes' : '<span class="empty">Not set (will use 30min default)</span>'; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Buffer Time:</div>
            <div class="info-value"><?php echo isset($buffer_minutes) ? esc_html($buffer_minutes) . ' minutes' : '<span class="empty">Not set (will use 0min default)</span>'; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Weekly Schedule Set:</div>
            <div class="info-value">
                <?php 
                if (is_array($weekly) && !empty($weekly)) {
                    $total_blocks = 0;
                    foreach ($weekly as $day_blocks) {
                        if (is_array($day_blocks)) {
                            $total_blocks += count($day_blocks);
                        }
                    }
                    if ($total_blocks > 0) {
                        echo '<span class="success">✓ Yes (' . $total_blocks . ' time blocks)</span>';
                    } else {
                        echo '<span class="error">✗ Weekly array exists but empty!</span>';
                    }
                } else {
                    echo '<span class="error">✗ No weekly schedule set!</span>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <?php if (is_array($weekly) && !empty($weekly)) : ?>
    <div class="card">
        <h2>Weekly Schedule Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Time Blocks</th>
                    <th>Total Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $days = array(
                    'mon' => 'Monday',
                    'tue' => 'Tuesday',
                    'wed' => 'Wednesday',
                    'thu' => 'Thursday',
                    'fri' => 'Friday',
                    'sat' => 'Saturday',
                    'sun' => 'Sunday',
                );
                
                foreach ($days as $key => $name) :
                    $day_blocks = isset($weekly[$key]) ? $weekly[$key] : array();
                    $total_minutes = 0;
                    ?>
                    <tr>
                        <td class="day-name"><?php echo esc_html($name); ?></td>
                        <td>
                            <?php 
                            if (!empty($day_blocks) && is_array($day_blocks)) {
                                foreach ($day_blocks as $block) {
                                    if (isset($block['start']) && isset($block['end'])) {
                                        echo '<span class="time-block">' . esc_html($block['start']) . ' - ' . esc_html($block['end']) . '</span> ';
                                        
                                        // Calculate minutes
                                        $start = strtotime($block['start']);
                                        $end = strtotime($block['end']);
                                        if ($start && $end) {
                                            $total_minutes += ($end - $start) / 60;
                                        }
                                    }
                                }
                            } else {
                                echo '<span class="empty">No availability</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($total_minutes > 0) {
                                $hours = floor($total_minutes / 60);
                                $mins = $total_minutes % 60;
                                echo $hours > 0 ? $hours . 'h ' : '';
                                echo $mins > 0 ? $mins . 'm' : '';
                                echo ' <span class="empty">(' . ceil($total_minutes / ($slot_minutes ?: 30)) . ' slots)</span>';
                            } else {
                                echo '<span class="empty">-</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if ($settings) : ?>
    <div class="card">
        <h2>Settings via Helper Function</h2>
        <p>This is what the code sees when it calls <code>vh360_get_availability_settings(<?php echo $user_id; ?>)</code>:</p>
        <pre><?php print_r($settings); ?></pre>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>Test Slot Generation</h2>
        <p>Let's try to generate slots for the next 7 days:</p>
        <?php 
        if (function_exists('vh360_get_open_appointment_slots')) {
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+6 days'));
            
            $test_slots = vh360_get_open_appointment_slots($user_id, $start_date, $end_date);
            
            if (!empty($test_slots)) {
                echo '<p class="success">✓ Generated ' . count($test_slots) . ' slots!</p>';
                echo '<p>First 10 slots:</p>';
                echo '<pre>';
                print_r(array_slice($test_slots, 0, 10));
                echo '</pre>';
            } else {
                echo '<p class="error">✗ No slots generated!</p>';
                echo '<p><strong>Possible reasons:</strong></p>';
                echo '<ul>';
                echo '<li>No weekly availability set</li>';
                echo '<li>All available times are in the past</li>';
                echo '<li>Time format issues in saved data</li>';
                echo '<li>Timezone causing all slots to appear as "past"</li>';
                echo '</ul>';
            }
        } else {
            echo '<p class="error">Function vh360_get_open_appointment_slots() not found!</p>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>Recommendations</h2>
        <?php 
        $issues = array();
        
        if (!in_array($account_type, array('professional', 'organization'))) {
            $issues[] = '❌ User is not a professional or organization account type';
        }
        
        if (!is_array($weekly) || empty($weekly)) {
            $issues[] = '❌ No weekly schedule set - user needs to go to Dashboard → Availability and add time blocks';
        } else {
            $has_blocks = false;
            foreach ($weekly as $day_blocks) {
                if (is_array($day_blocks) && count($day_blocks) > 0) {
                    $has_blocks = true;
                    break;
                }
            }
            if (!$has_blocks) {
                $issues[] = '❌ Weekly array exists but has no time blocks - user needs to add availability for at least one day';
            }
        }
        
        if (empty($issues)) {
            echo '<p class="success">✓ Configuration looks good!</p>';
            echo '<p>If slots still don\'t appear, check:</p>';
            echo '<ul>';
            echo '<li>Timezone settings (slots in past are filtered)</li>';
            echo '<li>Browser console for JavaScript errors</li>';
            echo '<li>wp-admin → Posts → Events for conflicting block events</li>';
            echo '</ul>';
        } else {
            echo '<p class="error">Issues found:</p>';
            echo '<ul>';
            foreach ($issues as $issue) {
                echo '<li>' . $issue . '</li>';
            }
            echo '</ul>';
        }
        ?>
    </div>
    
    <p>
        <a href="?user_id=<?php echo $user_id; ?>" class="btn">Refresh</a>
        <a href="?" class="btn btn-secondary">Check Another User</a>
        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user_id); ?>" class="btn btn-secondary">Edit User in WP Admin</a>
    </p>
    
</body>
</html>
<?php
