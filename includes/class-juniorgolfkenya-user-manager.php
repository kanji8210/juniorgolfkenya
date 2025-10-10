<?php

/**
 * User management operations
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

/**
 * User management class.
 *
 * This class handles user-related operations and role management.
 */
class JuniorGolfKenya_User_Manager {

    /**
     * Create new member user
     *
     * @since    1.0.0
     * @param    array    $user_data      User data
     * @param    array    $member_data    Member-specific data
     * @param    array    $parent_data    Optional parent/guardian data for members under 18
     * @return   array                    Result with user_id and member_id or error
     */
    public static function create_member_user($user_data, $member_data = array(), $parent_data = array()) {
        // Validate required fields
        if (empty($user_data['user_email']) || empty($user_data['user_login'])) {
            return array('success' => false, 'message' => 'Email and username are required');
        }

        // Check if user already exists
        if (username_exists($user_data['user_login']) || email_exists($user_data['user_email'])) {
            return array('success' => false, 'message' => 'User already exists');
        }

        // Create WordPress user
        $user_id = wp_create_user(
            $user_data['user_login'],
            $user_data['user_pass'] ?? wp_generate_password(),
            $user_data['user_email']
        );

        if (is_wp_error($user_id)) {
            return array('success' => false, 'message' => $user_id->get_error_message());
        }

        // Update user profile
        if (isset($user_data['display_name'])) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $user_data['display_name']
            ));
        }

        // Assign member role
        $user = new WP_User($user_id);
        $user->set_role('jgk_member'); // Fixed: Changed from jgf_member to jgk_member

        // Create member record
        $member_data['user_id'] = $user_id;
        $member_id = JuniorGolfKenya_Database::create_member($member_data);

        if (!$member_id) {
            // Rollback user creation if member creation fails
            wp_delete_user($user_id);
            return array('success' => false, 'message' => 'Failed to create member record');
        }

        // Handle parent/guardian data if member is under 18
        $parents_manager = new JuniorGolfKenya_Parents();
        $parent_errors = array();
        
        if ($parents_manager->requires_parent_info($member_id)) {
            // At least one parent/guardian is required for minors
            if (empty($parent_data)) {
                // Rollback member and user creation
                JuniorGolfKenya_Database::delete_member($member_id);
                wp_delete_user($user_id);
                return array('success' => false, 'message' => 'Parent/guardian information is required for members under 18');
            }
            
            // Add parent/guardian records
            foreach ($parent_data as $parent) {
                $result = $parents_manager->add_parent($member_id, $parent);
                if ($result === false) {
                    $parent_errors[] = 'Failed to add parent: ' . ($parent['first_name'] ?? 'Unknown') . ' ' . ($parent['last_name'] ?? 'Unknown');
                }
            }
            
            // Check if at least one parent was added successfully
            $member_parents = $parents_manager->get_member_parents($member_id);
            if (empty($member_parents)) {
                // Rollback member and user creation
                JuniorGolfKenya_Database::delete_member($member_id);
                wp_delete_user($user_id);
                return array(
                    'success' => false, 
                    'message' => 'Failed to add parent/guardian information: ' . implode(', ', $parent_errors)
                );
            }
        } else if (!empty($parent_data)) {
            // Add parent data even if not required (member is 18+)
            foreach ($parent_data as $parent) {
                $parents_manager->add_parent($member_id, $parent);
            }
        }

        // Log the creation
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'member_created',
            'object_type' => 'member',
            'object_id' => $member_id,
            'new_values' => json_encode(array('user_id' => $user_id, 'member_id' => $member_id))
        ));

        $response = array(
            'success' => true,
            'user_id' => $user_id,
            'member_id' => $member_id,
            'message' => 'Member created successfully'
        );
        
        // Add warnings about parent data if any
        if (!empty($parent_errors)) {
            $response['warnings'] = $parent_errors;
        }

        return $response;
    }

    /**
     * Approve member account
     *
     * @since    1.0.0
     * @param    int    $member_id    Member ID
     * @return   bool
     */
    public static function approve_member($member_id) {
        $member = JuniorGolfKenya_Database::get_member($member_id);
        
        if (!$member) {
            return false;
        }

        // Update member status
        $result = JuniorGolfKenya_Database::update_member($member_id, array('status' => 'active'));

        if ($result) {
            // Send approval email
            self::send_approval_email($member->user_id);

            // Log the approval
            JuniorGolfKenya_Database::log_audit(array(
                'action' => 'member_approved',
                'object_type' => 'member',
                'object_id' => $member_id,
                'old_values' => json_encode(array('status' => $member->status)),
                'new_values' => json_encode(array('status' => 'active'))
            ));

            return true;
        }

        return false;
    }

    /**
     * Assign coach to member
     *
     * @since    1.0.0
     * @param    int    $member_id    Member ID
     * @param    int    $coach_id     Coach User ID
     * @return   bool
     */
    public static function assign_coach($member_id, $coach_id) {
        // Verify coach has proper role
        $coach_user = get_user_by('ID', $coach_id);
        if (!$coach_user || !in_array('jgk_coach', $coach_user->roles)) { // Fixed: Changed from jgf_coach to jgk_coach
            return false;
        }

        // Update member record with assigned coach
        $result = JuniorGolfKenya_Database::update_member($member_id, array(
            'coach_id' => $coach_id
        ));

        if ($result) {
            // Log the assignment
            JuniorGolfKenya_Database::log_audit(array(
                'action' => 'coach_assigned',
                'object_type' => 'member',
                'object_id' => $member_id,
                'new_values' => json_encode(array('coach_id' => $coach_id))
            ));

            return true;
        }

        return false;
    }

    /**
     * Process role request
     *
     * @since    1.0.0
     * @param    int       $request_id    Role request ID
     * @param    bool      $approve       Whether to approve or deny
     * @param    string    $reason        Reason for decision
     * @return   bool
     */
    public static function process_role_request($request_id, $approve, $reason = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'jgk_role_requests'; // Fixed: Changed from jgf to jgk
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $request_id));

        if (!$request) {
            return false;
        }

        $status = $approve ? 'approved' : 'denied';
        
        // Update request status
        $updated = JuniorGolfKenya_Database::update_role_request($request_id, $status, get_current_user_id());

        if ($updated && $approve) {
            // Assign the requested role
            $user = new WP_User($request->requester_user_id);
            $user->add_role($request->requested_role);

            // If becoming a coach, create coach profile
            if ($request->requested_role === 'jgk_coach') { // Fixed: Changed from jgf_coach to jgk_coach
                self::create_coach_profile($request->requester_user_id);
            }

            // Send approval notification
            self::send_role_approval_email($request->requester_user_id, $request->requested_role);
        } else if ($updated && !$approve) {
            // Send denial notification
            self::send_role_denial_email($request->requester_user_id, $request->requested_role, $reason);
        }

        // Log the decision
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'role_request_processed',
            'object_type' => 'role_request',
            'object_id' => $request_id,
            'new_values' => json_encode(array(
                'status' => $status,
                'reason' => $reason,
                'processed_by' => get_current_user_id()
            ))
        ));

        return true;
    }

    /**
     * Create coach profile
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   bool
     */
    public static function create_coach_profile($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'jgk_coach_profiles'; // Fixed: Changed from jgf to jgk
        
        // Check if profile already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE user_id = %d", $user_id));
        
        if ($exists) {
            return true;
        }

        return $wpdb->insert($table, array(
            'user_id' => $user_id,
            'verification_status' => 'pending',
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Get users by role
     *
     * @since    1.0.0
     * @param    string $role    Role name
     * @return   array
     */
    public static function get_users_by_role($role) {
        return get_users(array('role' => $role));
    }

    /**
     * Get available coaches
     *
     * @since    1.0.0
     * @return   array
     */
    public static function get_available_coaches() {
        global $wpdb;

        $table = $wpdb->prefix . 'jgk_coach_profiles';
        $sql = "SELECT cp.*, u.display_name, u.user_email 
                FROM $table cp 
                LEFT JOIN {$wpdb->users} u ON cp.user_id = u.ID 
                WHERE cp.verification_status = 'approved' 
                ORDER BY u.display_name";

        return $wpdb->get_results($sql);
    }

    /**
     * Send approval email
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   bool
     */
    private static function send_approval_email($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        $subject = 'Your Junior Golf Kenya membership has been approved!';
        $message = sprintf(
            'Dear %s,

Your membership application has been approved! You can now access your member portal and start enjoying all the benefits of Junior Golf Kenya membership.

Login to your member portal: %s

Best regards,
Junior Golf Kenya Team',
            $user->display_name,
            home_url('/jgk-member-portal/')
        );

        return wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Send role approval email
     *
     * @since    1.0.0
     * @param    int       $user_id    User ID
     * @param    string    $role       Role name
     * @return   bool
     */
    private static function send_role_approval_email($user_id, $role) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        $role_labels = array(
            'jgk_coach' => 'Coach', // Fixed: Changed from jgf_coach to jgk_coach
            'jgk_staff' => 'Staff'  // Fixed: Changed from jgf_staff to jgk_staff
        );

        $role_label = $role_labels[$role] ?? $role;

        $subject = sprintf('Your %s role request has been approved!', $role_label);
        $message = sprintf(
            'Dear %s,

Your request for %s role has been approved! You now have access to additional features and responsibilities.

Login to your dashboard: %s

Best regards,
Junior Golf Kenya Team',
            $user->display_name,
            $role_label,
            admin_url()
        );

        return wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Send role denial email
     *
     * @since    1.0.0
     * @param    int       $user_id    User ID
     * @param    string    $role       Role name
     * @param    string    $reason     Denial reason
     * @return   bool
     */
    private static function send_role_denial_email($user_id, $role, $reason = '') {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        $role_labels = array(
            'jgk_coach' => 'Coach', // Fixed: Changed from jgf_coach to jgk_coach
            'jgk_staff' => 'Staff'  // Fixed: Changed from jgf_staff to jgk_staff
        );

        $role_label = $role_labels[$role] ?? $role;

        $subject = sprintf('Your %s role request update', $role_label);
        $reason_text = $reason ? "\n\nReason: " . $reason : '';
        
        $message = sprintf(
            'Dear %s,

Thank you for your interest in becoming a %s with Junior Golf Kenya. After careful review, we are unable to approve your request at this time.%s

You may reapply in the future or contact us for more information.

Best regards,
Junior Golf Kenya Team',
            $user->display_name,
            $role_label,
            $reason_text
        );

        return wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Check if user can manage members
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID (optional, defaults to current user)
     * @return   bool
     */
    public static function can_manage_members($user_id = null) {
        if ($user_id) {
            $user = get_user_by('ID', $user_id);
            return $user && user_can($user, 'edit_members');
        }
        
        return current_user_can('edit_members');
    }

    /**
     * Check if user can approve role requests
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID (optional, defaults to current user)
     * @return   bool
     */
    public static function can_approve_roles($user_id = null) {
        if ($user_id) {
            $user = get_user_by('ID', $user_id);
            return $user && user_can($user, 'approve_role_requests');
        }
        
        return current_user_can('approve_role_requests');
    }
}