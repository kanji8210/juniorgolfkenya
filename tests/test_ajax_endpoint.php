<?php
/**
 * Test AJAX endpoint directly
 */

// Load WordPress
require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-member-details.php');

// Initialize the class
new JuniorGolfKenya_Member_Details();

echo "=== Testing AJAX Endpoint ===\n";

// Test with a valid member (assuming member ID 1 exists)
$_POST = array(
    'action' => 'jgk_get_member_details',
    'member_id' => '1',
    'nonce' => wp_create_nonce('jgk_members_action')
);

// Simulate AJAX call
try {
    echo "Making AJAX call...\n";
    do_action('wp_ajax_jgk_get_member_details');
    echo "AJAX call completed\n";
} catch (Exception $e) {
    echo "Error during AJAX call: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";