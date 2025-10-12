<?php
/**
 * Test Data Generator
 *
 * Generates test members and allows cleanup for production mode
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
 * Test Data Generator Class
 *
 * @since      1.0.0
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */
class JuniorGolfKenya_Test_Data {

    /**
     * Generate test members
     *
     * @since    1.0.0
     * @param    int    $count    Number of test members to generate
     * @return   array            Array of generated member IDs and user IDs
     */
    public static function generate_test_members($count = 10) {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        $parents_table = $wpdb->prefix . 'jgk_parents_guardians';
        
        $generated = array(
            'members' => array(),
            'users' => array(),
            'parents' => array(),
            'errors' => array()
        );
        
        // Sample data arrays
        $first_names = array('James', 'Emma', 'Oliver', 'Sophia', 'Noah', 'Ava', 'Liam', 'Isabella', 'Ethan', 'Mia', 
                             'Lucas', 'Charlotte', 'Mason', 'Amelia', 'Logan', 'Harper', 'Elijah', 'Evelyn', 'Aiden', 'Abigail');
        $last_names = array('Mwangi', 'Kamau', 'Ochieng', 'Wanjiku', 'Otieno', 'Njeri', 'Kipchoge', 'Akinyi', 
                           'Mutua', 'Wambui', 'Kimani', 'Njoroge', 'Kariuki', 'Muthoni', 'Omondi');
        $clubs = array('Karen Country Club', 'Muthaiga Golf Club', 'Royal Nairobi Golf Club', 'Limuru Country Club',
                      'Windsor Golf Hotel', 'Great Rift Valley Lodge', 'Nakuru Golf Club', 'Nyali Golf Club');
        $genders = array('male', 'female');
        
        for ($i = 0; $i < $count; $i++) {
            try {
                // Generate random data
                $first_name = $first_names[array_rand($first_names)];
                $last_name = $last_names[array_rand($last_names)];
                $gender = $genders[array_rand($genders)];
                $age = rand(5, 17); // Within junior age range
                $birth_year = date('Y') - $age;
                $birth_month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
                $birth_day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
                $date_of_birth = "{$birth_year}-{$birth_month}-{$birth_day}";
                
                $email = strtolower($first_name . '.' . $last_name . $i . '@testjgk.local');
                $username = strtolower($first_name . $last_name . $i);
                $phone = '+254' . rand(700000000, 799999999);
                $club = $clubs[array_rand($clubs)];
                $handicap = rand(10, 36);
                
                // Create WordPress user
                $user_id = wp_create_user(
                    $username,
                    'TestPassword123!', // Test password
                    $email
                );
                
                if (is_wp_error($user_id)) {
                    $generated['errors'][] = "Failed to create user {$username}: " . $user_id->get_error_message();
                    continue;
                }
                
                // Assign member role
                $user = new WP_User($user_id);
                $user->set_role('jgk_member');
                
                // Update user meta
                update_user_meta($user_id, 'first_name', $first_name);
                update_user_meta($user_id, 'last_name', $last_name);
                update_user_meta($user_id, 'jgk_test_data', '1'); // Mark as test data
                
                // Generate membership number
                $membership_number = 'TEST-JGK-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
                
                // Insert member record
                $member_data = array(
                    'user_id' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'date_of_birth' => $date_of_birth,
                    'gender' => $gender,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => rand(1, 999) . ' Test Street, Nairobi',
                    'membership_type' => 'junior',
                    'membership_number' => $membership_number,
                    'status' => 'active',
                    'club_name' => $club,
                    'handicap_index' => $handicap,
                    'created_at' => current_time('mysql')
                );
                
                $wpdb->insert($members_table, $member_data);
                $member_id = $wpdb->insert_id;
                
                if (!$member_id) {
                    $generated['errors'][] = "Failed to insert member record for {$username}";
                    wp_delete_user($user_id);
                    continue;
                }
                
                // Generate parent/guardian
                $parent_first = ($gender === 'male') ? 'John' : 'Jane';
                $parent_last = $last_name;
                $parent_phone = '+254' . rand(700000000, 799999999);
                $parent_email = strtolower($parent_first . '.' . $parent_last . '.parent' . $i . '@testjgk.local');
                $relationship = ($gender === 'male') ? 'father' : 'mother';
                
                $parent_data = array(
                    'member_id' => $member_id,
                    'first_name' => $parent_first,
                    'last_name' => $parent_last,
                    'relationship' => $relationship,
                    'phone' => $parent_phone,
                    'email' => $parent_email,
                    'is_primary' => 1,
                    'created_at' => current_time('mysql')
                );
                
                $wpdb->insert($parents_table, $parent_data);
                $parent_id = $wpdb->insert_id;
                
                $generated['members'][] = $member_id;
                $generated['users'][] = $user_id;
                $generated['parents'][] = $parent_id;
                
            } catch (Exception $e) {
                $generated['errors'][] = "Exception generating member {$i}: " . $e->getMessage();
            }
        }
        
        return $generated;
    }
    
