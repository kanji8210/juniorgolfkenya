<?php
/**
 * Coach role request form
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

global $wpdb;
$current_user = wp_get_current_user();
$user_id = get_current_user_id();
?>

<div class="jgk-coach-request-form">
    <div class="jgk-form-header">
        <h2>Apply to Become a Coach</h2>
        <p>Join our team of professional golf coaches and help develop the next generation of golfers</p>
    </div>

    <?php if (is_user_logged_in()): ?>
        
        <?php
        // Check if user is already a coach
        if (in_array('jgk_coach', $current_user->roles)): ?>
            <div class="jgk-notice jgk-notice-info">
                <p><strong>You already have coach access!</strong></p>
                <p><a href="<?php echo home_url('/coach-dashboard'); ?>">Go to Coach Dashboard</a></p>
            </div>
        <?php else:
            // Check if already submitted a request
            $role_requests_table = $wpdb->prefix . 'jgf_role_requests';
            $existing_request = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$role_requests_table} WHERE requester_user_id = %d ORDER BY created_at DESC LIMIT 1",
                $user_id
            ));
            
            if ($existing_request && $existing_request->status === 'pending'): ?>
                <div class="jgk-notice jgk-notice-warning">
                    <p><strong>You have a pending coach role request. We will review it soon!</strong></p>
                    <p><strong>Submitted:</strong> <?php echo date('F j, Y', strtotime($existing_request->created_at)); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($existing_request->status); ?></p>
                </div>
            <?php else: ?>
    
    <form method="post" id="jgk-coach-request-form" class="jgk-form">
        <?php wp_nonce_field('jgk_coach_request_action', 'jgk_coach_request_nonce'); ?>
        <input type="hidden" name="action" value="jgk_submit_coach_request">
        
        <div class="jgk-form-section">
            <h3>Personal Information</h3>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($current_user->user_firstname); ?>" required>
                </div>
                <div class="jgk-form-field">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($current_user->user_lastname); ?>" required>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" readonly>
                </div>
                <div class="jgk-form-field">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" placeholder="+254..." required>
                </div>
            </div>
        </div>
        
        <div class="jgk-form-section">
            <h3>Coaching Experience</h3>
            
            <div class="jgk-form-field">
                <label for="years_experience">Years of Coaching Experience *</label>
                <select id="years_experience" name="years_experience" required>
                    <option value="">Select...</option>
                    <option value="0-1">Less than 1 year</option>
                    <option value="1-3">1-3 years</option>
                    <option value="3-5">3-5 years</option>
                    <option value="5-10">5-10 years</option>
                    <option value="10+">10+ years</option>
                </select>
            </div>
            
            <div class="jgk-form-field">
                <label for="specialization">Specialization</label>
                <input type="text" id="specialization" name="specialization" placeholder="e.g., Junior Golf, Swing Technique, Mental Game">
            </div>
            
            <div class="jgk-form-field">
                <label for="certifications">Certifications & Qualifications *</label>
                <textarea id="certifications" name="certifications" rows="4" placeholder="List your coaching certifications, PGA qualifications, or relevant training..." required></textarea>
            </div>
            
            <div class="jgk-form-field">
                <label for="experience">Coaching Experience Details *</label>
                <textarea id="experience" name="experience" rows="5" placeholder="Describe your coaching experience, achievements, and approach to coaching junior golfers..." required></textarea>
            </div>
        </div>
        
        <div class="jgk-form-section">
            <h3>References</h3>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="reference_name">Reference Name</label>
                    <input type="text" id="reference_name" name="reference_name" placeholder="Full name">
                </div>
                <div class="jgk-form-field">
                    <label for="reference_contact">Reference Contact</label>
                    <input type="text" id="reference_contact" name="reference_contact" placeholder="Phone or email">
                </div>
            </div>
        </div>
        
        <div class="jgk-form-field">
            <label>
                <input type="checkbox" name="agree_terms" required>
                I agree to the Junior Golf Kenya coaching terms and conditions *
            </label>
        </div>
        
        <div class="jgk-form-actions">
            <button type="submit" class="jgk-btn jgk-btn-primary">Submit Application</button>
        </div>
    </form>
    
    <?php 
            endif;
        endif;
    else: ?>
        <div class="jgk-notice jgk-notice-warning">
            <p><strong>You must be logged in to apply as a coach.</strong></p>
            <p><a href="<?php echo wp_login_url(get_permalink()); ?>" class="jgk-btn jgk-btn-primary">Login</a> | 
               <a href="<?php echo home_url('/member-registration'); ?>" class="jgk-btn jgk-btn-secondary">Register</a></p>
        </div>
    <?php endif; ?>
</div>

<style>
.jgk-coach-request-form {
    max-width: 800px;
    margin: 40px auto;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.jgk-form-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.jgk-form-header h2 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.jgk-form-header p {
    color: #7f8c8d;
    font-size: 16px;
}

.jgk-form-section {
    margin-bottom: 30px;
}

.jgk-form-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.jgk-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
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
    color: #2c3e50;
}

.jgk-form-field input[type="text"],
.jgk-form-field input[type="email"],
.jgk-form-field input[type="tel"],
.jgk-form-field select,
.jgk-form-field textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 15px;
    transition: border-color 0.3s;
}

.jgk-form-field input:focus,
.jgk-form-field select:focus,
.jgk-form-field textarea:focus {
    outline: none;
    border-color: #667eea;
}

.jgk-form-field input[readonly] {
    background: #f8f9fa;
    cursor: not-allowed;
}

.jgk-form-actions {
    margin-top: 30px;
    text-align: center;
}

.jgk-btn {
    display: inline-block;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
}

.jgk-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.jgk-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.jgk-btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.jgk-btn-secondary:hover {
    background: #667eea;
    color: white;
}

.jgk-notice {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.jgk-notice-info {
    background: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #0c5460;
}

.jgk-notice-warning {
    background: #fff3cd;
    color: #856404;
    border-left: 4px solid #856404;
}

@media (max-width: 768px) {
    .jgk-coach-request-form {
        padding: 20px;
    }
    
    .jgk-form-row {
        grid-template-columns: 1fr;
    }
}
</style>
