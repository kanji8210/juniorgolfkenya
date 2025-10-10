<?php
/**
 * Test member creation with all fields including handicap and medical_conditions
 */

require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-database.php');

echo "=== Testing Member Creation with All Fields ===\n\n";

// Test data similar to the error message
$member_data = array(
    'user_id' => 2,
    'membership_number' => 'JGK20256757',
    'membership_type' => 'junior',
    'status' => 'pending',
    'join_date' => '2025-10-10',
    'expiry_date' => '2026-10-10',
    'date_of_birth' => '2025-06-11',
    'gender' => 'male',
    'phone' => '+254112855094',
    'handicap' => '0.2',
    'club_affiliation' => 'RTYUIJBN',
    'emergency_contact_name' => '',
    'emergency_contact_phone' => '',
    'medical_conditions' => 'non',
    'created_at' => '2025-10-10 18:22:17',
    'updated_at' => '2025-10-10 18:22:17'
);

echo "Test 1: Creating member with handicap and medical_conditions...\n";
$member_id = JuniorGolfKenya_Database::create_member($member_data);

if ($member_id) {
    echo "✅ Success! Member created with ID: $member_id\n\n";
    
    echo "Test 2: Retrieving created member...\n";
    $member = JuniorGolfKenya_Database::get_member($member_id);
    
    if ($member) {
        echo "✅ Success! Member retrieved:\n";
        echo "  - Name: {$member->first_name} {$member->last_name}\n";
        echo "  - Membership Number: {$member->membership_number}\n";
        echo "  - Handicap: {$member->handicap}\n";
        echo "  - Medical Conditions: {$member->medical_conditions}\n";
        echo "  - Phone: {$member->phone}\n";
        echo "  - Status: {$member->status}\n";
    } else {
        echo "❌ Failed to retrieve member\n";
    }
    
    echo "\nTest 3: Updating member...\n";
    $update_result = JuniorGolfKenya_Database::update_member($member_id, array(
        'handicap' => '1.5',
        'medical_conditions' => 'Allergies: Pollen',
        'first_name' => 'John',
        'last_name' => 'Doe'
    ));
    
    if ($update_result) {
        echo "✅ Success! Member updated\n";
        
        $updated_member = JuniorGolfKenya_Database::get_member($member_id);
        echo "  - Updated Handicap: {$updated_member->handicap}\n";
        echo "  - Updated Medical Conditions: {$updated_member->medical_conditions}\n";
        echo "  - Updated Name: {$updated_member->first_name} {$updated_member->last_name}\n";
    } else {
        echo "❌ Failed to update member\n";
    }
    
    echo "\nTest 4: Cleaning up - deleting test member...\n";
    global $wpdb;
    $table = $wpdb->prefix . 'jgk_members';
    $deleted = $wpdb->delete($table, array('id' => $member_id));
    
    if ($deleted) {
        echo "✅ Test member deleted successfully\n";
    } else {
        echo "⚠️  Note: You may need to manually delete test member ID: $member_id\n";
    }
    
} else {
    echo "❌ Failed to create member\n";
    
    global $wpdb;
    if ($wpdb->last_error) {
        echo "Database error: {$wpdb->last_error}\n";
    }
}

echo "\n=== All tests completed ===\n";
