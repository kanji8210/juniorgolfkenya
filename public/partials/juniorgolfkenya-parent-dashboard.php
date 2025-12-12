<?php
/**
 * Parent Dashboard - Frontend View
 *
 * Dashboard for parents managing multiple junior members
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
$parent_email = $current_user->user_email;

// Load parent dashboard class
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-parent-dashboard.php';

// Check if user is a parent
if (!JuniorGolfKenya_Parent_Dashboard::is_parent($parent_email)) {
    echo '<div class="jgk-notice jgk-notice-error">No children registered under this account. Please contact administrator if this is an error.</div>';
    return;
}

// Get parent's children and payment summary
$children = JuniorGolfKenya_Parent_Dashboard::get_parent_children($parent_email);
$payment_summary = JuniorGolfKenya_Parent_Dashboard::get_payment_summary($parent_email);
$parent_info = JuniorGolfKenya_Parent_Dashboard::get_parent_info($parent_email);
?>

<link rel="stylesheet" href="<?php echo JUNIORGOLFKENYA_PLUGIN_URL; ?>public/partials/css/juniorgolfkenya-member-dashboard.css">

<div class="jgk-member-dashboard jgk-parent-dashboard">
    <!-- Parent Header -->
    <div class="jgk-dashboard-header">
        <div class="jgk-member-info">
            <div class="jgk-member-avatar">
                <div class="jgk-avatar-placeholder">
                    <?php echo strtoupper(substr($parent_info->first_name, 0, 1)); ?>
                </div>
            </div>
            <div class="jgk-member-details">
                <h1>Welcome, <?php echo esc_html($parent_info->first_name); ?>!</h1>
                <p class="jgk-member-email"><?php echo esc_html($parent_email); ?></p>
                <div class="jgk-member-badges">
                    <span class="jgk-badge jgk-badge-type">
                        👨‍👩‍👧‍👦 Parent / Guardian
                    </span>
                    <span class="jgk-badge jgk-badge-junior">
                        <?php echo count($children); ?> <?php echo count($children) === 1 ? 'Child' : 'Children'; ?>
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

    <!-- Payment Alert for Pending Children -->
    <?php if ($payment_summary['pending_payments'] > 0): ?>
    <div class="jgk-payment-banner">
        <div class="jgk-payment-banner-content">
            <div class="jgk-payment-banner-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="jgk-payment-banner-text">
                <h3>⚠️ Payment Required</h3>
                <p><?php echo $payment_summary['pending_payments']; ?> 
                   <?php echo $payment_summary['pending_payments'] === 1 ? 'membership needs' : 'memberships need'; ?> 
                   payment to be activated.</p>
                <div class="jgk-payment-banner-amount">
                    <span class="jgk-amount-highlight">KES <?php echo number_format($payment_summary['pending_payments'] * 5000); ?></span>
                    <span class="jgk-period-highlight">Total Due</span>
                </div>
            </div>
            <div class="jgk-payment-banner-actions">
                <a href="#pending-payments" class="jgk-payment-cta-btn jgk-payment-mpesa">
                    <span class="dashicons dashicons-visibility"></span>
                    View Pending Payments
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Statistics -->
    <div class="jgk-stats-grid">
        <div class="jgk-stat-card jgk-card-primary">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo count($children); ?></h3>
                <p>Total Children</p>
            </div>
        </div>
        <div class="jgk-stat-card jgk-card-success">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo $payment_summary['active_memberships']; ?></h3>
                <p>Active Memberships</p>
            </div>
        </div>
        <div class="jgk-stat-card jgk-card-warning">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="jgk-stat-content">
                <h3><?php echo $payment_summary['pending_payments']; ?></h3>
                <p>Pending Payments</p>
            </div>
        </div>
        <div class="jgk-stat-card jgk-card-info">
            <div class="jgk-stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="jgk-stat-content">
                <h3>KES <?php echo number_format($payment_summary['total_paid']); ?></h3>
                <p>Total Paid</p>
            </div>
        </div>
    </div>

    <!-- Children List -->
    <div class="jgk-dashboard-content">
        <div class="jgk-dashboard-main">
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-admin-users"></span>
                        Your Children's Memberships
                    </h2>
                </div>
                <div class="jgk-children-grid">
                    <?php foreach ($children as $child): 
                        $child_stats = JuniorGolfKenya_Parent_Dashboard::get_child_stats($child->id);
                        $profile_image = JuniorGolfKenya_Member_Dashboard::get_profile_image($child->id, 'thumbnail');
                    ?>
                    <div class="jgk-child-card jgk-child-status-<?php echo esc_attr($child->status); ?>">
                        <div class="jgk-child-header">
                            <div class="jgk-child-avatar">
                                <?php if ($profile_image): ?>
                                    <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($child->first_name); ?>">
                                <?php else: ?>
                                    <div class="jgk-avatar-placeholder">
                                        <?php echo strtoupper(substr($child->first_name, 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="jgk-child-info">
                                <h3><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></h3>
                                <p class="jgk-child-membership">
                                    <?php echo esc_html($child->membership_number); ?>
                                </p>
                                <span class="jgk-status-badge jgk-status-<?php echo esc_attr($child->status); ?>">
                                    <?php echo ucfirst($child->status); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="jgk-child-details">
                            <div class="jgk-detail-item">
                                <span class="jgk-detail-label">
                                    <span class="dashicons dashicons-calendar"></span>
                                    Age:
                                </span>
                                <span class="jgk-detail-value">
                                    <?php 
                                    $age = date_diff(date_create($child->date_of_birth), date_create('today'))->y;
                                    echo $age . ' years';
                                    ?>
                                </span>
                            </div>
                            <div class="jgk-detail-item">
                                <span class="jgk-detail-label">
                                    <span class="dashicons dashicons-id-alt"></span>
                                    Gender:
                                </span>
                                <span class="jgk-detail-value">
                                    <?php echo ucfirst($child->gender); ?>
                                </span>
                            </div>
                            <div class="jgk-detail-item">
                                <span class="jgk-detail-label">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    Joined:
                                </span>
                                <span class="jgk-detail-value">
                                    <?php echo date('M Y', strtotime($child->joined_date)); ?>
                                </span>
                            </div>
                            <?php if ($child_stats['coach']): ?>
                            <div class="jgk-detail-item">
                                <span class="jgk-detail-label">
                                    <span class="dashicons dashicons-awards"></span>
                                    Coach:
                                </span>
                                <span class="jgk-detail-value">
                                    <?php echo esc_html($child_stats['coach']->display_name); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="jgk-child-actions">
                            <?php if ($child->status === 'approved'): ?>
                                <a href="#pay-<?php echo $child->id; ?>" class="jgk-button jgk-button-pay" data-member-id="<?php echo $child->id; ?>">
                                    <span class="dashicons dashicons-money-alt"></span>
                                    Pay Now (KES 5,000)
                                </a>
                            <?php elseif ($child->status === 'active'): ?>
                                <div class="jgk-payment-status-active">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    Membership Active
                                </div>
                                <?php if ($child->expiry_date): ?>
                                <small class="jgk-expiry-date">
                                    Expires: <?php echo date('d M Y', strtotime($child->expiry_date)); ?>
                                </small>
                                <?php endif; ?>
                            <?php elseif ($child->status === 'approved' || $child->status === 'pending'): ?>
                                <div class="jgk-payment-status-approved">
                                    <span class="dashicons dashicons-money-alt"></span>
                                    Payment Required
                                </div>
                                <small class="jgk-payment-note">Complete payment to activate membership</small>
                            <?php endif; ?>
                        </div>

                        <?php if ($child_stats['total_paid'] > 0): ?>
                        <div class="jgk-child-payment-history">
                            <small>
                                Total Paid: <strong>KES <?php echo number_format($child_stats['total_paid']); ?></strong>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pending Payments Section -->
            <?php if ($payment_summary['pending_payments'] > 0): ?>
            <div class="jgk-dashboard-section" id="pending-payments">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-money-alt"></span>
                        Pending Payments
                    </h2>
                </div>
                <div class="jgk-payment-card">
                    <div class="jgk-payment-info">
                        <h3>Complete Membership Payments</h3>
                        <p>The following memberships have been approved and require payment to be activated:</p>
                        
                        <?php foreach ($payment_summary['children_needing_payment'] as $child): ?>
                        <div class="jgk-pending-payment-item">
                            <div class="jgk-pending-info">
                                <strong><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></strong>
                                <span class="jgk-membership-number"><?php echo esc_html($child->membership_number); ?></span>
                            </div>
                            <div class="jgk-pending-amount">
                                <span class="jgk-amount">KES 5,000</span>
                                <span class="jgk-period">/ Year</span>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="jgk-payment-total">
                            <strong>Total Amount Due:</strong>
                            <span class="jgk-total-amount">KES <?php echo number_format($payment_summary['pending_payments'] * 5000); ?></span>
                        </div>
                    </div>

                    <div class="jgk-payment-methods">
                        <h4>Choose Payment Method</h4>
                        <div class="jgk-payment-buttons">
                            <?php if (class_exists('WooCommerce')): 
                                $membership_product_id = get_option('jgk_membership_product_id');
                                if ($membership_product_id):
                                    $add_to_cart_url = wc_get_cart_url() . '?add-to-cart=' . $membership_product_id;
                            ?>
                                <a href="<?php echo esc_url($add_to_cart_url); ?>" class="jgk-payment-btn jgk-payment-mpesa">
                                    <span class="dashicons dashicons-smartphone"></span>
                                    Pay with M-Pesa
                                </a>
                                <a href="<?php echo esc_url($add_to_cart_url); ?>" class="jgk-payment-btn jgk-payment-elipa">
                                    <span class="dashicons dashicons-credit-card"></span>
                                    Pay with Card / eLipa
                                </a>
                            <?php endif; else: ?>
                                <div class="jgk-payment-notice">
                                    <p><strong>⚠️ Payment system is being configured.</strong></p>
                                    <p>Please contact the administrator to complete payment.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="jgk-payment-note">
                            <small>
                                <span class="dashicons dashicons-info"></span>
                                All payments are processed securely. You will receive a confirmation email after payment is completed for each membership.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="jgk-dashboard-sidebar">
            <!-- Parent Information -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-admin-users"></span>
                        Parent Information
                    </h2>
                </div>
                <div class="jgk-info-grid">
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Name:</span>
                        <span class="jgk-info-value"><?php echo esc_html($parent_info->first_name . ' ' . $parent_info->last_name); ?></span>
                    </div>
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Email:</span>
                        <span class="jgk-info-value"><?php echo esc_html($parent_email); ?></span>
                    </div>
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Phone:</span>
                        <span class="jgk-info-value"><?php echo esc_html($parent_info->phone ?: 'Not provided'); ?></span>
                    </div>
                    <div class="jgk-info-item">
                        <span class="jgk-info-label">Mobile:</span>
                        <span class="jgk-info-value"><?php echo esc_html($parent_info->mobile ?: 'Not provided'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-admin-tools"></span>
                        Quick Actions
                    </h2>
                </div>
                <div style="padding: 20px;">
                    <a href="mailto:info@juniorgolfkenya.com" class="jgk-button" style="width: 100%; margin-bottom: 10px;">
                        <span class="dashicons dashicons-email"></span>
                        Contact Support
                    </a>
                    <a href="#" class="jgk-button" style="width: 100%; background: #6b7280;">
                        <span class="dashicons dashicons-download"></span>
                        Download Receipts
                    </a>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-sos"></span>
                        Need Help?
                    </h2>
                </div>
                <div style="padding: 20px;">
                    <p style="font-size: 14px; line-height: 1.6; margin-bottom: 15px;">
                        If you have any questions about your children's memberships or payments, please contact us:
                    </p>
                    <div style="font-size: 13px; line-height: 1.8;">
                        <p><strong>📧 Email:</strong><br>info@juniorgolfkenya.com</p>
                        <p><strong>📞 Phone:</strong><br>+254 XXX XXX XXX</p>
                        <p><strong>🕐 Hours:</strong><br>Mon-Fri: 9:00 AM - 5:00 PM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Parent Dashboard Specific Styles */
