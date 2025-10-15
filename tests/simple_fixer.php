<?php
/**
 * Simple PHP Syntax Fixer
 */

$file_path = '../public/partials/juniorgolfkenya-registration-form.php';

echo "=== FIXING SYNTAX ERRORS ===\n";

// Create backup
$backup_file = $file_path . '.backup.' . date('Y-m-d_H-i-s');
copy($file_path, $backup_file);
echo "Backup created: $backup_file\n";

// Read the file
$content = file_get_contents($file_path);
$lines = explode("\n", $content);

echo "Original lines: " . count($lines) . "\n";

// Find and fix the specific issues
$fixed_lines = [];
$skip_mode = false;
$skip_start = false;

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $line_num = $i + 1;
    
    // Skip the duplicate code block (around lines 280-400)
    if (strpos($line, '// Insert into members table') !== false && $line_num > 200) {
        $skip_mode = true;
        $skip_start = true;
        echo "Starting skip at line $line_num\n";
        continue;
    }
    
    // End skip when we reach the second auto-login comment or redirect
    if ($skip_mode && (strpos($line, '// Auto-login the user after successful registration') !== false || 
                       strpos($line, 'wp_redirect($redirect_url);') !== false ||
                       strpos($line, 'exit;') !== false)) {
        if (!$skip_start) {
            $skip_mode = false;
            echo "Ending skip at line $line_num\n";
            
            // Add proper closing and redirect logic
            $fixed_lines[] = '                        // Set registration success flag';
            $fixed_lines[] = '                        $registration_success = true;';
            $fixed_lines[] = '';
            $fixed_lines[] = '                        // Auto-login the user after successful registration';
            $fixed_lines[] = '                        wp_set_current_user($user_id);';
            $fixed_lines[] = '                        wp_set_auth_cookie($user_id);';
            $fixed_lines[] = '';
            $fixed_lines[] = '                        // Determine redirect URL';
            $fixed_lines[] = '                        $portal_page_id = get_option(\'jgk_page_member_portal\');';
            $fixed_lines[] = '                        if ($portal_page_id) {';
            $fixed_lines[] = '                            $redirect_url = get_permalink($portal_page_id);';
            $fixed_lines[] = '                        } else {';
            $fixed_lines[] = '                            $dashboard_page_id = get_option(\'jgk_page_member_dashboard\');';
            $fixed_lines[] = '                            $redirect_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url(\'/member-portal\');';
            $fixed_lines[] = '                        }';
            $fixed_lines[] = '';
            $fixed_lines[] = '                        // Perform redirect';
            $fixed_lines[] = '                        wp_redirect($redirect_url);';
            $fixed_lines[] = '                        exit;';
            continue;
        }
        $skip_start = false;
    }
    
    // If we're in skip mode, don't add the line
    if ($skip_mode) {
        continue;
    }
    
    // Add missing closing braces before ?>
    if (trim($line) === '?>') {
        $fixed_lines[] = '    }';
        $fixed_lines[] = '}';
    }
    
    $fixed_lines[] = $line;
}

// Write the fixed content
$fixed_content = implode("\n", $fixed_lines);
file_put_contents($file_path, $fixed_content);

echo "Fixed lines: " . count($fixed_lines) . "\n";

// Test the syntax
$temp_file = tempnam(sys_get_temp_dir(), 'php_test');
file_put_contents($temp_file, $fixed_content);

echo "\n=== TESTING SYNTAX ===\n";
$result = shell_exec("php -l $temp_file 2>&1");
echo $result;

if (strpos($result, 'No syntax errors') !== false) {
    echo "✅ SUCCESS: File fixed!\n";
} else {
    echo "❌ FAILED: Restoring backup\n";
    copy($backup_file, $file_path);
}

unlink($temp_file);

?>