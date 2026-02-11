<?php
/**
 * Diagnostics helpers
 * Provides a read-only shortcode to inspect member image data
 */

if (!defined('ABSPATH')) {
    exit;
}

class JuniorGolfKenya_Diagnostics {

    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }

    public function register_shortcodes() {
        add_shortcode('jgk_member_image_diag', array($this, 'render_member_image_diag'));
    }

    /**
     * Shortcode output (read-only) - only for administrators
     * Usage: [jgk_member_image_diag limit="50"]
     */
    public function render_member_image_diag($atts) {
        if (!current_user_can('manage_options')) {
            return '<p>Insufficient permissions to view diagnostics.</p>';
        }

        $atts = shortcode_atts(array(
            'limit' => 50,
        ), $atts, 'jgk_member_image_diag');

        $limit = intval($atts['limit']) ?: 50;

        // Fetch members
        if (!class_exists('JuniorGolfKenya_Database')) {
            require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
        }

        if (!class_exists('JuniorGolfKenya_Media')) {
            require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-media.php';
        }

        $members = JuniorGolfKenya_Database::get_members(1, $limit);

        $out = '';
        $out .= '<div class="jgk-diag"><h3>Member Image Diagnostics (showing ' . esc_html(count($members)) . ')</h3>';
        $out .= '<table style="width:100%; border-collapse: collapse;">';
        $out .= '<thead><tr>';
        $out .= '<th style="border:1px solid #ddd;padding:6px;text-align:left">ID</th>';
        $out .= '<th style="border:1px solid #ddd;padding:6px;text-align:left">Name</th>';
        $out .= '<th style="border:1px solid #ddd;padding:6px;text-align:left">profile_image_id</th>';
        $out .= '<th style="border:1px solid #ddd;padding:6px;text-align:left">image_url</th>';
        $out .= '<th style="border:1px solid #ddd;padding:6px;text-align:left">html_preview</th>';
        $out .= '</tr></thead><tbody>';

        foreach ($members as $m) {
            $id = $m->id;
            $name = trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')) ?: ($m->display_name ?? '');
            $pid = $m->profile_image_id ?? null;
            $url = JuniorGolfKenya_Media::get_profile_image_url($id, 'thumbnail');
            $html = JuniorGolfKenya_Media::get_profile_image_html($id, 'thumbnail');

            $html_preview = '';
            if ($html === '' || $html === null) {
                $html_preview = '<em>(empty)</em>';
            } else {
                $safe = esc_html(substr(trim(preg_replace('/\s+/', ' ', $html)), 0, 200));
                // If it contains an <img> tag, render it after the preview
                if (strpos($html, '<img') !== false) {
                    $html_preview = '<div style="display:flex;gap:8px;align-items:center;"><div style="font-family:monospace;">' . $safe . '</div><div>' . $html . '</div></div>';
                } else {
                    $html_preview = '<div style="font-family:monospace;">' . $safe . '</div>';
                }
            }

            $out .= '<tr>';
            $out .= '<td style="border:1px solid #ddd;padding:6px">' . esc_html($id) . '</td>';
            $out .= '<td style="border:1px solid #ddd;padding:6px">' . esc_html($name) . '</td>';
            $out .= '<td style="border:1px solid #ddd;padding:6px">' . esc_html(var_export($pid, true)) . '</td>';
            $out .= '<td style="border:1px solid #ddd;padding:6px">' . esc_html($url ?: '(none)') . '</td>';
            $out .= '<td style="border:1px solid #ddd;padding:6px">' . $html_preview . '</td>';
            $out .= '</tr>';
        }

        $out .= '</tbody></table></div>';

        return $out;
    }
}

new JuniorGolfKenya_Diagnostics();
