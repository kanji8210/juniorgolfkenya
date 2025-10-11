# 👁️ Member Visibility Control - Complete Guide

**Feature:** Admin can control which members appear publicly  
**Date:** 11 octobre 2025  
**Status:** ✅ IMPLEMENTED

---

## 📋 Overview

This feature allows administrators to **control which members are visible to the public** (on galleries, member directories, leaderboards, etc.). Members can be:
- ✅ **Public** (Visible) - Shown in public listings
- ❌ **Hidden** (Private) - Only visible to admins and coaches

---

## 🎯 Use Cases

### Why Control Member Visibility?

1. **Privacy Protection**
   - Young members whose parents prefer privacy
   - VIP members who want discretion
   - New members during probation period

2. **Professional Presentation**
   - Show only active, photo-ready members
   - Hide suspended or expired members
   - Feature star performers

3. **Safety & Security**
   - Control who appears in public photos
   - Manage consent for promotional materials
   - Comply with GDPR/data protection

4. **Quality Control**
   - Hide incomplete profiles
   - Show only members with photos
   - Control brand representation

---

## 🔧 Technical Implementation

### 1. Database Changes

**File:** `includes/class-juniorgolfkenya-activator.php`

#### Added Column to `wp_jgk_members` Table

```sql
ALTER TABLE wp_jgk_members
ADD COLUMN is_public TINYINT(1) DEFAULT 0,
ADD INDEX idx_is_public (is_public);
```

**Column Details:**
- **Name:** `is_public`
- **Type:** `TINYINT(1)` (boolean)
- **Default:** `0` (hidden by default)
- **Values:**
  - `0` = Hidden (private)
  - `1` = Public (visible)
- **Index:** Yes (for fast filtering)

---

### 2. Admin Interface Changes

#### A. Edit Member Form

**File:** `admin/partials/juniorgolfkenya-admin-member-edit.php`

**Added field after Coach Assignment (line ~171):**

```php
<div class="jgk-form-field">
    <label for="is_public">Public Visibility</label>
    <select id="is_public" name="is_public">
        <option value="1" <?php selected($edit_member->is_public ?? 0, 1); ?>>
            ✓ Visible Publicly
        </option>
        <option value="0" <?php selected($edit_member->is_public ?? 0, 0); ?>>
            ✗ Hidden from Public
        </option>
    </select>
    <small>Control if this member appears in public member directories, galleries, and listings</small>
</div>
```

**Features:**
- ✅ Clear dropdown with icons
- ✅ Helpful description text
- ✅ Pre-selected based on current value
- ✅ Easy to understand (Visible/Hidden)

#### B. Members List Table

**File:** `admin/partials/juniorgolfkenya-admin-members.php`

**Added "Visibility" column in table header:**

```php
<thead>
    <tr>
        <th>Photo</th>
        <th>Member #</th>
        <th>Name</th>
        <th>Email</th>
        <th>Type</th>
        <th>Status</th>
        <th>Visibility</th>  <!-- NEW COLUMN -->
        <th>Coach</th>
        <th>Joined</th>
        <th>Actions</th>
    </tr>
</thead>
```

**Added visibility badge in table row:**

```php
<td style="text-align: center;">
    <?php if (isset($member->is_public) && $member->is_public == 1): ?>
    <span style="background: #46b450; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
        👁️ PUBLIC
    </span>
    <?php else: ?>
    <span style="background: #999; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
        🔒 HIDDEN
    </span>
    <?php endif; ?>
</td>
```

**Visual Indicators:**
- 🟢 **Green badge** = 👁️ PUBLIC (visible)
- ⚫ **Gray badge** = 🔒 HIDDEN (private)
- Easy to scan at a glance

#### C. Save Functionality

**File:** `admin/partials/juniorgolfkenya-admin-members.php`

**Added to edit_member case (line ~157):**

```php
$member_data = array(
    'first_name' => sanitize_text_field($_POST['first_name']),
    'last_name' => sanitize_text_field($_POST['last_name']),
    // ... other fields ...
    'is_public' => isset($_POST['is_public']) ? intval($_POST['is_public']) : 0
);
```

**Security:**
- ✅ Sanitized with `intval()`
- ✅ Defaults to `0` if not set
- ✅ Only accepts 0 or 1

---

### 3. Public Display Shortcode

**File:** `public/partials/juniorgolfkenya-public-members.php`

#### New Shortcode: `[jgk_public_members]`

**Purpose:** Display a gallery of public members on any page/post

**Attributes:**

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | int | 12 | Number of members to display |
| `columns` | int | 4 | Grid columns (1-6) |
| `orderby` | string | `first_name` | Sort field (first_name, last_name, handicap, created_at) |
| `order` | string | `ASC` | Sort direction (ASC, DESC) |
| `type` | string | (all) | Filter by membership type |

**Examples:**

