 # ARMember Data Import Guide

## Overview

This feature allows administrators to import member data from ARMember plugin into the Junior Golf Kenya system. The import process is designed to be safe, flexible, and auditable.

## Prerequisites

1. **ARMember Plugin**: Must be installed and activated on the same WordPress site
2. **Admin Access**: User must have `manage_options` capability
3. **Database**: Both ARMember and JGK database tables must exist

## Accessing the Import Tool

Navigate to: **WordPress Admin → JuniorGolfKenya → Import Data**

## Import Process

### Step 1: Review ARMember Data
- The page will automatically detect if ARMember is active
- Shows the total count of ARMember members available for import

### Step 2: Configure Import Options

#### Skip Existing Members (Recommended)
- **Enabled**: Members already in JGK will not be imported again
- **Disabled**: Will attempt to process all ARMember members
- **Use case**: Prevents duplicates on subsequent imports

#### Update Existing Members
- **Enabled**: Updates existing JGK members with ARMember data
- **Disabled**: Leaves existing members unchanged
- **Note**: Only updates empty fields (phone, DOB, gender)
- **Mutually exclusive with "Skip Existing"**

#### Force Junior Membership Type
- **Enabled**: All imported members get "junior" type regardless of age
- **Disabled**: Only members aged 2-17 are imported as juniors
- **Recommendation**: Keep enabled for Junior Golf program

#### Default Status
- Choose the default status for imported members:
  - **Active**: Member can access all features
  - **Pending**: Requires manual approval
  - **Suspended**: Account is temporarily disabled

### Step 3: Preview Import (Optional but Recommended)

Click **"Preview Import"** to see:
- First 10 members that will be processed
- Their current data (name, email, phone, age, gender)
- Whether they already exist in JGK
- Whether they will be imported or skipped

**Preview Indicators:**
- ✓ **Will import**: Member will be added to JGK
- ⚠️ **Already exists**: Member is already in JGK database
- ✗ **Will skip (age)**: Member doesn't meet age requirements (2-17)

### Step 4: Start Import

Click **"Start Import"** button:
- A confirmation dialog will appear
- The import will process all ARMember members in batches
- Progress is automatic (50 members per batch)

## Data Mapping

### From ARMember to JGK

| ARMember Field | JGK Field | Notes |
|----------------|-----------|-------|
| `user_id` | `user_id` | WordPress user ID |
| `user_email` | From WordPress users table | Email address |
| `display_name` | From WordPress users table | Full display name |
| User meta: `first_name` | User meta: `first_name` | First name |
| User meta: `last_name` | User meta: `last_name` | Last name |
| User meta: `phone` | `phone` | Contact number |
| User meta: `date_of_birth` | `date_of_birth` | Birth date (YYYY-MM-DD) |
| User meta: `gender` | `gender` | Gender (male/female) |
| `arm_primary_status` | `status` | Mapped to JGK status |

### Status Mapping

| ARMember Status | Code | JGK Status |
|-----------------|------|------------|
| Active | 1 | active |
| Inactive | 2 | suspended |
| Pending | 3 | pending |
| Terminated | 4 | suspended |
| Expired | 5 | expired |

## Import Results

After import completion, you'll see statistics:

- **Imported**: New members added to JGK
- **Updated**: Existing members that were updated
- **Skipped**: Members not imported (already exist or don't meet criteria)
- **Errors**: Members that failed to import with error messages

## Age Validation

The system automatically validates member ages:

### Validation Rules:
1. **Valid Junior Age**: 2-17 years old
2. **Too Young**: Under 2 years (rejected)
3. **Too Old**: 18+ years (rejected unless force junior type enabled)
4. **No Date of Birth**: Can still import if force junior type enabled

### Age Calculation:
- Calculated from `date_of_birth` field
- Uses current date for comparison
- Displayed in preview table

## Safety Features

### Non-Destructive Operation
- Does NOT modify WordPress user accounts
- Does NOT delete any ARMember data
- Only creates new JGK member records

### Duplicate Prevention
- Checks for existing user_id in JGK database
- "Skip Existing" option prevents re-import
- Safe to run multiple times

### Audit Trail
- All imports logged in `wp_jgk_audit_log` table
- Records action: `member_imported_from_armember`
- Stores source data and timestamp
- Includes admin user who performed import

## Troubleshooting

### "ARMember plugin is not active"
**Solution**: Install and activate ARMember plugin

### "No ARMember Data Found"
**Possible causes**:
- ARMember has no registered members
- Database table doesn't exist
- Database prefix mismatch

**Solution**: Verify ARMember installation and member data

### "No members were imported"
**Possible causes**:
- All members already exist (with skip_existing enabled)
- All members fail age validation
- Database permission issues

**Solution**: 
- Review import options
- Check preview to see why members are skipped
- Verify member ages in ARMember

### Import Errors
**If you see specific error messages**:
- Click "View detailed messages" under import results
- Each error shows User ID and specific problem
- Common issues: invalid date formats, missing required data

## Best Practices

1. **Always Preview First**: Use preview to verify data before importing
2. **Backup Database**: Create database backup before large imports
3. **Test with Small Batch**: Import a few members first to verify setup
4. **Review Age Data**: Ensure DOB fields are populated in ARMember
5. **Check Results**: Review import statistics and detailed messages
6. **Verify in Members Page**: Navigate to JGK Members to confirm import

## Technical Details

### Database Tables Used

**ARMember Tables:**
- `wp_arm_members`: Main ARMember data
- `wp_arm_membership_setup`: Membership plans
- `wp_arm_payment_log`: Payment history
- `wp_arm_members_activity`: Member activities

**JGK Tables:**
- `wp_jgk_members`: Junior Golf Kenya members
- `wp_jgk_audit_log`: Import audit trail

### Performance

- **Batch Processing**: 50 members per batch
- **Memory Efficient**: Processes in chunks to avoid timeouts
- **No Time Limit**: PHP time limit automatically handled for large imports

### Data Validation

All imported data is validated:
- Email format validation
- Date format validation (YYYY-MM-DD)
- Age calculation accuracy
- Status code mapping

## Post-Import Actions

After successful import:

1. **Review Members**: Go to JGK Members page
2. **Verify Data**: Check imported member details
3. **Update Missing Data**: Edit members to add missing information
4. **Assign Coaches**: Assign coaches to new members if needed
5. **Set Expiry Dates**: Configure membership expiry dates

## Support

For issues or questions about the import feature:
- Check WordPress debug log
- Review JGK audit log
- Contact plugin support with error messages

---

**Version**: 1.0.0  
**Last Updated**: October 2025
