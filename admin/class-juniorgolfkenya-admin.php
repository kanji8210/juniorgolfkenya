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
    }

    /**
     * Add admin menu pages.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            'Junior Golf Kenya',
            'Golf Members',
            'manage_options',
            'juniorgolfkenya',
            array($this, 'display_admin_page'),
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Members',
            'Members',
            'manage_options',
            'juniorgolfkenya-members',
            array($this, 'display_members_page')
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Payments',
            'Payments',
            'manage_options',
            'juniorgolfkenya-payments',
            array($this, 'display_payments_page')
        );

        add_submenu_page(
            'juniorgolfkenya',
            'Reports',
            'Reports',
            'manage_options',
            'juniorgolfkenya-reports',
            array($this, 'display_reports_page')
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
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-settings.php';
    }
}