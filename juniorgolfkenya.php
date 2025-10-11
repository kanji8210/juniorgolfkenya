<?php
/**
 * Plugin Name: Junior Golf Kenya - Membership Management
 * Plugin URI: https://github.com/kanji8210/juniorgolfkenya
 * Description: A comprehensive membership management plugin for the Junior Golf Foundation website that provides paid member registration, profile and certification management, competition integration, membership verification, payments and subscription management, and admin reporting.
 * Version: 1.0.0
 * Author: Dennis Kosgei for PSM consult
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: juniorgolfkenya
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('JUNIORGOLFKENYA_VERSION', '1.0.0');
define('JUNIORGOLFKENYA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JUNIORGOLFKENYA_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_juniorgolfkenya() {
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-activator.php';
    JuniorGolfKenya_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_juniorgolfkenya() {
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-deactivator.php';
    JuniorGolfKenya_Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstall.
 */
function uninstall_juniorgolfkenya() {
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-uninstaller.php';
    JuniorGolfKenya_Uninstaller::uninstall();
}

register_activation_hook(__FILE__, 'activate_juniorgolfkenya');
register_deactivation_hook(__FILE__, 'deactivate_juniorgolfkenya');
register_uninstall_hook(__FILE__, 'uninstall_juniorgolfkenya');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya.php';

/**
 * Begins execution of the plugin.
 */
function run_juniorgolfkenya() {
    $plugin = new JuniorGolfKenya();
    $plugin->run();
}
run_juniorgolfkenya();

/**
 * Register AJAX handler for getting coach members
 */
add_action('wp_ajax_jgk_get_coach_members', 'jgk_ajax_get_coach_members');

