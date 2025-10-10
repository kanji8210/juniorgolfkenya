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
            $wpdb->update(
                $table,
                array('is_primary_contact' => 0),
                array('member_id' => $old_data->member_id, 'id !=' => $parent_id),
                array('%d'),
                array('%d', '%d')
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
     * (based on age - under 18)
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
        
        return $age < 18;
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
