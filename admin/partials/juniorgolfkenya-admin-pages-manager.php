<?php
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}

$required_pages = array(
    array('title' => 'Parent Dashboard', 'option' => 'jgk_page_parent_dashboard', 'shortcode' => '[jgk_parent_dashboard]'),
    array('title' => 'Member Dashboard', 'option' => 'jgk_page_member_dashboard', 'shortcode' => '[jgk_member_dashboard]'),
    array('title' => 'Coach Dashboard', 'option' => 'jgk_page_coach_dashboard', 'shortcode' => '[jgk_coach_dashboard]'),
    array('title' => 'Registration', 'option' => 'jgk_page_registration', 'shortcode' => '[jgk_registration_form]'),
    array('title' => 'Login', 'option' => 'jgk_page_login', 'shortcode' => '[jgk_login_form]'),
);

echo '<div class="wrap"><h1>Pages Manager</h1>';
echo '<table class="widefat fixed striped"><thead><tr><th>Page</th><th>Status</th><th>Shortcode</th><th>Action</th></tr></thead><tbody>';

foreach ($required_pages as $page) {
    $page_id = get_option($page['option']);
    $exists = $page_id && get_post_status($page_id);
    $url = $exists ? get_permalink($page_id) : '';
    echo '<tr>';
    echo '<td>' . esc_html($page['title']) . '</td>';
    echo '<td>' . ($exists ? '<span style="color:green;">Exists</span>' : '<span style="color:red;">Missing</span>') . '</td>';
    echo '<td><code>' . esc_html($page['shortcode']) . '</code></td>';
    echo '<td>';
    if (!$exists) {
        $create_url = wp_nonce_url(admin_url('admin-post.php?action=jgk_create_page&option=' . urlencode($page['option']) . '&title=' . urlencode($page['title']) . '&shortcode=' . urlencode($page['shortcode'])), 'jgk_create_page');
        echo '<a class="button button-primary" href="' . esc_url($create_url) . '">Create Page</a>';
    } else {
        echo '<a class="button" href="' . esc_url($url) . '" target="_blank">View Page</a> ';
        echo '<span style="color:green;">OK</span>';
    }
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table></div>';