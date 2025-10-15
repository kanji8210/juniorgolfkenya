<?php
/**
 * Reports & Analytics Admin View (clean rebuilt version)
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('view_reports')) { 
    wp_die(__('You do not have sufficient permissions to access this page.')); 
}

global $wpdb;

// ------------------------------------------------------------------
// Filters (defaults)
// ------------------------------------------------------------------
$report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'overview';
$date_from   = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
$date_to     = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
$coach_id    = isset($_GET['coach_id']) ? intval($_GET['coach_id']) : 0;

// Custom tables
$members_table  = $wpdb->prefix . 'jgk_members';
$payments_table = $wpdb->prefix . 'jgk_payments';

// Membership product id (legacy option or settings)
$legacy_membership_id   = intval(get_option('jgk_membership_product_id', 0));
$payment_settings       = get_option('jgk_payment_settings', array());
$settings_membership_id = intval($payment_settings['membership_product_id'] ?? 0);
$membership_product_id  = $legacy_membership_id > 0 ? $legacy_membership_id : $settings_membership_id;
if ($membership_product_id <= 0) { 
    $membership_product_id = null; 
}

// WooCommerce table availability (for breakdown only)
$wc_tables_ok = (
    $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->posts)) === $wpdb->posts &&
    $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->postmeta)) === $wpdb->postmeta &&
    $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'woocommerce_order_items')) === ($wpdb->prefix . 'woocommerce_order_items') &&
    $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'woocommerce_order_itemmeta')) === ($wpdb->prefix . 'woocommerce_order_itemmeta')
);

// ------------------------------------------------------------------
// Overview Stats (safe defaults if tables absent)
// ------------------------------------------------------------------
$overview_stats = array(
    'total_members' => 0,
    'active_members' => 0,
    'monthly_revenue' => 0,
    'total_revenue' => 0,
    'total_coaches' => 0,
    'active_coaches' => 0,
    'total_tournaments' => 0,
    'upcoming_tournaments' => 0,
);

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $members_table)) === $members_table) {
    $overview_stats['total_members']  = intval($wpdb->get_var("SELECT COUNT(*) FROM $members_table"));
    $overview_stats['active_members'] = intval($wpdb->get_var("SELECT COUNT(*) FROM $members_table WHERE status='active'"));
}

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $payments_table)) === $payments_table) {
    // JGK payments revenue
    $jgk_revenue = floatval($wpdb->get_var("SELECT SUM(amount) FROM $payments_table WHERE status='completed'") ?: 0);
    $month_start = date('Y-m-01');
    $jgk_monthly_revenue = floatval($wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $payments_table WHERE status='completed' AND DATE(payment_date) BETWEEN %s AND %s", $month_start, date('Y-m-d'))) ?: 0);

    // WooCommerce revenue (if tables exist)
    $wc_revenue = 0;
    $wc_monthly_revenue = 0;

    if ($wc_tables_ok) {
        $wc_revenue = floatval($wpdb->get_var("
            SELECT SUM(om_total.meta_value)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} om_total ON p.ID = om_total.post_id AND om_total.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed')
        ") ?: 0);

        $wc_monthly_revenue = floatval($wpdb->get_var($wpdb->prepare("
            SELECT SUM(om_total.meta_value)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} om_total ON p.ID = om_total.post_id AND om_total.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed')
            AND DATE(p.post_date) BETWEEN %s AND %s", $month_start, date('Y-m-d'))) ?: 0);
    }

    // Total revenue (JGK + WooCommerce)
    $overview_stats['total_revenue'] = $jgk_revenue + $wc_revenue;
    $overview_stats['monthly_revenue'] = $jgk_monthly_revenue + $wc_monthly_revenue;
}

// Coaches (basic query via WP_User_Query if role exists)
if (class_exists('WP_User_Query')) {
    $coach_query = new WP_User_Query(array('role' => 'coach', 'fields' => array('ID')));
    if (!is_wp_error($coach_query)) {
        $overview_stats['total_coaches'] = count($coach_query->get_results());
        $overview_stats['active_coaches'] = $overview_stats['total_coaches'];
    }
}

// ------------------------------------------------------------------
// Membership stats (placeholder / minimal)
// ------------------------------------------------------------------
$membership_stats = array(
    'new_members' => 0,
    'renewals' => 0,
    'cancellations' => 0,
    'net_growth' => 0,
    'by_type' => array()
);

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $members_table)) === $members_table) {
    // Simple type aggregation if column membership_type exists
    $maybe_col = $wpdb->get_results("SHOW COLUMNS FROM $members_table LIKE 'membership_type'");
    if ($maybe_col) {
        $rows = $wpdb->get_results("SELECT membership_type AS t, COUNT(*) active_count FROM $members_table WHERE status='active' GROUP BY membership_type");
        foreach ($rows as $r) {
            $membership_stats['by_type'][$r->t] = array(
                'active' => intval($r->active_count),
                'new' => 0,
                'revenue' => 0,
            );
        }
    }
}

// ------------------------------------------------------------------
// Payment stats from custom payments table AND WooCommerce (combined)
// ------------------------------------------------------------------
$payment_stats = array(
    'total_revenue' => 0,
    'completed_payments' => 0,
    'pending_payments' => 0,
    'failed_payments' => 0,
    'total_payments' => 0,
    'by_method' => array()
);

// JGK Payments stats
$jgk_stats = array(
    'total_revenue' => 0,
    'completed_payments' => 0,
    'pending_payments' => 0,
    'failed_payments' => 0,
    'total_payments' => 0,
    'by_method' => array()
);

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $payments_table)) === $payments_table) {
    $agg = $wpdb->get_row($wpdb->prepare("SELECT
        COUNT(*) total_payments,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) completed_payments,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending_payments,
        SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) failed_payments,
        SUM(CASE WHEN status='completed' THEN amount ELSE 0 END) total_revenue
        FROM $payments_table
        WHERE DATE(payment_date) BETWEEN %s AND %s", $date_from, $date_to));

    if ($agg) {
        $jgk_stats['total_payments']      = intval($agg->total_payments);
        $jgk_stats['completed_payments']  = intval($agg->completed_payments);
        $jgk_stats['pending_payments']    = intval($agg->pending_payments);
        $jgk_stats['failed_payments']     = intval($agg->failed_payments);
        $jgk_stats['total_revenue']       = floatval($agg->total_revenue);
    }

    $methods = $wpdb->get_results($wpdb->prepare("SELECT payment_method,
        COUNT(*) cnt,
        SUM(CASE WHEN status='completed' THEN amount ELSE 0 END) amt,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) completed
        FROM $payments_table
        WHERE DATE(payment_date) BETWEEN %s AND %s
        GROUP BY payment_method", $date_from, $date_to));

    foreach ($methods as $m) {
        $total = intval($m->cnt);
        $completed = intval($m->completed);
        $revenue = floatval($m->amt);
        $method = !empty($m->payment_method) ? $m->payment_method : 'unknown';

        $jgk_stats['by_method'][$method] = array(
            'total' => $total,
            'completed' => $completed,
            'revenue' => $revenue
        );
    }
}

// WooCommerce stats
$wc_stats = array(
    'total_revenue' => 0,
    'completed_payments' => 0,
    'pending_payments' => 0,
    'failed_payments' => 0,
    'total_payments' => 0,
    'by_method' => array()
);

if ($wc_tables_ok) {
    // Count WooCommerce orders by status
    $wc_counts = $wpdb->get_row($wpdb->prepare("SELECT
        COUNT(*) total_orders,
        SUM(CASE WHEN post_status='wc-completed' THEN 1 ELSE 0 END) completed_orders,
        SUM(CASE WHEN post_status IN ('wc-pending', 'wc-on-hold') THEN 1 ELSE 0 END) pending_orders,
        SUM(CASE WHEN post_status IN ('wc-cancelled', 'wc-refunded', 'wc-failed') THEN 1 ELSE 0 END) failed_orders,
        SUM(CASE WHEN post_status='wc-completed' THEN CAST(meta_value AS DECIMAL(10,2)) ELSE 0 END) total_revenue
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} om_total ON p.ID = om_total.post_id AND om_total.meta_key = '_order_total'
        WHERE p.post_type = 'shop_order'
        AND DATE(p.post_date) BETWEEN %s AND %s", $date_from, $date_to));

    if ($wc_counts) {
        $wc_stats['total_payments']      = intval($wc_counts->total_orders);
        $wc_stats['completed_payments']  = intval($wc_counts->completed_orders);
        $wc_stats['pending_payments']    = intval($wc_counts->pending_orders);
        $wc_stats['failed_payments']     = intval($wc_counts->failed_orders);
        $wc_stats['total_revenue']       = floatval($wc_counts->total_revenue);
    }

    // WooCommerce payments by method
    $wc_methods = $wpdb->get_results($wpdb->prepare("SELECT
        COALESCE(pm.meta_value, 'woocommerce') as payment_method,
        COUNT(*) cnt,
        SUM(CASE WHEN p.post_status='wc-completed' THEN CAST(om_total.meta_value AS DECIMAL(10,2)) ELSE 0 END) amt,
        SUM(CASE WHEN p.post_status='wc-completed' THEN 1 ELSE 0 END) completed
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} om_total ON p.ID = om_total.post_id AND om_total.meta_key = '_order_total'
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_payment_method'
        WHERE p.post_type = 'shop_order'
        AND DATE(p.post_date) BETWEEN %s AND %s
        GROUP BY COALESCE(pm.meta_value, 'woocommerce')", $date_from, $date_to));

    foreach ($wc_methods as $m) {
        $total = intval($m->cnt);
        $completed = intval($m->completed);
        $revenue = floatval($m->amt);
        $method = !empty($m->payment_method) ? $m->payment_method : 'woocommerce';

        $wc_stats['by_method'][$method] = array(
            'total' => $total,
            'completed' => $completed,
            'revenue' => $revenue
        );
    }
}

// Combine JGK and WooCommerce stats
$payment_stats['total_payments'] = $jgk_stats['total_payments'] + $wc_stats['total_payments'];
$payment_stats['completed_payments'] = $jgk_stats['completed_payments'] + $wc_stats['completed_payments'];
$payment_stats['pending_payments'] = $jgk_stats['pending_payments'] + $wc_stats['pending_payments'];
$payment_stats['failed_payments'] = $jgk_stats['failed_payments'] + $wc_stats['failed_payments'];
$payment_stats['total_revenue'] = $jgk_stats['total_revenue'] + $wc_stats['total_revenue'];

// Combine payment methods
$payment_stats['by_method'] = array_merge_recursive($jgk_stats['by_method'], $wc_stats['by_method']);

// Sum up duplicate methods
foreach ($payment_stats['by_method'] as $method => $data) {
    if (is_array($data['total'])) {
        $payment_stats['by_method'][$method]['total'] = array_sum($data['total']);
        $payment_stats['by_method'][$method]['completed'] = array_sum($data['completed']);
        $payment_stats['by_method'][$method]['revenue'] = array_sum($data['revenue']);
    }
}

// ------------------------------------------------------------------
// WooCommerce payment method breakdown (additive, no overwrite of totals)
// ------------------------------------------------------------------
if ($membership_product_id && $wc_tables_ok) {
    $woo_methods = $wpdb->get_results($wpdb->prepare("SELECT 
        pm_payment.meta_value AS payment_method,
        COUNT(*) AS total,
        SUM(CASE WHEN p.post_status='wc-completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN p.post_status='wc-completed' THEN pm_total.meta_value ELSE 0 END) AS amount
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
        INNER JOIN {$wpdb->postmeta} pm_total   ON p.ID = pm_total.post_id AND pm_total.meta_key   = '_order_total'
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type='shop_order'
          AND p.post_status IN ('wc-completed','wc-pending','wc-processing','wc-on-hold','wc-failed')
          AND oim.meta_key='_product_id'
          AND oim.meta_value = %d
          AND DATE(p.post_date) BETWEEN %s AND %s
        GROUP BY pm_payment.meta_value", $membership_product_id, $date_from, $date_to));
    
    foreach ($woo_methods as $wm) {
        $total = intval($wm->total);
        $completed = intval($wm->completed);
        $rate = $total ? ($completed / $total * 100) : 0;
        $key = $wm->payment_method ?: 'unknown';
        
        if (!isset($payment_stats['by_method'][$key])) {
            $payment_stats['by_method'][$key] = array(
                'count' => $total,
                'amount' => floatval($wm->amount),
                'success_rate' => $rate,
            );
        } else {
            // Merge (add counts/amount, recompute success rate naive)
            $existing = $payment_stats['by_method'][$key];
            $newCount = $existing['count'] + $total;
            $newAmount = $existing['amount'] + floatval($wm->amount);
            // Weighted success rate (by transactions)
            $combinedCompleted = ($existing['success_rate'] / 100 * $existing['count']) + $completed;
            $newRate = $newCount ? ($combinedCompleted / $newCount * 100) : 0;
            $payment_stats['by_method'][$key] = array(
                'count' => $newCount,
                'amount' => $newAmount,
                'success_rate' => $newRate,
            );
        }
    }
}

// ------------------------------------------------------------------
// Monthly data placeholder for charts (last 12 months)
// ------------------------------------------------------------------
$monthly_data = array();
for ($i = 11; $i >= 0; $i--) {
    $label = date('Y-m', strtotime("-{$i} months"));
    $monthly_data[$label] = array(
        'members' => 0,
        'new_members' => 0,
        'revenue' => 0,
    );
}

// Populate monthly revenue data (JGK + WooCommerce)
foreach ($monthly_data as $month => &$data) {
    $month_start = $month . '-01';
    $month_end = date('Y-m-t', strtotime($month_start)); // Last day of month

    // JGK payments revenue for this month
    $jgk_monthly = 0;
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $payments_table)) === $payments_table) {
        $jgk_monthly = floatval($wpdb->get_var($wpdb->prepare("
            SELECT SUM(amount) FROM $payments_table
            WHERE status='completed'
            AND DATE(payment_date) BETWEEN %s AND %s", $month_start, $month_end)) ?: 0);
    }

    // WooCommerce revenue for this month
    $wc_monthly = 0;
    if ($wc_tables_ok) {
        $wc_monthly = floatval($wpdb->get_var($wpdb->prepare("
            SELECT SUM(CAST(om_total.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} om_total ON p.ID = om_total.post_id AND om_total.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order'
            AND p.post_status = 'wc-completed'
            AND DATE(p.post_date) BETWEEN %s AND %s", $month_start, $month_end)) ?: 0);
    }

    $data['revenue'] = $jgk_monthly + $wc_monthly;
}

// Coaches list (simple) for filters when needed
$coaches = array();
if (class_exists('WP_User_Query')) {
    $cq = new WP_User_Query(array('role' => 'coach'));
    if (!is_wp_error($cq)) { 
        $coaches = $cq->get_results(); 
    }
}

// Coach performance (placeholder) if specific coach requested
if ($report_type === 'coaches' && $coach_id) {
    $coach_performance = array(
        'name' => get_user_by('id', $coach_id)->display_name ?? 'Coach',
        'assigned_members' => 0,
        'avg_rating' => 0,
        'training_sessions' => 0,
        'improvement_rate' => 0,
        'recent_feedback' => array(),
        'member_count' => 0,
        'status' => 'active',
    );
}
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">Reports &amp; Analytics</h1>
    <hr class="wp-header-end">

    <!-- Filters -->
    <div class="jgk-report-filters">
        <form method="get" style="display:flex;gap:15px;align-items:center;margin:20px 0;flex-wrap:wrap;">
            <input type="hidden" name="page" value="juniorgolfkenya-reports" />
            <label>Report Type:</label>
            <select name="report_type" onchange="this.form.submit()">
                <option value="overview" <?php selected($report_type,'overview');?>>Overview</option>
                <option value="membership" <?php selected($report_type,'membership');?>>Membership</option>
                <option value="payments" <?php selected($report_type,'payments');?>>Payments</option>
                <option value="coaches" <?php selected($report_type,'coaches');?>>Coaches</option>
            </select>
            <label>From:</label>
            <input type="date" name="date_from" value="<?php echo esc_attr($date_from);?>" />
            <label>To:</label>
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to);?>" />
            <?php if ($report_type==='coaches'): ?>
            <label>Coach:</label>
            <select name="coach_id">
                <option value="">All Coaches</option>
                <?php foreach ($coaches as $c): ?>
                    <option value="<?php echo $c->ID; ?>" <?php selected($coach_id,$c->ID);?>><?php echo esc_html($c->display_name);?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <input type="submit" class="button button-primary" value="Generate" />
        </form>
    </div>

    <?php if ($report_type==='overview'): ?>
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
                        <td><?php echo esc_html(ucfirst($type)); ?></td>
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
                        <td><?php echo esc_html(ucfirst($method)); ?></td>
                        <td><?php echo $stats['count']; ?></td>
                        <td>KSh <?php echo number_format($stats['amount'], 2); ?></td>
                        <td><?php echo number_format($stats['success_rate'], 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="jgk-payment-history">
            <h3>Recent Payments</h3>
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
                    // Get recent payments from JGK payments table
                    $recent_payments = array();
                    
                    if ($wpdb->get_var("SHOW TABLES LIKE '$payments_table'") == $payments_table) {
                        $jgk_payments = $wpdb->get_results($wpdb->prepare("
                            SELECT 
                                p.payment_date as date_created,
                                CONCAT(m.first_name, ' ', m.last_name) as member_name,
                                p.order_id,
                                p.amount,
                                p.payment_method,
                                p.status
                            FROM {$payments_table} p
                            LEFT JOIN {$members_table} m ON p.member_id = m.id
                            WHERE DATE(p.payment_date) BETWEEN %s AND %s
                            ORDER BY p.payment_date DESC
                            LIMIT 50
                        ", $date_from, $date_to));
                        
                        foreach ($jgk_payments as $payment) {
                            $recent_payments[] = array(
                                'date' => $payment->date_created,
                                'member' => $payment->member_name ?: 'Unknown',
                                'order_id' => $payment->order_id ?: 'N/A',
                                'amount' => $payment->amount,
                                'payment_method' => $payment->payment_method,
                                'status' => $payment->status,
                            );
                        }
                    }

                    // WooCommerce payments
                    if ($wc_tables_ok) {
                        $wc_payments = $wpdb->get_results($wpdb->prepare("
                            SELECT
                                p.post_date as date_created,
                                CASE
                                    WHEN m.user_id IS NOT NULL THEN CONCAT(u.display_name, ' (', m.membership_number, ')')
                                    WHEN pm_customer.meta_value != 0 THEN CONCAT('Customer #', pm_customer.meta_value)
                                    ELSE 'Guest Customer'
                                END as member_name,
                                p.ID as order_id,
                                CAST(pm_total.meta_value AS DECIMAL(10,2)) as amount,
                                COALESCE(pm_payment.meta_value, 'woocommerce') as payment_method,
                                CASE
                                    WHEN p.post_status = 'wc-completed' THEN 'completed'
                                    WHEN p.post_status = 'wc-processing' THEN 'processing'
                                    WHEN p.post_status = 'wc-pending' THEN 'pending'
                                    WHEN p.post_status = 'wc-on-hold' THEN 'on_hold'
                                    WHEN p.post_status = 'wc-cancelled' THEN 'cancelled'
                                    WHEN p.post_status = 'wc-refunded' THEN 'refunded'
                                    WHEN p.post_status = 'wc-failed' THEN 'failed'
                                    ELSE 'unknown'
                                END as status
                            FROM {$wpdb->posts} p
                            INNER JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
                            INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
                            LEFT JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = '_payment_method'
                            LEFT JOIN $members_table m ON pm_customer.meta_value = m.user_id
                            LEFT JOIN $users_table u ON m.user_id = u.ID
                            WHERE p.post_type = 'shop_order'
                            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold', 'wc-cancelled', 'wc-refunded', 'wc-failed')
                            AND DATE(p.post_date) BETWEEN %s AND %s
                            ORDER BY p.post_date DESC
                            LIMIT 25
                        ", $date_from, $date_to));

                        foreach ($wc_payments as $payment) {
                            $recent_payments[] = array(
                                'date' => $payment->date_created,
                                'member' => $payment->member_name ?: 'Unknown',
                                'order_id' => $payment->order_id ?: 'N/A',
                                'amount' => $payment->amount,
                                'payment_method' => $payment->payment_method,
                                'status' => $payment->status,
                            );
                        }
                    }

                    // Sort combined payments by date (most recent first)
                    usort($recent_payments, function($a, $b) {
                        return strtotime($b['date']) - strtotime($a['date']);
                    });

                    // Limit to 50 most recent
                    $recent_payments = array_slice($recent_payments, 0, 50);
                    
                    // Display payments
                    if (empty($recent_payments)): ?>
                    <tr>
                        <td colspan="6">No payments found for the selected period.</td>
                    </tr>
                    <?php else:
                        foreach ($recent_payments as $payment): ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($payment['date'])); ?></td>
                        <td><?php echo esc_html($payment['member']); ?></td>
                        <td><?php echo $payment['order_id']; ?> <?php if (isset($payment['source']) && $payment['source'] === 'woocommerce') echo '<small style="color:#666;">(WC)</small>'; ?></td>
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
            
            <?php if (!empty($coach_performance['recent_feedback'])): ?>
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
            <?php endif; ?>
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
                        <td><?php echo $coach_performance['member_count'] ?? 0; ?></td>
                        <td>
                            <?php if ($coach_performance['avg_rating'] ?? 0): ?>
                            <?php echo number_format($coach_performance['avg_rating'], 1); ?>/5
                            <?php else: ?>
                            No ratings
                            <?php endif; ?>
                        </td>
                        <td><?php echo $coach_performance['training_sessions'] ?? 0; ?></td>
                        <td>
                            <span class="jgk-status-active">Active</span>
                        </td>
                        <td>
                            <a href="?page=juniorgolfkenya-reports&report_type=coaches&coach_id=<?php echo $coach->ID; ?>&date_from=<?php echo esc_attr($date_from); ?>&date_to=<?php echo esc_attr($date_to); ?>" class="button button-small">
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

.jgk-status-completed,
.jgk-status-active {
    color: #46b450;
    font-weight: bold;
}

.jgk-status-pending {
    color: #f39c12;
    font-weight: bold;
}

.jgk-status-failed {
    color: #e74c3c;
    font-weight: bold;
}
</style>

<script>
// Initialize charts if overview report
<?php if ($report_type === 'overview'): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Membership growth chart
    const membershipCtx = document.getElementById('membershipChart');
    if (membershipCtx && typeof Chart !== 'undefined') {
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
    if (revenueCtx && typeof Chart !== 'undefined') {
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
    if (statusCtx && typeof Chart !== 'undefined') {
        const statusData = {
            labels: ['Active', 'Pending', 'Inactive'],
            datasets: [{
                data: [
                    <?php echo $overview_stats['active_members']; ?>,
                    <?php echo $overview_stats['total_members'] - $overview_stats['active_members']; ?>,
                    0 // Placeholder for inactive
                ],
                backgroundColor: [
                    '#46b450',
                    '#f39c12',
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
    if (paymentMethodsCtx && typeof Chart !== 'undefined') {
        const methodLabels = <?php echo json_encode(array_keys($payment_stats['by_method'])); ?>;
        const methodData = <?php echo json_encode(array_column($payment_stats['by_method'], 'count')); ?>;
        
        new Chart(paymentMethodsCtx, {
            type: 'pie',
            data: {
                labels: methodLabels,
                datasets: [{
                    data: methodData,
                    backgroundColor: [
                        '#2271b1',
                        '#46b450',
                        '#ffb900',
                        '#e74c3c',
                        '#9b59b6',
                        '#34495e'
                    ]
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
                        text: 'Payment Method Distribution'
                    }
                }
            }
        });
    }
});
<?php endif; ?>
</script>