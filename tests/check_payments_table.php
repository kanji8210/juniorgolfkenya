<?php
require_once('../../../wp-load.php');
global $wpdb;
echo "Columns in jgk_payments:\n";
$columns = $wpdb->get_results('DESCRIBE ' . $wpdb->prefix . 'jgk_payments');
foreach($columns as $col) {
    echo "  - " . $col->Field . "\n";
}
