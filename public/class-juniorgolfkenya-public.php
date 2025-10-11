<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 */
class JuniorGolfKenya_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, JUNIORGOLFKENYA_PLUGIN_URL . 'public/css/juniorgolfkenya-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, JUNIORGOLFKENYA_PLUGIN_URL . 'public/js/juniorgolfkenya-public.js', array('jquery'), $this->version, false);
    }

    /**
     * Initialize shortcodes.
     *
     * @since    1.0.0
     */
    public function init_shortcodes() {
        add_shortcode('jgk_member_portal', array($this, 'member_portal_shortcode'));
        add_shortcode('jgk_registration_form', array($this, 'registration_form_shortcode'));
        add_shortcode('jgk_verification_widget', array($this, 'verification_widget_shortcode'));
        add_shortcode('jgk_coach_dashboard', array($this, 'coach_dashboard_shortcode'));
        add_shortcode('jgk_member_dashboard', array($this, 'member_dashboard_shortcode'));
    }

    /**
     * Member portal shortcode.
     *
     * @since    1.0.0
     */
    public function member_portal_shortcode($atts) {
        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-member-portal.php';
        return ob_get_clean();
    }

    /**
     * Registration form shortcode.
     *
     * @since    1.0.0
     */
    public function registration_form_shortcode($atts) {
        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-registration-form.php';
        return ob_get_clean();
    }

    /**
     * Verification widget shortcode.
     *
     * @since    1.0.0
     */
    public function verification_widget_shortcode($atts) {
        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-verification-widget.php';
        return ob_get_clean();
    }

    /**
     * Coach dashboard shortcode.
     *
     * @since    1.0.0
     */
    public function coach_dashboard_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="jgk-notice jgk-notice-error">You must be logged in to view this page.</div>';
        }

        // Get current user
        $current_user = wp_get_current_user();
        
        // Check if user has coach role
        if (!in_array('jgk_coach', $current_user->roles)) {
            return '<div class="jgk-notice jgk-notice-error">You do not have permission to view this page.</div>';
        }

        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-coach-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Member dashboard shortcode.
     *
     * @since    1.0.0
     */
    public function member_dashboard_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="jgk-notice jgk-notice-error">You must be logged in to view this page.</div>';
        }

        // Get current user
        $current_user = wp_get_current_user();
        
        // Check if user has member role
        if (!in_array('jgk_member', $current_user->roles)) {
            return '<div class="jgk-notice jgk-notice-error">You do not have permission to view this page.</div>';
        }

        // Get member record from database
        global $wpdb;
        $members_table = $wpdb->prefix . 'jgk_members';
        $member = $wpdb->get_row($wpdb->prepare("
            SELECT status, first_name, last_name, membership_number
            FROM {$members_table}
            WHERE user_id = %d
        ", $current_user->ID));

        // Check if member exists and status
        if (!$member) {
            return '<div class="jgk-notice jgk-notice-error">Member profile not found. Please contact administrator.</div>';
        }

        // If status is pending, show waiting message
        if ($member->status === 'pending' || $member->status === 'pending_approval') {
            ob_start();
            ?>
            <div class="jgk-pending-approval">
                <div class="jgk-pending-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <h2>Membership Pending Approval</h2>
                <p>Hello <?php echo esc_html($member->first_name . ' ' . $member->last_name); ?>,</p>
                <div class="jgk-pending-details">
                    <p>Thank you for registering with Junior Golf Kenya!</p>
                    <p><strong>Your membership number:</strong> <?php echo esc_html($member->membership_number); ?></p>
                    <p>Your membership is currently <strong>pending approval</strong> by our administration team. You will receive an email notification once your membership is approved.</p>
                    <p>Once approved, you will be able to access:</p>
                    <ul>
                        <li>✅ Your personal member dashboard</li>
                        <li>✅ Coach information and contact details</li>
                        <li>✅ Training schedules and events</li>
                        <li>✅ Your profile and membership information</li>
                    </ul>
                    <p>If you have any questions, please contact us at <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>"><?php echo esc_html(get_option('admin_email')); ?></a></p>
                </div>
            </div>
            <style>
                .jgk-pending-approval {
                    max-width: 700px;
                    margin: 60px auto;
                    padding: 0;
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                }
                .jgk-pending-icon {
                    width: 100px;
                    height: 100px;
                    margin: -50px auto 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    border-radius: 50%;
                    color: white;
                    font-size: 50px;
                    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
                }
                .jgk-pending-approval h2 {
                    margin: 0 0 20px 0;
                    padding: 30px 40px 0;
                    color: #2c3e50;
                    font-size: 28px;
                    font-weight: 700;
                }
                .jgk-pending-approval > p {
                    margin: 0 0 30px 0;
                    padding: 0 40px;
                    color: #7f8c8d;
                    font-size: 16px;
                }
                .jgk-pending-details {
                    padding: 30px 40px 40px;
                    background: #f8f9fa;
                    border-radius: 0 0 15px 15px;
                    text-align: left;
                }
                .jgk-pending-details p {
                    margin: 0 0 15px 0;
                    color: #2c3e50;
                    font-size: 15px;
                    line-height: 1.6;
                }
                .jgk-pending-details p:last-of-type {
                    margin-bottom: 0;
                }
                .jgk-pending-details ul {
                    margin: 20px 0;
                    padding-left: 0;
                    list-style: none;
                }
                .jgk-pending-details li {
                    margin-bottom: 10px;
                    padding-left: 5px;
                    color: #2c3e50;
                    font-size: 15px;
                }
                .jgk-pending-details a {
                    color: #4facfe;
                    text-decoration: none;
                }
                .jgk-pending-details a:hover {
                    text-decoration: underline;
                }
                @media (max-width: 768px) {
                    .jgk-pending-approval {
                        margin: 40px 15px;
                    }
                    .jgk-pending-approval h2 {
                        padding: 20px 20px 0;
                        font-size: 22px;
                    }
                    .jgk-pending-approval > p {
                        padding: 0 20px;
                    }
                    .jgk-pending-details {
                        padding: 20px;
                    }
                }
            </style>
            <?php
            return ob_get_clean();
        }

        // If status is suspended or expired, show appropriate message
        if ($member->status === 'suspended') {
            return '<div class="jgk-notice jgk-notice-error">Your membership is currently suspended. Please contact administrator for more information.</div>';
        }

        if ($member->status === 'expired') {
            return '<div class="jgk-notice jgk-notice-warning">Your membership has expired. Please renew your membership to access the dashboard.</div>';
        }

        // Member is active, show dashboard
        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-member-dashboard.php';
        return ob_get_clean();
    }
}