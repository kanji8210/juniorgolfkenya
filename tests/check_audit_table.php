<?php
require_once('../../../wp-load.php');
global $wpdb;
$columns = $wpdb->get_results('DESCRIBE ' . $wpdb->prefix . 'jgk_audit_log');
foreach($columns as $col) {
    echo $col->Field . "\n";
}
