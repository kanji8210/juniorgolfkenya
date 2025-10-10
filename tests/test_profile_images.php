<?php
/**
 * Test script for member profile image management
 */

// Load WordPress
if (php_sapi_name() === 'cli') {
    require_once('../../../wp-load.php');
} else {
    if (!defined('ABSPATH')) {
        die('This file must be accessed through WordPress');
    }
}

// Load plugin classes
require_once('includes/class-juniorgolfkenya-database.php');
require_once('includes/class-juniorgolfkenya-user-manager.php');
require_once('includes/class-juniorgolfkenya-parents.php');
require_once('includes/class-juniorgolfkenya-media.php');

// Load WordPress functions
if (!function_exists('wp_create_user')) {
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}
if (!function_exists('wp_delete_user')) {
    require_once(ABSPATH . 'wp-admin/includes/user.php');
}
if (!function_exists('media_handle_upload')) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
}

echo "=== Testing Member Profile Image Management ===\n\n";

$test_results = array();
$test_number = 1;

/**
 * Test 1: Create a test member for image upload
 */
echo "Test $test_number: Create test member\n";
$test_number++;

$user_data = array(
    'user_login' => 'test_image_member_' . time(),
    'user_email' => 'image_member_' . time() . '@test.com',
    'user_pass' => 'TestPassword123!',
    'display_name' => 'Test Image Member',
    'first_name' => 'Test',
    'last_name' => 'Image'
);

$member_data = array(
    'membership_type' => 'adult',
    'status' => 'active',
    'date_of_birth' => date('Y-m-d', strtotime('-25 years')),
    'gender' => 'male'
);

$result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data);
if ($result['success']) {
    echo "✓ PASS: Test member created\n";
    echo "  - User ID: " . $result['user_id'] . "\n";
    echo "  - Member ID: " . $result['member_id'] . "\n";
    $test_results[] = true;
    $test_member_id = $result['member_id'];
    $test_user_id = $result['user_id'];
} else {
    echo "✗ FAIL: Failed to create test member: " . $result['message'] . "\n";
    $test_results[] = false;
    die("Cannot continue without test member\n");
}
echo "\n";

/**
 * Test 2: Test default avatar generation
 */
echo "Test $test_number: Generate default avatar\n";
$test_number++;

$member = JuniorGolfKenya_Database::get_member($test_member_id);
$avatar_html = JuniorGolfKenya_Media::get_profile_image_html($test_member_id, 'thumbnail');

