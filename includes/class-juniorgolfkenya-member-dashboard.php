<?php
/**
 * Member Dashboard Class
 *
 * Handles dashboard data and statistics for members
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
 * Member Dashboard Class
 *
 * Provides methods to retrieve and display member dashboard data
 *
 * @since      1.0.0
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 * @author     Junior Golf Kenya
 */
class JuniorGolfKenya_Member_Dashboard {

    /**
     * Get member dashboard statistics
     *
     * @since    1.0.0
     * @param    int    $member_id    The member ID
     * @return   array                Dashboard statistics
     */
    public static function get_stats($member_id) {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
        
        // Get member basic info
        $member = $wpdb->get_row($wpdb->prepare("
            SELECT 
                m.*,
                u.user_email,
                u.display_name,
                u.user_registered
            FROM {$members_table} m
            LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
            WHERE m.id = %d
        ", $member_id));
        
        if (!$member) {
            return array();
        }
        
        // Get assigned coaches count
        $coaches_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$coach_members_table}
            WHERE member_id = %d 
            AND status = 'active'
        ", $member_id));
        
        // Get primary coach info
        $primary_coach = $wpdb->get_row($wpdb->prepare("
            SELECT 
                u.ID as coach_id,
                u.display_name as coach_name,
                u.user_email as coach_email
            FROM {$coach_members_table} cm
            INNER JOIN {$wpdb->users} u ON cm.coach_id = u.ID
            WHERE cm.member_id = %d 
            AND cm.is_primary = 1
            AND cm.status = 'active'
        ", $member_id));
        
        // Calculate membership duration
        $membership_duration = '';
        if ($member->date_joined) {
            $joined_date = new DateTime($member->date_joined);
            $now = new DateTime();
            $interval = $joined_date->diff($now);
            
            $years = $interval->y;
            $months = $interval->m;
            
            if ($years > 0) {
                $membership_duration = $years . ' year' . ($years > 1 ? 's' : '');
                if ($months > 0) {
                    $membership_duration .= ', ' . $months . ' month' . ($months > 1 ? 's' : '');
                }
            } elseif ($months > 0) {
                $membership_duration = $months . ' month' . ($months > 1 ? 's' : '');
            } else {
                $membership_duration = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
            }
        }
        
        // Calculate age
        $age = '';
        if ($member->date_of_birth) {
            $dob = new DateTime($member->date_of_birth);
            $now = new DateTime();
            $age = $dob->diff($now)->y;
        }
        
        return array(
            'member' => $member,
            'coaches_count' => (int) $coaches_count,
            'primary_coach' => $primary_coach,
            'membership_duration' => $membership_duration,
            'age' => $age,
            'profile_completion' => self::calculate_profile_completion($member)
        );
    }
    
    /**
     * Calculate profile completion percentage
     *
     * @since    1.0.0
     * @param    object    $member    The member object
     * @return   int                  Completion percentage (0-100)
     */
    private static function calculate_profile_completion($member) {
        $total_fields = 15;
        $completed_fields = 0;
        
        $fields_to_check = array(
            'first_name', 'last_name', 'phone', 'date_of_birth', 'gender',
            'address', 'club_name', 'handicap_index', 'emergency_contact_name',
            'emergency_contact_phone', 'biography', 'membership_number'
        );
        
        foreach ($fields_to_check as $field) {
            if (!empty($member->$field)) {
                $completed_fields++;
            }
        }
        
        // Check if has coaches
        if (!empty($member->coach_id)) {
            $completed_fields++;
        }
        
        // Check if has profile image
        if (!empty($member->user_id)) {
            $avatar_id = get_user_meta($member->user_id, 'jgk_profile_image', true);
            if ($avatar_id) {
                $completed_fields++;
            }
        }
        
        // Check if email exists
        if (!empty($member->user_email)) {
            $completed_fields++;
        }
        
        return round(($completed_fields / $total_fields) * 100);
    }
    
    /**
     * Get member's assigned coaches
     *
     * @since    1.0.0
     * @param    int    $member_id    The member ID
     * @return   array                Array of coach objects
     */
    public static function get_assigned_coaches($member_id) {
        global $wpdb;
        
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
        $coaches_table = $wpdb->prefix . 'jgk_coach_profiles';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as coach_id,
                u.display_name as coach_name,
                u.user_email as coach_email,
                cm.is_primary,
                cm.assigned_date as assigned_at,
                NULL as coach_phone,
                cp.specialties as specialization
            FROM {$coach_members_table} cm
            INNER JOIN {$wpdb->users} u ON cm.coach_id = u.ID
            LEFT JOIN {$coaches_table} cp ON u.ID = cp.user_id
            WHERE cm.member_id = %d 
            AND cm.status = 'active'
            ORDER BY cm.is_primary DESC, u.display_name ASC
        ", $member_id));
    }
    
    /**
     * Get member's recent activities
     *
     * @since    1.0.0
     * @param    int    $member_id    The member ID
     * @param    int    $limit        Limit results (default: 10)
     * @return   array                Array of activity records
     */
    public static function get_recent_activities($member_id, $limit = 10) {
        global $wpdb;
        
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
        $members_table = $wpdb->prefix . 'jgk_members';
        
        $activities = array();
        
        // Get coach assignments history
        $coach_assignments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                cm.assigned_date as date,
                'coach_assigned' as type,
                u.display_name as coach_name,
                cm.is_primary
            FROM {$coach_members_table} cm
            INNER JOIN {$wpdb->users} u ON cm.coach_id = u.ID
            WHERE cm.member_id = %d
            ORDER BY cm.assigned_date DESC
            LIMIT %d
        ", $member_id, $limit));
        
        foreach ($coach_assignments as $assignment) {
            $activities[] = array(
                'date' => $assignment->date,
                'type' => $assignment->type,
                'description' => ($assignment->is_primary ? 'Primary coach' : 'Coach') . ' assigned: ' . $assignment->coach_name,
                'icon' => 'admin-users'
            );
        }
        
        // Get status changes (from member table history - if implemented)
        // Placeholder for future activity logging
        
        // Sort by date descending
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return array_slice($activities, 0, $limit);
    }
    
    /**
     * Get member's upcoming events/sessions
     *
     * @since    1.0.0
     * @param    int    $member_id    The member ID
     * @param    int    $days         Days to look ahead (default: 30)
     * @return   array                Array of upcoming events
     */
    public static function get_upcoming_events($member_id, $days = 30) {
        // Placeholder for future events/sessions feature
        // This will be implemented when events management is added
        return array();
    }
    
    /**
     * Get member's progress data
     *
     * @since    1.0.0
     * @param    int    $member_id    The member ID
     * @return   array                Progress data
     */
    public static function get_progress_data($member_id) {
        // Placeholder for future progress tracking feature
        // This will include:
        // - Handicap history
        // - Skill improvements
        // - Goals and achievements
        // - Training sessions completed
        return array(
            'handicap_history' => array(),
            'achievements' => array(),
            'goals' => array()
        );
    }
    
    /**
     * Get member's payment history
     *
     * @since    1.0.0
     * @param    int    $member_id    The member ID
     * @param    int    $limit        Limit results (default: 10)
     * @return   array                Array of payment records
     */
    public static function get_payment_history($member_id, $limit = 10) {
        // Placeholder for future payment tracking feature
        // This will be implemented when payment management is added
        return array();
    }
    
    /**
     * Get member's parents/guardians
     *
     * @since    1.0.0
     * @param    int    $member_id    The member ID
     * @return   array                Array of parent/guardian records
     */
    public static function get_parents($member_id) {
        global $wpdb;
        
        $parents_table = $wpdb->prefix . 'jgk_parents_guardians';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$parents_table}'") != $parents_table) {
            return array();
        }
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM {$parents_table}
            WHERE member_id = %d
            ORDER BY 
                CASE relationship
                    WHEN 'father' THEN 1
                    WHEN 'mother' THEN 2
                    WHEN 'guardian' THEN 3
                    ELSE 4
                END
        ", $member_id));
    }
    
    /**
     * Get member's profile image URL
     *
     * @since    1.0.0
     * @param    int       $member_id    The member ID
     * @param    string    $size         Image size (default: 'thumbnail')
     * @return   string                  Image URL or empty string
     */
    public static function get_profile_image($member_id, $size = 'thumbnail') {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        
        $user_id = $wpdb->get_var($wpdb->prepare("
            SELECT user_id FROM {$members_table} WHERE id = %d
        ", $member_id));
        
        if ($user_id) {
            $avatar_id = get_user_meta($user_id, 'jgk_profile_image', true);
            if ($avatar_id) {
                $image = wp_get_attachment_image_src($avatar_id, $size);
                return $image ? $image[0] : '';
            }
        }
        
        return '';
    }
}
