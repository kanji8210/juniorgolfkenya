<?php
/**
 * Provide a admin area view for reports and analytics
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('view_reports')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Load required classes
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';

// Get filter parameters
$report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'overview';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-t');
$coach_id = isset($_GET['coach_id']) ? intval($_GET['coach_id']) : 0;

// Get report data
$overview_stats = JuniorGolfKenya_Database::get_overview_statistics();
$membership_stats_base = JuniorGolfKenya_Database::get_membership_stats();

// Extend membership stats with additional data needed by the report
global $wpdb;
$members_table = $wpdb->prefix . 'jgk_members';
$payments_table = $wpdb->prefix . 'jgk_payments';

// Resolve membership product ID from settings (new) or legacy option
$payment_settings = get_option('jgk_payment_settings', array());
$membership_product_id_legacy = intval(get_option('jgk_membership_product_id', 0));
$membership_product_id_settings = isset($payment_settings['membership_product_id']) ? intval($payment_settings['membership_product_id']) : 0;
$membership_product_id = $membership_product_id_settings ?: $membership_product_id_legacy;

// Check WooCommerce tables presence to avoid SQL errors when WooCommerce is not installed
$wc_order_items_table = $wpdb->prefix . 'woocommerce_order_items';
$wc_order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
$wc_tables_ok = (
    $wpdb->get_var("SHOW TABLES LIKE '{$wc_order_items_table}'") === $wc_order_items_table
) && (
    $wpdb->get_var("SHOW TABLES LIKE '{$wc_order_itemmeta_table}'") === $wc_order_itemmeta_table
);

// Get new members in date range
$new_members = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $members_table WHERE DATE(created_at) BETWEEN %s AND %s",
    $date_from,
    $date_to
));

// Build complete membership stats
$membership_stats = array_merge($membership_stats_base, array(
    'new_members' => $new_members ?? 0,
    'renewals' => 0, // TODO: implement renewals tracking
    'cancellations' => 0, // TODO: implement cancellations tracking
    'net_growth' => $new_members ?? 0,
    'by_type' => array()
));

// Get stats by membership type (with new and revenue for selected period)
$types = $wpdb->get_results("
    SELECT membership_type, COUNT(*) as count 
    FROM $members_table 
    GROUP BY membership_type
");
foreach ($types as $type) {
    $type_key = $type->membership_type;
    $active_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $members_table WHERE membership_type = %s AND status = 'active'",
        $type_key
    ));
    $new_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $members_table WHERE membership_type = %s AND DATE(created_at) BETWEEN %s AND %s",
        $type_key, $date_from, $date_to
    ));
    $type_revenue = 0;
    if ($wpdb->get_var("SHOW TABLES LIKE '$payments_table'") == $payments_table) {
        $type_revenue = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(p.amount),0)
             FROM $payments_table p
             INNER JOIN $members_table m ON p.member_id = m.id
             WHERE m.membership_type = %s AND p.status = 'completed' AND DATE(p.payment_date) BETWEEN %s AND %s",
            $type_key, $date_from, $date_to
        )));
    }
    $membership_stats['by_type'][$type_key] = array(
        'count' => intval($type->count),
        'active' => intval($active_count),
        'new' => intval($new_count),
        'revenue' => $type_revenue,
    );
}

$coaches = JuniorGolfKenya_Database::get_coaches();

// Get enhanced payment stats from WooCommerce orders
$payment_stats = array(
    'total_revenue' => 0,
    'completed_payments' => 0,
    'pending_payments' => 0,
    'failed_payments' => 0,
    'total_payments' => 0,
    'by_method' => array()
);

// Aggregate primary totals from JGK payments (manual + WooCommerce recorded)
if ($wpdb->get_var("SHOW TABLES LIKE '$payments_table'") == $payments_table) {
    $payment_stats['total_revenue'] = floatval($wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM $payments_table WHERE status = 'completed' AND DATE(payment_date) BETWEEN %s AND %s",
        $date_from, $date_to
    )));
    $payment_stats['completed_payments'] = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table WHERE status = 'completed' AND DATE(payment_date) BETWEEN %s AND %s",
        $date_from, $date_to
    )));
    $payment_stats['pending_payments'] = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table WHERE status = 'pending' AND DATE(payment_date) BETWEEN %s AND %s",
        $date_from, $date_to
    )));
    $payment_stats['failed_payments'] = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table WHERE status = 'failed' AND DATE(payment_date) BETWEEN %s AND %s",
        $date_from, $date_to
    )));
    $payment_stats['total_payments'] = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table WHERE DATE(payment_date) BETWEEN %s AND %s",
        $date_from, $date_to
    )));
}

// Query WooCommerce orders for membership payments (for method breakdown and WC-only insights)
if ($membership_product_id && $wc_tables_ok) {
    // Get completed orders with membership product
    $completed_orders = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_date, pm.meta_value as total
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status = 'wc-completed'
        AND oim.meta_key = '_product_id'
        AND oim.meta_value = %d
        AND DATE(p.post_date) BETWEEN %s AND %s
    ", $membership_product_id, $date_from, $date_to));

    $payment_stats['completed_payments'] = count($completed_orders);
    $payment_stats['total_revenue'] = array_sum(array_column($completed_orders, 'total'));

    // Get pending orders
    $pending_orders = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_date, pm.meta_value as total
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status = 'wc-pending'
        AND oim.meta_key = '_product_id'
        AND oim.meta_value = %d
        AND DATE(p.post_date) BETWEEN %s AND %s
    ", $membership_product_id, $date_from, $date_to));

    $payment_stats['pending_payments'] = count($pending_orders);

    // Get failed orders
    $failed_orders = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_date, pm.meta_value as total
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status = 'wc-failed'
        AND oim.meta_key = '_product_id'
        AND oim.meta_value = %d
        AND DATE(p.post_date) BETWEEN %s AND %s
    ", $membership_product_id, $date_from, $date_to));

    $payment_stats['failed_payments'] = count($failed_orders);

    // Total payments (all statuses)
    $all_orders = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_date, pm.meta_value as total, p.post_status
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-failed', 'wc-cancelled', 'wc-refunded')
        AND oim.meta_key = '_product_id'
        AND oim.meta_value = %d
        AND DATE(p.post_date) BETWEEN %s AND %s
    ", $membership_product_id, $date_from, $date_to));

    $payment_stats['total_payments'] = count($all_orders);

    // Get payment methods breakdown
    $payment_methods = $wpdb->get_results($wpdb->prepare("
        SELECT pm_payment.meta_value as payment_method, COUNT(*) as count, SUM(pm_total.meta_value) as amount
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
        INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-failed')
        AND oim.meta_key = '_product_id'
        AND oim.meta_value = %d
        AND DATE(p.post_date) BETWEEN %s AND %s
        GROUP BY pm_payment.meta_value
    ", $membership_product_id, $date_from, $date_to));

    foreach ($payment_methods as $method) {
        $key = $method->payment_method ?: 'other';
        $payment_stats['by_method'][$key] = array(
            'count' => intval($method->count),
            'amount' => floatval($method->amount),
            'success_rate' => 0.0,
        );
    }

    // Compute success rate by method
    $method_status = $wpdb->get_results($wpdb->prepare("\n        SELECT pm_payment.meta_value as payment_method, p.post_status, COUNT(*) as cnt\n        FROM {$wpdb->posts} p\n        INNER JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'\n        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id\n        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id\n        WHERE p.post_type = 'shop_order'\n        AND p.post_status IN ('wc-completed', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-failed', 'wc-cancelled', 'wc-refunded')\n        AND oim.meta_key = '_product_id'\n        AND oim.meta_value = %d\n        AND DATE(p.post_date) BETWEEN %s AND %s\n        GROUP BY pm_payment.meta_value, p.post_status\n    ", $membership_product_id, $date_from, $date_to));

    $tmp = array();
    foreach ($method_status as $row) {
        $m = $row->payment_method ?: 'other';
        if (!isset($tmp[$m])) {
            $tmp[$m] = array('total' => 0, 'completed' => 0);
        }
        $cnt = intval($row->cnt);
        $tmp[$m]['total'] += $cnt;
        if ($row->post_status === 'wc-completed') {
            $tmp[$m]['completed'] += $cnt;
        }
    }
    foreach ($tmp as $m => $vals) {
        if ($vals['total'] > 0 && isset($payment_stats['by_method'][$m])) {
            $payment_stats['by_method'][$m]['success_rate'] = round(($vals['completed'] / $vals['total']) * 100, 1);
        }
    }
}

// Get monthly data for charts (last 12 months)
$monthly_data = array();
for ($i = 11; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end = date('Y-m-t', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));

    $monthly_data[$month_name] = array(
        'members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE DATE(created_at) <= %s", $month_end
        )),
        'revenue' => $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $payments_table WHERE status = 'completed' AND DATE(payment_date) BETWEEN %s AND %s",
            $month_start, $month_end
        )),
        'new_members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE DATE(created_at) BETWEEN %s AND %s",
            $month_start, $month_end
        ))
    );
}

if ($coach_id) {
    // Get coach performance manually (method doesn't exist)
    $coach_performance = array(
        'total_members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE coach_id = %d",
            $coach_id
        )),
        'active_members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE coach_id = %d AND status = 'active'",
            $coach_id
        ))
    );
}
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">Reports & Analytics</h1>
    <hr class="wp-header-end">

    <!-- Report Filters -->
    <div class="jgk-report-filters">
        <form method="get" style="display: flex; gap: 15px; align-items: center; margin: 20px 0;">
            <input type="hidden" name="page" value="juniorgolfkenya-reports">
            
            <label>Report Type:</label>
            <select name="report_type" onchange="this.form.submit()">
                <option value="overview" <?php selected($report_type, 'overview'); ?>>Overview</option>
                <option value="membership" <?php selected($report_type, 'membership'); ?>>Membership</option>
                <option value="payments" <?php selected($report_type, 'payments'); ?>>Payments</option>
                <option value="coaches" <?php selected($report_type, 'coaches'); ?>>Coaches</option>
            </select>
            
            <label>From:</label>
            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
            
            <label>To:</label>
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
            
            <?php if ($report_type === 'coaches'): ?>
            <label>Coach:</label>
            <select name="coach_id">
                <option value="">All Coaches</option>
                <?php foreach ($coaches as $coach): ?>
                <option value="<?php echo $coach->ID; ?>" <?php selected($coach_id, $coach->ID); ?>>
                    <?php echo esc_html($coach->display_name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            
            <input type="submit" class="button-primary" value="Generate Report">
            <button type="button" class="button" onclick="exportReport()">Export PDF</button>
        </form>
    </div>

    <?php if ($report_type === 'overview'): ?>
    <!-- Overview Report -->
    <div class="jgk-report-section">
        <h2>Organization Overview</h2>
        
        <div class="jgk-overview-grid">
            <div class="jgk-overview-card">
                <div class="jgk-overview-icon">👥</div>
                <div class="jgk-overview-content">
                    <h3><?php echo $overview_stats['total_members']; ?></h3>
                    <p>Total Members</p>
                    <small><?php echo $overview_stats['active_members']; ?> active</small>
                </div>
            </div>
            
            <div class="jgk-overview-card">
                <div class="jgk-overview-icon">🏌️</div>
                <div class="jgk-overview-content">
                    <h3><?php echo $overview_stats['total_coaches']; ?></h3>
                    <p>Total Coaches</p>
                    <small><?php echo $overview_stats['active_coaches']; ?> active</small>
                </div>
            </div>
            
            <div class="jgk-overview-card">
                <div class="jgk-overview-icon">💰</div>
                <div class="jgk-overview-content">
                    <h3>KSh <?php echo number_format($overview_stats['total_revenue'], 2); ?></h3>
                    <p>Total Revenue</p>
                    <small>This month: KSh <?php echo number_format($overview_stats['monthly_revenue'], 2); ?></small>
                </div>
            </div>
            
            <div class="jgk-overview-card">
                <div class="jgk-overview-icon">🏆</div>
                <div class="jgk-overview-content">
                    <h3><?php echo $overview_stats['total_tournaments']; ?></h3>
                    <p>Tournaments</p>
                    <small><?php echo $overview_stats['upcoming_tournaments']; ?> upcoming</small>
                </div>
            </div>
        </div>
        
        <div class="jgk-charts-grid">
            <div class="jgk-chart-container">
                <h3>Membership Growth (Last 12 Months)</h3>
                <canvas id="membershipChart" width="400" height="200"></canvas>
            </div>
            <div class="jgk-chart-container">
                <h3>Revenue Trends (Last 12 Months)</h3>
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Additional Charts -->
        <div class="jgk-charts-grid">
            <div class="jgk-chart-container">
                <h3>Membership Status Distribution</h3>
                <canvas id="statusChart" width="400" height="200"></canvas>
            </div>
            <div class="jgk-chart-container">
                <h3>Payment Methods</h3>
                <canvas id="paymentMethodsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <?php elseif ($report_type === 'membership'): ?>
    <!-- Membership Report -->
    <div class="jgk-report-section">
        <h2>Membership Report (<?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>)</h2>
        
        <div class="jgk-membership-stats">
            <div class="jgk-stat-row">
                <span>New Memberships:</span>
                <strong><?php echo $membership_stats['new_members']; ?></strong>
            </div>
            <div class="jgk-stat-row">
                <span>Renewals:</span>
                <strong><?php echo $membership_stats['renewals']; ?></strong>
            </div>
            <div class="jgk-stat-row">
                <span>Cancellations:</span>
                <strong><?php echo $membership_stats['cancellations']; ?></strong>
            </div>
            <div class="jgk-stat-row">
                <span>Net Growth:</span>
                <strong><?php echo $membership_stats['net_growth']; ?></strong>
            </div>
        </div>
        
        <div class="jgk-membership-breakdown">
            <h3>Membership Types</h3>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Active Members</th>
                        <th>New This Period</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($membership_stats['by_type'] as $type => $stats): ?>
                    <tr>
                        <td><?php echo ucfirst($type); ?></td>
                        <td><?php echo $stats['active']; ?></td>
                        <td><?php echo $stats['new']; ?></td>
                        <td>KSh <?php echo number_format($stats['revenue'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($report_type === 'payments'): ?>
    <!-- Payments Report -->
    <div class="jgk-report-section">
        <h2>Payments Report (<?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>)</h2>

        <div class="jgk-payment-summary">
            <div class="jgk-summary-grid">
                <div class="jgk-summary-item">
                    <h4>Total Revenue</h4>
                    <span>KSh <?php echo number_format($payment_stats['total_revenue'], 2); ?></span>
                </div>
                <div class="jgk-summary-item">
                    <h4>Completed Payments</h4>
                    <span><?php echo intval($payment_stats['completed_payments']); ?></span>
                </div>
                <div class="jgk-summary-item">
                    <h4>Pending Payments</h4>
                    <span><?php echo intval($payment_stats['pending_payments']); ?></span>
                </div>
                <div class="jgk-summary-item">
                    <h4>Failed Payments</h4>
                    <span><?php echo intval($payment_stats['failed_payments']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="jgk-payment-breakdown">
            <h3>Payment Methods</h3>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>Transactions</th>
                        <th>Amount</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_stats['by_method'] as $method => $stats): ?>
                    <tr>
                        <td><?php echo ucfirst($method); ?></td>
                        <td><?php echo $stats['count']; ?></td>
                        <td>KSh <?php echo number_format($stats['amount'], 2); ?></td>
                        <td><?php echo number_format($stats['success_rate'], 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="jgk-payment-history">
            <h3>Payment History</h3>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Member</th>
                        <th>Order ID</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get payment history from both WooCommerce orders and JGK payments table
                    $payment_history = array();
                    
                    // Get WooCommerce orders
                    if ($membership_product_id && $wc_tables_ok) {
                        $orders = $wpdb->get_results($wpdb->prepare("
                            SELECT 
                                p.ID as order_id,
                                p.post_date as date_created,
                                CONCAT(u.display_name, ' (', m.membership_number, ')') as member_name,
                                pm_total.meta_value as amount,
                                pm_payment.meta_value as payment_method,
                                CASE 
                                    WHEN p.post_status = 'wc-completed' THEN 'completed'
                                    WHEN p.post_status = 'wc-pending' THEN 'pending'
                                    WHEN p.post_status = 'wc-processing' THEN 'processing'
                                    WHEN p.post_status = 'wc-on-hold' THEN 'on-hold'
                                    WHEN p.post_status = 'wc-failed' THEN 'failed'
                                    ELSE 'unknown'
                                END as status
                            FROM {$wpdb->posts} p
                            INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
                            LEFT JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method_title'
                            LEFT JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
                            LEFT JOIN {$wpdb->users} u ON pm_customer.meta_value = u.ID
                            LEFT JOIN {$members_table} m ON u.ID = m.user_id
                            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
                            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
                            WHERE p.post_type = 'shop_order'
                            AND p.post_status IN ('wc-completed', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-failed')
                            AND oim.meta_key = '_product_id'
                            AND oim.meta_value = %d
                            AND DATE(p.post_date) BETWEEN %s AND %s
                            ORDER BY p.post_date DESC
                            LIMIT 100
                        ", $membership_product_id, $date_from, $date_to));
                        
                        foreach ($orders as $order) {
                            $payment_history[] = array(
                                'date' => $order->date_created,
                                'member' => $order->member_name ?: 'Unknown',
                                'order_id' => $order->order_id,
                                'amount' => $order->amount,
                                'payment_method' => $order->payment_method ?: 'Unknown',
                                'status' => $order->status,
                                'source' => 'woocommerce'
                            );
                        }
                    }
                    
                    // Get payments from JGK payments table
                    if ($wpdb->get_var("SHOW TABLES LIKE '$payments_table'") == $payments_table) {
                        $jgk_payments = $wpdb->get_results($wpdb->prepare("
                            SELECT 
                                p.payment_date as date_created,
                                CONCAT(u.display_name, ' (', m.membership_number, ')') as member_name,
                                p.order_id,
                                p.amount,
                                p.payment_method,
                                p.status
                            FROM {$payments_table} p
                            LEFT JOIN {$members_table} m ON p.member_id = m.id
                            LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                            WHERE DATE(p.payment_date) BETWEEN %s AND %s
                            ORDER BY p.payment_date DESC
                            LIMIT 100
                        ", $date_from, $date_to));
                        
                        foreach ($jgk_payments as $payment) {
                            $payment_history[] = array(
                                'date' => $payment->date_created,
                                'member' => $payment->member_name ?: 'Unknown',
                                'order_id' => $payment->order_id ?: 'N/A',
                                'amount' => $payment->amount,
                                'payment_method' => $payment->payment_method,
                                'status' => $payment->status,
                                'source' => 'jgk'
                            );
                        }
                    }
                    
                    // Sort combined payments by date (most recent first)
                    usort($payment_history, function($a, $b) {
                        return strtotime($b['date']) - strtotime($a['date']);
                    });
                    
                    // Display payments
                    if (empty($payment_history)): ?>
                    <tr>
                        <td colspan="6">No payments found for the selected period.</td>
                    </tr>
                    <?php else:
                        foreach (array_slice($payment_history, 0, 50) as $payment): ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($payment['date'])); ?></td>
                        <td><?php echo esc_html($payment['member']); ?></td>
                        <td><?php echo $payment['order_id']; ?></td>
                        <td>KSh <?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo esc_html($payment['payment_method']); ?></td>
                        <td>
                            <span class="jgk-status-<?php echo esc_attr($payment['status']); ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($report_type === 'coaches'): ?>
    <!-- Coaches Report -->
    <div class="jgk-report-section">
        <h2>Coaches Performance Report</h2>
        
        <?php if ($coach_id && isset($coach_performance)): ?>
        <!-- Individual Coach Report -->
        <div class="jgk-coach-performance">
            <h3><?php echo esc_html($coach_performance['name']); ?> Performance</h3>
            
            <div class="jgk-performance-metrics">
                <div class="jgk-metric">
                    <span>Assigned Members</span>
                    <strong><?php echo $coach_performance['assigned_members']; ?></strong>
                </div>
                <div class="jgk-metric">
                    <span>Average Rating</span>
                    <strong><?php echo number_format($coach_performance['avg_rating'], 1); ?>/5</strong>
                </div>
                <div class="jgk-metric">
                    <span>Training Sessions</span>
                    <strong><?php echo $coach_performance['training_sessions']; ?></strong>
                </div>
                <div class="jgk-metric">
                    <span>Member Improvement</span>
                    <strong><?php echo $coach_performance['improvement_rate']; ?>%</strong>
                </div>
            </div>
            
            <div class="jgk-coach-feedback">
                <h4>Recent Feedback</h4>
                <?php foreach ($coach_performance['recent_feedback'] as $feedback): ?>
                <div class="jgk-feedback-item">
                    <div class="jgk-feedback-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php echo $i <= $feedback->rating ? '★' : '☆'; ?>
                        <?php endfor; ?>
                    </div>
                    <div class="jgk-feedback-content">
                        <p><?php echo esc_html($feedback->comment); ?></p>
                        <small>by <?php echo esc_html($feedback->member_name); ?> on <?php echo date('M j, Y', strtotime($feedback->created_at)); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php else: ?>
        <!-- All Coaches Overview -->
        <div class="jgk-coaches-overview">
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Coach</th>
                        <th>Assigned Members</th>
                        <th>Avg Rating</th>
                        <th>Training Sessions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coaches as $coach): ?>
                    <tr>
                        <td><?php echo esc_html($coach->display_name); ?></td>
                        <td><?php echo $coach->member_count; ?></td>
                        <td>
                            <?php if ($coach->avg_rating): ?>
                            <?php echo number_format($coach->avg_rating, 1); ?>/5
                            <?php else: ?>
                            No ratings
                            <?php endif; ?>
                        </td>
                        <td><?php echo $coach->training_sessions ?: 0; ?></td>
                        <td>
                            <span class="jgk-status-<?php echo esc_attr($coach->status); ?>">
                                <?php echo ucfirst($coach->status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="?page=juniorgolfkenya-reports&report_type=coaches&coach_id=<?php echo $coach->ID; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="button button-small">
                                View Details
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.jgk-report-filters {
    background: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.jgk-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.jgk-overview-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.jgk-overview-icon {
    font-size: 2em;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f6ff;
    border-radius: 50%;
}

.jgk-overview-content h3 {
    font-size: 1.8em;
    margin: 0;
    color: #2271b1;
}

.jgk-overview-content p {
    margin: 5px 0;
    font-weight: 600;
}

.jgk-overview-content small {
    color: #666;
}

.jgk-charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 30px 0;
}

.jgk-chart-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.jgk-membership-stats,
.jgk-payment-summary {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
    margin: 20px 0;
}

.jgk-stat-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #ddd;
}

.jgk-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.jgk-summary-item {
    text-align: center;
}

.jgk-summary-item h4 {
    margin: 0 0 10px 0;
    color: #666;
}

.jgk-summary-item span {
    font-size: 1.5em;
    font-weight: bold;
    color: #2271b1;
}

.jgk-performance-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.jgk-metric {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
}

.jgk-feedback-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin: 10px 0;
}

.jgk-feedback-rating {
    color: #ffb900;
    margin-bottom: 10px;
}
</style>

<script>
function exportReport() {
    // Generate PDF export
    const reportType = '<?php echo $report_type; ?>';
    const dateFrom = '<?php echo $date_from; ?>';
    const dateTo = '<?php echo $date_to; ?>';
    const coachId = <?php echo $coach_id ?: 0; ?>;
    
    const url = `<?php echo admin_url('admin-ajax.php'); ?>?action=jgk_export_report&report_type=${reportType}&date_from=${dateFrom}&date_to=${dateTo}&coach_id=${coachId}`;
    window.open(url, '_blank');
}

// Initialize charts if overview report
<?php if ($report_type === 'overview'): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Membership growth chart
    const membershipCtx = document.getElementById('membershipChart');
    if (membershipCtx) {
        new Chart(membershipCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($monthly_data)); ?>,
                datasets: [{
                    label: 'Total Members',
                    data: <?php echo json_encode(array_column($monthly_data, 'members')); ?>,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'New Members',
                    data: <?php echo json_encode(array_column($monthly_data, 'new_members')); ?>,
                    borderColor: '#46b450',
                    backgroundColor: 'rgba(70, 180, 80, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Membership Growth Over Time'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Revenue chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($monthly_data)); ?>,
                datasets: [{
                    label: 'Monthly Revenue (KSh)',
                    data: <?php echo json_encode(array_column($monthly_data, 'revenue')); ?>,
                    backgroundColor: '#ffb900',
                    borderColor: '#f39c12',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Revenue Trends'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KSh ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Status distribution chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        const statusData = {
            labels: ['Active', 'Pending', 'Approved', 'Expired', 'Suspended'],
            datasets: [{
                data: [
                    <?php echo $overview_stats['active_members']; ?>,
                    <?php echo $overview_stats['pending_members'] ?? 0; ?>,
                    <?php echo $overview_stats['approved_members'] ?? 0; ?>,
                    <?php echo $overview_stats['expired_members'] ?? 0; ?>,
                    <?php echo $overview_stats['suspended_members'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#46b450',
                    '#f39c12',
                    '#3498db',
                    '#e74c3c',
                    '#95a5a6'
                ],
                borderWidth: 1
            }]
        };

        new Chart(statusCtx, {
            type: 'doughnut',
            data: statusData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Membership Status Distribution'
                    }
                }
            }
        });
    }

    // Payment methods chart
    const paymentMethodsCtx = document.getElementById('paymentMethodsChart');
    if (paymentMethodsCtx) {
        const methodLabels = <?php echo json_encode(array_keys($payment_stats['by_method'])); ?>;
        const methodData = <?php echo json_encode(array_column($payment_stats['by_method'], 'amount')); ?>;

        new Chart(paymentMethodsCtx, {
            type: 'pie',
            data: {
                labels: methodLabels,
                datasets: [{
                    data: methodData,
                    backgroundColor: [
                        '#3498db',
                        '#e74c3c',
                        '#f39c12',
                        '#9b59b6',
                        '#1abc9c'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Payment Methods Distribution'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': KSh ' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
});
<?php endif; ?>

// Handle PDF export
document.getElementById('export-pdf-btn').addEventListener('click', function() {
    const reportType = document.getElementById('report-type').value;
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;

    if (!startDate || !endDate) {
        alert('Please select both start and end dates for the export.');
        return;
    }

    // Create form to submit PDF export request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = jgkAjax.ajaxurl;
    form.target = '_blank'; // Open in new tab

    // Add form fields
    const fields = {
        'action': 'jgk_export_reports_pdf',
        'nonce': jgkAjax.reports_nonce,
        'report_type': reportType,
        'start_date': startDate,
        'end_date': endDate
    };

    for (const [key, value] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
});
</script>