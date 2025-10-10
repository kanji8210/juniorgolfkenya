<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class JuniorGolfKenya_Deactivator {

    /**
     * Short Description.
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        self::clear_scheduled_events();
        self::flush_rewrite_rules();
        self::log_deactivation();
    }

    /**
     * Clear all scheduled events
     *
     * @since    1.0.0
     */
    private static function clear_scheduled_events() {
        // Clear renewal reminder cron jobs
        wp_clear_scheduled_hook('jgk_send_renewal_reminders');
        wp_clear_scheduled_hook('jgk_process_expired_memberships');
        wp_clear_scheduled_hook('jgk_cleanup_temp_data');
        
        // Clear any other scheduled hooks specific to the plugin
        $scheduled_hooks = array(
            'jgk_daily_cleanup',
            'jgk_weekly_reports',
            'jgk_payment_retry'
        );

        foreach ($scheduled_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }

    /**
     * Flush rewrite rules
     *
     * @since    1.0.0
     */
    private static function flush_rewrite_rules() {
        flush_rewrite_rules();
    }

    /**
     * Log deactivation event
     *
     * @since    1.0.0
     */
    private static function log_deactivation() {
        global $wpdb;
        
        $table_audit_log = $wpdb->prefix . 'jgk_audit_log';
        
        // Check if audit table exists before logging
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_audit_log'") == $table_audit_log;
        
        if ($table_exists) {
            $wpdb->insert(
                $table_audit_log,
                array(
                    'user_id' => get_current_user_id(),
                    'member_id' => null,
                    'action' => 'plugin_deactivated',
                    'object_type' => 'plugin',
                    'object_id' => 0,
                    'old_values' => null,
                    'new_values' => null,
                    'ip_address' => self::get_user_ip(),
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }

    /**
     * Get user IP address
     *
     * @since    1.0.0
     * @return   string
     */
    private static function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}