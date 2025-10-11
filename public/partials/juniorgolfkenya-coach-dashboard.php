<?php
/**
 * Coach Dashboard - Frontend View
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/public/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$coach_id = $current_user->ID;

// Load dashboard class
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-coach-dashboard.php';

// Get dashboard data
$stats = JuniorGolfKenya_Coach_Dashboard::get_stats($coach_id);
$coach_profile = JuniorGolfKenya_Coach_Dashboard::get_coach_profile($coach_id);
$assigned_members = JuniorGolfKenya_Coach_Dashboard::get_assigned_members($coach_id, '', 10, 0);
$performance = JuniorGolfKenya_Coach_Dashboard::get_performance_metrics($coach_id, 'month');
?>

<div class="jgk-coach-dashboard">
    <!-- Header Section -->
    <div class="jgk-dashboard-header">
        <div class="jgk-coach-info">
            <div class="jgk-coach-avatar">
                <?php 
                $profile_image = !empty($coach_profile->profile_image) ? $coach_profile->profile_image : '';
                if ($profile_image): ?>
                    <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($coach_profile->display_name); ?>">
                <?php else: ?>
                    <div class="jgk-avatar-placeholder">
                        <?php echo strtoupper(substr($coach_profile->display_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="jgk-coach-details">
                <h1>Welcome, <?php echo esc_html($coach_profile->display_name); ?>!</h1>
                <p class="jgk-coach-email"><?php echo esc_html($coach_profile->user_email); ?></p>
                <?php if (!empty($coach_profile->specialization)): ?>
                    <p class="jgk-coach-specialization">
                        <span class="dashicons dashicons-awards"></span>
                        <?php echo esc_html($coach_profile->specialization); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="jgk-dashboard-actions">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="jgk-btn jgk-btn-secondary">
                <span class="dashicons dashicons-exit"></span> Logout
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="jgk-stats-grid">
        <div class="jgk-stat-card jgk-card-primary">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo number_format($stats['total_members']); ?></h3>
                <p>Total Members</p>
            </div>
        </div>

        <div class="jgk-stat-card jgk-card-success">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo number_format($stats['members_by_status']['active'] ?? 0); ?></h3>
                <p>Active Members</p>
            </div>
        </div>

        <div class="jgk-stat-card jgk-card-info">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo number_format($stats['primary_members']); ?></h3>
                <p>Primary Members</p>
            </div>
        </div>

        <div class="jgk-stat-card jgk-card-warning">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo ($performance['net_change'] >= 0 ? '+' : '') . number_format($performance['net_change']); ?></h3>
                <p>This Month</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="jgk-dashboard-content">
        <!-- Assigned Members Section -->
        <div class="jgk-dashboard-section jgk-members-section">
            <div class="jgk-section-header">
                <h2>
                    <span class="dashicons dashicons-groups"></span>
                    Your Members
                </h2>
                <a href="#view-all-members" class="jgk-btn jgk-btn-small">View All</a>
            </div>

            <?php if (!empty($assigned_members)): ?>
                <div class="jgk-members-list">
                    <?php foreach ($assigned_members as $member): ?>
                        <div class="jgk-member-card">
                            <div class="jgk-member-avatar">
                                <?php 
                                $avatar = get_avatar_url($member->user_id, array('size' => 50));
                                ?>
                                <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($member->first_name); ?>">
                                <?php if ($member->is_primary): ?>
                                    <span class="jgk-primary-badge" title="Primary Member">★</span>
                                <?php endif; ?>
                            </div>
                            <div class="jgk-member-info">
                                <h4><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></h4>
                                <p class="jgk-member-meta">
                                    <span class="jgk-member-type"><?php echo esc_html(ucfirst($member->membership_type)); ?></span>
                                    <span class="jgk-member-status jgk-status-<?php echo esc_attr($member->status); ?>">
                                        <?php echo esc_html(ucfirst($member->status)); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="jgk-member-actions">
                                <button class="jgk-btn jgk-btn-small jgk-btn-icon" title="View Details">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="jgk-empty-state">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p>No members assigned yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="jgk-dashboard-sidebar">
            <!-- Performance Section -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h3>
                        <span class="dashicons dashicons-chart-area"></span>
                        Performance (This Month)
                    </h3>
                </div>
                <div class="jgk-performance-stats">
                    <div class="jgk-performance-item">
                        <span class="jgk-performance-label">New Members:</span>
                        <span class="jgk-performance-value jgk-positive">+<?php echo number_format($performance['new_members']); ?></span>
                    </div>
                    <div class="jgk-performance-item">
                        <span class="jgk-performance-label">Removed:</span>
                        <span class="jgk-performance-value jgk-negative">-<?php echo number_format($performance['removed_members']); ?></span>
                    </div>
                    <hr>
                    <div class="jgk-performance-item">
                        <span class="jgk-performance-label"><strong>Net Change:</strong></span>
                        <span class="jgk-performance-value <?php echo $performance['net_change'] >= 0 ? 'jgk-positive' : 'jgk-negative'; ?>">
                            <?php echo ($performance['net_change'] >= 0 ? '+' : '') . number_format($performance['net_change']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Members by Type -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h3>
                        <span class="dashicons dashicons-category"></span>
                        Members by Type
                    </h3>
                </div>
                <div class="jgk-breakdown-list">
                    <?php if (!empty($stats['members_by_type'])): ?>
                        <?php foreach ($stats['members_by_type'] as $type => $count): ?>
                            <div class="jgk-breakdown-item">
                                <span class="jgk-breakdown-label"><?php echo esc_html(ucfirst($type)); ?>:</span>
                                <span class="jgk-breakdown-value"><?php echo number_format($count); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="jgk-text-muted">No data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h3>
                        <span class="dashicons dashicons-clock"></span>
                        Recent Activity
                    </h3>
                </div>
                <div class="jgk-activity-list">
                    <?php if (!empty($stats['recent_activities'])): ?>
                        <?php foreach (array_slice($stats['recent_activities'], 0, 5) as $activity): ?>
                            <div class="jgk-activity-item">
                                <span class="jgk-activity-icon">
                                    <span class="dashicons dashicons-<?php echo $activity['is_primary'] ? 'star-filled' : 'admin-users'; ?>"></span>
                                </span>
                                <div class="jgk-activity-content">
                                    <p class="jgk-activity-text">
                                        <strong><?php echo esc_html($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                        assigned as <?php echo $activity['is_primary'] ? 'primary' : 'secondary'; ?> member
                                    </p>
                                    <p class="jgk-activity-time"><?php echo human_time_diff(strtotime($activity['assigned_at']), current_time('timestamp')) . ' ago'; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="jgk-text-muted">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Coach Dashboard Styles */
