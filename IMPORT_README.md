# ARMember Import Feature

## Quick Start

### Access the Import Tool
1. Login to WordPress Admin
2. Go to **JuniorGolfKenya → Import Data**
3. Follow the on-screen instructions

### Basic Import (Recommended)
1. Keep default settings:
   - ✓ Skip Existing Members
   - ✓ Force Junior Membership Type
   - Default Status: Active
2. Click **"Preview Import"** to see what will be imported
3. Click **"Start Import"** to begin

### What Gets Imported
- WordPress user accounts (as references)
- First name, last name, display name
- Email address and phone number
- Date of birth and gender
- ARMember membership status

### Requirements
- ARMember plugin must be active
- Members must be between 2-17 years old (or use Force Junior Type option)
- Admin privileges required

## Features

✅ **Safe**: Doesn't modify WordPress users or ARMember data  
✅ **Smart**: Automatically maps ARMember statuses to JGK statuses  
✅ **Flexible**: Multiple import options for different scenarios  
✅ **Auditable**: All imports logged in audit trail  
✅ **Preview**: See what will be imported before committing  

## Status Mapping

| ARMember | → | JGK |
|----------|---|-----|
| Active (1) | → | active |
| Inactive (2) | → | suspended |
| Pending (3) | → | pending |
| Terminated (4) | → | suspended |
| Expired (5) | → | expired |

## Common Use Cases

### First Time Import
Import all ARMember members into JGK for the first time.

**Settings:**
- ✓ Skip Existing Members
- ✗ Update Existing Members
- ✓ Force Junior Membership Type
- Default Status: Active

### Sync Updated Data
Update existing JGK members with new data from ARMember.

**Settings:**
- ✗ Skip Existing Members
- ✓ Update Existing Members
- ✓ Force Junior Membership Type
- Default Status: Active

### Import Pending Review
Import members but keep them pending for manual review.

**Settings:**
- ✓ Skip Existing Members
- ✗ Update Existing Members
- ✓ Force Junior Membership Type
- Default Status: Pending

## Need Help?

📖 Read the full [Import Guide](IMPORT_GUIDE.md)  
🐛 Check WordPress debug log for errors  
📊 Review import statistics after completion
