<?php
/**
 * PHP Syntax Checker and Fixer
 */

$file_path = '../public/partials/juniorgolfkenya-registration-form.php';

if (!file_exists($file_path)) {
    echo "File not found: $file_path\n";
    exit(1);
}

$content = file_get_contents($file_path);
$lines = explode("\n", $content);

echo "=== PHP SYNTAX ANALYSIS ===\n";
echo "File: $file_path\n";
echo "Total lines: " . count($lines) . "\n\n";

// Check for PHP syntax errors
echo "Checking PHP syntax...\n";
$temp_file = tempnam(sys_get_temp_dir(), 'php_check');
file_put_contents($temp_file, $content);

$output = shell_exec("php -l $temp_file 2>&1");
if (strpos($output, 'No syntax errors') !== false) {
    echo "✅ No syntax errors found\n";
} else {
    echo "❌ Syntax errors found:\n";
    echo $output . "\n";
}

unlink($temp_file);

// Count braces for balance check
$open_braces = 0;
$close_braces = 0;
$php_mode = false;
$in_string = false;
$string_char = '';

echo "\n=== BRACE ANALYSIS ===\n";

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $line_num = $i + 1;
    
    // Check for PHP opening/closing tags
    if (strpos($line, '<?php') !== false || strpos($line, '<?') !== false) {
        $php_mode = true;
    }
    if (strpos($line, '?>') !== false) {
        $php_mode = false;
    }
    
    // Only analyze PHP sections
    if ($php_mode) {
        $chars = str_split($line);
        for ($j = 0; $j < count($chars); $j++) {
            $char = $chars[$j];
            
            // Handle string detection
            if (!$in_string && ($char === '"' || $char === "'")) {
                $in_string = true;
                $string_char = $char;
                continue;
            } elseif ($in_string && $char === $string_char && ($j === 0 || $chars[$j-1] !== '\\')) {
                $in_string = false;
                $string_char = '';
                continue;
            }
            
            // Count braces only outside of strings
            if (!$in_string) {
                if ($char === '{') {
                    $open_braces++;
                    echo "Line $line_num: Opening brace found (total open: $open_braces)\n";
                } elseif ($char === '}') {
                    $close_braces++;
                    echo "Line $line_num: Closing brace found (total close: $close_braces)\n";
                }
            }
        }
    }
}

echo "\nSUMMARY:\n";
echo "Opening braces: $open_braces\n";
echo "Closing braces: $close_braces\n";
echo "Difference: " . ($open_braces - $close_braces) . "\n";

if ($open_braces === $close_braces) {
    echo "✅ Braces are balanced\n";
} else {
    echo "❌ Braces are NOT balanced\n";
    if ($open_braces > $close_braces) {
        $missing = $open_braces - $close_braces;
        echo "Missing $missing closing brace(s)\n";
    } else {
        $extra = $close_braces - $open_braces;
        echo "Extra $extra closing brace(s)\n";
    }
}

// Look for specific problematic patterns
echo "\n=== PATTERN ANALYSIS ===\n";

// Look for duplicate code blocks
$problematic_lines = array();

for ($i = 0; $i < count($lines) - 1; $i++) {
    $line = trim($lines[$i]);
    
    // Look for specific problematic patterns
    if (strpos($line, '// Insert into members table') !== false) {
        $problematic_lines[] = array('line' => $i + 1, 'content' => $line, 'issue' => 'Duplicate insert section');
    }
    
    if (strpos($line, 'Auto-login the user after successful registration') !== false) {
        $problematic_lines[] = array('line' => $i + 1, 'content' => $line, 'issue' => 'Duplicate auto-login section');
    }
}

if (count($problematic_lines) > 0) {
    echo "Found problematic patterns:\n";
    foreach ($problematic_lines as $issue) {
        echo "Line {$issue['line']}: {$issue['issue']}\n";
        echo "  Content: {$issue['content']}\n";
    }
} else {
    echo "No obvious problematic patterns found\n";
}

// Check for unclosed PHP tags
echo "\n=== PHP TAG ANALYSIS ===\n";
$php_opens = substr_count($content, '<?php') + substr_count($content, '<?');
$php_closes = substr_count($content, '?>');

echo "PHP opening tags: $php_opens\n";
echo "PHP closing tags: $php_closes\n";

if ($php_opens === $php_closes) {
    echo "✅ PHP tags are balanced\n";
} else {
    echo "❌ PHP tags are NOT balanced\n";
}

echo "\n=== RECOMMENDATIONS ===\n";

if ($open_braces > $close_braces) {
    echo "1. Add " . ($open_braces - $close_braces) . " closing brace(s) '}' before the ?> tag\n";
}

if (count($problematic_lines) > 0) {
    echo "2. Remove duplicate code sections found in the analysis\n";
}

echo "3. Check lines around 280-300 for duplicate database insertion code\n";
echo "4. Ensure all PHP code blocks are properly closed\n";

echo "\n=== SUGGESTED FIX ===\n";
echo "The file appears to have duplicate code sections. Here's what to do:\n";
echo "1. Remove the duplicate insertion code starting around line 282\n";
echo "2. Ensure all if/else blocks are properly closed\n";
echo "3. Add missing closing braces before the ?> tag\n";

?>