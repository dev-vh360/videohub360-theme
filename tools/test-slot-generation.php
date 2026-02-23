<?php
/**
 * Test script for debugging slot generation
 * Run from command line: php test-slot-generation.php
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

echo "=== Slot Generation Debug Test ===\n\n";

$professional_id = 1;
$test_date = '2026-02-24'; // Tomorrow from the user's perspective

echo "Testing for Professional ID: $professional_id\n";
echo "Test Date: $test_date\n\n";

// Get settings
$settings = vh360_get_availability_settings($professional_id);
echo "Settings Retrieved:\n";
echo "- Timezone: " . $settings['timezone'] . "\n";
echo "- Slot Minutes: " . $settings['slot_minutes'] . "\n";
echo "- Buffer Minutes: " . $settings['buffer_minutes'] . "\n";
echo "- Weekly Keys: " . implode(', ', array_keys($settings['weekly'])) . "\n\n";

// Test date parsing
$start_date = new DateTime($test_date, new DateTimeZone($settings['timezone']));
echo "Start Date Object Created: " . $start_date->format('Y-m-d H:i:s') . "\n";
echo "Day of Week: " . $start_date->format('D') . "\n";
echo "Day of Week (lowercase): " . strtolower($start_date->format('D')) . "\n\n";

$day_of_week = strtolower($start_date->format('D'));
echo "Checking weekly array for key '$day_of_week':\n";
if (isset($settings['weekly'][$day_of_week])) {
    echo "✓ Found time blocks for $day_of_week\n";
    print_r($settings['weekly'][$day_of_week]);
} else {
    echo "✗ No time blocks found for $day_of_week\n";
    echo "Available keys in weekly array:\n";
    print_r(array_keys($settings['weekly']));
}
echo "\n";

// Test time block parsing
if (!empty($settings['weekly'][$day_of_week])) {
    $time_block = $settings['weekly'][$day_of_week][0];
    echo "Testing first time block:\n";
    echo "- Raw start: " . $time_block['start'] . "\n";
    echo "- Raw end: " . $time_block['end'] . "\n";
    
    // Test time extraction (using fixed method)
    $start_time = substr($time_block['start'], 0, 5);
    $end_time = substr($time_block['end'], 0, 5);
    echo "- After substr(0,5) start: '$start_time'\n";
    echo "- After substr(0,5) end: '$end_time'\n\n";
    
    // Test DateTime creation
    $date_str = $start_date->format('Y-m-d');
    $block_start_str = $date_str . ' ' . $start_time;
    echo "Creating DateTime from: '$block_start_str'\n";
    $block_start = DateTime::createFromFormat('Y-m-d H:i', $block_start_str, new DateTimeZone($settings['timezone']));
    if ($block_start) {
        echo "✓ Block start created: " . $block_start->format('Y-m-d H:i:s') . "\n";
    } else {
        echo "✗ Block start creation FAILED\n";
        echo "DateTime errors: ";
        print_r(DateTime::getLastErrors());
    }
    
    $block_end_str = $date_str . ' ' . $end_time;
    $block_end = DateTime::createFromFormat('Y-m-d H:i', $block_end_str, new DateTimeZone($settings['timezone']));
    if ($block_end) {
        echo "✓ Block end created: " . $block_end->format('Y-m-d H:i:s') . "\n\n";
    } else {
        echo "✗ Block end creation FAILED\n";
        echo "DateTime errors: ";
        print_r(DateTime::getLastErrors());
    }
    
    if ($block_start && $block_end) {
        // Test slot generation
        echo "Testing slot generation:\n";
        $slot_count = 0;
        $slot_start = clone $block_start;
        $now = new DateTime('now', new DateTimeZone($settings['timezone']));
        echo "Current time: " . $now->format('Y-m-d H:i:s') . "\n\n";
        
        while ($slot_start < $block_end && $slot_count < 5) {
            $slot_end = clone $slot_start;
            $slot_end->modify('+' . $settings['slot_minutes'] . ' minutes');
            
            echo "Slot #" . ($slot_count + 1) . ":\n";
            echo "  Start: " . $slot_start->format('Y-m-d H:i:s') . "\n";
            echo "  End: " . $slot_end->format('Y-m-d H:i:s') . "\n";
            echo "  Is in past? " . ($slot_start <= $now ? 'YES (filtered out)' : 'NO (would be included)') . "\n";
            
            // Check conflict
            $has_conflict = vh360_check_slot_conflict(
                $professional_id,
                $slot_start->format('Y-m-d'),
                $slot_start->format('H:i:s'),
                $slot_end->format('Y-m-d'),
                $slot_end->format('H:i:s')
            );
            echo "  Has conflict? " . ($has_conflict ? 'YES (filtered out)' : 'NO (would be included)') . "\n\n";
            
            $slot_start->modify('+' . ($settings['slot_minutes'] + $settings['buffer_minutes']) . ' minutes');
            $slot_count++;
        }
    }
}

echo "\n=== Now calling actual function ===\n\n";
$slots = vh360_get_open_appointment_slots($professional_id, $test_date, $test_date);
echo "Slots returned: " . count($slots) . "\n";
if (!empty($slots)) {
    echo "First few slots:\n";
    foreach (array_slice($slots, 0, 3) as $slot) {
        echo "  - " . $slot['datetime'] . " (" . $slot['start'] . " - " . $slot['end'] . ")\n";
    }
} else {
    echo "✗ No slots generated!\n";
}
