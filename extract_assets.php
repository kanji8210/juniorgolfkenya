<?php
/**
 * Automated CSS/JS Extraction Script for Junior Golf Kenya Plugin
 * Extracts embedded CSS and JavaScript from PHP files in partials/ folder
 */

class JGK_CodeOrganizer {

    private $partials_dir;
    private $css_dir;
    private $js_dir;
    private $plugin_url;

    public function __construct() {
        $this->partials_dir = __DIR__ . '/public/partials/';
        $this->css_dir = $this->partials_dir . 'css/';
        $this->js_dir = $this->partials_dir . 'js/';
        $this->plugin_url = 'partials/'; // Relative URL for enqueue functions

        // Ensure directories exist
        if (!is_dir($this->css_dir)) {
            mkdir($this->css_dir, 0755, true);
        }
        if (!is_dir($this->js_dir)) {
            mkdir($this->js_dir, 0755, true);
        }
    }

    public function process_all_files() {
        $php_files = glob($this->partials_dir . '*.php');

        echo "🔍 Scanning PHP files in partials/ folder...\n";
        echo "Found " . count($php_files) . " PHP files\n\n";

        $processed = 0;
        $skipped = 0;

        foreach ($php_files as $file_path) {
            $filename = basename($file_path);

            // Skip already processed files
            if (in_array($filename, [
                'juniorgolfkenya-registration-form.php',
                'juniorgolfkenya-public-members.php'
            ])) {
                echo "⏭️  Skipping $filename (already processed)\n";
                $skipped++;
                continue;
            }

            if ($this->process_file($file_path)) {
                $processed++;
                echo "✅ Processed $filename\n";
            } else {
                echo "❌ Failed to process $filename\n";
            }
        }

        echo "\n📊 Summary:\n";
        echo "- Processed: $processed files\n";
        echo "- Skipped: $skipped files\n";
        echo "- Total: " . ($processed + $skipped) . " files\n";
    }

    private function process_file($file_path) {
        $content = file_get_contents($file_path);
        $filename = basename($file_path, '.php');
        $base_name = str_replace('juniorgolfkenya-', '', $filename);

        $modified = false;

        // Extract CSS
        if (strpos($content, '<style>') !== false) {
            $content = $this->extract_css($content, $filename);
            $modified = true;
        }

        // Extract JavaScript
        if (strpos($content, '<script>') !== false) {
            $content = $this->extract_javascript($content, $filename);
            $modified = true;
        }

        if ($modified) {
            // Create backup
            copy($file_path, $file_path . '.backup');

            // Write updated content
            file_put_contents($file_path, $content);

            // Add enqueue functions
            $this->add_enqueue_functions($file_path, $filename);
        }

        return $modified;
    }

    private function extract_css($content, $filename) {
        $css_pattern = '/<style>(.*?)<\/style>/s';
        preg_match_all($css_pattern, $content, $matches);

        if (!empty($matches[1])) {
            $css_content = '';
            foreach ($matches[1] as $css_block) {
                $css_content .= trim($css_block) . "\n\n";
            }

            // Clean up CSS
            $css_content = $this->clean_css($css_content);

            // Create CSS file
            $css_filename = $filename . '.css';
            $css_path = $this->css_dir . $css_filename;
            file_put_contents($css_path, $css_content);

            echo "   📄 Created CSS file: $css_filename\n";

            // Remove CSS from PHP content
            $content = preg_replace($css_pattern, '', $content);
        }

        return $content;
    }

    private function extract_javascript($content, $filename) {
        $js_pattern = '/<script>(.*?)<\/script>/s';
        preg_match_all($js_pattern, $content, $matches);

        if (!empty($matches[1])) {
            $js_content = '';
            foreach ($matches[1] as $js_block) {
                $js_content .= trim($js_block) . "\n\n";
            }

            // Clean up JavaScript
            $js_content = $this->clean_javascript($js_content);

            // Create JS file
            $js_filename = $filename . '.js';
            $js_path = $this->js_dir . $js_filename;
            file_put_contents($js_path, $js_content);

            echo "   📄 Created JS file: $js_filename\n";

            // Remove JS from PHP content
            $content = preg_replace($js_pattern, '', $content);
        }

        return $content;
    }

    private function clean_css($css) {
        // Add header comment
        $header = "/* " . ucwords(str_replace(['-', '_'], ' ', basename($css, '.css'))) . " Styles */\n\n";
        return $header . trim($css);
    }

    private function clean_javascript($js) {
        // Add header comment
        $header = "/**\n * " . ucwords(str_replace(['-', '_'], ' ', basename($js, '.js'))) . " JavaScript\n */\n\n";
        return $header . trim($js);
    }

    private function add_enqueue_functions($file_path, $filename) {
        $content = file_get_contents($file_path);

        // Check if enqueue functions already exist
        if (strpos($content, 'enqueue_' . str_replace('-', '_', $filename) . '_assets') !== false) {
            return;
        }

        // Find the end of the file (before return statement)
        $enqueue_code = $this->generate_enqueue_code($filename);

        // Insert before the return statement
        if (strpos($content, 'return ob_get_clean();') !== false) {
            $content = str_replace(
                'return ob_get_clean();',
                $enqueue_code . "\nreturn ob_get_clean();",
                $content
            );
        } else {
            // Append at the end if no return statement found
            $content .= "\n" . $enqueue_code;
        }

        file_put_contents($file_path, $content);
    }

    private function generate_enqueue_code($filename) {
        $function_name = 'enqueue_' . str_replace('-', '_', $filename) . '_assets';
        $css_handle = 'jgk-' . str_replace('_', '-', str_replace('juniorgolfkenya-', '', $filename));
        $js_handle = $css_handle;

        $code = "<?php\n";
        $code .= "// Enqueue styles and scripts for $filename\n";
        $code .= "function $function_name() {\n";
        $code .= "    \$plugin_dir = plugin_dir_path(__FILE__);\n";
        $code .= "    \$plugin_url = plugin_dir_url(__FILE__);\n";
        $code .= "    \n";
        $code .= "    // Enqueue CSS\n";
        $code .= "    wp_enqueue_style(\n";
        $code .= "        '$css_handle',\n";
        $code .= "        \$plugin_url . 'css/$filename.css',\n";
        $code .= "        array(),\n";
        $code .= "        '1.0.0'\n";
        $code .= "    );\n";
        $code .= "    \n";
        $code .= "    // Enqueue JavaScript\n";
        $code .= "    wp_enqueue_script(\n";
        $code .= "        '$js_handle',\n";
        $code .= "        \$plugin_url . 'js/$filename.js',\n";
        $code .= "        array(),\n";
        $code .= "        '1.0.0',\n";
        $code .= "        true\n";
        $code .= "    );\n";
        $code .= "}\n";
        $code .= "add_action('wp_enqueue_scripts', '$function_name');\n";
        $code .= "$function_name();\n";
        $code .= "?>";

        return $code;
    }
}

// Run the organizer
echo "🚀 Starting automated CSS/JS extraction for Junior Golf Kenya plugin\n";
echo "================================================================\n\n";

$organizer = new JGK_CodeOrganizer();
$organizer->process_all_files();

echo "\n✅ Automated extraction completed!\n";
echo "📁 Check the css/ and js/ folders for the extracted files\n";
echo "🔧 Review the updated PHP files for proper enqueue integration\n";
?>