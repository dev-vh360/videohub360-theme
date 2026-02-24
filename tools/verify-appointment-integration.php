<?php
/**
 * Appointment Live Room Integration - Verification Script
 * 
 * This script verifies that all the necessary components for the
 * appointment Live Room integration are in place.
 * 
 * Run this script by accessing it in a browser while logged in as an admin:
 * /path-to-theme/tools/verify-appointment-integration.php
 * 
 * @package Videohub360_Theme
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check - only admins can run this
if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator to run this verification script.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Appointment Live Room Integration - Verification</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
        h1 { color: #2271b1; }
        h2 { color: #135e96; margin-top: 30px; }
        .check { margin: 10px 0; padding: 10px; border-left: 4px solid #ddd; }
        .pass { border-color: #00a32a; background: #f0f6fc; }
        .fail { border-color: #d63638; background: #fcf0f1; }
        .icon { font-weight: bold; margin-right: 10px; }
        .pass .icon { color: #00a32a; }
        .fail .icon { color: #d63638; }
        .code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .summary { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .summary.all-pass { background: #d7f5e6; border: 1px solid #00a32a; }
        .summary.has-fail { background: #fef1f1; border: 1px solid #d63638; }
    </style>
</head>
<body>
    <h1>🔍 Appointment Live Room Integration - Verification</h1>
    <p>This script verifies that all components of the appointment Live Room integration are in place.</p>

    <?php
    $checks = array();
    $all_passed = true;

    // Check 1: Appointment gate file exists
    $gate_file = get_template_directory() . '/includes/appointment-live-room-gate.php';
    $checks[] = array(
        'name' => 'Access Gate File Exists',
        'pass' => file_exists($gate_file),
        'details' => 'File: <span class="code">includes/appointment-live-room-gate.php</span>',
    );

    // Check 2: Gate function exists
    $checks[] = array(
        'name' => 'Access Gate Function Exists',
        'pass' => function_exists('vh360_gate_appointment_live_room_access'),
        'details' => 'Function: <span class="code">vh360_gate_appointment_live_room_access()</span>',
    );

    // Check 3: Gate is hooked into template_redirect
    $checks[] = array(
        'name' => 'Access Gate Hooked to template_redirect',
        'pass' => has_action('template_redirect', 'vh360_gate_appointment_live_room_access'),
        'details' => 'Hook priority: ' . has_action('template_redirect', 'vh360_gate_appointment_live_room_access'),
    );

    // Check 4: Availability AJAX class exists
    $checks[] = array(
        'name' => 'Availability AJAX Class Exists',
        'pass' => class_exists('VH360_Availability_Ajax'),
        'details' => 'Class: <span class="code">VH360_Availability_Ajax</span>',
    );

    // Check 5: Book appointment action is registered
    $checks[] = array(
        'name' => 'Book Appointment AJAX Action Registered',
        'pass' => has_action('wp_ajax_vh360_book_appointment_slot'),
        'details' => 'Action: <span class="code">wp_ajax_vh360_book_appointment_slot</span>',
    );

    // Check 6: Account type helper exists
    $checks[] = array(
        'name' => 'Account Type Helper Exists',
        'pass' => function_exists('vh360_get_user_account_type'),
        'details' => 'Function: <span class="code">vh360_get_user_account_type()</span>',
    );

    // Check 7: Display mode helper exists
    $checks[] = array(
        'name' => 'Display Mode Helper Exists',
        'pass' => function_exists('vh360_get_author_display_mode'),
        'details' => 'Function: <span class="code">vh360_get_author_display_mode()</span>',
    );

    // Check 8: Live activity function exists
    $checks[] = array(
        'name' => 'Live Activity Function Exists',
        'pass' => function_exists('vh360_create_went_live_post'),
        'details' => 'Function: <span class="code">vh360_create_went_live_post()</span>',
    );

    // Check 9: Live activity hook exists
    $checks[] = array(
        'name' => 'Live Activity Hook Registered',
        'pass' => has_action('vh360_live_room_started', 'vh360_create_went_live_post'),
        'details' => 'Hook priority: ' . has_action('vh360_live_room_started', 'vh360_create_went_live_post'),
    );

    // Check 10: Core plugin AJAX class exists
    $checks[] = array(
        'name' => 'Core Plugin AJAX Class Exists',
        'pass' => class_exists('VideoHub360_Ajax'),
        'details' => 'Class: <span class="code">VideoHub360_Ajax</span>',
    );

    // Check 11: Agora token action is registered
    $checks[] = array(
        'name' => 'Agora Token AJAX Action Registered',
        'pass' => has_action('wp_ajax_vh360_generate_agora_token') || has_action('wp_ajax_nopriv_vh360_generate_agora_token'),
        'details' => 'Actions: <span class="code">wp_ajax_vh360_generate_agora_token</span>, <span class="code">wp_ajax_nopriv_vh360_generate_agora_token</span>',
    );

    // Check 12: Live Room template exists
    $live_room_template = get_template_directory() . '/videohub360-live-room.php';
    $checks[] = array(
        'name' => 'Live Room Template Exists',
        'pass' => file_exists($live_room_template),
        'details' => 'File: <span class="code">videohub360-live-room.php</span>',
    );

    // Check 13: Template switch function exists
    $checks[] = array(
        'name' => 'Live Room Template Switch Function Exists',
        'pass' => function_exists('vh360_theme_maybe_use_live_room_template'),
        'details' => 'Function: <span class="code">vh360_theme_maybe_use_live_room_template()</span>',
    );

    // Check 14: Template switch is hooked
    $checks[] = array(
        'name' => 'Live Room Template Switch Hooked',
        'pass' => has_filter('single_template', 'vh360_theme_maybe_use_live_room_template'),
        'details' => 'Hook priority: ' . has_filter('single_template', 'vh360_theme_maybe_use_live_room_template'),
    );

    // Check 15: Business booking JS exists
    $booking_js = get_template_directory() . '/assets/js/business-booking.js';
    $checks[] = array(
        'name' => 'Business Booking JavaScript Exists',
        'pass' => file_exists($booking_js),
        'details' => 'File: <span class="code">assets/js/business-booking.js</span>',
    );

    // Check 16: Documentation exists
    $docs_file = get_template_directory() . '/docs/APPOINTMENT-LIVE-ROOM-INTEGRATION.md';
    $checks[] = array(
        'name' => 'Implementation Documentation Exists',
        'pass' => file_exists($docs_file),
        'details' => 'File: <span class="code">docs/APPOINTMENT-LIVE-ROOM-INTEGRATION.md</span>',
    );

    // Display results
    ?>
    <h2>📋 Verification Results</h2>
    <?php
    $pass_count = 0;
    $fail_count = 0;

    foreach ($checks as $check) {
        $class = $check['pass'] ? 'pass' : 'fail';
        $icon = $check['pass'] ? '✅' : '❌';
        
        if ($check['pass']) {
            $pass_count++;
        } else {
            $fail_count++;
            $all_passed = false;
        }
        
        echo '<div class="check ' . $class . '">';
        echo '<span class="icon">' . $icon . '</span>';
        echo '<strong>' . esc_html($check['name']) . '</strong><br>';
        echo '<small>' . $check['details'] . '</small>';
        echo '</div>';
    }
    ?>

    <div class="summary <?php echo $all_passed ? 'all-pass' : 'has-fail'; ?>">
        <h3><?php echo $all_passed ? '🎉 All Checks Passed!' : '⚠️ Some Checks Failed'; ?></h3>
        <p>
            <strong>Passed:</strong> <?php echo $pass_count; ?> / <?php echo count($checks); ?><br>
            <?php if ($fail_count > 0): ?>
                <strong>Failed:</strong> <?php echo $fail_count; ?> / <?php echo count($checks); ?>
            <?php endif; ?>
        </p>
        <?php if ($all_passed): ?>
            <p>✅ The appointment Live Room integration is properly installed and configured.</p>
        <?php else: ?>
            <p>❌ Some components are missing or not properly configured. Please review the failed checks above.</p>
        <?php endif; ?>
    </div>

    <h2>📝 Next Steps</h2>
    <ol>
        <li>If all checks passed, test the booking flow on a Business profile</li>
        <li>Verify that a Live Room is created when an appointment is booked</li>
        <li>Test access control by attempting to access an appointment Live Room as:
            <ul>
                <li>The professional (should work)</li>
                <li>The client (should work)</li>
                <li>A different logged-in user (should get 404)</li>
                <li>A logged-out user (should redirect to login)</li>
            </ul>
        </li>
        <li>Verify that appointment Live Rooms do NOT create activity feed posts</li>
        <li>Verify that regular Live Rooms still work normally</li>
    </ol>

    <h2>🔗 Useful Links</h2>
    <ul>
        <li><a href="<?php echo admin_url('admin.php?page=videohub360-settings'); ?>">VideoHub360 Settings</a> - Configure Agora credentials</li>
        <li><a href="<?php echo home_url('/dashboard/'); ?>">Dashboard</a> - View availability settings</li>
        <li><a href="<?php echo get_template_directory_uri(); ?>/../docs/APPOINTMENT-LIVE-ROOM-INTEGRATION.md">Implementation Docs</a></li>
    </ul>

</body>
</html>
