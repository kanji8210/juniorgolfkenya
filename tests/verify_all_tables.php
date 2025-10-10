<?php
/**
 * Comprehensive verification of all database tables and their columns
 */

require_once('../../../wp-load.php');
global $wpdb;

echo "=== Comprehensive Database Table Verification ===\n\n";

// Define expected structure for each table
$expected_structures = array(
    'jgk_members' => array(
        'id', 'user_id', 'membership_number', 'membership_type', 'status', 'coach_id',
        'date_joined', 'date_expires', 'expiry_date', 'join_date', 'date_of_birth',
        'gender', 'first_name', 'last_name', 'phone', 'email', 'address', 'biography',
        'parents_guardians', 'profile_image_url', 'consent_photography',
        'emergency_contact_name', 'emergency_contact_phone', 'club_affiliation',
        'handicap', 'medical_conditions', 'parental_consent', 'created_at', 'updated_at'
    ),
    'jgk_memberships' => array(
        'id', 'member_id', 'plan_id', 'status', 'start_date', 'end_date',
        'auto_renew', 'created_at', 'updated_at'
    ),
    'jgk_plans' => array(
        'id', 'name', 'description', 'price', 'currency', 'billing_period',
        'features', 'status', 'created_at', 'updated_at'
    ),
    'jgk_payments' => array(
        'id', 'member_id', 'membership_id', 'amount', 'currency', 'payment_method',
        'payment_gateway', 'transaction_id', 'status', 'payment_date',
        'created_at', 'updated_at'
    ),
    'jgk_competition_entries' => array(
        'id', 'member_id', 'competition_name', 'competition_date', 'entry_date',
        'status', 'score', 'placement', 'notes', 'created_at', 'updated_at'
    ),
    'jgk_certifications' => array(
        'id', 'member_id', 'certification_type', 'certification_name',
        'issuing_organization', 'issue_date', 'expiry_date', 'certificate_number',
        'status', 'created_at', 'updated_at'
    ),
    'jgk_audit_log' => array(
        'id', 'user_id', 'action', 'entity_type', 'entity_id', 'old_values',
        'new_values', 'ip_address', 'user_agent', 'created_at'
    ),
    'jgf_coach_profiles' => array(
        'id', 'user_id', 'bio', 'specialization', 'experience_years',
        'certifications', 'hourly_rate', 'availability', 'verification_status',
        'created_at', 'updated_at'
    ),
    'jgf_coach_ratings' => array(
        'id', 'coach_user_id', 'member_id', 'rating', 'review', 'created_at'
    ),
    'jgf_recommendations' => array(
        'id', 'coach_user_id', 'member_id', 'recommendation_text',
        'recommendation_type', 'status', 'created_at', 'updated_at'
    ),
    'jgf_training_schedules' => array(
        'id', 'coach_user_id', 'member_id', 'training_date', 'start_time',
        'end_time', 'location', 'notes', 'status', 'created_at', 'updated_at'
    ),
    'jgf_role_requests' => array(
        'id', 'user_id', 'requested_role', 'status', 'notes', 'created_at', 'updated_at'
    )
);

$all_good = true;

foreach ($expected_structures as $table_name => $expected_columns) {
    $full_table_name = $wpdb->prefix . $table_name;
    
    echo "Checking $full_table_name...\n";
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    
    if (!$table_exists) {
        echo "  ❌ Table does not exist!\n\n";
        $all_good = false;
        continue;
    }
    
    // Get actual columns
    $actual_columns_result = $wpdb->get_results("DESCRIBE $full_table_name");
    $actual_columns = array_map(function($col) { return $col->Field; }, $actual_columns_result);
    
    // Check for missing columns
    $missing = array_diff($expected_columns, $actual_columns);
    
    // Check for extra columns (not necessarily bad, but worth noting)
    $extra = array_diff($actual_columns, $expected_columns);
    
    if (empty($missing) && empty($extra)) {
        echo "  ✅ All columns present and correct\n";
    } else {
        if (!empty($missing)) {
            echo "  ❌ Missing columns: " . implode(', ', $missing) . "\n";
            $all_good = false;
        }
        if (!empty($extra)) {
            echo "  ⚠️  Extra columns (not used in current code): " . implode(', ', $extra) . "\n";
        }
    }
    
    echo "  Total columns: " . count($actual_columns) . "\n\n";
}

if ($all_good) {
    echo "=== ✅ ALL TABLES ARE CORRECTLY CONFIGURED ===\n";
} else {
    echo "=== ❌ SOME TABLES NEED ATTENTION ===\n";
}
