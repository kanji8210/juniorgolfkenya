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

/**
 * Register AJAX handler for member details modal
 */
add_action('wp_ajax_jgk_get_member_details', 'jgk_get_member_details_callback');

function jgk_get_member_details_callback() {
    // Load required classes
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-media.php';
    
    // Debug: Log the request
    error_log('JGK Member Details AJAX: Request received');
    error_log('JGK Member Details AJAX: POST data: ' . print_r($_POST, true));
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'jgk_members_action')) {
        error_log('JGK Member Details AJAX: Nonce verification failed');
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        error_log('JGK Member Details AJAX: Permission check failed - user does not have manage_options');
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $member_id = intval($_POST['member_id']);
    error_log('JGK Member Details AJAX: Member ID: ' . $member_id);
    
    if (!$member_id) {
        error_log('JGK Member Details AJAX: Invalid member ID');
        wp_send_json_error(array('message' => 'Invalid member ID'));
        return;
    }
    
    // Get member data
    $member = JuniorGolfKenya_Database::get_member($member_id);
    error_log('JGK Member Details AJAX: Member data: ' . print_r($member, true));
    
    if (!$member) {
        error_log('JGK Member Details AJAX: Member not found');
        wp_send_json_error(array('message' => 'Member not found'));
        return;
    }
    
    error_log('JGK Member Details AJAX: Member found, proceeding with HTML generation');    // Get member parents
    $member_parents = JuniorGolfKenya_Database::get_member_parents($member_id);

    // Get coach name if assigned
    $coach_name = '';
    if ($member->coach_id) {
        $coach = get_userdata($member->coach_id);
        $coach_name = $coach ? $coach->display_name : 'Unknown Coach';
    }

    // Calculate age
    $age = '';
    if (!empty($member->date_of_birth)) {
        try {
            $birthdate = new DateTime($member->date_of_birth);
            $today = new DateTime();
            $age = $today->diff($birthdate)->y . ' years old';
        } catch (Exception $e) {
            $age = 'Invalid date';
        }
    }

    // Build HTML response
    ob_start();
    ?>
    <div class="member-details-wrapper">
        <div class="member-profile-section">
            <div class="member-profile-header">
                <?php echo JuniorGolfKenya_Media::get_profile_image_html($member->id, 'medium', array('style' => 'width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #0073aa;')); ?>
                <div class="member-profile-info">
                    <h3><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></h3>
                    <p class="member-email"><?php echo esc_html($member->user_email); ?></p>
                    <span class="member-status-badge status-<?php echo esc_attr($member->status); ?>">
                        <?php echo ucfirst(esc_html($member->status)); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="member-details-grid">
            <div class="member-details-section">
                <h4>Personal Information</h4>
                <div class="member-details-table">
                    <div class="detail-row">
                        <span class="detail-label">Full Name:</span>
                        <span class="detail-value"><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo esc_html($member->user_email); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?php echo esc_html($member->phone ?: 'Not provided'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date of Birth:</span>
                        <span class="detail-value"><?php echo esc_html($member->date_of_birth ?: 'Not provided'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Age:</span>
                        <span class="detail-value"><?php echo esc_html($age ?: 'Not calculated'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Gender:</span>
                        <span class="detail-value"><?php echo esc_html($member->gender ?: 'Not specified'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Golf Handicap:</span>
                        <span class="detail-value"><?php echo esc_html($member->handicap ?: 'Not set'); ?></span>
                    </div>
                </div>
            </div>

            <div class="member-details-section">
                <h4>Membership Details</h4>
                <div class="member-details-table">
                    <div class="detail-row">
                        <span class="detail-label">Membership Type:</span>
                        <span class="detail-value"><?php echo esc_html(ucfirst($member->membership_type)); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="member-status-badge status-<?php echo esc_attr($member->status); ?>">
                                <?php echo ucfirst(esc_html($member->status)); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Club Affiliation:</span>
                        <span class="detail-value"><?php echo esc_html($member->club_affiliation ?: 'None'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Assigned Coach:</span>
                        <span class="detail-value"><?php echo esc_html($coach_name ?: 'No coach assigned'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Public Visibility:</span>
                        <span class="detail-value">
                            <?php if ($member->is_public): ?>
                                <span style="color: #28a745;">✓ Visible on public pages</span>
                            <?php else: ?>
                                <span style="color: #dc3545;">✗ Hidden from public</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Member Since:</span>
                        <span class="detail-value"><?php echo date('M j, Y', strtotime($member->created_at)); ?></span>
                    </div>
                </div>
            </div>

            <div class="member-details-section">
                <h4>Emergency Contact</h4>
                <div class="member-details-table">
                    <div class="detail-row">
                        <span class="detail-label">Contact Name:</span>
                        <span class="detail-value"><?php echo esc_html($member->emergency_contact_name ?: 'Not provided'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contact Phone:</span>
                        <span class="detail-value"><?php echo esc_html($member->emergency_contact_phone ?: 'Not provided'); ?></span>
                    </div>
                </div>
            </div>

            <div class="member-details-section">
                <h4>Additional Information</h4>
                <div class="member-details-table">
                    <div class="detail-row">
                        <span class="detail-label">Medical Conditions:</span>
                        <span class="detail-value"><?php echo esc_html($member->medical_conditions ?: 'None specified'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value"><?php echo nl2br(esc_html($member->address ?: 'Not provided')); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Biography:</span>
                        <span class="detail-value"><?php echo nl2br(esc_html($member->biography ?: 'Not provided')); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Photography Consent:</span>
                        <span class="detail-value">
                            <?php if ($member->consent_photography === 'yes'): ?>
                                <span style="color: #28a745;">✓ Granted</span>
                            <?php else: ?>
                                <span style="color: #dc3545;">✗ Not granted</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Parental Consent:</span>
                        <span class="detail-value">
                            <?php if ($member->parental_consent): ?>
                                <span style="color: #28a745;">✓ Granted</span>
                            <?php else: ?>
                                <span style="color: #dc3545;">✗ Not granted</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($member_parents)): ?>
        <div class="member-details-section">
            <h4>Parents/Guardians</h4>
            <div class="parents-list">
                <?php foreach ($member_parents as $parent): ?>
                <div class="parent-entry" style="background: #f9f9f9; padding: 15px; margin-bottom: 10px; border-radius: 5px; border-left: 4px solid #0073aa;">
                    <h5 style="margin: 0 0 10px 0; color: #0073aa;">
                        <?php echo esc_html($parent->first_name . ' ' . $parent->last_name); ?>
                        <span style="font-weight: normal; font-size: 14px; color: #666;">
                            (<?php echo ucfirst($parent->relationship); ?>)
                        </span>
                    </h5>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">
                        <div><strong>Phone:</strong> <?php echo esc_html($parent->phone ?: 'N/A'); ?></div>
                        <div><strong>Email:</strong> <?php echo esc_html($parent->email ?: 'N/A'); ?></div>
                        <?php if ($parent->occupation): ?>
                        <div><strong>Occupation:</strong> <?php echo esc_html($parent->occupation); ?></div>
                        <?php endif; ?>
                        <div>
                            <?php if ($parent->is_primary_contact): ?>
                            <span style="color: #46b450; font-weight: bold;">✓ Primary Contact</span>
                            <?php endif; ?>
                            <?php if ($parent->emergency_contact): ?>
                            <span style="color: #d54e21; font-weight: bold;">⚠ Emergency Contact</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php

    $html = ob_get_clean();
    error_log('JGK Member Details AJAX: HTML generated successfully, length: ' . strlen($html));
    wp_send_json_success(array('html' => $html));
}

/**
 * Register AJAX handler for coach role request submission
 */
add_action('wp_ajax_jgk_submit_coach_request', 'jgk_ajax_submit_coach_request');

function jgk_ajax_submit_coach_request() {
    // Verify nonce
    if (!isset($_POST['jgk_coach_request_nonce']) || !wp_verify_nonce($_POST['jgk_coach_request_nonce'], 'jgk_coach_request_action')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('You must be logged in to submit a request');
    }
    
    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    
    // Check if user is already a coach
    if (in_array('jgk_coach', $current_user->roles)) {
        wp_send_json_error('You already have coach access');
    }
    
    global $wpdb;
    $role_requests_table = $wpdb->prefix . 'jgf_role_requests';
    
    // Check if user already has a pending request
    $existing_request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$role_requests_table} WHERE requester_user_id = %d AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
        $user_id
    ));
    
    if ($existing_request) {
        wp_send_json_error('You already have a pending coach role request');
    }
    
    // Validate required fields
    $required_fields = array('first_name', 'last_name', 'phone', 'years_experience', 'certifications', 'experience');
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error('Please fill in all required fields');
        }
    }
    
    // Prepare data
    $data = array(
        'requester_user_id' => $user_id,
        'requested_role' => 'jgk_coach',
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'years_experience' => sanitize_text_field($_POST['years_experience']),
        'specialization' => sanitize_text_field($_POST['specialization']),
        'certifications' => sanitize_textarea_field($_POST['certifications']),
        'experience' => sanitize_textarea_field($_POST['experience']),
        'reference_name' => sanitize_text_field($_POST['reference_name'] ?? ''),
        'reference_contact' => sanitize_text_field($_POST['reference_contact'] ?? ''),
        'status' => 'pending',
        'created_at' => current_time('mysql')
    );
    
    // Insert into database
    $inserted = $wpdb->insert($role_requests_table, $data);
    
    if ($inserted) {
        // Send notification email to admin
        $admin_email = get_option('admin_email');
        $subject = 'New Coach Role Request - Junior Golf Kenya';
        $message = sprintf(
            "A new coach role request has been submitted:\n\n" .
            "Name: %s %s\n" .
            "Email: %s\n" .
            "Phone: %s\n" .
            "Experience: %s years\n\n" .
            "View and approve in the admin dashboard: %s",
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['years_experience'],
            admin_url('admin.php?page=juniorgolfkenya-role-requests')
        );
        
        wp_mail($admin_email, $subject, $message);
        
        wp_send_json_success('Your application has been submitted successfully! We will review it soon.');
    } else {
        wp_send_json_error('Failed to submit request. Please try again.');
    }
}

/**
 * Handle coach request form submission (non-AJAX fallback)
 */
add_action('init', 'jgk_handle_coach_request_form');

function jgk_handle_coach_request_form() {
    if (!isset($_POST['action']) || $_POST['action'] !== 'jgk_submit_coach_request') {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['jgk_coach_request_nonce']) || !wp_verify_nonce($_POST['jgk_coach_request_nonce'], 'jgk_coach_request_action')) {
        wp_die('Security check failed');
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to submit a request');
    }
    
    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    
    // Check if user is already a coach
    if (in_array('jgk_coach', $current_user->roles)) {
        wp_redirect(add_query_arg('error', 'already_coach', wp_get_referer()));
        exit;
    }
    
    global $wpdb;
    $role_requests_table = $wpdb->prefix . 'jgf_role_requests';
    
    // Check if user already has a pending request
    $existing_request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$role_requests_table} WHERE requester_user_id = %d AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
        $user_id
    ));
    
    if ($existing_request) {
        wp_redirect(add_query_arg('error', 'pending_request', wp_get_referer()));
        exit;
    }
    
    // Validate required fields
    $required_fields = array('first_name', 'last_name', 'phone', 'years_experience', 'certifications', 'experience');
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_redirect(add_query_arg('error', 'missing_fields', wp_get_referer()));
            exit;
        }
    }
    
    // Prepare data
    $data = array(
        'requester_user_id' => $user_id,
        'requested_role' => 'jgk_coach',
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'years_experience' => sanitize_text_field($_POST['years_experience']),
        'specialization' => sanitize_text_field($_POST['specialization']),
        'certifications' => sanitize_textarea_field($_POST['certifications']),
        'experience' => sanitize_textarea_field($_POST['experience']),
        'reference_name' => sanitize_text_field($_POST['reference_name'] ?? ''),
        'reference_contact' => sanitize_text_field($_POST['reference_contact'] ?? ''),
        'status' => 'pending',
        'created_at' => current_time('mysql')
    );
    
    // Insert into database
    $inserted = $wpdb->insert($role_requests_table, $data);
    
    if ($inserted) {
        // Send notification email to admin
        $admin_email = get_option('admin_email');
        $subject = 'New Coach Role Request - Junior Golf Kenya';
        $message = sprintf(
            "A new coach role request has been submitted:\n\n" .
            "Name: %s %s\n" .
            "Email: %s\n" .
            "Phone: %s\n" .
            "Experience: %s years\n\n" .
            "View and approve in the admin dashboard: %s",
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['years_experience'],
            admin_url('admin.php?page=juniorgolfkenya-role-requests')
        );
        
        wp_mail($admin_email, $subject, $message);
        
        wp_redirect(add_query_arg('success', 'request_submitted', wp_get_referer()));
        exit;
    } else {
        wp_redirect(add_query_arg('error', 'submission_failed', wp_get_referer()));
        exit;
    }
}