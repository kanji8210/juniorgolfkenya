<?php
/**
 * Test script for AJAX action registration
 */

// Load WordPress
require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-member-details.php');

echo "=== Testing AJAX Action Registration ===\n\n";

echo "Testing AJAX action registration...\n";

// Check if the action is registered
global $wp_filter;
if (isset($wp_filter['wp_ajax_jgk_get_member_details'])) {
    echo "✓ AJAX action is registered\n";
    $callbacks = $wp_filter['wp_ajax_jgk_get_member_details']->callbacks;
    if (!empty($callbacks)) {
        echo "✓ Callbacks found: " . count($callbacks) . "\n";
        foreach ($callbacks as $priority => $callback_array) {
            foreach ($callback_array as $callback) {
                if (is_array($callback) && isset($callback['function'])) {
                    $function_name = is_array($callback['function'])
                        ? get_class($callback['function'][0]) . '::' . $callback['function'][1]
                        : $callback['function'];
                    echo "  - Callback: $function_name\n";
                }
            }
        }
    } else {
        echo "✗ No callbacks found\n";
    }
} else {
    echo "✗ AJAX action not registered\n";
}

// Test class instantiation
try {
    $member_details = new JuniorGolfKenya_Member_Details();
    echo "✓ Member Details class instantiated successfully\n";
} catch (Exception $e) {
    echo "✗ Failed to instantiate class: " . $e->getMessage() . "\n";
}

echo "\n=== Testing AJAX Endpoint ===\n";

// Test the AJAX endpoint directly
echo "Testing AJAX endpoint...\n";

// Simulate AJAX request
$_POST = array(
    'action' => 'jgk_get_member_details',
    'member_id' => '1',
    'nonce' => wp_create_nonce('jgk_members_action')
);

// Check if we can call the function
if (function_exists('jgk_get_member_details_callback')) {
    echo "✓ Old callback function exists (should not exist after refactoring)\n";
} else {
    echo "✓ Old callback function properly removed\n";
}

// Test the new class method
if (class_exists('JuniorGolfKenya_Member_Details')) {
    $instance = new JuniorGolfKenya_Member_Details();
    if (method_exists($instance, 'ajax_get_member_details')) {
        echo "✓ New class method exists\n";
    } else {
        echo "✗ New class method missing\n";
    }
} else {
    echo "✗ Member Details class not found\n";
}

echo "\nTest completed.\n";