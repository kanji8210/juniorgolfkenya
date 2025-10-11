<?php
/**
 * Member Data Management
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
 * Member Data Management Class
 *
 * Handles member profile data, competitions, trophies, and performance tracking.
 *
 * @since      1.0.0
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 * @author     Junior Golf Kenya <info@juniorgolfkenya.com>
 */
class JuniorGolfKenya_Member_Data {

    /**
     * Get membership expiration status
     *
     * @param object $member Member object with registration_date or expiry_date
     * @return array Status information with color, message, and days remaining
     */
    public static function get_membership_status($member) {
        $status = array(
            'is_expiring_soon' => false,
            'is_expired' => false,
            'days_remaining' => 0,
            'color' => 'green',
            'bg_color' => '',
            'message' => 'Active',
            'alert_type' => 'success'
        );

        // Check if expiry_date exists, otherwise calculate from registration_date
        if (!empty($member->expiry_date)) {
            $expiry_date = strtotime($member->expiry_date);
        } elseif (!empty($member->registration_date)) {
            // Assume 1 year membership from registration
            $expiry_date = strtotime($member->registration_date . ' +1 year');
        } else {
            return $status; // No dates available
        }

        $today = time();
        $days_remaining = floor(($expiry_date - $today) / (60 * 60 * 24));
        
        $status['days_remaining'] = $days_remaining;
        $status['expiry_date'] = date('F j, Y', $expiry_date);

        // Expired (red alert)
        if ($days_remaining < 0) {
            $status['is_expired'] = true;
            $status['color'] = '#dc3545';
            $status['bg_color'] = '#f8d7da';
            $status['message'] = 'Membership Expired - Renew Now!';
            $status['alert_type'] = 'danger';
            $status['icon'] = 'warning';
        }
        // Expiring soon - less than 60 days (yellow warning)
        elseif ($days_remaining <= 60) {
            $status['is_expiring_soon'] = true;
            $status['color'] = '#856404';
            $status['bg_color'] = '#fff3cd';
            $status['message'] = 'Membership expires in ' . $days_remaining . ' days - Renew Soon!';
            $status['alert_type'] = 'warning';
            $status['icon'] = 'clock';
        }
        // Active
        else {
            $status['message'] = 'Active - Expires on ' . date('M j, Y', $expiry_date);
            $status['icon'] = 'yes-alt';
        }

        return $status;
    }

    /**
     * Get member's upcoming competitions
     *
     * @param int $member_id Member ID
     * @param int $limit Number of competitions to retrieve
     * @return array Array of competition objects
     */
    public static function get_upcoming_competitions($member_id, $limit = 5) {
        global $wpdb;
        
        // For now, return sample data until competitions table is created
        // TODO: Implement actual competitions table and query
        
        $competitions = array(
            (object) array(
                'id' => 1,
                'name' => 'Junior Golf Kenya Championship',
                'date' => date('Y-m-d', strtotime('+14 days')),
                'location' => 'Karen Country Club',
                'category' => 'Championship',
                'status' => 'registered'
            ),
            (object) array(
                'id' => 2,
                'name' => 'Youth Open Tournament',
                'date' => date('Y-m-d', strtotime('+21 days')),
                'location' => 'Muthaiga Golf Club',
                'category' => 'Open',
                'status' => 'pending'
            ),
            (object) array(
                'id' => 3,
                'name' => 'Inter-Club Challenge',
                'date' => date('Y-m-d', strtotime('+30 days')),
                'location' => 'Windsor Golf Club',
                'category' => 'Team Event',
                'status' => 'registered'
            )
        );

        return array_slice($competitions, 0, $limit);
    }

    /**
     * Get member's past competitions with results
     *
     * @param int $member_id Member ID
     * @param int $limit Number of competitions to retrieve
     * @return array Array of competition result objects
     */
    public static function get_past_competitions($member_id, $limit = 10) {
        global $wpdb;
        
        // Sample data until competitions results table is created
        // TODO: Implement actual competition results table
        
        $past_competitions = array(
            (object) array(
                'id' => 1,
                'name' => 'Summer Classic 2024',
                'date' => date('Y-m-d', strtotime('-30 days')),
                'location' => 'Karen Country Club',
                'position' => 3,
                'score' => 72,
                'participants' => 45,
                'handicap_used' => 12.5
            ),
            (object) array(
                'id' => 2,
                'name' => 'Junior Masters 2024',
                'date' => date('Y-m-d', strtotime('-60 days')),
                'location' => 'Muthaiga Golf Club',
                'position' => 5,
                'score' => 75,
                'participants' => 60,
                'handicap_used' => 13.0
            ),
            (object) array(
                'id' => 3,
                'name' => 'Spring Open 2024',
                'date' => date('Y-m-d', strtotime('-90 days')),
                'location' => 'Windsor Golf Club',
                'position' => 1,
                'score' => 68,
                'participants' => 38,
                'handicap_used' => 13.5
            )
        );

        return array_slice($past_competitions, 0, $limit);
    }

