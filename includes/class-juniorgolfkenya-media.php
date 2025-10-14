<?php

/**
 * Media management operations
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

/**
 * Media management class.
 *
 * This class handles profile image uploads and media library operations.
 */
class JuniorGolfKenya_Media {

    /**
     * Upload and attach profile image to a member
     *
     * @since    1.0.0
     * @param    int      $member_id    Member ID
     * @param    array    $file         $_FILES array entry
     * @return   array                  Result with attachment_id or error
     */
    public static function upload_profile_image($member_id, $file) {
        // Validate member exists
        $member = JuniorGolfKenya_Database::get_member($member_id);
        if (!$member) {
            return array(
                'success' => false,
                'message' => 'Member not found'
            );
        }

        // Validate file upload
        if (!isset($file['error']) || is_array($file['error'])) {
            return array(
                'success' => false,
                'message' => 'Invalid file upload'
            );
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'success' => false,
                'message' => self::get_upload_error_message($file['error'])
            );
        }

        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            return array(
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.'
            );
        }

        // Validate file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            return array(
                'success' => false,
                'message' => 'File too large. Maximum size is 5MB.'
            );
        }

        // Load WordPress media functions
        if (!function_exists('media_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        // Prepare file for upload
        $upload_overrides = array(
            'test_form' => false,
            'test_type' => true
        );

        // Create a temporary $_FILES entry if uploading programmatically
        if (!isset($_FILES['profile_image'])) {
            $_FILES['profile_image'] = $file;
        }

        // Upload to media library
        $attachment_id = media_handle_upload('profile_image', 0, array(), $upload_overrides);

        if (is_wp_error($attachment_id)) {
            return array(
                'success' => false,
                'message' => $attachment_id->get_error_message()
            );
        }

        // Delete old profile image if exists
        if (!empty($member->profile_image_id)) {
            wp_delete_attachment($member->profile_image_id, true);
        }

        // Update member with new profile image
        $result = JuniorGolfKenya_Database::update_member($member_id, array(
            'profile_image_id' => $attachment_id
        ));

        if (!$result) {
            // Rollback - delete uploaded image
            wp_delete_attachment($attachment_id, true);
            return array(
                'success' => false,
                'message' => 'Failed to update member profile'
            );
        }

        // Log the action
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'profile_image_uploaded',
            'object_type' => 'member',
            'object_id' => $member_id,
            'old_values' => json_encode(array('profile_image_id' => $member->profile_image_id)),
            'new_values' => json_encode(array('profile_image_id' => $attachment_id))
        ));

        return array(
            'success' => true,
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'message' => 'Profile image uploaded successfully'
        );
    }

    /**
     * Get member profile image URL
     *
     * @since    1.0.0
     * @param    int       $member_id    Member ID
     * @param    string    $size         Image size (thumbnail, medium, large, full)
     * @return   string|false             Image URL or false if not found
     */
    public static function get_profile_image_url($member_id, $size = 'thumbnail') {
        $member = JuniorGolfKenya_Database::get_member($member_id);
        
        if (!$member || empty($member->profile_image_id)) {
            return false;
        }

        $image_data = wp_get_attachment_image_src($member->profile_image_id, $size);
        
        return $image_data ? $image_data[0] : false;
    }

    /**
     * Get member profile image HTML
     *
     * @since    1.0.0
     * @param    int       $member_id    Member ID
     * @param    string    $size         Image size
     * @param    array     $attr         Additional attributes
     * @return   string                  HTML img tag or default avatar
     */
    public static function get_profile_image_html($member_id, $size = 'thumbnail', $attr = array()) {
        $member = JuniorGolfKenya_Database::get_member($member_id);
        
        if (!$member || empty($member->profile_image_id)) {
            return self::get_default_avatar_html($member, $size, $attr);
        }

        $default_attr = array(
            'class' => 'jgk-profile-image',
            'alt' => ($member->first_name ?? '') . ' ' . ($member->last_name ?? ''),
            'loading' => 'lazy'
        );

        $attr = wp_parse_args($attr, $default_attr);

        return wp_get_attachment_image($member->profile_image_id, $size, false, $attr);
    }

    /**
     * Get default avatar HTML
     *
     * @since    1.0.0
     * @param    object    $member    Member object
     * @param    string    $size      Image size
     * @param    array     $attr      Additional attributes
     * @return   string               HTML for default avatar
     */
    public static function get_default_avatar_html($member, $size = 'thumbnail', $attr = array()) {
        // Return empty string when no image is available
        return '';
    }

    /**
     * Delete member profile image
     *
     * @since    1.0.0
     * @param    int    $member_id    Member ID
     * @return   bool                 True on success, false on failure
     */
    public static function delete_profile_image($member_id) {
        $member = JuniorGolfKenya_Database::get_member($member_id);
        
        if (!$member || empty($member->profile_image_id)) {
            return false;
        }

        $old_attachment_id = $member->profile_image_id;

        // Update member record
        $result = JuniorGolfKenya_Database::update_member($member_id, array(
            'profile_image_id' => null
        ));

        if (!$result) {
            return false;
        }

        // Delete attachment from media library
        wp_delete_attachment($old_attachment_id, true);

        // Log the action
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'profile_image_deleted',
            'object_type' => 'member',
            'object_id' => $member_id,
            'old_values' => json_encode(array('profile_image_id' => $old_attachment_id)),
            'new_values' => json_encode(array('profile_image_id' => null))
        ));

        return true;
    }

    /**
     * Get upload error message
     *
     * @since    1.0.0
     * @param    int       $error_code    PHP upload error code
     * @return   string                   Human-readable error message
     */
    private static function get_upload_error_message($error_code) {
        $messages = array(
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        );

        return isset($messages[$error_code]) ? $messages[$error_code] : 'Unknown upload error';
    }

    /**
     * Validate and resize image
     *
     * @since    1.0.0
     * @param    int       $attachment_id    Attachment ID
     * @param    int       $max_width        Maximum width
     * @param    int       $max_height       Maximum height
     * @return   bool                        True on success, false on failure
     */
    public static function resize_image($attachment_id, $max_width = 800, $max_height = 800) {
        $file = get_attached_file($attachment_id);
        
        if (!$file) {
            return false;
        }

        if (!function_exists('wp_get_image_editor')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        $editor = wp_get_image_editor($file);
        
        if (is_wp_error($editor)) {
            return false;
        }

        $size = $editor->get_size();
        
        // Only resize if image is larger than max dimensions
        if ($size['width'] > $max_width || $size['height'] > $max_height) {
            $editor->resize($max_width, $max_height, false);
            $saved = $editor->save($file);
            
            if (is_wp_error($saved)) {
                return false;
            }

            // Regenerate thumbnails
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file));
        }

        return true;
    }

    /**
     * Get all members with profile images
     *
     * @since    1.0.0
     * @return   array    Array of member objects with profile images
     */
    public static function get_members_with_images() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'jgk_members';
        
        $query = "
            SELECT id, user_id, first_name, last_name, profile_image_id
            FROM $table
            WHERE profile_image_id IS NOT NULL
            ORDER BY updated_at DESC
        ";
        
        return $wpdb->get_results($query);
    }
}
