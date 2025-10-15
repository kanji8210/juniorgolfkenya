<?php
/**
 * PRODUCTION DIAGNOSTIC SCRIPT
 * Upload this file to your production server and run it
 * to identify why payments work locally but not online
 */

// IMPORTANT: Change this path to match your production WordPress installation
require_once('../../../../wp-load.php');
require_once('../includes/class-juniorgolfkenya-database.php');

echo "=== PRODUCTION ENVIRONMENT DIAGNOSTIC ===\n\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . " (Server time)\n";
echo "Server: " . ($_SERVER['HTTP_HOST'] ?? 'CLI') . "\n\n";

// Critical checks for production vs local differences
$issues_found = array();
$warnings = array();

// 1. Database Connection Test
echo "1. DATABASE CONNECTION TEST\n";
echo "   Testing basic database connectivity...\n";
global $wpdb;

if ($wpdb->last_error) {
    $issues_found[] = "Database connection error: " . $wpdb->last_error;
    echo "   ❌ Database error detected: " . $wpdb->last_error . "\n";
} else {
    echo "   ✅ Database connection OK\n";
}

// Test a simple query
$test_query = $wpdb->get_var("SELECT 1");
if ($test_query !== '1') {
    $issues_found[] = "Basic database query failed";
    echo "   ❌ Basic database query failed\n";
} else {
    echo "   ✅ Basic database query successful\n";
}

echo "   Database details:\n";
echo "     Host: " . DB_HOST . "\n";
echo "     Name: " . DB_NAME . "\n";
echo "     Prefix: " . $wpdb->prefix . "\n";
echo "\n";

// 2. Table Existence and Structure
echo "2. TABLE EXISTENCE AND STRUCTURE\n";
$required_tables = array(
    'jgk_payments' => $wpdb->prefix . 'jgk_payments',
    'jgk_members' => $wpdb->prefix . 'jgk_members',
);

foreach ($required_tables as $logical_name => $table_name) {
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    
    if (!$exists) {
        $issues_found[] = "Required table missing: $table_name";
        echo "   ❌ $logical_name ($table_name): MISSING\n";
    } else {
        echo "   ✅ $logical_name ($table_name): EXISTS\n";
        
        // Check table structure for payments table
        if ($logical_name === 'jgk_payments') {
            $columns = $wpdb->get_results("DESCRIBE $table_name");
            $required_columns = array('id', 'member_id', 'amount', 'status', 'payment_type', 'payment_method', 'created_at');
            $existing_columns = array_map(function($col) { return $col->Field; }, $columns);
            
            foreach ($required_columns as $req_col) {
                if (!in_array($req_col, $existing_columns)) {
                    $issues_found[] = "Missing column in $table_name: $req_col";
                    echo "      ❌ Missing column: $req_col\n";
                }
            }
        }
        
        // Check record count
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "      Records: $count\n";
    }
}
echo "\n";

// 3. Plugin Status
echo "3. PLUGIN STATUS\n";
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$plugin_file = 'juniorgolfkenya/juniorgolfkenya.php';
$is_active = is_plugin_active($plugin_file);

if (!$is_active) {
    $issues_found[] = "Plugin is not active";
    echo "   ❌ Plugin is NOT active\n";
} else {
    echo "   ✅ Plugin is active\n";
}

// Check if classes are loaded
if (!class_exists('JuniorGolfKenya_Database')) {
    $issues_found[] = "JuniorGolfKenya_Database class not loaded";
    echo "   ❌ JuniorGolfKenya_Database class not found\n";
} else {
    echo "   ✅ JuniorGolfKenya_Database class loaded\n";
}
echo "\n";

// 4. PHP Environment
echo "4. PHP ENVIRONMENT\n";
echo "   PHP Version: " . PHP_VERSION . "\n";

