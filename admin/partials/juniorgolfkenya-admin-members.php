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
                'user_pass' => $_POST['password']
            );
            
            $member_data = array(
                'membership_type' => sanitize_text_field($_POST['membership_type']),
                'club_affiliation' => sanitize_text_field($_POST['club_affiliation']),
                'emergency_contact_name' => sanitize_text_field($_POST['emergency_contact_name']),
                'emergency_contact_phone' => sanitize_text_field($_POST['emergency_contact_phone'])
            );

            $result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
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
                    <input type="text" id="display_name" name="display_name">
                </div>
                <div class="jgk-form-field">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="membership_type">Membership Type</label>
                    <select id="membership_type" name="membership_type">
                        <option value="standard">Standard</option>
                        <option value="premium">Premium</option>
                        <option value="youth">Youth</option>
                    </select>
                </div>
                <div class="jgk-form-field">
                    <label for="club_affiliation">Club Affiliation</label>
                    <input type="text" id="club_affiliation" name="club_affiliation">
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name">
                </div>
                <div class="jgk-form-field">
                    <label for="emergency_contact_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone">
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
            <form method="get">
                <input type="hidden" name="page" value="juniorgolfkenya-members">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                    <option value="expired" <?php selected($status_filter, 'expired'); ?>>Expired</option>
                </select>
                <input type="submit" class="button" value="Filter">
            </form>
        </div>
        <div class="alignright">
            <form method="get">
                <input type="hidden" name="page" value="juniorgolfkenya-members">
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
                    <th>Status</th>
                    <th>Club</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                <tr>
                    <td colspan="7">No members found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td><strong><?php echo esc_html($member->membership_number); ?></strong></td>
                    <td><?php echo esc_html($member->display_name ?: 'N/A'); ?></td>
                    <td><?php echo esc_html($member->user_email); ?></td>
                    <td>
                        <span class="jgk-status-<?php echo esc_attr($member->status); ?>">
                            <?php echo ucfirst($member->status); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($member->club_affiliation ?: 'N/A'); ?></td>
                    <td><?php echo date('M j, Y', strtotime($member->created_at)); ?></td>
                    <td>
                        <?php if ($member->status === 'pending'): ?>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('jgk_members_action'); ?>
                            <input type="hidden" name="action" value="approve_member">
                            <input type="hidden" name="member_id" value="<?php echo $member->id; ?>">
                            <input type="submit" class="button button-small jgk-button" value="Approve" 
                                   onclick="return confirm('Are you sure you want to approve this member?')">
                        </form>
                        <?php endif; ?>
                        
                        <button class="button button-small" onclick="toggleCoachAssignment(<?php echo $member->id; ?>)">
                            Assign Coach
                        </button>
                        
                        <!-- Coach Assignment Form (Hidden) -->
                        <div id="coach-form-<?php echo $member->id; ?>" style="display: none; margin-top: 10px;">
                            <form method="post">
                                <?php wp_nonce_field('jgk_members_action'); ?>
                                <input type="hidden" name="action" value="assign_coach">
                                <input type="hidden" name="member_id" value="<?php echo $member->id; ?>">
                                <select name="coach_id" required>
                                    <option value="">Select Coach</option>
                                    <?php foreach ($coaches as $coach): ?>
                                    <option value="<?php echo $coach->user_id; ?>">
                                        <?php echo esc_html($coach->display_name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="submit" class="button button-small" value="Assign">
                                <button type="button" class="button button-small" 
                                        onclick="toggleCoachAssignment(<?php echo $member->id; ?>)">Cancel</button>
                            </form>
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

<script>
function toggleAddMemberForm() {
    const form = document.getElementById('add-member-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function toggleCoachAssignment(memberId) {
    const form = document.getElementById('coach-form-' + memberId);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>