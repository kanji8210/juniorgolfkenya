<?php
/**
 * Member Dashboard - Frontend View
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

// Helper function to safely get object property
function jgk_get_prop($obj, $prop, $default = 'N/A') {
    return isset($obj->$prop) && !empty($obj->$prop) ? $obj->$prop : $default;
}

// Get current user
$current_user = wp_get_current_user();

// Load dashboard class
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-member-dashboard.php';

// Get member ID from user
global $wpdb;
$members_table = $wpdb->prefix . 'jgk_members';
$member = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM {$members_table} WHERE user_id = %d
", $current_user->ID));

if (!$member) {
    echo '<div class="jgk-notice jgk-notice-error">Member profile not found. Please contact the administrator.</div>';
    return;
}

$member_id = $member->id;

// Get dashboard data
$stats = JuniorGolfKenya_Member_Dashboard::get_stats($member_id);
$assigned_coaches = JuniorGolfKenya_Member_Dashboard::get_assigned_coaches($member_id);
$recent_activities = JuniorGolfKenya_Member_Dashboard::get_recent_activities($member_id, 10);
$parents = JuniorGolfKenya_Member_Dashboard::get_parents($member_id);
$profile_image = JuniorGolfKenya_Member_Dashboard::get_profile_image($member_id, 'medium');
?>

<div class="jgk-member-dashboard">
    <!-- Header Section -->
    <div class="jgk-dashboard-header">
        <div class="jgk-member-info">
            <div class="jgk-member-avatar">
                <?php if ($profile_image): ?>
                    <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($stats['member']->first_name); ?>">
                <?php else: ?>
                    <div class="jgk-avatar-placeholder">
                        <?php echo strtoupper(substr($stats['member']->first_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="jgk-member-details">
                <h1>Welcome, <?php echo esc_html($stats['member']->first_name); ?>!</h1>
                <p class="jgk-member-email"><?php echo esc_html($stats['member']->user_email); ?></p>
                <div class="jgk-member-badges">
                    <span class="jgk-badge jgk-badge-<?php echo esc_attr($stats['member']->status); ?>">
                        <?php echo esc_html(ucfirst($stats['member']->status)); ?>
                    </span>
                    <span class="jgk-badge jgk-badge-type">
                        <?php echo esc_html(ucfirst($stats['member']->membership_type)); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="jgk-dashboard-actions">
            <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="jgk-logout-btn">
                <span class="dashicons dashicons-exit"></span> Logout
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="jgk-stats-grid">
        <div class="jgk-stat-card jgk-card-primary">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo number_format($stats['coaches_count']); ?></h3>
                <p>Assigned Coaches</p>
            </div>
        </div>

        <div class="jgk-stat-card jgk-card-success">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo esc_html($stats['membership_duration'] ?: 'N/A'); ?></h3>
                <p>Member Since</p>
            </div>
        </div>

        <div class="jgk-stat-card jgk-card-info">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo number_format($stats['profile_completion']); ?>%</h3>
                <p>Profile Completion</p>
                <div class="jgk-progress-bar">
                    <div class="jgk-progress-fill" style="width: <?php echo $stats['profile_completion']; ?>%"></div>
                </div>
            </div>
        </div>

        <div class="jgk-stat-card jgk-card-warning">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-flag"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo esc_html(jgk_get_prop($stats['member'], 'handicap_index')); ?></h3>
                <p>Handicap Index</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="jgk-dashboard-content">
        <!-- Profile & Coaches Section -->
        <div class="jgk-dashboard-main">
            <!-- Personal Information -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-id"></span>
                        Personal Information
                    </h2>
                </div>
                <div class="jgk-info-grid">
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Full Name:</span>
                        <span class="jgk-info-value"><?php echo esc_html($stats['member']->first_name . ' ' . $stats['member']->last_name); ?></span>
                    </div>
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Date of Birth:</span>
                        <span class="jgk-info-value">
                            <?php 
                            if ($stats['member']->date_of_birth) {
                                echo esc_html(date('F j, Y', strtotime($stats['member']->date_of_birth)));
                                if ($stats['age']) {
                                    echo ' (' . $stats['age'] . ' years old)';
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Gender:</span>
                        <span class="jgk-info-value"><?php echo esc_html(ucfirst(jgk_get_prop($stats['member'], 'gender'))); ?></span>
                    </div>
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Phone:</span>
                        <span class="jgk-info-value"><?php echo esc_html(jgk_get_prop($stats['member'], 'phone')); ?></span>
                    </div>
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Club:</span>
                        <span class="jgk-info-value"><?php echo esc_html(jgk_get_prop($stats['member'], 'club_name')); ?></span>
                    </div>
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Membership Number:</span>
                        <span class="jgk-info-value"><?php echo esc_html(jgk_get_prop($stats['member'], 'membership_number')); ?></span>
                    </div>
                </div>
            </div>

            <!-- Your Coaches -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-businessman"></span>
                        Your Coaches
                    </h2>
                </div>

                <?php if (!empty($assigned_coaches)): ?>
                    <div class="jgk-coaches-grid">
                        <?php foreach ($assigned_coaches as $coach): ?>
                            <div class="jgk-coach-card">
                                <div class="jgk-coach-avatar">
                                    <?php 
                                    $coach_avatar = get_avatar_url($coach->coach_id, array('size' => 60));
                                    ?>
                                    <img src="<?php echo esc_url($coach_avatar); ?>" alt="<?php echo esc_attr($coach->coach_name); ?>">
                                    <?php if ($coach->is_primary): ?>
                                        <span class="jgk-primary-badge" title="Primary Coach">★</span>
                                    <?php endif; ?>
                                </div>
                                <div class="jgk-coach-info">
                                    <h4><?php echo esc_html($coach->coach_name); ?></h4>
                                    <?php if ($coach->is_primary): ?>
                                        <span class="jgk-badge jgk-badge-primary">Primary Coach</span>
                                    <?php endif; ?>
                                    <?php if ($coach->specialization): ?>
                                        <p class="jgk-coach-specialization">
                                            <span class="dashicons dashicons-awards"></span>
                                            <?php echo esc_html($coach->specialization); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="jgk-coach-contact">
                                        <span class="dashicons dashicons-email"></span>
                                        <?php echo esc_html($coach->coach_email); ?>
                                    </p>
                                    <?php if ($coach->coach_phone): ?>
                                        <p class="jgk-coach-contact">
                                            <span class="dashicons dashicons-phone"></span>
                                            <?php echo esc_html($coach->coach_phone); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="jgk-coach-since">
                                        <em>Assigned <?php echo human_time_diff(strtotime($coach->assigned_at), current_time('timestamp')); ?> ago</em>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="jgk-empty-state">
                        <span class="dashicons dashicons-admin-users"></span>
                        <p>No coaches assigned yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Parents/Guardians -->
            <?php if (!empty($parents)): ?>
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-groups"></span>
                        Parents/Guardians
                    </h2>
                </div>
                <div class="jgk-parents-grid">
                    <?php foreach ($parents as $parent): ?>
                        <div class="jgk-parent-card">
                            <div class="jgk-parent-header">
                                <h4><?php echo esc_html($parent->first_name . ' ' . $parent->last_name); ?></h4>
                                <span class="jgk-badge jgk-badge-relationship">
                                    <?php echo esc_html(ucfirst($parent->relationship)); ?>
                                </span>
                            </div>
                            <div class="jgk-parent-contacts">
                                <?php if ($parent->phone): ?>
                                    <p>
                                        <span class="dashicons dashicons-phone"></span>
                                        <a href="tel:<?php echo esc_attr($parent->phone); ?>"><?php echo esc_html($parent->phone); ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ($parent->email): ?>
                                    <p>
                                        <span class="dashicons dashicons-email"></span>
                                        <a href="mailto:<?php echo esc_attr($parent->email); ?>"><?php echo esc_html($parent->email); ?></a>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php if ($parent->is_primary_contact || $parent->emergency_contact): ?>
                                <div class="jgk-parent-flags">
                                    <?php if ($parent->is_primary_contact): ?>
                                        <span class="jgk-flag jgk-flag-primary">Primary Contact</span>
                                    <?php endif; ?>
                                    <?php if ($parent->emergency_contact): ?>
                                        <span class="jgk-flag jgk-flag-emergency">Emergency Contact</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="jgk-dashboard-sidebar">
            <!-- Primary Coach Widget -->
            <?php if ($stats['primary_coach']): ?>
            <div class="jgk-dashboard-section jgk-primary-coach-widget">
                <div class="jgk-section-header">
                    <h3>
                        <span class="dashicons dashicons-star-filled"></span>
                        Primary Coach
                    </h3>
                </div>
                <div class="jgk-primary-coach-card">
                    <div class="jgk-coach-avatar-large">
                        <?php 
                        $primary_avatar = get_avatar_url($stats['primary_coach']->coach_id, array('size' => 80));
                        ?>
                        <img src="<?php echo esc_url($primary_avatar); ?>" alt="<?php echo esc_attr($stats['primary_coach']->coach_name); ?>">
                    </div>
                    <h4><?php echo esc_html($stats['primary_coach']->coach_name); ?></h4>
                    <p class="jgk-coach-email"><?php echo esc_html($stats['primary_coach']->coach_email); ?></p>
                    <a href="mailto:<?php echo esc_attr($stats['primary_coach']->coach_email); ?>" class="jgk-btn jgk-btn-primary jgk-btn-block">
                        <span class="dashicons dashicons-email"></span>
                        Contact Coach
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Links -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h3>
                        <span class="dashicons dashicons-menu"></span>
                        Quick Links
                    </h3>
                </div>
                <div class="jgk-quick-links">
                    <a href="#" class="jgk-quick-link">
                        <span class="dashicons dashicons-edit"></span>
                        Edit Profile
                    </a>
                    <a href="#" class="jgk-quick-link">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        My Schedule
                    </a>
                    <a href="#" class="jgk-quick-link">
                        <span class="dashicons dashicons-chart-bar"></span>
                        My Progress
                    </a>
                    <a href="#" class="jgk-quick-link">
                        <span class="dashicons dashicons-tickets-alt"></span>
                        Payment History
                    </a>
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
                    <?php if (!empty($recent_activities)): ?>
                        <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                            <div class="jgk-activity-item">
                                <span class="jgk-activity-icon">
                                    <span class="dashicons dashicons-<?php echo esc_attr($activity['icon']); ?>"></span>
                                </span>
                                <div class="jgk-activity-content">
                                    <p class="jgk-activity-text"><?php echo esc_html($activity['description']); ?></p>
                                    <p class="jgk-activity-time"><?php echo human_time_diff(strtotime($activity['date']), current_time('timestamp')) . ' ago'; ?></p>
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
/* Member Dashboard Styles */
.jgk-member-dashboard {
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
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3);
}

