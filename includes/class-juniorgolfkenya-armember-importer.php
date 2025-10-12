<?php
/**
 * ARMember Importer Class
 *
 * Handles importing member data from ARMember plugin
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 * @since      1.0.0
 */

class JuniorGolfKenya_ARMember_Importer {

    /**
     * Check if ARMember plugin is active
     *
     * @since    1.0.0
     * @return   bool
     */
    public static function is_armember_active() {
        return class_exists('ARMember');
    }

    /**
     * Get ARMember database tables
     *
     * @since    1.0.0
     * @return   array
     */
    public static function get_armember_tables() {
        global $wpdb;
        
        return array(
            'members' => $wpdb->prefix . 'arm_members',
            'membership' => $wpdb->prefix . 'arm_membership_setup',
            'payment_log' => $wpdb->prefix . 'arm_payment_log',
            'user_memberships' => $wpdb->prefix . 'arm_members_activity'
        );
    }

    /**
     * Get total count of ARMember members
     *
     * @since    1.0.0
     * @return   int
     */
    public static function get_armember_members_count() {
        global $wpdb;
        
        if (!self::is_armember_active()) {
            return 0;
        }

        $tables = self::get_armember_tables();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$tables['members']}'");
        if (!$table_exists) {
            return 0;
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['members']}");
    }

    /**
     * Get ARMember members with pagination
     *
     * @since    1.0.0
     * @param    int    $offset    Offset for pagination
     * @param    int    $limit     Limit per page
     * @return   array
     */
    public static function get_armember_members($offset = 0, $limit = 50) {
        global $wpdb;
        
        if (!self::is_armember_active()) {
            return array();
        }

        $tables = self::get_armember_tables();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$tables['members']}'");
        if (!$table_exists) {
            return array();
        }

        $query = $wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.user_login,
                u.user_email,
                u.display_name,
                um_first.meta_value as first_name,
                um_last.meta_value as last_name,
                um_phone.meta_value as phone,
                um_dob.meta_value as date_of_birth,
                um_gender.meta_value as gender,
                am.arm_user_type,
                am.arm_primary_status
            FROM {$wpdb->users} u
            LEFT JOIN {$tables['members']} am ON u.ID = am.arm_user_id
            LEFT JOIN {$wpdb->usermeta} um_first ON u.ID = um_first.user_id AND um_first.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um_last ON u.ID = um_last.user_id AND um_last.meta_key = 'last_name'
            LEFT JOIN {$wpdb->usermeta} um_phone ON u.ID = um_phone.user_id AND um_phone.meta_key = 'phone'
            LEFT JOIN {$wpdb->usermeta} um_dob ON u.ID = um_dob.user_id AND um_dob.meta_key = 'date_of_birth'
            LEFT JOIN {$wpdb->usermeta} um_gender ON u.ID = um_gender.user_id AND um_gender.meta_key = 'gender'
            WHERE am.arm_user_id IS NOT NULL
            ORDER BY u.ID ASC
            LIMIT %d OFFSET %d
        ", $limit, $offset);

        return $wpdb->get_results($query);
    }

    /**
     * Import a single member from ARMember to JGK
     *
     * @since    1.0.0
     * @param    object    $armember_data    ARMember user data
     * @param    array     $options          Import options
     * @return   array                       Result array with success status and message
     */
    public static function import_member($armember_data, $options = array()) {
        global $wpdb;

        $defaults = array(
            'skip_existing' => true,
            'update_existing' => false,
            'force_junior_type' => true,
            'default_status' => 'active'
        );
        $options = array_merge($defaults, $options);

        // Check if user already exists in JGK
        $jgk_members_table = $wpdb->prefix . 'jgk_members';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$jgk_members_table} WHERE user_id = %d",
            $armember_data->user_id
        ));

        if ($existing) {
            if ($options['skip_existing']) {
                return array(
                    'success' => false,
                    'message' => 'Member already exists (skipped)',
                    'status' => 'skipped'
                );
            } elseif ($options['update_existing']) {
                // Update existing member
                return self::update_existing_member($existing, $armember_data, $options);
            }
        }

        // Calculate age from date of birth (for informational purposes only)
        $age = null;
        if (!empty($armember_data->date_of_birth)) {
            try {
                $birthdate = new DateTime($armember_data->date_of_birth);
                $today = new DateTime();
                $age = $today->diff($birthdate)->y;
            } catch (Exception $e) {
                // Invalid date format - continue anyway
            }
        }

        // Determine membership type - always use 'junior' or force type
        $membership_type = 'junior';
        if (isset($options['force_junior_type'])) {
            $membership_type = $options['force_junior_type'] ? 'junior' : 'junior';
        }

        // No age validation - import all members regardless of age

        // Prepare member data
        $member_data = array(
            'user_id' => $armember_data->user_id,
            'membership_type' => $membership_type,
            'status' => self::map_armember_status($armember_data->arm_primary_status, $options['default_status']),
            'date_of_birth' => $armember_data->date_of_birth ?: null,
            'gender' => $armember_data->gender ?: null,
            'phone' => $armember_data->phone ?: null,
            'date_joined' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        // Insert into JGK members table
        $result = $wpdb->insert(
            $jgk_members_table,
            $member_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Database error: ' . $wpdb->last_error,
                'status' => 'error'
            );
        }

        $member_id = $wpdb->insert_id;

        // Log the import in audit log
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'member_imported_from_armember',
            'object_type' => 'member',
            'object_id' => $member_id,
            'new_values' => json_encode(array(
                'user_id' => $armember_data->user_id,
                'source' => 'armember',
                'armember_user_type' => $armember_data->arm_user_type
            ))
        ));

        return array(
            'success' => true,
            'message' => 'Member imported successfully',
            'status' => 'imported',
            'member_id' => $member_id
        );
    }

    /**
     * Update an existing JGK member with ARMember data
     *
     * @since    1.0.0
     * @param    int       $member_id        JGK member ID
     * @param    object    $armember_data    ARMember user data
     * @param    array     $options          Import options
     * @return   array
     */
    private static function update_existing_member($member_id, $armember_data, $options) {
        $update_data = array();

        // Update only empty fields or all fields based on options
        if (!empty($armember_data->date_of_birth)) {
            $update_data['date_of_birth'] = $armember_data->date_of_birth;
        }
        if (!empty($armember_data->gender)) {
            $update_data['gender'] = $armember_data->gender;
        }
        if (!empty($armember_data->phone)) {
            $update_data['phone'] = $armember_data->phone;
        }

        if (empty($update_data)) {
            return array(
                'success' => false,
                'message' => 'No data to update',
                'status' => 'skipped'
            );
        }

        $result = JuniorGolfKenya_Database::update_member($member_id, $update_data);

        if ($result) {
            return array(
                'success' => true,
                'message' => 'Member updated successfully',
                'status' => 'updated',
                'member_id' => $member_id
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to update member',
                'status' => 'error'
            );
        }
    }

    /**
     * Map ARMember status to JGK status
     *
     * @since    1.0.0
     * @param    int       $arm_status       ARMember status code
     * @param    string    $default          Default status if mapping fails
     * @return   string
     */
    private static function map_armember_status($arm_status, $default = 'active') {
        // ARMember status codes:
        // 1 = Active
        // 2 = Inactive
        // 3 = Pending
        // 4 = Terminated
        // 5 = Expired
        
        $status_map = array(
            1 => 'active',
            2 => 'suspended',
            3 => 'pending',
            4 => 'suspended',
            5 => 'expired'
        );

        return isset($status_map[$arm_status]) ? $status_map[$arm_status] : $default;
    }

    /**
     * Batch import members from ARMember
     *
     * @since    1.0.0
     * @param    array    $options    Import options
     * @return   array                Statistics about the import
     */
    public static function batch_import($options = array()) {
        $total = self::get_armember_members_count();
        $batch_size = 50;
        $offset = 0;
        
        $stats = array(
            'total' => $total,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'messages' => array()
        );

        while ($offset < $total) {
            $members = self::get_armember_members($offset, $batch_size);
            
            foreach ($members as $member) {
                $result = self::import_member($member, $options);
                
                if ($result['success']) {
                    if ($result['status'] === 'imported') {
                        $stats['imported']++;
                    } elseif ($result['status'] === 'updated') {
                        $stats['updated']++;
                    }
                } else {
                    if ($result['status'] === 'skipped' || $result['status'] === 'skipped_age') {
                        $stats['skipped']++;
                    } else {
                        $stats['errors']++;
                        $stats['messages'][] = "User #{$member->user_id}: {$result['message']}";
                    }
                }
            }
            
            $offset += $batch_size;
        }

        return $stats;
    }

    /**
     * Preview import data before actual import
     *
     * @since    1.0.0
     * @param    int    $limit    Number of records to preview
     * @return   array
     */
    public static function preview_import($limit = 10) {
        $members = self::get_armember_members(0, $limit);
        $preview = array();

        foreach ($members as $member) {
            global $wpdb;
            $jgk_members_table = $wpdb->prefix . 'jgk_members';
            
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$jgk_members_table} WHERE user_id = %d",
                $member->user_id
            ));

            $age = null;
            if (!empty($member->date_of_birth)) {
                try {
                    $birthdate = new DateTime($member->date_of_birth);
                    $today = new DateTime();
                    $age = $today->diff($birthdate)->y;
                } catch (Exception $e) {
                    // Invalid date
                }
            }

            $preview[] = array(
                'user_id' => $member->user_id,
                'name' => $member->display_name,
                'email' => $member->user_email,
                'phone' => $member->phone,
                'age' => $age,
                'date_of_birth' => $member->date_of_birth,
                'gender' => $member->gender,
                'arm_status' => $member->arm_primary_status,
                'exists_in_jgk' => !empty($existing),
                'will_import' => empty($existing)  // Import all members regardless of age
            );
        }

        return $preview;
    }
}
