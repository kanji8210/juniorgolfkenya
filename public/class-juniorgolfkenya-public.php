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
}