.jgk-member-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.jgk-member-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    background: white;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.jgk-member-avatar img {
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
    color: #f5576c;
    background: white;
}

.jgk-member-details h1 {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: 700;
}

.jgk-member-email {
    margin: 0 0 10px 0;
    opacity: 0.9;
    font-size: 14px;
}

.jgk-member-badges {
    display: flex;
    gap: 8px;
}

.jgk-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.3);
}

.jgk-badge-active {
    background: #28a745;
}

.jgk-badge-pending {
    background: #ffc107;
}

.jgk-badge-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

/* Logout Button */
.jgk-logout-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
}

.jgk-logout-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    color: #fff;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.jgk-logout-btn:active {
    transform: translateY(0);
}

.jgk-logout-btn .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

/* Stats Grid - Same as coach dashboard */
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
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
    margin: 0 0 5px 0;
    color: #7f8c8d;
    font-size: 14px;
}

.jgk-progress-bar {
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 5px;
}

.jgk-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
    transition: width 0.3s ease;
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

/* Info Grid */
.jgk-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.jgk-info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.jgk-info-label {
    font-weight: 600;
    color: #7f8c8d;
    font-size: 13px;
}

.jgk-info-value {
    color: #2c3e50;
    font-size: 15px;
}

/* Coaches Grid */
.jgk-coaches-grid {
    display: grid;
    gap: 20px;
}

