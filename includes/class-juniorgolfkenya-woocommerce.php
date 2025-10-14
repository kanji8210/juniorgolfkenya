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
            return;
        }

        // Check if this order contains membership products
        $has_membership_product = self::order_contains_membership_product($order);

        if ($has_membership_product) {
            self::process_membership_payment($order);
        }
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
        // Log status changes for membership orders
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        if (self::order_contains_membership_product($order)) {
            error_log("JGK: Membership order {$order_id} status changed from {$old_status} to {$new_status}");

            // If order is completed, process the membership
            if ($new_status === 'completed') {
                self::process_membership_payment($order);
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

        // Advanced Payment Debug Logging
        error_log("JGK PAYMENT DEBUG: === Payment Check Started ===");
        error_log("JGK PAYMENT DEBUG: Order ID: " . $order->get_id());
        error_log("JGK PAYMENT DEBUG: Membership Product ID from settings: " . ($membership_product_id ?: 'NOT SET'));

        if (!$membership_product_id) {
            error_log("JGK PAYMENT DEBUG: ❌ No membership product ID configured in plugin settings");
            error_log("JGK PAYMENT DEBUG: === Payment Check Failed - No Product ID ===");
            return false;
        }

        error_log("JGK PAYMENT DEBUG: ✅ Membership product ID is configured: {$membership_product_id}");

        // Check if product exists in WooCommerce
        $product = wc_get_product($membership_product_id);
        if (!$product) {
            error_log("JGK PAYMENT DEBUG: ❌ Membership product ID {$membership_product_id} does not exist in WooCommerce");
            error_log("JGK PAYMENT DEBUG: === Payment Check Failed - Product Not Found ===");
            return false;
        }

        error_log("JGK PAYMENT DEBUG: ✅ Membership product exists: '" . $product->get_name() . "' (ID: {$membership_product_id})");

        // Count total payments for this membership product
        global $wpdb;
        $payments_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}jgk_payments
            WHERE membership_id IS NOT NULL AND status = 'completed'
        "));
        error_log("JGK PAYMENT DEBUG: 📊 Total completed membership payments: " . ($payments_count ?: 0));

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
        error_log("JGK PAYMENT DEBUG: 🛒 WooCommerce orders with this membership product: " . ($wc_payments_count ?: 0));

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            error_log("JGK PAYMENT DEBUG: Checking order item - Product ID: {$product_id}, Quantity: " . $item->get_quantity() . ", Name: '" . $item->get_name() . "'");

            if ($product_id == $membership_product_id) {
                error_log("JGK PAYMENT DEBUG: ✅ MATCH FOUND - Order contains membership product!");
                error_log("JGK PAYMENT DEBUG: === Payment Check Successful ===");
                return true;
            }
        }

        error_log("JGK PAYMENT DEBUG: ❌ No membership product found in order items");
        error_log("JGK PAYMENT DEBUG: === Payment Check Failed - Product Not In Order ===");
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

        error_log("JGK PAYMENT DEBUG: === Payment Processing Started ===");
        error_log("JGK PAYMENT DEBUG: Processing order ID: " . $order->get_id() . " for customer ID: " . ($customer_id ?: 'GUEST'));
        error_log("JGK PAYMENT DEBUG: Membership Product ID: " . ($membership_product_id ?: 'NOT SET'));

        if (!$membership_product_id || !$customer_id) {
            error_log("JGK PAYMENT DEBUG: ❌ Payment processing failed - Missing product ID or customer ID");
            error_log("JGK PAYMENT DEBUG: === Payment Processing Aborted ===");
            error_log("JGK: Missing membership product ID or customer ID for order {$order->get_id()}");
            return;
        }

        // Get member by user ID
        $member = JuniorGolfKenya_Database::get_member_by_user_id($customer_id);

        if (!$member) {
            error_log("JGK PAYMENT DEBUG: ❌ No member found for customer ID {$customer_id}");
            error_log("JGK PAYMENT DEBUG: === Payment Processing Aborted - No Member ===");
            error_log("JGK: No member found for user ID {$customer_id} in order {$order->get_id()}");
            return;
        }

        error_log("JGK PAYMENT DEBUG: ✅ Member found - ID: {$member->id}, Name: {$member->first_name} {$member->last_name}, Status: {$member->status}");

        // Check if member is approved (should be before payment)
        if ($member->status !== 'approved') {
            error_log("JGK PAYMENT DEBUG: ❌ Member status is '{$member->status}' - must be 'approved' for payment processing");
            error_log("JGK PAYMENT DEBUG: === Payment Processing Aborted - Member Not Approved ===");
            error_log("JGK: Member {$member->id} is not approved for payment processing in order {$order->get_id()}");
            return;
        }

        error_log("JGK PAYMENT DEBUG: ✅ Member is approved for payment processing");

        // Calculate membership amount from order
        $membership_amount = 0;
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id == $membership_product_id) {
                $membership_amount = $item->get_total();
                error_log("JGK PAYMENT DEBUG: 📦 Found membership product in order - Amount: {$membership_amount}");
                break;
            }
        }

        if ($membership_amount <= 0) {
            error_log("JGK PAYMENT DEBUG: ❌ Invalid membership amount: {$membership_amount}");
            error_log("JGK PAYMENT DEBUG: === Payment Processing Aborted - Invalid Amount ===");
            error_log("JGK: Invalid membership amount for order {$order->get_id()}");
            return;
        }

        error_log("JGK PAYMENT DEBUG: 💰 Valid membership amount: {$membership_amount}");

        // Record the payment in JGK system
        $payment_id = JuniorGolfKenya_Database::record_payment(
            $member->id,
            $order->get_id(),
            $membership_amount,
            $order->get_payment_method_title(),
            'completed',
            $order->get_transaction_id()
        );

        if (!$payment_id) {
            error_log("JGK PAYMENT DEBUG: ❌ Failed to record payment in JGK database");
            error_log("JGK PAYMENT DEBUG: === Payment Processing Failed ===");
            error_log("JGK: Failed to record payment for member {$member->id} in order {$order->get_id()}");
            return;
        }

        error_log("JGK PAYMENT DEBUG: ✅ Payment recorded in JGK database (Payment ID: {$payment_id})");

        // Update member status to active
        $user_manager = new JuniorGolfKenya_User_Manager();
        $status_updated = $user_manager->update_member_status($member->id, 'active');

        if ($status_updated) {
            // Send payment confirmation email
            $user_manager->send_payment_confirmation_email($member->id, $membership_amount);

            error_log("JGK PAYMENT DEBUG: ✅ Member status updated to 'active'");
            error_log("JGK PAYMENT DEBUG: ✅ Payment confirmation email sent");
            error_log("JGK PAYMENT DEBUG: 🎉 SUCCESSFULLY PROCESSED MEMBERSHIP PAYMENT!");
            error_log("JGK PAYMENT DEBUG: Member ID: {$member->id}, Amount: {$membership_amount}, Order ID: {$order->get_id()}");
            error_log("JGK PAYMENT DEBUG: === Payment Processing Completed Successfully ===");

            error_log("JGK: Successfully processed membership payment for member {$member->id} via WooCommerce order {$order->get_id()}");
        } else {
            error_log("JGK PAYMENT DEBUG: ❌ Failed to update member status to 'active'");
            error_log("JGK PAYMENT DEBUG: === Payment Processing Partially Failed ===");
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