function jgk_ajax_get_coach_members() {
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'jgk_coach_members')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check permissions
    if (!current_user_can('manage_coaches')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $coach_id = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;
    
    if (!$coach_id) {
        wp_send_json_error(array('message' => 'Invalid coach ID'));
    }
    
    global $wpdb;
    $members_table = $wpdb->prefix . 'jgk_members';
    $junction_table = $wpdb->prefix . 'jgk_coach_members';
    
    // Get assigned members with their details
    $query = $wpdb->prepare("
        SELECT 
            m.id,
            m.first_name,
            m.last_name,
            m.membership_type,
            cm.is_primary,
            cm.assigned_date
        FROM {$junction_table} cm
        INNER JOIN {$members_table} m ON cm.member_id = m.id
        WHERE cm.coach_id = %d AND cm.status = 'active'
        ORDER BY cm.is_primary DESC, cm.assigned_date DESC
    ", $coach_id);
    
    $results = $wpdb->get_results($query);
    
    $members = array();
    foreach ($results as $row) {
        $members[] = array(
            'id' => $row->id,
            'name' => $row->first_name . ' ' . $row->last_name,
            'membership_type' => $row->membership_type,
            'is_primary' => (bool) $row->is_primary,
            'assigned_date' => $row->assigned_date
        );
    }
    
    wp_send_json_success(array('members' => $members));
}

/**
 * Register AJAX handler for getting member details
 */
add_action('wp_ajax_jgk_get_member_details', 'jgk_ajax_get_member_details');

function jgk_ajax_get_member_details() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jgk_get_member_details')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check permissions
    if (!current_user_can('manage_coaches')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    
    if (!$member_id) {
        wp_send_json_error('Invalid member ID');
    }
    
    global $wpdb;
    $members_table = $wpdb->prefix . 'jgk_members';
    $users_table = $wpdb->users;
    $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
    $coaches_table = $wpdb->users;
    $parents_table = $wpdb->prefix . 'jgk_parents';
    
    // Get member basic info
    $member = $wpdb->get_row($wpdb->prepare("
        SELECT 
            m.*,
            u.user_email,
            u.display_name
        FROM {$members_table} m
        LEFT JOIN {$users_table} u ON m.user_id = u.ID
        WHERE m.id = %d
    ", $member_id));
    
    if (!$member) {
        wp_send_json_error('Member not found');
    }
    
    // Get all assigned coaches
    $coaches = $wpdb->get_results($wpdb->prepare("
        SELECT 
            c.ID as coach_id,
            c.display_name as name,
            cm.is_primary
        FROM {$coach_members_table} cm
        INNER JOIN {$coaches_table} c ON cm.coach_id = c.ID
        WHERE cm.member_id = %d AND cm.status = 'active'
        ORDER BY cm.is_primary DESC, c.display_name ASC
    ", $member_id));
    
    // Get parents/guardians (check if table exists first)
    $parents = array();
    if ($wpdb->get_var("SHOW TABLES LIKE '{$parents_table}'") == $parents_table) {
        $parents = $wpdb->get_results($wpdb->prepare("
            SELECT 
                parent_name as name,
                relationship,
                phone,
                email
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
    
    // Get profile image URL
    $profile_image = '';
    if ($member->user_id) {
        $avatar_id = get_user_meta($member->user_id, 'jgk_profile_image', true);
        if ($avatar_id) {
            $profile_image = wp_get_attachment_url($avatar_id);
        }
    }
    
    // Calculate age from date of birth
    $age = '';
    if ($member->date_of_birth) {
        $dob = new DateTime($member->date_of_birth);
        $now = new DateTime();
        $age = $dob->diff($now)->y;
    }
    
    // Format coaches array
    $coaches_array = array();
    foreach ($coaches as $coach) {
        $coaches_array[] = array(
            'id' => $coach->coach_id,
            'name' => $coach->name,
            'is_primary' => (bool)$coach->is_primary
        );
    }
    
    // Format parents array
    $parents_array = array();
    foreach ($parents as $parent) {
        $parents_array[] = array(
            'name' => $parent->name,
            'relationship' => $parent->relationship,
            'phone' => $parent->phone,
            'email' => $parent->email
        );
    }
    
    // Prepare response data
    $response = array(
        'id' => $member->id,
        'display_name' => $member->display_name ?: ($member->first_name . ' ' . $member->last_name),
        'first_name' => $member->first_name,
        'last_name' => $member->last_name,
        'email' => $member->user_email,
        'phone' => $member->phone,
        'date_of_birth' => $member->date_of_birth ? date('F j, Y', strtotime($member->date_of_birth)) : '',
        'age' => $age,
        'gender' => $member->gender,
        'status' => $member->status,
        'membership_type' => $member->membership_type,
        'membership_number' => $member->membership_number,
        'club_name' => $member->club_name,
        'handicap' => $member->handicap_index,
        'date_joined' => $member->date_joined ? date('F j, Y', strtotime($member->date_joined)) : '',
        'address' => $member->address,
        'biography' => $member->biography,
        'emergency_contact_name' => $member->emergency_contact_name,
        'emergency_contact_phone' => $member->emergency_contact_phone,
        'profile_image' => $profile_image,
        'coaches' => $coaches_array,
        'parents' => $parents_array
    );
    
    wp_send_json_success($response);
}

/**
 * Register AJAX handler for searching members
 */
add_action('wp_ajax_jgk_search_members', 'jgk_ajax_search_members');

function jgk_ajax_search_members() {
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'jgk_search_members')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check permissions
    if (!current_user_can('manage_coaches')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    if (strlen($search) < 2) {
        wp_send_json_error(array('message' => 'Search term too short'));
    }
    
    global $wpdb;
    $members_table = $wpdb->prefix . 'jgk_members';
    $users_table = $wpdb->users;
    
    // Search members by name, email, or membership number
    $like = '%' . $wpdb->esc_like($search) . '%';
    $query = $wpdb->prepare("
        SELECT 
            m.id,
            CONCAT(m.first_name, ' ', m.last_name) as name,
            m.membership_type as type,
            m.membership_number,
            u.user_email
        FROM {$members_table} m
        LEFT JOIN {$users_table} u ON m.user_id = u.ID
        WHERE m.status IN ('active', 'approved', 'pending')
        AND (
            CONCAT(m.first_name, ' ', m.last_name) LIKE %s
            OR u.user_email LIKE %s
            OR m.membership_number LIKE %s
        )
        ORDER BY m.first_name, m.last_name
        LIMIT 50
    ", $like, $like, $like);
    
    $results = $wpdb->get_results($query);
    
    $members = array();
    foreach ($results as $row) {
        $members[] = array(
            'id' => $row->id,
            'name' => $row->name,
            'type' => $row->type,
            'membership_number' => $row->membership_number,
            'email' => $row->user_email
        );
    }
    
    wp_send_json_success(array('members' => $members));
}