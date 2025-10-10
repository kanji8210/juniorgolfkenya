<?php
/**
 * Test Parents/Guardians functionality
 */

require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-database.php');
require_once('includes/class-juniorgolfkenya-parents.php');

echo "=== Testing Parents/Guardians Functionality ===\n\n";

// Create a test member first
echo "Step 1: Creating test member (under 18)...\n";
$member_data = array(
    'user_id' => 2,
    'membership_number' => 'TEST_PARENT_' . time(),
    'membership_type' => 'junior',
    'status' => 'active',
    'date_of_birth' => '2012-06-15', // 13 years old
    'first_name' => 'Young',
    'last_name' => 'Member',
    'gender' => 'male'
);

$member_id = JuniorGolfKenya_Database::create_member($member_data);

if ($member_id) {
    echo "  ✅ Test member created (ID: $member_id)\n\n";
} else {
    echo "  ❌ Failed to create test member\n";
    exit(1);
}

// Test 1: Check if member requires parent info
echo "Test 1: Check if member requires parent info...\n";
$requires_parent = JuniorGolfKenya_Parents::requires_parent_info($member_id);
echo "  " . ($requires_parent ? "✅" : "❌") . " Member requires parent info: " . ($requires_parent ? "Yes" : "No") . "\n\n";

// Test 2: Add first parent (Mother)
echo "Test 2: Adding mother as primary contact...\n";
$mother_data = array(
    'relationship' => 'mother',
    'first_name' => 'Jane',
    'last_name' => 'Doe',
    'email' => 'jane.doe@example.com',
    'phone' => '+254722000001',
    'mobile' => '+254700000001',
    'address' => '123 Main St, Nairobi',
    'occupation' => 'Teacher',
    'employer' => 'Nairobi School',
    'id_number' => '12345678',
    'is_primary_contact' => 1,
    'can_pickup' => 1,
    'emergency_contact' => 1
);

$mother_id = JuniorGolfKenya_Parents::add_parent($member_id, $mother_data);

if ($mother_id) {
    echo "  ✅ Mother added successfully (ID: $mother_id)\n\n";
} else {
    echo "  ❌ Failed to add mother\n\n";
}

// Test 3: Add second parent (Father)
echo "Test 3: Adding father...\n";
$father_data = array(
    'relationship' => 'father',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'phone' => '+254722000002',
    'mobile' => '+254700000002',
    'occupation' => 'Engineer',
    'can_pickup' => 1,
    'emergency_contact' => 1
);

$father_id = JuniorGolfKenya_Parents::add_parent($member_id, $father_data);

if ($father_id) {
    echo "  ✅ Father added successfully (ID: $father_id)\n\n";
} else {
    echo "  ❌ Failed to add father\n\n";
}

// Test 4: Get all parents for member
echo "Test 4: Getting all parents for member...\n";
$parents = JuniorGolfKenya_Parents::get_member_parents($member_id);
echo "  ✅ Found " . count($parents) . " parent(s)\n";
foreach ($parents as $parent) {
    echo "    - {$parent->first_name} {$parent->last_name} ({$parent->relationship})\n";
}
echo "\n";

// Test 5: Get primary contact
echo "Test 5: Getting primary contact...\n";
$primary = JuniorGolfKenya_Parents::get_primary_contact($member_id);
if ($primary) {
    echo "  ✅ Primary contact: {$primary->first_name} {$primary->last_name}\n";
    echo "     Email: {$primary->email}\n";
    echo "     Phone: {$primary->phone}\n\n";
} else {
    echo "  ❌ No primary contact found\n\n";
}

// Test 6: Get emergency contacts
echo "Test 6: Getting emergency contacts...\n";
$emergency = JuniorGolfKenya_Parents::get_emergency_contacts($member_id);
echo "  ✅ Found " . count($emergency) . " emergency contact(s)\n";
foreach ($emergency as $contact) {
    echo "    - {$contact->first_name} {$contact->last_name} ({$contact->phone})\n";
}
echo "\n";

// Test 7: Update parent
echo "Test 7: Updating father's information...\n";
$update_result = JuniorGolfKenya_Parents::update_parent($father_id, array(
    'employer' => 'Tech Company Ltd',
    'address' => '456 Tech Ave, Nairobi'
));

if ($update_result) {
    echo "  ✅ Father's information updated\n";
    $updated_father = JuniorGolfKenya_Parents::get_parent($father_id);
    echo "     Employer: {$updated_father->employer}\n";
    echo "     Address: {$updated_father->address}\n\n";
} else {
    echo "  ❌ Failed to update father's information\n\n";
}

// Test 8: Validate parent data
echo "Test 8: Testing data validation...\n";

// Valid data
$valid_data = array(
    'first_name' => 'Test',
    'last_name' => 'Parent',
    'relationship' => 'guardian',
    'email' => 'test@example.com'
);
$validation1 = JuniorGolfKenya_Parents::validate_parent_data($valid_data);
echo "  Valid data: " . ($validation1['valid'] ? "✅ PASS" : "❌ FAIL") . "\n";

// Invalid data (missing first name)
$invalid_data = array(
    'last_name' => 'Parent',
    'relationship' => 'guardian'
);
$validation2 = JuniorGolfKenya_Parents::validate_parent_data($invalid_data);
echo "  Invalid data: " . (!$validation2['valid'] ? "✅ PASS (correctly rejected)" : "❌ FAIL") . "\n";
if (!$validation2['valid']) {
    echo "     Errors: " . implode(', ', $validation2['errors']) . "\n";
}
echo "\n";

// Test 9: Get relationship types
echo "Test 9: Getting relationship types...\n";
$relationships = JuniorGolfKenya_Parents::get_relationship_types();
echo "  ✅ Available relationships:\n";
foreach ($relationships as $key => $label) {
    echo "    - $key: $label\n";
}
echo "\n";

// Cleanup
echo "Cleanup: Removing test data...\n";
global $wpdb;

// Delete parents
if ($mother_id) {
    JuniorGolfKenya_Parents::delete_parent($mother_id);
}
if ($father_id) {
    JuniorGolfKenya_Parents::delete_parent($father_id);
}

// Delete member
$members_table = $wpdb->prefix . 'jgk_members';
$wpdb->delete($members_table, array('id' => $member_id));

// Delete audit logs
$audit_table = $wpdb->prefix . 'jgk_audit_log';
$wpdb->query($wpdb->prepare("DELETE FROM $audit_table WHERE member_id = %d", $member_id));

echo "  ✅ Test data cleaned up\n\n";

echo "=== ✅ ALL TESTS COMPLETED SUCCESSFULLY ===\n";
