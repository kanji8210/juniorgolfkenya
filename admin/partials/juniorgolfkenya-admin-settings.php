<?php
/**
 * Provide a admin area view for plugin settings
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_POST['submit'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_settings')) {
        wp_die(__('Security check failed.'));
    }

    // Save general settings
    $general_settings = array(
        'organization_name' => sanitize_text_field($_POST['organization_name']),
        'organization_email' => sanitize_email($_POST['organization_email']),
        'organization_phone' => sanitize_text_field($_POST['organization_phone']),
        'organization_address' => sanitize_textarea_field($_POST['organization_address']),
        'default_membership_fee' => floatval($_POST['default_membership_fee']),
        'currency' => sanitize_text_field($_POST['currency']),
        'timezone' => sanitize_text_field($_POST['timezone']),
    );
    
    // Save payment settings
    $payment_settings = array(
        'enable_online_payments' => isset($_POST['enable_online_payments']),
        'stripe_public_key' => sanitize_text_field($_POST['stripe_public_key']),
        'stripe_secret_key' => sanitize_text_field($_POST['stripe_secret_key']),
        'paypal_client_id' => sanitize_text_field($_POST['paypal_client_id']),
        'paypal_client_secret' => sanitize_text_field($_POST['paypal_client_secret']),
        'mpesa_consumer_key' => sanitize_text_field($_POST['mpesa_consumer_key']),
        'mpesa_consumer_secret' => sanitize_text_field($_POST['mpesa_consumer_secret']),
        'mpesa_environment' => sanitize_text_field($_POST['mpesa_environment']),
    );
    
    // Save email settings
    $email_settings = array(
        'enable_email_notifications' => isset($_POST['enable_email_notifications']),
        'smtp_host' => sanitize_text_field($_POST['smtp_host']),
        'smtp_port' => intval($_POST['smtp_port']),
        'smtp_username' => sanitize_text_field($_POST['smtp_username']),
        'smtp_password' => sanitize_text_field($_POST['smtp_password']),
        'smtp_encryption' => sanitize_text_field($_POST['smtp_encryption']),
        'from_email' => sanitize_email($_POST['from_email']),
        'from_name' => sanitize_text_field($_POST['from_name']),
    );
    
    // Save advanced settings
    $advanced_settings = array(
        'auto_approve_members' => isset($_POST['auto_approve_members']),
        'require_payment_for_membership' => isset($_POST['require_payment_for_membership']),
        'enable_public_registration' => isset($_POST['enable_public_registration']),
        'enable_coach_ratings' => isset($_POST['enable_coach_ratings']),
        'max_members_per_coach' => intval($_POST['max_members_per_coach']),
        'session_timeout_minutes' => intval($_POST['session_timeout_minutes']),
        'enable_audit_log' => isset($_POST['enable_audit_log']),
    );
    
    // Update options
    update_option('jgk_general_settings', $general_settings);
    update_option('jgk_payment_settings', $payment_settings);
    update_option('jgk_email_settings', $email_settings);
    update_option('jgk_advanced_settings', $advanced_settings);
    
    $message = 'Settings saved successfully!';
    $message_type = 'success';
}

// Get current settings
$general_settings = get_option('jgk_general_settings', array());
$payment_settings = get_option('jgk_payment_settings', array());
$email_settings = get_option('jgk_email_settings', array());
$advanced_settings = get_option('jgk_advanced_settings', array());

// Default values
$general_defaults = array(
    'organization_name' => 'Junior Golf Kenya',
    'organization_email' => get_option('admin_email'),
    'organization_phone' => '',
    'organization_address' => '',
    'default_membership_fee' => 5000,
    'currency' => 'KSH',
    'timezone' => 'Africa/Nairobi',
);

$general_settings = wp_parse_args($general_settings, $general_defaults);
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">Settings</h1>
    <hr class="wp-header-end">

    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('jgk_settings'); ?>
        
        <!-- Settings Tabs -->
        <div class="jgk-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active" onclick="switchTab(event, 'general')">General</a>
                <a href="#payments" class="nav-tab" onclick="switchTab(event, 'payments')">Payments</a>
                <a href="#email" class="nav-tab" onclick="switchTab(event, 'email')">Email</a>
                <a href="#advanced" class="nav-tab" onclick="switchTab(event, 'advanced')">Advanced</a>
            </nav>
        </div>

        <!-- General Settings Tab -->
        <div id="general" class="jgk-tab-content">
            <h2>General Settings</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Organization Name</th>
                    <td>
                        <input type="text" name="organization_name" value="<?php echo esc_attr($general_settings['organization_name']); ?>" class="regular-text" required>
                        <p class="description">The name of your golf organization.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Organization Email</th>
                    <td>
                        <input type="email" name="organization_email" value="<?php echo esc_attr($general_settings['organization_email']); ?>" class="regular-text" required>
                        <p class="description">Primary contact email for the organization.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Phone Number</th>
                    <td>
                        <input type="text" name="organization_phone" value="<?php echo esc_attr($general_settings['organization_phone']); ?>" class="regular-text">
                        <p class="description">Organization contact phone number.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Address</th>
                    <td>
                        <textarea name="organization_address" rows="3" class="large-text"><?php echo esc_textarea($general_settings['organization_address']); ?></textarea>
                        <p class="description">Organization physical address.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Default Membership Fee</th>
                    <td>
                        <input type="number" name="default_membership_fee" value="<?php echo esc_attr($general_settings['default_membership_fee']); ?>" step="0.01" min="0" class="regular-text">
                        <p class="description">Default annual membership fee.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Currency</th>
                    <td>
                        <select name="currency">
                            <option value="KSH" <?php selected($general_settings['currency'], 'KSH'); ?>>Kenyan Shilling (KSH)</option>
                            <option value="USD" <?php selected($general_settings['currency'], 'USD'); ?>>US Dollar (USD)</option>
                            <option value="EUR" <?php selected($general_settings['currency'], 'EUR'); ?>>Euro (EUR)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Timezone</th>
                    <td>
                        <select name="timezone">
                            <option value="Africa/Nairobi" <?php selected($general_settings['timezone'], 'Africa/Nairobi'); ?>>Africa/Nairobi</option>
                            <option value="UTC" <?php selected($general_settings['timezone'], 'UTC'); ?>>UTC</option>
                            <option value="America/New_York" <?php selected($general_settings['timezone'], 'America/New_York'); ?>>America/New_York</option>
                            <option value="Europe/London" <?php selected($general_settings['timezone'], 'Europe/London'); ?>>Europe/London</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Payment Settings Tab -->
        <div id="payments" class="jgk-tab-content" style="display: none;">
            <h2>Payment Settings</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Online Payments</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_online_payments" <?php checked(isset($payment_settings['enable_online_payments']) && $payment_settings['enable_online_payments']); ?>>
                            Enable online payment processing
                        </label>
                    </td>
                </tr>
            </table>
            
            <h3>Stripe Settings</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Stripe Public Key</th>
                    <td>
                        <input type="text" name="stripe_public_key" value="<?php echo esc_attr($payment_settings['stripe_public_key'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Stripe Secret Key</th>
                    <td>
                        <input type="password" name="stripe_secret_key" value="<?php echo esc_attr($payment_settings['stripe_secret_key'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            
            <h3>PayPal Settings</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">PayPal Client ID</th>
                    <td>
                        <input type="text" name="paypal_client_id" value="<?php echo esc_attr($payment_settings['paypal_client_id'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">PayPal Client Secret</th>
                    <td>
                        <input type="password" name="paypal_client_secret" value="<?php echo esc_attr($payment_settings['paypal_client_secret'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            
            <h3>M-Pesa Settings</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Consumer Key</th>
                    <td>
                        <input type="text" name="mpesa_consumer_key" value="<?php echo esc_attr($payment_settings['mpesa_consumer_key'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Consumer Secret</th>
                    <td>
                        <input type="password" name="mpesa_consumer_secret" value="<?php echo esc_attr($payment_settings['mpesa_consumer_secret'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Environment</th>
                    <td>
                        <select name="mpesa_environment">
                            <option value="sandbox" <?php selected($payment_settings['mpesa_environment'] ?? '', 'sandbox'); ?>>Sandbox</option>
                            <option value="production" <?php selected($payment_settings['mpesa_environment'] ?? '', 'production'); ?>>Production</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Email Settings Tab -->
        <div id="email" class="jgk-tab-content" style="display: none;">
            <h2>Email Settings</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Email Notifications</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_email_notifications" <?php checked(isset($email_settings['enable_email_notifications']) && $email_settings['enable_email_notifications']); ?>>
                            Enable email notifications
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">From Email</th>
                    <td>
                        <input type="email" name="from_email" value="<?php echo esc_attr($email_settings['from_email'] ?? $general_settings['organization_email']); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">From Name</th>
                    <td>
                        <input type="text" name="from_name" value="<?php echo esc_attr($email_settings['from_name'] ?? $general_settings['organization_name']); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            
            <h3>SMTP Configuration</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">SMTP Host</th>
                    <td>
                        <input type="text" name="smtp_host" value="<?php echo esc_attr($email_settings['smtp_host'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">SMTP Port</th>
                    <td>
                        <input type="number" name="smtp_port" value="<?php echo esc_attr($email_settings['smtp_port'] ?? 587); ?>" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">SMTP Username</th>
                    <td>
                        <input type="text" name="smtp_username" value="<?php echo esc_attr($email_settings['smtp_username'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">SMTP Password</th>
                    <td>
                        <input type="password" name="smtp_password" value="<?php echo esc_attr($email_settings['smtp_password'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Encryption</th>
                    <td>
                        <select name="smtp_encryption">
                            <option value="tls" <?php selected($email_settings['smtp_encryption'] ?? '', 'tls'); ?>>TLS</option>
                            <option value="ssl" <?php selected($email_settings['smtp_encryption'] ?? '', 'ssl'); ?>>SSL</option>
                            <option value="none" <?php selected($email_settings['smtp_encryption'] ?? '', 'none'); ?>>None</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Advanced Settings Tab -->
        <div id="advanced" class="jgk-tab-content" style="display: none;">
            <h2>Advanced Settings</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Member Approval</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_approve_members" <?php checked(isset($advanced_settings['auto_approve_members']) && $advanced_settings['auto_approve_members']); ?>>
                            Automatically approve new members
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Payment Requirement</th>
                    <td>
                        <label>
                            <input type="checkbox" name="require_payment_for_membership" <?php checked(isset($advanced_settings['require_payment_for_membership']) && $advanced_settings['require_payment_for_membership']); ?>>
                            Require payment for membership activation
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Public Registration</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_public_registration" <?php checked(isset($advanced_settings['enable_public_registration']) && $advanced_settings['enable_public_registration']); ?>>
                            Allow public member registration
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Coach Ratings</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_coach_ratings" <?php checked(isset($advanced_settings['enable_coach_ratings']) && $advanced_settings['enable_coach_ratings']); ?>>
                            Enable coach rating system
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Max Members per Coach</th>
                    <td>
                        <input type="number" name="max_members_per_coach" value="<?php echo esc_attr($advanced_settings['max_members_per_coach'] ?? 15); ?>" min="1" max="50" class="small-text">
                        <p class="description">Maximum number of members that can be assigned to one coach.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Session Timeout</th>
                    <td>
                        <input type="number" name="session_timeout_minutes" value="<?php echo esc_attr($advanced_settings['session_timeout_minutes'] ?? 30); ?>" min="5" max="180" class="small-text">
                        <span>minutes</span>
                        <p class="description">How long users stay logged in without activity.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Audit Logging</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_audit_log" <?php checked(isset($advanced_settings['enable_audit_log']) && $advanced_settings['enable_audit_log']); ?>>
                            Enable audit logging for admin actions
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="Save Changes">
            <button type="button" class="button" onclick="resetToDefaults()">Reset to Defaults</button>
        </p>
    </form>
</div>

<style>
.jgk-settings-tabs {
    margin: 20px 0;
}

.jgk-tab-content {
    background: #fff;
    border: 1px solid #ddd;
    border-top: none;
    padding: 20px;
}

.form-table th {
    width: 200px;
}

.form-table td {
    padding: 15px 10px;
}

.form-table h3 {
    margin: 30px 0 0 0;
    padding: 10px 0;
    border-bottom: 1px solid #ddd;
}
</style>

<script>
function switchTab(evt, tabName) {
    // Hide all tab content
    const tabContents = document.getElementsByClassName('jgk-tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].style.display = 'none';
    }
    
    // Remove active class from all tabs
    const tabs = document.getElementsByClassName('nav-tab');
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('nav-tab-active');
    }
    
    // Show selected tab and mark as active
    document.getElementById(tabName).style.display = 'block';
    evt.currentTarget.classList.add('nav-tab-active');
}

function resetToDefaults() {
    if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
        // Reset form to default values
        document.querySelector('form').reset();
        // You could also make an AJAX call to reset in database
    }
}

// Test email configuration
function testEmailConfig() {
    // AJAX call to test email settings
    alert('Email test functionality would be implemented here');
}
</script>