<?php
/**
 * Enhanced Settings Page with Test Data Management
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

// Load test data class
require_once plugin_dir_path(dirname(__FILE__)) . '../includes/class-juniorgolfkenya-test-data.php';

// Handle form submissions
$message = '';
$message_type = '';

// Handle test data generation
if (isset($_POST['generate_test_data']) && check_admin_referer('jgk_test_data', 'jgk_test_nonce')) {
    $count = isset($_POST['test_count']) ? intval($_POST['test_count']) : 10;
    $result = JuniorGolfKenya_Test_Data::generate_test_members($count);
    
    if (!empty($result['errors'])) {
        $message = 'Generated ' . count($result['members']) . ' test members with some errors: ' . implode(', ', $result['errors']);
        $message_type = 'warning';
    } else {
        $message = 'Successfully generated ' . count($result['members']) . ' test members!';
        $message_type = 'success';
    }
}

// Handle test data deletion (Go to Production)
if (isset($_POST['delete_test_data']) && check_admin_referer('jgk_delete_test', 'jgk_delete_nonce')) {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'DELETE') {
        $result = JuniorGolfKenya_Test_Data::delete_all_test_data();
        $message = sprintf(
            'Production mode activated! Deleted: %d users, %d members, %d parents, %d coach assignments.',
            $result['users_deleted'],
            $result['members_deleted'],
            $result['parents_deleted'],
            $result['coach_assignments_deleted']
        );
        $message_type = 'success';
    } else {
        $message = 'Please type "DELETE" to confirm deletion of all test data.';
        $message_type = 'error';
    }
}

// Handle settings save
if (isset($_POST['save_settings']) && check_admin_referer('jgk_settings', 'jgk_settings_nonce')) {
    
    // Junior age settings
    $junior_settings = array(
        'min_age' => intval($_POST['min_age']),
        'max_age' => intval($_POST['max_age']),
    );
    
    // Membership pricing
    $pricing_settings = array(
        'subscription_price' => floatval($_POST['subscription_price']),
        'currency' => sanitize_text_field($_POST['currency']),
        'currency_symbol' => sanitize_text_field($_POST['currency_symbol']),
        'payment_frequency' => sanitize_text_field($_POST['payment_frequency']),
    );
    
    // General settings
    $general_settings = array(
        'organization_name' => sanitize_text_field($_POST['organization_name']),
        'organization_email' => sanitize_email($_POST['organization_email']),
        'organization_phone' => sanitize_text_field($_POST['organization_phone']),
        'organization_address' => sanitize_textarea_field($_POST['organization_address']),
        'timezone' => sanitize_text_field($_POST['timezone']),
    );
    
    // Update options
    update_option('jgk_junior_settings', $junior_settings);
    update_option('jgk_pricing_settings', $pricing_settings);
    update_option('jgk_general_settings', $general_settings);
    
    $message = 'Settings saved successfully!';
    $message_type = 'success';
}

// Get current settings
$junior_settings = get_option('jgk_junior_settings', array('min_age' => 2, 'max_age' => 17));
$pricing_settings = get_option('jgk_pricing_settings', array(
    'subscription_price' => 1050,
    'currency' => 'KSH',
    'currency_symbol' => 'KSh',
    'payment_frequency' => 'yearly'
));
$general_settings = get_option('jgk_general_settings', array(
    'organization_name' => 'Junior Golf Kenya',
    'organization_email' => get_option('admin_email'),
    'organization_phone' => '',
    'organization_address' => '',
    'timezone' => 'Africa/Nairobi',
));

$membership_fee_warning = '';
if (class_exists('WooCommerce')) {
    $payment_settings = get_option('jgk_payment_settings', array());
    $membership_product_id = intval($payment_settings['membership_product_id'] ?? 0);
    if ($membership_product_id > 0) {
        $product = wc_get_product($membership_product_id);
        if ($product) {
            $settings_fee = JuniorGolfKenya_Settings_Helper::get_default_membership_fee();
            $product_price = (float) $product->get_regular_price();
            if ($settings_fee > 0 && $product_price !== (float) $settings_fee) {
                $membership_fee_warning = sprintf(
                    'Membership product price (%s) differs from Default Membership Fee (%s). Checkout will use the settings fee.',
                    wc_price($product_price),
                    wc_price($settings_fee)
                );
            }
        } else {
            $membership_fee_warning = 'Membership product ID does not exist in WooCommerce. Please check the Membership Product ID.';
        }
    }
}

// Check for test data
$has_test_data = JuniorGolfKenya_Test_Data::has_test_data();
$test_counts = JuniorGolfKenya_Test_Data::count_test_data();
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        Junior Golf Kenya Settings
    </h1>
    <hr class="wp-header-end">

    <?php if ($message): ?>
    <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($membership_fee_warning): ?>
    <div class="notice notice-warning">
        <p><?php echo esc_html($membership_fee_warning); ?></p>
    </div>
    <?php endif; ?>

    <!-- Settings Tabs -->
    <div class="jgk-settings-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#tab-general" class="nav-tab nav-tab-active" data-tab="general">
                <span class="dashicons dashicons-admin-generic"></span> General
            </a>
            <a href="#tab-membership" class="nav-tab" data-tab="membership">
                <span class="dashicons dashicons-groups"></span> Membership
            </a>
            <a href="#tab-pricing" class="nav-tab" data-tab="pricing">
                <span class="dashicons dashicons-money-alt"></span> Pricing
            </a>
            <a href="#tab-test-data" class="nav-tab <?php echo $has_test_data ? 'tab-warning' : ''; ?>" data-tab="test-data">
                <span class="dashicons dashicons-database"></span> Test Data
                <?php if ($has_test_data): ?>
                    <span class="test-data-badge"><?php echo $test_counts['members']; ?></span>
                <?php endif; ?>
            </a>
        </nav>
    </div>

    <style>
        .jgk-settings-tabs { margin: 20px 0; }
        .jgk-tab-content { display: none; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-top: none; }
        .jgk-tab-content.active { display: block; }
        .jgk-settings-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #ddd; }
        .jgk-settings-section:last-child { border-bottom: none; }
        .jgk-settings-section h2 { margin-top: 0; }
        .test-data-badge { 
            background: #d63638; 
            color: white; 
            padding: 2px 8px; 
            border-radius: 10px; 
            font-size: 12px; 
            margin-left: 5px;
        }
        .tab-warning { color: #d63638; }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .danger-box {
            background: #f8d7da;
            border-left: 4px solid #d63638;
            padding: 15px;
            margin: 15px 0;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
        .jgk-button-danger {
            background: #d63638;
            border-color: #d63638;
            color: white;
        }
        .jgk-button-danger:hover {
            background: #c92a2b;
            border-color: #c92a2b;
        }
        .jgk-button-success {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        .jgk-button-success:hover {
            background: #218838;
            border-color: #218838;
        }
        .currency-preview {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            padding: 10px;
            background: #f0f0f1;
            border-radius: 4px;
        }
    </style>

    <!-- Tab: General Settings -->
    <div id="tab-general" class="jgk-tab-content active">
        <form method="post" action="">
            <?php wp_nonce_field('jgk_settings', 'jgk_settings_nonce'); ?>
            
            <div class="jgk-settings-section">
                <h2><span class="dashicons dashicons-building"></span> Organization Information</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="organization_name">Organization Name</label></th>
                        <td>
                            <input type="text" id="organization_name" name="organization_name" 
                                   value="<?php echo esc_attr($general_settings['organization_name']); ?>" 
                                   class="regular-text" required>
                            <p class="description">The name of your golf organization.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="organization_email">Organization Email</label></th>
                        <td>
                            <input type="email" id="organization_email" name="organization_email" 
                                   value="<?php echo esc_attr($general_settings['organization_email']); ?>" 
                                   class="regular-text" required>
                            <p class="description">Primary contact email address.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="organization_phone">Phone Number</label></th>
                        <td>
                            <input type="text" id="organization_phone" name="organization_phone" 
                                   value="<?php echo esc_attr($general_settings['organization_phone']); ?>" 
                                   class="regular-text" placeholder="+254700000000">
                            <p class="description">Contact phone number.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="organization_address">Address</label></th>
                        <td>
                            <textarea id="organization_address" name="organization_address" 
                                      rows="3" class="large-text"><?php echo esc_textarea($general_settings['organization_address']); ?></textarea>
                            <p class="description">Physical address of the organization.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="timezone">Timezone</label></th>
                        <td>
                            <select id="timezone" name="timezone" class="regular-text">
                                <option value="Africa/Nairobi" <?php selected($general_settings['timezone'], 'Africa/Nairobi'); ?>>Africa/Nairobi (EAT)</option>
                                <option value="UTC" <?php selected($general_settings['timezone'], 'UTC'); ?>>UTC</option>
                            </select>
                            <p class="description">Timezone for dates and times.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <button type="submit" name="save_settings" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span> Save General Settings
                </button>
            </p>
        </form>
    </div>

    <!-- Tab: Membership Settings -->
    <div id="tab-membership" class="jgk-tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('jgk_settings', 'jgk_settings_nonce'); ?>
            
            <div class="jgk-settings-section">
                <h2><span class="dashicons dashicons-calendar-alt"></span> Junior Age Requirements</h2>
                
                <div class="warning-box">
                    <strong>⚠️ Important:</strong> These age limits control who can register as a junior member. 
                    Changing these values will affect new registrations immediately.
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="min_age">Minimum Age</label></th>
                        <td>
                            <input type="number" id="min_age" name="min_age" 
                                   value="<?php echo esc_attr($junior_settings['min_age']); ?>" 
                                   min="1" max="10" required style="width: 100px;">
                            <span class="description">years old</span>
                            <p class="description">Minimum age to register as a junior member (default: 2 years).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_age">Maximum Age</label></th>
                        <td>
                            <input type="number" id="max_age" name="max_age" 
                                   value="<?php echo esc_attr($junior_settings['max_age']); ?>" 
                                   min="10" max="21" required style="width: 100px;">
                            <span class="description">years old</span>
                            <p class="description">Maximum age for junior membership (default: 17 years, must be < 18).</p>
                        </td>
                    </tr>
                </table>
                
                <div class="success-box" style="margin-top: 20px;">
                    <strong>Current Age Range:</strong> 
                    Juniors aged <strong><?php echo $junior_settings['min_age']; ?></strong> to 
                    <strong><?php echo $junior_settings['max_age']; ?></strong> years can register.
                </div>
            </div>
            
            <!-- Copy hidden fields from General tab -->
            <input type="hidden" name="organization_name" value="<?php echo esc_attr($general_settings['organization_name']); ?>">
            <input type="hidden" name="organization_email" value="<?php echo esc_attr($general_settings['organization_email']); ?>">
            <input type="hidden" name="organization_phone" value="<?php echo esc_attr($general_settings['organization_phone']); ?>">
            <input type="hidden" name="organization_address" value="<?php echo esc_attr($general_settings['organization_address']); ?>">
            <input type="hidden" name="timezone" value="<?php echo esc_attr($general_settings['timezone']); ?>">
            <input type="hidden" name="subscription_price" value="<?php echo esc_attr($pricing_settings['subscription_price']); ?>">
            <input type="hidden" name="currency" value="<?php echo esc_attr($pricing_settings['currency']); ?>">
            <input type="hidden" name="currency_symbol" value="<?php echo esc_attr($pricing_settings['currency_symbol']); ?>">
            <input type="hidden" name="payment_frequency" value="<?php echo esc_attr($pricing_settings['payment_frequency']); ?>">
            
            <p class="submit">
                <button type="submit" name="save_settings" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span> Save Membership Settings
                </button>
            </p>
        </form>
    </div>

    <!-- Tab: Pricing Settings -->
    <div id="tab-pricing" class="jgk-tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('jgk_settings', 'jgk_settings_nonce'); ?>
            
            <div class="jgk-settings-section">
                <h2><span class="dashicons dashicons-money-alt"></span> Membership Pricing</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="subscription_price">Subscription Price</label></th>
                        <td>
                            <input type="number" id="subscription_price" name="subscription_price" 
                                   value="<?php echo esc_attr($pricing_settings['subscription_price']); ?>" 
                                   min="0" step="0.01" required style="width: 200px;">
                            <p class="description">Membership fee amount (numbers only, e.g., 1050).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="currency">Currency Code</label></th>
                        <td>
                            <select id="currency" name="currency" required style="width: 200px;">
                                <option value="KSH" <?php selected($pricing_settings['currency'], 'KSH'); ?>>KSH - Kenyan Shilling</option>
                                <option value="USD" <?php selected($pricing_settings['currency'], 'USD'); ?>>USD - US Dollar</option>
                                <option value="EUR" <?php selected($pricing_settings['currency'], 'EUR'); ?>>EUR - Euro</option>
                                <option value="GBP" <?php selected($pricing_settings['currency'], 'GBP'); ?>>GBP - British Pound</option>
                                <option value="ZAR" <?php selected($pricing_settings['currency'], 'ZAR'); ?>>ZAR - South African Rand</option>
                                <option value="TZS" <?php selected($pricing_settings['currency'], 'TZS'); ?>>TZS - Tanzanian Shilling</option>
                                <option value="UGX" <?php selected($pricing_settings['currency'], 'UGX'); ?>>UGX - Ugandan Shilling</option>
                            </select>
                            <p class="description">Three-letter currency code (ISO 4217).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="currency_symbol">Currency Symbol</label></th>
                        <td>
                            <input type="text" id="currency_symbol" name="currency_symbol" 
                                   value="<?php echo esc_attr($pricing_settings['currency_symbol']); ?>" 
                                   required style="width: 100px;" placeholder="KSh">
                            <p class="description">Symbol to display (e.g., KSh, $, €, £).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="payment_frequency">Payment Frequency</label></th>
                        <td>
                            <select id="payment_frequency" name="payment_frequency" required style="width: 200px;">
                                <option value="monthly" <?php selected($pricing_settings['payment_frequency'], 'monthly'); ?>>Monthly</option>
                                <option value="quarterly" <?php selected($pricing_settings['payment_frequency'], 'quarterly'); ?>>Quarterly (3 months)</option>
                                <option value="yearly" <?php selected($pricing_settings['payment_frequency'], 'yearly'); ?>>Yearly (12 months)</option>
                                <option value="one-time" <?php selected($pricing_settings['payment_frequency'], 'one-time'); ?>>One-time</option>
                            </select>
                            <p class="description">How often members pay their subscription.</p>
                        </td>
                    </tr>
                </table>
                
                <div class="currency-preview">
                    <strong>Price Display Preview:</strong> 
                    <span id="price-preview">
                        <?php echo esc_html($pricing_settings['currency_symbol']); ?> 
                        <?php echo number_format($pricing_settings['subscription_price'], 2); ?>
                        <?php echo esc_html($pricing_settings['currency']); ?>
                        / <?php echo esc_html($pricing_settings['payment_frequency']); ?>
                    </span>
                </div>
            </div>
            
            <!-- Copy hidden fields -->
            <input type="hidden" name="organization_name" value="<?php echo esc_attr($general_settings['organization_name']); ?>">
            <input type="hidden" name="organization_email" value="<?php echo esc_attr($general_settings['organization_email']); ?>">
            <input type="hidden" name="organization_phone" value="<?php echo esc_attr($general_settings['organization_phone']); ?>">
            <input type="hidden" name="organization_address" value="<?php echo esc_attr($general_settings['organization_address']); ?>">
            <input type="hidden" name="timezone" value="<?php echo esc_attr($general_settings['timezone']); ?>">
            <input type="hidden" name="min_age" value="<?php echo esc_attr($junior_settings['min_age']); ?>">
            <input type="hidden" name="max_age" value="<?php echo esc_attr($junior_settings['max_age']); ?>">
            
            <p class="submit">
                <button type="submit" name="save_settings" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span> Save Pricing Settings
                </button>
            </p>
        </form>
        
        <script>
        // Live preview update
        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.getElementById('subscription_price');
            const currencySelect = document.getElementById('currency');
            const symbolInput = document.getElementById('currency_symbol');
            const frequencySelect = document.getElementById('payment_frequency');
            const preview = document.getElementById('price-preview');
            
            function updatePreview() {
                const price = parseFloat(priceInput.value) || 0;
                const symbol = symbolInput.value || '';
                const currency = currencySelect.value || '';
                const frequency = frequencySelect.value || '';
                
                preview.textContent = symbol + ' ' + price.toFixed(2) + ' ' + currency + ' / ' + frequency;
            }
            
            priceInput.addEventListener('input', updatePreview);
            currencySelect.addEventListener('change', function() {
                // Auto-fill symbol based on currency
                const symbols = {
                    'KSH': 'KSh',
                    'USD': '$',
                    'EUR': '€',
                    'GBP': '£',
                    'ZAR': 'R',
                    'TZS': 'TSh',
                    'UGX': 'USh'
                };
                symbolInput.value = symbols[this.value] || this.value;
                updatePreview();
            });
            symbolInput.addEventListener('input', updatePreview);
            frequencySelect.addEventListener('change', updatePreview);
        });
        </script>
    </div>

    <!-- Tab: Test Data Management -->
    <div id="tab-test-data" class="jgk-tab-content">
        <div class="jgk-settings-section">
            <h2><span class="dashicons dashicons-database"></span> Test Data Management</h2>
            
            <?php if ($has_test_data): ?>
                <div class="warning-box">
                    <strong>⚠️ Warning:</strong> Test data detected in your database!<br>
                    <strong><?php echo $test_counts['members']; ?></strong> test member(s) found.
                </div>
            <?php else: ?>
                <div class="success-box">
                    <strong>✓ Clean Database:</strong> No test data detected.
                </div>
            <?php endif; ?>
            
            <!-- Generate Test Data Section -->
            <div style="margin-top: 30px;">
                <h3><span class="dashicons dashicons-plus-alt"></span> Generate Test Data</h3>
                <p>Create test members for development and testing purposes. Test members will be marked and can be deleted later.</p>
                
                <form method="post" action="" style="margin-top: 20px;">
                    <?php wp_nonce_field('jgk_test_data', 'jgk_test_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="test_count">Number of Test Members</label></th>
                            <td>
                                <input type="number" id="test_count" name="test_count" value="10" min="1" max="50" required style="width: 100px;">
                                <p class="description">Generate between 1 and 50 test members (recommended: 10).</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="generate_test_data" class="button jgk-button-success">
                            <span class="dashicons dashicons-plus"></span> Generate Test Members
                        </button>
                    </p>
                </form>
                
                <div class="warning-box" style="margin-top: 20px;">
                    <strong>What gets created:</strong>
                    <ul>
                        <li>✓ WordPress users with role "JGK Member"</li>
                        <li>✓ Member records with random data (names, ages, clubs, etc.)</li>
                        <li>✓ Parent/Guardian records for each member</li>
                        <li>✓ All test data is marked with "TEST-" prefix in membership numbers</li>
                        <li>✓ Test accounts use password: <code>TestPassword123!</code></li>
                        <li>✓ Test emails end with: <code>@testjgk.local</code></li>
                    </ul>
                </div>
            </div>
            
            <?php if ($has_test_data): ?>
            <!-- Delete Test Data Section -->
            <div style="margin-top: 50px; border-top: 3px solid #d63638; padding-top: 30px;">
                <h3 style="color: #d63638;">
                    <span class="dashicons dashicons-trash"></span> Go to Production Mode
                </h3>
                
                <div class="danger-box">
                    <strong>🚨 DANGER ZONE:</strong> This action will permanently delete ALL test data from your database!
                    <br><br>
                    <strong>This includes:</strong>
                    <ul>
                        <li>❌ All test user accounts</li>
                        <li>❌ All test member records</li>
                        <li>❌ All test parent/guardian records</li>
                        <li>❌ All coach assignments for test members</li>
                    </ul>
                    <strong style="color: #d63638;">⚠️ This action CANNOT be undone!</strong>
                </div>
                
                <form method="post" action="" onsubmit="return confirm('Are you absolutely sure you want to delete ALL test data? This action cannot be undone!');" style="margin-top: 20px;">
                    <?php wp_nonce_field('jgk_delete_test', 'jgk_delete_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="confirm_delete">Confirmation</label></th>
                            <td>
                                <input type="text" id="confirm_delete" name="confirm_delete" 
                                       placeholder='Type "DELETE" to confirm' 
                                       required style="width: 250px; font-weight: bold;">
                                <p class="description" style="color: #d63638;">
                                    Type <strong>DELETE</strong> (all caps) to enable deletion.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="delete_test_data" class="button jgk-button-danger button-large">
                            <span class="dashicons dashicons-warning"></span> Delete All Test Data & Go to Production
                        </button>
                    </p>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.nav-tab');
        const contents = document.querySelectorAll('.jgk-tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('nav-tab-active'));
                contents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('nav-tab-active');
                
                // Show corresponding content
                const tabName = this.getAttribute('data-tab');
                document.getElementById('tab-' + tabName).classList.add('active');
            });
        });
    });
    </script>
</div>
