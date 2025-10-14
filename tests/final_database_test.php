<?php
/**
 * Final comprehensive test of all database operations
 */

require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-database.php');

echo "=== Final Comprehensive Database Test ===\n\n";

global $wpdb;
$all_passed = true;

// Test 1: Member creation with all fields
echo "Test 1: Member creation with handicap and medical_conditions...\n";
$member_data = array(
    'user_id' => 2,
    'membership_number' => 'TEST' . time(),
    'membership_type' => 'junior',
    'status' => 'pending',
    'join_date' => current_time('Y-m-d'),
    'expiry_date' => date('Y-m-d', strtotime('+1 year')),
    'date_of_birth' => '2010-06-11',
    'gender' => 'male',
    'phone' => '+254112855094',
    'handicap' => '0.2',
    'club_affiliation' => 'Test Club',
    'emergency_contact_name' => 'Jane Doe',
    'emergency_contact_phone' => '+254700000000',
    'medical_conditions' => 'None',
    'first_name' => 'Test',
    'last_name' => 'Member'
);

$member_id = JuniorGolfKenya_Database::create_member($member_data);

if ($member_id) {
    echo "  ✅ Member created successfully (ID: $member_id)\n\n";
} else {
    echo "  ❌ Failed to create member\n";
    if ($wpdb->last_error) {
        echo "  Error: {$wpdb->last_error}\n";
    }
    $all_passed = false;
}

// Test 2: Payment recording
if ($member_id) {
    echo "Test 2: Recording payment...\n";
    $payment_id = JuniorGolfKenya_Database::record_manual_payment($member_id, 50.00, 'membership', 'mpesa');
    
    if ($payment_id) {
        echo "  ✅ Payment recorded successfully (ID: $payment_id)\n\n";
    } else {
        echo "  ❌ Failed to record payment\n";
        if ($wpdb->last_error) {
            echo "  Error: {$wpdb->last_error}\n";
        }
        $all_passed = false;
    }
}

// Test 3: Status change (with audit log)
if ($member_id) {
    echo "Test 3: Updating member status (tests audit log)...\n";
    $status_updated = JuniorGolfKenya_Database::update_member_status($member_id, 'active', 'Test activation');
    
    if ($status_updated) {
        echo "  ✅ Status updated successfully\n";
        
        // Check audit log
        $audit_table = $wpdb->prefix . 'jgk_audit_log';
        $audit_count = $wpdb->get_var("SELECT COUNT(*) FROM $audit_table WHERE object_id = $member_id");
        echo "  ✅ Audit log entries: $audit_count\n\n";
    } else {
        echo "  ❌ Failed to update status\n";
        if ($wpdb->last_error) {
            echo "  Error: {$wpdb->last_error}\n";
        }
        $all_passed = false;
    }
}

// Test 4: Get coaches query
echo "Test 4: Get coaches query...\n";
$coaches = JuniorGolfKenya_Database::get_coaches();
if ($coaches !== false) {
    echo "  ✅ Coaches query executed successfully (found " . count($coaches) . " coaches)\n\n";
} else {
    echo "  ❌ Failed to get coaches\n";
    if ($wpdb->last_error) {
        echo "  Error: {$wpdb->last_error}\n";
    }
    $all_passed = false;
}

// Test 5: Get members query
echo "Test 5: Get members query...\n";
$members = JuniorGolfKenya_Database::get_members(1, 10);
if ($members !== false) {
    echo "  ✅ Members query executed successfully (found " . count($members) . " members)\n\n";
} else {
    echo "  ❌ Failed to get members\n";
    if ($wpdb->last_error) {
        echo "  Error: {$wpdb->last_error}\n";
    }
    $all_passed = false;
}

// Test 6: Get member by ID
if ($member_id) {
    echo "Test 6: Get member by ID...\n";
    $member = JuniorGolfKenya_Database::get_member($member_id);
    if ($member) {
        echo "  ✅ Member retrieved successfully\n";
        echo "     - Name: {$member->first_name} {$member->last_name}\n";
        echo "     - Handicap: {$member->handicap}\n";
        echo "     - Medical Conditions: {$member->medical_conditions}\n\n";
    } else {
        echo "  ❌ Failed to get member\n";
        $all_passed = false;
    }
}

// Cleanup
echo "Cleanup: Removing test data...\n";
if ($member_id) {
    // Delete audit logs
    $audit_table = $wpdb->prefix . 'jgk_audit_log';
    $wpdb->delete($audit_table, array('object_id' => $member_id));
    
    // Delete payments
    if (isset($payment_id)) {
        $payments_table = $wpdb->prefix . 'jgk_payments';
        $wpdb->delete($payments_table, array('id' => $payment_id));
    }
    
    // Delete member
    $members_table = $wpdb->prefix . 'jgk_members';
    $wpdb->delete($members_table, array('id' => $member_id));
    
    echo "  ✅ Test data cleaned up\n\n";
}

// Summary
if ($all_passed) {
    echo "=== ✅ ALL TESTS PASSED - DATABASE IS CORRECTLY CONFIGURED ===\n";
} else {
    echo "=== ❌ SOME TESTS FAILED - CHECK ERRORS ABOVE ===\n";
}
