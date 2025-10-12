<?php
// Test the get_members query to debug the empty list issue
require_once '../../../wp-load.php';

echo "🔍 Testing member queries...\n\n";

// Test basic member count
global $wpdb;
$members_table = $wpdb->prefix . 'jgk_members';
$count = $wpdb->get_var("SELECT COUNT(*) FROM {$members_table}");
echo "📊 Basic member count: {$count}\n";

// Test the actual get_members function
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';

echo "\n📋 Testing JuniorGolfKenya_Database::get_members():\n";
$members = JuniorGolfKenya_Database::get_members(1, 5, '');
echo "Returned " . count($members) . " members\n";

if (count($members) > 0) {
    echo "✅ get_members() works - showing first member:\n";
    $first = $members[0];
    echo "ID: {$first->id}, Name: {$first->full_name}, Email: {$first->user_email}\n";
}

echo "\n📋 Testing JuniorGolfKenya_Database::search_members():\n";
$search_members = JuniorGolfKenya_Database::search_members('', 1, 5);
echo "Search with empty term returned " . count($search_members) . " members\n";

echo "\n📋 Testing JuniorGolfKenya_Database::get_members_count():\n";
$total_count = JuniorGolfKenya_Database::get_members_count('');
echo "Total count: {$total_count}\n";

echo "\n� Testing JuniorGolfKenya_Database::get_membership_stats():\n";
$stats = JuniorGolfKenya_Database::get_membership_stats();
echo "Stats: " . json_encode($stats) . "\n";

// Test what happens in the admin page logic
echo "\n📋 Simulating admin page logic:\n";
$page = 1;
$per_page = 20;
$status_filter = '';
$search = '';

if ($search) {
    echo "Using search_members...\n";
    $test_members = JuniorGolfKenya_Database::search_members($search, $page, $per_page);
    $test_total_members = count($test_members);
} else {
    echo "Using get_members...\n";
    $test_members = JuniorGolfKenya_Database::get_members($page, $per_page, $status_filter);
    $test_total_members = JuniorGolfKenya_Database::get_members_count($status_filter);
}

echo "Admin logic would return " . count($test_members) . " members, total: {$test_total_members}\n";

if (count($test_members) === 0 && $test_total_members > 0) {
    echo "❌ MISMATCH: get_members returns 0 but get_members_count returns {$test_total_members}\n";
    echo "This suggests an issue with the JOIN in get_members\n";
} elseif (count($test_members) > 0) {
    echo "✅ Both functions work correctly\n";
}
?>