<?php
/**
 * Provide a admin area view for payments management
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
if (!current_user_can('manage_payments')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Load required classes
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_payment_action')) {
        wp_die(__('Security check failed.'));
    }

    switch ($_POST['action']) {
        case 'update_payment':
            $payment_id = intval($_POST['payment_id']);
            $status = sanitize_text_field($_POST['status']);
            $notes = sanitize_textarea_field($_POST['notes']);
            $amount_raw = isset($_POST['amount']) ? trim((string)$_POST['amount']) : null;
            $payment_type = isset($_POST['payment_type']) ? sanitize_text_field($_POST['payment_type']) : null;

            $update_data = array(
                'status' => $status,
                'notes' => $notes,
            );
            // Only update amount if the field is provided AND non-empty AND numeric
            if ($amount_raw !== null && $amount_raw !== '' && is_numeric($amount_raw)) {
                $update_data['amount'] = (float)$amount_raw;
            }
            if (!is_null($payment_type) && $payment_type !== '') { $update_data['payment_type'] = $payment_type; }

            $result = JuniorGolfKenya_Database::update_payment($payment_id, $update_data);
            
            if ($result) {
                $message = 'Payment updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update payment.';
                $message_type = 'error';
            }
            break;

        case 'record_payment':
            $member_id = intval($_POST['member_id']);
            $amount = floatval($_POST['amount']);
            $payment_type = sanitize_text_field($_POST['payment_type']);
            $payment_method = sanitize_text_field($_POST['payment_method']);
            $notes = sanitize_textarea_field($_POST['notes']);
            
            // Server-side validation: amount must be > 1
            if (!is_numeric($amount) || $amount <= 1) {
                $message = 'Amount must be greater than 1.';
                $message_type = 'error';
                break;
            }

            $result = JuniorGolfKenya_Database::record_manual_payment($member_id, $amount, $payment_type, $payment_method, $notes);
            
            if ($result) {
                $message = 'Payment recorded successfully!';
                $message_type = 'success';
            } else {
                global $wpdb;
                $err = method_exists($wpdb, 'last_error') ? $wpdb->last_error : '';
                if (!empty($err)) {
                    error_log('JGK PAYMENT INSERT ERROR: ' . $err);
                    $message = 'Failed to record payment. DB error: ' . esc_html($err);
                } else {
                    $message = 'Failed to record payment.';
                }
                $message_type = 'error';
            }
            break;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// Get payments
$payments = JuniorGolfKenya_Database::get_payments($status_filter, $type_filter, $date_from, $date_to);
$members = JuniorGolfKenya_Database::get_members();

// Calculate totals
$total_amount = array_sum(array_map(function($p) { return $p->amount; }, $payments));
$pending_amount = array_sum(array_map(function($p) { return $p->status === 'pending' ? $p->amount : 0; }, $payments));
$completed_amount = array_sum(array_map(function($p) { return $p->status === 'completed' ? $p->amount : 0; }, $payments));

global $wpdb;
$prefix = $wpdb->prefix;

// Detect membership product id from either legacy option or new settings array
$legacy_membership_id = intval(get_option('jgk_membership_product_id', 0));
$payment_settings = get_option('jgk_payment_settings', array());
$settings_membership_id = intval($payment_settings['membership_product_id'] ?? 0);
$membership_product_id = $legacy_membership_id > 0 ? $legacy_membership_id : $settings_membership_id;

// Debug scan of payment sources/tables
$tables = array(
    'jgk_payments' => $prefix . 'jgk_payments',
    'wp_posts' => $prefix . 'posts',
    'wp_postmeta' => $prefix . 'postmeta',
    'wc_order_items' => $prefix . 'woocommerce_order_items',
    'wc_order_itemmeta' => $prefix . 'woocommerce_order_itemmeta',
);

$dbg = array();

// Check table existence
foreach ($tables as $key => $table_name) {
    $dbg['tables'][$key]['exists'] = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
}

// JGK payments table stats
if ($dbg['tables']['jgk_payments']['exists']) {
    $jgk_stats = $wpdb->get_row("SELECT COUNT(*) as count, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed FROM {$tables['jgk_payments']}");
    $dbg['jgk']['count'] = intval($jgk_stats->count ?? 0);
    $dbg['jgk']['completed'] = intval($jgk_stats->completed ?? 0);
    
    // By gateway
    $gateway_stats = $wpdb->get_results("SELECT payment_method as gateway, COUNT(*) as cnt FROM {$tables['jgk_payments']} GROUP BY payment_method ORDER BY cnt DESC");
    $dbg['jgk']['by_gateway'] = array_map(function($row) {
        return array('gateway' => $row->gateway, 'cnt' => intval($row->cnt));
    }, $gateway_stats);
}

// WooCommerce integration check
$dbg['woocommerce']['tables_ok'] = $dbg['tables']['wp_posts']['exists'] && $dbg['tables']['wp_postmeta']['exists'] && $dbg['tables']['wc_order_items']['exists'] && $dbg['tables']['wc_order_itemmeta']['exists'];

if ($dbg['woocommerce']['tables_ok']) {
    // Membership product source detection
    $dbg['woocommerce']['membership_product_source'] = 'legacy_option';
    if ($legacy_membership_id > 0) {
        $dbg['woocommerce']['membership_product_source'] = 'legacy_option';
    } elseif ($settings_membership_id > 0) {
        $dbg['woocommerce']['membership_product_source'] = 'settings_array';
    }
    
    // Count membership orders
    $membership_orders_query = $wpdb->prepare("
        SELECT COUNT(DISTINCT p.ID) as membership_orders
        FROM {$tables['wp_posts']} p
        INNER JOIN {$tables['wc_order_items']} oi ON p.ID = oi.order_id
        INNER JOIN {$tables['wc_order_itemmeta']} oim ON oi.order_item_id = oim.order_item_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending')
        AND oim.meta_key = '_product_id'
        AND oim.meta_value = %d
    ", $membership_product_id);
    
    $membership_result = $wpdb->get_row($membership_orders_query);
    $dbg['woocommerce']['membership_orders'] = intval($membership_result->membership_orders ?? 0);
    
    // Stripe orders
    $stripe_orders = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$tables['wp_postmeta']} 
        WHERE meta_key = '_payment_method' 
        AND meta_value LIKE %s
    ", 'stripe%'));
    $dbg['woocommerce']['stripe_orders'] = intval($stripe_orders ?? 0);
    
    // Stripe keys check
    $dbg['woocommerce']['stripe_keys']['public'] = !empty(get_option('woocommerce_stripe_settings')['publishable_key'] ?? '');
    $dbg['woocommerce']['stripe_keys']['secret'] = !empty(get_option('woocommerce_stripe_settings')['secret_key'] ?? '');
    
    // iPay/M-Pesa orders
    $ipay_orders = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$tables['wp_postmeta']} 
        WHERE meta_key = '_payment_method' 
        AND meta_value IN ('ipay', 'elipa', 'mpesa', 'airtel', 'card')
    "));
    $dbg['woocommerce']['ipay_orders'] = intval($ipay_orders ?? 0);
    
    // iPay status transients
    $ipay_transients = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['wp_options']} WHERE option_name LIKE 'jgk_ipay_status_%'");
    $dbg['woocommerce']['ipay_status_transients'] = intval($ipay_transients ?? 0);
}
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">Payments Management</h1>
    <button class="page-title-action" onclick="openRecordModal()">Record Payment</button>
    <hr class="wp-header-end">

    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

        <?php
        // Diagnostic: Woo orders detected but none loaded
        $dbg_wc_orders = isset($dbg['woocommerce']['membership_orders']) ? intval($dbg['woocommerce']['membership_orders']) : 0;
        $has_wc_row = false;
        foreach ($payments as $p) { if (isset($p->source) && $p->source === 'woocommerce') { $has_wc_row = true; break; } }
        if ($dbg_wc_orders > 0 && !$has_wc_row) : ?>
            <div class="notice notice-warning" style="border-left-color:#dba617;">
                <p><strong>Notice:</strong> <?php echo esc_html($dbg_wc_orders); ?> WooCommerce membership orders ont été détectées (produit ID <?php echo intval($membership_product_id); ?>) mais aucune ligne WooCommerce n'est affichée dans la liste. Causes possibles:
                <ul style="margin-top:4px;list-style:disc;padding-left:18px;">
                    <li>Filtres actifs (status/type/dates) excluant toutes les commandes.</li>
                    <li>Product ID configuré différent de celui réellement utilisé dans certaines commandes (variations, produits remplacés).</li>
                    <li>Extension de cache/objet retardant la récupération (essayez de vider le cache).</li>
                    <li>Un statut de commande non inclus dans la requête (ex: custom status) – adapter la liste des statuts si nécessaire.</li>
                </ul>
                </p>
            </div>
        <?php endif; ?>

    <!-- Payment Statistics -->
    <div class="jgk-payment-stats">
        <div class="jgk-stat-card">
            <h3>KSh <?php echo number_format($total_amount, 2); ?></h3>
            <p>Total Payments</p>
        </div>
        <div class="jgk-stat-card">
            <h3>KSh <?php echo number_format($completed_amount, 2); ?></h3>
            <p>Completed</p>
        </div>
        <div class="jgk-stat-card">
            <h3>KSh <?php echo number_format($pending_amount, 2); ?></h3>
            <p>Pending</p>
        </div>
        <div class="jgk-stat-card">
            <h3><?php echo count($payments); ?></h3>
            <p>Total Transactions</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="page" value="juniorgolfkenya-payments">
                
                <select name="status">
                    <option value="all" <?php selected($status_filter, 'all'); ?>>All Statuses</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                    <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                    <option value="failed" <?php selected($status_filter, 'failed'); ?>>Failed</option>
                    <option value="refunded" <?php selected($status_filter, 'refunded'); ?>>Refunded</option>
                </select>
                
                <select name="type">
                    <option value="all" <?php selected($type_filter, 'all'); ?>>All Types</option>
                    <option value="membership" <?php selected($type_filter, 'membership'); ?>>Membership</option>
                    <option value="tournament" <?php selected($type_filter, 'tournament'); ?>>Tournament</option>
                    <option value="training" <?php selected($type_filter, 'training'); ?>>Training</option>
                    <option value="certification" <?php selected($type_filter, 'certification'); ?>>Certification</option>
                </select>
                
                <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="From Date">
                <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="To Date">
                
                <input type="submit" class="button" value="Filter">
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-payments'); ?>" class="button">Clear</a>
            </form>
        </div>
    </div>

    <!-- Debug: Payment Sources Scan -->
    <div class="jgk-debug-panel" style="margin: 15px 0;">
        <details>
            <summary style="cursor:pointer;font-weight:600;">Debug: Payment Sources Scan</summary>
            <div style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:15px;margin-top:10px;">
                <h3 style="margin-top:0;">Sources vérifiées</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Source/Table</th>
                            <th>Statut</th>
                            <th>Détails</th>
                            <th>Compteur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code><?php echo esc_html($tables['jgk_payments']); ?></code></td>
                            <td><?php echo !empty($dbg['tables']['jgk_payments']['exists']) ? '✔️ Found' : '❌ Missing'; ?></td>
                            <td>JGK payments table</td>
                            <td>
                                <?php if (!empty($dbg['jgk'])): ?>
                                    Total: <?php echo intval($dbg['jgk']['count']); ?>, Completed: <?php echo intval($dbg['jgk']['completed']); ?>
                                    <?php if (!empty($dbg['jgk']['by_gateway'])): ?>
                                        <br>By gateway:
                                        <?php foreach ($dbg['jgk']['by_gateway'] as $row): ?>
                                            <span style="display:inline-block;margin-right:8px;">
                                                <?php echo esc_html($row['gateway'] !== '' ? $row['gateway'] : 'unknown'); ?>: <?php echo intval($row['cnt']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>WooCommerce (product ID)</td>
                            <td>
                                <?php
                                if (!empty($dbg['woocommerce'])) {
                                    echo $dbg['woocommerce']['tables_ok'] ? '✔️ Tables OK' : '❌ WC tables missing';
                                } else {
                                    echo 'ℹ️ Not checked';
                                }
                                ?>
                            </td>
                            <td>
                                Product ID: <?php echo intval($membership_product_id); ?>
                                <br>Source: <?php echo esc_html($dbg['woocommerce']['membership_product_source'] ?? 'unknown'); ?>
                            </td>
                            <td>
                                <?php if (!empty($dbg['woocommerce'])): ?>
                                    Membership orders: <?php echo is_null($dbg['woocommerce']['membership_orders']) ? 'N/A' : intval($dbg['woocommerce']['membership_orders']); ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>WooCommerce (Stripe)</td>
                            <td><?php echo !empty($dbg['woocommerce']) && $dbg['woocommerce']['tables_ok'] ? '✔️ Checked' : 'ℹ️ Not checked'; ?></td>
                            <td>Orders with _payment_method LIKE 'stripe%'<br>Keys set: Pub=<?php echo !empty($dbg['woocommerce']) && $dbg['woocommerce']['stripe_keys']['public'] ? 'yes' : 'no'; ?>, Sec=<?php echo !empty($dbg['woocommerce']) && $dbg['woocommerce']['stripe_keys']['secret'] ? 'yes' : 'no'; ?></td>
                            <td><?php echo !empty($dbg['woocommerce']) ? intval($dbg['woocommerce']['stripe_orders']) : 0; ?></td>
                        </tr>
                        <tr>
                            <td>WooCommerce (iPay/eLipa/M-Pesa)</td>
                            <td><?php echo !empty($dbg['woocommerce']) && $dbg['woocommerce']['tables_ok'] ? '✔️ Checked' : 'ℹ️ Not checked'; ?></td>
                            <td>Orders with _payment_method IN (ipay, elipa, mpesa, airtel, card)<br>Transients: <?php echo !empty($dbg['woocommerce']) ? intval($dbg['woocommerce']['ipay_status_transients']) : 0; ?></td>
                            <td><?php echo !empty($dbg['woocommerce']) ? intval($dbg['woocommerce']['ipay_orders']) : 0; ?></td>
                        </tr>
                        <tr>
                            <td><code><?php echo esc_html($tables['wp_posts']); ?></code></td>
                            <td><?php echo !empty($dbg['tables']['wp_posts']['exists']) ? '✔️ Found' : '❌ Missing'; ?></td>
                            <td>WP posts (orders)</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code><?php echo esc_html($tables['wp_postmeta']); ?></code></td>
                            <td><?php echo !empty($dbg['tables']['wp_postmeta']['exists']) ? '✔️ Found' : '❌ Missing'; ?></td>
                            <td>WP postmeta (_payment_method, totals, etc.)</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code><?php echo esc_html($tables['wc_order_items']); ?></code></td>
                            <td><?php echo !empty($dbg['tables']['wc_order_items']['exists']) ? '✔️ Found' : '❌ Missing'; ?></td>
                            <td>WooCommerce order items</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code><?php echo esc_html($tables['wc_order_itemmeta']); ?></code></td>
                            <td><?php echo !empty($dbg['tables']['wc_order_itemmeta']['exists']) ? '✔️ Found' : '❌ Missing'; ?></td>
                            <td>WooCommerce order item meta (_product_id)</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </div>

    <!-- Payments Table -->
    <div class="jgk-table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Member</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="9">No payments found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td>
                        <strong>
                            <?php if (isset($payment->source) && $payment->source === 'woocommerce'): ?>
                                #WC-<?php echo $payment->id; ?>
                            <?php else: ?>
                                #<?php echo $payment->id; ?>
                            <?php endif; ?>
                        </strong>
                        <?php if (isset($payment->source) && $payment->source === 'woocommerce'): ?>
                            <br><small style="color: #666;">WooCommerce</small>
                        <?php else: ?>
                            <br><small style="color: #666;">Manual</small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($payment->member_name ?: 'Unknown Member'); ?></td>
                    <td><strong>KSh <?php echo number_format($payment->amount, 2); ?></strong></td>
                    <td><?php echo ucfirst($payment->payment_type); ?></td>
                    <td><?php echo ucfirst($payment->payment_method); ?></td>
                    <td>
                        <span class="jgk-status-<?php echo esc_attr($payment->status); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $payment->status)); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $date_field = isset($payment->source) && $payment->source === 'woocommerce' ? 'post_date' : 'created_at';
                        $date_value = isset($payment->$date_field) ? $payment->$date_field : $payment->created_at;
                        echo date('M j, Y', strtotime($date_value));
                        ?>
                    </td>
                    <td>
                        <?php if (!empty($payment->notes) || !empty($payment->transaction_id)): ?>
                            <div style="font-size: 11px;">
                                <?php if (!empty($payment->transaction_id)): ?>
                                    <strong>TXN:</strong> <?php echo esc_html($payment->transaction_id); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($payment->notes)): ?>
                                    <span title="<?php echo esc_attr($payment->notes); ?>">
                                        <?php echo esc_html(strlen($payment->notes) > 30 ? substr($payment->notes, 0, 30) . '...' : $payment->notes); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <em>No details</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($payment->source) && $payment->source === 'woocommerce'): ?>
                            <a href="<?php echo admin_url('post.php?post=' . $payment->id . '&action=edit'); ?>" class="button button-small" target="_blank">
                                View Order
                            </a>
                        <?php else: ?>
                            <button class="button button-small" onclick="openUpdateModal(<?php echo $payment->id; ?>, '<?php echo esc_js($payment->status); ?>', '<?php echo esc_js($payment->notes); ?>', '<?php echo esc_js($payment->amount); ?>', '<?php echo esc_js($payment->payment_type); ?>', <?php echo (isset($payment->source) && $payment->source === 'woocommerce') ? 'true' : 'false'; ?>)">
                                Update
                            </button>
                            <?php if ($payment->status === 'completed' && $payment->payment_method === 'online'): ?>
                            <button class="button button-small" onclick="generateReceipt(<?php echo $payment->id; ?>)">
                                Receipt
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Record Payment Modal -->
<div id="record-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Record New Payment</h2>
            <span class="jgk-modal-close" onclick="closeRecordModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="record-form">
                <?php wp_nonce_field('jgk_payment_action'); ?>
                <input type="hidden" name="action" value="record_payment">
                
                <div class="jgk-form-field">
                    <label for="member_id">Member:</label>
                    <select id="member_id" name="member_id" required>
                        <option value="">Select Member</option>
                        <?php foreach ($members as $member): ?>
                        <option value="<?php echo $member->id; ?>">
                            <?php echo esc_html($member->full_name . ' (' . $member->membership_number . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="jgk-form-field">
                    <label for="amount">Amount (KSh):</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="1.01" required>
                </div>
                
                <div class="jgk-form-field">
                    <label for="payment_type">Payment Type:</label>
                    <select id="payment_type" name="payment_type" required>
                        <option value="membership">Membership Fee</option>
                        <option value="tournament">Tournament Fee</option>
                        <option value="training">Training Fee</option>
                        <option value="certification">Certification Fee</option>
                    </select>
                </div>
                
                <div class="jgk-form-field">
                    <label for="payment_method">Payment Method:</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="online">Online Payment</option>
                    </select>
                </div>
                
                <div class="jgk-form-field">
                    <label for="record_notes">Notes:</label>
                    <textarea id="record_notes" name="notes" rows="3" placeholder="Payment notes or reference..."></textarea>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Record Payment">
                    <button type="button" class="button" onclick="closeRecordModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Payment Modal -->
<div id="update-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Update Payment</h2>
            <span class="jgk-modal-close" onclick="closeUpdateModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="update-form">
                <?php wp_nonce_field('jgk_payment_action'); ?>
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="payment_id" id="update-payment-id">
                <input type="hidden" id="update-original-amount" value="">
                
                <div class="jgk-form-field">
                    <label for="update_amount">Amount (KSh):</label>
                    <input type="number" id="update_amount" name="amount" step="0.01" min="1.01" placeholder="Leave blank to keep current amount">
                    <small id="update_amount_help" style="color:#b32d2e;display:block;margin-top:6px;">Warning: Changing the amount will affect reports and totals.</small>
                </div>

                <div class="jgk-form-field">
                    <label for="update_payment_type">Payment Type:</label>
                    <select id="update_payment_type" name="payment_type">
                        <option value="membership">Membership Fee</option>
                        <option value="tournament">Tournament Fee</option>
                        <option value="training">Training Fee</option>
                        <option value="certification">Certification Fee</option>
                    </select>
                </div>

                <div class="jgk-form-field">
                    <label for="update_status">Status:</label>
                    <select id="update_status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                
                <div class="jgk-form-field">
                    <label for="update_notes">Notes:</label>
                    <textarea id="update_notes" name="notes" rows="3" placeholder="Update notes..."></textarea>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Update Payment">
                    <button type="button" class="button" onclick="closeUpdateModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.jgk-payment-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.jgk-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    flex: 1;
}

.jgk-stat-card h3 {
    font-size: 1.5em;
    margin: 0 0 10px 0;
    color: #2271b1;
}

.jgk-stat-card p {
    margin: 0;
    color: #666;
}

.jgk-status-pending { color: #b32d2e; }
.jgk-status-completed { color: #00a32a; }
.jgk-status-failed { color: #d63638; }
.jgk-status-refunded { color: #dba617; }
</style>

<script>
function openRecordModal() {
    document.getElementById('record-modal').style.display = 'block';
}

function closeRecordModal() {
    document.getElementById('record-modal').style.display = 'none';
    document.getElementById('record-form').reset();
}

function openUpdateModal(paymentId, status, notes, amount, paymentType, isWooCommerce) {
    document.getElementById('update-payment-id').value = paymentId;
    document.getElementById('update_status').value = status;
    document.getElementById('update_notes').value = notes || '';
    document.getElementById('update_amount').value = amount || '';
    document.getElementById('update-original-amount').value = amount || '';
    if (paymentType) {
        document.getElementById('update_payment_type').value = paymentType;
    }
    // Disable amount/type for WooCommerce-origin payments
    var amountInput = document.getElementById('update_amount');
    var typeSelect = document.getElementById('update_payment_type');
    var help = document.getElementById('update_amount_help');
    if (isWooCommerce) {
        amountInput.disabled = true;
        amountInput.placeholder = 'Managed by WooCommerce';
        typeSelect.disabled = true;
        if (help) { help.style.display = 'none'; }
    } else {
        amountInput.disabled = false;
        typeSelect.disabled = false;
        if (help) { help.style.display = 'block'; }
    }
    document.getElementById('update-modal').style.display = 'block';
}

function closeUpdateModal() {
    document.getElementById('update-modal').style.display = 'none';
    document.getElementById('update-form').reset();
}

function generateReceipt(paymentId) {
    // Open receipt in new window
    const url = '<?php echo admin_url('admin-ajax.php'); ?>?action=jgk_generate_receipt&payment_id=' + paymentId;
    window.open(url, '_blank');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const recordModal = document.getElementById('record-modal');
    const updateModal = document.getElementById('update-modal');
    
    if (event.target === recordModal) {
        closeRecordModal();
    }
    if (event.target === updateModal) {
        closeUpdateModal();
    }
}

// Validate record form amount > 1
document.getElementById('record-form').addEventListener('submit', function(e) {
    var amtInput = document.getElementById('amount');
    var amt = parseFloat(amtInput.value);
    if (isNaN(amt) || amt <= 1) {
        e.preventDefault();
        alert('Amount must be greater than 1.');
        amtInput.focus();
    }
});

// Validate and confirm update form
document.getElementById('update-form').addEventListener('submit', function(e) {
    var originalStr = document.getElementById('update-original-amount').value || '';
    var currentStr = document.getElementById('update_amount').value || '';
    if (currentStr !== '') {
        var current = parseFloat(currentStr);
        if (isNaN(current) || current <= 1) {
            e.preventDefault();
            alert('Amount must be greater than 1.');
            document.getElementById('update_amount').focus();
            return;
        }
        var original = parseFloat(originalStr || '');
        if (!isNaN(original) && current !== original) {
            var ok = confirm('Changing the amount will update financial totals and reports. Do you want to proceed?');
            if (!ok) {
                e.preventDefault();
                return;
            }
        }
    }
});
</script>