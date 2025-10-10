<?php
/**
 * Test member creation through user manager (the original error case)
 */

require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-user-manager.php');

echo "=== Testing Member Creation (User Manager) ===\n\n";

// Prepare test data
$user_data = array(
    'user_login' => 'testmember_' . time(),
    'user_email' => 'test' . time() . '@example.com',
    'first_name' => 'Test',
    'last_name' => 'Member',
    'role' => 'jgk_member'
);

$member_data = array(
    'membership_type' => 'junior',
    'date_of_birth' => '2010-06-11',
    'gender' => 'male',
    'phone' => '+254712345678',
    'handicap' => '1.5',
    'club_affiliation' => 'Test Club',
    'medical_conditions' => 'None'
);

echo "Test: Creating member user...\n";
echo "  Username: {$user_data['user_login']}\n";
echo "  Email: {$user_data['user_email']}\n\n";

try {
    $result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data);
    
    if ($result['success']) {
        echo "✅ SUCCESS! Member created\n";
        echo "  User ID: {$result['user_id']}\n";
        echo "  Member ID: {$result['member_id']}\n";
        echo "  Message: {$result['message']}\n\n";
        
        // Verify audit log
        global $wpdb;
        $audit_table = $wpdb->prefix . 'jgk_audit_log';
        $audit_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $audit_table WHERE object_id = %d AND action = 'member_created'",
            $result['member_id']
        ));
        
        if ($audit_entry) {
            echo "✅ Audit log entry created successfully\n";
            echo "  Action: {$audit_entry->action}\n";
            echo "  Object Type: {$audit_entry->object_type}\n";
            echo "  User ID: {$audit_entry->user_id}\n\n";
        } else {
            echo "⚠️  No audit log entry found\n\n";
        }
        
        // Cleanup
        echo "Cleanup: Removing test data...\n";
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($result['user_id']);
        $members_table = $wpdb->prefix . 'jgk_members';
        $wpdb->delete($members_table, array('id' => $result['member_id']));
        if ($audit_entry) {
            $wpdb->delete($audit_table, array('id' => $audit_entry->id));
        }
        echo "  ✅ Test data cleaned up\n";
        
    } else {
        echo "❌ FAILED: {$result['message']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
}

echo "\n=== Test completed ===\n";
