# Changelog - Junior Golf Kenya Plugin

All notable changes to this project will be documented in this file.

## [1.2.0] - 2025-12-12

### Changed - Multiple Children Support Without Individual Emails
**Major Update:** Enhanced registration system to allow families to register multiple children using the parent's email address.

#### What Changed:

1. **Optional Child Email**
   - Child email is now optional during registration
   - If no email provided, system generates unique email based on parent email
   - Uses email aliasing format: `parent+child.name.123@domain.com`

2. **Required Parent Email**
   - Parent email is now mandatory (previously optional)
   - Parent email serves as primary family identifier
   - All children linked to parent via email in `jgk_parents_guardians` table

3. **Automatic Email Generation**
   - System automatically creates unique email for children without email
   - Format: parent email + child's name + random suffix
   - Ensures no duplicate emails in WordPress user system

4. **Enhanced Validation**
   - Removed "email already exists" error that blocked families
   - If generated email exists, adds additional random suffix
   - Parent phone now required for emergency contact

#### Technical Changes:

**File: `public/partials/juniorgolfkenya-registration-form.php`**

- Lines 54-62: Made child email optional, added `$use_parent_email` flag
- Lines 95-109: Required parent email validation, automatic email generation logic
- Line 111-114: Removed blocking check for existing emails, added smart suffix generation
- Line 514: Updated form label to indicate child email is optional
- Lines 620-632: Made parent email and phone required fields with clear messaging

#### Benefits:

✅ **Family-Friendly**: Parents can register multiple children without needing individual emails
✅ **Simplified Process**: One parent email manages all children
✅ **No Email Barriers**: Children without email addresses can still register
✅ **Parent Dashboard**: Single login to manage all family members
✅ **Unique Accounts**: Each child gets their own unique WordPress account

#### Use Cases:

**Scenario 1: Family with 3 children, no individual emails**
- Parent registers Child 1: No email → System generates `parent+child1.john.456@email.com`
- Parent registers Child 2: No email → System generates `parent+child2.jane.789@email.com`
- Parent registers Child 3: No email → System generates `parent+child3.mike.123@email.com`
- Parent logs in with `parent@email.com` → Sees all 3 children in dashboard