.jgk-parent-dashboard .jgk-children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    padding: 24px;
}

.jgk-child-card {
    background: #ffffff;
    border: 2px solid #e6e9ec;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
}

.jgk-child-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
    border-color: #0ea57a;
}

.jgk-child-status-active {
    border-left: 4px solid #10b981;
}

.jgk-child-status-approved {
    border-left: 4px solid #f59e0b;
}

.jgk-child-status-pending {
    border-left: 4px solid #6b7280;
}

.jgk-child-header {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.jgk-child-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.jgk-child-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.jgk-child-avatar .jgk-avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0ea57a 0%, #078a60 100%);
    color: white;
    font-size: 24px;
    font-weight: 700;
}

.jgk-child-info h3 {
    margin: 0 0 4px 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
}

.jgk-child-membership {
    margin: 0 0 8px 0;
    font-size: 0.875rem;
    color: #6b7280;
    font-family: monospace;
}

.jgk-status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.jgk-status-active {
    background: #d1fae5;
    color: #065f46;
}

.jgk-status-approved {
    background: #fef3c7;
    color: #92400e;
}

.jgk-status-pending {
    background: #f3f4f6;
    color: #374151;
}

.jgk-child-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
}

.jgk-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.jgk-detail-label {
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
}

.jgk-detail-label .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.jgk-detail-value {
    font-weight: 500;
    color: #0f172a;
}

