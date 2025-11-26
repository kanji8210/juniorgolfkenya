<?php
/**
 * Parent Dashboard Class
 *
 * Handles dashboard functionality for parents managing multiple junior members
 *
 * @since      1.0.0
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

class JuniorGolfKenya_Parent_Dashboard {

    /**
     * Get all children (members) for a parent by email
     *
     * @since    1.0.0
     * @param    string    $parent_email    Parent's email address
     * @return   array                      Array of member objects with their details
     */
    public static function get_parent_children($parent_email) {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        $parents_table = $wpdb->prefix . 'jgk_parents_guardians';
        $users_table = $wpdb->users;
        
        // Get all members where this email is listed as a parent/guardian
        $query = $wpdb->prepare("
            SELECT DISTINCT
                m.id,
                m.user_id,
                m.first_name,
                m.last_name,
                m.membership_number,
                m.membership_type,
                m.status,
                m.date_of_birth,
                m.gender,
                m.phone,
                m.profile_photo,
                m.joined_date,
                m.expiry_date,
                u.user_email,
                pg.relationship,
                pg.is_primary_contact
            FROM {$parents_table} pg
            INNER JOIN {$members_table} m ON pg.member_id = m.id
            LEFT JOIN {$users_table} u ON m.user_id = u.ID
            WHERE pg.email = %s
            ORDER BY m.first_name, m.last_name
        ", $parent_email);
        
        return $wpdb->get_results($query);
    }

    /**
     * Get dashboard stats for a specific child
     *
     * @since    1.0.0
     * @param    int      $member_id    Member ID
     * @return   array                  Dashboard statistics
     */
    public static function get_child_stats($member_id) {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        $payments_table = $wpdb->prefix . 'jgk_payments';
        
        // Get member details
        $member = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, u.user_email 
             FROM {$members_table} m 
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
             WHERE m.id = %d",
            $member_id
        ));
        
        if (!$member) {
            return null;
        }
        
        // Get payment status
        $last_payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$payments_table} 
             WHERE member_id = %d AND status = 'completed' 
             ORDER BY created_at DESC LIMIT 1",
            $member_id
        ));
        
        // Get total paid
        $total_paid = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$payments_table} 
             WHERE member_id = %d AND status = 'completed'",
            $member_id
        ));
        
        // Get assigned coach
        $coach = $wpdb->get_row($wpdb->prepare(
            "SELECT u.display_name, u.user_email
             FROM {$wpdb->prefix}jgk_coach_members cm
             INNER JOIN {$wpdb->users} u ON cm.coach_id = u.ID
             WHERE cm.member_id = %d AND cm.is_primary = 1 AND cm.status = 'active'
             LIMIT 1",
            $member_id
        ));
        
        return array(
            'member' => $member,
            'last_payment' => $last_payment,
            'total_paid' => $total_paid ? floatval($total_paid) : 0,
            'coach' => $coach,
            'payment_status' => $member->status === 'active' ? 'paid' : 'pending',
            'membership_active' => $member->status === 'active'
        );
    }

    /**
     * Get recent activities for a child
     *
     * @since    1.0.0
     * @param    int      $member_id    Member ID
     * @param    int      $limit        Number of activities to retrieve
     * @return   array                  Array of activity objects
     */
    public static function get_child_activities($member_id, $limit = 5) {
        global $wpdb;
        
        $audit_table = $wpdb->prefix . 'jgk_audit_log';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$audit_table} 
             WHERE member_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $member_id,
            $limit
        ));
    }

    /**
     * Check if user is a parent (has children registered)
     *
     * @since    1.0.0
     * @param    string   $email    User's email address
     * @return   bool               True if user is a parent, false otherwise
     */
    public static function is_parent($email) {
        global $wpdb;
        
        $parents_table = $wpdb->prefix . 'jgk_parents_guardians';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$parents_table} WHERE email = %s",
            $email
        ));
        
        return $count > 0;
    }

    /**
     * Get payment summary for all children
     *
     * @since    1.0.0
     * @param    string   $parent_email    Parent's email
     * @return   array                     Payment summary data
     */
    public static function get_payment_summary($parent_email) {
        $children = self::get_parent_children($parent_email);
        
        $summary = array(
            'total_children' => count($children),
            'active_memberships' => 0,
            'pending_payments' => 0,
            'total_paid' => 0,
            'children_needing_payment' => array()
        );
        
        foreach ($children as $child) {
            $stats = self::get_child_stats($child->id);
            
            if ($stats) {
                $summary['total_paid'] += $stats['total_paid'];
                
                if ($child->status === 'active') {
                    $summary['active_memberships']++;
                } elseif ($child->status === 'approved') {
                    $summary['pending_payments']++;
                    $summary['children_needing_payment'][] = $child;
                }
            }
        }
        
        return $summary;
    }

    /**
     * Get parent information by email
     *
     * @since    1.0.0
     * @param    string   $email    Parent's email
     * @return   object|null        Parent information or null
     */
    public static function get_parent_info($email) {
        global $wpdb;
        
        $parents_table = $wpdb->prefix . 'jgk_parents_guardians';
        
        // Get parent info from first child's record (all should be same)
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$parents_table} 
             WHERE email = %s 
             ORDER BY is_primary_contact DESC 
             LIMIT 1",
            $email
        ));
    }
}
