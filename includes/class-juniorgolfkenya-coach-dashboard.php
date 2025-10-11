<?php
/**
 * Coach Dashboard Class
 *
 * Handles dashboard data and statistics for coaches
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Coach Dashboard Class
 *
 * Provides methods to retrieve and display coach dashboard data
 *
 * @since      1.0.0
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 * @author     Junior Golf Kenya
 */
class JuniorGolfKenya_Coach_Dashboard {

    /**
     * Get coach dashboard statistics
     *
     * @since    1.0.0
     * @param    int    $coach_id    The coach user ID
     * @return   array               Dashboard statistics
     */
    public static function get_stats($coach_id) {
        global $wpdb;
        
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
        $members_table = $wpdb->prefix . 'jgk_members';
        
        // Get total assigned members
        $total_members = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT cm.member_id)
            FROM {$coach_members_table} cm
            INNER JOIN {$members_table} m ON cm.member_id = m.id
            WHERE cm.coach_id = %d 
            AND cm.status = 'active'
            AND m.status IN ('active', 'approved')
        ", $coach_id));
        
        // Get primary members count
        $primary_members = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT cm.member_id)
            FROM {$coach_members_table} cm
            INNER JOIN {$members_table} m ON cm.member_id = m.id
            WHERE cm.coach_id = %d 
            AND cm.is_primary = 1
            AND cm.status = 'active'
            AND m.status IN ('active', 'approved')
        ", $coach_id));
        
        // Get members by status
        $members_by_status = $wpdb->get_results($wpdb->prepare("
            SELECT m.status, COUNT(DISTINCT m.id) as count
            FROM {$coach_members_table} cm
            INNER JOIN {$members_table} m ON cm.member_id = m.id
            WHERE cm.coach_id = %d 
            AND cm.status = 'active'
            GROUP BY m.status
        ", $coach_id), OBJECT_K);
        
        // Get members by type
        $members_by_type = $wpdb->get_results($wpdb->prepare("
            SELECT m.membership_type, COUNT(DISTINCT m.id) as count
            FROM {$coach_members_table} cm
            INNER JOIN {$members_table} m ON cm.member_id = m.id
            WHERE cm.coach_id = %d 
            AND cm.status = 'active'
            AND m.status IN ('active', 'approved')
            GROUP BY m.membership_type
        ", $coach_id), OBJECT_K);
        
        // Get recent activities (last 10)
        $recent_activities = $wpdb->get_results($wpdb->prepare("
            SELECT 
                cm.member_id,
                cm.assigned_at,
                cm.is_primary,
                CONCAT(m.first_name, ' ', m.last_name) as member_name,
                m.membership_type,
                m.status as member_status
            FROM {$coach_members_table} cm
            INNER JOIN {$members_table} m ON cm.member_id = m.id
            WHERE cm.coach_id = %d 
            AND cm.status = 'active'
            ORDER BY cm.assigned_at DESC
            LIMIT 10
        ", $coach_id));
        
        // Get gender distribution
        $gender_distribution = $wpdb->get_results($wpdb->prepare("
            SELECT 
                m.gender,
                COUNT(DISTINCT m.id) as count
            FROM {$coach_members_table} cm
            INNER JOIN {$members_table} m ON cm.member_id = m.id
            WHERE cm.coach_id = %d 
            AND cm.status = 'active'
            AND m.status IN ('active', 'approved')
            GROUP BY m.gender
        ", $coach_id), OBJECT_K);
        
        return array(
            'total_members' => (int) $total_members,
            'primary_members' => (int) $primary_members,
            'secondary_members' => (int) $total_members - (int) $primary_members,
            'active_members' => isset($members_by_status['active']) ? (int) $members_by_status['active']->count : 0,
            'pending_members' => isset($members_by_status['pending']) ? (int) $members_by_status['pending']->count : 0,
            'members_by_type' => $members_by_type,
            'members_by_status' => $members_by_status,
            'gender_distribution' => $gender_distribution,
            'recent_activities' => $recent_activities
        );
    }
    
    /**
     * Get coach's assigned members list
     *
     * @since    1.0.0
     * @param    int       $coach_id    The coach user ID
     * @param    string    $status      Filter by member status (optional)
     * @param    int       $limit       Limit results (default: 50)
     * @param    int       $offset      Offset for pagination (default: 0)
     * @return   array                  Array of member objects
     */
    public static function get_assigned_members($coach_id, $status = '', $limit = 50, $offset = 0) {
        global $wpdb;
        
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
        $members_table = $wpdb->prefix . 'jgk_members';
        $users_table = $wpdb->users;
        
        $where = "cm.coach_id = %d AND cm.status = 'active'";
        $params = array($coach_id);
        
        if (!empty($status)) {
            $where .= " AND m.status = %s";
            $params[] = $status;
        }
        
        $query = $wpdb->prepare("
            SELECT 
                m.*,
                u.user_email,
                u.display_name,
                cm.is_primary,
                cm.assigned_at
            FROM {$coach_members_table} cm
            INNER JOIN {$members_table} m ON cm.member_id = m.id
            LEFT JOIN {$users_table} u ON m.user_id = u.ID
            WHERE {$where}
            ORDER BY cm.is_primary DESC, m.first_name ASC, m.last_name ASC
            LIMIT %d OFFSET %d
        ", array_merge($params, array($limit, $offset)));
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get upcoming events/sessions for coach
     *
     * @since    1.0.0
     * @param    int    $coach_id    The coach user ID
     * @param    int    $days        Number of days to look ahead (default: 30)
     * @return   array               Array of upcoming events
     */
    public static function get_upcoming_events($coach_id, $days = 30) {
        // Placeholder for future events/sessions feature
        // This will be implemented when events management is added
        return array();
    }
    
    /**
     * Get coach performance metrics
     *
     * @since    1.0.0
     * @param    int       $coach_id    The coach user ID
     * @param    string    $period      Period for metrics (week, month, year)
     * @return   array                  Performance metrics
     */
    public static function get_performance_metrics($coach_id, $period = 'month') {
        global $wpdb;
        
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
        
        // Calculate date range based on period
        $date_condition = '';
        switch ($period) {
            case 'week':
                $date_condition = "DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'year':
                $date_condition = "DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            case 'month':
            default:
                $date_condition = "DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
        
        // Get new members assigned in period
        $new_members = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$coach_members_table}
            WHERE coach_id = %d 
            AND status = 'active'
            AND assigned_at >= {$date_condition}
        ", $coach_id));
        
        // Get removed members in period
        $removed_members = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$coach_members_table}
            WHERE coach_id = %d 
            AND status = 'inactive'
            AND updated_at >= {$date_condition}
        ", $coach_id));
        
        return array(
            'period' => $period,
            'new_members' => (int) $new_members,
            'removed_members' => (int) $removed_members,
            'net_change' => (int) $new_members - (int) $removed_members
        );
    }
    
    /**
     * Get coach profile information
     *
     * @since    1.0.0
     * @param    int    $coach_id    The coach user ID
     * @return   object              Coach profile data
     */
    public static function get_coach_profile($coach_id) {
        global $wpdb;
        
        $coaches_table = $wpdb->prefix . 'jgk_coach_profiles';
        $users_table = $wpdb->users;
        
        $coach = $wpdb->get_row($wpdb->prepare("
            SELECT 
                c.*,
                u.user_email,
                u.display_name,
                u.user_registered
            FROM {$coaches_table} c
            LEFT JOIN {$users_table} u ON c.user_id = u.ID
            WHERE c.user_id = %d
        ", $coach_id));
        
        if ($coach) {
            // Get profile image URL
            $avatar_id = get_user_meta($coach_id, 'jgk_profile_image', true);
            if ($avatar_id) {
                $coach->profile_image = wp_get_attachment_url($avatar_id);
            } else {
                $coach->profile_image = '';
            }
        }
        
        return $coach;
    }
    
    /**
     * Get member progress tracking data
     *
     * @since    1.0.0
     * @param    int    $coach_id     The coach user ID
     * @param    int    $member_id    The member ID (optional)
     * @return   array                Progress tracking data
     */
    public static function get_member_progress($coach_id, $member_id = 0) {
        // Placeholder for future progress tracking feature
        // This will be implemented when progress tracking is added
        return array();
    }
}
