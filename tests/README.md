# Test Files

This directory contains test and utility scripts for the Junior Golf Kenya plugin.

**⚠️ IMPORTANT**: These files are for development/testing only and should NOT be accessed directly via web browser.

## Files

- `verify_all_tables.php` - Verify database table structure
- `test_*.php` - Various test scripts for plugin features
- `check_*.php` - Database verification scripts
- `fix_capabilities.php` - Utility to fix WordPress capabilities
- `recreate_tables.php` - Database recreation script

## Usage

These scripts should be run via:
1. WP-CLI: `wp eval-file tests/script-name.php`
2. WordPress admin (if integrated)
3. Direct execution with caution (ensure proper WordPress context)

## Security

Direct web access is blocked by `.htaccess`.
