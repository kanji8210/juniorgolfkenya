<?php

/**
 * Database operations manager
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

/**
 * Database operations manager class.
 *
 * This class handles all database operations for the plugin.
 */
class JuniorGolfKenya_Database {

    /**
     * Get member by ID
     *
     * @since    1.0.0
     * @param    int    $member_id    Member ID
     * @return   object|null
     */
    public static function get_member($member_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        $users_table = $wpdb->users;
        
        $query = "
            SELECT m.*, COALESCE(m.email, u.user_email) as user_email, u.display_name, u.user_login,
                   TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
                   CONCAT(u.display_name, ' (', m.membership_number, ')') as member_name,
                   c.display_name as coach_name
            FROM $table m 
            LEFT JOIN $users_table u ON m.user_id = u.ID 
            LEFT JOIN $users_table c ON m.coach_id = c.ID
            WHERE m.id = %d
        ";
        
        return $wpdb->get_row($wpdb->prepare($query, $member_id));
    }

    /**
     * Get member by user ID
     *
     * @since    1.0.0
     * @param    int    $user_id    WordPress User ID
     * @return   object|null
     */
    public static function get_member_by_user_id($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
    }

    /**
     * Get all members with pagination and filters
     *
     * @since    1.0.0
     * @param    int       $page         Current page
     * @param    int       $per_page     Items per page
     * @param    string    $status       Status filter
     * @return   array
     */
    public static function get_members($page = 1, $per_page = 20, $status = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'jgk_members';
        $users_table = $wpdb->users;
        $coaches_table = $wpdb->users;
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';

        $offset = ($page - 1) * $per_page;

        // Check if coach_members table exists and has data
        $coach_members_exists = $wpdb->get_var("SHOW TABLES LIKE '{$coach_members_table}'");
        $coach_members_count = $coach_members_exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$coach_members_table}") : 0;

        if ($coach_members_exists && $coach_members_count > 0) {
            // Use full query with coach JOINs
            $query = "
                SELECT m.*, COALESCE(m.email, u.user_email) as user_email, u.display_name, u.user_login,
                       TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
                       CONCAT(m.first_name, ' ', m.last_name) as full_name,
                       c.display_name as primary_coach_name,
                       GROUP_CONCAT(DISTINCT c2.display_name ORDER BY c2.display_name SEPARATOR ', ') as all_coaches
                FROM $table m
                LEFT JOIN $users_table u ON m.user_id = u.ID
                LEFT JOIN $coaches_table c ON m.coach_id = c.ID
                LEFT JOIN $coach_members_table cm ON m.id = cm.member_id AND cm.status = 'active'
                LEFT JOIN $coaches_table c2 ON cm.coach_id = c2.ID
            ";
        } else {
            // Fallback query without coach_members JOIN
            $query = "
                SELECT m.*, COALESCE(m.email, u.user_email) as user_email, u.display_name, u.user_login,
                       TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
                       CONCAT(m.first_name, ' ', m.last_name) as full_name,
                       c.display_name as primary_coach_name,
                       NULL as all_coaches
                FROM $table m
                LEFT JOIN $users_table u ON m.user_id = u.ID
                LEFT JOIN $coaches_table c ON m.coach_id = c.ID
            ";
        }

        $where_conditions = array();
        $params = array();

