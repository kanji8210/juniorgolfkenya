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

        // Capture selected member IDs before checkout.
        add_action('template_redirect', array(__CLASS__, 'capture_payment_selection'));

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

        $customer_id = $order->get_customer_id();
        if ($customer_id) {
            $customer = get_userdata($customer_id);
            if ($customer) {
                require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-parent-dashboard.php';
                if (in_array('jgk_parent', (array) $customer->roles, true) || JuniorGolfKenya_Parent_Dashboard::is_parent($customer->user_email)) {
                    $parent_dashboard_id = get_option('jgk_page_parent_dashboard');
                    return $parent_dashboard_id ? get_permalink($parent_dashboard_id) : home_url('/parent-dashboard');
                }
            }
        }

        $dashboard_page_id = get_option('jgk_page_member_dashboard');
        return $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/member-dashboard');
    }

    /**
     * Capture a payment selection from the current request and persist it in the session.
     *
     * @since    1.0.0
     */
    public static function capture_payment_selection() {
        if (!class_exists('WooCommerce') || is_admin()) {
            return;
        }

        if (!isset($_GET['jgk_pay_member']) && !isset($_GET['jgk_pay_members'])) {
            return;
        }

        if (!is_user_logged_in() || !WC()->session) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['jgk_pay_nonce'] ?? ''));
        if (empty($nonce) || !wp_verify_nonce($nonce, 'jgk_select_membership_payment')) {
            return;
        }

        $member_ids = self::get_requested_member_ids_from_request();
        $member_ids = self::filter_member_ids_for_current_user($member_ids);

        if (empty($member_ids)) {
            WC()->session->__unset('jgk_member_ids');
            WC()->session->__unset('jgk_member_id');
            return;
        }

        WC()->session->set('jgk_member_ids', $member_ids);
        WC()->session->set('jgk_member_id', reset($member_ids));
    }

    /**
     * Parse selected member IDs from the current request.
     *
     * @since    1.0.0
     * @return   array
     */
    private static function get_requested_member_ids_from_request() {
        $member_ids = array();

        if (isset($_GET['jgk_pay_member'])) {
            $member_ids[] = absint($_GET['jgk_pay_member']);
        }

        if (isset($_GET['jgk_pay_members'])) {
            $requested = explode(',', sanitize_text_field(wp_unslash($_GET['jgk_pay_members'])));
            foreach ($requested as $member_id) {
                $member_id = absint($member_id);
                if ($member_id > 0) {
                    $member_ids[] = $member_id;
                }
            }
        }

        $member_ids = array_values(array_unique(array_filter($member_ids)));

        return $member_ids;
    }

    /**
     * Limit selected member IDs to the current user's own children or member record.
     *
     * @since    1.0.0
     * @param    array $member_ids Requested member IDs.
     * @return   array
     */
    private static function filter_member_ids_for_current_user($member_ids) {
        if (empty($member_ids) || !is_user_logged_in()) {
            return array();
        }

        $current_user = wp_get_current_user();

        require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-parent-dashboard.php';
        if (in_array('jgk_parent', (array) $current_user->roles, true) || JuniorGolfKenya_Parent_Dashboard::is_parent($current_user->user_email)) {
            $children = JuniorGolfKenya_Parent_Dashboard::get_parent_children($current_user->user_email);
            $allowed_ids = array_map('intval', wp_list_pluck($children, 'id'));
            return array_values(array_intersect($member_ids, $allowed_ids));
        }

        $member = JuniorGolfKenya_Database::get_member_by_user_id($current_user->ID);
        if ($member && in_array((int) $member->id, $member_ids, true)) {
            return array((int) $member->id);
        }

        return array();
    }

    /**
     * Retrieve selected member IDs from the WooCommerce session.
     *
     * @since    1.0.0
     * @return   array
     */
    private static function get_selected_member_ids() {
        if (!class_exists('WooCommerce') || !WC()->session) {
            return array();
        }

        $member_ids = WC()->session->get('jgk_member_ids', array());
        if (!is_array($member_ids)) {
            $member_ids = array();
        }

        $member_ids = array_values(array_unique(array_filter(array_map('absint', $member_ids))));

        if (empty($member_ids)) {
            $single_member_id = absint(WC()->session->get('jgk_member_id', 0));
            if ($single_member_id > 0) {
                $member_ids = array($single_member_id);
            }
        }

        return $member_ids;
    }

    /**
     * Retrieve target member IDs recorded on an order.
     *
     * @since    1.0.0
     * @param    WC_Order $order WooCommerce order object.
     * @return   array
     */
    private static function get_order_member_ids($order) {
        $member_ids = array();

        $stored_ids = $order->get_meta('_jgk_member_ids', true);
        if (!empty($stored_ids)) {
            foreach (explode(',', $stored_ids) as $member_id) {
                $member_id = absint($member_id);
                if ($member_id > 0) {
                    $member_ids[] = $member_id;
                }
            }
        }

        if (empty($member_ids)) {
            $single_member_id = absint($order->get_meta('_jgk_member_id', true));
            if ($single_member_id > 0) {
                $member_ids[] = $single_member_id;
            }
        }

        return array_values(array_unique(array_filter($member_ids)));
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
        $debug_key = $customer_id ?: 'order_' . $order_id;

        error_log("JGK IPAY DEBUG: === MEMBERSHIP PAYMENT PROCESSING STARTED ===");
        error_log("JGK IPAY DEBUG: Processing order ID: " . $order_id . " for customer ID: " . ($customer_id ?: 'GUEST'));
        error_log("JGK IPAY DEBUG: Membership Product ID: " . ($membership_product_id ?: 'NOT SET'));
        error_log("JGK IPAY DEBUG: Payment Method: {$payment_method} | Transaction ID: " . ($transaction_id ?: 'Not set'));
        error_log("JGK IPAY DEBUG: iPay/eLipa Payment: " . (self::is_ipay_payment($payment_method) ? 'YES' : 'NO'));

        // Store iPay processing status for debug display
        if (self::is_ipay_payment($payment_method)) {
            set_transient('jgk_ipay_status_' . $debug_key, 'Processing iPay/eLipa payment for order ' . $order->get_id(), HOUR_IN_SECONDS);
        }

        if (!$membership_product_id) {
            error_log("JGK IPAY DEBUG: ❌ Payment processing failed - Missing product ID");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING ABORTED ===");
            error_log("JGK: Missing membership product ID for order {$order_id}");

            // Store error for debug display
            $errors = get_transient('jgk_payment_errors_' . $debug_key) ?: array();
            $errors[] = 'Missing membership product ID';
            set_transient('jgk_payment_errors_' . $debug_key, array_slice($errors, -5), HOUR_IN_SECONDS);

            return;
        }

        $target_member_ids = self::get_order_member_ids($order);
        $members = array();

        if (!empty($target_member_ids)) {
            foreach ($target_member_ids as $target_member_id) {
                $member = JuniorGolfKenya_Database::get_member($target_member_id);
                if ($member) {
                    $members[] = $member;
                }
            }
        }

        if (empty($members) && $customer_id) {
            // Get member by user ID
            $member = JuniorGolfKenya_Database::get_member_by_user_id($customer_id);

            if (!$member) {
                error_log("JGK IPAY DEBUG: ⚠️ No member found for customer ID {$customer_id} - auto-creating from order data");

                // Auto-create jgk_members record from WP user + order data
                $wp_user = get_userdata($customer_id);
                if (!$wp_user) {
                    error_log("JGK IPAY DEBUG: ❌ WordPress user {$customer_id} not found - cannot auto-create member");
                    return;
                }

                $member_data = array(
                    'user_id'         => $customer_id,
                    'first_name'      => $wp_user->first_name ?: $order->get_billing_first_name(),
                    'last_name'       => $wp_user->last_name ?: $order->get_billing_last_name(),
                    'email'           => $wp_user->user_email,
                    'phone'           => $order->get_billing_phone(),
                    'membership_type' => 'junior',
                    'status'          => 'approved',
                    'date_joined'     => current_time('mysql'),
                    'join_date'       => current_time('Y-m-d'),
                    'expiry_date'     => date('Y-m-d', strtotime('+1 year')),
                );

                $new_member_id = JuniorGolfKenya_Database::create_member($member_data);

                if (!$new_member_id) {
                    error_log("JGK IPAY DEBUG: ❌ Failed to auto-create member for customer {$customer_id}");
                    return;
                }

                // Assign jgk_member role if missing
                $user_obj = new WP_User($customer_id);
                if (!in_array('jgk_member', $user_obj->roles, true)) {
                    $user_obj->add_role('jgk_member');
                }

                error_log("JGK IPAY DEBUG: ✅ Auto-created member ID {$new_member_id} for customer {$customer_id}");

                // Re-fetch the newly created member
                $member = JuniorGolfKenya_Database::get_member_by_user_id($customer_id);
                if (!$member) {
                    error_log("JGK IPAY DEBUG: ❌ Could not retrieve auto-created member");
                    return;
                }
            }

            $members[] = $member;
        }

        if (empty($members)) {
            $errors = get_transient('jgk_payment_errors_' . $debug_key) ?: array();
            $errors[] = 'No target member found for this payment.';
            set_transient('jgk_payment_errors_' . $debug_key, array_slice($errors, -5), HOUR_IN_SECONDS);
            error_log("JGK IPAY DEBUG: ❌ No target member found for order {$order_id}");
            return;
        }

        // Avoid double-processing the same order
        global $wpdb;
        $existing_paid_member_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT member_id FROM {$wpdb->prefix}jgk_payments WHERE order_id = %d",
            $order_id
        ));

        // Calculate membership amount from order
        $membership_amount = 0;
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id == $membership_product_id) {
                $membership_amount += (float) $item->get_total();
            }
        }

        if ($membership_amount <= 0) {
            error_log("JGK IPAY DEBUG: ❌ Invalid membership amount: {$membership_amount}");
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING ABORTED - INVALID AMOUNT ===");

            // Store error for debug display
            $errors = get_transient('jgk_payment_errors_' . $debug_key) ?: array();
            $errors[] = 'Invalid membership amount: ' . $membership_amount;
            set_transient('jgk_payment_errors_' . $debug_key, array_slice($errors, -5), HOUR_IN_SECONDS);

            error_log("JGK: Invalid membership amount for order {$order_id}");
            return;
        }

        error_log("JGK IPAY DEBUG: 💰 Valid membership amount: {$membership_amount} KES");

        $per_member_amount = $membership_amount / max(1, count($members));
        $user_manager = new JuniorGolfKenya_User_Manager();
        $processed_members = array();

        foreach ($members as $member) {
            error_log("JGK IPAY DEBUG: ✅ Member found - ID: {$member->id}, Name: {$member->first_name} {$member->last_name}, Status: {$member->status}");

            if (in_array((int) $member->id, array_map('intval', $existing_paid_member_ids), true)) {
                if ($member->status !== 'active') {
                    $user_manager->update_member_status($member->id, 'active');
                }
                continue;
            }

            $allowed_statuses = array('approved', 'pending', 'pending_approval', 'active');
            if (!in_array($member->status, $allowed_statuses, true)) {
                error_log("JGK IPAY DEBUG: ❌ Member status is '{$member->status}' - not eligible for payment processing");
                continue;
            }

            $payment_id = JuniorGolfKenya_Database::record_payment(
                $member->id,
                $order_id,
                $per_member_amount,
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
                error_log("JGK IPAY DEBUG: ❌ Failed to record payment in JGK database for member {$member->id}");
                continue;
            }

            if ($user_manager->update_member_status($member->id, 'active')) {
                $user_manager->send_payment_confirmation_email($member->id, $per_member_amount);
                $processed_members[] = $member->id;
            }
        }

        if (!empty($processed_members)) {
            delete_transient('jgk_payment_errors_' . $debug_key);
            set_transient('jgk_ipay_status_' . $debug_key, 'Payment completed successfully - Member activated', HOUR_IN_SECONDS);
            error_log("JGK IPAY DEBUG: === PAYMENT PROCESSING COMPLETED SUCCESSFULLY ===");
        } else {
            $errors = get_transient('jgk_payment_errors_' . $debug_key) ?: array();
            $errors[] = 'Payment completed but no member records were updated.';
            set_transient('jgk_payment_errors_' . $debug_key, array_slice($errors, -5), HOUR_IN_SECONDS);
        }
    }

    /**
     * Add custom order meta for JGK member linking
     *
     * @since    1.0.0
     * @param    WC_Order    $order    WooCommerce Order object
     */
    public static function add_order_meta($order) {
        $member_ids = self::get_selected_member_ids();

        if (!empty($member_ids)) {
            $order->update_meta_data('_jgk_member_ids', implode(',', $member_ids));

            if (count($member_ids) === 1) {
                $member = JuniorGolfKenya_Database::get_member($member_ids[0]);
                if ($member) {
                    $order->update_meta_data('_jgk_member_id', $member->id);
                    $order->update_meta_data('_jgk_membership_number', $member->membership_number);
                }
            }

            return;
        }

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