<?php
// Check coach_members table status
require_once '../../../wp-load.php';

global $wpdb;
$table = $wpdb->prefix . 'jgk_coach_members';

echo "🔍 Checking coach_members table...\n\n";

$exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
if (!$exists) {
    echo "❌ coach_members table does NOT exist!\n";
    echo "This explains why get_members() returns empty - the JOIN fails.\n";
    exit;
}

echo "✅ coach_members table exists\n";

$count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
echo "Records in coach_members: {$count}\n";

if ($count > 0) {
    $sample = $wpdb->get_row("SELECT * FROM {$table} LIMIT 1");
    echo "Sample record:\n";
    echo "  member_id: " . ($sample->member_id ?? 'null') . "\n";
    echo "  coach_id: " . ($sample->coach_id ?? 'null') . "\n";
    echo "  status: " . ($sample->status ?? 'null') . "\n";
    echo "  created_at: " . ($sample->created_at ?? 'null') . "\n";
} else {
    echo "⚠️  coach_members table is empty - no coach assignments\n";
}

// Check if members exist
$members_table = $wpdb->prefix . 'jgk_members';
$members_count = $wpdb->get_var("SELECT COUNT(*) FROM {$members_table}");
echo "\n📊 Total members in jgk_members: {$members_count}\n";

// Test the problematic query
echo "\n🔍 Testing get_members query...\n";
$query = "
    SELECT m.*, u.user_email, u.display_name, u.user_login,
           TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
           CONCAT(m.first_name, ' ', m.last_name) as full_name,
           c.display_name as primary_coach_name,
           GROUP_CONCAT(DISTINCT c2.display_name ORDER BY c2.display_name SEPARATOR ', ') as all_coaches
    FROM {$members_table} m
    LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
    LEFT JOIN {$wpdb->users} c ON m.coach_id = c.ID
    LEFT JOIN {$table} cm ON m.id = cm.member_id AND cm.status = 'active'
    LEFT JOIN {$wpdb->users} c2 ON cm.coach_id = c2.ID
    GROUP BY m.id ORDER BY m.created_at DESC LIMIT 5
";

$results = $wpdb->get_results($query);
echo "Query returned " . count($results) . " results\n";

if (count($results) === 0) {
    echo "❌ Query returns no results - likely due to JOIN issues\n";

    // Test simpler query without coach_members JOIN
    echo "\n🔍 Testing simpler query without coach_members...\n";
    $simple_query = "
        SELECT m.*, u.user_email, u.display_name, u.user_login,
               TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) as age,
               CONCAT(m.first_name, ' ', m.last_name) as full_name,
               c.display_name as primary_coach_name
        FROM {$members_table} m
        LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
        LEFT JOIN {$wpdb->users} c ON m.coach_id = c.ID
        ORDER BY m.created_at DESC LIMIT 5
    ";

    $simple_results = $wpdb->get_results($simple_query);
    echo "Simple query returned " . count($simple_results) . " results\n";

    if (count($simple_results) > 0) {
        echo "✅ Simple query works - issue is with coach_members JOIN\n";
        echo "The coach_members table might have foreign key constraint issues.\n";
    }
}
?>