<?php
/**
 * Provide a admin area view for members management
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

// Load required classes
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-user-manager.php';

// Check user permissions
if (!current_user_can('edit_members')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_members_action')) {
        wp_die(__('Security check failed.'));
    }

    switch ($_POST['action']) {
        case 'approve_member':
            if (isset($_POST['member_id'])) {
                $result = JuniorGolfKenya_User_Manager::approve_member($_POST['member_id']);
                if ($result) {
                    $message = 'Member approved successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to approve member.';
                    $message_type = 'error';
                }
            }
            break;

        case 'assign_coach':
            if (isset($_POST['member_id']) && isset($_POST['coach_id'])) {
                $result = JuniorGolfKenya_User_Manager::assign_coach($_POST['member_id'], $_POST['coach_id']);
                if ($result) {
                    $message = 'Coach assigned successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to assign coach.';
                    $message_type = 'error';
                }
            }
            break;

        case 'create_member':
            $user_data = array(
                'user_login' => sanitize_user($_POST['username']),
                'user_email' => sanitize_email($_POST['email']),
                'display_name' => sanitize_text_field($_POST['display_name']),
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'user_pass' => $_POST['password']
            );
            
            $member_data = array(
                'membership_type' => sanitize_text_field($_POST['membership_type']),
                'status' => sanitize_text_field($_POST['status']),
                'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
                'gender' => sanitize_text_field($_POST['gender']),
                'phone' => sanitize_text_field($_POST['phone']),
                'handicap' => floatval($_POST['handicap']),
                'club_affiliation' => sanitize_text_field($_POST['club_affiliation']),
                'emergency_contact_name' => sanitize_text_field($_POST['emergency_contact_name']),
                'emergency_contact_phone' => sanitize_text_field($_POST['emergency_contact_phone']),
                'medical_conditions' => sanitize_textarea_field($_POST['medical_conditions']),
                'join_date' => current_time('Y-m-d'),
                'expiry_date' => date('Y-m-d', strtotime('+1 year'))
            );

            $result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;

        case 'update_member_status':
            if (isset($_POST['member_id']) && isset($_POST['new_status'])) {
                $member_id = intval($_POST['member_id']);
                $new_status = sanitize_text_field($_POST['new_status']);
                $reason = sanitize_textarea_field($_POST['reason']);
                
                $result = JuniorGolfKenya_Database::update_member_status($member_id, $new_status, $reason);
                if ($result) {
                    $message = 'Member status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update member status.';
                    $message_type = 'error';
                }
            }
            break;
    }
}

// Get members data
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

if ($search) {
    $members = JuniorGolfKenya_Database::search_members($search, $page, $per_page);
    $total_members = count($members); // Simplified for search
} else {
    $members = JuniorGolfKenya_Database::get_members($page, $per_page, $status_filter);
    $total_members = JuniorGolfKenya_Database::get_members_count($status_filter);
}

$total_pages = ceil($total_members / $per_page);

// Get statistics
$stats = JuniorGolfKenya_Database::get_membership_stats();

// Get available coaches
$coaches = JuniorGolfKenya_User_Manager::get_available_coaches();
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">JGK Members</h1>
    <a href="#" class="page-title-action" onclick="toggleAddMemberForm()">Add New Member</a>
    <hr class="wp-header-end">

    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="jgk-stats-grid">
        <div class="jgk-stat-card">
            <div class="jgk-stat-number"><?php echo number_format($stats['total']); ?></div>
            <div class="jgk-stat-label">Total Members</div>
        </div>
        <div class="jgk-stat-card">
            <div class="jgk-stat-number"><?php echo number_format($stats['active']); ?></div>
            <div class="jgk-stat-label">Active Members</div>
        </div>
        <div class="jgk-stat-card">
            <div class="jgk-stat-number"><?php echo number_format($stats['pending']); ?></div>
            <div class="jgk-stat-label">Pending Approval</div>
        </div>
        <div class="jgk-stat-card">
            <div class="jgk-stat-number"><?php echo number_format($stats['expired']); ?></div>
            <div class="jgk-stat-label">Expired</div>
        </div>
    </div>

    <!-- Add Member Form (Hidden by default) -->
    <div id="add-member-form" class="jgk-form-section" style="display: none;">
        <h2>Add New Member</h2>
        <form method="post">
            <?php wp_nonce_field('jgk_members_action'); ?>
            <input type="hidden" name="action" value="create_member">
            
            <h3>Personal Information</h3>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="jgk-form-field">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="jgk-form-field">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" placeholder="Auto-generated from first/last name">
                </div>
                <div class="jgk-form-field">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <small>Minimum 8 characters</small>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth">
                </div>
                <div class="jgk-form-field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                        <option value="prefer_not_to_say">Prefer not to say</option>
                    </select>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+254...">
                </div>
                <div class="jgk-form-field">
                    <label for="handicap">Golf Handicap</label>
                    <input type="number" id="handicap" name="handicap" step="0.1" min="0" max="54" placeholder="0.0">
                </div>
            </div>
            
            <h3>Membership Details</h3>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="membership_type">Membership Type *</label>
                    <select id="membership_type" name="membership_type" required>
                        <option value="junior">Junior (Under 18)</option>
                        <option value="youth">Youth (18-25)</option>
                        <option value="adult">Adult (26+)</option>
                        <option value="senior">Senior (65+)</option>
                        <option value="family">Family Package</option>
                    </select>
                </div>
                <div class="jgk-form-field">
                    <label for="status">Initial Status *</label>
                    <select id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="pending" selected>Pending Approval</option>
                        <option value="suspended">Suspended</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="club_affiliation">Club Affiliation</label>
                    <input type="text" id="club_affiliation" name="club_affiliation" placeholder="Current golf club">
                </div>
                <div class="jgk-form-field">
                    <label for="medical_conditions">Medical Conditions</label>
                    <textarea id="medical_conditions" name="medical_conditions" rows="2" placeholder="Any medical conditions to be aware of..."></textarea>
                </div>
            </div>
            
            <h3>Emergency Contact</h3>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" placeholder="Full name">
                </div>
                <div class="jgk-form-field">
                    <label for="emergency_contact_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" placeholder="+254...">
                </div>
            </div>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="Create Member">
                <button type="button" class="button" onclick="toggleAddMemberForm()">Cancel</button>
            </p>
        </form>
    </div>

    <!-- Filters and Search -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-flex; gap: 10px; align-items: center;">
                <input type="hidden" name="page" value="juniorgolfkenya-members">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending Approval</option>
                    <option value="expired" <?php selected($status_filter, 'expired'); ?>>Expired</option>
                    <option value="suspended" <?php selected($status_filter, 'suspended'); ?>>Suspended</option>
                </select>
                <input type="submit" class="button" value="Filter">
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members'); ?>" class="button">Clear Filter</a>
            </form>
        </div>
        <div class="alignright">
            <form method="get" style="display: inline-flex; gap: 5px;">
                <input type="hidden" name="page" value="juniorgolfkenya-members">
                <?php if ($status_filter): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                <?php endif; ?>
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search members...">
                <input type="submit" class="button" value="Search">
            </form>
        </div>
    </div>

    <!-- Members Table -->
    <div class="jgk-table-container">
        <table class="wp-list-table widefat fixed striped jgk-table">
            <thead>
                <tr>
                    <th>Member #</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Coach</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                <tr>
                    <td colspan="8">No members found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td><strong><?php echo esc_html($member->membership_number ?: 'JGK-' . str_pad($member->id, 4, '0', STR_PAD_LEFT)); ?></strong></td>
                    <td>
                        <strong><?php echo esc_html($member->display_name ?: ($member->first_name . ' ' . $member->last_name)); ?></strong>
                        <?php if ($member->age): ?>
                        <br><small>Age: <?php echo intval($member->age); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html($member->user_email); ?>
                        <?php if ($member->phone): ?>
                        <br><small><?php echo esc_html($member->phone); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo ucfirst($member->membership_type ?: 'standard'); ?></td>
                    <td>
                        <span class="jgk-status-<?php echo esc_attr($member->status); ?>">
                            <?php echo ucfirst($member->status); ?>
                        </span>
                        <?php if ($member->status === 'expired' && $member->expiry_date): ?>
                        <br><small>Expired: <?php echo date('M j, Y', strtotime($member->expiry_date)); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($member->coach_name): ?>
                        <?php echo esc_html($member->coach_name); ?>
                        <?php else: ?>
                        <em>No coach assigned</em>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($member->created_at)); ?></td>
                    <td class="jgk-actions">
                        <div class="jgk-action-buttons">
                            <!-- Status Actions -->
                            <?php if ($member->status === 'pending'): ?>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('jgk_members_action'); ?>
                                <input type="hidden" name="action" value="approve_member">
                                <input type="hidden" name="member_id" value="<?php echo $member->id; ?>">
                                <input type="submit" class="button button-small jgk-button-approve" value="Approve" 
                                       onclick="return confirm('Are you sure you want to approve this member?')">
                            </form>
                            <?php endif; ?>
                            
                            <!-- Change Status Button -->
                            <button class="button button-small jgk-button-status" 
                                    onclick="openStatusModal(<?php echo $member->id; ?>, '<?php echo esc_js($member->display_name); ?>', '<?php echo esc_js($member->status); ?>')">
                                Change Status
                            </button>
                            
                            <!-- Assign Coach Button -->
                            <button class="button button-small jgk-button-coach" 
                                    onclick="openCoachModal(<?php echo $member->id; ?>, '<?php echo esc_js($member->display_name); ?>')">
                                <?php echo $member->coach_name ? 'Change Coach' : 'Assign Coach'; ?>
                            </button>
                            
                            <!-- View Details -->
                            <button class="button button-small jgk-button-view" 
                                    onclick="viewMemberDetails(<?php echo $member->id; ?>)">
                                View Details
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $page
            );
            echo paginate_links($pagination_args);
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Change Status Modal -->
<div id="status-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Change Member Status</h2>
            <span class="jgk-modal-close" onclick="closeStatusModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="status-form">
                <?php wp_nonce_field('jgk_members_action'); ?>
                <input type="hidden" name="action" value="update_member_status">
                <input type="hidden" name="member_id" id="status-member-id">
                
                <p><strong>Member:</strong> <span id="status-member-name"></span></p>
                <p><strong>Current Status:</strong> <span id="status-current"></span></p>
                
                <div class="jgk-form-field">
                    <label for="new_status">New Status:</label>
                    <select id="new_status" name="new_status" required>
                        <option value="active">Active</option>
                        <option value="pending">Pending Approval</option>
                        <option value="expired">Expired</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                
                <div class="jgk-form-field">
                    <label for="status_reason">Reason for change:</label>
                    <textarea id="status_reason" name="reason" rows="3" placeholder="Optional reason for status change..."></textarea>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Update Status">
                    <button type="button" class="button" onclick="closeStatusModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Coach Modal -->
<div id="coach-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Assign Coach</h2>
            <span class="jgk-modal-close" onclick="closeCoachModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="coach-form">
                <?php wp_nonce_field('jgk_members_action'); ?>
                <input type="hidden" name="action" value="assign_coach">
                <input type="hidden" name="member_id" id="coach-member-id">
                
                <p><strong>Member:</strong> <span id="coach-member-name"></span></p>
                
                <div class="jgk-form-field">
                    <label for="coach_id">Select Coach:</label>
                    <select id="coach_id" name="coach_id" required>
                        <option value="">Choose a coach...</option>
                        <?php foreach ($coaches as $coach): ?>
                        <option value="<?php echo $coach->user_id; ?>">
                            <?php echo esc_html($coach->display_name); ?>
                            <?php if (isset($coach->member_count)): ?>
                            (<?php echo intval($coach->member_count); ?> members)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Assign Coach">
                    <button type="button" class="button" onclick="closeCoachModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.jgk-form-section {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.jgk-form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.jgk-form-field {
    flex: 1;
}

.jgk-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.jgk-form-field input,
.jgk-form-field select,
.jgk-form-field textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.jgk-form-field small {
    color: #666;
    font-size: 12px;
}

.jgk-form-section h3 {
    color: #2271b1;
    border-bottom: 2px solid #2271b1;
    padding-bottom: 5px;
    margin: 20px 0 15px 0;
}

.jgk-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.jgk-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.jgk-stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #2271b1;
}

.jgk-stat-label {
    color: #666;
    margin-top: 5px;
}

.jgk-actions {
    white-space: nowrap;
}

.jgk-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 5px;
    align-items: flex-start;
}

.jgk-action-buttons .button {
    margin: 0;
    white-space: nowrap;
}

.jgk-button-approve { background: #00a32a; color: white; border-color: #00a32a; }
.jgk-button-status { background: #2271b1; color: white; border-color: #2271b1; }
.jgk-button-coach { background: #dba617; color: white; border-color: #dba617; }
.jgk-button-view { background: #666; color: white; border-color: #666; }

.jgk-status-active { color: #00a32a; font-weight: bold; }
.jgk-status-pending { color: #b32d2e; font-weight: bold; }
.jgk-status-expired { color: #d63638; font-weight: bold; }
.jgk-status-suspended { color: #dba617; font-weight: bold; }

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
    max-width: 500px;
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

@media (max-width: 768px) {
    .jgk-form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .jgk-action-buttons {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .jgk-modal-content {
        width: 90%;
        margin: 5% auto;
    }
}
</style>

<script>
function toggleAddMemberForm() {
    const form = document.getElementById('add-member-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    
    if (form.style.display === 'block') {
        // Auto-generate display name when first/last name are filled
        const firstName = document.getElementById('first_name');
        const lastName = document.getElementById('last_name');
        const displayName = document.getElementById('display_name');
        
        function updateDisplayName() {
            if (firstName.value && lastName.value && !displayName.value) {
                displayName.value = firstName.value + ' ' + lastName.value;
            }
        }
        
        firstName.addEventListener('blur', updateDisplayName);
        lastName.addEventListener('blur', updateDisplayName);
    }
}

function openStatusModal(memberId, memberName, currentStatus) {
    document.getElementById('status-member-id').value = memberId;
    document.getElementById('status-member-name').textContent = memberName;
    document.getElementById('status-current').textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
    document.getElementById('new_status').value = currentStatus;
    document.getElementById('status-modal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('status-modal').style.display = 'none';
    document.getElementById('status-form').reset();
}

function openCoachModal(memberId, memberName) {
    document.getElementById('coach-member-id').value = memberId;
    document.getElementById('coach-member-name').textContent = memberName;
    document.getElementById('coach-modal').style.display = 'block';
}

function closeCoachModal() {
    document.getElementById('coach-modal').style.display = 'none';
    document.getElementById('coach-form').reset();
}

function viewMemberDetails(memberId) {
    // Open member details in new tab/window
    const url = '<?php echo admin_url('admin.php?page=juniorgolfkenya-member-details&member_id='); ?>' + memberId;
    window.open(url, '_blank');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const statusModal = document.getElementById('status-modal');
    const coachModal = document.getElementById('coach-modal');
    
    if (event.target === statusModal) {
        closeStatusModal();
    }
    if (event.target === coachModal) {
        closeCoachModal();
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const addMemberForm = document.querySelector('#add-member-form form');
    if (addMemberForm) {
        addMemberForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
        });
    }
});
</script>