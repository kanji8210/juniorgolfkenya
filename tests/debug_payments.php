<?php
require_once('../../../../wp-load.php');
require_once('../includes/class-juniorgolfkenya-database.php');

global $wpdb;

echo "=== DEBUGGING PAYMENT ISSUES ===\n\n";

// 1. Check table existence
$table = $wpdb->prefix . 'jgk_payments';
echo "1. Checking table existence:\n";
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
echo "   Table '$table' exists: " . ($table_exists ? "YES" : "NO") . "\n\n";

if (!$table_exists) {
    echo "ERROR: Payments table does not exist!\n";
    exit;
}

// 2. Check current payments count
echo "2. Current payments in database:\n";
$count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
echo "   Total payments: $count\n";

// Show recent payments
$recent = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 5");
echo "   Recent 5 payments:\n";
foreach ($recent as $payment) {
    echo "     ID: {$payment->id}, Member: {$payment->member_id}, Amount: {$payment->amount}, Status: {$payment->status}, Date: {$payment->created_at}\n";
}
echo "\n";

// 3. Test payment recording
echo "3. Testing payment recording:\n";
$test_member_id = 1; // Assuming member ID 1 exists
$test_amount = 25.00;
$test_type = 'membership';
$test_method = 'test';

echo "   Attempting to record test payment...\n";
echo "   Member ID: $test_member_id, Amount: $test_amount, Type: $test_type, Method: $test_method\n";

$result = JuniorGolfKenya_Database::record_manual_payment($test_member_id, $test_amount, $test_type, $test_method, 'Debug test payment');

if ($result) {
    echo "   ✅ Test payment recorded successfully! Payment ID: $result\n";
    
    // Verify the payment was actually inserted
    $inserted = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $result));
    if ($inserted) {
        echo "   ✅ Payment verified in database:\n";
        echo "     ID: {$inserted->id}\n";
        echo "     Amount: {$inserted->amount}\n";
        echo "     Status: {$inserted->status}\n";
        echo "     Gateway: {$inserted->payment_gateway}\n";
        echo "     Created: {$inserted->created_at}\n";
    } else {
        echo "   ❌ Payment ID returned but not found in database!\n";
    }
} else {
    echo "   ❌ Failed to record test payment\n";
    $error = $wpdb->last_error;
    if ($error) {
        echo "   Database error: $error\n";
    }
}
echo "\n";

// 4. Check database connection and permissions
echo "4. Database connection info:\n";
echo "   Database name: " . DB_NAME . "\n";
echo "   Table prefix: " . $wpdb->prefix . "\n";
echo "   Last error: " . $wpdb->last_error . "\n";
echo "   Last query: " . $wpdb->last_query . "\n\n";

// 5. Test get_payments function
echo "5. Testing get_payments function:\n";
$payments = JuniorGolfKenya_Database::get_payments();
echo "   Retrieved " . count($payments) . " payments\n";

if (count($payments) > 0) {
    echo "   Sample payment data:\n";
    $sample = $payments[0];
    foreach ($sample as $key => $value) {
        echo "     $key: $value\n";
    }
} else {
    echo "   No payments retrieved - checking why...\n";
    
    // Check members table
    $members_table = $wpdb->prefix . 'jgk_members';
    $members_exists = $wpdb->get_var("SHOW TABLES LIKE '$members_table'") == $members_table;
    echo "   Members table exists: " . ($members_exists ? "YES" : "NO") . "\n";
    
    if ($members_exists) {
        $member_count = $wpdb->get_var("SELECT COUNT(*) FROM $members_table");
        echo "   Members count: $member_count\n";
    }
}

// 6. Check WordPress and PHP environment
echo "\n6. Environment info:\n";
echo "   WordPress version: " . get_bloginfo('version') . "\n";
echo "   PHP version: " . PHP_VERSION . "\n";
echo "   Current time: " . current_time('mysql') . "\n";
echo "   Site URL: " . get_site_url() . "\n";
echo "   Is multisite: " . (is_multisite() ? "YES" : "NO") . "\n";

// 7. Check for any blocking factors
echo "\n7. Potential blocking factors:\n";

// Check if WP_DEBUG is enabled
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo "   WP_DEBUG is enabled\n";
} else {
    echo "   WP_DEBUG is disabled\n";
}

// Check error log
$error_log = ini_get('error_log');
echo "   PHP error log: $error_log\n";

echo "\n=== END DEBUG ===\n";
?>