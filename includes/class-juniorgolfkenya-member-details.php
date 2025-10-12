<?php
/**
 * Member Details Management Class
 *
 * Handles member details display, AJAX callbacks, and related functionality
 * for the Junior Golf Kenya plugin.
 *
 * @since      1.0.0
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * JuniorGolfKenya_Member_Details class.
 *
 * This class handles all member details related functionality including
 * AJAX callbacks for displaying member information in modals.
 *
 * @since      1.0.0
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */
class JuniorGolfKenya_Member_Details {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize hooks and actions.
     *
     * @since    1.0.0
     */
    public function init() {
        add_action('wp_ajax_jgk_get_member_details', array($this, 'ajax_get_member_details'));
    }

    /**
     * AJAX callback for getting member details.
     *
     * Handles the AJAX request for displaying member details in a modal.
     * Validates permissions, retrieves member data, and generates HTML response.
     *
     * @since    1.0.0
     */
    public function ajax_get_member_details() {
        try {
            // Log the request for debugging
            error_log('JGK Member Details AJAX: Request received at ' . date('Y-m-d H:i:s'));
            error_log('JGK Member Details AJAX: POST data: ' . print_r($_POST, true));

            // Load required classes
            $this->load_dependencies();

            // Validate request
            $validation_result = $this->validate_request();
            if (is_wp_error($validation_result)) {
                wp_send_json_error(array('message' => $validation_result->get_error_message()));
                return;
            }

            $member_id = intval($_POST['member_id']);

            // Get member data
            $member_data = $this->get_member_data($member_id);
            if (is_wp_error($member_data)) {
                wp_send_json_error(array('message' => $member_data->get_error_message()));
                return;
            }

            // Generate HTML response
            $html = $this->generate_member_details_html($member_data);

            error_log('JGK Member Details AJAX: HTML generated successfully, length: ' . strlen($html));
            wp_send_json_success(array('html' => $html));

        } catch (Exception $e) {
            error_log('JGK Member Details AJAX: Exception: ' . $e->getMessage());
            error_log('JGK Member Details AJAX: Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'Error generating member details: ' . $e->getMessage()));
        }
    }

    /**
     * Load required dependencies.
     *
     * @since    1.0.0
     */
    private function load_dependencies() {
        require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
        require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-media.php';
    }

    /**
     * Validate the AJAX request.
     *
     * @since    1.0.0
     * @return  bool|WP_Error True if valid, WP_Error if invalid.
     */
    private function validate_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'jgk_members_action')) {
            return new WP_Error('security_check_failed', 'Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            return new WP_Error('insufficient_permissions', 'Insufficient permissions');
        }

        // Validate member ID
        $member_id = intval($_POST['member_id']);
        if (!$member_id || $member_id <= 0) {
            return new WP_Error('invalid_member_id', 'Invalid member ID');
        }

        return true;
    }

    /**
     * Get member data for display.
     *
     * @since    1.0.0
     * @param   int $member_id The member ID.
     * @return  object|WP_Error Member data object or WP_Error on failure.
     */
    private function get_member_data($member_id) {
        // Get member data
        $member = JuniorGolfKenya_Database::get_member($member_id);
        if (!$member) {
            return new WP_Error('member_not_found', 'Member not found');
        }

        // Get additional data
        $member_parents = JuniorGolfKenya_Database::get_member_parents($member_id);

        // Get coach information
        $coach_name = '';
        if ($member->coach_id) {
            $coach = get_userdata($member->coach_id);
            $coach_name = $coach ? $coach->display_name : 'Unknown Coach';
        }

        // Calculate age
        $age = $this->calculate_age($member->date_of_birth);

        // Return combined data
        return (object) array(
            'member' => $member,
            'parents' => $member_parents,
            'coach_name' => $coach_name,
            'age' => $age
        );
    }

    /**
     * Calculate age from date of birth.
     *
     * @since    1.0.0
     * @param   string $date_of_birth Date of birth in YYYY-MM-DD format.
     * @return  string Age string or empty string if invalid.
     */
    private function calculate_age($date_of_birth) {
        if (empty($date_of_birth)) {
            return '';
        }

        try {
            $birthdate = new DateTime($date_of_birth);
            $today = new DateTime();
            $age_years = $today->diff($birthdate)->y;
            return $age_years . ' years old';
        } catch (Exception $e) {
            return 'Invalid date';
        }
    }

    /**
     * Generate HTML for member details display.
     *
     * @since    1.0.0
     * @param   object $member_data Combined member data.
     * @return  string HTML content for member details.
     */
    private function generate_member_details_html($member_data) {
        $member = $member_data->member;
        $member_parents = $member_data->parents;
        $coach_name = $member_data->coach_name;
        $age = $member_data->age;

        $html = '<div class="member-details-wrapper">';

        // Profile section
        $html .= $this->generate_profile_section($member);

        // Details grid
        $html .= '<div class="member-details-grid">';

        // Personal Information
        $html .= $this->generate_personal_info_section($member, $age);

        // Membership Details
        $html .= $this->generate_membership_details_section($member, $coach_name);

        // Emergency Contact
        $html .= $this->generate_emergency_contact_section($member);

        // Additional Information
        $html .= $this->generate_additional_info_section($member);

        $html .= '</div>'; // End member-details-grid

        // Parents section
        if (!empty($member_parents)) {
            $html .= $this->generate_parents_section($member_parents);
        }

        $html .= '</div>'; // End member-details-wrapper

        return $html;
    }

    /**
     * Generate profile section HTML.
     *
     * @since    1.0.0
     * @param   object $member Member data.
     * @return  string HTML for profile section.
     */
    private function generate_profile_section($member) {
        $html = '<div class="member-profile-section">';
        $html .= '<div class="member-profile-header">';

        error_log('JGK Member Details AJAX: About to call get_profile_image_html');
        $profile_image_html = JuniorGolfKenya_Media::get_profile_image_html(
            $member->id,
            'medium',
            array('style' => 'width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #0073aa;')
        );
        error_log('JGK Member Details AJAX: get_profile_image_html returned: ' . substr($profile_image_html, 0, 100));

        $html .= $profile_image_html;
        $html .= '<div class="member-profile-info">';
        $html .= '<h3>' . esc_html($member->first_name . ' ' . $member->last_name) . '</h3>';
        $html .= '<p class="member-email">' . esc_html($member->user_email) . '</p>';
        $html .= '<span class="member-status-badge status-' . esc_attr($member->status) . '">';
        $html .= ucfirst(esc_html($member->status));
        $html .= '</span>';
        $html .= '</div></div></div>';

        return $html;
    }

    /**
     * Generate personal information section HTML.
     *
     * @since    1.0.0
     * @param   object $member Member data.
     * @param   string $age    Calculated age.
     * @return  string HTML for personal information section.
     */
    private function generate_personal_info_section($member, $age) {
        $html = '<div class="member-details-section">';
        $html .= '<h4>Personal Information</h4>';
        $html .= '<div class="member-details-table">';
        $html .= '<div class="detail-row"><span class="detail-label">Full Name:</span><span class="detail-value">' . esc_html($member->first_name . ' ' . $member->last_name) . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">' . esc_html($member->user_email) . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Phone:</span><span class="detail-value">' . esc_html($member->phone ?: 'Not provided') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Date of Birth:</span><span class="detail-value">' . esc_html($member->date_of_birth ?: 'Not provided') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Age:</span><span class="detail-value">' . esc_html($age ?: 'Not calculated') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Gender:</span><span class="detail-value">' . esc_html($member->gender ?: 'Not specified') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Golf Handicap:</span><span class="detail-value">' . esc_html($member->handicap ?: 'Not set') . '</span></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Generate membership details section HTML.
     *
     * @since    1.0.0
     * @param   object $member    Member data.
     * @param   string $coach_name Coach name.
     * @return  string HTML for membership details section.
     */
    private function generate_membership_details_section($member, $coach_name) {
        $html = '<div class="member-details-section">';
        $html .= '<h4>Membership Details</h4>';
        $html .= '<div class="member-details-table">';
        $html .= '<div class="detail-row"><span class="detail-label">Membership Type:</span><span class="detail-value">' . esc_html(ucfirst($member->membership_type)) . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Status:</span><span class="detail-value"><span class="member-status-badge status-' . esc_attr($member->status) . '">' . ucfirst(esc_html($member->status)) . '</span></span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Club Affiliation:</span><span class="detail-value">' . esc_html($member->club_affiliation ?: 'None') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Assigned Coach:</span><span class="detail-value">' . esc_html($coach_name ?: 'No coach assigned') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Public Visibility:</span><span class="detail-value">';
        $html .= ($member->is_public) ? '<span style="color: #28a745;">✓ Visible on public pages</span>' : '<span style="color: #dc3545;">✗ Hidden from public</span>';
        $html .= '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Member Since:</span><span class="detail-value">' . date('M j, Y', strtotime($member->created_at)) . '</span></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Generate emergency contact section HTML.
     *
     * @since    1.0.0
     * @param   object $member Member data.
     * @return  string HTML for emergency contact section.
     */
    private function generate_emergency_contact_section($member) {
        $html = '<div class="member-details-section">';
        $html .= '<h4>Emergency Contact</h4>';
        $html .= '<div class="member-details-table">';
        $html .= '<div class="detail-row"><span class="detail-label">Contact Name:</span><span class="detail-value">' . esc_html($member->emergency_contact_name ?: 'Not provided') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Contact Phone:</span><span class="detail-value">' . esc_html($member->emergency_contact_phone ?: 'Not provided') . '</span></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Generate additional information section HTML.
     *
     * @since    1.0.0
     * @param   object $member Member data.
     * @return  string HTML for additional information section.
     */
    private function generate_additional_info_section($member) {
        $html = '<div class="member-details-section">';
        $html .= '<h4>Additional Information</h4>';
        $html .= '<div class="member-details-table">';
        $html .= '<div class="detail-row"><span class="detail-label">Medical Conditions:</span><span class="detail-value">' . esc_html($member->medical_conditions ?: 'None specified') . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Address:</span><span class="detail-value">' . nl2br(esc_html($member->address ?: 'Not provided')) . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Biography:</span><span class="detail-value">' . nl2br(esc_html($member->biography ?: 'Not provided')) . '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Photography Consent:</span><span class="detail-value">';
        $html .= ($member->consent_photography === 'yes') ? '<span style="color: #28a745;">✓ Granted</span>' : '<span style="color: #dc3545;">✗ Not granted</span>';
        $html .= '</span></div>';
        $html .= '<div class="detail-row"><span class="detail-label">Parental Consent:</span><span class="detail-value">';
        $html .= ($member->parental_consent) ? '<span style="color: #28a745;">✓ Granted</span>' : '<span style="color: #dc3545;">✗ Not granted</span>';
        $html .= '</span></div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Generate parents section HTML.
     *
     * @since    1.0.0
     * @param   array $member_parents Array of parent data.
     * @return  string HTML for parents section.
     */
    private function generate_parents_section($member_parents) {
        $html = '<div class="member-details-section">';
        $html .= '<h4>Parents/Guardians</h4>';
        $html .= '<div class="parents-list">';

        foreach ($member_parents as $parent) {
            $html .= '<div class="parent-entry" style="background: #f9f9f9; padding: 15px; margin-bottom: 10px; border-radius: 5px; border-left: 4px solid #0073aa;">';
            $html .= '<h5 style="margin: 0 0 10px 0; color: #0073aa;">';
            $html .= esc_html($parent->first_name . ' ' . $parent->last_name);
            $html .= '<span style="font-weight: normal; font-size: 14px; color: #666;"> (' . ucfirst($parent->relationship) . ')</span>';
            $html .= '</h5>';
            $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">';
            $html .= '<div><strong>Phone:</strong> ' . esc_html($parent->phone ?: 'N/A') . '</div>';
            $html .= '<div><strong>Email:</strong> ' . esc_html($parent->email ?: 'N/A') . '</div>';
            if ($parent->occupation) {
                $html .= '<div><strong>Occupation:</strong> ' . esc_html($parent->occupation) . '</div>';
            }
            $html .= '<div>';
            if ($parent->is_primary_contact) {
                $html .= '<span style="color: #46b450; font-weight: bold;">✓ Primary Contact</span> ';
            }
            if ($parent->emergency_contact) {
                $html .= '<span style="color: #d54e21; font-weight: bold;">⚠ Emergency Contact</span>';
            }
            $html .= '</div>';
            $html .= '</div></div>';
        }

        $html .= '</div></div>';

        return $html;
    }
}