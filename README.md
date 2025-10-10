# Junior Golf Kenya - Plugin Structure

## Folder Organization

### Root Level
- `juniorgolfkenya.php` - Main plugin file with activation/deactivation hooks
- `about.txt` - Plugin documentation and feature overview

### `/admin/` - Admin-facing functionality
- `class-juniorgolfkenya-admin.php` - Admin class with menu pages
- `/css/` - Admin stylesheets
- `/js/` - Admin JavaScript files
- `/partials/` - Admin template files

### `/public/` - User-facing functionality
- `class-juniorgolfkenya-public.php` - Public class with shortcodes
- `/css/` - Public stylesheets
- `/js/` - Public JavaScript files
- `/partials/` - Public template files

### `/includes/` - Core functionality
- `class-juniorgolfkenya.php` - Main plugin class
- `class-juniorgolfkenya-activator.php` - **Activation & DB creation**
- `class-juniorgolfkenya-deactivator.php` - **Deactivation cleanup**
- `class-juniorgolfkenya-uninstaller.php` - **Complete uninstall**
- `class-juniorgolfkenya-loader.php` - Hook management
- `class-juniorgolfkenya-i18n.php` - Internationalization

### `/languages/` - Translation files
- Language files for internationalization

## Key Features

### Activation (`class-juniorgolfkenya-activator.php`)
- ✅ Creates 7 database tables (members, memberships, plans, payments, etc.)
- ✅ Inserts default membership plan
- ✅ Creates required pages with shortcodes
- ✅ Sets default plugin options
- ✅ Flushes rewrite rules

### Deactivation (`class-juniorgolfkenya-deactivator.php`)
- ✅ Clears scheduled cron events
- ✅ Logs deactivation event
- ✅ Flushes rewrite rules
- ✅ Preserves all data for reactivation

### Uninstall (`class-juniorgolfkenya-uninstaller.php`)
- ✅ Optional complete data removal
- ✅ Drops all plugin tables
- ✅ Removes all options and transients
- ✅ Deletes created pages
- ✅ Cleans user meta data
- ✅ Removes uploaded files

## Database Schema

### Tables Created:
1. `wp_jgk_members` - Member profiles and details
2. `wp_jgk_memberships` - Membership history and subscriptions
3. `wp_jgk_plans` - Membership plans and pricing
4. `wp_jgk_payments` - Payment transactions
5. `wp_jgk_competition_entries` - Tournament registrations
6. `wp_jgk_certifications` - Member certifications
7. `wp_jgk_audit_log` - Activity logging

## Admin Features
- Member management dashboard
- Payment processing and history
- Reports and analytics
- Plugin settings configuration
- Competition and certification tracking

## Public Features
- Member registration form
- Member portal dashboard
- Public membership verification
- Payment integration (Stripe/PayPal)
- Competition entry system

## Shortcodes
- `[jgk_member_portal]` - Member dashboard
- `[jgk_registration_form]` - New member registration
- `[jgk_verification_widget]` - Public membership verification

## Next Steps
1. Implement specific functionality in each class
2. Create admin partial templates
3. Create public partial templates
4. Add JavaScript functionality
5. Implement payment gateway integration
6. Add email notification system
7. Create REST API endpoints

## Security Features
- User capability checks
- Data sanitization and validation
- Audit logging
- Secure file uploads
- PCI-compliant payment handling