<?php

/**
 * Fired during plugin uninstall
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

/**
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's uninstall.
 */
class JuniorGolfKenya_Uninstaller {

    /**
     * Short Description.
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function uninstall() {
        // Check if user has permission to uninstall
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Get uninstall options
        $delete_data = get_option('jgk_delete_data_on_uninstall', false);
        
        if ($delete_data) {
            self::delete_tables();
            self::delete_options();
            self::delete_pages();
            self::delete_user_meta();
            self::delete_uploads();
        }
        
        self::clear_all_scheduled_events();
        self::log_uninstall();
    }

    /**
     * Delete all plugin tables
     *
     * @since    1.0.0
     */
    private static function delete_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'jgk_members',
            $wpdb->prefix . 'jgk_memberships',
            $wpdb->prefix . 'jgk_plans',
            $wpdb->prefix . 'jgk_payments',
            $wpdb->prefix . 'jgk_competition_entries',
            $wpdb->prefix . 'jgk_certifications',
            $wpdb->prefix . 'jgk_audit_log'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Delete all plugin options
     *
     * @since    1.0.0
     */
    private static function delete_options() {
        $options = array(
            'jgk_payment_gateway',
            'jgk_currency',
            'jgk_renewal_reminder_days',
            'jgk_grace_period_days',
            'jgk_email_notifications',
            'jgk_public_verification',
            'jgk_stripe_publishable_key',
            'jgk_stripe_secret_key',
            'jgk_paypal_client_id',
            'jgk_paypal_client_secret',
            'jgk_email_templates',
            'jgk_membership_settings',
            'jgk_competition_settings',
            'jgk_certification_settings',
            'jgk_delete_data_on_uninstall'
        );

        foreach ($options as $option) {
            delete_option($option);
        }

        // Delete transients
        delete_transient('jgk_member_stats');
        delete_transient('jgk_payment_stats');
    }

    /**
     * Delete plugin-created pages
     *
     * @since    1.0.0
     */
    private static function delete_pages() {
        $pages = array(
            'jgk-member-portal',
            'jgk-registration',
            'jgk-member-verification'
        );

        foreach ($pages as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                wp_delete_post($page->ID, true);
            }
        }
    }

    /**
     * Delete user meta data related to plugin
     *
     * @since    1.0.0
     */
    private static function delete_user_meta() {
        global $wpdb;

        $meta_keys = array(
            'jgk_member_id',
            'jgk_membership_number',
            'jgk_emergency_contact',
            'jgk_club_affiliation',
            'jgk_parental_consent'
        );

        foreach ($meta_keys as $meta_key) {
            $wpdb->delete($wpdb->usermeta, array('meta_key' => $meta_key));
        }
    }

    /**
     * Delete uploaded files
     *
     * @since    1.0.0
     */
    private static function delete_uploads() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/juniorgolfkenya/';

        if (is_dir($plugin_upload_dir)) {
            self::delete_directory($plugin_upload_dir);
        }
    }

    /**
     * Recursively delete directory
     *
     * @since    1.0.0
     * @param    string    $dir    Directory path
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Clear all scheduled events
     *
     * @since    1.0.0
     */
    private static function clear_all_scheduled_events() {
        $scheduled_hooks = array(
            'jgk_send_renewal_reminders',
            'jgk_process_expired_memberships',
            'jgk_cleanup_temp_data',
            'jgk_daily_cleanup',
            'jgk_weekly_reports',
            'jgk_payment_retry'
        );

        foreach ($scheduled_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }

    /**
     * Log uninstall event
     *
     * @since    1.0.0
     */
    private static function log_uninstall() {
        // Log to WordPress error log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Junior Golf Kenya plugin uninstalled by user ID: ' . get_current_user_id());
        }
    }
}