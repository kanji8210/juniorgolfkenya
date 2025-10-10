<?php
/**
 * Create coach_members junction table for many-to-many relationship
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

echo "\n=== CREATING COACH-MEMBERS JUNCTION TABLE ===\n\n";

$table_name = $wpdb->prefix . 'jgk_coach_members';

// Check if table already exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "⚠️  Table $table_name already exists!\n";
    echo "Do you want to recreate it? This will DELETE all existing assignments!\n";
    echo "Type 'yes' to continue: ";
    $confirm = trim(fgets(STDIN));
    
    if ($confirm !== 'yes') {
        echo "❌ Aborted.\n";
        exit;
    }
    
    $wpdb->query("DROP TABLE $table_name");
    echo "✅ Dropped existing table\n\n";
}

// Create the junction table
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    coach_id bigint(20) unsigned NOT NULL,
    member_id bigint(20) unsigned NOT NULL,
    assigned_date datetime DEFAULT CURRENT_TIMESTAMP,
    assigned_by bigint(20) unsigned DEFAULT NULL,
    notes text,
    is_primary tinyint(1) DEFAULT 0,
    status varchar(20) DEFAULT 'active',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY coach_member (coach_id, member_id),
    KEY coach_id (coach_id),
    KEY member_id (member_id),
    KEY status (status)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

// Verify creation
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "✅ Table $table_name created successfully!\n\n";
    
    // Show table structure
    echo "Table structure:\n";
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
    
    echo "\n=== MIGRATING EXISTING ASSIGNMENTS ===\n\n";
    
    // Migrate existing coach assignments from wp_jgk_members.coach_id
    $members_with_coaches = $wpdb->get_results("
        SELECT id, coach_id, created_at
        FROM {$wpdb->prefix}jgk_members
        WHERE coach_id IS NOT NULL AND coach_id > 0
    ");
    
    if (empty($members_with_coaches)) {
        echo "ℹ️  No existing assignments to migrate.\n";
    } else {
        $migrated = 0;
        foreach ($members_with_coaches as $member) {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'coach_id' => $member->coach_id,
                    'member_id' => $member->id,
                    'assigned_date' => $member->created_at,
                    'is_primary' => 1, // Mark as primary coach
                    'status' => 'active'
                )
            );
            
            if ($result) {
                $migrated++;
            }
        }
        
        echo "✅ Migrated $migrated existing assignments\n";
    }
} else {
    echo "❌ Failed to create table $table_name\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. The many-to-many relationship table is ready\n";
echo "2. Old one-to-many assignments have been migrated\n";
echo "3. Update the coach assignment interface to use this table\n";
echo "4. Keep the 'coach_id' column in wp_jgk_members for primary coach\n\n";