    /**
     * Get member's trophies and achievements
     *
     * @param int $member_id Member ID
     * @return array Array of trophy objects
     */
    public static function get_trophies($member_id) {
        global $wpdb;
        
        // Sample data until trophies table is created
        // TODO: Implement actual trophies/achievements table
        
        $trophies = array(
            (object) array(
                'id' => 1,
                'name' => 'Spring Open Champion 2024',
                'type' => 'gold',
                'date' => date('Y-m-d', strtotime('-90 days')),
                'competition' => 'Spring Open 2024',
                'category' => 'Junior Category'
            ),
            (object) array(
                'id' => 2,
                'name' => 'Summer Classic - 3rd Place',
                'type' => 'bronze',
                'date' => date('Y-m-d', strtotime('-30 days')),
                'competition' => 'Summer Classic 2024',
                'category' => 'Youth Division'
            ),
            (object) array(
                'id' => 3,
                'name' => 'Most Improved Player 2024',
                'type' => 'special',
                'date' => date('Y-m-d', strtotime('-120 days')),
                'competition' => 'Annual Awards',
                'category' => 'Special Achievement'
            )
        );

        return $trophies;
    }

    /**
     * Get member's performance statistics
     *
     * @param int $member_id Member ID
     * @return array Performance data with statistics
     */
    public static function get_performance_stats($member_id) {
        global $wpdb;
        
        // Sample data until performance tracking is fully implemented
        // TODO: Calculate actual statistics from competition results
        
        $stats = array(
            'competitions_played' => 12,
            'wins' => 2,
            'top_3_finishes' => 5,
            'top_10_finishes' => 9,
            'average_score' => 73.5,
            'best_score' => 68,
            'current_handicap' => 12.5,
            'handicap_improvement' => -2.5, // Negative means improvement
            'total_rounds' => 45,
            'birdies' => 23,
            'eagles' => 2,
            'pars' => 245,
            'participation_rate' => 85, // Percentage of available competitions attended
            'trend' => 'improving' // improving, declining, stable
        );

        return $stats;
    }

    /**
     * Get member's performance chart data
     *
     * @param int $member_id Member ID
     * @param int $months Number of months to retrieve
     * @return array Chart data for performance over time
     */
    public static function get_performance_chart_data($member_id, $months = 6) {
        // Sample data for chart visualization
        // TODO: Calculate from actual competition results
        
        $data = array();
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('M Y', strtotime("-$i months"));
            $data[] = array(
                'month' => $month,
                'average_score' => rand(70, 80),
                'handicap' => 13.5 - ($months - $i) * 0.2, // Improving trend
                'competitions' => rand(1, 3)
            );
        }

        return $data;
    }

    /**
     * Check if member can edit certain profile fields
     *
     * @param string $field Field name
     * @return bool Whether field is editable by member
     */
    public static function is_field_editable($field) {
        // Fields that members CAN edit
        $editable_fields = array(
            'phone',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'medical_conditions',
            'biography',
            'club_affiliation',
            'profile_image'
        );

        // Fields that only ADMIN can edit
        $admin_only_fields = array(
            'first_name',
            'last_name',
            'email',
            'membership_type',
            'status',
            'membership_number',
            'registration_date',
            'expiry_date',
            'coach_id',
            'handicap' // Handicap updated by coach/admin only
        );

        return in_array($field, $editable_fields);
    }

    /**
     * Update member profile (member-editable fields only)
     *
     * @param int $member_id Member ID
     * @param array $data Profile data to update
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function update_member_profile($member_id, $data) {
        global $wpdb;
        $members_table = $wpdb->prefix . 'jgk_members';

        // Filter to only allow editable fields
        $allowed_updates = array();
        foreach ($data as $field => $value) {
            if (self::is_field_editable($field)) {
                $allowed_updates[$field] = sanitize_text_field($value);
            }
        }

        if (empty($allowed_updates)) {
            return new WP_Error('no_updates', 'No valid fields to update.');
        }

        $result = $wpdb->update(
            $members_table,
            $allowed_updates,
            array('id' => $member_id),
            null,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update member profile.');
        }

        return true;
    }
}
