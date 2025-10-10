<?php
/**
 * Check and rename ALL jgf tables to jgk
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

echo "\n=== CHECKING ALL JGF TABLES ===\n\n";

// List of tables to check/rename
$tables_to_rename = array(
    'jgf_coach_ratings' => 'jgk_coach_ratings',
    'jgf_training_schedules' => 'jgk_training_schedules',
    'jgf_role_requests' => 'jgk_role_requests'
);

$renamed_count = 0;
$already_correct = 0;
$errors = 0;

foreach ($tables_to_rename as $old_name => $new_name) {
    $old_full = $wpdb->prefix . $old_name;
    $new_full = $wpdb->prefix . $new_name;
    
    // Check if old table exists
    $old_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_full'") == $old_full;
    
    // Check if new table exists
    $new_exists = $wpdb->get_var("SHOW TABLES LIKE '$new_full'") == $new_full;
    
    echo "Table: $old_name\n";
    
    if ($new_exists) {
        echo "  ✅ Already correct: $new_full exists\n";
        $already_correct++;
    } elseif ($old_exists) {
        echo "  ⚠️  Found old table: $old_full\n";
        echo "  → Renaming to: $new_full...\n";
        
        $result = $wpdb->query("RENAME TABLE $old_full TO $new_full");
        
        if ($result !== false) {
            echo "  ✅ SUCCESS: Renamed $old_name → $new_name\n";
            $renamed_count++;
        } else {
            echo "  ❌ ERROR: Failed to rename\n";
            echo "  Error: " . $wpdb->last_error . "\n";
            $errors++;
        }
    } else {
        echo "  ℹ️  Neither old nor new table exists (will be created when needed)\n";
    }
    
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Tables renamed: $renamed_count\n";
echo "Already correct: $already_correct\n";
echo "Errors: $errors\n\n";

// Verify all tables
echo "=== VERIFICATION: ALL TABLES ===\n";
$all_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}jg%'", ARRAY_N);

if (empty($all_tables)) {
    echo "No JG tables found\n";
} else {
    foreach ($all_tables as $table) {
        $table_name = $table[0];
        $status = (strpos($table_name, '_jgk_') !== false || strpos($table_name, 'jgk_') !== false) ? '✅' : '⚠️';
        echo "$status $table_name\n";
    }
}

echo "\n";
