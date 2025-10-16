<?php
/**
 * Provide a admin area view for the main dashboard
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
if (!current_user_can('view_member_dashboard')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Load required classes
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';

// Get dashboard statistics
$stats = JuniorGolfKenya_Database::get_membership_stats();
$recent_members = JuniorGolfKenya_Database::get_members(1, 5);
$pending_role_requests = JuniorGolfKenya_Database::get_role_requests('pending');
?>

<div class="wrap jgk-admin-container">
    <div class="jgk-admin-header">
        <h1>Junior Golf Kenya Dashboard</h1>
        <p>Welcome to the Junior Golf Kenya membership management system.</p>
    </div>

    <!-- Quick Stats -->
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
            <div class="jgk-stat-number"><?php echo count($pending_role_requests); ?></div>
            <div class="jgk-stat-label">Role Requests</div>
        </div>
    </div>

    <div class="jgk-dashboard-layout">
        <!-- Recent Members -->
        <div class="jgk-table-container">
            <h2>Recent Members</h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_members)): ?>
                    <tr>
                        <td colspan="4">No members found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recent_members as $member): ?>
                    <tr>
                        <td><?php echo esc_html($member->display_name ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($member->user_email); ?></td>
                        <td>
                            <span class="jgk-status-<?php echo esc_attr($member->status); ?>">
                                <?php echo ucfirst($member->status); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($member->created_at)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p><a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members'); ?>" class="button">View All Members</a></p>
        </div>

        <!-- Quick Actions -->
        <div class="jgk-form-section">
            <h2>Quick Actions</h2>
            <div>
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members'); ?>" class="jgk-button">
                    <span class="dashicons dashicons-admin-users"></span>
                    Manage Members
                </a>
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-role-requests'); ?>" class="jgk-button">
                    <span class="dashicons dashicons-groups"></span>
                    Review Role Requests
                    <?php if (count($pending_role_requests) > 0): ?>
                    <span class="count-badge"><?php echo count($pending_role_requests); ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-coaches'); ?>" class="jgk-button">
                    <span class="dashicons dashicons-awards"></span>
                    Manage Coaches
                </a>
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-payments'); ?>" class="jgk-button">
                    <span class="dashicons dashicons-money-alt"></span>
                    View Payments
                </a>
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-reports'); ?>" class="jgk-button">
                    <span class="dashicons dashicons-chart-bar"></span>
                    Generate Reports
                </a>
            </div>

            <?php if (current_user_can('approve_role_requests') && !empty($pending_role_requests)): ?>
            <h3>Pending Role Requests</h3>
            <ul>
                <?php foreach (array_slice($pending_role_requests, 0, 3) as $request): ?>
                <li>
                    <strong><?php echo esc_html($request->display_name); ?></strong>
                    wants to become <?php echo esc_html($request->requested_role); ?>
                    <br><small><?php echo human_time_diff(strtotime($request->created_at)); ?> ago</small>
                </li>
                <?php endforeach; ?>
                <?php if (count($pending_role_requests) > 3): ?>
                <li><em>... and <?php echo count($pending_role_requests) - 3; ?> more</em></li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>