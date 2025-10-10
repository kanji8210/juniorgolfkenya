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
$membership_stats = JuniorGolfKenya_Database::get_membership_statistics($date_from, $date_to);
$payment_stats = JuniorGolfKenya_Database::get_payment_statistics($date_from, $date_to);
$coaches = JuniorGolfKenya_Database::get_coaches();

if ($coach_id) {
    $coach_performance = JuniorGolfKenya_Database::get_coach_performance($coach_id, $date_from, $date_to);
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
                <h3>Membership Growth</h3>
                <canvas id="membershipChart"></canvas>
            </div>
            <div class="jgk-chart-container">
                <h3>Revenue Trends</h3>
                <canvas id="revenueChart"></canvas>
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
                    <span>KSh <?php echo number_format($payment_stats['completed_payments'], 2); ?></span>
                </div>
                <div class="jgk-summary-item">
                    <h4>Pending Payments</h4>
                    <span>KSh <?php echo number_format($payment_stats['pending_payments'], 2); ?></span>
                </div>
                <div class="jgk-summary-item">
                    <h4>Failed Payments</h4>
                    <span>KSh <?php echo number_format($payment_stats['failed_payments'], 2); ?></span>
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
    // Initialize membership chart
    const membershipCtx = document.getElementById('membershipChart').getContext('2d');
    // Chart implementation would go here
    
    // Initialize revenue chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    // Chart implementation would go here
});
<?php endif; ?>
</script>