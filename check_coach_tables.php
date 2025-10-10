<?php
/**
 * Check which coach tables exist in the database
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

echo "\n=== CHECKING COACH TABLES ===\n\n";

// Check for jgf_coach_profiles
$jgf_table = $wpdb->prefix . 'jgf_coach_profiles';
$jgf_exists = $wpdb->get_var("SHOW TABLES LIKE '$jgf_table'") == $jgf_table;
echo "Table: $jgf_table\n";
echo "Status: " . ($jgf_exists ? "✅ EXISTS" : "❌ MISSING") . "\n\n";

// Check for jgk_coach_profiles
$jgk_table = $wpdb->prefix . 'jgk_coach_profiles';
$jgk_exists = $wpdb->get_var("SHOW TABLES LIKE '$jgk_table'") == $jgk_table;
echo "Table: $jgk_table\n";
echo "Status: " . ($jgk_exists ? "✅ EXISTS" : "❌ MISSING") . "\n\n";

// List all tables with 'coach' in the name
echo "=== ALL TABLES WITH 'COACH' ===\n";
$all_tables = $wpdb->get_results("SHOW TABLES LIKE '%coach%'", ARRAY_N);
if (empty($all_tables)) {
    echo "❌ No tables found with 'coach' in the name\n";
} else {
    foreach ($all_tables as $table) {
        echo "✅ " . $table[0] . "\n";
    }
}

echo "\n=== COACHES IN DATABASE ===\n";
// Check for coaches with jgf_coach role
$jgf_coaches = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.display_name, um.meta_value as role
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = 'wp_capabilities'
    AND um.meta_value LIKE '%jgf_coach%'
");

echo "\nCoaches with 'jgf_coach' role: " . count($jgf_coaches) . "\n";
foreach ($jgf_coaches as $coach) {
    echo "  - ID: {$coach->ID} | {$coach->display_name} ({$coach->user_login})\n";
}

// Check for coaches with jgk_coach role
$jgk_coaches = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.display_name, um.meta_value as role
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = 'wp_capabilities'
    AND um.meta_value LIKE '%jgk_coach%'
");

echo "\nCoaches with 'jgk_coach' role: " . count($jgk_coaches) . "\n";
foreach ($jgk_coaches as $coach) {
    echo "  - ID: {$coach->ID} | {$coach->display_name} ({$coach->user_login})\n";
}

echo "\n=== SOLUTION ===\n";
if ($jgf_exists && !$jgk_exists) {
    echo "⚠️  PROBLEM: Table 'jgf_coach_profiles' exists but code expects 'jgk_coach_profiles'\n";
    echo "✅ SOLUTION: Rename the table\n\n";
    echo "Execute this SQL:\n";
    echo "RENAME TABLE {$jgf_table} TO {$jgk_table};\n";
} elseif (!$jgf_exists && !$jgk_exists) {
    echo "⚠️  PROBLEM: No coach profile tables exist\n";
    echo "✅ SOLUTION: Reactivate the plugin to create tables\n";
} elseif ($jgk_exists) {
    echo "✅ Table 'jgk_coach_profiles' exists - Good!\n";
}

echo "\n";
