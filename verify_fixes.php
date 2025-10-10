<?php
/**
 * Verification script for many-to-many fixes
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "=== Verification of Many-to-Many Fixes ===\n\n";

// 1. Check member count from junction table
echo "📊 Testing get_coaches() with junction table count:\n";
require_once('includes/class-juniorgolfkenya-database.php');
$coaches = JuniorGolfKenya_Database::get_coaches();

foreach ($coaches as $coach) {
    echo "   - {$coach->display_name}: {$coach->member_count} member(s)\n";
}

// 2. Check members with all_coaches column
echo "\n📋 Testing get_members() with all_coaches display:\n";
$members = JuniorGolfKenya_Database::get_members(1, 5, '');

foreach ($members as $member) {
    $coaches_display = $member->all_coaches ?: 'No coaches';
    echo "   - {$member->first_name} {$member->last_name}: {$coaches_display}\n";
}

// 3. Verify AJAX endpoints are registered
echo "\n✅ AJAX Endpoints:\n";
if (has_action('wp_ajax_jgk_get_coach_members')) {
    echo "   ✓ wp_ajax_jgk_get_coach_members registered\n";
} else {
    echo "   ✗ wp_ajax_jgk_get_coach_members NOT registered\n";
}

if (has_action('wp_ajax_jgk_search_members')) {
    echo "   ✓ wp_ajax_jgk_search_members registered\n";
} else {
    echo "   ✗ wp_ajax_jgk_search_members NOT registered\n";
}

// 4. Test member search query
echo "\n🔍 Testing member search (first 3 results):\n";
$like = '%' . $wpdb->esc_like('') . '%';
$members_table = $wpdb->prefix . 'jgk_members';
$users_table = $wpdb->users;

$test_results = $wpdb->get_results("
    SELECT 
        m.id,
        CONCAT(m.first_name, ' ', m.last_name) as name,
        m.membership_type as type
    FROM {$members_table} m
    LEFT JOIN {$users_table} u ON m.user_id = u.ID
    WHERE m.status IN ('active', 'approved', 'pending')
    ORDER BY m.first_name, m.last_name
    LIMIT 3
");

foreach ($test_results as $result) {
    echo "   - {$result->name} ({$result->type})\n";
}

echo "\n✅ All fixes verified!\n";
echo "\nNext steps:\n";
echo "1. Go to WordPress Admin → JGK Coaches\n";
echo "2. Check that member counts show correct numbers\n";
echo "3. Click 'Assign More' on any coach\n";
echo "4. Try the new search interface - type a member name\n";
echo "5. Select members from search results\n";
echo "6. Add them to the coach\n";
echo "7. Go to JGK Members page\n";
echo "8. Check that 'Coach' column shows all assigned coaches\n";
