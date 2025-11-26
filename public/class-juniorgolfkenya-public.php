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
    
    function printUrl() {

        echo JUNIORGOLFKENYA_PLUGIN_URL;
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
        add_shortcode('jgk_parent_dashboard', array($this, 'parent_dashboard_shortcode'));
        add_shortcode('jgk_public_members', array($this, 'public_members_shortcode'));
        add_shortcode('jgk_coach_request_form', array($this, 'coach_request_form_shortcode'));
    }

    /**
     * Initialize WooCommerce integration.
     *
     * @since    1.0.0
     */
    public function init_woocommerce_integration() {
        JuniorGolfKenya_WooCommerce::init();
    }

    /**
     * Member portal shortcode.
     *
     * @since    1.0.0
     */
    public function member_portal_shortcode($atts) {
        // Enqueue assets for the member portal
        wp_enqueue_style(
            'jgk-member-portal',
            JUNIORGOLFKENYA_PLUGIN_URL . 'public/partials/css/juniorgolfkenya-member-portal.css',
            array(),
            '1.0.0'
        );

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
        // Enqueue assets for the registration form
        $base_url = JUNIORGOLFKENYA_PLUGIN_URL;

        // Fix for local development - force HTTP instead of HTTPS
        if (strpos($base_url, 'https://localhost') === 0) {
            $base_url = str_replace('https://localhost', 'http://localhost', $base_url);
        }

        $css_url = $base_url . 'public/partials/css/juniorgolfkenya-registration-form.css';
        $js_url = $base_url . 'public/partials/js/juniorgolfkenya-registration-form.js';

        wp_enqueue_style(
            'jgk-registration-form',
            $css_url,
            array(),
            '1.0.0'
        );
        wp_enqueue_script(
            'jgk-registration-form',
            $js_url,
            array('jquery'),
            '1.0.0',
            true
        );

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
        // Enqueue assets for the verification widget
        wp_enqueue_style(
            'jgk-verification-widget',
            JUNIORGOLFKENYA_PLUGIN_URL . 'public/partials/css/juniorgolfkenya-verification-widget.css',
            array(),
            '1.0.0'
        );

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
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $login_url = wp_login_url($current_url);
            $coach_request_url = home_url('/coach-role-request');
            
            ob_start();
            ?>
            <div class="jgk-login-required">
                <div class="jgk-login-box">
                    <div class="jgk-login-icon">
                        <span class="dashicons dashicons-lock"></span>
                    </div>
                    <h2>Login Required</h2>
                    <p>You must be logged in to access the Coach Dashboard.</p>
                    <div class="jgk-login-actions">
                        <a href="<?php echo esc_url($login_url); ?>" class="jgk-btn jgk-btn-primary">
                            <span class="dashicons dashicons-admin-users"></span>
                            Login to Your Account
                        </a>
                        <p class="jgk-or-divider">or</p>
                        <a href="<?php echo esc_url($coach_request_url); ?>" class="jgk-btn jgk-btn-secondary">
                            <span class="dashicons dashicons-star-filled"></span>
                            Apply to Become a Coach
                        </a>
                    </div>
                    <p class="jgk-help-text">
                        Need help? <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">Contact us</a>
                    </p>
                </div>
            </div>
            
            <style>
                .jgk-login-required {
                    max-width: 500px;
                    margin: 80px auto;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                }
                .jgk-login-box {
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                    padding: 0;
                    text-align: center;
                    overflow: hidden;
                }
                .jgk-login-icon {
                    width: 100px;
                    height: 100px;
                    margin: -50px auto 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 50%;
                    color: white;
                    font-size: 50px;
                    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
                }
                .jgk-login-icon .dashicons {
                    width: 50px;
                    height: 50px;
                    font-size: 50px;
                }
                .jgk-login-box h2 {
                    margin: 0 0 15px 0;
                    padding: 30px 40px 0;
                    color: #2c3e50;
                    font-size: 28px;
                    font-weight: 700;
                }
                .jgk-login-box > p {
                    margin: 0 0 30px 0;
                    padding: 0 40px;
                    color: #7f8c8d;
                    font-size: 16px;
                }
                .jgk-login-actions {
                    padding: 30px 40px;
                    background: #f8f9fa;
                }
                .jgk-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    padding: 14px 30px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 16px;
                    transition: all 0.3s ease;
                    width: 100%;
                    max-width: 320px;
                    margin: 0 auto;
                }
                .jgk-btn-primary {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                }
                .jgk-btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                    color: white;
                }
                .jgk-btn-secondary {
                    background: white;
                    color: #667eea;
                    border: 2px solid #667eea;
                    margin-top: 10px;
                }
                .jgk-btn-secondary:hover {
                    background: #667eea;
                    color: white;
                    transform: translateY(-2px);
                }
                .jgk-btn .dashicons {
                    width: 20px;
                    height: 20px;
                    font-size: 20px;
                }
                .jgk-or-divider {
                    margin: 20px 0;
                    color: #95a5a6;
                    font-size: 14px;
                    position: relative;
                }
                .jgk-or-divider::before,
                .jgk-or-divider::after {
                    content: '';
                    position: absolute;
                    top: 50%;
                    width: 40%;
                    height: 1px;
                    background: #ddd;
                }
                .jgk-or-divider::before {
                    left: 0;
                }
                .jgk-or-divider::after {
                    right: 0;
                }
                .jgk-help-text {
                    margin: 20px 0 0 0;
                    padding: 0 40px 30px;
                    font-size: 14px;
                    color: #7f8c8d;
                }
                .jgk-help-text a {
                    color: #667eea;
                    text-decoration: none;
                    font-weight: 600;
                }
                .jgk-help-text a:hover {
                    text-decoration: underline;
                }
                @media (max-width: 768px) {
                    .jgk-login-required {
                        margin: 40px 15px;
                    }
                    .jgk-login-box h2 {
                        padding: 20px 20px 0;
                        font-size: 24px;
                    }
                    .jgk-login-box > p {
                        padding: 0 20px;
                        font-size: 15px;
                    }
                    .jgk-login-actions,
                    .jgk-help-text {
                        padding-left: 20px;
                        padding-right: 20px;
                    }
                }
            </style>
            <?php
            return ob_get_clean();
        }

        // Get current user
        $current_user = wp_get_current_user();
        
        // TEMPORAIRE : Permission check désactivée pour test
        // Check if user has coach role
        // if (!in_array('jgk_coach', $current_user->roles)) {
        //     return '<div class="jgk-notice jgk-notice-error">You do not have permission to view this page.</div>';
        // }

        // Enqueue assets for the coach dashboard
        wp_enqueue_style(
            'jgk-coach-dashboard',
            JUNIORGOLFKENYA_PLUGIN_URL . 'public/partials/css/juniorgolfkenya-coach-dashboard.css',
            array(),
            '1.0.0'
        );

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
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $login_url = wp_login_url($current_url);
            $register_url = home_url('/member-registration');
            
            ob_start();
            ?>
            <div class="jgk-login-required">
                <div class="jgk-login-box">
                    <div class="jgk-login-icon">
                        <span class="dashicons dashicons-lock"></span>
                    </div>
                    <h2>Login Required</h2>
                    <p>You must be logged in to access your Member Dashboard.</p>
                    <div class="jgk-login-actions">
                        <a href="<?php echo esc_url($login_url); ?>" class="jgk-btn jgk-btn-primary">
                            <span class="dashicons dashicons-admin-users"></span>
                            Login to Your Account
                        </a>
                        <p class="jgk-or-divider">or</p>
                        <a href="<?php echo esc_url($register_url); ?>" class="jgk-btn jgk-btn-secondary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Become a Member
                        </a>
                    </div>
                    <p class="jgk-help-text">
                        Need help? <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">Contact us</a>
                    </p>
                </div>
            </div>
            
            <style>
                .jgk-login-required {
                    max-width: 500px;
                    margin: 80px auto;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                }
                .jgk-login-box {
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                    padding: 0;
                    text-align: center;
                    overflow: hidden;
                }
                .jgk-login-icon {
                    width: 100px;
                    height: 100px;
                    margin: -50px auto 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 50%;
                    color: white;
                    font-size: 50px;
                    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
                }
                .jgk-login-icon .dashicons {
                    width: 50px;
                    height: 50px;
                    font-size: 50px;
                }
                .jgk-login-box h2 {
                    margin: 0 0 15px 0;
                    padding: 30px 40px 0;
                    color: #2c3e50;
                    font-size: 28px;
                    font-weight: 700;
                }
                .jgk-login-box > p {
                    margin: 0 0 30px 0;
                    padding: 0 40px;
                    color: #7f8c8d;
                    font-size: 16px;
                }
                .jgk-login-actions {
                    padding: 30px 40px;
                    background: #f8f9fa;
                }
                .jgk-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    padding: 14px 30px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 16px;
                    transition: all 0.3s ease;
                    width: 100%;
                    max-width: 320px;
                    margin: 0 auto;
                }
                .jgk-btn-primary {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                }
                .jgk-btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                    color: white;
                }
                .jgk-btn-secondary {
                    background: white;
                    color: #667eea;
                    border: 2px solid #667eea;
                    margin-top: 10px;
                }
                .jgk-btn-secondary:hover {
                    background: #667eea;
                    color: white;
                    transform: translateY(-2px);
                }
                .jgk-btn .dashicons {
                    width: 20px;
                    height: 20px;
                    font-size: 20px;
                }
                .jgk-or-divider {
                    margin: 20px 0;
                    color: #95a5a6;
                    font-size: 14px;
                    position: relative;
                }
                .jgk-or-divider::before,
                .jgk-or-divider::after {
                    content: '';
                    position: absolute;
                    top: 50%;
                    width: 40%;
                    height: 1px;
                    background: #ddd;
                }
                .jgk-or-divider::before {
                    left: 0;
                }
                .jgk-or-divider::after {
                    right: 0;
                }
                .jgk-help-text {
                    margin: 20px 0 0 0;
                    padding: 0 40px 30px;
                    font-size: 14px;
                    color: #7f8c8d;
                }
                .jgk-help-text a {
                    color: #667eea;
                    text-decoration: none;
                    font-weight: 600;
                }
                .jgk-help-text a:hover {
                    text-decoration: underline;
                }
                @media (max-width: 768px) {
                    .jgk-login-required {
                        margin: 40px 15px;
                    }
                    .jgk-login-box h2 {
                        padding: 20px 20px 0;
                        font-size: 24px;
                    }
                    .jgk-login-box > p {
                        padding: 0 20px;
                        font-size: 15px;
                    }
                    .jgk-login-actions,
                    .jgk-help-text {
                        padding-left: 20px;
                        padding-right: 20px;
                    }
                }
            </style>
            <?php
            return ob_get_clean();
        }

        // Get current user
        $current_user = wp_get_current_user();
        
        // Check if user is a parent - if so, redirect to parent dashboard
        require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-parent-dashboard.php';
        if (JuniorGolfKenya_Parent_Dashboard::is_parent($current_user->user_email)) {
            // User is a parent, show parent dashboard instead
            return $this->parent_dashboard_shortcode($atts);
        }
        
        // TEMPORAIRE : Permission check désactivée pour test
        // Check if user has member role
        // if (!in_array('jgk_member', $current_user->roles)) {
        //     return '<div class="jgk-notice jgk-notice-error">You do not have permission to view this page.</div>';
        // }

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
        // Enqueue assets for the member dashboard
        wp_enqueue_style(
            'jgk-member-dashboard',
            JUNIORGOLFKENYA_PLUGIN_URL . 'public/partials/css/juniorgolfkenya-member-dashboard.css',
            array(),
            '1.0.0'
        );

        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-member-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Parent dashboard shortcode.
     *
     * @since    1.0.0
     */
    public function parent_dashboard_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $login_url = wp_login_url($current_url);
            $register_url = home_url('/member-registration');
            
            ob_start();
            ?>
            <div class="jgk-login-required">
                <div class="jgk-login-box">
                    <div class="jgk-login-icon">
                        <span class="dashicons dashicons-lock"></span>
                    </div>
                    <h2>Login Required</h2>
                    <p>You must be logged in to access your Parent Dashboard.</p>
                    <div class="jgk-login-actions">
                        <a href="<?php echo esc_url($login_url); ?>" class="jgk-btn jgk-btn-primary">
                            <span class="dashicons dashicons-admin-users"></span>
                            Login to Your Account
                        </a>
                        <p class="jgk-or-divider">or</p>
                        <a href="<?php echo esc_url($register_url); ?>" class="jgk-btn jgk-btn-secondary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Register a Junior Member
                        </a>
                    </div>
                    <p class="jgk-help-text">
                        Need help? <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">Contact us</a>
                    </p>
                </div>
            </div>
            
            <style>
                .jgk-login-required {
                    max-width: 500px;
                    margin: 80px auto;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                }
                .jgk-login-box {
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                    padding: 0;
                    text-align: center;
                    overflow: hidden;
                }
                .jgk-login-icon {
                    width: 100px;
                    height: 100px;
                    margin: -50px auto 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #0ea57a 0%, #078a60 100%);
                    border-radius: 50%;
                    color: white;
                    font-size: 50px;
                    box-shadow: 0 8px 25px rgba(14, 165, 122, 0.3);
                }
                .jgk-login-icon .dashicons {
                    width: 50px;
                    height: 50px;
                    font-size: 50px;
                }
                .jgk-login-box h2 {
                    margin: 0 0 15px 0;
                    padding: 30px 40px 0;
                    color: #2c3e50;
                    font-size: 28px;
                    font-weight: 700;
                }
                .jgk-login-box > p {
                    margin: 0 0 30px 0;
                    padding: 0 40px;
                    color: #7f8c8d;
                    font-size: 16px;
                }
                .jgk-login-actions {
                    padding: 30px 40px;
                    background: #f8f9fa;
                }
                .jgk-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    padding: 14px 30px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 16px;
                    transition: all 0.3s ease;
                    width: 100%;
                    max-width: 320px;
                    margin: 0 auto;
                }
                .jgk-btn-primary {
                    background: linear-gradient(135deg, #0ea57a 0%, #078a60 100%);
                    color: white;
                    box-shadow: 0 4px 15px rgba(14, 165, 122, 0.3);
                }
                .jgk-btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(14, 165, 122, 0.4);
                    color: white;
                }
                .jgk-btn-secondary {
                    background: white;
                    color: #0ea57a;
                    border: 2px solid #0ea57a;
                    margin-top: 10px;
                }
                .jgk-btn-secondary:hover {
                    background: #0ea57a;
                    color: white;
                    transform: translateY(-2px);
                }
                .jgk-btn .dashicons {
                    width: 20px;
                    height: 20px;
                    font-size: 20px;
                }
                .jgk-or-divider {
                    margin: 20px 0;
                    color: #95a5a6;
                    font-size: 14px;
                    position: relative;
                }
                .jgk-or-divider::before,
                .jgk-or-divider::after {
                    content: '';
                    position: absolute;
                    top: 50%;
                    width: 40%;
                    height: 1px;
                    background: #ddd;
                }
                .jgk-or-divider::before {
                    left: 0;
                }
                .jgk-or-divider::after {
                    right: 0;
                }
                .jgk-help-text {
                    margin: 20px 0 0 0;
                    padding: 0 40px 30px;
                    font-size: 14px;
                    color: #7f8c8d;
                }
                .jgk-help-text a {
                    color: #0ea57a;
                    text-decoration: none;
                    font-weight: 600;
                }
                .jgk-help-text a:hover {
                    text-decoration: underline;
                }
                @media (max-width: 768px) {
                    .jgk-login-required {
                        margin: 40px 15px;
                    }
                    .jgk-login-box h2 {
                        padding: 20px 20px 0;
                        font-size: 24px;
                    }
                    .jgk-login-box > p {
                        padding: 0 20px;
                        font-size: 15px;
                    }
                    .jgk-login-actions,
                    .jgk-help-text {
                        padding-left: 20px;
                        padding-right: 20px;
                    }
                }
            </style>
            <?php
            return ob_get_clean();
        }

        // Get current user
        $current_user = wp_get_current_user();
        
        // Load parent dashboard class
        require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-parent-dashboard.php';
        
        // Check if user is a parent
        if (!JuniorGolfKenya_Parent_Dashboard::is_parent($current_user->user_email)) {
            return '<div class="jgk-notice jgk-notice-error">No children registered under this account. Please contact administrator if this is an error.</div>';
        }

        // Enqueue assets for the parent dashboard
        wp_enqueue_style(
            'jgk-parent-dashboard',
            JUNIORGOLFKENYA_PLUGIN_URL . 'public/partials/css/juniorgolfkenya-member-dashboard.css',
            array(),
            '1.0.0'
        );

        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-parent-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Public members gallery shortcode.
     *
     * @since    1.0.0
     */
    public function public_members_shortcode($atts) {
        // Enqueue assets for public members
        wp_enqueue_style(
            'jgk-public-members',
            JUNIORGOLFKENYA_PLUGIN_URL . 'public/partials/css/juniorgolfkenya-public-members.css',
            array(),
            '1.0.0'
        );

        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-public-members.php';
        return ob_get_clean();
    }

    /**
     * Coach request form shortcode.
     *
     * @since    1.0.0
     */
    public function coach_request_form_shortcode($atts) {
        // Enqueue assets for coach request form
        wp_enqueue_style(
            'jgk-coach-request-form',
            JUNIORGOLFKENYA_PLUGIN_URL . 'public/partials/css/juniorgolfkenya-coach-request-form.css',
            array(),
            '1.0.0'
        );

        ob_start();
        include JUNIORGOLFKENYA_PLUGIN_PATH . 'public/partials/juniorgolfkenya-coach-request-form.php';
        return ob_get_clean();
    }
}