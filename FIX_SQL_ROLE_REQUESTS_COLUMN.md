# 🔧 SQL Error Fix - Role Requests Column Name

**Error:** "Unknown column 'user_id' in 'where clause'"  
**Date:** 11 octobre 2025  
**Status:** ✅ FIXED

---

## 🐛 Problem Description

**Error Message:**
```
WordPress database error: [Unknown column 'user_id' in 'where clause']
SELECT * FROM wp_jgf_role_requests WHERE user_id = 1 ORDER BY created_at DESC LIMIT 1
```

**Root Cause:**
The `wp_jgf_role_requests` table uses the column name **`requester_user_id`** but several SQL queries were using **`user_id`** instead.

---

## 📊 Table Schema

The correct schema from `class-juniorgolfkenya-activator.php`:

```sql
CREATE TABLE wp_jgf_role_requests (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    requester_user_id bigint(20) UNSIGNED NOT NULL,  ← CORRECT NAME
    requested_role varchar(64) NOT NULL,
    reason text,
    status varchar(32) DEFAULT 'pending',
    reviewed_by bigint(20) UNSIGNED,
    reviewed_at datetime,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY requester_user_id (requester_user_id),
    KEY requested_role (requested_role),
    KEY status (status)
)
```

---

## 🔍 Files Corrected

### 1. `includes/class-juniorgolfkenya-activator.php`

**Line ~637** - Check existing request in coach request form

**Before:**
```php
$existing_request = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$role_requests_table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
    $user_id
));
```

**After:**
```php
$existing_request = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$role_requests_table} WHERE requester_user_id = %d ORDER BY created_at DESC LIMIT 1",
    $user_id
));
```

---

### 2. `juniorgolfkenya.php` - First Ajax Handler

**Line ~362** - Check pending request (Ajax)

**Before:**
```php
$existing_request = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$role_requests_table} WHERE user_id = %d AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
    $user_id
));
```

**After:**
```php
$existing_request = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$role_requests_table} WHERE requester_user_id = %d AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
    $user_id
));
```

---

### 3. `juniorgolfkenya.php` - First Ajax INSERT

**Line ~378** - Insert coach request data (Ajax)

**Before:**
```php
$data = array(
    'user_id' => $user_id,
    'requested_role' => 'jgk_coach',
    // ... other fields
);
```

**After:**
```php
$data = array(
    'requester_user_id' => $user_id,
    'requested_role' => 'jgk_coach',
    // ... other fields
);
```

---

### 4. `juniorgolfkenya.php` - Second Form Handler

**Line ~460** - Check pending request (Regular form)

**Before:**
```php
$existing_request = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$role_requests_table} WHERE user_id = %d AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
    $user_id
));
```

**After:**
```php
$existing_request = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$role_requests_table} WHERE requester_user_id = %d AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
    $user_id
));
```

---

### 5. `juniorgolfkenya.php` - Second Form INSERT

**Line ~478** - Insert coach request data (Regular form)

**Before:**
```php
$data = array(
    'user_id' => $user_id,
    'requested_role' => 'jgk_coach',
    // ... other fields
);
```

**After:**
```php
$data = array(
    'requester_user_id' => $user_id,
    'requested_role' => 'jgk_coach',
    // ... other fields
);
```

---

## ✅ Already Correct

These files/methods already use the correct column name:

### `includes/class-juniorgolfkenya-database.php`

**Method:** `get_role_requests()` - Line ~520

```php
SELECT r.*, u.display_name, u.user_email
FROM wp_jgk_role_requests r
LEFT JOIN wp_users u ON r.requester_user_id = u.ID  ← CORRECT ✅
WHERE r.status = %s
ORDER BY r.created_at DESC
```

This was already using `requester_user_id` correctly in the JOIN.

---

## 🧪 Testing the Fix

### 1. Test Coach Application (Ajax)

**Steps:**
1. Logout of WordPress
2. Register as new member
3. Go to `/coach-role-request`
4. Fill in coach application form
5. Submit via Ajax

**Expected Result:**
- ✅ No SQL error
- ✅ Request saved to database
- ✅ Success message shown
- ✅ Admin receives email notification

### 2. Test Duplicate Request Check

**Steps:**
1. Login as member who already applied
2. Go to `/coach-role-request` again
3. Try to submit again

**Expected Result:**
- ✅ Message: "You have a pending coach role request"
- ✅ Shows submission date
- ✅ Shows current status
- ✅ No duplicate entry created

### 3. Test Admin View

**Steps:**
1. Login as administrator
2. Go to Dashboard → Role Requests
3. View pending coach requests

**Expected Result:**
- ✅ All requests load correctly
- ✅ Requester name shows (from JOIN)
- ✅ Request details visible
- ✅ Can approve/reject requests

---

## 📝 Summary of Changes

| File | Line | Change | Type |
|------|------|--------|------|
| `class-juniorgolfkenya-activator.php` | ~637 | `user_id` → `requester_user_id` | SELECT |
| `juniorgolfkenya.php` | ~362 | `user_id` → `requester_user_id` | SELECT |
| `juniorgolfkenya.php` | ~378 | `'user_id'` → `'requester_user_id'` | INSERT |
| `juniorgolfkenya.php` | ~460 | `user_id` → `requester_user_id` | SELECT |
| `juniorgolfkenya.php` | ~478 | `'user_id'` → `'requester_user_id'` | INSERT |

**Total Changes:** 5 corrections across 2 files

---

## 🔄 Why This Happened

**Inconsistency in naming convention:**

1. **Database schema** used: `requester_user_id` (more descriptive)
2. **Some queries** used: `user_id` (simpler but ambiguous)

**Lesson:** Always use the exact column name from the table schema!

---

## 🛡️ Prevention

### Future Development Guidelines:

1. **Always reference the schema** when writing SQL queries
2. **Use consistent naming** across all tables
3. **Test with SQL error reporting** enabled:
   ```php
   // In wp-config.php for development:
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
4. **Add unit tests** for database operations
5. **Document column names** in code comments

---

## ✅ Fix Complete!

All SQL queries now use the correct column name **`requester_user_id`**.

**Test the following pages:**
- ✅ `/coach-role-request` - Coach application form
- ✅ Dashboard → Role Requests - Admin view
- ✅ Member Portal - After login
- ✅ Coach Dashboard - For approved coaches

**No more SQL errors!** 🎉
