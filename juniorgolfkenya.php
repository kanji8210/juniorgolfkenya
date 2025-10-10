<?php
/**
 * Plugin Name: Junior Golf Kenya - Membership Management
 * Plugin URI: https://github.com/kanji8210/juniorgolfkenya
 * Description: A comprehensive membership management plugin for the Junior Golf Foundation website that provides paid member registration, profile and certification management, competition integration, membership verification, payments and subscription management, and admin reporting.
 * Version: 1.0.0
 * Author: Dennis Kosgei for PSM consult
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: juniorgolfkenya
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('JUNIORGOLFKENYA_VERSION', '1.0.0');
define('JUNIORGOLFKENYA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JUNIORGOLFKENYA_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_juniorgolfkenya() {
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-activator.php';
    JuniorGolfKenya_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_juniorgolfkenya() {
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-deactivator.php';
    JuniorGolfKenya_Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstall.
 */
function uninstall_juniorgolfkenya() {
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-uninstaller.php';
    JuniorGolfKenya_Uninstaller::uninstall();
}

register_activation_hook(__FILE__, 'activate_juniorgolfkenya');
register_deactivation_hook(__FILE__, 'deactivate_juniorgolfkenya');
register_uninstall_hook(__FILE__, 'uninstall_juniorgolfkenya');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya.php';

/**
 * Begins execution of the plugin.
 */
function run_juniorgolfkenya() {
    $plugin = new JuniorGolfKenya();
    $plugin->run();
}
run_juniorgolfkenya();