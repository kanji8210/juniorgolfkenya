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
        wp_enqueue_script($this->plugin_name, JUNIORGOLFKENYA_PLUGIN_URL . 'admin/js/juniorgolfkenya-admin.js', array('jquery'), $this->version, false);
        
        // Localize script with AJAX URL and nonces
        wp_localize_script($this->plugin_name, 'jgkAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'members_nonce' => wp_create_nonce('jgk_members_action')
        ));
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
