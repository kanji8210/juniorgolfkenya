<?php
/**
 * Script to check actual columns in jgk_members table
 */

require_once('../../../wp-load.php');
global $wpdb;

echo "=== Checking jgk_members table structure ===\n\n";

$table_name = $wpdb->prefix . 'jgk_members';
$columns = $wpdb->get_results("DESCRIBE $table_name");

echo "Current columns in $table_name:\n";
foreach($columns as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}

echo "\n=== Columns from the INSERT query error ===\n";
$expected_columns = [
    'membership_number',
    'status',
    'join_date',
    'expiry_date',
    'created_at',
    'updated_at',
    'membership_type',
    'date_of_birth',
    'gender',
    'phone',
    'handicap',           // ❌ MISSING
    'club_affiliation',
    'emergency_contact_name',
    'emergency_contact_phone',
    'medical_conditions', // ❌ MISSING
    'user_id'
];

$actual_columns = array_map(function($col) { return $col->Field; }, $columns);

echo "\nMissing columns:\n";
foreach($expected_columns as $col) {
    if (!in_array($col, $actual_columns)) {
        echo "  ❌ $col\n";
    }
}

echo "\nAll checks completed!\n";
