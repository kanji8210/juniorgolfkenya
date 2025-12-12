# Junior Golf Kenya Plugin - User Handbook

## Table of Contents
1. [Overview](#overview)
2. [Plugin Features](#plugin-features)
3. [User Roles](#user-roles)
4. [Installation & Setup](#installation--setup)
5. [Payment Configuration](#payment-configuration)
6. [User Journeys](#user-journeys)
7. [Dashboard Features](#dashboard-features)
8. [Admin Functions](#admin-functions)
9. [Troubleshooting](#troubleshooting)

---

## Overview

**Junior Golf Kenya** is a comprehensive WordPress plugin designed to manage junior golf memberships, coaching, payments, and parent-child relationships. The plugin integrates seamlessly with WooCommerce for payment processing and provides role-based dashboards for administrators, coaches, parents, and members.

### Key Capabilities
- Multi-step member registration with document uploads
- Parent-child relationship management
- Role-based dashboards (Admin, Coach, Parent, Member)
- WooCommerce payment integration
- Membership verification system
- Coach application and approval workflow
- Audit trail and reporting

---

## Plugin Features

### 1. Custom Authentication System
- **Custom Login Page**: Replace WordPress default login with branded login form
- **Role-Based Redirects**: Automatic routing to appropriate dashboard after login
- **Parent Detection**: Identifies parents by email association with members
- **Logged-in State Detection**: Shows dashboard links when users are already authenticated

### 2. Multi-Step Registration
The registration form includes 5 comprehensive steps:
1. **Basic Information**: Name, date of birth, gender, nationality
2. **Contact Details**: Child's email (optional - can use parent's email), phone, address
3. **Guardian Information**: Parent/guardian details (email required)
4. **Membership Selection**: Choose membership type
5. **Document Upload**: Profile photo and birth certificate (required)

**Important Notes:**
- **Child Email is Optional**: If a child doesn't have an email, the system will automatically generate a unique email based on the parent's email
- **Multiple Children Per Family**: Parents can register multiple children using the same parent email
- **Parent Email Required**: The parent's email is mandatory and used for dashboard access to manage all children

### 3. Member Management
- Create and edit member profiles
- Upload and manage profile photos
- Track membership status
- View payment history
- Manage multiple children (for parents)

### 4. Payment Processing
- **WooCommerce Integration**: Uses WooCommerce product keys for membership fees
- **Multiple Payment Gateways**: Supports client's configured payment methods including:
  - M-Pesa
  - eLipa
  - Stripe
  - Any other WooCommerce-enabled gateway
- **Payment Tracking**: Complete audit trail of all transactions
- **Automated Status Updates**: Membership status updates upon successful payment

### 5. Dashboard System
Each user role has a dedicated dashboard:
- **Admin Dashboard**: Full system management
- **Coach Dashboard**: View assigned members, submit reports
- **Parent Dashboard**: Manage multiple children, view payment status
- **Member Dashboard**: View profile, membership status, payment history

---

## User Roles

### 1. Administrator
**Capabilities:**
- Full plugin management
- Approve/reject coach applications
- Manage all members
- View all payments and reports
- Import members from ARMember
- System settings configuration
- Generate reports and analytics
- Manually adjust member status if needed

**Access Level:** Complete system control

**Note:** Member registration no longer requires admin approval. Members are auto-approved and directed to payment immediately after registration.

### 2. Coach (jgk_coach)
**Capabilities:**
- View assigned members
- Submit coaching reports
- Update member progress
- View member profiles
- Access coach-specific dashboard

**Access Level:** Limited to coaching functions

### 3. Parent
**Capabilities:**
- Register children as members
- Manage multiple children profiles
- View all children's membership status
- Track payment history for all children
- Update children's information
- Upload documents

**Access Level:** Limited to their children's data

**Parent Detection:** Parents are identified by having their email linked to one or more members in the `jgk_parents_guardians` table.

### 4. Member (jgk_member/jgk_junior)
**Capabilities:**
- View own profile
- View membership status
- View payment history
- Update personal information
- Upload profile photo

**Access Level:** Limited to own data only

---

## Installation & Setup

### Prerequisites
- WordPress 5.0 or higher
- PHP 7.4 or higher
- WooCommerce plugin installed and activated
- MySQL database

### Installation Steps

1. **Upload Plugin**
   ```
   Upload the juniorgolfkenya folder to /wp-content/plugins/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Junior Golf Kenya"
   - Click "Activate"

3. **Database Setup**
   - Plugin automatically creates required tables on activation:
     - `jgk_members`
     - `jgk_parents_guardians`
     - `jgk_payments`
     - `jgk_audit_log`
     - `jgk_member_roles`

4. **Create Required Pages**
   Create WordPress pages with these shortcodes:
   
   ```
   [jgk_login_form]           → Login page
   [jgk_registration_form]    → Registration page
   [jgk_member_dashboard]     → Member dashboard
   [jgk_parent_dashboard]     → Parent dashboard
   [jgk_coach_dashboard]      → Coach dashboard
   [jgk_verification_widget]  → Verification widget
   [jgk_public_members]       → Public member listing
   [jgk_coach_request_form]   → Coach application form
   ```

5. **Configure Settings**
   - Navigate to Admin Dashboard → Junior Golf Kenya → Settings
   - Configure membership types
   - Set up notification emails
   - Configure payment options

---

## Payment Configuration

### WooCommerce Integration

The plugin uses **WooCommerce product keys** to process membership payments. This allows you to leverage your existing WooCommerce payment gateway configuration.

#### Setup Process

1. **Create WooCommerce Products**
   - Go to WooCommerce → Products → Add New
   - Create products for each membership type:
     - Junior Membership (e.g., $50)
     - Premium Junior Membership (e.g., $100)
     - Coach Membership (e.g., $75)

2. **Link Products to Plugin**
   - Go to Junior Golf Kenya → Settings → Payment Settings
   - Map each membership type to its WooCommerce product
   - Save configuration

3. **Configure Payment Gateways**
   - Go to WooCommerce → Settings → Payments
   - Enable and configure your preferred payment methods:
     - **M-Pesa**: Configure M-Pesa gateway settings
     - **eLipa**: Enter API credentials
     - **Stripe**: Add Stripe keys
     - **PayPal**: Configure PayPal settings
     - Any other installed gateway

4. **Payment Flow**
   ```
   Member Registration → Registration Form Completed → 
   Account Auto-Approved → Redirected to Checkout → 
   Membership Product Added to Cart → 
   Payment Gateway Selection → Process Payment → 
   Membership Activated
   ```

### Supported Payment Gateways

The plugin supports **any payment gateway configured in WooCommerce**, including:

- **M-Pesa** (Mobile money for Kenya)
- **eLipa** (East African payment solution)
- **Stripe** (Credit/debit cards)
- **PayPal** (International payments)
- **Bank Transfer**
- **Cash on Delivery** (for in-person payments)
- Any other WooCommerce-compatible gateway

### Payment Tracking

All payments are logged in the `jgk_payments` table with:
- Member ID
- Payment amount
- Payment date
- Payment method
- Transaction ID
- Payment status (pending, completed, failed)
- WooCommerce order ID

---

## User Journeys

### Journey 1: New Member Registration (Parent Registering Child)

**Scenario:** A parent wants to register their child as a junior golf member.

**Steps:**

1. **Access Registration Page**
   - Navigate to the registration page
   - If already logged in, see welcome message with dashboard link
   - If not logged in, see registration form

2. **Complete Step 1: Basic Information**
   - Enter child's first name, last name
   - Select date of birth (calendar picker)
   - Select gender (Male/Female/Other)
   - Enter nationality
   - Click "Next"

3. **Complete Step 2: Contact Details**
   - Enter child's email address (OPTIONAL - leave empty to use parent's email)
   - Enter phone number (optional)
   - Enter physical address
   - Enter city
   - Enter postal code
   - Click "Next"
   
   **Note:** If you have multiple children without individual email addresses, you can leave the child email blank. The system will automatically create a unique account using the parent's email.

4. **Complete Step 3: Guardian Information**
   - Enter parent/guardian first name
   - Enter parent/guardian last name
   - Enter parent email (REQUIRED - used for parent dashboard access)
   - Enter parent phone number (REQUIRED)
   - Select relationship (Mother/Father/Guardian)
   - Click "Next"
   
   **Important:** The parent email is used to access the parent dashboard where you can manage all your children's memberships.

5. **Complete Step 4: Membership Selection**
   - View available membership types
   - Select desired membership
   - View pricing and benefits
   - Click "Next"

6. **Complete Step 5: Document Upload**
   - Upload profile photo (JPG, PNG, max 5MB)
   - Upload birth certificate (PDF, JPG, PNG, max 10MB) - **Required**
   - Review all entered information
   - Click "Submit Registration"

7. **Payment Processing**
   - Redirected to WooCommerce checkout
   - Membership product automatically added to cart
   - Select payment method (M-Pesa, eLipa, Stripe, etc.)
   - Complete payment
   - Receive confirmation email
   - Membership automatically activated

8. **Account Creation**
   - System creates member account
   - Member is auto-approved (no admin approval needed)
   - System creates parent account (if email doesn't exist)
   - Links parent to child in database
   - Parent receives login credentials

9. **Post-Payment**
   - Member/Parent logs in using custom login page
   - Automatically redirected to appropriate dashboard
   - Can view child's profile and active membership status

---

### Journey 2: Parent Managing Multiple Children

**Scenario:** A parent has already registered one child and wants to add a second child.

**Steps:**

1. **Login**
   - Navigate to custom login page
   - Enter email and password
   - Click "Login"
   - System detects parent role
   - Redirected to Parent Dashboard

2. **View Parent Dashboard**
   - See welcome message with parent name
   - View list of all registered children
   - See each child's:
     - Name and age
     - Membership status
     - Profile photo
     - Last payment date
     - "View Details" and "Make Payment" buttons

3. **Register Another Child**
   - Click "Register New Child" button
   - Redirected to registration form
   - Complete all 5 steps as before
   - Parent email auto-fills from logged-in session
   - Submit and complete payment

4. **Manage Children**
   - Return to Parent Dashboard
   - View both children
   - Click "View Details" to see full profile
   - Click "Make Payment" to renew membership
   - View payment history for each child

5. **Update Information**
   - Click on child's name
   - Edit contact information
   - Upload new profile photo
   - Update emergency contacts
   - Save changes

---

### Journey 3: Coach Application and Approval

**Scenario:** A qualified golf coach wants to join the program.

**Steps:**

1. **Submit Coach Application**
   - Navigate to coach application page
   - Complete coach request form:
     - Personal information
     - Coaching qualifications
     - Experience details
     - References
     - Upload coaching certificates
   - Submit application

2. **Admin Review**
   - Admin receives notification
   - Admin navigates to Admin Dashboard → Role Requests
   - Views coach application with all details
   - Reviews qualifications and documents
   - Decision:
     - **Approve**: Click "Approve"
     - **Reject**: Click "Reject" with reason

3. **Approval Process**
   - System creates WordPress user account
   - Assigns `jgk_coach` role
   - Sends approval email with login credentials
   - Logs action in audit trail

4. **Coach Login**
   - Coach receives email
   - Navigates to custom login page
   - Enters credentials
   - System detects coach role
   - Redirected to Coach Dashboard

5. **Coach Dashboard Access**
   - View assigned members
   - See member progress
   - Submit coaching reports
   - Update member training records
   - View coaching schedule

---

### Journey 4: Member Viewing Profile and Payment History

**Scenario:** A junior member wants to check their membership status and payment history.

**Steps:**

1. **Login**
   - Navigate to custom login page
   - Enter email and password
   - Click "Login"
   - System detects member role
   - Redirected to Member Dashboard

2. **View Dashboard**
   - See welcome message with member name
   - View membership status:
     - Active/Expired/Pending
     - Membership type
     - Expiry date
   - View profile information
   - See profile photo

3. **View Payment History**
   - Click "Payment History" tab
   - See table of all payments:
     - Date
     - Amount
     - Payment method
     - Status (Completed/Pending/Failed)
     - Transaction ID
   - Download payment receipts

4. **Update Profile**
   - Click "Edit Profile" button
   - Update contact information
   - Change profile photo
   - Update password
   - Save changes

5. **Renew Membership**
   - See expiry warning if membership near expiration
   - Click "Renew Membership" button
   - Redirected to WooCommerce checkout
   - Select payment method
   - Complete payment
   - Membership automatically extended

---

### Journey 5: Admin Managing System

**Scenario:** Administrator performs daily system management tasks.

**Steps:**

1. **Login**
   - Navigate to custom login page or wp-admin
   - Enter admin credentials
   - Redirected to WordPress Admin Dashboard

2. **Access Plugin Dashboard**
   - Navigate to Junior Golf Kenya menu
   - Access various admin sections:
     - Dashboard (overview)
     - Members
     - Coaches
     - Payments
     - Reports
     - Role Requests
     - Settings

3. **Manage Members**
   - Click "Members" menu
   - View all registered members in table:
     - ID, Name, Email, Status, Membership Type
   - Search/filter members
   - Click "Edit" to modify member details
   - Click "Delete" to remove member (with confirmation)
   - View member's payment history
   - Manually update membership status

4. **Review Payments**
   - Click "Payments" menu
   - View payment transactions table
   - Filter by:
     - Date range
     - Payment status
     - Payment method
     - Member name
   - Export payments report (CSV)
   - View failed payments for follow-up

5. **Approve Coach Applications**
   - Click "Role Requests" menu
   - View pending coach applications
   - Click application to see full details
   - Review qualifications and documents
   - Click "Approve" or "Reject"
   - System sends automated notification

6. **Generate Reports**
   - Click "Reports" menu
   - Select report type:
     - Member registration trends
     - Payment summary
     - Coach performance
     - Membership renewals
   - Select date range
   - Click "Generate Report"
   - Export as PDF or CSV

7. **Configure Settings**
   - Click "Settings" menu
   - Configure:
     - Membership types and prices
     - Email templates
     - Payment gateway mappings
     - Registration form fields
     - System notifications
   - Save configuration

8. **Import Members**
   - Click "Import" menu
   - Select source (ARMember plugin)
   - Map fields to plugin structure
   - Preview import
   - Execute import
   - Review import log

---

## Dashboard Features

### Admin Dashboard
**Location:** WordPress Admin → Junior Golf Kenya

**Features:**
- **Overview Statistics**
  - Total members
  - Active memberships
  - Pending payments
  - Total revenue
  - Recent registrations

- **Quick Actions**
  - Add new member
  - Approve coach requests
  - Generate reports
  - View audit log

- **Data Tables**
  - All members with search/filter
  - Recent payments
  - Pending approvals
  - System notifications

### Coach Dashboard
**Location:** Custom page with `[jgk_coach_dashboard]` shortcode

**Features:**
- **Assigned Members**
  - List of members under coaching
  - Member progress tracking
  - Training session records

- **Coaching Tools**
  - Submit progress reports
  - Schedule training sessions
  - View member performance metrics

- **Profile Management**
  - Update coach information
  - Upload new certificates
  - View coaching history

### Parent Dashboard
**Location:** Custom page with `[jgk_parent_dashboard]` shortcode

**Features:**
- **Children Overview**
  - Card-based display of all children
  - Each card shows:
    - Child's name and age
    - Profile photo
    - Membership status (Active/Expired/Pending)
    - Membership type
    - Last payment date
    - Quick action buttons

- **Actions Per Child**
  - "View Details" - Full profile
  - "Make Payment" - Renew membership
  - "Edit Information" - Update details
  - "Upload Documents" - Add/update files

- **Family Summary**
  - Total children registered
  - Total active memberships
  - Total payments this year
  - Upcoming renewals

- **Bulk Actions**
  - Renew all memberships at once
  - Update emergency contact for all children
  - View combined payment history

### Member Dashboard
**Location:** Custom page with `[jgk_member_dashboard]` shortcode

**Features:**
- **Profile Section**
  - Personal information display
  - Profile photo
  - Edit profile button
  - Change password option

- **Membership Status**
  - Current status indicator
  - Membership type
  - Registration date
  - Expiry date
  - Renewal button

- **Payment History**
  - Complete transaction list
  - Payment receipts
  - Download invoices

- **Documents**
  - View uploaded birth certificate
  - Update profile photo
  - Download membership card

---

## Admin Functions

### Member Management
**Path:** Admin → Junior Golf Kenya → Members

**Actions:**
- **Add Member**: Manually create member account
- **Edit Member**: Modify all member details
- **Delete Member**: Remove member (soft delete with audit trail)
- **Bulk Actions**: Export, email, status update
- **Search/Filter**: By name, email, status, membership type

### Payment Management
**Path:** Admin → Junior Golf Kenya → Payments

**Actions:**
- **View Payments**: Complete transaction history
- **Manual Payment**: Record offline payment
- **Refund**: Process payment refund
- **Export**: Generate payment reports (CSV, PDF)
- **Filter**: By date, status, method, member

### Coach Management
**Path:** Admin → Junior Golf Kenya → Coaches

**Actions:**
- **View Coaches**: All approved coaches
- **Edit Coach**: Update coach details
- **Assign Members**: Link members to coach
- **Deactivate Coach**: Remove coach access
- **View Reports**: Coach-submitted reports

### Role Requests
**Path:** Admin → Junior Golf Kenya → Role Requests

**Actions:**
- **View Applications**: Pending coach requests
- **Review Details**: Full application review
- **Approve**: Create coach account
- **Reject**: Deny with reason notification
- **Audit**: View approval history

### Reports
**Path:** Admin → Junior Golf Kenya → Reports

**Available Reports:**
- **Member Registration Report**
  - Total registrations by month
  - Demographics breakdown
  - Membership type distribution
  
- **Payment Report**
  - Revenue by period
  - Payment method breakdown
  - Failed payments analysis
  
- **Coach Performance Report**
  - Members per coach
  - Session completion rates
  - Coach ratings
  
- **Membership Renewal Report**
  - Upcoming renewals
  - Renewal rates
  - Lapsed memberships

### Import Tool
**Path:** Admin → Junior Golf Kenya → Import

**Features:**
- Import from ARMember plugin
- CSV import capability
- Field mapping interface
- Duplicate detection
- Import preview
- Error handling and logging

### Settings
**Path:** Admin → Junior Golf Kenya → Settings

**Configuration Options:**
- **General Settings**
  - Site information
  - Contact details
  - Default membership type
  
- **Membership Settings**
  - Membership types and prices
  - Membership duration
  - Renewal reminders
  
- **Payment Settings**
  - WooCommerce product mapping
  - Currency settings
  - Payment confirmation emails
  
- **Email Settings**
  - Email templates
  - SMTP configuration
  - Notification preferences
  
- **Registration Settings**
  - Required fields
  - Document upload limits
  - Age restrictions
  
- **Coach Settings**
  - Application requirements
  - Approval workflow
  - Coach capabilities

---

## Troubleshooting

### Common Issues and Solutions

#### 1. Login Issues

**Problem:** User cannot login

**Solutions:**
- Verify email and password are correct
- Check if user account exists in WordPress
- Ensure user has correct role assigned
- Clear browser cache and cookies
- Check if custom login page is properly configured

**Problem:** Wrong dashboard redirect after login

**Solutions:**
- Verify user role is correctly assigned
- Check if parent email is linked in database
- Review redirect logic in login form
- Clear WordPress transients

#### 2. Registration Issues

**Problem:** Form stuck on step 1

**Solutions:**
- Verify JavaScript is loaded correctly
- Check browser console for errors
- Ensure form class names match (`jgk-progress-step`)
- Clear browser cache

**Problem:** Birth certificate upload fails

**Solutions:**
- Check file size (must be under 10MB)
- Verify file format (PDF, JPG, PNG only)
- Ensure upload directory has write permissions
- Increase PHP `upload_max_filesize` and `post_max_size`

**Problem:** Registration completes but account not created

**Solutions:**
- Check email doesn't already exist
- Review PHP error logs
- Verify database tables exist
- Check WordPress user creation permissions
- Verify WooCommerce is active for payment redirect

#### 3. Payment Issues

**Problem:** Payment not processing

**Solutions:**
- Verify WooCommerce is active
- Check payment gateway configuration
- Ensure product is linked correctly
- Review WooCommerce status page
- Check payment gateway credentials

**Problem:** Payment completed but membership not activated

**Solutions:**
- Check WooCommerce order status
- Verify webhook/IPN is configured
- Review payment callback logs
- Manually update membership status in admin

#### 4. Parent Dashboard Issues

**Problem:** Parent cannot see children

**Solutions:**
- Verify parent email matches in `jgk_parents_guardians` table
- Check database relationship is established
- Ensure children have correct member status
- Review parent detection logic

**Problem:** Parent dashboard shows wrong children

**Solutions:**
- Check email uniqueness in parent records
- Verify parent-child linking in database
- Review data integrity in `jgk_parents_guardians`

#### 5. File Upload Issues

**Problem:** Profile photo or birth certificate won't upload

**Solutions:**
- Check PHP upload limits:
  ```php
  upload_max_filesize = 10M
  post_max_size = 10M
  max_execution_time = 300
  ```
- Verify WordPress upload directory permissions (755)
- Check file MIME type validation
- Review `wp-content/uploads/juniorgolfkenya/` directory

#### 6. Database Issues

**Problem:** Plugin tables not created

**Solutions:**
- Deactivate and reactivate plugin
- Check MySQL user permissions
- Review activation error logs
- Manually run table creation SQL

**Problem:** Data not saving

**Solutions:**
- Check database connection
- Verify table structure
- Review WordPress debug log
- Check for SQL errors in PHP error log

#### 7. Coach Dashboard Issues

**Problem:** Coach cannot access dashboard

**Solutions:**
- Verify user has `jgk_coach` role
- Check role capabilities
- Ensure coach approval completed
- Review user meta data

**Problem:** Coach cannot see assigned members

**Solutions:**
- Verify member-coach assignment in database
- Check coach ID matches
- Review assignment query logic

---

## Technical Support

### Debug Mode

Enable WordPress debug mode to troubleshoot issues:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `wp-content/debug.log`

### Database Verification

Run database tests to verify table integrity:

```php
// Navigate to: /wp-content/plugins/juniorgolfkenya/tests/
// Run: verify_all_tables.php
```

### Audit Trail

Review all system actions in the audit log:
- Admin → Junior Golf Kenya → Audit Log
- Filter by date, action type, user
- Export for analysis

### System Requirements Check

Minimum requirements:
- WordPress: 5.0+
- PHP: 7.4+
- MySQL: 5.6+
- WooCommerce: 4.0+
- Memory Limit: 256MB
- Upload Limit: 10MB

---

## Best Practices

### For Administrators

1. **Regular Backups**
   - Backup database weekly
   - Include uploaded documents
   - Test restore procedure

2. **Security**
   - Keep WordPress and plugins updated
   - Use strong passwords
   - Enable two-factor authentication
   - Regular security audits

3. **Member Management**
   - Review pending payments weekly
   - Process coach applications promptly
   - Send renewal reminders
   - Maintain clean member data

4. **Reporting**
   - Generate monthly reports
   - Track membership trends
   - Monitor payment success rates
   - Review coach performance

### For Parents

1. **Profile Maintenance**
   - Keep contact information updated
   - Upload current photos
   - Update emergency contacts
   - Renew memberships before expiry

2. **Payment Management**
   - Set renewal reminders
   - Keep payment methods updated
   - Save payment receipts
   - Monitor payment status

3. **Multi-Child Management**
   - Use consistent email across children
   - Maintain individual profiles
   - Track each child's progress
   - Coordinate renewals

### For Coaches

1. **Member Engagement**
   - Submit regular progress reports
   - Maintain session records
   - Update member training plans
   - Communicate with parents

2. **Profile Management**
   - Keep qualifications updated
   - Upload new certificates
   - Update availability
   - Maintain professional profile

---

## Frequently Asked Questions (FAQ)

**Q: Can I register multiple children with the same email address?**
A: Yes! You can register multiple children using your parent email. The child's email is optional - if you leave it blank, the system will automatically create a unique account based on your parent email. This allows you to manage all your children from one parent dashboard.

**Q: My children don't have email addresses. Can they still register?**
A: Absolutely! The child email is optional. Simply leave the child email field blank during registration, and provide your parent email. The system will generate unique accounts for each child automatically.

**Q: How do I access my children's accounts if they don't have email?**
A: You access all your children's information through the Parent Dashboard using your parent email. You don't need to log in to individual child accounts - everything is managed from your single parent account.

**Q: Can a parent manage children with different membership types?**
A: Yes, each child can have a different membership type, and parents can manage all types from one dashboard.

**Q: What happens after I complete the registration form?**
A: You will be automatically redirected to the payment page to complete your membership payment. Your account is immediately approved - no waiting for admin approval.

**Q: Do I need admin approval before I can pay?**
A: No, the registration process has been streamlined. After completing the registration form, you are automatically approved and redirected to payment.

**Q: What happens if a payment fails?**
A: The system logs the failed payment, sends notification to admin and parent, and provides retry option.

**Q: Can members pay via mobile money?**
A: Yes, if M-Pesa or other mobile payment gateways are configured in WooCommerce.

**Q: How long are memberships valid?**
A: Membership duration is configurable in settings (typically 1 year from payment date).

**Q: Can coaches see all members?**
A: No, coaches only see members specifically assigned to them by administrators.

**Q: What file formats are accepted for birth certificates?**
A: PDF, JPG, and PNG files up to 10MB in size.

**Q: Can parents register children of different ages?**
A: Yes, the system supports juniors of all ages. Age restrictions can be configured in settings.

**Q: Is there a bulk registration option for families?**
A: Parents can register multiple children sequentially. Bulk payments can be processed for all children.

**Q: What payment methods are supported?**
A: All payment methods configured in WooCommerce, including M-Pesa, eLipa, Stripe, PayPal, etc.

**Q: Can membership be paused or refunded?**
A: Administrators can manually adjust membership status and process refunds through the admin panel.

---

## Version History

**Version 1.0.0**
- Initial release
- Member registration system
- Parent-child relationship management
- WooCommerce payment integration
- Role-based dashboards
- Custom login system
- Coach application workflow
- Admin management tools

**Version 1.1.0**
- Streamlined registration process
- Auto-approval of member registration
- Direct redirect to payment after registration
- Removed admin approval requirement for new members
- Improved payment flow for better user experience

**Version 1.2.0**
- Child email now optional during registration
- Multiple children can be registered using parent's email
- Automatic unique email generation for children without email
- Enhanced parent dashboard for multi-child management
- Parent email now required and used as primary family identifier

**Version 1.2.1**
- Removed "pending approval" stage from member dashboard
- Members see dashboard immediately after registration
- Direct payment access for approved members
- Updated parent dashboard to show "Payment Required" instead of "Awaiting Admin Approval"
- Streamlined user experience: Register → Pay → Access Dashboard

---

## Support & Contact

For technical support or questions:
- **Plugin Documentation**: See this handbook
- **Admin Dashboard**: Junior Golf Kenya → Support
- **Issue Reporting**: Contact system administrator
- **Feature Requests**: Submit through admin panel
---

**Last Updated:** December 12, 2025  
**Plugin Version:** 1.2.1  
**WordPress Compatibility:** 5.0+  
**WooCommerce Compatibility:** 4.0+ developed for Junior Golf Kenya. All rights reserved.

---

**Last Updated:** December 12, 2025  
**Plugin Version:** 1.1.0  
**WordPress Compatibility:** 5.0+  
**WooCommerce Compatibility:** 4.0+
