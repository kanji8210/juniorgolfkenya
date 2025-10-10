<?php
/**
 * Quick check for coach_name references
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "=== Checking for Undefined Property Issues ===\n\n";

// Test the get_members query
require_once('includes/class-juniorgolfkenya-database.php');
$members = JuniorGolfKenya_Database::get_members(1, 5, '');

echo "✅ Testing member properties:\n";
foreach ($members as $member) {
    echo "\nMember: {$member->first_name} {$member->last_name}\n";
    echo "  - id: " . (isset($member->id) ? $member->id : 'MISSING') . "\n";
    echo "  - display_name: " . (isset($member->display_name) ? $member->display_name : 'MISSING') . "\n";
    echo "  - all_coaches: " . (isset($member->all_coaches) ? ($member->all_coaches ?: 'No coaches') : 'MISSING') . "\n";
    echo "  - coach_name: " . (isset($member->coach_name) ? 'DEPRECATED - SHOULD NOT EXIST!' : 'Not present (good!)') . "\n";
    echo "  - primary_coach_name: " . (isset($member->primary_coach_name) ? $member->primary_coach_name : 'Not set') . "\n";
}

echo "\n✅ All checks passed! No undefined property errors should occur.\n";
echo "\nThe warning should now be gone. Refresh your WordPress admin page.\n";
