<?php
/**
 * Automated diagnostic for membership renewal rules
 * - Creates test members with different statuses and expiry dates
 * - Checks when renewal logic (payment processing eligibility) would allow processing
 */

// Load WordPress
if (php_sapi_name() === 'cli') {
    require_once('../../../wp-load.php');
} else {
    if (!defined('ABSPATH')) {
        die('This file must be accessed through WordPress');
    }
}

require_once('includes/class-juniorgolfkenya-database.php');
require_once('includes/class-juniorgolfkenya-user-manager.php');
require_once('includes/class-juniorgolfkenya-member-data.php');

echo "=== Renewal Flow Diagnostic ===\n\n";

$allowed_statuses = array('approved', 'pending', 'pending_approval', 'active');

// Helper to create member
function create_test_member($email_suffix, $status, $expiry_offset_days) {
    $user_data = array(
        'user_login' => 'renew_test_' . $email_suffix . '_' . time(),
        'user_email' => 'renew_' . $email_suffix . '_' . time() . '@test.local',
        'user_pass' => wp_generate_password(12),
        'display_name' => 'Renew Test '
    );

    $member_data = array(
        'membership_type' => 'junior',
        'status' => $status,
        'date_of_birth' => date('Y-m-d', strtotime('-12 years')),
        'expiry_date' => date('Y-m-d', strtotime("{$expiry_offset_days} days"))
    );

    $res = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data);
    if (empty($res['success'])) {
        echo "ERROR: Failed to create test member: " . ($res['message'] ?? 'unknown') . "\n";
        return false;
    }

    return $res;
}

// Test 1: Approved member (should be eligible for renewal processing)
$res1 = create_test_member('approved', 'approved', '+30');
if (!$res1) exit(1);
$m1 = JuniorGolfKenya_Database::get_member($res1['member_id']);
$status1 = JuniorGolfKenya_Member_Data::get_membership_status($m1);
$eligible1 = in_array($m1->status, $allowed_statuses, true);

echo "Member #{$m1->id} status='{$m1->status}' expiry='{$m1->expiry_date}'\n";
echo "  - Membership status summary: " . json_encode($status1) . "\n";
echo "  - Renewal processing eligible: " . ($eligible1 ? 'YES' : 'NO') . "\n\n";

// Test 2: Expired member (should NOT be eligible according to current processing rules)
$res2 = create_test_member('expired', 'expired', '-10');
if (!$res2) exit(1);
$m2 = JuniorGolfKenya_Database::get_member($res2['member_id']);
$status2 = JuniorGolfKenya_Member_Data::get_membership_status($m2);
$eligible2 = in_array($m2->status, $allowed_statuses, true);

echo "Member #{$m2->id} status='{$m2->status}' expiry='{$m2->expiry_date}'\n";
echo "  - Membership status summary: " . json_encode($status2) . "\n";
echo "  - Renewal processing eligible: " . ($eligible2 ? 'YES' : 'NO') . "\n\n";

// Expectations
echo "=== EXPECTATIONS ===\n";
echo "- Approved member should be eligible: " . ($eligible1 ? 'PASS' : 'FAIL') . "\n";
echo "- Expired member should NOT be eligible (current code excludes 'expired'): " . (!$eligible2 ? 'PASS' : 'FAIL') . "\n\n";

// Cleanup
echo "Cleaning up test members...\n";
JuniorGolfKenya_Database::delete_member($m1->id);
wp_delete_user($m1->user_id);
JuniorGolfKenya_Database::delete_member($m2->id);
wp_delete_user($m2->user_id);

echo "Done.\n";

exit(0);