    /**
     * Delete all test data (users, members, parents)
     *
     * @since    1.0.0
     * @return   array    Results of deletion operation
     */
    public static function delete_all_test_data() {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        $parents_table = $wpdb->prefix . 'jgk_parents_guardians';
        $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
        
        $results = array(
            'users_deleted' => 0,
            'members_deleted' => 0,
            'parents_deleted' => 0,
            'coach_assignments_deleted' => 0,
            'errors' => array()
        );
        
        try {
            // Find all test users
            $test_users = get_users(array(
                'meta_key' => 'jgk_test_data',
                'meta_value' => '1'
            ));
            
            foreach ($test_users as $user) {
                // Get member record
                $member = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$members_table} WHERE user_id = %d",
                    $user->ID
                ));
                
                if ($member) {
                    // Delete parent records
                    $parent_count = $wpdb->delete(
                        $parents_table,
                        array('member_id' => $member->id),
                        array('%d')
                    );
                    $results['parents_deleted'] += ($parent_count ? $parent_count : 0);
                    
                    // Delete coach assignments
                    $coach_count = $wpdb->delete(
                        $coach_members_table,
                        array('member_id' => $member->id),
                        array('%d')
                    );
                    $results['coach_assignments_deleted'] += ($coach_count ? $coach_count : 0);
                    
                    // Delete member record
                    $member_deleted = $wpdb->delete(
                        $members_table,
                        array('id' => $member->id),
                        array('%d')
                    );
                    if ($member_deleted) {
                        $results['members_deleted']++;
                    }
                }
                
                // Delete WordPress user (includes all user meta)
                if (wp_delete_user($user->ID)) {
                    $results['users_deleted']++;
                }
            }
            
            // Also delete members with TEST- membership numbers (backup check)
            $test_members = $wpdb->get_results(
                "SELECT id, user_id FROM {$members_table} WHERE membership_number LIKE 'TEST-%'"
            );
            
            foreach ($test_members as $member) {
                // Delete parent records
                $wpdb->delete($parents_table, array('member_id' => $member->id), array('%d'));
                
                // Delete coach assignments
                $wpdb->delete($coach_members_table, array('member_id' => $member->id), array('%d'));
                
                // Delete member
                $wpdb->delete($members_table, array('id' => $member->id), array('%d'));
                
                // Delete user if exists
                if ($member->user_id) {
                    wp_delete_user($member->user_id);
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = "Exception during cleanup: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Check if test data exists
     *
     * @since    1.0.0
     * @return   bool    True if test data exists
     */
    public static function has_test_data() {
        $test_users = get_users(array(
            'meta_key' => 'jgk_test_data',
            'meta_value' => '1',
            'number' => 1
        ));
        
        return !empty($test_users);
    }
    
    /**
     * Count test data items
     *
     * @since    1.0.0
     * @return   array    Counts of test items
     */
    public static function count_test_data() {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        
        $test_users = get_users(array(
            'meta_key' => 'jgk_test_data',
            'meta_value' => '1'
        ));
        
        $test_members = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$members_table} WHERE membership_number LIKE 'TEST-%'"
        );
        
        return array(
            'users' => count($test_users),
            'members' => (int) $test_members
        );
    }
}