if (strpos($avatar_html, 'jgk-avatar-default') !== false) {
    echo "✓ PASS: Default avatar generated\n";
    echo "  - HTML contains 'jgk-avatar-default' class\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: Default avatar not properly generated\n";
    echo "  - HTML output: " . substr($avatar_html, 0, 100) . "...\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 3: Test profile image URL (should be false for new member)
 */
echo "Test $test_number: Check profile image URL (should be false)\n";
$test_number++;

$image_url = JuniorGolfKenya_Media::get_profile_image_url($test_member_id);
if ($image_url === false) {
    echo "✓ PASS: No profile image URL for new member\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: Unexpected image URL found: $image_url\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 4: Create a test image file for upload
 */
echo "Test $test_number: Create test image file\n";
$test_number++;

// Create a minimal valid JPEG file (1x1 pixel)
$test_image_path = sys_get_temp_dir() . '/test_profile_' . time() . '.jpg';

// Minimal JPEG header for a 1x1 red pixel
$jpeg_data = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA//2Q==');
file_put_contents($test_image_path, $jpeg_data);

if (file_exists($test_image_path)) {
    echo "✓ PASS: Test image created at $test_image_path\n";
    echo "  - Size: " . filesize($test_image_path) . " bytes\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: Failed to create test image\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 5: Upload profile image
 */
echo "Test $test_number: Upload profile image\n";
$test_number++;

$file_array = array(
    'name' => 'test_profile.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => $test_image_path,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($test_image_path)
);

$upload_result = JuniorGolfKenya_Media::upload_profile_image($test_member_id, $file_array);

if ($upload_result['success']) {
    echo "✓ PASS: Profile image uploaded successfully\n";
    echo "  - Attachment ID: " . $upload_result['attachment_id'] . "\n";
    echo "  - URL: " . $upload_result['url'] . "\n";
    $test_results[] = true;
    $test_attachment_id = $upload_result['attachment_id'];
} else {
    echo "✗ FAIL: Failed to upload image: " . $upload_result['message'] . "\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 6: Verify image URL after upload
 */
echo "Test $test_number: Verify image URL after upload\n";
$test_number++;

$image_url = JuniorGolfKenya_Media::get_profile_image_url($test_member_id);
if ($image_url && filter_var($image_url, FILTER_VALIDATE_URL)) {
    echo "✓ PASS: Profile image URL is valid\n";
    echo "  - URL: $image_url\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: Invalid or missing image URL\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 7: Verify HTML output with actual image
 */
echo "Test $test_number: Verify HTML output with image\n";
$test_number++;

$image_html = JuniorGolfKenya_Media::get_profile_image_html($test_member_id, 'thumbnail');
if (strpos($image_html, '<img') !== false && strpos($image_html, 'jgk-profile-image') !== false) {
    echo "✓ PASS: HTML contains proper image tag\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: HTML does not contain proper image tag\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 8: Upload a second image (should replace first)
 */
echo "Test $test_number: Upload replacement image\n";
$test_number++;

$test_image_path2 = sys_get_temp_dir() . '/test_profile2_' . time() . '.jpg';
// Use the same minimal JPEG
file_put_contents($test_image_path2, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA//2Q=='));

$file_array2 = array(
    'name' => 'test_profile2.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => $test_image_path2,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($test_image_path2)
);

$upload_result2 = JuniorGolfKenya_Media::upload_profile_image($test_member_id, $file_array2);

if ($upload_result2['success'] && $upload_result2['attachment_id'] != $test_attachment_id) {
    echo "✓ PASS: Second image uploaded and replaced first\n";
    echo "  - New Attachment ID: " . $upload_result2['attachment_id'] . "\n";
    echo "  - Old attachment should be deleted\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: Failed to replace image\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 9: Delete profile image
 */
echo "Test $test_number: Delete profile image\n";
$test_number++;

$delete_result = JuniorGolfKenya_Media::delete_profile_image($test_member_id);
if ($delete_result) {
    echo "✓ PASS: Profile image deleted successfully\n";
    $test_results[] = true;
    
    // Verify image URL is false after deletion
    $image_url_after_delete = JuniorGolfKenya_Media::get_profile_image_url($test_member_id);
    if ($image_url_after_delete === false) {
        echo "✓ PASS: Image URL correctly returns false after deletion\n";
        $test_results[] = true;
    } else {
        echo "✗ FAIL: Image URL still exists after deletion\n";
        $test_results[] = false;
    }
} else {
    echo "✗ FAIL: Failed to delete profile image\n";
    $test_results[] = false;
}
echo "\n";

/**
 * Test 10: Test file validation (invalid type)
 */
echo "Test $test_number: Test invalid file type (should fail)\n";
$test_number++;

$test_text_file = sys_get_temp_dir() . '/test_invalid.txt';
file_put_contents($test_text_file, 'This is not an image');

$file_array_invalid = array(
    'name' => 'test_invalid.txt',
    'type' => 'text/plain',
    'tmp_name' => $test_text_file,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($test_text_file)
);

$upload_result_invalid = JuniorGolfKenya_Media::upload_profile_image($test_member_id, $file_array_invalid);

if (!$upload_result_invalid['success']) {
    echo "✓ PASS: Invalid file type correctly rejected\n";
    echo "  - Message: " . $upload_result_invalid['message'] . "\n";
    $test_results[] = true;
} else {
    echo "✗ FAIL: Invalid file type was accepted\n";
    $test_results[] = false;
}

// Clean up test file
unlink($test_text_file);
echo "\n";

// Clean up test member
echo "Cleaning up test member...\n";
JuniorGolfKenya_Database::delete_member($test_member_id);
wp_delete_user($test_user_id);

// Clean up test images
if (file_exists($test_image_path)) unlink($test_image_path);
if (file_exists($test_image_path2)) unlink($test_image_path2);

echo "✓ Cleanup complete\n\n";

// Summary
$passed = array_sum($test_results);
$total = count($test_results);
$percentage = round(($passed / $total) * 100, 2);

echo "=== TEST SUMMARY ===\n";
echo "Passed: $passed / $total ($percentage%)\n";
echo ($passed === $total) ? "✓ ALL TESTS PASSED!\n" : "✗ SOME TESTS FAILED\n";
