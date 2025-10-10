<?php
/**
 * Test script for creating a member with parent/guardian information
 */

// Détecter si nous sommes en ligne de commande ou via le navigateur
if (php_sapi_name() === 'cli') {
    // Mode ligne de commande - charger WordPress
    require_once('../../../wp-load.php');
} else {
    // Mode navigateur - utiliser la fonction add_action
    // Ce fichier doit être inclus via admin ou une page WordPress
    if (!defined('ABSPATH')) {
        die('This file must be accessed through WordPress');
    }
}

// Load plugin classes
require_once('includes/class-juniorgolfkenya-database.php');
require_once('includes/class-juniorgolfkenya-user-manager.php');
require_once('includes/class-juniorgolfkenya-parents.php');

// Load WordPress user functions if not already loaded
if (!function_exists('wp_create_user')) {
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}
if (!function_exists('wp_delete_user')) {
    require_once(ABSPATH . 'wp-admin/includes/user.php');
}

echo "=== Testing Member Creation with Parent/Guardian Information ===\n\n";

$test_results = array();
$test_number = 1;

/**
 * Test 1: Create a junior member (under 18) WITHOUT parent data (should fail)
 */
echo "Test $test_number: Create junior member without parent data (should fail)\n";
$test_number++;

$user_data = array(
    'user_login' => 'test_junior_no_parent_' . time(),
    'user_email' => 'junior_no_parent_' . time() . '@test.com',
    'user_pass' => 'TestPassword123!',
    'display_name' => 'Test Junior',
    'first_name' => 'Test',
    'last_name' => 'Junior'
);

$member_data = array(
    'membership_type' => 'junior',
    'status' => 'active',
    'date_of_birth' => date('Y-m-d', strtotime('-15 years')), // 15 years old
    'gender' => 'male',
    'phone' => '+254712345678',
    'club_affiliation' => 'Test Club'
);

$result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data);
if (!$result['success']) {
    echo "✓ PASS: Member creation correctly failed: " . $result['message'] . "\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: Member creation should have failed but succeeded\n";
    $test_results[] = false;
    // Clean up
    if (isset($result['member_id'])) {
        JuniorGolfKenya_Database::delete_member($result['member_id']);
    }
    if (isset($result['user_id'])) {
        wp_delete_user($result['user_id']);
    }
}
echo "\n";

/**
 * Test 2: Create a junior member (under 18) WITH parent data (should succeed)
 */
echo "Test $test_number: Create junior member with parent data (should succeed)\n";
$test_number++;

$user_data = array(
    'user_login' => 'test_junior_with_parent_' . time(),
    'user_email' => 'junior_with_parent_' . time() . '@test.com',
    'user_pass' => 'TestPassword123!',
    'display_name' => 'John Doe Junior',
    'first_name' => 'John',
    'last_name' => 'Doe'
);

$member_data = array(
    'membership_type' => 'junior',
    'status' => 'active',
    'date_of_birth' => date('Y-m-d', strtotime('-16 years')), // 16 years old
    'gender' => 'male',
    'phone' => '+254723456789',
    'club_affiliation' => 'Junior Golf Club',
    'handicap' => 18.5
);

$parent_data = array(
    array(
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'relationship' => 'mother',
        'phone' => '+254734567890',
        'email' => 'jane.doe@test.com',
        'occupation' => 'Teacher',
        'is_primary_contact' => 1,
        'emergency_contact' => 1,
        'can_pickup' => 1
    ),
    array(
        'first_name' => 'John',
        'last_name' => 'Doe Sr.',
        'relationship' => 'father',
        'phone' => '+254745678901',
        'email' => 'john.doe.sr@test.com',
        'occupation' => 'Engineer',
        'emergency_contact' => 1,
        'can_pickup' => 1
    )
);

