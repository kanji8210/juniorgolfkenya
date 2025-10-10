<?php
/**
 * Script to add missing capabilities to administrator role
 * Run this once after updating the plugin
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "=== Adding Missing Capabilities to Administrator Role ===\n\n";

$admin_role = get_role('administrator');

if (!$admin_role) {
    echo "✗ ERROR: Administrator role not found!\n";
    exit(1);
}

$custom_caps = array(
    'view_member_dashboard',
    'edit_members',
    'manage_coaches',
    'manage_payments',
    'view_reports',
    'manage_competitions',
    'coach_rate_player',
    'coach_recommend_competition',
    'coach_recommend_training',
    'approve_role_requests',
    'manage_certifications'
);

$added = 0;
$already_exists = 0;

foreach ($custom_caps as $cap) {
    if ($admin_role->has_cap($cap)) {
        echo "  ✓ $cap - Already exists\n";
        $already_exists++;
    } else {
        $admin_role->add_cap($cap);
        echo "  + $cap - ADDED\n";
        $added++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Already existing: $already_exists\n";
echo "Newly added: $added\n";
echo "Total capabilities: " . count($custom_caps) . "\n";
echo "\n✓ Done! Administrator role updated successfully.\n";
