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
    echo '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to access the member portal.</p>';
    return;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get member data
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
$member = JuniorGolfKenya_Database::get_member_by_user_id($user_id);

if (!$member) {
    echo '<p>Member profile not found. Please contact the administrator.</p>';
    return;
}

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

    <h2>Welcome, <?php echo esc_html($member->first_name . ' ' . $member->last_name); ?>!</h2>
    
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
    <div class="jgk-card">
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
    .jgk-info-grid,
    .jgk-form-row {
        grid-template-columns: 1fr;
    }
}
</style>
