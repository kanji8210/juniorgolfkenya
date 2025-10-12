<?php
// Check if coach_members table exists
require_once '../../../wp-load.php';

global $wpdb;
$table = $wpdb->prefix . 'jgk_coach_members';
$exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

if ($exists) {
    echo "✅ coach_members table exists\n";
} else {
    echo "❌ coach_members table missing - you need to reactivate the plugin\n";
}

// Also check member count
$members_table = $wpdb->prefix . 'jgk_members';
$member_count = $wpdb->get_var("SELECT COUNT(*) FROM {$members_table}");
echo "📊 Total members in database: {$member_count}\n";
?>