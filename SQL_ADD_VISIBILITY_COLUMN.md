# 🔧 Quick SQL Setup - Add is_public Column

**Purpose:** Add member visibility control column to existing database  
**Status:** ✅ READY TO RUN

---

## 🎯 Quick Setup (30 seconds)

### Option 1: Run in phpMyAdmin

1. Open **phpMyAdmin**
2. Select your WordPress database
3. Click **SQL** tab
4. Copy-paste this query:

```sql
-- Add is_public column if it doesn't exist
ALTER TABLE wp_jgk_members
ADD COLUMN IF NOT EXISTS is_public TINYINT(1) DEFAULT 0 COMMENT 'Public visibility: 0=hidden, 1=visible',
ADD INDEX IF NOT EXISTS idx_is_public (is_public);

-- Verify it worked
DESCRIBE wp_jgk_members;
```

5. Click **Go**
6. ✅ Done!

---

### Option 2: Run via WordPress Plugin

If you prefer, **deactivate and reactivate** the plugin:

1. Go to **Plugins**
2. **Deactivate** "Junior Golf Kenya"
3. **Activate** "Junior Golf Kenya"
4. ✅ Column automatically created!

**Why this works:** The plugin activation includes the new schema, so reactivating applies the changes.

---

## 🧪 Verify Installation

### Check if column exists:

```sql
SHOW COLUMNS FROM wp_jgk_members LIKE 'is_public';
```

**Expected result:**
```
Field      | Type       | Null | Key | Default | Extra
-----------|------------|------|-----|---------|------
is_public  | tinyint(1) | YES  | MUL | 0       |
```

### Check index exists:

```sql
SHOW INDEX FROM wp_jgk_members WHERE Column_name = 'is_public';
```

**Expected result:**
```
Table          | Key_name       | Column_name | Index_type
---------------|----------------|-------------|------------
wp_jgk_members | idx_is_public  | is_public   | BTREE
```

---

## 🔄 Migration Scenarios

### Scenario 1: New Installation

✅ **Nothing to do!**  
The column is automatically created during plugin activation.

### Scenario 2: Existing Installation (Before this feature)

⚠️ **Action needed:**  
Run the SQL above OR reactivate the plugin.

### Scenario 3: Make All Active Members Public (Optional)

If you want all current active members to be visible:

```sql
-- Make all active members public
UPDATE wp_jgk_members
SET is_public = 1
WHERE status = 'active';

-- Verify
SELECT 
    COUNT(*) as total_members,
    SUM(is_public = 1) as public_members,
    SUM(is_public = 0) as hidden_members
FROM wp_jgk_members;
```

### Scenario 4: Make Only Members with Photos Public

More selective approach:

```sql
-- Make public only if:
-- - Status is active
-- - Has profile photo
-- - Consent to photography is 'yes'
UPDATE wp_jgk_members
SET is_public = 1
WHERE status = 'active'
AND profile_image_id IS NOT NULL
AND consent_photography = 'yes';

-- Verify
SELECT first_name, last_name, is_public, profile_image_id, consent_photography
FROM wp_jgk_members
WHERE is_public = 1;
```

---

## 🐛 Troubleshooting

### Error: Column already exists

**If you see:**
```
ERROR 1060: Duplicate column name 'is_public'
```

**Solution:** Column already exists, no action needed! ✅

### Error: Table doesn't exist

**If you see:**
```
ERROR 1146: Table 'wp_jgk_members' doesn't exist
```

**Solution:** 
1. Make sure plugin is activated
2. Check table prefix (might not be `wp_`)
3. Run this to find your prefix:

```sql
SHOW TABLES LIKE '%jgk_members';
```

Then replace `wp_` with your actual prefix.

### Check Current State

**See all member visibility:**

```sql
SELECT 
    id,
    membership_number,
    first_name,
    last_name,
    status,
    is_public,
    CASE 
        WHEN is_public = 1 THEN '👁️ PUBLIC'
        ELSE '🔒 HIDDEN'
    END as visibility_status
FROM wp_jgk_members
ORDER BY is_public DESC, first_name;
```

---

## ✅ Post-Installation Checklist

After running the SQL:

- [ ] Column `is_public` exists
- [ ] Index `idx_is_public` exists  
- [ ] Default value is `0` (all hidden)
- [ ] Admin can see "Public Visibility" dropdown in edit form
- [ ] Admin can see visibility badge in members list
- [ ] Shortcode `[jgk_public_members]` works
- [ ] Only members with `is_public = 1` appear in gallery

---

## 🚀 Quick Test

1. **Add column** (run SQL above)
2. **Edit any member** in admin
3. **Set "Public Visibility"** to "Visible Publicly"
4. **Create test page** with `[jgk_public_members]`
5. **Check page** - member should appear!

**Total time:** 2 minutes ⏱️

---

**READY TO GO! 🎉**

For detailed documentation, see `MEMBER_VISIBILITY_GUIDE.md`