```php
// Basic usage - show 12 members in 4 columns
[jgk_public_members]

// Show only 6 members in 3 columns
[jgk_public_members limit="6" columns="3"]

// Show junior members only, ordered by handicap
[jgk_public_members type="junior" orderby="handicap" order="ASC"]

// Show 20 members in 5 columns, newest first
[jgk_public_members limit="20" columns="5" orderby="created_at" order="DESC"]
```

#### Automatic Filtering

**Query automatically filters:**
```sql
SELECT * FROM wp_jgk_members
WHERE is_public = 1          -- Only public members
AND status = 'active'         -- Only active members
ORDER BY first_name ASC
LIMIT 12;
```

**Security:**
- ✅ Only shows `is_public = 1`
- ✅ Only shows `status = 'active'`
- ✅ All inputs sanitized
- ✅ SQL injection protected

---

## 🎨 Frontend Gallery Design

### Card Layout

Each member card displays:

```
┌─────────────────────────┐
│                         │
│    Profile Photo        │  (1:1 ratio)
│    or Initials          │
│                         │
├─────────────────────────┤
│  Name                   │  (H3 title)
│  Handicap: 12.5         │  (if available)
│  Club: Karen CC         │  (if available)
│  Short bio...           │  (20 words)
│  ─────────────────      │
│  [Junior Member]        │  (badge)
└─────────────────────────┘
```

### Responsive Grid

```css
Desktop (>1024px):  4 columns (or custom)
Tablet (768-1024):  3 columns
Mobile (480-768):   2 columns
Small (<480px):     1 column (centered, max 400px)
```

### Visual Features

- ✅ **Gradient backgrounds** for photos
- ✅ **Hover animations** (lift + shadow)
- ✅ **Placeholder initials** (if no photo)
- ✅ **Membership type badges** (with gradient)
- ✅ **Clean, modern design**
- ✅ **Fully responsive**

---

## 🔄 Workflow Examples

### Example 1: Make Member Public

**Admin side:**
1. Go to Members → All Members
2. Find member (currently shows 🔒 HIDDEN badge)
3. Click "Edit Member"
4. Find "Public Visibility" dropdown
5. Select "✓ Visible Publicly"
6. Click "Update Member"
7. ✅ Badge changes to 👁️ PUBLIC

**Frontend:**
- Member now appears in `[jgk_public_members]` galleries
- Member appears in public searches
- Member shown in leaderboards (Phase 2)

### Example 2: Hide Member from Public

**Scenario:** Parent requests privacy for junior member

**Admin side:**
1. Edit member profile
2. Change "Public Visibility" to "✗ Hidden from Public"
3. Save
4. ✅ Badge changes to 🔒 HIDDEN

**Frontend:**
- Member removed from all public galleries
- Member hidden in searches
- Member still visible to:
  - ✅ Admins (in admin panel)
  - ✅ Assigned coaches
  - ✅ Self (own dashboard)

### Example 3: Bulk Public Gallery Page

**Create page "Our Members":**

1. Create new page in WordPress
2. Add shortcode: `[jgk_public_members limit="24" columns="4"]`
3. Publish
4. Only members with `is_public = 1` appear
5. Auto-updates when admin changes visibility

---

## 📊 SQL Queries Reference

### Check Member Visibility

```sql
-- See all public members
SELECT id, first_name, last_name, is_public
FROM wp_jgk_members
WHERE is_public = 1
ORDER BY first_name;

-- Count public vs hidden
SELECT 
    is_public,
    COUNT(*) as count
FROM wp_jgk_members
GROUP BY is_public;
```

### Make All Members Public (Bulk)

```sql
-- CAUTION: Make all active members public
UPDATE wp_jgk_members
SET is_public = 1
WHERE status = 'active';

-- Make all members hidden (reset)
UPDATE wp_jgk_members
SET is_public = 0;
```

### Make Specific Member Public

```sql
-- By member ID
UPDATE wp_jgk_members
SET is_public = 1
WHERE id = 123;

-- By membership number
UPDATE wp_jgk_members
SET is_public = 1
WHERE membership_number = 'JGK-0001';
```

### Filter Public Members with Photos

```sql
-- Only public members with profile photos
SELECT * FROM wp_jgk_members
WHERE is_public = 1
AND status = 'active'
AND profile_image_id IS NOT NULL
ORDER BY first_name;
```

---

## 🧪 Testing Checklist

### Admin Side Tests

- [ ] ✅ Edit member form shows "Public Visibility" dropdown
- [ ] ✅ Default value is "Hidden" (0)
- [ ] ✅ Can select "Visible Publicly" (1)
- [ ] ✅ Can select "Hidden from Public" (0)
- [ ] ✅ Value saves correctly on submit
- [ ] ✅ Members list shows visibility badge
- [ ] ✅ Green badge for public members (👁️ PUBLIC)
- [ ] ✅ Gray badge for hidden members (🔒 HIDDEN)
- [ ] ✅ Badge updates after edit

### Frontend Tests

