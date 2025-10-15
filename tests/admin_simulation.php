<?php
require_once('../../../../wp-load.php');
require_once('../includes/class-juniorgolfkenya-database.php');

echo "=== SIMULATING ADMIN PAYMENTS PAGE ===\n\n";

// Simulate the exact same logic as in juniorgolfkenya-admin-payments.php

// 1. Check user permissions (we'll skip this in CLI)
echo "1. User Permissions Check: SKIPPED (CLI context)\n\n";

// 2. Process the same filter parameters as the admin page
$status_filter = 'all'; // Default
$type_filter = 'all';   // Default  
$date_from = '';        // Default
$date_to = '';          // Default

echo "2. Filter Parameters:\n";
echo "   Status filter: $status_filter\n";
echo "   Type filter: $type_filter\n";
echo "   Date from: " . ($date_from ?: 'none') . "\n";
echo "   Date to: " . ($date_to ?: 'none') . "\n\n";

// 3. Get payments using the exact same call
echo "3. Getting payments...\n";
$payments = JuniorGolfKenya_Database::get_payments($status_filter, $type_filter, $date_from, $date_to);
echo "   Retrieved " . count($payments) . " payments\n\n";

// 4. Get members (for dropdown in admin)
echo "4. Getting members...\n";
$members = JuniorGolfKenya_Database::get_members();
echo "   Retrieved " . count($members) . " members\n\n";

// 5. Calculate totals (same as admin page)
echo "5. Calculating totals...\n";
$total_amount = array_sum(array_map(function($p) { return $p->amount; }, $payments));
$pending_amount = array_sum(array_map(function($p) { return $p->status === 'pending' ? $p->amount : 0; }, $payments));
$completed_amount = array_sum(array_map(function($p) { return $p->status === 'completed' ? $p->amount : 0; }, $payments));

echo "   Total amount: KSh " . number_format($total_amount, 2) . "\n";
echo "   Pending amount: KSh " . number_format($pending_amount, 2) . "\n";
echo "   Completed amount: KSh " . number_format($completed_amount, 2) . "\n";
echo "   Total transactions: " . count($payments) . "\n\n";

// 6. Debug section (same as admin page)
echo "6. Debug section (same as admin page):\n";
global $wpdb;
$prefix = $wpdb->prefix;

$dbg = array();

// Detect membership product id from either legacy option or new settings array
$legacy_membership_id = intval(get_option('jgk_membership_product_id', 0));
$payment_settings = get_option('jgk_payment_settings', array());
$settings_membership_id = intval($payment_settings['membership_product_id'] ?? 0);
$membership_product_id = $legacy_membership_id > 0 ? $legacy_membership_id : $settings_membership_id;

echo "   Membership product ID detection:\n";
echo "     Legacy: $legacy_membership_id\n";
echo "     Settings: $settings_membership_id\n";
echo "     Final: $membership_product_id\n\n";

// Table existence
$tables = array(
    'jgk_payments' => $prefix . 'jgk_payments',
    'wp_posts' => $wpdb->posts,
    'wp_postmeta' => $wpdb->postmeta,
    'wc_order_items' => $prefix . 'woocommerce_order_items',
    'wc_order_itemmeta' => $prefix . 'woocommerce_order_itemmeta',
);

echo "   Table existence check:\n";
foreach ($tables as $label => $tbl) {
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tbl)) === $tbl;
    $dbg['tables'][$label] = array('name' => $tbl, 'exists' => $exists);
    echo "     $label ($tbl): " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}
echo "\n";

// Counts from JGK payments table
if (!empty($dbg['tables']['jgk_payments']['exists'])) {
    echo "   JGK payments table analysis:\n";
    $jgk_total = intval($wpdb->get_var("SELECT COUNT(*) FROM {$tables['jgk_payments']}"));
    $jgk_completed = intval($wpdb->get_var("SELECT COUNT(*) FROM {$tables['jgk_payments']} WHERE status='completed'"));
    $jgk_by_gateway = $wpdb->get_results("SELECT COALESCE(payment_gateway,'') as gateway, COUNT(*) as cnt FROM {$tables['jgk_payments']} GROUP BY payment_gateway", ARRAY_A);
    
    echo "     Total count: $jgk_total\n";
    echo "     Completed: $jgk_completed\n";
    echo "     By gateway:\n";
    foreach ($jgk_by_gateway as $gw) {
        echo "       " . ($gw['gateway'] ?: 'empty') . ": " . $gw['cnt'] . "\n";
    }
    
    $dbg['jgk'] = array(
        'count' => $jgk_total,
        'completed' => $jgk_completed,
        'by_gateway' => $jgk_by_gateway,
    );
}
echo "\n";

// 7. WooCommerce analysis (same as admin page)
$wc_sources_ok = (!empty($dbg['tables']['wp_posts']['exists']) && !empty($dbg['tables']['wp_postmeta']['exists']) && !empty($dbg['tables']['wc_order_items']['exists']) && !empty($dbg['tables']['wc_order_itemmeta']['exists']));

echo "7. WooCommerce analysis:\n";
echo "   WC sources OK: " . ($wc_sources_ok ? 'YES' : 'NO') . "\n";