$result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data, $parent_data);
if ($result['success']) {
    echo "✓ PASS: Member created successfully\n";
    echo "  - User ID: " . $result['user_id'] . "\n";
    echo "  - Member ID: " . $result['member_id'] . "\n";
    $test_results[] = true;
    
    // Verify parents were added
    $parents_manager = new JuniorGolfKenya_Parents();
    $parents = $parents_manager->get_member_parents($result['member_id']);
    
    if (count($parents) === 2) {
        echo "✓ PASS: Both parents added successfully\n";
        $test_results[] = true;
    } else {
        echo "✗ FAIL: Expected 2 parents, got " . count($parents) . "\n";
        $test_results[] = false;
    }
    
    // Verify primary contact
    $primary = $parents_manager->get_primary_contact($result['member_id']);
    if ($primary && $primary->first_name === 'Jane') {
        echo "✓ PASS: Primary contact correctly identified\n";
        $test_results[] = true;
    } else {
        echo "✗ FAIL: Primary contact not correctly set\n";
        $test_results[] = false;
    }
    
    // Verify emergency contacts
    $emergency = $parents_manager->get_emergency_contacts($result['member_id']);
    if (count($emergency) === 2) {
        echo "✓ PASS: Both emergency contacts identified\n";
        $test_results[] = true;
    } else {
        echo "✗ FAIL: Expected 2 emergency contacts, got " . count($emergency) . "\n";
        $test_results[] = false;
    }
    
    // Clean up
    $saved_member_id = $result['member_id'];
    $saved_user_id = $result['user_id'];
    
} else {
    echo "✗ FAIL: Member creation failed: " . $result['message'] . "\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 3: Create an adult member (18+) WITH parent data (should succeed, parents optional)
 */
echo "Test $test_number: Create adult member with parent data (should succeed)\n";
$test_number++;

$user_data = array(
    'user_login' => 'test_adult_with_parent_' . time(),
    'user_email' => 'adult_with_parent_' . time() . '@test.com',
    'user_pass' => 'TestPassword123!',
    'display_name' => 'Alice Smith',
    'first_name' => 'Alice',
    'last_name' => 'Smith'
);

$member_data = array(
    'membership_type' => 'youth',
    'status' => 'active',
    'date_of_birth' => date('Y-m-d', strtotime('-20 years')), // 20 years old
    'gender' => 'female',
    'phone' => '+254756789012',
    'club_affiliation' => 'Senior Golf Club'
);

$parent_data = array(
    array(
        'first_name' => 'Robert',
        'last_name' => 'Smith',
        'relationship' => 'father',
        'phone' => '+254767890123',
        'email' => 'robert.smith@test.com'
    )
);

$result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data, $parent_data);
if ($result['success']) {
    echo "✓ PASS: Adult member with parent data created successfully\n";
    echo "  - User ID: " . $result['user_id'] . "\n";
    echo "  - Member ID: " . $result['member_id'] . "\n";
    $test_results[] = true;
    
    // Verify parent was added even though not required
    $parents_manager = new JuniorGolfKenya_Parents();
    $parents = $parents_manager->get_member_parents($result['member_id']);
    
    if (count($parents) === 1) {
        echo "✓ PASS: Parent added for adult member\n";
        $test_results[] = true;
    } else {
        echo "✗ FAIL: Expected 1 parent, got " . count($parents) . "\n";
        $test_results[] = false;
    }
    
    // Clean up
    JuniorGolfKenya_Database::delete_member($result['member_id']);
    wp_delete_user($result['user_id']);
    
} else {
    echo "✗ FAIL: Adult member creation failed: " . $result['message'] . "\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 4: Create an adult member (18+) WITHOUT parent data (should succeed)
 */
echo "Test $test_number: Create adult member without parent data (should succeed)\n";
$test_number++;

$user_data = array(
    'user_login' => 'test_adult_no_parent_' . time(),
    'user_email' => 'adult_no_parent_' . time() . '@test.com',
    'user_pass' => 'TestPassword123!',
    'display_name' => 'Bob Johnson',
    'first_name' => 'Bob',
    'last_name' => 'Johnson'
);

$member_data = array(
    'membership_type' => 'adult',
    'status' => 'active',
    'date_of_birth' => date('Y-m-d', strtotime('-30 years')), // 30 years old
    'gender' => 'male',
    'phone' => '+254778901234',
    'club_affiliation' => 'Pro Golf Club'
);

$result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data);
if ($result['success']) {
    echo "✓ PASS: Adult member without parent data created successfully\n";
    echo "  - User ID: " . $result['user_id'] . "\n";
    echo "  - Member ID: " . $result['member_id'] . "\n";
    $test_results[] = true;
    
    // Clean up
    JuniorGolfKenya_Database::delete_member($result['member_id']);
    wp_delete_user($result['user_id']);
    
} else {
    echo "✗ FAIL: Adult member creation failed: " . $result['message'] . "\n";
    $test_results[] = false;
}
echo "\n";

// Clean up the successful junior member from Test 2
if (isset($saved_member_id) && isset($saved_user_id)) {
    echo "Cleaning up test member from Test 2...\n";
    JuniorGolfKenya_Database::delete_member($saved_member_id);
    wp_delete_user($saved_user_id);
    echo "✓ Cleanup complete\n\n";
}

// Summary
$passed = array_sum($test_results);
$total = count($test_results);
$percentage = round(($passed / $total) * 100, 2);

echo "=== TEST SUMMARY ===\n";
echo "Passed: $passed / $total ($percentage%)\n";
echo ($passed === $total) ? "✓ ALL TESTS PASSED!\n" : "✗ SOME TESTS FAILED\n";
