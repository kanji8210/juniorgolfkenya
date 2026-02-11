<?php
/**
 * Diagnostic test to determine why member images are not showing
 * - Checks default avatar HTML for members without images
 * - Uploads a tiny test image and verifies the URL and HTML output
 */

// Load WordPress
if (php_sapi_name() === 'cli') {
    require_once('../../../wp-load.php');
} else {
    if (!defined('ABSPATH')) {
        die('This file must be accessed through WordPress');
    }
}

// Load plugin classes (paths relative to tests folder)
require_once('includes/class-juniorgolfkenya-database.php');
require_once('includes/class-juniorgolfkenya-user-manager.php');
require_once('includes/class-juniorgolfkenya-media.php');

// Ensure WP media functions are available
if (!function_exists('media_handle_upload')) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
}

echo "=== Member Image Visibility Diagnostic ===\n";

// Create a minimal test member
$user_data = array(
    'user_login' => 'diag_image_member_' . time(),
    'user_email' => 'diag_image_' . time() . '@test.local',
    'user_pass' => wp_generate_password(12),
    'display_name' => 'Diag Image'
);

$member_data = array(
    'membership_type' => 'junior',
    'status' => 'active',
    'date_of_birth' => date('Y-m-d', strtotime('-12 years'))
);

$create = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data);
if (empty($create['success'])) {
    echo "ERROR: Failed to create test member: " . ($create['message'] ?? 'unknown') . "\n";
    exit(1);
}

$member_id = $create['member_id'];
$user_id = $create['user_id'];

echo "Created member ID: $member_id (user $user_id)\n";

// Check default avatar HTML
$avatar_html = JuniorGolfKenya_Media::get_profile_image_html($member_id, 'thumbnail');

if ($avatar_html === '' || $avatar_html === null) {
    echo "NOTICE: Default avatar HTML is empty. This will cause no image to appear in lists/views.\n";
} elseif (strpos($avatar_html, '<img') !== false) {
    echo "OK: Default avatar HTML contains an <img> tag (unexpected for new member).\n";
} else {
    // show truncated output for inspection
    $out = trim(preg_replace('/\s+/', ' ', $avatar_html));
    $preview = substr($out, 0, 200);
    echo "NOTICE: Default avatar HTML present but does not contain <img>. Preview: $preview\n";
}

// Inspect DB for profile_image_id
$member_obj = JuniorGolfKenya_Database::get_member($member_id);
$profile_image_id = $member_obj->profile_image_id ?? null;
echo "DB profile_image_id: " . var_export($profile_image_id, true) . "\n";

// Create tiny JPEG and upload
$tmp = sys_get_temp_dir() . '/diag_img_' . time() . '.jpg';
$jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA//2Q==');
file_put_contents($tmp, $jpeg);

$file_array = array(
    'name' => 'diag.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => $tmp,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($tmp)
);

echo "Uploading test image...\n";
$upload = JuniorGolfKenya_Media::upload_profile_image($member_id, $file_array);
if (empty($upload['success'])) {
    echo "ERROR: Upload failed: " . ($upload['message'] ?? 'unknown') . "\n";
} else {
    echo "Upload success. Attachment ID: " . ($upload['attachment_id'] ?? 'N/A') . "\n";
    echo "Attachment URL: " . ($upload['url'] ?? 'N/A') . "\n";

    // Verify get_profile_image_url and HTML
    $url = JuniorGolfKenya_Media::get_profile_image_url($member_id, 'thumbnail');
    echo "get_profile_image_url (thumbnail): " . var_export($url, true) . "\n";

    $html_after = JuniorGolfKenya_Media::get_profile_image_html($member_id, 'thumbnail');
    if (strpos($html_after, '<img') !== false) {
        echo "OK: get_profile_image_html returns an <img> tag after upload.\n";
    } else {
        echo "WARNING: get_profile_image_html does not return <img> even after upload. Output: " . substr(trim(preg_replace('/\s+/', ' ', $html_after)), 0, 200) . "\n";
    }
}

// Cleanup
echo "Cleaning up...\n";
if (!empty($upload['attachment_id'])) {
    wp_delete_attachment($upload['attachment_id'], true);
}
JuniorGolfKenya_Database::delete_member($member_id);
wp_delete_user($user_id);
if (file_exists($tmp)) unlink($tmp);

echo "Done.\n";

// Exit with non-zero if default avatar was empty (helps CI detect the root cause)
if ($avatar_html === '' || $avatar_html === null) {
    exit(2);
}

exit(0);
