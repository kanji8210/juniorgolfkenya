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
                <?php
                // Debug information for payment CTA selection
                if (WP_DEBUG) {
                    echo '<div class="jgk-debug-info" style="background: #f0f0f0; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 12px;">';
                    echo '<strong>Debug - Payment CTA Selection:</strong><br>';
                    echo 'WooCommerce Active: ' . (class_exists('WooCommerce') ? 'Yes' : 'No') . '<br>';
                    echo 'Membership Product ID: ' . (get_option('jgk_membership_product_id') ?: 'Not set') . '<br>';
                    echo 'Current User ID: ' . get_current_user_id() . '<br>';
                    echo 'Member Status: ' . $stats['member']->status . '<br>';
                    echo 'Product URL Generation: ' . (get_option('jgk_membership_product_id') ? 'Using existing product' : 'Will create new product') . '<br>';
                    echo '</div>';
                }

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
                            // Debug information for payment selection
                            if (WP_DEBUG) {
                                echo '<div class="jgk-debug-info" style="background: #f0f0f0; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 12px;">';
                                echo '<strong>Debug - Payment Selection:</strong><br>';
                                echo 'WooCommerce Active: ' . (class_exists('WooCommerce') ? 'Yes' : 'No') . '<br>';
                                echo 'Membership Product ID: ' . (get_option('jgk_membership_product_id') ?: 'Not set') . '<br>';
                                echo 'Current User ID: ' . get_current_user_id() . '<br>';
                                echo 'Member Status: ' . $stats['member']->status . '<br>';
                                echo 'Cart URL: ' . wc_get_cart_url() . '<br>';

                                // Enhanced debug: Show recent WooCommerce orders for membership product
                                if (class_exists('WooCommerce') && get_option('jgk_membership_product_id')) {
                                    global $wpdb;
                                    $membership_product_id = get_option('jgk_membership_product_id');
                                    $user_id = get_current_user_id();

                                    // Get recent orders containing the membership product
                                    $recent_orders = $wpdb->get_results($wpdb->prepare("
                                        SELECT o.ID, o.post_date, o.post_status, oi.order_item_name
                                        FROM {$wpdb->posts} o
                                        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
                                        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
                                        WHERE o.post_type = 'shop_order'
                                        AND o.post_author = %d
                                        AND oim.meta_key = '_product_id'
                                        AND oim.meta_value = %d
                                        ORDER BY o.post_date DESC
                                        LIMIT 3
                                    ", $user_id, $membership_product_id));

                                    if (!empty($recent_orders)) {
                                        echo '<br><strong>Recent Membership Orders:</strong><br>';
                                        foreach ($recent_orders as $order) {
                                            $order_obj = wc_get_order($order->ID);
                                            $payment_method = $order_obj->get_payment_method_title();
                                            $status = $order_obj->get_status();
                                            echo "Order #{$order->ID} ({$order->post_date}): {$status} - {$payment_method}<br>";
                                        }
                                    } else {
                                        echo '<br><strong>Recent Membership Orders:</strong> None found<br>';
                                    }

                                    // Check for iPay/eLipa payment processing
                                    $ipay_logs = $wpdb->get_results($wpdb->prepare("
                                        SELECT meta_value, meta_key
                                        FROM {$wpdb->postmeta}
                                        WHERE post_id IN (
                                            SELECT ID FROM {$wpdb->posts}
                                            WHERE post_type = 'shop_order'
                                            AND post_author = %d
                                        )
                                        AND meta_key LIKE %s
                                        ORDER BY meta_id DESC
                                        LIMIT 5
                                    ", $user_id, 'jgk_ipay_%'));

                                    if (!empty($ipay_logs)) {
                                        echo '<br><strong>iPay/eLipa Processing:</strong><br>';
                                        foreach ($ipay_logs as $log) {
                                            $key = str_replace('jgk_ipay_', '', $log->meta_key);
                                            echo "{$key}: {$log->meta_value}<br>";
                                        }
                                    } else {
                                        echo '<br><strong>iPay/eLipa Processing:</strong> No recent activity<br>';
                                    }
                                }

                                echo '</div>';
                            }

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
                                    
                                    if (WP_DEBUG) {
                                        echo '<div class="jgk-debug-info" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 12px;">';
                                        echo '<strong>Debug:</strong> New membership product created with ID: ' . $membership_product_id;
                                        echo '</div>';
                                    }
                                }
                                
                                $product = wc_get_product($membership_product_id);
                                if ($product) {
                                    $add_to_cart_url = wc_get_cart_url() . '?add-to-cart=' . $membership_product_id;
                                    
                                    if (WP_DEBUG) {
                                        echo '<div class="jgk-debug-info" style="background: #d1ecf1; color: #0c5460; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 12px;">';
                                        echo '<strong>Debug - Product Info:</strong><br>';
                                        echo 'Product Name: ' . $product->get_name() . '<br>';
                                        echo 'Product Price: KSh ' . $product->get_price() . '<br>';
                                        echo 'Add to Cart URL: ' . $add_to_cart_url . '<br>';
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
                                } else {
                                    if (WP_DEBUG) {
                                        echo '<div class="jgk-debug-info" style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 12px;">';
                                        echo '<strong>Debug Error:</strong> Could not retrieve product with ID: ' . $membership_product_id;
                                        echo '</div>';
                                    }
                                }
                            } else {
                                // Fallback if WooCommerce is not active
                                if (WP_DEBUG) {
                                    echo '<div class="jgk-debug-info" style="background: #fff3cd; color: #856404; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 12px;">';
                                    echo '<strong>Debug Warning:</strong> WooCommerce is not active or installed.';
                                    echo '</div>';
                                }
                                
                                ?>
                                <div class="jgk-payment-notice">
                                    <p><strong>Payment system is being configured.</strong></p>
                                    <p>Please contact the administrator to complete your payment.</p>
                                    <p><em>Phone: +254 XXX XXX XXX</em></p>
                                </div>
                                <?php
                            }
                            ?>

                            <?php if (WP_DEBUG && class_exists('WooCommerce')): ?>
                            <div class="jgk-debug-info" style="background: #e9ecef; padding: 15px; margin-top: 15px; border-radius: 5px; font-size: 12px; border-left: 4px solid #007cba;">
                                <strong>🔍 Payment Verification Debug:</strong><br><br>

                                <?php
                                $membership_product_id = get_option('jgk_membership_product_id');
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
                                echo '• Membership Product ID: ' . ($membership_product_id ?: 'Not set') . '<br>';
                                echo '• WooCommerce Active: Yes<br>';
                                echo '• Debug Mode: Enabled<br>';
                                echo '• Current Time: ' . current_time('mysql') . '<br>';
                                ?>
                            </div>
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
                //link edit profile to user portal of current user
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

.jgk-badge-approved {
    background: #17a2b8;
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

/* Payment Section */
.jgk-payment-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.jgk-payment-info h3 {
    margin: 0 0 16px 0;
    font-size: 24px;
    font-weight: 700;
}

.jgk-payment-info > p {
    margin: 0 0 24px 0;
    font-size: 16px;
    line-height: 1.6;
    opacity: 0.9;
}

.jgk-payment-amount {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 16px;
}

.jgk-amount {
    font-size: 32px;
    font-weight: 700;
    color: #ffd700;
}

.jgk-period {
    font-size: 18px;
    font-weight: 500;
    opacity: 0.8;
}

.jgk-payment-description {
    font-size: 14px;
    opacity: 0.8;
    line-height: 1.5;
}

.jgk-payment-methods h4 {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
}

.jgk-payment-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.jgk-payment-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px 24px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.jgk-payment-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.jgk-payment-mpesa {
    border-color: #00c853;
    background: rgba(0, 200, 83, 0.1);
}

.jgk-payment-mpesa:hover {
    background: rgba(0, 200, 83, 0.2);
    border-color: #00c853;
}

.jgk-payment-elipa {
    border-color: #1976d2;
    background: rgba(25, 118, 210, 0.1);
}

.jgk-payment-elipa:hover {
    background: rgba(25, 118, 210, 0.2);
    border-color: #1976d2;
}

.jgk-payment-notice {
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    text-align: center;
    color: rgba(255, 255, 255, 0.9);
    font-size: 14px;
    backdrop-filter: blur(10px);
}

.jgk-payment-note {
    margin-top: 20px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
}

.jgk-payment-note small {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12px;
    line-height: 1.5;
    opacity: 0.8;
}

/* Responsive Payment */
@media (max-width: 768px) {
    .jgk-payment-card {
        grid-template-columns: 1fr;
        gap: 24px;
        padding: 24px;
    }
    
    .jgk-payment-info h3 {
        font-size: 20px;
    }
    
    .jgk-amount {
        font-size: 28px;
    }
    
    .jgk-payment-buttons {
        flex-direction: row;
        gap: 8px;
    }
    
    .jgk-payment-btn {
        flex: 1;
        padding: 12px 16px;
        font-size: 14px;
    }
}

/* Payment Banner */
.jgk-payment-banner {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 16px;
    margin: 30px 0;
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
    overflow: hidden;
    animation: paymentPulse 3s ease-in-out infinite;
}

@keyframes paymentPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.jgk-payment-banner-content {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 24px;
    align-items: center;
    padding: 32px 40px;
}

.jgk-payment-banner-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: white;
    backdrop-filter: blur(10px);
}

.jgk-payment-banner-text h3 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 700;
    color: white;
}

.jgk-payment-banner-text p {
    margin: 0 0 16px 0;
    font-size: 16px;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.5;
}

.jgk-payment-banner-amount {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.jgk-amount-highlight {
    font-size: 32px;
    font-weight: 700;
    color: #ffd700;
}

.jgk-period-highlight {
    font-size: 18px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
}

.jgk-payment-banner-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.jgk-payment-cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    min-width: 160px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.jgk-payment-cta-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    color: white;
    text-decoration: none;
}

.jgk-payment-mpesa {
    border-color: #00c853;
    background: rgba(0, 200, 83, 0.3);
    box-shadow: 0 4px 12px rgba(0, 200, 83, 0.2);
}

.jgk-payment-mpesa:hover {
    background: rgba(0, 200, 83, 0.4);
    border-color: #00c853;
    box-shadow: 0 6px 20px rgba(0, 200, 83, 0.3);
}

.jgk-payment-elipa {
    border-color: #1976d2;
    background: rgba(25, 118, 210, 0.3);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
}

.jgk-payment-elipa:hover {
    background: rgba(25, 118, 210, 0.4);
    border-color: #1976d2;
    box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
}

/* Responsive Payment Banner */
@media (max-width: 768px) {
    .jgk-payment-banner-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 20px;
    }
    
    .jgk-payment-banner-actions {
        flex-direction: row;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .jgk-payment-cta-btn {
        min-width: 140px;
    }
}

/* Success Banner */
.jgk-success-banner {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 16px;
    margin: 30px 0;
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
    overflow: hidden;
}

.jgk-success-banner-content {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 24px;
    align-items: center;
    padding: 32px 40px;
}

.jgk-success-banner-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: white;
    backdrop-filter: blur(10px);
}

.jgk-success-banner-text h3 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 700;
    color: white;
}

.jgk-success-banner-text p {
    margin: 0 0 16px 0;
    font-size: 16px;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.5;
}

.jgk-success-features {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.jgk-feature-tag {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

/* Responsive Success Banner */
@media (max-width: 768px) {
    .jgk-success-banner-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 20px;
    }
    
    .jgk-success-features {
        justify-content: center;
    }
}
</style>
