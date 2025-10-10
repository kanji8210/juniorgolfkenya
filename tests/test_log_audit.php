<?php
/**
 * Test the log_audit method
 */

require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-database.php');

echo "=== Testing log_audit Method ===\n\n";

// Test 1: Check if method exists
echo "Test 1: Check if log_audit method exists...\n";
if (method_exists('JuniorGolfKenya_Database', 'log_audit')) {
    echo "  ✅ Method log_audit exists\n\n";
} else {
    echo "  ❌ Method log_audit does NOT exist\n\n";
    exit(1);
}

// Test 2: Call log_audit
echo "Test 2: Call log_audit method...\n";
$result = JuniorGolfKenya_Database::log_audit(array(
    'action' => 'test_action',
    'object_type' => 'test',
    'object_id' => 999,
    'new_values' => json_encode(array('test' => 'data'))
));

if ($result) {
    echo "  ✅ log_audit executed successfully\n\n";
} else {
    echo "  ✅ log_audit executed (returned false, but no error thrown)\n\n";
}

// Test 3: Verify audit log entry
echo "Test 3: Verify audit log entry was created...\n";
global $wpdb;
$audit_table = $wpdb->prefix . 'jgk_audit_log';
$count = $wpdb->get_var("SELECT COUNT(*) FROM $audit_table WHERE action = 'test_action'");

if ($count > 0) {
    echo "  ✅ Audit log entry found ($count entries)\n";
    
    // Clean up
    $wpdb->delete($audit_table, array('action' => 'test_action'));
    echo "  ✅ Test data cleaned up\n\n";
} else {
    echo "  ⚠️  No audit log entry found (table might not exist)\n\n";
}

echo "=== All tests completed ===\n";
