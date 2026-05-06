<?php
/**
 * Parents/Guardians Management Class
 *
 * Handles all operations related to members' parents and guardians
 *                                                                
 * @since      1.0.0
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

class JuniorGolfKenya_Parents {

    /**
     * Create or reuse a parent WordPress account.
     *
     * @since    1.0.0
     * @param    array   $data        Parent account data.
     * @param    string  $password    Optional password for new accounts.
     * @return   array
     */
    public static function create_or_get_parent_account($data, $password = '') {
        $email = sanitize_email($data['email'] ?? '');
        $first_name = sanitize_text_field($data['first_name'] ?? '');
        $last_name = sanitize_text_field($data['last_name'] ?? '');

        if (empty($email) || !is_email($email)) {
            return array(
                'success' => false,
                'message' => 'Valid parent email is required.'
            );
        }

        if (empty($first_name) || empty($last_name)) {
            return array(
                'success' => false,
                'message' => 'Parent first name and last name are required.'
            );
        }

        $existing_user = get_user_by('email', $email);
        $is_new = false;

        if ($existing_user) {
            $user_id = (int) $existing_user->ID;
            $username = $existing_user->user_login;
        } else {
            $username = self::generate_parent_username($first_name, $last_name, $email);
            $parent_password = !empty($password) ? $password : wp_generate_password(20, true, true);
            $user_id = wp_create_user($username, $parent_password, $email);

            if (is_wp_error($user_id)) {
                return array(
                    'success' => false,
                    'message' => $user_id->get_error_message()
                );
            }

            $is_new = true;
        }

        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
        ));

        $user = new WP_User($user_id);
        if (!in_array('jgk_parent', $user->roles, true)) {
            $user->add_role('jgk_parent');
        }

        update_user_meta($user_id, 'jgk_is_parent_account', 1);

        if ($is_new) {
            if (function_exists('wp_send_new_user_notifications')) {
                wp_send_new_user_notifications($user_id, 'user');
            } elseif (function_exists('wp_new_user_notification')) {
                wp_new_user_notification($user_id, null, 'user');
            }
        }

        return array(
            'success' => true,
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'is_new' => $is_new,
        );
    }

    /**
     * Generate a unique username for a parent account.
     *
     * @since    1.0.0
     * @param    string $first_name Parent first name.
     * @param    string $last_name  Parent last name.
     * @param    string $email      Parent email.
     * @return   string
     */
    private static function generate_parent_username($first_name, $last_name, $email) {
        $base_username = sanitize_user(strtolower($first_name . '.' . $last_name), true);

        if (empty($base_username)) {
            $email_parts = explode('@', $email);
            $base_username = sanitize_user($email_parts[0], true);
        }

        if (empty($base_username)) {
            $base_username = 'jgk-parent';
        }

        $username = $base_username;
        $suffix = 1;

        while (username_exists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }

        return $username;
    }

    /**
     * Get grouped parent contacts from existing member data.
     *
     * @since    1.0.0
     * @return   array
     */
    public static function get_parent_contacts_grouped_by_email() {
        global $wpdb;

        $table = $wpdb->prefix . 'jgk_parents_guardians';

        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY is_primary_contact DESC, created_at ASC, id ASC");
        $grouped = array();

        foreach ($rows as $row) {
            $email = sanitize_email($row->email ?? '');
            if (empty($email)) {
                continue;
            }

            if (!isset($grouped[$email])) {
                $grouped[$email] = array(
                    'email' => $email,
                    'first_name' => $row->first_name,
                    'last_name' => $row->last_name,
                    'relationship' => $row->relationship,
                    'member_ids' => array(),
                    'rows' => array(),
                );
            }

            $grouped[$email]['member_ids'][] = (int) $row->member_id;
            $grouped[$email]['rows'][] = $row;

            if (!empty($row->is_primary_contact)) {
                $grouped[$email]['first_name'] = $row->first_name;
                $grouped[$email]['last_name'] = $row->last_name;
                $grouped[$email]['relationship'] = $row->relationship;
            }
        }

        foreach ($grouped as &$entry) {
            $entry['member_ids'] = array_values(array_unique(array_filter(array_map('intval', $entry['member_ids']))));
            $entry['children_count'] = count($entry['member_ids']);
        }
        unset($entry);

        return array_values($grouped);
    }

    /**
     * Build a summary of parent-account coverage from existing data.
     *
     * @since    1.0.0
     * @param    int $sample_limit Number of sample rows to include.
     * @return   array
     */
    public static function get_parent_account_summary($sample_limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . 'jgk_parents_guardians';
        $total_records = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $grouped = self::get_parent_contacts_grouped_by_email();

        $summary = array(
            'total_parent_records' => $total_records,
            'unique_parent_emails' => count($grouped),
            'emails_with_accounts' => 0,
            'emails_with_parent_role' => 0,
            'emails_missing_accounts' => 0,
            'invalid_or_missing_email_records' => max(0, $total_records - count($grouped)),
            'sample_missing_accounts' => array(),
            'sample_existing_accounts' => array(),
        );

        foreach ($grouped as $entry) {
            $user = get_user_by('email', $entry['email']);
            if ($user) {
                $summary['emails_with_accounts']++;
                if (in_array('jgk_parent', (array) $user->roles, true) || get_user_meta($user->ID, 'jgk_is_parent_account', true)) {
                    $summary['emails_with_parent_role']++;
                }

                if (count($summary['sample_existing_accounts']) < $sample_limit) {
                    $summary['sample_existing_accounts'][] = array(
                        'email' => $entry['email'],
                        'name' => trim($entry['first_name'] . ' ' . $entry['last_name']),
                        'children_count' => $entry['children_count'],
                        'wp_user_id' => (int) $user->ID,
                        'has_parent_role' => in_array('jgk_parent', (array) $user->roles, true) || (bool) get_user_meta($user->ID, 'jgk_is_parent_account', true),
                    );
                }
            } else {
                $summary['emails_missing_accounts']++;
                if (count($summary['sample_missing_accounts']) < $sample_limit) {
                    $summary['sample_missing_accounts'][] = array(
                        'email' => $entry['email'],
                        'name' => trim($entry['first_name'] . ' ' . $entry['last_name']),
                        'relationship' => $entry['relationship'],
                        'children_count' => $entry['children_count'],
                    );
                }
            }
        }

        return $summary;
    }

    /**
     * Create or sync parent WordPress accounts from existing parent records.
     *
     * @since    1.0.0
     * @param    int $limit Maximum number of parent emails to process. 0 means all.
     * @return   array
     */
    public static function sync_parent_accounts_from_existing_data($limit = 0) {
        $grouped = self::get_parent_contacts_grouped_by_email();
        $results = array(
            'processed' => 0,
            'created' => 0,
            'updated_existing' => 0,
            'failed' => 0,
            'details' => array(),
        );

        foreach ($grouped as $entry) {
            if ($limit > 0 && $results['processed'] >= $limit) {
                break;
            }

            $account = self::create_or_get_parent_account(array(
                'email' => $entry['email'],
                'first_name' => $entry['first_name'],
                'last_name' => $entry['last_name'],
            ));

            $results['processed']++;

            if (empty($account['success'])) {
                $results['failed']++;
                $results['details'][] = array(
                    'email' => $entry['email'],
                    'status' => 'failed',
                    'message' => $account['message'] ?? 'Unknown error',
                    'children_count' => $entry['children_count'],
                );
                continue;
            }

            if (!empty($account['is_new'])) {
                $results['created']++;
                $status = 'created';
                $message = 'Created new parent account.';
            } else {
                $results['updated_existing']++;
                $status = 'updated';
                $message = 'Existing WordPress user confirmed as parent account.';
            }

            $results['details'][] = array(
                'email' => $entry['email'],
                'status' => $status,
                'message' => $message,
                'username' => $account['username'] ?? '',
                'user_id' => (int) ($account['user_id'] ?? 0),
                'children_count' => $entry['children_count'],
            );
        }

        return $results;
    }

    /**
     * Add a parent/guardian to a member
     *
     * @since    1.0.0
     * @param    int      $member_id    Member ID
     * @param    array    $data         Parent/guardian data
     * @return   int|false               Parent ID on success, false on failure
     */
    public static function add_parent($member_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_parents_guardians';
        
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['relationship'])) {
            return false;
        }
        
        // If setting as primary contact, unset other primary contacts for this member
        if (!empty($data['is_primary_contact']) && $data['is_primary_contact'] == 1) {
            $wpdb->update(
                $table,
                array('is_primary_contact' => 0),
                array('member_id' => $member_id),
                array('%d'),
                array('%d')
            );
        }
        
        $parent_data = array(
            'member_id' => $member_id,
            'relationship' => sanitize_text_field($data['relationship']),
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'email' => isset($data['email']) ? sanitize_email($data['email']) : null,
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : null,
            'mobile' => isset($data['mobile']) ? sanitize_text_field($data['mobile']) : null,
            'address' => isset($data['address']) ? sanitize_textarea_field($data['address']) : null,
            'occupation' => isset($data['occupation']) ? sanitize_text_field($data['occupation']) : null,
            'employer' => isset($data['employer']) ? sanitize_text_field($data['employer']) : null,
            'id_number' => isset($data['id_number']) ? sanitize_text_field($data['id_number']) : null,
            'is_primary_contact' => isset($data['is_primary_contact']) ? (int)$data['is_primary_contact'] : 0,
            'can_pickup' => isset($data['can_pickup']) ? (int)$data['can_pickup'] : 1,
            'emergency_contact' => isset($data['emergency_contact']) ? (int)$data['emergency_contact'] : 0,
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table, $parent_data);
        
        if ($result === false) {
            return false;
        }
        
        // Log the action
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'parent_added',
            'object_type' => 'parent_guardian',
            'object_id' => $wpdb->insert_id,
            'member_id' => $member_id,
            'new_values' => wp_json_encode($parent_data)
        ));
        
        return $wpdb->insert_id;
    }

    /**
     * Update a parent/guardian
     *
     * @since    1.0.0
     * @param    int      $parent_id    Parent ID
     * @param    array    $data         Updated data
     * @return   bool                   True on success, false on failure
     */
    public static function update_parent($parent_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_parents_guardians';
        
        // Get current parent data for audit log
        $old_data = self::get_parent($parent_id);
        if (!$old_data) {
            return false;
        }
        
        // If setting as primary contact, unset other primary contacts for this member
        if (!empty($data['is_primary_contact']) && $data['is_primary_contact'] == 1) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET is_primary_contact = 0 WHERE member_id = %d AND id != %d",
                    $old_data->member_id,
                    $parent_id
                )
            );
        }
        
        $parent_data = array();
        
        // Only update provided fields
        $fields = array(
            'relationship', 'first_name', 'last_name', 'email', 'phone', 
            'mobile', 'address', 'occupation', 'employer', 'id_number',
            'is_primary_contact', 'can_pickup', 'emergency_contact', 'notes'
        );
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                if (in_array($field, array('is_primary_contact', 'can_pickup', 'emergency_contact'))) {
                    $parent_data[$field] = (int)$data[$field];
                } elseif ($field == 'email') {
                    $parent_data[$field] = sanitize_email($data[$field]);
                } elseif (in_array($field, array('address', 'notes'))) {
                    $parent_data[$field] = sanitize_textarea_field($data[$field]);
                } else {
                    $parent_data[$field] = sanitize_text_field($data[$field]);
                }
            }
        }
        
        $parent_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update($table, $parent_data, array('id' => $parent_id));
        
        if ($result === false) {
            return false;
        }
        
        // Log the action
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'parent_updated',
            'object_type' => 'parent_guardian',
            'object_id' => $parent_id,
            'member_id' => $old_data->member_id,
            'old_values' => wp_json_encode($old_data),
            'new_values' => wp_json_encode($parent_data)
        ));
        
        return true;
    }

    /**
     * Delete a parent/guardian
     *
     * @since    1.0.0
     * @param    int     $parent_id    Parent ID
     * @return   bool                  True on success, false on failure
     */
    public static function delete_parent($parent_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_parents_guardians';
        
        // Get parent data for audit log
        $parent_data = self::get_parent($parent_id);
        if (!$parent_data) {
            return false;
        }
        
        $result = $wpdb->delete($table, array('id' => $parent_id));
        
        if ($result === false) {
            return false;
        }
        
        // Log the action
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'parent_deleted',
            'object_type' => 'parent_guardian',
            'object_id' => $parent_id,
            'member_id' => $parent_data->member_id,
            'old_values' => wp_json_encode($parent_data)
        ));
        
        return true;
    }

    /**
     * Get a parent/guardian by ID
     *
     * @since    1.0.0
     * @param    int      $parent_id    Parent ID
     * @return   object|null             Parent data or null
     */
    public static function get_parent($parent_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_parents_guardians';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $parent_id
        ));
    }

    /**
     * Get all parents/guardians for a member
     *
     * @since    1.0.0
     * @param    int     $member_id    Member ID
     * @return   array                 Array of parent objects
     */
    public static function get_member_parents($member_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_parents_guardians';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE member_id = %d ORDER BY is_primary_contact DESC, id ASC",
            $member_id
        ));
    }

    /**
     * Get primary contact for a member
     *
     * @since    1.0.0
     * @param    int      $member_id    Member ID
     * @return   object|null             Primary contact or null
     */
    public static function get_primary_contact($member_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_parents_guardians';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE member_id = %d AND is_primary_contact = 1 LIMIT 1",
            $member_id
        ));
    }

    /**
     * Get emergency contacts for a member
     *
     * @since    1.0.0
     * @param    int     $member_id    Member ID
     * @return   array                 Array of emergency contact objects
     */
    public static function get_emergency_contacts($member_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_parents_guardians';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE member_id = %d AND emergency_contact = 1 ORDER BY is_primary_contact DESC",
            $member_id
        ));
    }

    /**
     * Check if a member requires parent/guardian information
     * (based on age - under dynamic max age + 1)
     *
     * @since    1.0.0
     * @param    int     $member_id    Member ID
     * @return   bool                  True if requires parent info
     */
    public static function requires_parent_info($member_id) {
        $member = JuniorGolfKenya_Database::get_member($member_id);
        
        if (!$member || empty($member->date_of_birth)) {
            return false;
        }
        
        $dob = new DateTime($member->date_of_birth);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        
        $parent_age_limit = JuniorGolfKenya_Settings_Helper::get_max_age() + 1;
        
        return $age < $parent_age_limit;
    }

    /**
     * Validate parent/guardian data
     *
     * @since    1.0.0
     * @param    array    $data    Parent data to validate
     * @return   array             Array with 'valid' => bool and 'errors' => array
     */
    public static function validate_parent_data($data) {
        $errors = array();
        
        // Required fields
        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }
        
        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        }
        
        if (empty($data['relationship'])) {
            $errors[] = 'Relationship is required';
        }
        
        // Valid relationships
        $valid_relationships = array('father', 'mother', 'guardian', 'legal_guardian', 'other');
        if (!empty($data['relationship']) && !in_array($data['relationship'], $valid_relationships)) {
            $errors[] = 'Invalid relationship type';
        }
        
        // Email validation
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors[] = 'Invalid email address';
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Get relationship type options
     *
     * @since    1.0.0
     * @return   array    Array of relationship types
     */
    public static function get_relationship_types() {
        return array(
            'father' => __('Father', 'juniorgolfkenya'),
            'mother' => __('Mother', 'juniorgolfkenya'),
            'guardian' => __('Guardian', 'juniorgolfkenya'),
            'legal_guardian' => __('Legal Guardian', 'juniorgolfkenya'),
            'other' => __('Other', 'juniorgolfkenya')
        );
    }
}
