<?php
/**
 * Fix: Rename coach tables from jgf to jgk and update coach roles
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

echo "\n=== FIXING COACH TABLES AND ROLES ===\n\n";

// Step 1: Rename tables
echo "Step 1: Renaming tables...\n";

$jgf_profiles = $wpdb->prefix . 'jgf_coach_profiles';
$jgk_profiles = $wpdb->prefix . 'jgk_coach_profiles';

$jgf_ratings = $wpdb->prefix . 'jgf_coach_ratings';
$jgk_ratings = $wpdb->prefix . 'jgk_coach_ratings';

// Rename coach profiles table
$result1 = $wpdb->query("RENAME TABLE $jgf_profiles TO $jgk_profiles");
if ($result1 !== false) {
    echo "  ✅ Renamed $jgf_profiles → $jgk_profiles\n";
} else {
    echo "  ❌ Failed to rename $jgf_profiles\n";
    echo "  Error: " . $wpdb->last_error . "\n";
}

// Rename coach ratings table
$result2 = $wpdb->query("RENAME TABLE $jgf_ratings TO $jgk_ratings");
if ($result2 !== false) {
    echo "  ✅ Renamed $jgf_ratings → $jgk_ratings\n";
} else {
    echo "  ❌ Failed to rename $jgf_ratings\n";
    echo "  Error: " . $wpdb->last_error . "\n";
}

// Step 2: Update coach roles
echo "\nStep 2: Updating coach roles (jgf_coach → jgk_coach)...\n";

$updated = $wpdb->query("
    UPDATE {$wpdb->usermeta}
    SET meta_value = REPLACE(meta_value, 'jgf_coach', 'jgk_coach')
    WHERE meta_key = 'wp_capabilities'
    AND meta_value LIKE '%jgf_coach%'
");

if ($updated !== false) {
    echo "  ✅ Updated $updated coach role(s)\n";
} else {
    echo "  ❌ Failed to update coach roles\n";
    echo "  Error: " . $wpdb->last_error . "\n";
}

// Step 3: Verify the changes
echo "\n=== VERIFICATION ===\n";

// Check tables
$profiles_exists = $wpdb->get_var("SHOW TABLES LIKE '$jgk_profiles'") == $jgk_profiles;
$ratings_exists = $wpdb->get_var("SHOW TABLES LIKE '$jgk_ratings'") == $jgk_ratings;

echo "\nTables:\n";
echo "  wp_jgk_coach_profiles: " . ($profiles_exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
echo "  wp_jgk_coach_ratings: " . ($ratings_exists ? "✅ EXISTS" : "❌ MISSING") . "\n";

// Check coaches
$jgk_coaches = $wpdb->get_results("
    SELECT u.ID, u.display_name, u.user_email
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = 'wp_capabilities'
    AND um.meta_value LIKE '%jgk_coach%'
");

echo "\nCoaches with 'jgk_coach' role: " . count($jgk_coaches) . "\n";
foreach ($jgk_coaches as $coach) {
    echo "  ✅ ID: {$coach->ID} | {$coach->display_name} ({$coach->user_email})\n";
}

// Check if any jgf_coach remains
$jgf_count = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->usermeta}
    WHERE meta_key = 'wp_capabilities'
    AND meta_value LIKE '%jgf_coach%'
");

if ($jgf_count > 0) {
    echo "\n⚠️  WARNING: Still $jgf_count coaches with 'jgf_coach' role!\n";
} else {
    echo "\n✅ SUCCESS: All coaches migrated to 'jgk_coach' role!\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Refresh the WordPress admin page\n";
echo "2. Go to 'JGK Members' → Edit a member\n";
echo "3. The 'Assigned Coach' dropdown should now show your coaches!\n\n";
