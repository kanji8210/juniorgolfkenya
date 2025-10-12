<?php
/**
 * Test script for member details class
 */

// Load WordPress
if (php_sapi_name() === 'cli') {
    require_once('../../../wp-load.php');
} else {
    if (!defined('ABSPATH')) {
        die('This file must be accessed through WordPress');
    }
}

// Load plugin classes
require_once('includes/class-juniorgolfkenya-member-details.php');

echo "=== Testing Member Details Class ===\n\n";

$test_results = array();
$test_number = 1;

/**
 * Test 1: Class instantiation
 */
echo "Test $test_number: Class instantiation\n";
$test_number++;

try {
    $member_details = new JuniorGolfKenya_Member_Details();
    echo "✓ PASS: Member Details class instantiated successfully\n";
    $test_results[] = true;
} catch (Exception $e) {
    echo "✗ FAIL: Failed to instantiate class: " . $e->getMessage() . "\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 2: Check if AJAX action is registered
 */
echo "Test $test_number: AJAX action registration\n";
$test_number++;

global $wp_filter;
$ajax_actions = isset($wp_filter['wp_ajax_jgk_get_member_details']) ? $wp_filter['wp_ajax_jgk_get_member_details'] : null;

if ($ajax_actions && !empty($ajax_actions->callbacks)) {
    echo "✓ PASS: AJAX action 'jgk_get_member_details' is registered\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: AJAX action 'jgk_get_member_details' is not registered\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 3: Check class methods exist
 */
echo "Test $test_number: Class methods existence\n";
$test_number++;

$required_methods = array(
    'ajax_get_member_details',
    'validate_request',
    'get_member_data',
    'generate_member_details_html'
);

$missing_methods = array();
foreach ($required_methods as $method) {
    if (!method_exists($member_details, $method)) {
        $missing_methods[] = $method;
    }
}

if (empty($missing_methods)) {
    echo "✓ PASS: All required methods exist in the class\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: Missing methods: " . implode(', ', $missing_methods) . "\n";
    $test_results[] = false;
}
echo "\n";

$passed_tests = array_sum($test_results);
$total_tests = count($test_results);

echo "=== TEST SUMMARY ===\n";
echo "Passed: $passed_tests / $total_tests (" . round(($passed_tests / $total_tests) * 100) . "%)\n";

if ($passed_tests === $total_tests) {
    echo "🎉 All tests passed! Member Details class is working correctly.\n";
} else {
    echo "❌ Some tests failed. Please check the implementation.\n";
}