.jgk-coach-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
}

/* Header */
.jgk-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.jgk-coach-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.jgk-coach-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    background: white;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.jgk-coach-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.jgk-avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
    background: white;
}

.jgk-coach-details h1 {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: 700;
}

.jgk-coach-email {
    margin: 0 0 5px 0;
    opacity: 0.9;
    font-size: 14px;
}

.jgk-coach-specialization {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    opacity: 0.95;
}

/* Stats Grid */
.jgk-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.jgk-stat-card {
    display: flex;
    align-items: center;
    gap: 20px;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.jgk-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.jgk-stat-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 28px;
}

.jgk-card-primary .jgk-stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.jgk-card-success .jgk-stat-icon {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.jgk-card-info .jgk-stat-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.jgk-card-warning .jgk-stat-icon {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.jgk-stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
}

.jgk-stat-content p {
    margin: 0;
    color: #7f8c8d;
    font-size: 14px;
}

/* Dashboard Content */
.jgk-dashboard-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
}

.jgk-dashboard-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.jgk-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.jgk-section-header h2,
.jgk-section-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #2c3e50;
}

/* Members List */
.jgk-members-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.jgk-member-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.jgk-member-card:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.jgk-member-avatar {
    position: relative;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.jgk-member-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.jgk-primary-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    background: #ffd700;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 2px solid white;
}

.jgk-member-info {
    flex: 1;
}

.jgk-member-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #2c3e50;
}

.jgk-member-meta {
    margin: 0;
    display: flex;
    gap: 10px;
    font-size: 12px;
}

.jgk-member-type {
    color: #7f8c8d;
}

.jgk-member-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.jgk-status-active {
    background: #d4edda;
    color: #155724;
}

.jgk-status-pending {
    background: #fff3cd;
    color: #856404;
}

/* Performance Stats */
.jgk-performance-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.jgk-performance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.jgk-performance-value {
    font-weight: 700;
    font-size: 18px;
}

.jgk-positive {
    color: #28a745;
}

.jgk-negative {
    color: #dc3545;
}

/* Breakdown List */
.jgk-breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.jgk-breakdown-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.jgk-breakdown-value {
    font-weight: 600;
    color: #667eea;
}

/* Activity List */
.jgk-activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.jgk-activity-item {
    display: flex;
    gap: 12px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.jgk-activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #667eea;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.jgk-activity-text {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #2c3e50;
}

.jgk-activity-time {
    margin: 0;
    font-size: 12px;
    color: #7f8c8d;
}

/* Buttons */
.jgk-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.jgk-btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.jgk-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
}

.jgk-btn-small {
    padding: 6px 12px;
    font-size: 12px;
}

.jgk-btn-icon {
    padding: 8px;
    background: transparent;
    border: 1px solid #dee2e6;
}

.jgk-btn-icon:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* Empty State */
.jgk-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #7f8c8d;
}

.jgk-empty-state .dashicons {
    font-size: 48px;
    opacity: 0.3;
}

/* Notice */
.jgk-notice {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.jgk-notice-error {
    background: #fee;
    color: #c33;
    border-left: 4px solid #c33;
}

/* Responsive */
@media (max-width: 1024px) {
    .jgk-dashboard-content {
        grid-template-columns: 1fr;
    }
    
    .jgk-dashboard-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .jgk-dashboard-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .jgk-coach-info {
        flex-direction: column;
    }
    
    .jgk-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
