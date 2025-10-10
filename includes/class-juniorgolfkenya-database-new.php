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
            SELECT m.*, u.user_email, u.display_name, u.user_login,
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
        
        $offset = ($page - 1) * $per_page;
        
        $query = "
            SELECT m.*, u.user_email, u.display_name, u.user_login,
                   TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
                   c.display_name as coach_name
            FROM $table m 
            LEFT JOIN $users_table u ON m.user_id = u.ID 
            LEFT JOIN $coaches_table c ON m.coach_id = c.ID
        ";
        
        $where_conditions = array();
        $params = array();
        
        if ($status) {
            $where_conditions[] = "m.status = %s";
            $params[] = $status;
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
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
            SELECT m.*, u.user_email, u.display_name, u.user_login,
                   TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
                   c.display_name as coach_name
            FROM $table m 
            LEFT JOIN $users_table u ON m.user_id = u.ID 
            LEFT JOIN $coaches_table c ON m.coach_id = c.ID
            WHERE (
                u.display_name LIKE %s OR 
                u.user_email LIKE %s OR 
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
     * Update member status
     *
     * @since    1.0.0
     * @param    int       $member_id    Member ID
     * @param    string    $status       New status (active, pending, expired, suspended)
     * @param    string    $reason       Optional reason for status change
     * @return   bool
     */
    public static function update_member_status($member_id, $status, $reason = '') {
        global $wpdb;
        
        $member_table = $wpdb->prefix . 'jgk_members';
        $audit_table = $wpdb->prefix . 'jgk_audit_log';
        
        // Get current member data
        $current_member = self::get_member($member_id);
        if (!$current_member) {
            return false;
        }
        
        $old_status = $current_member->status;
        
        // Update member status
        $update_data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        // Handle specific status changes
        switch ($status) {
            case 'active':
                if ($old_status === 'expired') {
                    // Extend expiry date by 1 year
                    $update_data['expiry_date'] = date('Y-m-d', strtotime('+1 year'));
                }
                break;
            case 'expired':
                if ($old_status !== 'expired') {
                    $update_data['expiry_date'] = current_time('Y-m-d');
                }
                break;
            case 'suspended':
                // Could add suspension_date or other fields
                break;
        }
        
        $result = $wpdb->update($member_table, $update_data, array('id' => $member_id));
        
        // Log the status change in audit log
        if ($result && $wpdb->get_var("SHOW TABLES LIKE '$audit_table'") == $audit_table) {
            $wpdb->insert(
                $audit_table,
                array(
                    'user_id' => get_current_user_id(),
                    'action' => 'member_status_changed',
                    'object_type' => 'member',
                    'object_id' => $member_id,
                    'details' => wp_json_encode(array(
                        'old_status' => $old_status,
                        'new_status' => $status,
                        'reason' => $reason
                    )),
                    'ip_address' => self::get_user_ip(),
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
            );
        }
        
        return $result !== false;
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
        $members_table = $wpdb->prefix . 'jgk_members';
        $ratings_table = $wpdb->prefix . 'jgk_coach_ratings';
        
        $query = "
            SELECT u.ID, u.display_name, u.user_email,
                   um1.meta_value as experience_years,
                   um2.meta_value as specialties,
                   um3.meta_value as bio,
                   um4.meta_value as status,
                   COUNT(DISTINCT m.id) as member_count,
                   AVG(r.rating) as avg_rating,
                   COUNT(DISTINCT ts.id) as training_sessions
            FROM $users_table u
            INNER JOIN $usermeta_table um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities' AND um.meta_value LIKE '%jgf_coach%'
            LEFT JOIN $usermeta_table um1 ON u.ID = um1.user_id AND um1.meta_key = 'jgk_experience_years'
            LEFT JOIN $usermeta_table um2 ON u.ID = um2.user_id AND um2.meta_key = 'jgk_specialties'
            LEFT JOIN $usermeta_table um3 ON u.ID = um3.user_id AND um3.meta_key = 'jgk_bio'
            LEFT JOIN $usermeta_table um4 ON u.ID = um4.user_id AND um4.meta_key = 'jgk_coach_status'
            LEFT JOIN $members_table m ON u.ID = m.coach_id AND m.status = 'active'
            LEFT JOIN $ratings_table r ON u.ID = r.coach_id
            LEFT JOIN {$wpdb->prefix}jgk_training_schedule ts ON u.ID = ts.coach_id
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
        
        $query = "
            SELECT p.*, 
                   CONCAT(u.display_name, ' (', m.membership_number, ')') as member_name
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
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        
        return $wpdb->get_results($query);
    }

    /**
     * Record new payment
     *
     * @since    1.0.0
     * @param    int       $member_id       Member ID
     * @param    float     $amount          Payment amount
     * @param    string    $payment_type    Payment type
     * @param    string    $payment_method  Payment method
     * @param    string    $notes           Payment notes
     * @return   int|false
     */
    public static function record_payment($member_id, $amount, $payment_type, $payment_method, $notes = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_payments';
        
        $data = array(
            'member_id' => $member_id,
            'amount' => $amount,
            'payment_type' => $payment_type,
            'payment_method' => $payment_method,
            'status' => 'completed',
            'notes' => $notes,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
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
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $data, array('id' => $payment_id)) !== false;
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
            LEFT JOIN $users_table u ON r.user_id = u.ID
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
            WHERE um.meta_key = 'wp_capabilities' AND um.meta_value LIKE '%jgf_coach%'
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
        
        // Tournament statistics
        $stats['total_tournaments'] = $wpdb->get_var("SELECT COUNT(*) FROM $competitions_table");
        $stats['upcoming_tournaments'] = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $competitions_table 
            WHERE start_date > CURDATE()
        ");
        
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
}