if (version_compare(PHP_VERSION, '7.4', '<')) {
    $issues_found[] = "PHP version too old: " . PHP_VERSION . " (requires 7.4+)";
    echo "   ❌ PHP version too old (requires 7.4+)\n";
} else {
    echo "   ✅ PHP version compatible\n";
}

echo "   Memory Limit: " . ini_get('memory_limit') . "\n";
echo "   Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "   Error Reporting: " . error_reporting() . "\n";
echo "   Display Errors: " . ini_get('display_errors') . "\n";
echo "   Log Errors: " . ini_get('log_errors') . "\n";
echo "\n";

// 5. File Permissions
echo "5. FILE PERMISSIONS\n";
$critical_files = array(
    JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php',
    JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-payments.php',
);

foreach ($critical_files as $file) {
    if (!file_exists($file)) {
        $issues_found[] = "Critical file missing: " . basename($file);
        echo "   ❌ " . basename($file) . ": MISSING\n";
    } elseif (!is_readable($file)) {
        $issues_found[] = "Critical file not readable: " . basename($file);
        echo "   ❌ " . basename($file) . ": NOT READABLE\n";
    } else {
        echo "   ✅ " . basename($file) . ": OK\n";
    }
}
echo "\n";

// 6. Payment Function Tests
echo "6. PAYMENT FUNCTION TESTS\n";

// Test getting payments
echo "   Testing get_payments()...\n";
try {
    $payments = JuniorGolfKenya_Database::get_payments();
    echo "   ✅ get_payments() successful: " . count($payments) . " payments\n";
    
    if (count($payments) === 0) {
        $warnings[] = "No payments found - this might be expected if no payments exist";
        echo "   ⚠️  No payments returned (might be normal if none exist)\n";
    }
} catch (Exception $e) {
    $issues_found[] = "get_payments() failed: " . $e->getMessage();
    echo "   ❌ get_payments() failed: " . $e->getMessage() . "\n";
}

