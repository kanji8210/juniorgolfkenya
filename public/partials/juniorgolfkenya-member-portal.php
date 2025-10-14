<?php
/**
 * Member Portal - Frontend interface for members
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

// Check if user is logged in
if (!is_user_logged_in()) {
    $current_url = get_permalink();
    $login_url = wp_login_url($current_url);
    $register_url = home_url('/member-registration');
    ?>
    <div class="jgk-login-required">
        <div class="jgk-login-box">
            <div class="jgk-login-icon">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <h2>Login Required</h2>
            <p>You must be logged in to access the Member Portal.</p>
            <div class="jgk-login-actions">
                <a href="<?php echo esc_url($login_url); ?>" class="jgk-btn jgk-btn-primary">
                    <span class="dashicons dashicons-admin-users"></span>
                    Login to Your Account
                </a>
                <p class="jgk-or-divider">or</p>
                <a href="<?php echo esc_url($register_url); ?>" class="jgk-btn jgk-btn-secondary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Become a Member
                </a>
            </div>
            <p class="jgk-help-text">
                Need help? <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">Contact us</a>
            </p>
        </div>
    </div>
    
    <style>
        .jgk-login-required {
            max-width: 500px;
            margin: 80px auto;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
        }
        .jgk-login-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 0;
            text-align: center;
            overflow: hidden;
        }
        .jgk-login-icon {
            width: 100px;
            height: 100px;
            margin: -50px auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            color: white;
            font-size: 50px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .jgk-login-icon .dashicons {
            width: 50px;
            height: 50px;
            font-size: 50px;
        }
        .jgk-login-box h2 {
            margin: 0 0 15px 0;
            padding: 30px 40px 0;
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
        }
        .jgk-login-box > p {
            margin: 0 0 30px 0;
            padding: 0 40px;
            color: #7f8c8d;
            font-size: 16px;
        }
        .jgk-login-actions {
            padding: 30px 40px;
            background: #f8f9fa;
        }
        .jgk-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }
        .jgk-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .jgk-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .jgk-btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            margin-top: 10px;
        }
        .jgk-btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        .jgk-btn .dashicons {
            width: 20px;
            height: 20px;
            font-size: 20px;
        }
        .jgk-or-divider {
            margin: 20px 0;
            color: #95a5a6;
            font-size: 14px;
            position: relative;
        }
        .jgk-or-divider::before,
        .jgk-or-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #ddd;
        }
        .jgk-or-divider::before {
            left: 0;
        }
        .jgk-or-divider::after {
            right: 0;
        }
        .jgk-help-text {
            margin: 20px 0 0 0;
            padding: 0 40px 30px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .jgk-help-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .jgk-help-text a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .jgk-login-required {
                margin: 40px 15px;
            }
            .jgk-login-box h2 {
                padding: 20px 20px 0;
                font-size: 24px;
            }
            .jgk-login-box > p {
                padding: 0 20px;
                font-size: 15px;
            }
            .jgk-login-actions,
            .jgk-help-text {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
    <?php
    return;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get member data
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-member-data.php';

$member = JuniorGolfKenya_Database::get_member_by_user_id($user_id);

if (!$member) {
    echo '<p>Member profile not found. Please contact the administrator.</p>';
    return;
}

// Get membership status with expiration check
$membership_status = JuniorGolfKenya_Member_Data::get_membership_status($member);

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_member_portal')) {
        $message = 'Security check failed.';
        $message_type = 'error';
    } else {
        switch ($_POST['action']) {
            case 'update_coach':
                $new_coach_id = !empty($_POST['coach_id']) ? intval($_POST['coach_id']) : null;
                
                global $wpdb;
                $members_table = $wpdb->prefix . 'jgk_members';
                $result = $wpdb->update(
                    $members_table,
                    array('coach_id' => $new_coach_id),
                    array('id' => $member->id),
                    array('%d'),
                    array('%d')
                );
                
                if ($result !== false) {
                    $message = 'Coach assignment updated successfully!';
                    $message_type = 'success';
                    // Refresh member data
                    $member = JuniorGolfKenya_Database::get_member_by_user_id($user_id);
                } else {
                    $message = 'Failed to update coach assignment.';
                    $message_type = 'error';
                }
                break;
                
            case 'update_profile':
                global $wpdb;
                $members_table = $wpdb->prefix . 'jgk_members';
                
                $update_data = array(
                    'phone' => sanitize_text_field($_POST['phone']),
                    'emergency_contact_name' => sanitize_text_field($_POST['emergency_contact_name']),
                    'emergency_contact_phone' => sanitize_text_field($_POST['emergency_contact_phone']),
                    'medical_conditions' => sanitize_textarea_field($_POST['medical_conditions']),
                    'address' => sanitize_textarea_field($_POST['address'])
                );
                
                $result = $wpdb->update(
                    $members_table,
                    $update_data,
                    array('id' => $member->id)
                );
                
                if ($result !== false) {
                    $message = 'Profile updated successfully!';
                    $message_type = 'success';
                    // Refresh member data
                    $member = JuniorGolfKenya_Database::get_member_by_user_id($user_id);
                } else {
                    $message = 'Failed to update profile.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get available coaches
$coaches = get_users(array(
    'role' => 'jgf_coach',
    'orderby' => 'display_name',
    'order' => 'ASC'
));

// Get current coach info
$current_coach = null;
if (!empty($member->coach_id)) {
    $current_coach = get_userdata($member->coach_id);
}
?>

<div class="jgk-member-portal">
    <?php if ($message): ?>
    <div class="jgk-message jgk-message-<?php echo esc_attr($message_type); ?>">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <!-- Membership Status Alert -->
    <?php if ($membership_status['is_expired'] || $membership_status['is_expiring_soon']): ?>
    <div class="jgk-membership-alert" style="background-color: <?php echo esc_attr($membership_status['bg_color']); ?>; color: <?php echo esc_attr($membership_status['color']); ?>;">
        <div class="jgk-alert-icon">
            <span class="dashicons dashicons-<?php echo esc_attr($membership_status['icon']); ?>"></span>
        </div>
        <div class="jgk-alert-content">
            <h3><?php echo esc_html($membership_status['message']); ?></h3>
            <?php if ($membership_status['is_expired']): ?>
                <p>Your membership expired <?php echo abs($membership_status['days_remaining']); ?> days ago. Please renew to continue accessing member benefits.</p>
            <?php else: ?>
                <p>Your membership expires on <strong><?php echo esc_html($membership_status['expiry_date']); ?></strong>. Renew now to avoid interruption.</p>
            <?php endif; ?>
            <a href="#" class="jgk-renew-btn">Renew Membership</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="jgk-portal-header">
        <h2>Welcome, <?php echo esc_html($member->first_name . ' ' . $member->last_name); ?>!</h2>
        <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="jgk-logout-btn">
            <span class="dashicons dashicons-exit"></span> Logout
        </a>
    </div>

    <!-- Quick Access Cards -->
    <div class="jgk-quick-access">
        <?php 
        $dashboard_page_id = get_option('jgk_page_member_dashboard');
        $dashboard_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/member-dashboard');
        $portal_page_id = get_option('jgk_page_member_portal');
        $portal_url = $portal_page_id ? get_permalink($portal_page_id) : home_url('/member-portal');
        ?>
        <a href="<?php echo esc_url($dashboard_url); ?>" class="jgk-access-card jgk-card-primary">
            <div class="jgk-card-icon">
                <span class="dashicons dashicons-dashboard"></span>
            </div>
            <h3>My Dashboard</h3>
            <p>View your complete profile, performance, and statistics</p>
        </a>

        <a href="<?php echo esc_url($dashboard_url . '#competitions'); ?>" class="jgk-access-card jgk-card-success">
            <div class="jgk-card-icon">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <h3>Competitions</h3>
            <p>Browse upcoming events and view past results</p>
        </a>

        <a href="<?php echo esc_url($dashboard_url . '#trophies'); ?>" class="jgk-access-card jgk-card-warning">
            <div class="jgk-card-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <h3>My Trophies</h3>
            <p>View your achievements and awards</p>
        </a>

        <a href="<?php echo esc_url($portal_url . '#edit-profile'); ?>" class="jgk-access-card jgk-card-info">
            <div class="jgk-card-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <h3>Edit Profile</h3>
            <p>Update your contact information and preferences</p>
        </a>
    </div>
    
    <!-- Member Info Card -->
    <div class="jgk-card">
        <h3>Member Information</h3>
        <div class="jgk-info-grid">
            <div class="jgk-info-item">
                <strong>Membership Number:</strong>
                <span><?php echo esc_html($member->membership_number); ?></span>
            </div>
            <div class="jgk-info-item">
                <strong>Membership Type:</strong>
                <span><?php echo esc_html(ucfirst($member->membership_type)); ?></span>
            </div>
            <div class="jgk-info-item">
                <strong>Status:</strong>
                <span class="jgk-status-<?php echo esc_attr($member->status); ?>">
                    <?php echo esc_html(ucfirst($member->status)); ?>
                </span>
            </div>
            <div class="jgk-info-item">
                <strong>Handicap:</strong>
                <span><?php echo esc_html($member->handicap ?? 'N/A'); ?></span>
            </div>
        </div>
    </div>

    <!-- Coach Assignment Section -->
    <div class="jgk-card">
        <h3>Your Coach</h3>
        
        <?php if ($current_coach): ?>
        <div class="jgk-current-coach">
            <p><strong>Current Coach:</strong> <?php echo esc_html($current_coach->display_name); ?></p>
            <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($current_coach->user_email); ?>"><?php echo esc_html($current_coach->user_email); ?></a></p>
        </div>
        <?php else: ?>
        <p class="jgk-no-coach">You don't have a coach assigned yet.</p>
        <?php endif; ?>

        <form method="post" class="jgk-form">
            <?php wp_nonce_field('jgk_member_portal'); ?>
            <input type="hidden" name="action" value="update_coach">
            
            <div class="jgk-form-field">
                <label for="coach_id">
                    <?php echo $current_coach ? 'Change Coach:' : 'Select a Coach:'; ?>
                </label>
                <select id="coach_id" name="coach_id">
                    <option value="">No coach</option>
                    <?php foreach ($coaches as $coach): ?>
                    <option value="<?php echo esc_attr($coach->ID); ?>" 
                            <?php selected($member->coach_id ?? 0, $coach->ID); ?>>
                        <?php echo esc_html($coach->display_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="jgk-button jgk-button-primary">
                <?php echo $current_coach ? 'Update Coach' : 'Assign Coach'; ?>
            </button>
        </form>
    </div>

    <!-- Profile Update Section -->
    <div id="edit-profile" class="jgk-card">
        <h3>Update Your Information</h3>
        
        <form method="post" class="jgk-form">
            <?php wp_nonce_field('jgk_member_portal'); ?>
            <input type="hidden" name="action" value="update_profile">
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo esc_attr($member->phone ?? ''); ?>" 
                           placeholder="+254...">
                </div>
            </div>
            
            <h4>Emergency Contact</h4>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" 
                           value="<?php echo esc_attr($member->emergency_contact_name ?? ''); ?>">
                </div>
                <div class="jgk-form-field">
                    <label for="emergency_contact_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" 
                           value="<?php echo esc_attr($member->emergency_contact_phone ?? ''); ?>">
                </div>
            </div>
            
            <div class="jgk-form-field">
                <label for="medical_conditions">Medical Conditions</label>
                <textarea id="medical_conditions" name="medical_conditions" rows="3"><?php echo esc_textarea($member->medical_conditions ?? ''); ?></textarea>
                <small>Please list any medical conditions or allergies we should be aware of.</small>
            </div>
            
            <div class="jgk-form-field">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo esc_textarea($member->address ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="jgk-button jgk-button-primary">Update Profile</button>
        </form>
    </div>
</div>

<style>
.jgk-member-portal {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.jgk-portal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.jgk-portal-header h2 {
    margin: 0;
    color: #2c3e50;
    font-size: 28px;
}

.jgk-logout-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.jgk-logout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    color: #fff;
    text-decoration: none;
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

.jgk-logout-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.jgk-logout-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Membership Alert */
.jgk-membership-alert {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    border-left: 5px solid currentColor;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.jgk-alert-icon {
    font-size: 48px;
    line-height: 1;
}

.jgk-alert-icon .dashicons {
    width: 48px;
    height: 48px;
    font-size: 48px;
}

.jgk-alert-content {
    flex: 1;
}

.jgk-alert-content h3 {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 700;
}

.jgk-alert-content p {
    margin: 0 0 12px 0;
    font-size: 15px;
    line-height: 1.5;
}

.jgk-renew-btn {
    display: inline-block;
    padding: 10px 20px;
    background: currentColor;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    transition: opacity 0.3s ease;
}

.jgk-renew-btn:hover {
    opacity: 0.8;
    color: white;
    text-decoration: none;
}

/* Quick Access Cards */
.jgk-quick-access {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.jgk-access-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    color: #2c3e50;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.jgk-access-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    text-decoration: none;
    color: #2c3e50;
}

.jgk-card-primary:hover {
    border-color: #667eea;
}

.jgk-card-success:hover {
    border-color: #28a745;
}

.jgk-card-warning:hover {
    border-color: #ffc107;
}

.jgk-card-info:hover {
    border-color: #17a2b8;
}

.jgk-card-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 36px;
}

