<?php
require_once('../../../../wp-load.php');

echo "=== ENVIRONMENT COMPARISON DIAGNOSTIC ===\n\n";

// 1. Database configuration
echo "1. Database Configuration:\n";
echo "   DB_HOST: " . DB_HOST . "\n";
echo "   DB_NAME: " . DB_NAME . "\n";
echo "   DB_USER: " . DB_USER . "\n";
echo "   Table prefix: " . $GLOBALS['wpdb']->prefix . "\n\n";

// 2. WordPress configuration
echo "2. WordPress Configuration:\n";
echo "   WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'TRUE' : 'FALSE') . "\n";
echo "   WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'TRUE' : 'FALSE') . "\n";
echo "   WP_DEBUG_DISPLAY: " . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'TRUE' : 'FALSE') . "\n";
echo "   SCRIPT_DEBUG: " . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'TRUE' : 'FALSE') . "\n";
echo "   WordPress version: " . get_bloginfo('version') . "\n";
echo "   Site URL: " . get_site_url() . "\n";
echo "   Home URL: " . get_home_url() . "\n";
echo "   Admin URL: " . admin_url() . "\n\n";

// 3. PHP configuration
echo "3. PHP Configuration:\n";
echo "   PHP version: " . PHP_VERSION . "\n";
echo "   Memory limit: " . ini_get('memory_limit') . "\n";
echo "   Max execution time: " . ini_get('max_execution_time') . "\n";
echo "   Error reporting: " . error_reporting() . "\n";
echo "   Display errors: " . ini_get('display_errors') . "\n";
echo "   Log errors: " . ini_get('log_errors') . "\n";
echo "   Error log: " . ini_get('error_log') . "\n\n";

// 4. Server information
echo "4. Server Information:\n";
if (isset($_SERVER['SERVER_SOFTWARE'])) {
    echo "   Server software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
}
if (isset($_SERVER['HTTP_HOST'])) {
    echo "   HTTP host: " . $_SERVER['HTTP_HOST'] . "\n";
}
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    echo "   Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
}
echo "   Operating system: " . PHP_OS . "\n";
echo "   SAPI: " . php_sapi_name() . "\n\n";

// 5. Plugin status
echo "5. Plugin Status:\n";
$plugin_file = 'juniorgolfkenya/juniorgolfkenya.php';
echo "   Plugin active: " . (is_plugin_active($plugin_file) ? 'YES' : 'NO') . "\n";
echo "   Plugin path: " . JUNIORGOLFKENYA_PLUGIN_PATH . "\n";
echo "   Plugin URL: " . JUNIORGOLFKENYA_PLUGIN_URL . "\n\n";

// 6. User capabilities and permissions
echo "6. Current User Context:\n";
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "   User ID: " . $current_user->ID . "\n";
    echo "   User login: " . $current_user->user_login . "\n";
    echo "   User roles: " . implode(', ', $current_user->roles) . "\n";
    echo "   Can manage payments: " . (current_user_can('manage_payments') ? 'YES' : 'NO') . "\n";
} else {
    echo "   No user logged in (CLI context)\n";
}
echo "\n";

// 7. Database tables check
echo "7. Database Tables Check:\n";
global $wpdb;
$required_tables = array(
    'jgk_members' => $wpdb->prefix . 'jgk_members',
    'jgk_payments' => $wpdb->prefix . 'jgk_payments',
    'jgk_memberships' => $wpdb->prefix . 'jgk_memberships',
    'jgk_audit_log' => $wpdb->prefix . 'jgk_audit_log'
);

foreach ($required_tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    echo "   $name: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "     Record count: $count\n";
    }
}
echo "\n";

// 8. WordPress options related to payments
echo "8. Payment-related WordPress Options:\n";
$payment_options = array(
    'jgk_membership_product_id',
    'jgk_payment_settings',
    'jgk_currency',
    'jgk_stripe_public_key',
    'jgk_stripe_secret_key'
);

foreach ($payment_options as $option) {
    $value = get_option($option, 'NOT_SET');
    if ($option === 'jgk_stripe_secret_key' && $value !== 'NOT_SET') {
        $value = '***HIDDEN***';
    }
    echo "   $option: " . (is_array($value) ? json_encode($value) : $value) . "\n";
}
echo "\n";

// 9. File permissions check
echo "9. File Permissions Check:\n";
$files_to_check = array(
    JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php',
    JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-payments.php',
    JUNIORGOLFKENYA_PLUGIN_PATH . 'juniorgolfkenya.php'
);

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        echo "   " . basename($file) . ": " . decoct($perms & 0777) . " (readable: " . (is_readable($file) ? 'YES' : 'NO') . ")\n";
    } else {
        echo "   " . basename($file) . ": FILE NOT FOUND\n";
    }
}
echo "\n";

// 10. Recent error logs
echo "10. Recent Error Logs:\n";
$error_log_file = ini_get('error_log');
if ($error_log_file && file_exists($error_log_file)) {
    echo "   Checking: $error_log_file\n";
    $log_content = file_get_contents($error_log_file);
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -20); // Last 20 lines
    
    $jgk_errors = array_filter($recent_lines, function($line) {
        return stripos($line, 'jgk') !== false || stripos($line, 'juniorgolf') !== false;
    });
    
    if (count($jgk_errors) > 0) {
        echo "   Recent JGK-related errors:\n";
        foreach ($jgk_errors as $error) {
            echo "     " . trim($error) . "\n";
        }
    } else {
        echo "   No recent JGK-related errors found\n";
    }
} else {
    echo "   Error log file not accessible or not configured\n";
}

echo "\n=== END ENVIRONMENT DIAGNOSTIC ===\n";

// Create environment report for comparison
$report = array(
    'timestamp' => current_time('mysql'),
    'environment' => 'local', // Change this when running on production
    'wordpress_version' => get_bloginfo('version'),
    'php_version' => PHP_VERSION,
    'site_url' => get_site_url(),
    'database' => array(
        'host' => DB_HOST,
        'name' => DB_NAME,
        'prefix' => $wpdb->prefix
    ),
    'tables' => array(),
    'payment_counts' => array()
);

// Add table info to report
foreach ($required_tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    $report['tables'][$name] = array(
        'exists' => $exists,
        'count' => $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table") : 0
    );
}

// Add payment counts
require_once('../includes/class-juniorgolfkenya-database.php');
$payments = JuniorGolfKenya_Database::get_payments();
$report['payment_counts'] = array(
    'total' => count($payments),
    'completed' => count(array_filter($payments, function($p) { return $p->status === 'completed'; })),
    'pending' => count(array_filter($payments, function($p) { return $p->status === 'pending'; }))
);

file_put_contents('environment_report_' . date('Y-m-d_H-i-s') . '.json', json_encode($report, JSON_PRETTY_PRINT));
echo "Environment report saved to: environment_report_" . date('Y-m-d_H-i-s') . ".json\n";
?>