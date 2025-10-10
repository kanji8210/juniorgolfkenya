<?php
/**
 * Script to recreate database tables for testing
 * Run this from command line: php recreate_tables.php
 */

require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-activator.php');

global $wpdb;

echo "=== Junior Golf Kenya - Table Recreation Script ===\n\n";

// List of all tables
$tables = array(
    'jgk_members',
    'jgk_memberships',
    'jgk_plans',
    'jgk_payments',
    'jgk_competition_entries',
    'jgk_certifications',
    'jgk_audit_log',
    'jgf_coach_ratings',
    'jgf_recommendations',
    'jgf_training_schedules',
    'jgf_role_requests',
    'jgf_coach_profiles'
);

// Step 1: Drop existing tables
echo "Step 1: Dropping existing tables...\n";
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    echo "  - Dropped: $table\n";
}

echo "\nStep 2: Creating tables...\n";

// Use reflection to call private method
$activator = new ReflectionClass('JuniorGolfKenya_Activator');
$method = $activator->getMethod('create_tables');
$method->setAccessible(true);
$result = $method->invoke(null);

echo "\nStep 3: Verifying tables...\n";
$verification_method = $activator->getMethod('verify_tables');
$verification_method->setAccessible(true);
$verification = $verification_method->invoke(null);

if ($verification['success']) {
    echo "\n✅ SUCCESS! All tables created successfully!\n";
    echo "Created tables:\n";
    foreach ($verification['existing'] as $table) {
        echo "  ✓ $table\n";
    }
} else {
    echo "\n❌ WARNING! Some tables failed to create.\n";
    if (!empty($verification['existing'])) {
        echo "\nSuccessfully created:\n";
        foreach ($verification['existing'] as $table) {
            echo "  ✓ $table\n";
        }
    }
    if (!empty($verification['missing'])) {
        echo "\nFailed to create:\n";
        foreach ($verification['missing'] as $table) {
            echo "  ✗ $table\n";
        }
    }
}

// Show last database error if any
if ($wpdb->last_error) {
    echo "\n⚠️  Last database error: " . $wpdb->last_error . "\n";
}

echo "\n=== Script completed ===\n";