.jgk-card-primary .jgk-card-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.jgk-card-success .jgk-card-icon {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.jgk-card-warning .jgk-card-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ff6f00 100%);
    color: white;
}

.jgk-card-info .jgk-card-icon {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.jgk-card-icon .dashicons {
    width: 36px;
    height: 36px;
    font-size: 36px;
}

.jgk-access-card h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
}

.jgk-access-card p {
    margin: 0;
    font-size: 14px;
    color: #7f8c8d;
    line-height: 1.5;
}

.jgk-message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    border-left: 4px solid;
}

.jgk-message-success {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.jgk-message-error {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

.jgk-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.jgk-card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.jgk-card h4 {
    margin-top: 25px;
    margin-bottom: 15px;
    color: #34495e;
}

.jgk-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.jgk-info-item {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 5px;
}

.jgk-info-item strong {
    display: block;
    color: #6c757d;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.jgk-info-item span {
    font-size: 1.1em;
    color: #2c3e50;
}

.jgk-status-active {
    color: #28a745;
    font-weight: bold;
}

.jgk-status-pending {
    color: #ffc107;
    font-weight: bold;
}

.jgk-status-expired {
    color: #dc3545;
    font-weight: bold;
}

.jgk-current-coach {
    background: #e3f2fd;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.jgk-current-coach p {
    margin: 8px 0;
}

.jgk-no-coach {
    color: #6c757d;
    font-style: italic;
    margin-bottom: 20px;
}

.jgk-form {
    margin-top: 20px;
}

.jgk-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.jgk-form-field {
    margin-bottom: 20px;
}

.jgk-form-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
}

.jgk-form-field input[type="text"],
.jgk-form-field input[type="tel"],
.jgk-form-field input[type="email"],
.jgk-form-field select,
.jgk-form-field textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 1em;
    transition: border-color 0.15s;
}

.jgk-form-field input:focus,
.jgk-form-field select:focus,
.jgk-form-field textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.jgk-form-field small {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 0.9em;
}

.jgk-button {
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    font-size: 1em;
    cursor: pointer;
    transition: background-color 0.2s;
}

.jgk-button-primary {
    background: #3498db;
    color: white;
}

.jgk-button-primary:hover {
    background: #2980b9;
}

@media (max-width: 768px) {
    .jgk-portal-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .jgk-portal-header h2 {
        font-size: 22px;
    }
    
    .jgk-logout-btn {
        width: 100%;
        justify-content: center;
    }
    
    .jgk-membership-alert {
        flex-direction: column;
        text-align: center;
    }
    
    .jgk-quick-access {
        grid-template-columns: 1fr;
    }
    
    .jgk-info-grid,
    .jgk-form-row {
        grid-template-columns: 1fr;
    }
}
</style>
