<?php
/**
 * ADMIN INTERFACE DIAGNOSTIC
 * This script tests the specific issues with payment display in admin interface
 */

require_once('../../../../wp-load.php');
require_once('../includes/class-juniorgolfkenya-database.php');

echo "=== ADMIN INTERFACE PAYMENT DISPLAY DIAGNOSTIC ===\n\n";

// 1. Test the exact admin page scenario
echo "1. ADMIN PAGE SIMULATION\n";

// Check if we can access the admin functionality
if (!function_exists('current_user_can')) {
    echo "   ❌ WordPress user functions not available\n";
} else {
    echo "   ✅ WordPress user functions available\n";
}

// Simulate getting payments with all filters as 'all' (default admin page state)
echo "   Testing default admin page call: get_payments('all', 'all', '', '')\n";

try {
    $start_time = microtime(true);
    $payments = JuniorGolfKenya_Database::get_payments('all', 'all', '', '');
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    echo "   ✅ Query executed in " . round($execution_time, 2) . "ms\n";
    echo "   ✅ Retrieved " . count($payments) . " payments\n";
    
    if (count($payments) > 0) {
        echo "   Payment data structure check:\n";
        $first_payment = $payments[0];
        $required_fields = array('id', 'member_id', 'amount', 'status', 'payment_type', 'payment_method', 'created_at', 'member_name', 'source');
        
        foreach ($required_fields as $field) {
            if (property_exists($first_payment, $field)) {
                echo "     ✅ $field: " . (is_null($first_payment->$field) ? 'NULL' : 'OK') . "\n";
            } else {
                echo "     ❌ $field: MISSING\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ get_payments() failed: " . $e->getMessage() . "\n";
}
echo "\n";

// 2. Test specific filter combinations that might cause issues
echo "2. FILTER COMBINATION TESTS\n";

$filter_tests = array(
    array('status' => 'completed', 'type' => 'all', 'desc' => 'Completed payments only'),
    array('status' => 'pending', 'type' => 'all', 'desc' => 'Pending payments only'),
    array('status' => 'all', 'type' => 'membership', 'desc' => 'Membership payments only'),
    array('status' => 'completed', 'type' => 'membership', 'desc' => 'Completed membership payments'),
);

foreach ($filter_tests as $test) {
    echo "   Testing: {$test['desc']}\n";
    try {
        $filtered_payments = JuniorGolfKenya_Database::get_payments($test['status'], $test['type'], '', '');
        echo "     ✅ Result: " . count($filtered_payments) . " payments\n";
    } catch (Exception $e) {
        echo "     ❌ Failed: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 3. Database Query Analysis
echo "3. DATABASE QUERY ANALYSIS\n";

global $wpdb;
$payments_table = $wpdb->prefix . 'jgk_payments';
$members_table = $wpdb->prefix . 'jgk_members';
$users_table = $wpdb->users;

// Test the manual payments query (main part of get_payments)
echo "   Testing manual payments query...\n";
$manual_query = "
    SELECT p.*,
           CONCAT(u.display_name, ' (', m.membership_number, ')') as member_name,
           'manual' as source
    FROM $payments_table p
    LEFT JOIN $members_table m ON p.member_id = m.id
    LEFT JOIN $users_table u ON m.user_id = u.ID
    WHERE 1=1
    ORDER BY p.created_at DESC
";

$start_time = microtime(true);
$manual_results = $wpdb->get_results($manual_query);
$end_time = microtime(true);
$query_time = ($end_time - $start_time) * 1000;

if ($wpdb->last_error) {
    echo "   ❌ Query failed: " . $wpdb->last_error . "\n";
} else {
    echo "   ✅ Query successful in " . round($query_time, 2) . "ms\n";
    echo "   ✅ Retrieved " . count($manual_results) . " manual payments\n";
}

// Check for NULL member_name issues
if (count($manual_results) > 0) {
    $null_member_names = array_filter($manual_results, function($p) {
        return is_null($p->member_name) || $p->member_name === '' || strpos($p->member_name, '(') === false;
    });
    
    if (count($null_member_names) > 0) {
        echo "   ⚠️  " . count($null_member_names) . " payments have NULL/incomplete member names\n";
        echo "     This suggests member linking issues\n";
        
        // Show details of first problematic payment
        $problem_payment = $null_member_names[0];
        echo "     Sample problem payment: ID {$problem_payment->id}, Member ID {$problem_payment->member_id}\n";
        
        // Check if the member exists
        $member_check = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, u.display_name FROM $members_table m LEFT JOIN $users_table u ON m.user_id = u.ID WHERE m.id = %d",
            $problem_payment->member_id
        ));
        
        if ($member_check) {
            echo "     Member exists: {$member_check->display_name} (Membership: {$member_check->membership_number})\n";
        } else {
            echo "     ❌ Member ID {$problem_payment->member_id} not found in members table\n";
        }
    } else {
        echo "   ✅ All payments have proper member names\n";
    }
}
echo "\n";

// 4. Test WooCommerce Integration
echo "4. WOOCOMMERCE INTEGRATION TEST\n";

$wc_tables = array(
    'posts' => $wpdb->posts,
    'postmeta' => $wpdb->postmeta,
    'order_items' => $wpdb->prefix . 'woocommerce_order_items',
    'order_itemmeta' => $wpdb->prefix . 'woocommerce_order_itemmeta'
);

$wc_available = true;
echo "   Checking WooCommerce table availability:\n";
foreach ($wc_tables as $name => $table) {
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
    echo "     $name ($table): " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
    if (!$exists) {
        $wc_available = false;
    }
}

if ($wc_available) {
    echo "   ✅ WooCommerce tables available\n";
    
    // Check membership product configuration
    $legacy_membership_id = intval(get_option('jgk_membership_product_id', 0));
    $payment_settings = get_option('jgk_payment_settings', array());
    $settings_membership_id = intval($payment_settings['membership_product_id'] ?? 0);
    $membership_product_id = $legacy_membership_id > 0 ? $legacy_membership_id : $settings_membership_id;
    
    echo "   Membership product ID: $membership_product_id\n";
    
    if ($membership_product_id > 0) {
        // Check for WooCommerce orders
        $wc_order_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT o.ID)
            FROM {$wpdb->posts} o
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            WHERE oim.meta_key = '_product_id' AND oim.meta_value = %d
            AND o.post_type = 'shop_order'
        ", $membership_product_id));
        
        echo "   WooCommerce orders for membership product: $wc_order_count\n";
    } else {
        echo "   ⚠️  No membership product ID configured\n";
    }
} else {
    echo "   ❌ WooCommerce not available (missing tables)\n";
}
echo "\n";

// 5. Admin Interface Specific Issues
echo "5. ADMIN INTERFACE SPECIFIC CHECKS\n";

// Check if admin styles/scripts would load
echo "   Checking admin assets:\n";
$admin_css = JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/css/juniorgolfkenya-admin.css';
$admin_js = JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/js/juniorgolfkenya-admin.js';

if (file_exists($admin_css)) {
    echo "   ✅ Admin CSS exists\n";
} else {
    echo "   ❌ Admin CSS missing\n";
}

if (file_exists($admin_js)) {
    echo "   ✅ Admin JS exists\n";
} else {
    echo "   ❌ Admin JS missing\n";
}

// Check for admin page file
$admin_payments_file = JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-payments.php';
if (file_exists($admin_payments_file)) {
    echo "   ✅ Admin payments page file exists\n";
    
    // Check if it's readable and has no syntax errors
    $content = @file_get_contents($admin_payments_file);
    if ($content === false) {
        echo "   ❌ Cannot read admin payments file\n";
    } else {
        echo "   ✅ Admin payments file readable (" . strlen($content) . " bytes)\n";
        
        // Basic syntax check
        if (strpos($content, '<?php') === 0) {
            echo "   ✅ Admin payments file has proper PHP opening tag\n";
        } else {
            echo "   ❌ Admin payments file missing PHP opening tag\n";
        }
    }
} else {
    echo "   ❌ Admin payments page file missing\n";
}
echo "\n";

// 6. Memory and Performance Check
echo "6. PERFORMANCE AND RESOURCE CHECK\n";

$memory_limit = ini_get('memory_limit');
$memory_usage = memory_get_usage(true);
$memory_peak = memory_get_peak_usage(true);

echo "   Memory limit: $memory_limit\n";
echo "   Current usage: " . round($memory_usage / 1024 / 1024, 2) . " MB\n";
echo "   Peak usage: " . round($memory_peak / 1024 / 1024, 2) . " MB\n";

// Test performance with a larger query
if (count($payments) > 0) {
    echo "   Testing performance with payment processing...\n";
    $start_memory = memory_get_usage();
    $start_time = microtime(true);
    
    // Simulate the admin page calculations
    $total_amount = array_sum(array_map(function($p) { return $p->amount; }, $payments));
    $pending_amount = array_sum(array_map(function($p) { return $p->status === 'pending' ? $p->amount : 0; }, $payments));
    $completed_amount = array_sum(array_map(function($p) { return $p->status === 'completed' ? $p->amount : 0; }, $payments));
    
    $end_time = microtime(true);
    $end_memory = memory_get_usage();
    
    $processing_time = ($end_time - $start_time) * 1000;
    $memory_used = $end_memory - $start_memory;
    
    echo "   ✅ Payment processing completed in " . round($processing_time, 2) . "ms\n";
    echo "   ✅ Memory used: " . round($memory_used / 1024, 2) . " KB\n";
    echo "   ✅ Calculated totals: Total=KSh" . number_format($total_amount, 2) . 
         ", Pending=KSh" . number_format($pending_amount, 2) . 
         ", Completed=KSh" . number_format($completed_amount, 2) . "\n";
}
echo "\n";

// 7. Final Summary
echo "7. DIAGNOSTIC SUMMARY\n";

if (count($payments) > 0) {
    echo "   ✅ GOOD: Payment data is available (" . count($payments) . " payments)\n";
    echo "   ✅ GOOD: get_payments() function works\n";
    echo "   ✅ GOOD: Database queries execute successfully\n";
    
    echo "\n   If admin interface still shows 'No payments', check:\n";
    echo "   • User permissions (current_user_can('manage_payments'))\n";
    echo "   • JavaScript errors in browser console\n";
    echo "   • AJAX response errors in network tab\n";
    echo "   • Admin page URL and routing\n";
    echo "   • WordPress admin hooks and filters\n";
    
} else {
    echo "   ❌ ISSUE: No payment data available\n";
    echo "   This explains why admin interface shows 'No payments'\n";
    echo "\n   Possible causes:\n";
    echo "   • No payments have been recorded yet\n";
    echo "   • Database query issues\n";
    echo "   • Table relationship problems\n";
    echo "   • Data was deleted or corrupted\n";
}

echo "\n=== END ADMIN INTERFACE DIAGNOSTIC ===\n";

// Save diagnostic results
$diagnostic_results = array(
    'timestamp' => date('Y-m-d H:i:s'),
    'payment_count' => count($payments),
    'database_responsive' => !$wpdb->last_error,
    'woocommerce_available' => $wc_available,
    'admin_files_exist' => file_exists($admin_payments_file),
    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
    'query_performance_ms' => isset($query_time) ? round($query_time, 2) : null
);

file_put_contents('admin_diagnostic_' . date('Y-m-d_H-i-s') . '.json', json_encode($diagnostic_results, JSON_PRETTY_PRINT));
echo "Diagnostic results saved for comparison\n";
?>