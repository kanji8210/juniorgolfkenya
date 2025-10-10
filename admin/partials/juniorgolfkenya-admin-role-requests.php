<?php
/**
 * Provide a admin area view for role requests management
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
if (!current_user_can('approve_role_requests')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Load required classes
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-user-manager.php';

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_POST['action']) && $_POST['action'] === 'process_role_request') {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_role_request_action')) {
        wp_die(__('Security check failed.'));
    }

    $request_id = intval($_POST['request_id']);
    $approve = $_POST['decision'] === 'approve';
    $reason = sanitize_textarea_field($_POST['reason']);

    $result = JuniorGolfKenya_User_Manager::process_role_request($request_id, $approve, $reason);
    
    if ($result) {
        $message = $approve ? 'Role request approved successfully!' : 'Role request denied.';
        $message_type = 'success';
    } else {
        $message = 'Failed to process role request.';
        $message_type = 'error';
    }
}

// Get role requests
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
$role_requests = JuniorGolfKenya_Database::get_role_requests($status_filter);
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">Role Requests</h1>
    <hr class="wp-header-end">

    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <!-- Status Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="juniorgolfkenya-role-requests">
                <select name="status" onchange="this.form.submit()">
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                    <option value="approved" <?php selected($status_filter, 'approved'); ?>>Approved</option>
                    <option value="denied" <?php selected($status_filter, 'denied'); ?>>Denied</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Role Requests Table -->
    <div class="jgk-table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Email</th>
                    <th>Requested Role</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($role_requests)): ?>
                <tr>
                    <td colspan="7">No role requests found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($role_requests as $request): ?>
                <tr>
                    <td><strong><?php echo esc_html($request->display_name); ?></strong></td>
                    <td><?php echo esc_html($request->user_email); ?></td>
                    <td>
                        <?php 
                        $role_labels = array(
                            'jgf_coach' => 'Coach',
                            'jgf_staff' => 'Staff'
                        );
                        echo esc_html($role_labels[$request->requested_role] ?? $request->requested_role); 
                        ?>
                    </td>
                    <td>
                        <?php if (strlen($request->reason) > 50): ?>
                        <span title="<?php echo esc_attr($request->reason); ?>">
                            <?php echo esc_html(substr($request->reason, 0, 50) . '...'); ?>
                        </span>
                        <?php else: ?>
                        <?php echo esc_html($request->reason ?: 'No reason provided'); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($request->created_at)); ?></td>
                    <td>
                        <span class="jgk-status-<?php echo esc_attr($request->status); ?>">
                            <?php echo ucfirst($request->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($request->status === 'pending'): ?>
                        <button class="button button-small" onclick="openProcessModal(<?php echo $request->id; ?>, '<?php echo esc_js($request->display_name); ?>', '<?php echo esc_js($request->requested_role); ?>')">
                            Review
                        </button>
                        <?php else: ?>
                        <em>Processed</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Process Request Modal -->
<div id="process-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Process Role Request</h2>
            <span class="jgk-modal-close" onclick="closeProcessModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="process-form">
                <?php wp_nonce_field('jgk_role_request_action'); ?>
                <input type="hidden" name="action" value="process_role_request">
                <input type="hidden" name="request_id" id="modal-request-id">
                
                <p>
                    <strong>Requester:</strong> <span id="modal-requester"></span><br>
                    <strong>Requested Role:</strong> <span id="modal-role"></span>
                </p>
                
                <div class="jgk-form-field">
                    <label>Decision:</label>
                    <label style="display: inline; margin-right: 15px;">
                        <input type="radio" name="decision" value="approve" checked> Approve
                    </label>
                    <label style="display: inline;">
                        <input type="radio" name="decision" value="deny"> Deny
                    </label>
                </div>
                
                <div class="jgk-form-field">
                    <label for="reason">Reason (optional for approval, recommended for denial):</label>
                    <textarea id="reason" name="reason" rows="3" placeholder="Provide a reason for your decision..."></textarea>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Process Request">
                    <button type="button" class="button" onclick="closeProcessModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.jgk-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.jgk-modal-content {
    background-color: #fff;
    margin: 10% auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 60%;
    max-width: 600px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.jgk-modal-header {
    padding: 20px;
    background-color: #f7f7f7;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.jgk-modal-header h2 {
    margin: 0;
}

.jgk-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.jgk-modal-close:hover {
    color: #000;
}

.jgk-modal-body {
    padding: 20px;
}
</style>

<script>
function openProcessModal(requestId, requesterName, requestedRole) {
    document.getElementById('modal-request-id').value = requestId;
    document.getElementById('modal-requester').textContent = requesterName;
    document.getElementById('modal-role').textContent = requestedRole;
    document.getElementById('process-modal').style.display = 'block';
}

function closeProcessModal() {
    document.getElementById('process-modal').style.display = 'none';
    document.getElementById('process-form').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('process-modal');
    if (event.target === modal) {
        closeProcessModal();
    }
}
</script>