.jgk-child-actions {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
}

.jgk-button-pay {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white !important;
    font-size: 14px !important;
    padding: 10px 16px !important;
}

.jgk-button-pay:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
}

.jgk-payment-status-active,
.jgk-payment-status-pending,
.jgk-payment-status-approved {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
}

.jgk-payment-status-active {
    background: #d1fae5;
    color: #065f46;
}

.jgk-payment-status-pending {
    background: #f3f4f6;
    color: #374151;
}

.jgk-payment-status-approved {
    background: #fef3c7;
    color: #92400e;
}

.jgk-payment-note {
    display: block;
    text-align: center;
    margin-top: 8px;
    color: #92400e;
    font-size: 12px;
    font-weight: 500;
}

.jgk-expiry-date {
    display: block;
    text-align: center;
    margin-top: 8px;
    color: #6b7280;
    font-size: 12px;
}

.jgk-child-payment-history {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
    text-align: center;
    font-size: 13px;
    color: #6b7280;
}

/* Pending Payments */
.jgk-pending-payment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #fef3c7;
    border-radius: 8px;
    margin-bottom: 12px;
}

.jgk-pending-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.jgk-membership-number {
    font-size: 12px;
    color: #92400e;
    font-family: monospace;
}

.jgk-pending-amount {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.jgk-pending-amount .jgk-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: #dc2626;
}

.jgk-pending-amount .jgk-period {
    font-size: 0.875rem;
    color: #92400e;
}

.jgk-payment-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #fee2e2;
    border-radius: 8px;
    margin-top: 16px;
    font-size: 1.125rem;
}

.jgk-total-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #dc2626;
}

@media (max-width: 768px) {
    .jgk-parent-dashboard .jgk-children-grid {
        grid-template-columns: 1fr;
    }
}
</style>
