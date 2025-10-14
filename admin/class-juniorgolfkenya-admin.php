<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class JuniorGolfKenya_Admin {

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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, JUNIORGOLFKENYA_PLUGIN_URL . 'admin/css/juniorgolfkenya-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Debug: Log current screen
        $screen = get_current_screen();
        error_log('JGK Enqueue Debug - Screen ID: ' . ($screen ? $screen->id : 'null'));
        error_log('JGK Enqueue Debug - Page: ' . (isset($_GET['page']) ? $_GET['page'] : 'null'));

        wp_enqueue_script($this->plugin_name, JUNIORGOLFKENYA_PLUGIN_URL . 'admin/js/juniorgolfkenya-admin.js', array('jquery'), $this->version, false);

        // Load Chart.js only on reports page
        if (isset($_GET['page']) && $_GET['page'] === 'juniorgolfkenya-reports') {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true);
        }

        // Localize script with AJAX URL and nonces
        wp_localize_script($this->plugin_name, 'jgkAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'members_nonce' => wp_create_nonce('jgk_members_action'),
            'reports_nonce' => wp_create_nonce('jgk_reports_action')
        ));

        error_log('JGK Enqueue Debug - Script enqueued and localized successfully');
    }

    /**
     * Handle AJAX request for PDF export.
     *
     * @since    1.0.0
     */
    public function export_reports_pdf() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'jgk_reports_action')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('view_reports')) {
            wp_die('Insufficient permissions');
        }

        $report_type = sanitize_text_field($_POST['report_type']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);

        // Generate PDF content
        $pdf_content = $this->generate_pdf_content($report_type, $start_date, $end_date);

        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="jgk-report-' . $report_type . '-' . date('Y-m-d') . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Output PDF content
        echo $pdf_content;
        wp_die();
    }

    /**
     * Generate PDF content for reports.
     *
     * @param string $report_type Type of report
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return string PDF content
     */
    private function generate_pdf_content($report_type, $start_date, $end_date) {
        global $wpdb;

        $content = "Junior Golf Kenya - " . ucfirst($report_type) . " Report\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
        $content .= "Period: " . $start_date . " to " . $end_date . "\n\n";

        switch ($report_type) {
            case 'overview':
                // Get overview statistics
                $overview_stats = $this->get_overview_stats($start_date, $end_date);
                $content .= "OVERVIEW STATISTICS\n";
                $content .= "==================\n\n";
                $content .= "Total Members: " . $overview_stats['total_members'] . "\n";
                $content .= "Active Members: " . $overview_stats['active_members'] . "\n";
                $content .= "Total Revenue: KSh " . number_format($overview_stats['total_revenue']) . "\n";
                $content .= "Total Payments: " . $overview_stats['total_payments'] . "\n\n";

                // Monthly data
                $monthly_data = $this->get_monthly_data($start_date, $end_date);
                $content .= "MONTHLY BREAKDOWN\n";
                $content .= "=================\n\n";
                foreach ($monthly_data as $month => $data) {
                    $content .= $month . ":\n";
                    $content .= "  Members: " . $data['members'] . "\n";
                    $content .= "  New Members: " . $data['new_members'] . "\n";
                    $content .= "  Revenue: KSh " . number_format($data['revenue']) . "\n\n";
                }
                break;

            case 'payments':
                // Get payment statistics
                $payment_stats = $this->get_payment_stats($start_date, $end_date);
                $content .= "PAYMENT STATISTICS\n";
                $content .= "==================\n\n";
                $content .= "Total Payments: " . $payment_stats['total_payments'] . "\n";
                $content .= "Total Revenue: KSh " . number_format($payment_stats['total_revenue']) . "\n";
                $content .= "Average Payment: KSh " . number_format($payment_stats['average_payment']) . "\n\n";

                $content .= "PAYMENT METHODS\n";
                $content .= "===============\n\n";
                foreach ($payment_stats['by_method'] as $method => $data) {
                    $content .= $method . ": " . $data['count'] . " payments, KSh " . number_format($data['amount']) . "\n";
                }
                break;

            case 'members':
                // Get member statistics
                $member_stats = $this->get_member_stats($start_date, $end_date);
                $content .= "MEMBER STATISTICS\n";
                $content .= "=================\n\n";
                $content .= "Total Members: " . $member_stats['total_members'] . "\n";
                $content .= "Active Members: " . $member_stats['active_members'] . "\n";
                $content .= "New Members: " . $member_stats['new_members'] . "\n\n";

                $content .= "MEMBERS BY STATUS\n";
                $content .= "=================\n\n";
                foreach ($member_stats['by_status'] as $status => $count) {
                    $content .= ucfirst($status) . ": " . $count . "\n";
                }
                break;
        }

        return $content;
    }

    /**
     * Get overview statistics for PDF.
     */
    private function get_overview_stats($start_date, $end_date) {
        global $wpdb;

        $stats = array(
            'total_members' => 0,
            'active_members' => 0,
            'total_revenue' => 0,
            'total_payments' => 0
        );

        // Total members
        $stats['total_members'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jgk_members");

        // Active members
        $stats['active_members'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jgk_members WHERE status = 'active'");

        // Total revenue and payments
        $query = $wpdb->prepare(
            "SELECT SUM(amount) as revenue, COUNT(*) as payments FROM {$wpdb->prefix}jgk_payments
             WHERE payment_date BETWEEN %s AND %s",
            $start_date, $end_date
        );
        $result = $wpdb->get_row($query);
        $stats['total_revenue'] = $result->revenue ?: 0;
        $stats['total_payments'] = $result->payments ?: 0;

        return $stats;
    }

    /**
     * Get monthly data for PDF.
     */
    private function get_monthly_data($start_date, $end_date) {
        global $wpdb;

        $monthly_data = array();

        $query = $wpdb->prepare(
            "SELECT
                DATE_FORMAT(payment_date, '%Y-%m') as month,
                COUNT(DISTINCT member_id) as members,
                SUM(amount) as revenue
             FROM {$wpdb->prefix}jgk_payments
             WHERE payment_date BETWEEN %s AND %s
             GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
             ORDER BY month",
            $start_date, $end_date
        );

        $results = $wpdb->get_results($query);

        foreach ($results as $row) {
            $monthly_data[$row->month] = array(
                'members' => $row->members,
                'new_members' => 0, // Would need more complex query for new members
                'revenue' => $row->revenue
            );
        }

        return $monthly_data;
    }

    /**
     * Get payment statistics for PDF.
     */
    private function get_payment_stats($start_date, $end_date) {
        global $wpdb;

        $stats = array(
            'total_payments' => 0,
            'total_revenue' => 0,
            'average_payment' => 0,
            'by_method' => array()
        );

        $query = $wpdb->prepare(
            "SELECT
                COUNT(*) as total_payments,
                SUM(amount) as total_revenue,
                AVG(amount) as average_payment
             FROM {$wpdb->prefix}jgk_payments
             WHERE payment_date BETWEEN %s AND %s",
            $start_date, $end_date
        );

        $result = $wpdb->get_row($query);
        $stats['total_payments'] = $result->total_payments ?: 0;
        $stats['total_revenue'] = $result->total_revenue ?: 0;
        $stats['average_payment'] = $result->average_payment ?: 0;

        // Payment methods
        $method_query = $wpdb->prepare(
            "SELECT payment_method, COUNT(*) as count, SUM(amount) as amount
             FROM {$wpdb->prefix}jgk_payments
             WHERE payment_date BETWEEN %s AND %s
             GROUP BY payment_method",
            $start_date, $end_date
        );

        $method_results = $wpdb->get_results($method_query);
        foreach ($method_results as $row) {
            $stats['by_method'][$row->payment_method] = array(
                'count' => $row->count,
                'amount' => $row->amount
            );
        }

        return $stats;
    }

    /**
     * Get member statistics for PDF.
     */
    private function get_member_stats($start_date, $end_date) {
        global $wpdb;

        $stats = array(
            'total_members' => 0,
            'active_members' => 0,
            'new_members' => 0,
            'by_status' => array()
        );

        // Total members
        $stats['total_members'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jgk_members");

        // Active members
        $stats['active_members'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jgk_members WHERE status = 'active'");

        // New members in period
        $stats['new_members'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jgk_members WHERE DATE(created_at) BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        // Members by status
        $status_results = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$wpdb->prefix}jgk_members GROUP BY status");
        foreach ($status_results as $row) {
            $stats['by_status'][$row->status] = $row->count;
        }

        return $stats;
    }

    /**
     * Add admin menu pages.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu with golf icon
        $golf_icon = 'data:image/svg+xml;base64,' . base64_encode('
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#a7aaad">
                <path d="M12.24 2.06c-.03-.01-.06-.02-.09-.03-.11-.03-.22-.03-.33 0l-.09.03c-.18.06-.34.17-.46.31L9.4 4.54c-.12.14-.2.31-.23.49-.02.08-.03.17-.03.26v14.45c0 .41.34.75.75.75s.75-.34.75-.75V10.7l1.86-2.17c.14-.16.22-.36.22-.58V5.37c0-.19-.07-.36-.19-.49-.12-.13-.28-.2-.45-.22zM13.5 5.37v2.58l-1.5 1.75-1.5-1.75V5.37h3zM6.5 18c0-2.21 1.79-4 4-4s4 1.79 4 4-1.79 4-4 4-4-1.79-4-4zm1.5 0c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5S8 16.62 8 18z"/>
            </svg>
        ');

        add_menu_page(
            'Junior Golf Kenya',
            'JuniorGolfKenya',
            'view_member_dashboard',
            'juniorgolfkenya',
            array($this, 'display_admin_page'),
            $golf_icon,
            30
        );

        add_submenu_page(
            'juniorgolfkenya',
            'JGK Members',
            'JGK Members',
            'edit_members',
            'juniorgolfkenya-members',
            array($this, 'display_members_page')
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Coaches',
            'Coaches',
            'approve_role_requests',
            'juniorgolfkenya-coaches',
            array($this, 'display_coaches_page')
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Role Requests',
            'Role Requests',
            'approve_role_requests',
            'juniorgolfkenya-role-requests',
            array($this, 'display_role_requests_page')
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Payments',
            'Payments',
            'manage_payments',
            'juniorgolfkenya-payments',
            array($this, 'display_payments_page')
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Reports',
            'Reports',
            'view_reports',
            'juniorgolfkenya-reports',
            array($this, 'display_reports_page')
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Import from ARMember',
            'Import Data',
            'manage_options',
            'juniorgolfkenya-import',
            array($this, 'display_import_page')
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Settings',
            'Settings',
            'manage_options',
            'juniorgolfkenya-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display the main admin page.
     *
     * @since    1.0.0
     */
    public function display_admin_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-display.php';
    }

    /**
     * Display the members page.
     *
     * @since    1.0.0
     */
    public function display_members_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-members.php';
    }

    /**
     * Display the coaches page.
     *
     * @since    1.0.0
     */
    public function display_coaches_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-coaches.php';
    }

    /**
     * Display the role requests page.
     *
     * @since    1.0.0
     */
    public function display_role_requests_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-role-requests.php';
    }

    /**
     * Display the payments page.
     *
     * @since    1.0.0
     */
    public function display_payments_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-payments.php';
    }

    /**
     * Display the reports page.
     *
     * @since    1.0.0
     */
    public function display_reports_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-reports.php';
    }

    /**
     * Display the import page.
     *
     * @since    1.0.0
     */
    public function display_import_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-import.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-settings.php';
    }

    /**
     * Display activation notice after plugin activation
     *
     * @since    1.0.0
     */
    public function display_activation_notice() {
        $activation_data = get_transient('jgk_activation_notice');
        
        if (!$activation_data) {
            return;
        }
        
        // Delete the transient so it only shows once
        delete_transient('jgk_activation_notice');
        
        $verification = $activation_data['verification'];
        
        if ($verification['success']) {
            $total_tables = count($verification['existing']);
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Junior Golf Kenya Plugin Activated Successfully!</strong></p>';
            echo '<p>✅ All ' . esc_html($total_tables) . ' database tables were created successfully.</p>';
            echo '<p>Tables created: <code>' . esc_html(implode(', ', $verification['existing'])) . '</code></p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>Junior Golf Kenya Plugin Activation Warning!</strong></p>';
            echo '<p>⚠️ Some database tables could not be created.</p>';
            
            if (!empty($verification['existing'])) {
                echo '<p>✅ Successfully created: <code>' . esc_html(implode(', ', $verification['existing'])) . '</code></p>';
            }
            
            if (!empty($verification['missing'])) {
                echo '<p>❌ Failed to create: <code>' . esc_html(implode(', ', $verification['missing'])) . '</code></p>';
                echo '<p>Please check your database permissions or contact support.</p>';
            }
            
            echo '</div>';
        }
    }
}
