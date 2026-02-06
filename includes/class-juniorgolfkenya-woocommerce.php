<?php
/**
 * WooCommerce Integration for Junior Golf Kenya
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

/**
 * WooCommerce Integration class.
 *
 * Handles WooCommerce order processing and member status updates.
 */
class JuniorGolfKenya_WooCommerce {

    /**
     * Initialize WooCommerce integration
     *
     * @since    1.0.0
     */
    public static function init() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Hook into WooCommerce order completion
        add_action('woocommerce_order_status_completed', array(__CLASS__, 'handle_order_completion'), 10, 1);

        // Hook into order status changes
        add_action('woocommerce_order_status_changed', array(__CLASS__, 'handle_order_status_change'), 10, 3);

        // Add custom order meta for JGK member linking
        add_action('woocommerce_checkout_create_order', array(__CLASS__, 'add_order_meta'), 10, 1);

        // Redirect membership payments to member dashboard after checkout
        add_filter('woocommerce_get_checkout_order_received_url', array(__CLASS__, 'filter_order_received_url'), 10, 2);

        // Ensure membership fee is authoritative at checkout
        add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'sync_cart_membership_price'), 10, 1);
    }

    /**
     * Ensure membership product price matches settings
     *
     * @since    1.0.0
     * @param    int|null $product_id Optional product ID override
     * @return   int|null
     */
    public static function ensure_membership_product_price($product_id = null) {
        if (!class_exists('WooCommerce')) {
            return null;
        }

        $membership_product_id = $product_id ?: get_option('jgk_membership_product_id', 0);
        if (!$membership_product_id) {
            return null;
        }

        $product = wc_get_product($membership_product_id);
        if (!$product) {
            return null;
        }

        $fee = JuniorGolfKenya_Settings_Helper::get_default_membership_fee();
        $current_price = (float) $product->get_regular_price();

        if ($fee > 0 && $current_price !== (float) $fee) {
            $product->set_regular_price($fee);
            $product->set_price($fee);
            $product->save();
        }

        return $membership_product_id;
    }

    /**
     * Sync membership product price in the cart to match settings
     *
     * @since    1.0.0
     * @param    WC_Cart $cart WooCommerce cart instance
     */
    public static function sync_cart_membership_price($cart) {
        if (!class_exists('WooCommerce') || is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!$cart || $cart->is_empty()) {
            return;
        }

        $membership_product_id = get_option('jgk_membership_product_id', 0);
        if (!$membership_product_id) {
            return;
        }

        $fee = JuniorGolfKenya_Settings_Helper::get_default_membership_fee();
        if ($fee <= 0) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (empty($cart_item['data'])) {
                continue;
            }

            $product = $cart_item['data'];
            if ((int) $product->get_id() !== (int) $membership_product_id) {
                continue;
            }

            $product->set_price($fee);
        }
    }

    /**
     * Redirect membership orders to the member dashboard after checkout
     *
     * @since    1.0.0
     * @param    string   $url    Default order received URL
     * @param    mixed    $order  WooCommerce order object or ID
     * @return   string
     */
    public static function filter_order_received_url($url, $order) {
        $order = is_a($order, 'WC_Order') ? $order : wc_get_order($order);
        if (!$order) {
            return $url;
        }

        if (!self::order_contains_membership_product($order)) {
            return $url;
        }

        $status = $order->get_status();
        if (!in_array($status, array('processing', 'completed'), true)) {
            return $url;
        }

        $dashboard_page_id = get_option('jgk_page_member_dashboard');
        return $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/member-dashboard');
    }

    /**
     * Handle WooCommerce order completion
     *
     * @since    1.0.0
     * @param    int    $order_id    WooCommerce Order ID
     */
    public static function handle_order_completion($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("JGK PAYMENT DEBUG: ❌ Order {$order_id} not found");
            return;
        }

        // Enhanced iPay/eLipa Debug Logging
        $payment_method = $order->get_payment_method();
        $payment_method_title = $order->get_payment_method_title();
        $transaction_id = $order->get_transaction_id();
        $order_total = $order->get_total();
        $customer_id = $order->get_customer_id();

        error_log("JGK IPAY DEBUG: ==========================================");
        error_log("JGK IPAY DEBUG: 🎯 ORDER COMPLETION DETECTED");
        error_log("JGK IPAY DEBUG: Order ID: {$order_id}");
        error_log("JGK IPAY DEBUG: Payment Method: {$payment_method} ({$payment_method_title})");
        error_log("JGK IPAY DEBUG: Transaction ID: " . ($transaction_id ?: 'Not set'));
        error_log("JGK IPAY DEBUG: Order Total: {$order_total} KES");
        error_log("JGK IPAY DEBUG: Customer ID: " . ($customer_id ?: 'Guest checkout'));
        error_log("JGK IPAY DEBUG: Order Status: " . $order->get_status());
        error_log("JGK IPAY DEBUG: Is iPay/eLipa Payment: " . (self::is_ipay_payment($payment_method) ? 'YES' : 'NO'));

        // Check if this order contains membership products
        $has_membership_product = self::order_contains_membership_product($order);

        if ($has_membership_product) {
            error_log("JGK IPAY DEBUG: ✅ Order contains membership product - processing payment");
            self::process_membership_payment($order);
        } else {
            error_log("JGK IPAY DEBUG: ❌ Order does not contain membership product - skipping JGK processing");
        }
        error_log("JGK IPAY DEBUG: ==========================================");
    }

    /**
     * Check if payment method is iPay/eLipa
     *
     * @since    1.0.0
     * @param    string    $payment_method    WooCommerce payment method
     * @return   bool
     */
    private static function is_ipay_payment($payment_method) {
        $ipay_methods = array('ipay', 'elipa', 'mpesa', 'airtel', 'card'); // Common iPay/eLipa methods
        return in_array(strtolower($payment_method), $ipay_methods);
    }

    /**
     * Handle order status changes
     *
     * @since    1.0.0
     * @param    int       $order_id    Order ID
     * @param    string    $old_status  Old status
     * @param    string    $new_status  New status
     */
    public static function handle_order_status_change($order_id, $old_status, $new_status) {
        // Enhanced iPay/eLipa Debug Logging for status changes
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("JGK IPAY DEBUG: ❌ Order {$order_id} not found during status change");
            return;
        }

        $payment_method = $order->get_payment_method();
        $is_ipay = self::is_ipay_payment($payment_method);

        error_log("JGK IPAY DEBUG: 🔄 STATUS CHANGE: Order {$order_id} | {$old_status} → {$new_status} | Payment: {$payment_method} | iPay: " . ($is_ipay ? 'YES' : 'NO'));

        if (self::order_contains_membership_product($order)) {
            error_log("JGK IPAY DEBUG: 📋 Membership order {$order_id} status changed from {$old_status} to {$new_status}");

            // If order is completed or processing, process the membership
            if (in_array($new_status, array('completed', 'processing'), true)) {
                error_log("JGK IPAY DEBUG: ✅ Processing completed membership payment for order {$order_id}");
                self::process_membership_payment($order);
            } elseif ($new_status === 'failed') {
                error_log("JGK IPAY DEBUG: ❌ Membership payment failed for order {$order_id}");
            } elseif ($new_status === 'cancelled') {
                error_log("JGK IPAY DEBUG: 🚫 Membership order cancelled: {$order_id}");
            }
        }
    }

    /**
     * Check if order contains membership product
     *
     * @since    1.0.0
     * @param    WC_Order    $order    WooCommerce Order object
     * @return   bool
     */
    private static function order_contains_membership_product($order) {
        $membership_product_id = get_option('jgk_membership_product_id', 0);

        // Advanced iPay/eLipa Payment Debug Logging
        error_log("JGK IPAY DEBUG: === MEMBERSHIP PRODUCT CHECK ===");
        error_log("JGK IPAY DEBUG: Order ID: " . $order->get_id());
        error_log("JGK IPAY DEBUG: Membership Product ID from settings: " . ($membership_product_id ?: 'NOT SET'));

        if (!$membership_product_id) {
            error_log("JGK IPAY DEBUG: ❌ No membership product ID configured in plugin settings");
            error_log("JGK IPAY DEBUG: === PRODUCT CHECK FAILED - NO PRODUCT ID ===");
            return false;
        }

        error_log("JGK IPAY DEBUG: ✅ Membership product ID is configured: {$membership_product_id}");

        // Check if product exists in WooCommerce
        $product = wc_get_product($membership_product_id);
        if (!$product) {
            error_log("JGK IPAY DEBUG: ❌ Membership product ID {$membership_product_id} does not exist in WooCommerce");
            error_log("JGK IPAY DEBUG: === PRODUCT CHECK FAILED - PRODUCT NOT FOUND ===");
            return false;
        }

        error_log("JGK IPAY DEBUG: ✅ Membership product exists: '" . $product->get_name() . "' (ID: {$membership_product_id})");

        // Count total payments for this membership product
        global $wpdb;
        $payments_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}jgk_payments
            WHERE membership_id IS NOT NULL AND status = 'completed'
        "));
        error_log("JGK IPAY DEBUG: 📊 Total completed membership payments: " . ($payments_count ?: 0));

        // Count payments specifically for this product via WooCommerce orders
        $wc_payments_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT o.ID)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} o ON pm.post_id = o.ID
            WHERE pm.meta_key = '_product_id'
            AND pm.meta_value = %s
            AND o.post_type = 'shop_order'
            AND o.post_status IN ('wc-completed', 'wc-processing')
        ", $membership_product_id));
        error_log("JGK IPAY DEBUG: 🛒 WooCommerce orders with this membership product: " . ($wc_payments_count ?: 0));

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            error_log("JGK IPAY DEBUG: Checking order item - Product ID: {$product_id}, Quantity: " . $item->get_quantity() . ", Name: '" . $item->get_name() . "'");

            if ($product_id == $membership_product_id) {
                error_log("JGK IPAY DEBUG: ✅ MATCH FOUND - Order contains membership product!");
                error_log("JGK IPAY DEBUG: === PRODUCT CHECK SUCCESSFUL ===");
                return true;
            }
        }

        error_log("JGK IPAY DEBUG: ❌ No membership product found in order items");
        error_log("JGK IPAY DEBUG: === PRODUCT CHECK FAILED - PRODUCT NOT IN ORDER ===");
        return false;
    }

    /**
     * Process membership payment from WooCommerce order
     *
     * @since    1.0.0
     * @param    WC_Order    $order    WooCommerce Order object
     */
    private static function process_membership_payment($order) {
        $membership_product_id = get_option('jgk_membership_product_id', 0);
        $customer_id = $order->get_customer_id();
        $payment_method = $order->get_payment_method();
        $transaction_id = $order->get_transaction_id();
        $order_id = $order->get_id();

        error_log("JGK IPAY DEBUG: === MEMBERSHIP PAYMENT PROCESSING STARTED ===");
        error_log("JGK IPAY DEBUG: Processing order ID: " . $order_id . " for customer ID: " . ($customer_id ?: 'GUEST'));
        error_log("JGK IPAY DEBUG: Membership Product ID: " . ($membership_product_id ?: 'NOT SET'));
        error_log("JGK IPAY DEBUG: Payment Method: {$payment_method} | Transaction ID: " . ($transaction_id ?: 'Not set'));
        error_log("JGK IPAY DEBUG: iPay/eLipa Payment: " . (self::is_ipay_payment($payment_method) ? 'YES' : 'NO'));

        // Store iPay processing status for debug display
        if (self::is_ipay_payment($payment_method)) {
            set_transient('jgk_ipay_status_' . $customer_id, 'Processing iPay/eLipa payment for order ' . $order->get_id(), HOUR_IN_SECONDS);
        }

        if (!$membership_product_id || !$customer_id) {
            error_log("JGK IPAY DEBUG: ❌ Payment processing failed - Missing product ID or customer ID");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING ABORTED ===");
            error_log("JGK: Missing membership product ID or customer ID for order {$order_id}");

            // Store error for debug display
            $errors = get_transient('jgk_payment_errors_' . $customer_id) ?: array();
            $errors[] = 'Missing membership product ID or customer ID';
            set_transient('jgk_payment_errors_' . $customer_id, array_slice($errors, -5), HOUR_IN_SECONDS);

            return;
        }

        // Get member by user ID
        $member = JuniorGolfKenya_Database::get_member_by_user_id($customer_id);

        if (!$member) {
            error_log("JGK IPAY DEBUG: ❌ No member found for customer ID {$customer_id}");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING ABORTED - NO MEMBER ===");

            // Store error for debug display
            $errors = get_transient('jgk_payment_errors_' . $customer_id) ?: array();
            $errors[] = 'No member record found for user';
            set_transient('jgk_payment_errors_' . $customer_id, array_slice($errors, -5), HOUR_IN_SECONDS);

            error_log("JGK: No member found for user ID {$customer_id} in order {$order_id}");
            return;
        }

        error_log("JGK IPAY DEBUG: ✅ Member found - ID: {$member->id}, Name: {$member->first_name} {$member->last_name}, Status: {$member->status}");

        // Avoid double-processing the same order
        global $wpdb;
        $existing_payment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}jgk_payments WHERE order_id = %d LIMIT 1",
            $order_id
        ));

        if ($existing_payment_id) {
            if ($member->status !== 'active') {
                $user_manager = new JuniorGolfKenya_User_Manager();
                $user_manager->update_member_status($member->id, 'active');
            }

            error_log("JGK IPAY DEBUG: ✅ Order {$order_id} already recorded (Payment ID: {$existing_payment_id})");
            return;
        }

        // Allow payment processing for pending/approved statuses
        $allowed_statuses = array('approved', 'pending', 'pending_approval', 'active');
        if (!in_array($member->status, $allowed_statuses, true)) {
            error_log("JGK IPAY DEBUG: ❌ Member status is '{$member->status}' - not eligible for payment processing");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING ABORTED - MEMBER STATUS NOT ELIGIBLE ===");

            // Store error for debug display
            $errors = get_transient('jgk_payment_errors_' . $customer_id) ?: array();
            $errors[] = "Member status is '{$member->status}' - not eligible for payment processing";
            set_transient('jgk_payment_errors_' . $customer_id, array_slice($errors, -5), HOUR_IN_SECONDS);

            error_log("JGK: Member {$member->id} status not eligible for payment processing in order {$order_id}");
            return;
        }

        error_log("JGK IPAY DEBUG: ✅ Member is approved for payment processing");

        // Calculate membership amount from order
        $membership_amount = 0;
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id == $membership_product_id) {
                $membership_amount = $item->get_total();
                error_log("JGK IPAY DEBUG: 📦 Found membership product in order - Amount: {$membership_amount} KES");
                break;
            }
        }

        if ($membership_amount <= 0) {
            error_log("JGK IPAY DEBUG: ❌ Invalid membership amount: {$membership_amount}");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING ABORTED - INVALID AMOUNT ===");

            // Store error for debug display
            $errors = get_transient('jgk_payment_errors_' . $customer_id) ?: array();
            $errors[] = 'Invalid membership amount: ' . $membership_amount;
            set_transient('jgk_payment_errors_' . $customer_id, array_slice($errors, -5), HOUR_IN_SECONDS);

            error_log("JGK: Invalid membership amount for order {$order_id}");
            return;
        }

        error_log("JGK IPAY DEBUG: 💰 Valid membership amount: {$membership_amount} KES");

        // Record the payment in JGK system
        $payment_id = JuniorGolfKenya_Database::record_payment(
            $member->id,
            $order_id,
            $membership_amount,
            $order->get_payment_method_title(),
            'completed',
            $order->get_transaction_id(),
            array(
                'payment_type' => 'membership',
                'payment_gateway' => 'woocommerce',
                'currency' => $order->get_currency(),
                'notes' => 'WooCommerce order #' . $order_id,
                'payment_date' => ($order->get_date_paid() ? $order->get_date_paid()->date_i18n('Y-m-d H:i:s') : $order->get_date_created()->date_i18n('Y-m-d H:i:s'))
            )
        );

        if (!$payment_id) {
            error_log("JGK IPAY DEBUG: ❌ Failed to record payment in JGK database");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING FAILED ===");

            // Store error for debug display
            $errors = get_transient('jgk_payment_errors_' . $customer_id) ?: array();
            $errors[] = 'Failed to record payment in database';
            set_transient('jgk_payment_errors_' . $customer_id, array_slice($errors, -5), HOUR_IN_SECONDS);

            error_log("JGK: Failed to record payment for member {$member->id} in order {$order_id}");
            return;
        }

        error_log("JGK IPAY DEBUG: ✅ Payment recorded in JGK database (Payment ID: {$payment_id})");

        // Update member status to active
        $user_manager = new JuniorGolfKenya_User_Manager();
        $status_updated = $user_manager->update_member_status($member->id, 'active');

        if ($status_updated) {
            // Send payment confirmation email
            $user_manager->send_payment_confirmation_email($member->id, $membership_amount);

            error_log("JGK IPAY DEBUG: ✅ Member status updated to 'active'");
            error_log("JGK IPAY DEBUG: ✅ Payment confirmation email sent");
            error_log("JGK IPAY DEBUG: 🎉 SUCCESSFULLY PROCESSED MEMBERSHIP PAYMENT!");
            error_log("JGK IPAY DEBUG: Member ID: {$member->id} | Amount: {$membership_amount} KES | Order ID: {$order->get_id()} | Payment Method: {$payment_method}");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING COMPLETED SUCCESSFULLY ===");

            // Clear any previous errors and update status
            delete_transient('jgk_payment_errors_' . $customer_id);
            set_transient('jgk_ipay_status_' . $customer_id, 'Payment completed successfully - Member activated', HOUR_IN_SECONDS);

            error_log("JGK: Successfully processed membership payment for member {$member->id} via WooCommerce order {$order->get_id()}");
        } else {
            error_log("JGK IPAY DEBUG: ❌ Failed to update member status to 'active'");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING PARTIALLY FAILED ===");

            // Store error for debug display
            $errors = get_transient('jgk_payment_errors_' . $customer_id) ?: array();
            $errors[] = 'Failed to update member status to active';
            set_transient('jgk_payment_errors_' . $customer_id, array_slice($errors, -5), HOUR_IN_SECONDS);

            error_log("JGK: Failed to update member status for member {$member->id} in order {$order->get_id()}");
        }
    }

    /**
     * Add custom order meta for JGK member linking
     *
     * @since    1.0.0
     * @param    WC_Order    $order    WooCommerce Order object
     */
    public static function add_order_meta($order) {
        $customer_id = $order->get_customer_id();

        if ($customer_id) {
            $member = JuniorGolfKenya_Database::get_member_by_user_id($customer_id);
            if ($member) {
                $order->update_meta_data('_jgk_member_id', $member->id);
                $order->update_meta_data('_jgk_membership_number', $member->membership_number);
            }
        }
    }

    /**
     * Get WooCommerce orders for a member
     *
     * @since    1.0.0
     * @param    int    $member_id    Member ID
     * @return   array
     */
    public static function get_member_orders($member_id) {
        if (!class_exists('WooCommerce')) {
            return array();
        }

        $member = JuniorGolfKenya_Database::get_member($member_id);
        if (!$member) {
            return array();
        }

        $orders = wc_get_orders(array(
            'customer_id' => $member->user_id,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $membership_product_id = get_option('jgk_membership_product_id', 0);
        $member_orders = array();

        foreach ($orders as $order) {
            if (self::order_contains_membership_product($order)) {
                $member_orders[] = array(
                    'order_id' => $order->get_id(),
                    'order_number' => $order->get_order_number(),
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
                    'payment_method' => $order->get_payment_method_title()
                );
            }
        }

        return $member_orders;
    }
}