**Scenario 2: Mixed family (some children have email, some don't)**
- Child 1 (teenager): Has email `teen@email.com` → Uses own email
- Child 2 (younger): No email → System generates from parent email
- Parent logs in → Manages both children from parent dashboard

#### Migration Notes:

- Existing members with email addresses are not affected
- Parent email is now required for all new registrations
- System handles email conflicts automatically

#### Configuration:

No configuration required - changes are automatic.

---

## [1.1.0] - 2025-12-12

### Changed - Streamlined Registration Process
**Major Update:** Removed admin approval requirement for new member registrations to improve user experience and speed up the payment process.

#### What Changed:

1. **Automatic Approval**
   - New members are now automatically approved upon registration
   - Status changed from `'pending'` to `'approved'` immediately
   - No waiting period for admin review

2. **Direct Payment Redirect**
   - After completing registration form, users are redirected directly to WooCommerce checkout
   - Membership product is automatically added to cart
   - Streamlined payment flow reduces friction and dropout

3. **Updated Email Notifications**
   - Member welcome email now mentions immediate payment requirement
   - Admin notification email reflects auto-approval status
   - Clear call-to-action for payment completion

4. **Improved User Flow**
   ```
   OLD FLOW:
   Register → Wait for Admin Approval → Receive Approval Email → 
   Login → Navigate to Payment → Complete Payment

   NEW FLOW:
   Register → Immediate Redirect to Checkout → Complete Payment → Done
   ```

#### Technical Changes:

**File: `public/partials/juniorgolfkenya-registration-form.php`**

- Line 225: Changed member status from `'pending'` to `'approved'`
- Lines 275-283: Updated welcome email to mention payment requirement
- Lines 290-296: Updated admin notification email
- Lines 315-331: Added automatic cart population and checkout redirect
  - Clears WooCommerce cart
  - Adds membership product to cart
  - Stores member ID in session for payment tracking
  - Redirects to WooCommerce checkout page

**File: `HANDBOOK.md`**

- Updated "User Journey 1" to reflect new payment flow
- Updated "Payment Configuration" section
- Updated "Administrator" role description
- Added FAQ about auto-approval
- Updated version history

#### Benefits:

✅ **Faster Onboarding**: Members can complete registration and payment in one session
✅ **Higher Conversion**: Reduced steps mean fewer dropouts
✅ **Better UX**: Clear, straightforward process from registration to payment
✅ **Less Admin Work**: No manual approval needed for every registration
✅ **Immediate Revenue**: Payments processed immediately after registration

#### Migration Notes:

- Existing members with `'pending'` status are not affected
- Admin can still manually adjust member status if needed
- All approval functionality remains in admin panel for edge cases
- Coach approval process remains unchanged (still requires admin review)

#### Configuration Required:

Ensure the following is configured for this to work properly:

1. **WooCommerce Product ID**
   - Set in: Admin → Junior Golf Kenya → Settings
   - Option: `jgk_membership_product_id`
   - Must point to valid WooCommerce product

2. **Payment Gateways**
   - Configure in: WooCommerce → Settings → Payments
   - Enable: M-Pesa, eLipa, Stripe, or other gateways
   - Test payment flow after update

3. **Email Templates**
   - Review updated email notifications
   - Customize if needed in Settings

#### Rollback Instructions:

If you need to revert to the old approval process:

1. Edit `public/partials/juniorgolfkenya-registration-form.php`
2. Line 225: Change `'status' => 'approved'` back to `'status' => 'pending'`
3. Lines 315-331: Replace checkout redirect code with old portal redirect
4. Update email templates to mention pending approval

---

## [1.0.0] - 2025-12-05

### Added - Initial Release

#### Core Features:
- Multi-step member registration system with document uploads
- Parent-child relationship management
- WooCommerce payment integration (M-Pesa, eLipa, Stripe, etc.)
- Custom login system replacing WordPress default
- Role-based dashboards (Admin, Coach, Parent, Member)
- Coach application and approval workflow
- Membership verification system
- Audit trail and reporting
- Member import from ARMember plugin

#### User Roles:
- **Administrator**: Full system management
- **Coach (jgk_coach)**: View assigned members, submit reports
- **Parent**: Manage multiple children, track payments
- **Member (jgk_member)**: View profile, membership status, payment history

#### Registration Features:
- 5-step registration form
- Birth certificate upload (required)
- Profile photo upload
- Parent/guardian information
- Age verification (2-17 years for juniors)
- Email validation

#### Payment System:
- WooCommerce product-based membership fees
- Multiple payment gateway support
- Payment tracking in database
- Automated membership activation on payment
- Payment history for members and parents

#### Dashboard Features:
- **Admin Dashboard**: Member management, payment tracking, reports, coach approvals
- **Coach Dashboard**: View assigned members, submit progress reports
- **Parent Dashboard**: Manage multiple children, payment tracking, profile updates
- **Member Dashboard**: View profile, membership status, payment history

#### Database Tables:
- `jgk_members`: Member profiles and membership data
- `jgk_parents_guardians`: Parent-child relationships
- `jgk_payments`: Payment transaction records
- `jgk_audit_log`: System activity tracking
- `jgk_member_roles`: Member role assignments

#### Shortcodes:
- `[jgk_login_form]`: Custom login page
- `[jgk_registration_form]`: Member registration
- `[jgk_member_dashboard]`: Member dashboard
- `[jgk_parent_dashboard]`: Parent dashboard
- `[jgk_coach_dashboard]`: Coach dashboard
- `[jgk_verification_widget]`: Membership verification
- `[jgk_public_members]`: Public member listing
- `[jgk_coach_request_form]`: Coach application

#### Security:
- Nonce verification on all forms
- Role-based access control
- Password hashing (WordPress standards)
- File upload validation
- SQL injection prevention
- XSS protection

#### Email Notifications:
- Welcome email for new members
- Admin notification for new registrations
- Payment confirmation emails
- Coach approval/rejection notifications

---

## Future Roadmap

### Planned Features:
- Bulk member registration for organizations
- SMS notifications integration
- Mobile app integration (API)
- Advanced reporting and analytics
- Membership renewal reminders
- Coach performance tracking
- Member progress tracking system
- Tournament management module
- Training session scheduling
- Parent-coach messaging system

### Under Consideration:
- Multi-language support
- Integration with golf course management systems
- QR code membership cards
- Automated backup system
- Advanced search and filtering
- Custom reporting builder

---

## Support

For issues, questions, or feature requests:
- **Documentation**: See HANDBOOK.md
- **Admin Panel**: Junior Golf Kenya → Support
- **GitHub**: Contact repository owner

---

## License

Proprietary software developed for Junior Golf Kenya. All rights reserved.