.jgk-coach-card {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border-radius: 12px;
    border-left: 4px solid #667eea;
}

.jgk-coach-avatar {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.jgk-coach-avatar img {
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
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    border: 2px solid white;
}

.jgk-coach-info h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 18px;
}

.jgk-coach-specialization,
.jgk-coach-contact {
    margin: 5px 0;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #7f8c8d;
}

.jgk-coach-since {
    margin: 10px 0 0 0;
    font-size: 12px;
    color: #95a5a6;
}

/* Parents Grid */
.jgk-parents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.jgk-parent-card {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #f5576c;
}

.jgk-parent-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.jgk-parent-header h4 {
    margin: 0;
    color: #2c3e50;
}

.jgk-badge-relationship {
    background: #f5576c;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
}

.jgk-parent-contacts p {
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.jgk-parent-contacts a {
    color: #667eea;
    text-decoration: none;
}

.jgk-parent-contacts a:hover {
    text-decoration: underline;
}

.jgk-parent-flags {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.jgk-flag {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.jgk-flag-primary {
    background: #d4edda;
    color: #155724;
}

.jgk-flag-emergency {
    background: #f8d7da;
    color: #721c24;
}

/* Primary Coach Widget */
.jgk-primary-coach-widget {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border: 2px solid #667eea30;
}

.jgk-primary-coach-card {
    text-align: center;
}

.jgk-coach-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 15px;
    border: 3px solid #667eea;
}

.jgk-coach-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.jgk-primary-coach-card h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 18px;
}

.jgk-coach-email {
    margin: 0 0 15px 0;
    font-size: 13px;
    color: #7f8c8d;
}

/* Quick Links */
.jgk-quick-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.jgk-quick-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    color: #2c3e50;
    text-decoration: none;
    transition: all 0.3s ease;
}

.jgk-quick-link:hover {
    background: #667eea;
    color: white;
    transform: translateX(5px);
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
    background: #f5576c;
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
    justify-content: center;
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

.jgk-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.jgk-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.jgk-btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.jgk-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
}

.jgk-btn-block {
    width: 100%;
}

/* Empty State & Notice */
.jgk-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #7f8c8d;
}

.jgk-empty-state .dashicons {
    font-size: 48px;
    opacity: 0.3;
}

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

.jgk-text-muted {
    color: #7f8c8d;
    font-style: italic;
}

/* Responsive */
@media (max-width: 1024px) {
    .jgk-dashboard-content {
        grid-template-columns: 1fr;
    }
    
    .jgk-info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .jgk-dashboard-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .jgk-member-info {
        flex-direction: column;
    }
    
    .jgk-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .jgk-parents-grid {
        grid-template-columns: 1fr;
    }
}
</style>
