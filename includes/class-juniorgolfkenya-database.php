<?php

/**
 * Database operations manager
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0    /**
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
        
        return $wpdb->update($table, $data, array('id' => $member_id));
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
    }    JuniorGolfKenya
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
        
        // Basic sanitization and validation
        $allowed_statuses = array('pending', 'active', 'expired', 'suspended');

        if (isset($data['status']) && !in_array($data['status'], $allowed_statuses, true)) {
            // Normalize unknown status to pending and log
            $invalid_status = $data['status'];
            $data['status'] = 'pending';
            error_log("[JuniorGolfKenya] create_member: invalid status provided (normalized to 'pending'): {$invalid_status}");
        }

        // Sanitize common fields if present
        if (isset($data['membership_number'])) {
            $data['membership_number'] = sanitize_text_field($data['membership_number']);
        }
        if (isset($data['membership_type'])) {
            $data['membership_type'] = sanitize_text_field($data['membership_type']);
        }
        if (isset($data['full_name'])) {
            $data['full_name'] = sanitize_text_field($data['full_name']);
        }
        if (isset($data['user_id'])) {
            $data['user_id'] = intval($data['user_id']);
        }
        if (isset($data['email'])) {
            $data['email'] = sanitize_email($data['email']);
        }
        if (isset($data['phone'])) {
            $data['phone'] = sanitize_text_field($data['phone']);
        }
        if (isset($data['address'])) {
            $data['address'] = sanitize_textarea_field($data['address']);
        }

        // Ensure created_at is in proper format
        if (empty($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }

        // Attempt insert and capture debug info on failure
        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        // On failure, capture SQL error and last query for debugging
        $last_error = isset($wpdb->last_error) ? $wpdb->last_error : 'unknown_error';
        $last_query = isset($wpdb->last_query) ? $wpdb->last_query : '';

        $debug_message = sprintf(
            "[JuniorGolfKenya][ERROR] create_member failed: %s | query: %s | data: %s",
            $last_error,
            $last_query,
            wp_json_encode($data)
        );

        // PHP error log (visible in server logs)
        error_log($debug_message);

        // Try to insert a debug row into audit table if it exists
        $audit_table = $wpdb->prefix . 'jgk_audit_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '$audit_table'") == $audit_table) {
            $wpdb->insert(
                $audit_table,
                array(
                    'user_id' => get_current_user_id(),
                    'action' => 'create_member_failed',
                    'object_type' => 'member',
                    'details' => $debug_message,
                    'ip_address' => self::get_user_ip(),
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );
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