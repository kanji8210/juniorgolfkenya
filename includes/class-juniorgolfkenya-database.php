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
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $member_id));
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
     * Get all members with pagination
     *
     * @since    1.0.0
     * @param    int    $page        Page number
     * @param    int    $per_page    Items per page
     * @param    string $status      Member status filter
     * @return   array
     */
    public static function get_members($page = 1, $per_page = 20, $status = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        $offset = ($page - 1) * $per_page;
        
        $where = '';
        $params = array();
        
        if (!empty($status)) {
            $where = ' WHERE status = %s';
            $params[] = $status;
        }
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $sql = "SELECT m.*, u.user_email, u.display_name 
                FROM $table m 
                LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
                $where 
                ORDER BY m.created_at DESC 
                LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Get member count
     *
     * @since    1.0.0
     * @param    string $status    Status filter
     * @return   int
     */
    public static function get_members_count($status = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        
        if (!empty($status)) {
            return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE status = %s", $status));
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    /**
     * Create new member
     *
     * @since    1.0.0
     * @param    array    $data    Member data
     * @return   int|false         Member ID on success, false on failure
     */
    public static function create_member($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        
        $defaults = array(
            'membership_number' => self::generate_membership_number(),
            'membership_type' => 'standard',
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Update member
     *
     * @since    1.0.0
     * @param    int      $member_id    Member ID
     * @param    array    $data         Updated data
     * @return   bool
     */
    public static function update_member($member_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $data, array('id' => $member_id));
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
        return $wpdb->delete($table, array('id' => $member_id));
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
     * Get coach ratings for a member
     *
     * @since    1.0.0
     * @param    int    $member_id    Member ID
     * @return   array
     */
    public static function get_member_ratings($member_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgf_coach_ratings';
        $sql = "SELECT cr.*, u.display_name as coach_name 
                FROM $table cr 
                LEFT JOIN {$wpdb->users} u ON cr.coach_user_id = u.ID 
                WHERE cr.member_id = %d 
                ORDER BY cr.created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $member_id));
    }

    /**
     * Get role requests
     *
     * @since    1.0.0
     * @param    string $status    Status filter
     * @return   array
     */
    public static function get_role_requests($status = 'pending') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgf_role_requests';
        $sql = "SELECT rr.*, u.display_name, u.user_email 
                FROM $table rr 
                LEFT JOIN {$wpdb->users} u ON rr.requester_user_id = u.ID 
                WHERE rr.status = %s 
                ORDER BY rr.created_at ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $status));
    }

    /**
     * Update role request status
     *
     * @since    1.0.0
     * @param    int       $request_id    Request ID
     * @param    string    $status        New status
     * @param    int       $reviewer_id   Reviewer user ID
     * @return   bool
     */
    public static function update_role_request($request_id, $status, $reviewer_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgf_role_requests';
        $data = array(
            'status' => $status,
            'reviewed_at' => current_time('mysql')
        );
        
        if ($reviewer_id) {
            $data['reviewed_by'] = $reviewer_id;
        }
        
        return $wpdb->update($table, $data, array('id' => $request_id));
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
        
        $stats = array();
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $stats['active'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'active'");
        $stats['pending'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'");
        $stats['expired'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'expired'");
        
        return $stats;
    }

    /**
     * Search members
     *
     * @since    1.0.0
     * @param    string $search_term    Search term
     * @param    int    $page          Page number
     * @param    int    $per_page      Items per page
     * @return   array
     */
    public static function search_members($search_term, $page = 1, $per_page = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT m.*, u.user_email, u.display_name 
                FROM $table m 
                LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
                WHERE m.membership_number LIKE %s 
                OR u.display_name LIKE %s 
                OR u.user_email LIKE %s 
                ORDER BY m.created_at DESC 
                LIMIT %d OFFSET %d";
        
        $search_like = '%' . $wpdb->esc_like($search_term) . '%';
        
        return $wpdb->get_results($wpdb->prepare($sql, $search_like, $search_like, $search_like, $per_page, $offset));
    }

    /**
     * Log audit trail
     *
     * @since    1.0.0
     * @param    array    $data    Audit data
     * @return   bool
     */
    public static function log_audit($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_audit_log';
        
        $defaults = array(
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'ip_address' => self::get_user_ip()
        );
        
        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert($table, $data);
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
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
}