if ($wc_sources_ok) {
    $statuses = "('wc-completed','wc-processing','wc-pending','wc-on-hold','wc-cancelled','wc-refunded','wc-failed')";
    $wc_membership_count = null;
    
    if ($membership_product_id > 0) {
        $wc_membership_count = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT o.ID)
             FROM {$wpdb->posts} o
             INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id' AND oim.meta_value = %d
             WHERE o.post_type = 'shop_order' AND o.post_status IN $statuses",
            $membership_product_id
        )));
    }
    
    echo "   WC membership orders: " . ($wc_membership_count !== null ? $wc_membership_count : 'N/A (no product ID)') . "\n";
} else {
    echo "   WooCommerce tables missing - integration disabled\n";
}
echo "\n";

// 8. Check if payments would display in admin interface
echo "8. Payment Display Check:\n";
if (count($payments) > 0) {
    echo "   SUCCESS: " . count($payments) . " payments would be displayed\n";
    echo "   First payment preview:\n";
    $first = $payments[0];
    echo "     ID: {$first->id}\n";
    echo "     Member: {$first->member_name}\n";
    echo "     Amount: KSh " . number_format($first->amount, 2) . "\n";
    echo "     Status: {$first->status}\n";
    echo "     Date: {$first->created_at}\n";
    echo "     Source: {$first->source}\n";
} else {
    echo "   PROBLEM: No payments would be displayed in admin interface\n";
    echo "   This means the admin page would show 'No payments found'\n";
}
echo "\n";

// 9. Test recording a new payment (same process as admin form)
echo "9. Testing payment recording (simulating admin form):\n";
$test_member_id = 22; // Use an existing member
$test_amount = 100.00;
$test_type = 'membership';
$test_method = 'diagnostic_test';
$test_notes = 'Diagnostic test from environment check';

echo "   Simulating form submission:\n";
echo "   Member ID: $test_member_id\n";
echo "   Amount: $test_amount\n";
echo "   Type: $test_type\n";
echo "   Method: $test_method\n";
echo "   Notes: $test_notes\n";

// Server-side validation (same as admin page)
if (!is_numeric($test_amount) || $test_amount <= 1) {
    echo "   ❌ VALIDATION FAILED: Amount must be greater than 1\n";
} else {
    echo "   ✅ Validation passed\n";
    
    $result = JuniorGolfKenya_Database::record_manual_payment($test_member_id, $test_amount, $test_type, $test_method, $test_notes);
    
    if ($result) {
        echo "   ✅ Payment recorded successfully! ID: $result\n";
        
        // Check if it appears in the list immediately
        $updated_payments = JuniorGolfKenya_Database::get_payments();
        echo "   Updated payment count: " . count($updated_payments) . "\n";
        
        // Clean up test payment
        $wpdb->delete($wpdb->prefix . 'jgk_payments', array('id' => $result));
        echo "   Test payment cleaned up\n";
    } else {
        echo "   ❌ Failed to record payment\n";
        $err = $wpdb->last_error;
        if (!empty($err)) {
            echo "   Database error: $err\n";
        }
    }
}

echo "\n=== END ADMIN PAGE SIMULATION ===\n";

// 10. Generate a comparison checklist
echo "\n10. COMPARISON CHECKLIST FOR ONLINE ENVIRONMENT:\n";
echo "    When you upload this script to your online server, check:\n\n";
echo "    A. Database connectivity:\n";
echo "       - DB_HOST, DB_NAME, DB_USER are correct for production\n";
echo "       - All required tables exist\n";
echo "       - Table permissions allow SELECT/INSERT\n\n";
echo "    B. WordPress configuration:\n";
echo "       - Plugin is active\n";
echo "       - User has 'manage_payments' capability\n";
echo "       - No fatal PHP errors\n\n";
echo "    C. File permissions:\n";
echo "       - Plugin files are readable\n";
echo "       - No permission issues\n\n";
echo "    D. PHP/Server environment:\n";
echo "       - PHP version compatibility (needs 7.4+)\n";
echo "       - Memory limits adequate\n";
echo "       - Error reporting enabled to catch issues\n\n";
echo "    E. Payment recording:\n";
echo "       - Test payment recording works\n";
echo "       - get_payments() returns data\n";
echo "       - Admin interface shows payments\n\n";

// Save detailed environment info for comparison
$detailed_report = array(
    'environment' => 'local',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => array(
        'host' => DB_HOST,
        'name' => DB_NAME,
        'prefix' => $wpdb->prefix,
        'tables' => $dbg['tables'] ?? array(),
        'payment_count' => count($payments)
    ),
    'wordpress' => array(
        'version' => get_bloginfo('version'),
        'site_url' => get_site_url(),
        'debug_enabled' => defined('WP_DEBUG') && WP_DEBUG,
        'plugin_active' => is_plugin_active('juniorgolfkenya/juniorgolfkenya.php')
    ),
    'php' => array(
        'version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ),
    'payments' => array(
        'total_count' => count($payments),
        'total_amount' => $total_amount,
        'by_status' => array(
            'completed' => count(array_filter($payments, function($p) { return $p->status === 'completed'; })),
            'pending' => count(array_filter($payments, function($p) { return $p->status === 'pending'; })),
            'failed' => count(array_filter($payments, function($p) { return $p->status === 'failed'; }))
        )
    )
);

file_put_contents('detailed_environment_local.json', json_encode($detailed_report, JSON_PRETTY_PRINT));
echo "    Detailed report saved: detailed_environment_local.json\n";
echo "    Upload this script to production and compare the outputs!\n";
?>