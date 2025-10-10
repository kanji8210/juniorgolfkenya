<?php
/**
 * Test database queries to ensure all columns exist
 */

require_once('../../../wp-load.php');
require_once('includes/class-juniorgolfkenya-database.php');

echo "=== Testing Database Queries ===\n\n";

global $wpdb;
$wpdb->show_errors();

// Test 1: Get members
echo "Test 1: Get members with pagination...\n";
try {
    $members = JuniorGolfKenya_Database::get_members(1, 5);
    echo "✅ Success! Retrieved " . count($members) . " members\n";
    if (!empty($members)) {
        echo "   Sample member: " . print_r($members[0], true) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

if ($wpdb->last_error) {
    echo "❌ Database Error: " . $wpdb->last_error . "\n";
}

echo "\n";

// Test 2: Get member by ID
echo "Test 2: Get member by ID...\n";
try {
    $member = JuniorGolfKenya_Database::get_member(1);
    if ($member) {
        echo "✅ Success! Member found\n";
    } else {
        echo "⚠️  No member with ID 1 (this is OK if table is empty)\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

if ($wpdb->last_error) {
    echo "❌ Database Error: " . $wpdb->last_error . "\n";
}

echo "\n";

// Test 3: Get membership stats
echo "Test 3: Get membership stats...\n";
try {
    $stats = JuniorGolfKenya_Database::get_membership_stats();
    echo "✅ Success! Stats: " . print_r($stats, true) . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

if ($wpdb->last_error) {
    echo "❌ Database Error: " . $wpdb->last_error . "\n";
}

echo "\n";

// Test 4: Get coaches
echo "Test 4: Get coaches...\n";
try {
    $coaches = JuniorGolfKenya_Database::get_coaches();
    echo "✅ Success! Found " . count($coaches) . " coaches\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

if ($wpdb->last_error) {
    echo "❌ Database Error: " . $wpdb->last_error . "\n";
}

echo "\n";

// Test 5: Search members
echo "Test 5: Search members...\n";
try {
    $results = JuniorGolfKenya_Database::search_members('test', 1, 5);
    echo "✅ Success! Found " . count($results) . " results\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

if ($wpdb->last_error) {
    echo "❌ Database Error: " . $wpdb->last_error . "\n";
}

echo "\n=== All tests completed ===\n";
