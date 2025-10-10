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
            
            $result = JuniorGolfKenya_Database::update_payment($payment_id, array(
                'status' => $status,
                'notes' => $notes
            ));
            
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
            
            $result = JuniorGolfKenya_Database::record_payment($member_id, $amount, $payment_type, $payment_method, $notes);
            
            if ($result) {
                $message = 'Payment recorded successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to record payment.';
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
                    <td><strong>#<?php echo $payment->id; ?></strong></td>
                    <td><?php echo esc_html($payment->member_name); ?></td>
                    <td><strong>KSh <?php echo number_format($payment->amount, 2); ?></strong></td>
                    <td><?php echo ucfirst($payment->payment_type); ?></td>
                    <td><?php echo ucfirst($payment->payment_method); ?></td>
                    <td>
                        <span class="jgk-status-<?php echo esc_attr($payment->status); ?>">
                            <?php echo ucfirst($payment->status); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($payment->created_at)); ?></td>
                    <td>
                        <?php if ($payment->notes): ?>
                        <span title="<?php echo esc_attr($payment->notes); ?>">
                            <?php echo esc_html(strlen($payment->notes) > 30 ? substr($payment->notes, 0, 30) . '...' : $payment->notes); ?>
                        </span>
                        <?php else: ?>
                        <em>No notes</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button button-small" onclick="openUpdateModal(<?php echo $payment->id; ?>, '<?php echo esc_js($payment->status); ?>', '<?php echo esc_js($payment->notes); ?>')">
                            Update
                        </button>
                        <?php if ($payment->status === 'completed' && $payment->payment_method === 'online'): ?>
                        <button class="button button-small" onclick="generateReceipt(<?php echo $payment->id; ?>)">
                            Receipt
                        </button>
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
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required>
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

function openUpdateModal(paymentId, status, notes) {
    document.getElementById('update-payment-id').value = paymentId;
    document.getElementById('update_status').value = status;
    document.getElementById('update_notes').value = notes || '';
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
</script>