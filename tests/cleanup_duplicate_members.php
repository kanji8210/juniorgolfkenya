<?php
/**
 * Script to remove non-active duplicate members from the database.
 * Criteria: Same first name, last name, and date of birth. Only keep the 'active' one if duplicates exist.
 *
 * Usage: Run from WP-CLI or include in a test environment.
 */

require_once dirname(__DIR__) . '/includes/class-juniorgolfkenya-database.php';

global $wpdb;
$table = $wpdb->prefix . 'jgk_members';

// Find duplicates by first_name, last_name, date_of_birth
$duplicates = $wpdb->get_results("
    SELECT first_name, last_name, date_of_birth, COUNT(*) as count
    FROM $table
    GROUP BY first_name, last_name, date_of_birth
    HAVING count > 1
");

$total_deleted = 0;

foreach ($duplicates as $dup) {
    // Get all members with these details
    $members = $wpdb->get_results($wpdb->prepare(
        "SELECT id, status FROM $table WHERE first_name = %s AND last_name = %s AND date_of_birth = %s",
        $dup->first_name, $dup->last_name, $dup->date_of_birth
    ));

    // Keep the 'active' one if exists, else keep the first, delete others
    $active = array_filter($members, function($m) { return $m->status === 'active'; });
    $to_keep = [];
    if (!empty($active)) {
        $to_keep[] = reset($active)->id;
    } else {
        $to_keep[] = $members[0]->id;
    }

    foreach ($members as $m) {
        if (!in_array($m->id, $to_keep)) {
            $wpdb->delete($table, ['id' => $m->id]);
            $total_deleted++;
        }
    }
}

echo "Deleted $total_deleted duplicate non-active member(s).\n";
