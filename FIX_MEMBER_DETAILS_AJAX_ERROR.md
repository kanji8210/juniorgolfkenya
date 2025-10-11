# Fix Member Details Modal AJAX Error

**Date**: 2024
**Issue**: "Network error. Please try again." when clicking "View Details" button

## Problem

The member details modal was showing a network error because:
1. The JavaScript was using `ajaxurl` which wasn't defined in the admin context
2. The parents table query could fail if the table doesn't exist

## Solution

### 1. **Localized AJAX URL** - `admin/class-juniorgolfkenya-admin.php`

Added `wp_localize_script()` to make AJAX URL available to JavaScript:

```php
public function enqueue_scripts() {
    wp_enqueue_script($this->plugin_name, JUNIORGOLFKENYA_PLUGIN_URL . 'admin/js/juniorgolfkenya-admin.js', array('jquery'), $this->version, false);
    
    // Localize script with AJAX URL
    wp_localize_script($this->plugin_name, 'jgkAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('jgk_ajax_nonce')
    ));
}
```

### 2. **Fallback AJAX URL** - `admin/partials/juniorgolfkenya-admin-members.php`

Updated JavaScript to use localized URL with PHP fallback:

```javascript
// Get AJAX URL (fallback for compatibility)
const ajaxUrl = (typeof jgkAjax !== 'undefined') ? jgkAjax.ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';

// Fetch member details via AJAX
jQuery.post(ajaxUrl, {
    action: 'jgk_get_member_details',
    member_id: memberId,
    nonce: '<?php echo wp_create_nonce('jgk_get_member_details'); ?>'
}, function(response) {
    // ...
});
```

### 3. **Safe Parents Table Query** - `juniorgolfkenya.php`

Added table existence check before querying parents:

```php
// Get parents/guardians (check if table exists first)
$parents = array();
if ($wpdb->get_var("SHOW TABLES LIKE '{$parents_table}'") == $parents_table) {
    $parents = $wpdb->get_results($wpdb->prepare("
        SELECT 
            parent_name as name,
            relationship,
            phone,
            email
        FROM {$parents_table}
        WHERE member_id = %d
        ORDER BY 
            CASE relationship
                WHEN 'father' THEN 1
                WHEN 'mother' THEN 2
                WHEN 'guardian' THEN 3
                ELSE 4
            END
    ", $member_id));
}
```

## Files Modified

1. ✅ `admin/class-juniorgolfkenya-admin.php` - Added wp_localize_script()
2. ✅ `admin/partials/juniorgolfkenya-admin-members.php` - Added fallback AJAX URL
3. ✅ `juniorgolfkenya.php` - Added table existence check

## Benefits

- ✅ AJAX URL properly defined in all contexts
- ✅ Fallback ensures compatibility
- ✅ Safe database queries prevent errors
- ✅ Member details modal now works correctly

## Testing

1. ✅ Click "View Details" button
2. ✅ Modal opens without network error
3. ✅ Member details load successfully
4. ✅ Works even if parents table doesn't exist
5. ✅ Profile image displays correctly
6. ✅ Coaches list displays from junction table
7. ✅ Parents/guardians display if available

## Result

The "View Details" modal now loads member information successfully without network errors! 🎉