        if ($status) {
            $where_conditions[] = "m.status = %s";
            $params[] = $status;
        }

        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
        }

        if ($coach_members_exists && $coach_members_count > 0) {
            $query .= " GROUP BY m.id";
        }

        $query .= " ORDER BY m.created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Get members count
     *
     * @since    1.0.0
     * @param    string    $status    Status filter
     * @return   int
     */
    public static function get_members_count($status = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        $query = "SELECT COUNT(*) FROM $table";
        
        if ($status) {
            $query .= " WHERE status = %s";
            return $wpdb->get_var($wpdb->prepare($query, $status));
        }
        
        return $wpdb->get_var($query);
    }

    /**
     * Search members
     *
     * @since    1.0.0
     * @param    string    $search_term    Search term
     * @param    int       $page           Current page
     * @param    int       $per_page       Items per page
     * @return   array
     */
    public static function search_members($search_term, $page = 1, $per_page = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        $users_table = $wpdb->users;
        $coaches_table = $wpdb->users;
        
        $offset = ($page - 1) * $per_page;
        
        $query = "
            SELECT m.*, COALESCE(m.email, u.user_email) as user_email, u.display_name, u.user_login,
                   TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
                   c.display_name as coach_name
            FROM $table m 
            LEFT JOIN $users_table u ON m.user_id = u.ID 
            LEFT JOIN $coaches_table c ON m.coach_id = c.ID
            WHERE (
                u.display_name LIKE %s OR 
                COALESCE(m.email, u.user_email) LIKE %s OR 
                m.membership_number LIKE %s OR
                m.phone LIKE %s OR
                CONCAT(m.first_name, ' ', m.last_name) LIKE %s
            )
            ORDER BY m.created_at DESC 
            LIMIT %d OFFSET %d
        ";
        
        $like_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        return $wpdb->get_results($wpdb->prepare(
            $query, 
            $like_term, $like_term, $like_term, $like_term, $like_term,
            $per_page, $offset
        ));
    }

    /**
     * Create new member
     *
     * @since    1.0.0
     * @param    array    $data    Member data
     * @return   int|false
     */
    public static function create_member($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        
        $defaults = array(
            'membership_number' => self::generate_membership_number(),
            'status' => 'pending',
            'date_joined' => current_time('mysql'),
            'join_date' => current_time('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Update member
     *
     * @since    1.0.0
     * @param    int      $member_id    Member ID
     * @param    array    $data         Data to update
     * @return   bool
     */
    public static function update_member($member_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $data, array('id' => $member_id)) !== false;
    }

    /**
     * Delete member
     *
     * @since    1.0.0
     * @param    int    $member_id    Member ID
     * @return   bool
     */
    public static function delete_member($member_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        return $wpdb->delete($table, array('id' => $member_id)) !== false;
    }

    /**
     * Generate unique membership number
     *
     * @since    1.0.0
     * @return   string
     */
    public static function generate_membership_number() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        $prefix = 'JGK';
        $year = date('Y');
        
        do {
            $number = $prefix . $year . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE membership_number = %s", $number));
        } while ($exists);
        
        return $number;
    }

    /**
     * Get membership statistics
     *
     * @since    1.0.0
     * @return   array
     */
    public static function get_membership_stats() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        
        $stats = array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'active' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'active'"),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'"),
            'expired' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'expired'"),
            'suspended' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'suspended'")
        );
        
        return $stats;
    }

    /**
     * Get coaches with member count
     *
     * @since    1.0.0
     * @return   array
     */
    public static function get_coaches() {
        global $wpdb;
        
        $users_table = $wpdb->users;
        $usermeta_table = $wpdb->usermeta;
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
        $ratings_table = $wpdb->prefix . 'jgk_coach_ratings';
        $training_table = $wpdb->prefix . 'jgk_training_schedules';
        
        $query = "
            SELECT u.ID, u.display_name, u.user_email,
                   um1.meta_value as experience_years,
                   um2.meta_value as specialties,
                   um3.meta_value as bio,
                   um4.meta_value as status,
                   COUNT(DISTINCT cm.member_id) as member_count,
                   AVG(r.rating) as avg_rating,
                   COUNT(DISTINCT ts.id) as training_sessions
            FROM $users_table u
            INNER JOIN $usermeta_table um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities' AND um.meta_value LIKE '%jgk_coach%'
            LEFT JOIN $usermeta_table um1 ON u.ID = um1.user_id AND um1.meta_key = 'jgk_experience_years'
            LEFT JOIN $usermeta_table um2 ON u.ID = um2.user_id AND um2.meta_key = 'jgk_specialties'
            LEFT JOIN $usermeta_table um3 ON u.ID = um3.user_id AND um3.meta_key = 'jgk_bio'
            LEFT JOIN $usermeta_table um4 ON u.ID = um4.user_id AND um4.meta_key = 'jgk_coach_status'
            LEFT JOIN $coach_members_table cm ON u.ID = cm.coach_id AND cm.status = 'active'
            LEFT JOIN $ratings_table r ON u.ID = r.coach_user_id
            LEFT JOIN $training_table ts ON u.ID = ts.coach_user_id
            GROUP BY u.ID
            ORDER BY u.display_name
        ";
        
        return $wpdb->get_results($query);
    }

    /**
     * Get payments with filters
     *
     * @since    1.0.0
     * @param    string    $status_filter    Status filter
     * @param    string    $type_filter      Type filter
     * @param    string    $date_from        Start date
     * @param    string    $date_to          End date
     * @return   array
     */
    public static function get_payments($status_filter = 'all', $type_filter = 'all', $date_from = '', $date_to = '') {
        global $wpdb;

        $payments_table = $wpdb->prefix . 'jgk_payments';
        $members_table = $wpdb->prefix . 'jgk_members';
        $users_table = $wpdb->users;

        // Get manually recorded payments
        $manual_payments = array();

        $query = "
            SELECT p.*,
                   CONCAT(u.display_name, ' (', m.membership_number, ')') as member_name,
                   'manual' as source
            FROM $payments_table p
            LEFT JOIN $members_table m ON p.member_id = m.id
            LEFT JOIN $users_table u ON m.user_id = u.ID
            WHERE 1=1
        ";

        $params = array();

        if ($status_filter !== 'all') {
            $query .= " AND p.status = %s";
            $params[] = $status_filter;
        }

        if ($type_filter !== 'all') {
            $query .= " AND p.payment_type = %s";
            $params[] = $type_filter;
        }

        if ($date_from) {
            $query .= " AND DATE(p.created_at) >= %s";
            $params[] = $date_from;
        }

        if ($date_to) {
            $query .= " AND DATE(p.created_at) <= %s";
            $params[] = $date_to;
        }

        $query .= " ORDER BY p.created_at DESC";

        if (!empty($params)) {
            $manual_payments = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $manual_payments = $wpdb->get_results($query);
        }

        // Get WooCommerce payments for ALL orders (not just membership product)
        $woocommerce_payments = array();

        // Check WooCommerce tables exist
        $wc_tables_ok = (
            $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->posts)) === $wpdb->posts &&
            $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->postmeta)) === $wpdb->postmeta &&
            $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'woocommerce_order_items')) === ($wpdb->prefix . 'woocommerce_order_items') &&
            $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'woocommerce_order_itemmeta')) === ($wpdb->prefix . 'woocommerce_order_itemmeta')
        );

        if ($wc_tables_ok) {
            error_log("JGK PAYMENT DEBUG: Getting ALL WooCommerce payments");

            $wc_query = "
                SELECT
                    o.ID as id,
                    o.ID as order_id,
                    o.post_date as created_at,
                    om_total.meta_value as amount,
                    CASE
                        WHEN oim.meta_value = %d THEN 'membership'
                        ELSE 'other'
                    END as payment_type,
                    COALESCE(om_payment.meta_value, 'woocommerce') as payment_method,
                    CASE
                        WHEN o.post_status = 'wc-completed' THEN 'completed'
                        WHEN o.post_status = 'wc-processing' THEN 'processing'
                        WHEN o.post_status = 'wc-pending' THEN 'pending'
                        WHEN o.post_status = 'wc-on-hold' THEN 'on_hold'
                        WHEN o.post_status = 'wc-cancelled' THEN 'cancelled'
                        WHEN o.post_status = 'wc-refunded' THEN 'refunded'
                        WHEN o.post_status = 'wc-failed' THEN 'failed'
                        ELSE 'unknown'
                    END as status,
                    CASE
                        WHEN m.user_id IS NOT NULL THEN CONCAT(u.display_name, ' (', m.membership_number, ')')
                        WHEN om_customer.meta_value != 0 THEN CONCAT('Customer #', om_customer.meta_value)
                        ELSE 'Guest Customer'
                    END as member_name,
                    'woocommerce' as source,
                    om_transaction.meta_value as transaction_id,
                    CONCAT('WooCommerce Order #', o.ID) as notes
                FROM {$wpdb->posts} o
                INNER JOIN {$wpdb->postmeta} om_customer ON o.ID = om_customer.post_id AND om_customer.meta_key = '_customer_user'
                INNER JOIN {$wpdb->postmeta} om_total ON o.ID = om_total.post_id AND om_total.meta_key = '_order_total'
                LEFT JOIN {$wpdb->postmeta} om_payment ON o.ID = om_payment.post_id AND om_payment.meta_key = '_payment_method'
                LEFT JOIN {$wpdb->postmeta} om_transaction ON o.ID = om_transaction.post_id AND om_transaction.meta_key = '_transaction_id'
                INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
                LEFT JOIN $members_table m ON om_customer.meta_value = m.user_id
                LEFT JOIN $users_table u ON m.user_id = u.ID
                WHERE o.post_type = 'shop_order'
                AND o.post_status IN ('wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold', 'wc-cancelled', 'wc-refunded', 'wc-failed')
            ";

            $wc_params = array($membership_product_id);

            // Add date filters for WooCommerce payments
            if ($date_from) {
                $wc_query .= " AND DATE(o.post_date) >= %s";
                $wc_params[] = $date_from;
            }

            if ($date_to) {
                $wc_query .= " AND DATE(o.post_date) <= %s";
                $wc_params[] = $date_to;
            }

            // Add status filter for WooCommerce payments
            if ($status_filter !== 'all') {
                $status_map = array(
                    'completed' => 'wc-completed',
                    'processing' => 'wc-processing',
                    'pending' => 'wc-pending',
                    'on_hold' => 'wc-on-hold',
                    'cancelled' => 'wc-cancelled',
                    'refunded' => 'wc-refunded',
                    'failed' => 'wc-failed'
                );

                if (isset($status_map[$status_filter])) {
                    $wc_query .= " AND o.post_status = %s";
                    $wc_params[] = $status_map[$status_filter];
                }
            }

            // Add type filter for WooCommerce payments
            if ($type_filter !== 'all') {
                if ($type_filter === 'membership') {
                    $wc_query .= " AND oim.meta_value = %d";
                    $wc_params[] = $membership_product_id;
                } elseif ($type_filter === 'other') {
                    $wc_query .= " AND oim.meta_value != %d";
                    $wc_params[] = $membership_product_id;
                }
                // For other types, we'll need to map them to products or categories
            }

            $wc_query .= " ORDER BY o.post_date DESC";

            $woocommerce_payments = $wpdb->get_results($wpdb->prepare($wc_query, $wc_params));

            error_log("JGK PAYMENT DEBUG: Found " . count($woocommerce_payments) . " WooCommerce payments");
        }

        // Combine and sort all payments
        $all_payments = array_merge($manual_payments, $woocommerce_payments);

        // Sort by date (newest first)
        usort($all_payments, function($a, $b) {
            $date_a = isset($a->created_at) ? strtotime($a->created_at) : 0;
            $date_b = isset($b->created_at) ? strtotime($b->created_at) : 0;
            return $date_b - $date_a;
        });

        error_log("JGK PAYMENT DEBUG: Total payments returned: " . count($all_payments));

        return $all_payments;
    }

    /**
     * Update payment
     *
     * @since    1.0.0
     * @param    int      $payment_id    Payment ID
     * @param    array    $data          Data to update
     * @return   bool
     */
    public static function update_payment($payment_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_payments';
        
        // Fetch payment to check source (manual vs WooCommerce)
        $payment = $wpdb->get_row($wpdb->prepare("SELECT id, order_id FROM {$table} WHERE id = %d", $payment_id));
        if (!$payment) {
            return false;
        }

        // Whitelist allowed fields
        $allowed = array('amount','payment_type','payment_method','status','notes','payment_date');
        $update = array();
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }

        // Validate amount if present: must be numeric and > 1
        if (isset($update['amount'])) {
            if (!is_numeric($update['amount']) || floatval($update['amount']) <= 1) {
                error_log('JGK PAYMENT VALIDATION: Rejecting update, amount must be > 1');
                return false;
            }
            $update['amount'] = floatval($update['amount']);
        }

        // If WooCommerce payment (has order_id), restrict editable fields
        if (!empty($payment->order_id)) {
            // Only allow status (non-conflicting) and notes for WC entries
            $wc_allowed = array('status','notes');
            $update = array_intersect_key($update, array_flip($wc_allowed));
        }

        if (empty($update)) {
            return true; // nothing to update
        }

        $update['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $update, array('id' => $payment_id)) !== false;
    }

    /**
     * Get role requests with filters
     *
     * @since    1.0.0
     * @param    string    $status    Status filter
     * @return   array
     */
    public static function get_role_requests($status = 'pending') {
        global $wpdb;
        
        $requests_table = $wpdb->prefix . 'jgk_role_requests';
        $users_table = $wpdb->users;
        
        $query = "
            SELECT r.*, u.display_name, u.user_email
            FROM $requests_table r
            LEFT JOIN $users_table u ON r.requester_user_id = u.ID
            WHERE r.status = %s
            ORDER BY r.created_at DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $status));
    }

    /**
     * Get overview statistics
     *
     * @since    1.0.0
     * @return   array
     */
    public static function get_overview_statistics() {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        $payments_table = $wpdb->prefix . 'jgk_payments';
        $competitions_table = $wpdb->prefix . 'jgk_competitions';
        $users_table = $wpdb->users;
        $usermeta_table = $wpdb->usermeta;
        
        $stats = array();
        
        // Member statistics
        $stats['total_members'] = $wpdb->get_var("SELECT COUNT(*) FROM $members_table");
        $stats['active_members'] = $wpdb->get_var("SELECT COUNT(*) FROM $members_table WHERE status = 'active'");
        
        // Coach statistics
        $stats['total_coaches'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT u.ID) 
            FROM $users_table u
            INNER JOIN $usermeta_table um ON u.ID = um.user_id 
            WHERE um.meta_key = 'wp_capabilities' AND um.meta_value LIKE '%jgk_coach%'
        ");
        $stats['active_coaches'] = $stats['total_coaches']; // Simplified
        
        // Revenue statistics
        $stats['total_revenue'] = $wpdb->get_var("
            SELECT COALESCE(SUM(amount), 0) 
            FROM $payments_table 
            WHERE status = 'completed'
        ");
        $stats['monthly_revenue'] = $wpdb->get_var("
            SELECT COALESCE(SUM(amount), 0) 
            FROM $payments_table 
            WHERE status = 'completed' 
            AND MONTH(created_at) = MONTH(CURDATE()) 
            AND YEAR(created_at) = YEAR(CURDATE())
        ");
        
        // Tournament statistics (check if table exists)
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$competitions_table'");
        if ($table_exists) {
            $stats['total_tournaments'] = $wpdb->get_var("SELECT COUNT(*) FROM $competitions_table");
            $stats['upcoming_tournaments'] = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM $competitions_table 
                WHERE start_date > CURDATE()
            ");
        } else {
            $stats['total_tournaments'] = 0;
            $stats['upcoming_tournaments'] = 0;
        }
        
        return $stats;
    }

    /**
     * Get user IP address
     *
     * @since    1.0.0
     * @return   string
     */
    private static function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
    }
    /**
     * Log audit entry
     *
     * @since    1.0.0
     * @param    array    $data    Audit data (action, object_type, object_id, old_values, new_values)
     * @return   bool
     */
    public static function log_audit($data) {
        global $wpdb;
        
        $audit_table = $wpdb->prefix . 'jgk_audit_log';
        
        // Check if audit table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$audit_table'") != $audit_table) {
            return false;
        }
        
        // Prepare audit data
        $audit_data = array(
            'user_id' => get_current_user_id(),
            'member_id' => isset($data['member_id']) ? $data['member_id'] : null,
            'action' => isset($data['action']) ? $data['action'] : '',
            'object_type' => isset($data['object_type']) ? $data['object_type'] : '',
            'object_id' => isset($data['object_id']) ? $data['object_id'] : 0,
            'old_values' => isset($data['old_values']) ? $data['old_values'] : null,
            'new_values' => isset($data['new_values']) ? $data['new_values'] : null,
            'ip_address' => self::get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert(
            $audit_table,
            $audit_data,
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }

    /**
     * Get member parents/guardians
     *
     * @since    1.0.0
     * @param    int    $member_id    Member ID
     * @return   array
     */
    public static function get_member_parents($member_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_parents_guardians';
        
        $query = $wpdb->prepare("
            SELECT * FROM $table 
            WHERE member_id = %d 
            ORDER BY is_primary_contact DESC, created_at ASC
        ", $member_id);
        
        return $wpdb->get_results($query);
    }

    /**
     * Record payment for a member
     *
     * @since    1.0.0
     * @param    int       $member_id       Member ID
     * @param    int       $order_id        WooCommerce Order ID
     * @param    float     $amount          Payment amount
     * @param    string    $payment_method  Payment method
     * @param    string    $status          Payment status
     * @param    string    $transaction_id  Transaction ID (optional)
     * @return   bool|int                   Insert ID on success, false on failure
     */
    public static function record_payment($member_id, $order_id = null, $amount = 0, $payment_method = '', $status = 'completed', $transaction_id = '', $args = array()) {
        global $wpdb;

        $table = $wpdb->prefix . 'jgk_payments';

        // Check if payments table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return false;
        }

        // Validate amount > 1
        if (!is_numeric($amount) || floatval($amount) <= 1) {
            error_log('JGK PAYMENT VALIDATION: record_payment rejected, amount must be > 1');
            return false;
        }

        $defaults = array(
            'payment_type' => 'membership',
            'payment_gateway' => 'manual',
            'membership_id' => null,
            'currency' => get_option('jgk_currency', 'KES'),
            'notes' => '',
            'payment_date' => current_time('mysql')
        );

        $args = wp_parse_args($args, $defaults);

        $payment_data = array(
            'member_id' => intval($member_id)
        );
        $formats = array('%d');

        if (!is_null($order_id)) {
            $payment_data['order_id'] = intval($order_id);
            $formats[] = '%d';
        }

        if (!empty($args['membership_id'])) {
            $payment_data['membership_id'] = intval($args['membership_id']);
            $formats[] = '%d';
        }

        $payment_data['amount'] = floatval($amount);
        $formats[] = '%f';

        $payment_data['payment_type'] = $args['payment_type'];
        $formats[] = '%s';

        $payment_data['currency'] = $args['currency'];
        $formats[] = '%s';

        $payment_data['payment_method'] = $payment_method;
        $formats[] = '%s';

        $payment_data['payment_gateway'] = $args['payment_gateway'];
        $formats[] = '%s';

        $payment_data['transaction_id'] = $transaction_id;
        $formats[] = '%s';

        $payment_data['status'] = $status;
        $formats[] = '%s';

        $payment_data['payment_date'] = $args['payment_date'];
        $formats[] = '%s';

        $payment_data['notes'] = $args['notes'];
        $formats[] = '%s';

        $payment_data['created_at'] = current_time('mysql');
        $formats[] = '%s';

        $result = $wpdb->insert($table, $payment_data, $formats);

        if ($result !== false) {
            return $wpdb->insert_id;
        }

        return false;
    }

    public static function record_manual_payment($member_id, $amount, $payment_type, $payment_method, $notes = '', $status = 'completed') {
        return self::record_payment(
            $member_id,
            null,
            $amount,
            $payment_method,
            $status,
            '',
            array(
                'payment_type' => $payment_type,
                'payment_gateway' => 'manual',
                'notes' => $notes,
                'payment_date' => current_time('mysql')
            )
        );
    }
}