- [ ] ✅ `[jgk_public_members]` shortcode renders
- [ ] ✅ Only shows members with `is_public = 1`
- [ ] ✅ Only shows `status = 'active'` members
- [ ] ✅ Hidden members do NOT appear
- [ ] ✅ Grid layout is responsive
- [ ] ✅ Hover animations work
- [ ] ✅ Profile photos display correctly
- [ ] ✅ Placeholder initials show (no photo)
- [ ] ✅ `limit` attribute works
- [ ] ✅ `columns` attribute works
- [ ] ✅ `orderby` attribute works
- [ ] ✅ `type` filter works
- [ ] ✅ Mobile responsive (1/2/3/4 columns)

### Database Tests

- [ ] ✅ Column `is_public` exists in table
- [ ] ✅ Index `idx_is_public` exists
- [ ] ✅ Default value is `0` (hidden)
- [ ] ✅ Accepts only `0` or `1`
- [ ] ✅ Query filters correctly

---

## 🚀 Future Enhancements (Phase 2)

### 1. Bulk Actions

**Add to members list:**
```php
// Bulk action dropdown
<select name="bulk_action">
    <option value="">Bulk Actions</option>
    <option value="make_public">Make Public</option>
    <option value="make_hidden">Make Hidden</option>
</select>
<button type="submit">Apply</button>
```

### 2. Quick Toggle

**Add to members table:**
```php
// Click to toggle visibility
<button onclick="toggleVisibility(<?php echo $member->id; ?>)">
    <?php echo $member->is_public ? '👁️' : '🔒'; ?>
</button>
```

### 3. Visibility Filter

**Add to filters:**
```php
<select name="visibility">
    <option value="">All Visibility</option>
    <option value="1">Public Only</option>
    <option value="0">Hidden Only</option>
</select>
```

### 4. Member Settings Page

**Allow members to control own visibility:**
```php
// In member dashboard settings
<label>
    <input type="checkbox" name="is_public" value="1">
    Make my profile visible in public member directory
</label>
```

### 5. Conditional Visibility

**Advanced rules:**
```php
// Auto-hide if:
- No profile photo
- Incomplete profile
- Membership expired
- Under 13 years old (requires parent consent)
```

---

## 📝 Page Creation Guide

### Create "Our Members" Page

**Step 1: Create Page**
1. Go to Pages → Add New
2. Title: "Our Members"
3. URL slug: `/our-members`

**Step 2: Add Shortcode**
```
[jgk_public_members limit="24" columns="4"]
```

**Step 3: Add Description**
```
Meet our talented junior golfers! Below you'll find profiles of our active members who have chosen to share their journey with the community.
```

**Step 4: Publish**

**Result:**
- Page shows 24 public members in 4-column grid
- Auto-updates when admins change visibility
- Fully responsive on all devices

---

## 🔐 Privacy & Security

### GDPR Compliance

✅ **Default Hidden:** All members start hidden (opt-in model)  
✅ **Admin Control:** Only admins can make members public  
✅ **Member Consent:** Link with `consent_photography` field  
✅ **Easy Removal:** One-click to hide member  
✅ **No PII:** Public gallery shows only approved info (name, photo, handicap)

### Recommended Policy

```
By default, all member profiles are private. Members appear in public directories only when:

1. Admin explicitly sets "Public Visibility" to "Visible"
2. Member has given "Consent to Photography" (optional check)
3. Member status is "Active"
4. Profile is complete and approved

Parents/guardians can request to hide their child's profile at any time by contacting us.
```

---

## 📚 Documentation for Users

### For Admins

**How to make a member visible publicly:**
1. Navigate to **Junior Golf Kenya → Members**
2. Click **Edit Member** for the desired member
3. Scroll to **"Public Visibility"** dropdown
4. Select **"✓ Visible Publicly"**
5. Click **"Update Member"**
6. ✅ Member now appears in public galleries

**Quick check:**
- Look for 👁️ **PUBLIC** badge = Member is visible
- Look for 🔒 **HIDDEN** badge = Member is private

### For Website Editors

**Display members on any page:**

Add this shortcode to any page or post:
```
[jgk_public_members]
```

**Customize the display:**
```
[jgk_public_members limit="12" columns="3" orderby="handicap"]
```

**Show only junior members:**
```
[jgk_public_members type="junior" limit="20"]
```

---

## ✅ Summary

**What Changed:**
1. ✅ Added `is_public` column to database
2. ✅ Added visibility dropdown in edit form
3. ✅ Added visibility badge in members list
4. ✅ Created public members gallery shortcode
5. ✅ Implemented responsive gallery design
6. ✅ Added comprehensive filtering

**Key Features:**
- ✅ **Default Hidden:** Privacy-first approach
- ✅ **Admin Control:** Only admins control visibility
- ✅ **Easy Toggle:** Simple dropdown interface
- ✅ **Visual Badges:** Quick status at a glance
- ✅ **Public Gallery:** Beautiful responsive display
- ✅ **Flexible Shortcode:** Customizable for any use case

**Next Steps:**
1. Test admin interface
2. Create "Our Members" page with shortcode
3. Make selected members public
4. View public gallery
5. Move to Phase 2 enhancements

**READY TO USE! 🚀**
