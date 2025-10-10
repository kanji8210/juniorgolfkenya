<?php
/**
 * Verification script for many-to-many implementation
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo "=== Many-to-Many Coach-Member Implementation Verification ===\n\n";

// 1. Check junction table exists
$table = $wpdb->prefix . 'jgk_coach_members';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

if ($table_exists) {
    echo "✅ Junction table exists: $table\n";
    
    // Check structure
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
    echo "\n📋 Table structure:\n";
    foreach ($columns as $col) {
        echo "   - {$col->Field} ({$col->Type})\n";
    }
    
    // Check for data
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "\n📊 Current assignments: $count\n";
    
    if ($count > 0) {
        echo "\n📝 Sample assignments:\n";
        $assignments = $wpdb->get_results("
            SELECT cm.*, m.first_name, m.last_name, u.display_name as coach_name
            FROM $table cm
            LEFT JOIN {$wpdb->prefix}jgk_members m ON cm.member_id = m.id
            LEFT JOIN {$wpdb->users} u ON cm.coach_id = u.ID
            LIMIT 5
        ");
        
        foreach ($assignments as $assign) {
            echo "   - Coach: {$assign->coach_name} (#" . ($assign->coach_id ?? 'N/A') . 
                 ") -> Member: {$assign->first_name} {$assign->last_name} (#" . ($assign->member_id ?? 'N/A') . 
                 ") [" . ($assign->is_primary ? 'PRIMARY' : 'secondary') . "]\n";
        }
    }
} else {
    echo "❌ Junction table NOT found: $table\n";
    echo "Please run create_coach_members_table.php first.\n";
    exit(1);
}

// 2. Check for coaches
$coaches = $wpdb->get_results("
    SELECT u.ID, u.display_name
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = 'wp_capabilities'
    AND um.meta_value LIKE '%jgk_coach%'
    LIMIT 5
");

echo "\n👨‍🏫 Available coaches: " . count($coaches) . "\n";
foreach ($coaches as $coach) {
    echo "   - {$coach->display_name} (ID: {$coach->ID})\n";
}

// 3. Check for members
$members_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jgk_members");
echo "\n👥 Total members: $members_count\n";

// 4. Check AJAX endpoint registration
if (has_action('wp_ajax_jgk_get_coach_members')) {
    echo "\n✅ AJAX endpoint registered: wp_ajax_jgk_get_coach_members\n";
} else {
    echo "\n⚠️  AJAX endpoint NOT registered: wp_ajax_jgk_get_coach_members\n";
}

echo "\n✅ Verification complete!\n";
echo "\nNext steps:\n";
echo "1. Navigate to Coaches page in WordPress admin\n";
echo "2. Click 'Assign Members' on any coach\n";
echo "3. Verify the modal shows 'Currently Assigned Members' section\n";
echo "4. Select members and click 'Add Selected Members'\n";
echo "5. Verify members appear with 'Remove' buttons\n";
