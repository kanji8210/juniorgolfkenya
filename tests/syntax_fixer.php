<?php
/**
 * Automatic PHP File Fixer
 */

$file_path = '../public/partials/juniorgolfkenya-registration-form.php';

if (!file_exists($file_path)) {
    echo "File not found: $file_path\n";
    exit(1);
}

$content = file_get_contents($file_path);

echo "=== FIXING PHP SYNTAX ERRORS ===\n";
echo "Original file size: " . strlen($content) . " bytes\n";

// Create backup
$backup_file = $file_path . '.backup.' . date('Y-m-d_H-i-s');
file_put_contents($backup_file, $content);
echo "Backup created: $backup_file\n";

// Fix 1: Remove duplicate code block starting from line 282
echo "Fixing duplicate code block...\n";

// The problematic section starts with "// Insert into members table" after "Redirect to Member Portal"
// and ends before the original closing braces

$pattern1 = '/\/\/ Redirect to Member Portal after successful registration\s*\$portal_page_id = get_option\(\'jgk_page_member_portal\'\);\s*\/\/ Insert into members table.*?\/\/ Perform redirect\s*wp_redirect\(\$redirect_url\);\s*exit;/s';

// Replace with corrected version
$replacement1 = "// Set registration success flag
                        \$registration_success = true;

                        // Auto-login the user after successful registration
                        wp_set_current_user(\$user_id);
                        wp_set_auth_cookie(\$user_id);

                        // Determine redirect URL based on configuration
                        \$portal_page_id = get_option('jgk_page_member_portal');
                        if (\$portal_page_id) {
                            \$redirect_url = get_permalink(\$portal_page_id);
                        } else {
                            \$dashboard_page_id = get_option('jgk_page_member_dashboard');
                            \$redirect_url = \$dashboard_page_id ? get_permalink(\$dashboard_page_id) : home_url('/member-portal');
                        }

                        // Perform redirect
                        wp_redirect(\$redirect_url);
                        exit;";

$content = preg_replace($pattern1, $replacement1, $content);

// Fix 2: Add missing closing braces before the ?> tag
echo "Adding missing closing braces...\n";

// Find the last ?> and add missing braces before it
$content = preg_replace('/(\s*)\?>(\s*<div class="jgk-registration-form">)/', '$1}$2}$3$4', $content);

// Alternative approach: find the exact location and add braces
$lines = explode("\n", $content);
$fixed_lines = [];
$brace_count = 0;
$php_mode = false;

foreach ($lines as $i => $line) {
    // Track PHP mode
    if (strpos($line, '<?php') !== false || strpos($line, '<?') !== false) {
        $php_mode = true;
    }
    
    // If we hit the first ?> and we're missing braces, add them
    if (strpos($line, '?>') !== false && $php_mode) {
        // Add missing closing braces before ?>
        $fixed_lines[] = '}';
        $fixed_lines[] = '}';
        $php_mode = false;
    }
    
    $fixed_lines[] = $line;
}

$content = implode("\n", $fixed_lines);

// Write the fixed content
file_put_contents($file_path, $content);

echo "Fixed file size: " . strlen($content) . " bytes\n";

// Verify the fix
echo "\n=== VERIFICATION ===\n";
$temp_file = tempnam(sys_get_temp_dir(), 'php_check_fixed');
file_put_contents($temp_file, $content);

$output = shell_exec("php -l $temp_file 2>&1");
if (strpos($output, 'No syntax errors') !== false) {
    echo "✅ Syntax errors fixed successfully!\n";
    echo "$output\n";
} else {
    echo "❌ Still has syntax errors:\n";
    echo "$output\n";
    
    // Restore backup if fix failed
    echo "Restoring backup...\n";
    file_put_contents($file_path, file_get_contents($backup_file));
}

unlink($temp_file);

echo "\nDone! ";
if (strpos($output, 'No syntax errors') !== false) {
    echo "File has been fixed successfully.\n";
    echo "Backup is available at: $backup_file\n";
} else {
    echo "Fix failed, original file restored.\n";
}

?>