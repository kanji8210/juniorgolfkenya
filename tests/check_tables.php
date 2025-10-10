<?php
require_once('../../../wp-load.php');
global $wpdb;
$wpdb->show_errors();
$table1 = $wpdb->prefix . 'jgk_members';
$table2 = $wpdb->prefix . 'jgf_coach_profiles';

echo "Checking if tables exist:\\n";
echo "jgk_members: " . ($wpdb->get_var("SHOW TABLES LIKE '$table1'") == $table1 ? "EXISTS" : "MISSING") . "\\n";
echo "jgf_coach_profiles: " . ($wpdb->get_var("SHOW TABLES LIKE '$table2'") == $table2 ? "EXISTS" : "MISSING") . "\\n";

if ($wpdb->last_error) {
    echo "\\nLast DB Error: " . $wpdb->last_error . "\\n";
}
