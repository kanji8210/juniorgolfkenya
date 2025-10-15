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

    <!-- Payment Banner for Approved Members -->
    <?php if ($stats['member']->status === 'approved'): ?>
    <div class="jgk-payment-banner">
        <div class="jgk-payment-banner-content">
            <div class="jgk-payment-banner-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="jgk-payment-banner-text">
                <h3>🎉 Your Membership is Approved!</h3>
                <p>Complete your payment to activate your membership and start enjoying all the benefits.</p>
                <div class="jgk-payment-banner-amount">
                    <span class="jgk-amount-highlight">KES 5,000</span>
                    <span class="jgk-period-highlight">/ Year</span>
                </div>
            </div>
            <div class="jgk-payment-banner-actions">

                if (class_exists('WooCommerce')) {
                    $membership_product_id = get_option('jgk_membership_product_id');
                    if ($membership_product_id) {
                        $product_url = get_permalink($membership_product_id);
                        $add_to_cart_url = wc_get_cart_url() . '?add-to-cart=' . $membership_product_id;
                        ?>
                        <a href="<?php echo esc_url($product_url); ?>" class="jgk-payment-cta-btn jgk-payment-mpesa">
                            <span class="dashicons dashicons-smartphone"></span>
                            Pay with M-Pesa
                        </a>
                        <a href="<?php echo esc_url($product_url); ?>" class="jgk-payment-cta-btn jgk-payment-elipa">
                            <span class="dashicons dashicons-credit-card"></span>
                            Pay with eLipa
                        </a>
                        <?php
                    } else {
                        ?>
                        <p class="jgk-payment-notice">Membership product not configured. Please contact administrator.</p>
                        <?php
                    }
                } else {
                    ?>
                    <p class="jgk-payment-notice">WooCommerce not installed or activated.</p>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Success Banner for Active Members -->
    <?php if ($stats['member']->status === 'active'): ?>
    <div class="jgk-success-banner">
        <div class="jgk-success-banner-content">
            <div class="jgk-success-banner-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="jgk-success-banner-text">
                <h3>🎉 Welcome to Junior Golf Kenya!</h3>
                <p>Your membership is now fully active. You can start enjoying all the benefits of being a Junior Golf Kenya member.</p>
                <div class="jgk-success-features">
                    <span class="jgk-feature-tag">✓ Professional Coaching</span>
                    <span class="jgk-feature-tag">✓ Tournament Access</span>
                    <span class="jgk-feature-tag">✓ Training Facilities</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                <span class="dashicons dashicons-admin-users"></span>
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

            <!-- Membership Payment Section (for approved members) -->
            <?php if ($stats['member']->status === 'approved'): ?>
            <div class="jgk-dashboard-section">
                <div class="jgk-section-header">
                    <h2>
                        <span class="dashicons dashicons-money-alt"></span>
                        Membership Payment
                    </h2>
                </div>
                <div class="jgk-payment-card">
                    <div class="jgk-payment-info">
                        <h3>Complete Your Membership</h3>
                        <p>Your membership application has been approved! Please complete your payment to activate your membership.</p>
                        <div class="jgk-payment-amount">
                            <span class="jgk-amount">KES 5,000</span>
                            <span class="jgk-period">/ Year</span>
                        </div>
                        <p class="jgk-payment-description">
                            Annual membership fee includes access to coaching, tournaments, and exclusive training facilities.
                        </p>
                    </div>
                    <div class="jgk-payment-methods">
                        <h4>Choose Payment Method</h4>
                        <div class="jgk-payment-buttons">
                            <?php
                            // Check if WooCommerce is active
                            if (class_exists('WooCommerce')) {
                                // Get or create membership product
                                $membership_product_id = get_option('jgk_membership_product_id');
                                if (!$membership_product_id) {
                                    // Create product if it doesn't exist
                                    $product = new WC_Product_Simple();
                                    $product->set_name('Junior Golf Kenya Annual Membership');
                                    $product->set_regular_price(5000);
                                    $product->set_description('Annual membership fee for Junior Golf Kenya');
                                    $product->set_short_description('Complete your membership payment');
                                    $product->set_sku('JGK-MEMBERSHIP-2025');
                                    $product->set_virtual(true);
                                    $product->set_downloadable(false);
                                    $product->set_stock_status('instock');
                                    $product->set_catalog_visibility('hidden');
                                    $product->save();
                                    
                                    $membership_product_id = $product->get_id();
                                    update_option('jgk_membership_product_id', $membership_product_id);
                                }
                                
                                $product = wc_get_product($membership_product_id);
                                        echo 'Product Status: ' . $product->get_status() . '<br>';
                                        echo '</div>';
                                    }
                                    
                                    ?>
                                    <a href="<?php echo esc_url($add_to_cart_url); ?>" class="jgk-payment-btn jgk-payment-mpesa">
                                        <span class="dashicons dashicons-smartphone"></span>
                                        Pay with M-Pesa
                                    </a>
                                    <a href="<?php echo esc_url($add_to_cart_url); ?>" class="jgk-payment-btn jgk-payment-elipa">
                                        <span class="dashicons dashicons-credit-card"></span>
                                        Pay with eLipa
                                    </a>
                                    <?php
                                }
                            } else {
                                // Fallback if WooCommerce is not active
                                ?>
                                <div class="jgk-payment-notice">
                                    <p><strong>Payment system is being configured.</strong></p>
                                    <p>Please contact the administrator to complete your payment.</p>
                                    <p><em>Phone: +254 XXX XXX XXX</em></p>
                                </div>
                                <?php
                            }
                            ?>

                        </div>
                                $user_id = get_current_user_id();
                                global $wpdb;

                                // Check membership payment verification
                                $member_payments = $wpdb->get_results($wpdb->prepare("
                                    SELECT p.*, m.first_name, m.last_name, m.status as member_status
                                    FROM {$wpdb->prefix}jgk_payments p
                                    LEFT JOIN {$wpdb->prefix}jgk_members m ON p.member_id = m.id
                                    WHERE p.member_id = %d
                                    ORDER BY p.created_at DESC
                                    LIMIT 3
                                ", $user_id));

                                echo '<strong>Manual Payments:</strong><br>';
                                if (!empty($member_payments)) {
                                    foreach ($member_payments as $payment) {
                                        echo "• Payment #{$payment->id}: KSh {$payment->amount} ({$payment->payment_method}) - {$payment->status}<br>";
                                    }
                                } else {
                                    echo '• No manual payments found<br>';
                                }

                                echo '<br><strong>WooCommerce Orders:</strong><br>';
                                if ($membership_product_id) {
                                    $wc_orders = $wpdb->get_results($wpdb->prepare("
                                        SELECT o.ID, o.post_date, o.post_status, pm.meta_value as total
                                        FROM {$wpdb->posts} o
                                        INNER JOIN {$wpdb->postmeta} pm ON o.ID = pm.post_id AND pm.meta_key = '_order_total'
                                        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
                                        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
                                        WHERE o.post_type = 'shop_order'
                                        AND o.post_author = %d
                                        AND oim.meta_key = '_product_id'
                                        AND oim.meta_value = %d
                                        ORDER BY o.post_date DESC
                                        LIMIT 3
                                    ", $user_id, $membership_product_id));

                                    if (!empty($wc_orders)) {
                                        foreach ($wc_orders as $order) {
                                            $order_obj = wc_get_order($order->ID);
                                            $payment_method = $order_obj->get_payment_method_title();
                                            echo "• Order #{$order->ID}: KSh {$order->total} ({$payment_method}) - {$order->post_status}<br>";
                                        }
                                    } else {
                                        echo '• No WooCommerce orders found for membership product<br>';
                                    }
                                } else {
                                    echo '• Membership product not configured<br>';
                                }

                                // Check for payment processing errors
                                echo '<br><strong>Payment Processing Status:</strong><br>';
                                $error_logs = get_transient('jgk_payment_errors_' . $user_id);
                                if ($error_logs) {
                                    echo '• Recent errors: ' . implode(', ', $error_logs) . '<br>';
                                } else {
                                    echo '• No recent payment errors<br>';
                                }

                                // Check iPay/eLipa processing status
                                $ipay_status = get_transient('jgk_ipay_status_' . $user_id);
                                if ($ipay_status) {
                                    echo '• iPay/eLipa Status: ' . $ipay_status . '<br>';
                                } else {
                                    echo '• No active iPay/eLipa processing<br>';
                                }

                                echo '<br><strong>System Configuration:</strong><br>';
                            <?php endif; ?>

                        </div>
                        <div class="jgk-payment-note">
                            <small>
                                <span class="dashicons dashicons-info"></span>
                                Payments are processed securely through our payment gateway. You will receive a confirmation email once payment is completed.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                    <?php 
                    $portal_page_id = get_option('jgk_page_member_portal');
                    $portal_url = $portal_page_id ? get_permalink($portal_page_id) : home_url('/member-portal');
                    ?>
                    <a href="<?php echo esc_url($portal_url . '#edit-profile'); ?>" class="jgk-quick-link">
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