// Test payment recording
echo "   Testing payment recording...\n";
try {
    // Find a member to test with
    $test_member = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}jgk_members LIMIT 1");
    
    if ($test_member) {
        $test_payment_id = JuniorGolfKenya_Database::record_manual_payment(
            $test_member->id, 
            10.00, 
            'membership', 
            'diagnostic_test', 
            'Production diagnostic test'
        );
        
        if ($test_payment_id) {
            echo "   ✅ Payment recording successful: ID $test_payment_id\n";
            
            // Clean up test payment
            $wpdb->delete($wpdb->prefix . 'jgk_payments', array('id' => $test_payment_id));
            echo "   ✅ Test payment cleaned up\n";
        } else {
            $issues_found[] = "Payment recording failed";
            echo "   ❌ Payment recording failed\n";
            if ($wpdb->last_error) {
                echo "      Database error: " . $wpdb->last_error . "\n";
            }
        }
    } else {
        $warnings[] = "No members found to test payment recording with";
        echo "   ⚠️  No members found to test with\n";
    }
} catch (Exception $e) {
    $issues_found[] = "Payment recording test failed: " . $e->getMessage();
    echo "   ❌ Payment recording test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. WordPress Environment
echo "7. WORDPRESS ENVIRONMENT\n";
echo "   WordPress Version: " . get_bloginfo('version') . "\n";
echo "   Site URL: " . get_site_url() . "\n";
echo "   Home URL: " . get_home_url() . "\n";
echo "   WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'TRUE' : 'FALSE') . "\n";
echo "   WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'TRUE' : 'FALSE') . "\n";

if (!defined('WP_DEBUG') || !WP_DEBUG) {
    $warnings[] = "WP_DEBUG is disabled - consider enabling it to catch errors";
}
echo "\n";

// 8. Recent Error Logs
echo "8. RECENT ERROR LOGS\n";
$error_log_locations = array(
    ini_get('error_log'),
    ABSPATH . 'wp-content/debug.log',
    dirname(__FILE__) . '/error_log',
    dirname(__FILE__) . '/errors.log'
);

$found_logs = false;
foreach ($error_log_locations as $log_file) {
    if ($log_file && file_exists($log_file) && is_readable($log_file)) {
        echo "   Checking: $log_file\n";
        $found_logs = true;
        
        $log_content = file_get_contents($log_file);
        if ($log_content) {
            $lines = explode("\n", $log_content);
            $recent_lines = array_slice($lines, -50); // Last 50 lines
            
            // Look for JGK or payment related errors
            $relevant_errors = array_filter($recent_lines, function($line) {
                return stripos($line, 'jgk') !== false || 
                       stripos($line, 'payment') !== false || 
                       stripos($line, 'juniorgolf') !== false ||
                       stripos($line, 'fatal') !== false ||
                       stripos($line, 'error') !== false;
            });
            
            if (count($relevant_errors) > 0) {
                echo "   Recent relevant errors:\n";
                foreach (array_slice($relevant_errors, -10) as $error) {
                    echo "     " . trim($error) . "\n";
                }
            } else {
                echo "   No recent relevant errors found\n";
            }
        }
        break; // Only check the first accessible log
    }
}

if (!$found_logs) {
    echo "   No accessible error logs found\n";
}
echo "\n";

// 9. Summary and Recommendations
echo "9. SUMMARY AND RECOMMENDATIONS\n";

if (count($issues_found) === 0) {
    echo "   ✅ No critical issues found!\n";
    echo "   The environment appears to be configured correctly.\n";
    
    if (count($warnings) > 0) {
        echo "\n   Warnings (non-critical):\n";
        foreach ($warnings as $warning) {
            echo "   ⚠️  $warning\n";
        }
    }
    
    echo "\n   If payments still don't work, check:\n";
    echo "   - User permissions (manage_payments capability)\n";
    echo "   - AJAX endpoints are working\n";
    echo "   - Browser console for JavaScript errors\n";
    echo "   - Network tab for failed requests\n";
    
} else {
    echo "   ❌ Critical issues found that need to be fixed:\n";
    foreach ($issues_found as $issue) {
        echo "   • $issue\n";
    }
    
    echo "\n   Recommended actions:\n";
    echo "   1. Fix the critical issues listed above\n";
    echo "   2. Ensure all database tables exist and have correct structure\n";
    echo "   3. Verify plugin is active and files are accessible\n";
    echo "   4. Check PHP version and memory limits\n";
    echo "   5. Review error logs for specific errors\n";
}

// 10. Environment Comparison Data
echo "\n10. ENVIRONMENT DATA FOR COMPARISON\n";
$environment_data = array(
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => 'production', // Change this if testing on staging
    'server' => $_SERVER['HTTP_HOST'] ?? 'CLI',
    'wordpress_version' => get_bloginfo('version'),
    'php_version' => PHP_VERSION,
    'plugin_active' => $is_active,
    'tables_exist' => array(),
    'payment_count' => 0,
    'issues_found' => $issues_found,
    'warnings' => $warnings
);

foreach ($required_tables as $logical_name => $table_name) {
    $environment_data['tables_exist'][$logical_name] = 
        $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
}

if (isset($payments)) {
    $environment_data['payment_count'] = count($payments);
}

$filename = 'production_diagnostic_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($filename, json_encode($environment_data, JSON_PRETTY_PRINT));
echo "Environment data saved to: $filename\n";

echo "\n=== END PRODUCTION DIAGNOSTIC ===\n";

// If running via web browser, also output as HTML for better readability
if (isset($_SERVER['HTTP_HOST'])) {
    echo "\n\n<!-- HTML VERSION FOR BROWSER -->\n";
    echo "<style>body{font-family:monospace;white-space:pre;}</style>\n";
    echo "<h2>Production Diagnostic Complete</h2>\n";
    echo "<p>Check the output above for issues and recommendations.</p>\n";
    echo "<p>Compare with your local environment data to identify differences.</p>\n";
}
?>