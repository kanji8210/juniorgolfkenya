<?php
/**
 * Custom Login Form - Public View
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/public/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle login form submission
$login_errors = array();
$login_success = false;

if (isset($_POST['jgk_login'])) {
    // Verify nonce
    if (!isset($_POST['jgk_login_nonce']) || !wp_verify_nonce($_POST['jgk_login_nonce'], 'jgk_user_login')) {
        $login_errors[] = 'Security check failed. Please try again.';
    } else {
        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validate input
        if (empty($username)) {
            $login_errors[] = 'Username or email is required.';
        }
        if (empty($password)) {
            $login_errors[] = 'Password is required.';
        }

        // If no errors, attempt login
        if (empty($login_errors)) {
            $creds = array(
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember
            );

            $user = wp_signon($creds, is_ssl());

            if (is_wp_error($user)) {
                $login_errors[] = 'Invalid username/email or password. Please try again.';
            } else {
                $login_success = true;
                
                // Determine redirect based on user role
                if (in_array('administrator', $user->roles)) {
                    $redirect_url = admin_url();
                } elseif (in_array('jgk_coach', $user->roles)) {
                    $coach_dashboard_id = get_option('jgk_page_coach_dashboard');
                    $redirect_url = $coach_dashboard_id ? get_permalink($coach_dashboard_id) : home_url('/coach-dashboard');
                } elseif (in_array('jgk_member', $user->roles) || in_array('jgk_junior', $user->roles)) {
                    // Check if user is a parent
                    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-parent-dashboard.php';
                    if (JuniorGolfKenya_Parent_Dashboard::is_parent($user->user_email)) {
                        $parent_dashboard_id = get_option('jgk_page_parent_dashboard');
                        $redirect_url = $parent_dashboard_id ? get_permalink($parent_dashboard_id) : home_url('/parent-dashboard');
                    } else {
                        $member_dashboard_id = get_option('jgk_page_member_dashboard');
                        $redirect_url = $member_dashboard_id ? get_permalink($member_dashboard_id) : home_url('/member-dashboard');
                    }
                } else {
                    $redirect_url = home_url();
                }

                // Check for redirect parameter
                if (isset($_GET['redirect_to']) && !empty($_GET['redirect_to'])) {
                    $redirect_url = esc_url_raw($_GET['redirect_to']);
                }

                wp_redirect($redirect_url);
                exit;
            }
        }
    }
}

// Check if already logged in
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $dashboard_page_id = get_option('jgk_page_member_dashboard');
    $dashboard_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/member-dashboard');
    ?>
    <div class="jgk-login-form">
        <div class="jgk-login-container">
            <div class="jgk-already-logged-in">
                <div class="jgk-login-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <h2>You're Already Logged In</h2>
                <p>Welcome back, <strong><?php echo esc_html($current_user->display_name); ?></strong>!</p>
                <div class="jgk-login-actions">
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="jgk-btn jgk-btn-primary">
                        <span class="dashicons dashicons-dashboard"></span>
                        Go to Dashboard
                    </a>
                    <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="jgk-btn jgk-btn-secondary">
                        <span class="dashicons dashicons-exit"></span>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    return;
}
?>

<div class="jgk-login-form">
    <div class="jgk-login-container">
        <div class="jgk-login-card">
            <!-- Login Header -->
            <div class="jgk-login-header">
                <div class="jgk-login-logo">
                    <?php 
                    $custom_logo_id = get_theme_mod('custom_logo');
                    if ($custom_logo_id) {
                        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                        echo '<img src="' . esc_url($logo[0]) . '" alt="' . get_bloginfo('name') . '">';
                    } else {
                        echo '<span class="dashicons dashicons-admin-users"></span>';
                    }
                    ?>
                </div>
                <h2>Member Login</h2>
                <p>Sign in to access your Junior Golf Kenya account</p>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($login_errors)): ?>
                <div class="jgk-login-errors">
                    <span class="dashicons dashicons-warning"></span>
                    <div>
                        <?php foreach ($login_errors as $error): ?>
                            <p><?php echo esc_html($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="post" class="jgk-login-form-inner">
                <?php wp_nonce_field('jgk_user_login', 'jgk_login_nonce'); ?>

                <div class="jgk-form-group">
                    <label for="username">
                        <span class="dashicons dashicons-admin-users"></span>
                        Username or Email
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo esc_attr($_POST['username'] ?? ''); ?>" 
                        required 
                        autocomplete="username"
                        placeholder="Enter your username or email"
                    >
                </div>

                <div class="jgk-form-group">
                    <label for="password">
                        <span class="dashicons dashicons-lock"></span>
                        Password
                    </label>
                    <div class="jgk-password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        >
                        <button type="button" class="jgk-toggle-password" aria-label="Toggle password visibility">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                </div>

                <div class="jgk-form-options">
                    <label class="jgk-checkbox-label">
                        <input type="checkbox" name="remember" value="1" <?php checked(isset($_POST['remember'])); ?>>
                        <span>Remember Me</span>
                    </label>
                    <a href="<?php echo wp_lostpassword_url(get_permalink()); ?>" class="jgk-forgot-password">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" name="jgk_login" class="jgk-btn jgk-btn-primary jgk-btn-large jgk-btn-login">
                    <span class="dashicons dashicons-unlock"></span>
                    Sign In
                </button>
            </form>

            <!-- Registration Link -->
            <div class="jgk-login-footer">
                <p>Don't have an account?</p>
                <?php 
                $register_page_id = get_option('jgk_page_member_registration');
                $register_url = $register_page_id ? get_permalink($register_page_id) : home_url('/member-registration');
                ?>
                <a href="<?php echo esc_url($register_url); ?>" class="jgk-register-link">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Become a Member
                </a>
            </div>

            <!-- Help Section -->
            <div class="jgk-login-help">
                <p>Need help? <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">Contact Support</a></p>
            </div>
        </div>
    </div>
</div>
