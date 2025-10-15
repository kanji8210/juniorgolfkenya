<?php
require_once('../../../../wp-load.php');
require_once('../includes/class-juniorgolfkenya-database.php');

echo "=== TESTING PAYMENT DISPLAY ISSUES ===\n\n";

// Test the exact same call that the admin page makes
echo "1. Testing get_payments() with no filters (same as admin page):\n";
$payments = JuniorGolfKenya_Database::get_payments('all', 'all', '', '');
echo "   Retrieved " . count($payments) . " payments\n\n";

if (count($payments) > 0) {
    echo "2. Payment details:\n";
    foreach ($payments as $index => $payment) {
        echo "   Payment " . ($index + 1) . ":\n";
        echo "     ID: {$payment->id}\n";
        echo "     Member ID: {$payment->member_id}\n";
        echo "     Member Name: " . (isset($payment->member_name) ? $payment->member_name : 'N/A') . "\n";
        echo "     Amount: {$payment->amount}\n";
        echo "     Status: {$payment->status}\n";
        echo "     Source: {$payment->source}\n";
        echo "     Date: {$payment->created_at}\n";
        echo "     Payment Type: {$payment->payment_type}\n";
        echo "     Payment Method: {$payment->payment_method}\n";
        echo "     Notes: " . (isset($payment->notes) ? $payment->notes : 'N/A') . "\n";
        echo "\n";
    }
} else {
    echo "2. No payments found - investigating:\n";
    
    global $wpdb;
    
    // Check direct query to payments table
    $payments_table = $wpdb->prefix . 'jgk_payments';
    $direct_count = $wpdb->get_var("SELECT COUNT(*) FROM $payments_table");
    echo "   Direct count from payments table: $direct_count\n";
    
    if ($direct_count > 0) {
        echo "   Direct query shows payments exist, but get_payments() returns none.\n";
        echo "   This suggests an issue in the get_payments() method.\n";
        
        // Test the exact query from get_payments
        $members_table = $wpdb->prefix . 'jgk_members';
        $users_table = $wpdb->users;
        
        $query = "
            SELECT p.*,
                   CONCAT(u.display_name, ' (', m.membership_number, ')') as member_name,
                   'manual' as source
            FROM $payments_table p
            LEFT JOIN $members_table m ON p.member_id = m.id
            LEFT JOIN $users_table u ON m.user_id = u.ID
            WHERE 1=1
            ORDER BY p.created_at DESC
        ";
        
        echo "   Testing direct query:\n";
        $direct_payments = $wpdb->get_results($query);
        echo "   Direct query returned: " . count($direct_payments) . " payments\n";
        
        if (count($direct_payments) > 0) {
            echo "   Sample direct payment:\n";
            $sample = $direct_payments[0];
            foreach ($sample as $key => $value) {
                echo "     $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
            }
        }
        
        // Check members table
        $members_exists = $wpdb->get_var("SHOW TABLES LIKE '$members_table'") == $members_table;
        echo "\n   Members table exists: " . ($members_exists ? "YES" : "NO") . "\n";
        
        if ($members_exists) {
            $members_count = $wpdb->get_var("SELECT COUNT(*) FROM $members_table");
            echo "   Members count: $members_count\n";
            
            if ($members_count > 0) {
                // Check if any members are linked to payments
                $linked_members = $wpdb->get_results("
                    SELECT DISTINCT p.member_id, m.id as member_exists, u.display_name
                    FROM $payments_table p
                    LEFT JOIN $members_table m ON p.member_id = m.id
                    LEFT JOIN $users_table u ON m.user_id = u.ID
                    LIMIT 5
                ");
                
                echo "   Member linkage check:\n";
                foreach ($linked_members as $link) {
                    echo "     Payment member_id: {$link->member_id}, Member exists: " . 
                         ($link->member_exists ? "YES" : "NO") . 
                         ", Display name: " . ($link->display_name ?: 'N/A') . "\n";
                }
            }
        }
    }
}

// 3. Test with specific filters
echo "\n3. Testing with specific filters:\n";

$completed_payments = JuniorGolfKenya_Database::get_payments('completed', 'all', '', '');
echo "   Completed payments: " . count($completed_payments) . "\n";

$membership_payments = JuniorGolfKenya_Database::get_payments('all', 'membership', '', '');
echo "   Membership payments: " . count($membership_payments) . "\n";

// 4. Check WooCommerce integration
echo "\n4. WooCommerce integration check:\n";

$legacy_membership_id = intval(get_option('jgk_membership_product_id', 0));
$payment_settings = get_option('jgk_payment_settings', array());
$settings_membership_id = intval($payment_settings['membership_product_id'] ?? 0);
$membership_product_id = $legacy_membership_id > 0 ? $legacy_membership_id : $settings_membership_id;

echo "   Legacy membership product ID: $legacy_membership_id\n";
echo "   Settings membership product ID: $settings_membership_id\n";
echo "   Final membership product ID: $membership_product_id\n";

// Check WooCommerce tables
global $wpdb;
$wc_tables_check = array(
    'posts' => $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->posts)) === $wpdb->posts,
    'postmeta' => $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->postmeta)) === $wpdb->postmeta,
    'woocommerce_order_items' => $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'woocommerce_order_items')) === ($wpdb->prefix . 'woocommerce_order_items'),
    'woocommerce_order_itemmeta' => $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'woocommerce_order_itemmeta')) === ($wpdb->prefix . 'woocommerce_order_itemmeta')
);

echo "   WooCommerce tables:\n";
foreach ($wc_tables_check as $table => $exists) {
    echo "     $table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

if ($membership_product_id > 0 && all_wc_tables_exist($wc_tables_check)) {
    $wc_orders = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT o.ID)
        FROM {$wpdb->posts} o
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id 
        WHERE oim.meta_key = '_product_id' AND oim.meta_value = %d
        AND o.post_type = 'shop_order'
    ", $membership_product_id));
    
    echo "   WooCommerce orders for membership product: $wc_orders\n";
}

function all_wc_tables_exist($checks) {
    return array_reduce($checks, function($carry, $item) {
        return $carry && $item;
    }, true);
}

echo "\n=== END PAYMENT DISPLAY TEST ===\n";
?>