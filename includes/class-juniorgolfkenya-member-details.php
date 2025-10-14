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
     * Debug mode flag
     *
     * @since    1.0.1
     * @var      bool
     */
    private $debug_mode;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->init();
    }

    /**
     * Initialize hooks and actions.
     *
     * @since    1.0.0
     */
    public function init() {
        // AJAX functionality removed - now using inline expansion
        // add_action('wp_ajax_jgk_get_member_details', array($this, 'ajax_get_member_details'));

        // Add debug hook if enabled
        if ($this->debug_mode) {
            add_action('wp_ajax_jgk_debug_member_details', array($this, 'debug_member_details'));
        }
    }

    /**
     * Debug endpoint for member details
     *
     * @since    1.0.1
     */
    public function debug_member_details() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $debug_data = array(
            'server' => array(
                'PHP_VERSION' => PHP_VERSION,
                'WORDPRESS_VERSION' => get_bloginfo('version'),
                'WP_DEBUG' => WP_DEBUG,
                'WP_DEBUG_LOG' => WP_DEBUG_LOG,
            ),
            'plugin' => array(
                'JUNIORGOLFKENYA_PLUGIN_PATH' => defined('JUNIORGOLFKENYA_PLUGIN_PATH') ? JUNIORGOLFKENYA_PLUGIN_PATH : 'Not defined',
            ),
            'test_member_id' => isset($_GET['test_member_id']) ? absint($_GET['test_member_id']) : null
        );

        // Test member retrieval if ID provided
        if (!empty($debug_data['test_member_id'])) {
            $this->load_dependencies();
            $member = JuniorGolfKenya_Database::get_member($debug_data['test_member_id']);
            $debug_data['test_member'] = $member ? array(
                'id' => $member->id,
                'name' => $member->first_name . ' ' . $member->last_name,
                'status' => $member->status
            ) : 'Member not found';
        }

        wp_send_json_success($debug_data);
    }

    /**
     * Log debug messages with different levels
     *
     * @since    1.0.1
     * @param string $message The debug message
     * @param string $level The log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
     * @param mixed $context Additional context data
     */
    private function log_debug($message, $level = 'DEBUG', $context = null) {
        if (!$this->debug_mode) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s.v');
        $log_message = "[{$timestamp}] [{$level}] {$message}";

        if ($context !== null) {
            $log_message .= " | Context: " . $this->format_context($context);
        }

        error_log("JGK Member Details: {$log_message}");
    }

    /**
     * Format context data for logging
     *
     * @since    1.0.1
     * @param mixed $context The context data to format
     * @return string Formatted context string
     */
    private function format_context($context) {
        if (is_scalar($context)) {
            return (string) $context;
        }

        if (is_object($context) && $context instanceof WP_Error) {
            return $context->get_error_message() . ' (code: ' . $context->get_error_code() . ')';
        }

        // Prevent recursion and large data dumps
        return wp_json_encode($this->sanitize_log_data($context), JSON_PRETTY_PRINT);
    }

    /**
     * Sanitize data for logging to prevent sensitive information exposure
     *
     * @since    1.0.1
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private function sanitize_log_data($data) {
        if (is_array($data)) {
            $sanitized = array();
            foreach ($data as $key => $value) {
                // Mask sensitive fields
                if (in_array($key, array('password', 'nonce', 'auth_key', 'secret'), true)) {
                    $sanitized[$key] = '***MASKED***';
                } else {
                    $sanitized[$key] = $this->sanitize_log_data($value);
                }
            }
            return $sanitized;
        }

        if (is_object($data)) {
            // Convert objects to arrays for logging
            $array = (array) $data;
            return $this->sanitize_log_data($array);
        }

        return $data;
    }

    /**
     * Load required dependencies.
     *
     * @since    1.0.0
     */
    private function load_dependencies() {
        $this->log_debug('Loading dependencies', 'DEBUG');

        $required_files = array(
            'database' => JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php',
            'media' => JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-media.php'
        );

        foreach ($required_files as $key => $file) {
            if (!file_exists($file)) {
                $this->log_debug("Required file not found: {$file}", 'ERROR');
                throw new RuntimeException("Required file not found: {$key}");
            }
            require_once $file;
            $this->log_debug("Loaded dependency: {$key}", 'DEBUG');
        }

        // Verify classes exist
        if (!class_exists('JuniorGolfKenya_Database')) {
            throw new RuntimeException('JuniorGolfKenya_Database class not found after loading dependencies');
        }
        if (!class_exists('JuniorGolfKenya_Media')) {
            throw new RuntimeException('JuniorGolfKenya_Media class not found after loading dependencies');
        }

        $this->log_debug('All dependencies loaded successfully', 'DEBUG');
    }

    /**
     * Validate the AJAX request.
     *
     * @since    1.0.0
     * @return  bool|WP_Error True if valid, WP_Error if invalid.
     */
    private function validate_request() {
        $this->log_debug('Validating AJAX request', 'DEBUG');

        // Check if it's an AJAX request
        if (!wp_doing_ajax()) {
            return new WP_Error('invalid_request', 'This endpoint is for AJAX requests only');
        }

        // Verify nonce
        if (empty($_POST['nonce'])) {
            return new WP_Error('missing_nonce', 'Security nonce is missing');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'jgk_members_action')) {
            $this->log_debug('Nonce verification failed', 'WARNING', array(
                'received_nonce' => substr($_POST['nonce'], 0, 8) . '...'
            ));
            return new WP_Error('invalid_nonce', 'Security verification failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            $current_user = wp_get_current_user();
            $this->log_debug('Permission check failed', 'WARNING', array(
                'current_user' => $current_user->user_login,
                'capabilities' => $current_user->allcaps
            ));
            return new WP_Error('insufficient_permissions', 'You do not have permission to perform this action');
        }

        // Validate member ID
        if (empty($_POST['member_id'])) {
            return new WP_Error('missing_member_id', 'Member ID is required');
        }

        $member_id = absint($_POST['member_id']);
        if ($member_id <= 0) {
            return new WP_Error('invalid_member_id', 'Invalid member ID format');
        }

        $this->log_debug('Request validation passed', 'DEBUG');
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
        $this->log_debug("Retrieving member data for ID: {$member_id}", 'DEBUG');

        // Get member data
        $member = JuniorGolfKenya_Database::get_member($member_id);
        if (!$member) {
            $this->log_debug("Member not found in database", 'ERROR', array('member_id' => $member_id));
            return new WP_Error('member_not_found', 'Member not found in the system');
        }

        $this->log_debug("Member base data retrieved", 'DEBUG', array(
            'member_name' => $member->first_name . ' ' . $member->last_name,
            'status' => $member->status
        ));

        // Get additional data
        $member_parents = JuniorGolfKenya_Database::get_member_parents($member_id);
        $this->log_debug("Retrieved member parents", 'DEBUG', array(
            'parents_count' => is_countable($member_parents) ? count($member_parents) : 0
        ));

        // Get coach information
        $coach_name = '';
        if (!empty($member->coach_id)) {
            $coach = get_userdata($member->coach_id);
            $coach_name = $coach ? $coach->display_name : 'Unknown Coach';
            $this->log_debug("Coach data retrieved", 'DEBUG', array(
                'coach_id' => $member->coach_id,
                'coach_name' => $coach_name
            ));
        }

        // Calculate age
        $age = $this->calculate_age($member->date_of_birth);
        $this->log_debug("Age calculated", 'DEBUG', array(
            'date_of_birth' => $member->date_of_birth,
            'calculated_age' => $age
        ));

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
            $this->log_debug('Date of birth is empty, cannot calculate age', 'DEBUG');
            return '';
        }

        try {
            $birthdate = new DateTime($date_of_birth);
            $today = new DateTime();
            $age_years = $today->diff($birthdate)->y;
            $result = $age_years . ' years old';
            
            $this->log_debug("Age calculation successful", 'DEBUG', array(
                'date_of_birth' => $date_of_birth,
                'calculated_age' => $result
            ));
            
            return $result;
        } catch (Exception $e) {
            $this->log_debug("Age calculation failed", 'ERROR', array(
                'date_of_birth' => $date_of_birth,
                'error' => $e->getMessage()
            ));
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
        $this->log_debug('Generating member details HTML', 'DEBUG');

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
        } else {
            $this->log_debug('No parents data found for member', 'DEBUG');
        }

        $html .= '</div>'; // End member-details-wrapper

        $this->log_debug('Member details HTML generated successfully', 'DEBUG', array(
            'html_length' => strlen($html),
            'sections_generated' => 5 // profile + 4 grid sections + parents
        ));

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
        $this->log_debug('Generating profile section', 'DEBUG');

        $html = '<div class="member-profile-section">';
        $html .= '<div class="member-profile-header">';

        $this->log_debug('Calling get_profile_image_html', 'DEBUG', array(
            'member_id' => $member->id
        ));

        $profile_image_html = JuniorGolfKenya_Media::get_profile_image_html(
            $member->id,
            'medium',
            array(
                'style' => 'width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #0073aa;',
                'class' => 'member-profile-image'
            )
        );

        $this->log_debug('Profile image HTML received', 'DEBUG', array(
            'html_length' => strlen($profile_image_html),
            'has_image' => !empty($profile_image_html) && strpos($profile_image_html, 'src=') !== false
        ));

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

    // ... [The rest of your generate_*_section methods remain the same, but you can add debug logs to each if needed]

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

        foreach ($member_parents as $index => $parent) {
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