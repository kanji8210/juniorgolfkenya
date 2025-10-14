<?php
/**
 * Member Registration Form - Public View
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

// Handle form submission
$registration_success = false;
$registration_errors = array();

if (isset($_POST['jgk_register_member'])) {
    // Verify nonce
    if (!isset($_POST['jgk_register_nonce']) || !wp_verify_nonce($_POST['jgk_register_nonce'], 'jgk_member_registration')) {
        $registration_errors[] = 'Security check failed. Please try again.';
    } else {
        // Sanitize and validate input
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? '');
        $gender = sanitize_text_field($_POST['gender'] ?? '');
        $membership_type = 'junior'; // Forced: junior program only (2-17 years)
        $club_affiliation = sanitize_text_field($_POST['club_affiliation'] ?? '');
        $address = sanitize_textarea_field($_POST['address'] ?? '');
        $medical_conditions = sanitize_textarea_field($_POST['medical_conditions'] ?? '');
        $emergency_contact_name = sanitize_text_field($_POST['emergency_contact_name'] ?? '');
        $emergency_contact_phone = sanitize_text_field($_POST['emergency_contact_phone'] ?? '');
        $handicap = floatval($_POST['handicap'] ?? 0);
        $consent_photography = isset($_POST['consent_photography']) ? 'yes' : 'no';
        $parental_consent = isset($_POST['parental_consent']) ? 1 : 0;
        
        // Parent/Guardian information (for minors)
        $parent_first_name = sanitize_text_field($_POST['parent_first_name'] ?? '');
        $parent_last_name = sanitize_text_field($_POST['parent_last_name'] ?? '');
        $parent_email = sanitize_email($_POST['parent_email'] ?? '');
        $parent_phone = sanitize_text_field($_POST['parent_phone'] ?? '');
        $parent_relationship = sanitize_text_field($_POST['parent_relationship'] ?? '');
        
        // Validate required fields
        if (empty($first_name)) {
            $registration_errors[] = 'First name is required.';
        }
        if (empty($last_name)) {
            $registration_errors[] = 'Last name is required.';
        }
        if (empty($email) || !is_email($email)) {
            $registration_errors[] = 'Valid email address is required.';
        }
        if (empty($password)) {
            $registration_errors[] = 'Password is required.';
        }
        if (strlen($password) < 8) {
            $registration_errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirm_password) {
            $registration_errors[] = 'Passwords do not match.';
        }
        
        // Age validation (2-17 years) - REQUIRED for juniors
        if (empty($date_of_birth)) {
            $registration_errors[] = 'Date of birth is required to verify eligibility.';
        } else {
            try {
                $birthdate = new DateTime($date_of_birth);
                $today = new DateTime();
                $age = $today->diff($birthdate)->y;
                
                if ($age < 2) {
                    $registration_errors[] = 'The minimum age for registration is 2 years.';
                }
                
                if ($age >= 18) {
                    $registration_errors[] = 'This program is reserved for juniors under 18 years old. If you are 18 years or older, please contact us directly.';
                }
            } catch (Exception $e) {
                $registration_errors[] = 'Invalid date of birth format.';
            }
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            $registration_errors[] = 'This email address is already registered.';
        }
        
        // Parent/guardian information REQUIRED for all juniors
        if (empty($parent_first_name) || empty($parent_last_name)) {
            $registration_errors[] = 'Parent/guardian information is required (first name and last name).';
        }
        
        if (empty($parent_email) && empty($parent_phone)) {
            $registration_errors[] = 'At least one parent contact method is required (email or phone).';
        }
        
        if (empty($parent_relationship)) {
            $registration_errors[] = 'Veuillez indiquer votre relation avec l\'enfant (mère, père, tuteur...).';
        }
        
        // If no errors, proceed with registration
        if (empty($registration_errors)) {
            global $wpdb;
            
            // Create WordPress user account
            $username = sanitize_user($first_name . '.' . $last_name . rand(100, 999));
            
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                $registration_errors[] = 'Failed to create user account: ' . $user_id->get_error_message();
            } else {
                // Update user meta
                wp_update_user(array(
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'display_name' => $first_name . ' ' . $last_name,
                ));
                
                // Assign jgk_member role
                $user = new WP_User($user_id);
                $user->set_role('jgk_member');
                
                // Generate membership number
                $membership_number = 'JGK-' . date('Y') . '-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                
                // Insert into members table
                $members_table = $wpdb->prefix . 'jgk_members';
                $insert_result = $wpdb->insert(
                    $members_table,
                    array(
                        'user_id' => $user_id,
                        'membership_number' => $membership_number,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'date_of_birth' => !empty($date_of_birth) ? $date_of_birth : null,
                        'gender' => $gender,
                        'phone' => $phone,
                        'address' => $address,
                        'membership_type' => $membership_type,
                        'status' => 'active', // Active immediately - no approval needed
                        'date_joined' => current_time('mysql'),
                        'expiry_date' => date('Y-m-d', strtotime('+1 year')),
                        'club_affiliation' => $club_affiliation,
                        'handicap' => $handicap,
                        'medical_conditions' => $medical_conditions,
                        'emergency_contact_name' => $emergency_contact_name,
                        'emergency_contact_phone' => $emergency_contact_phone,
                        'consent_photography' => $consent_photography,
                        'parental_consent' => $parental_consent,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ),
                    array(
                        '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%s'
                    )
                );
                
                if ($insert_result === false) {
                    $registration_errors[] = 'Failed to save member information.';
                    wp_delete_user($user_id);
                } else {
                    $member_id = $wpdb->insert_id;
                    
                    // Insert parent/guardian information if provided
                    if (!empty($parent_first_name) && !empty($parent_last_name)) {
                        $parents_table = $wpdb->prefix . 'jgk_parents_guardians';
                        $wpdb->insert(
                            $parents_table,
                            array(
                                'member_id' => $member_id,
                                'relationship' => !empty($parent_relationship) ? $parent_relationship : 'parent',
                                'first_name' => $parent_first_name,
                                'last_name' => $parent_last_name,
                                'email' => $parent_email,
                                'phone' => $parent_phone,
                                'is_primary_contact' => 1,
                                'emergency_contact' => 1,
                                'created_at' => current_time('mysql'),
                            ),
                            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
                        );
                    }
                    
                    // Send notification email to user
                    $to = $email;
                    $subject = 'Welcome to Junior Golf Kenya - Account Created Successfully';
                    
                    // Get dashboard URL from saved page ID
                    $dashboard_page_id = get_option('jgk_page_member_dashboard');
                    $dashboard_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/member-dashboard');
                    
                    $message = "Dear {$first_name} {$last_name},\n\n";
                    $message .= "Welcome to Junior Golf Kenya! Your account has been created successfully.\n\n";
                    $message .= "Membership Details:\n";
                    $message .= "- Membership Number: {$membership_number}\n";
                    $message .= "- Username: {$username}\n";
                    $message .= "- Email: {$email}\n\n";
                    $message .= "You can now log in and access your member dashboard:\n";
                    $message .= "Login URL: " . wp_login_url() . "\n\n";
                    $message .= "Dashboard URL: " . $dashboard_url . "\n\n";
                    $message .= "Your membership is active and valid until " . date('F j, Y', strtotime('+1 year')) . ".\n\n";
                    $message .= "If you have any questions, please don't hesitate to contact us.\n\n";
                    $message .= "Best regards,\n";
                    $message .= "Junior Golf Kenya Team";
                    
                    wp_mail($to, $subject, $message);
                    
                    // Send notification to admin
                    $admin_email = get_option('admin_email');
                    $admin_subject = 'New Member Registration - Junior Golf Kenya';
                    $admin_message = "A new member has registered:\n\n";
                    $admin_message .= "Name: {$first_name} {$last_name}\n";
                    $admin_message .= "Email: {$email}\n";
                    $admin_message .= "Membership Number: {$membership_number}\n";
                    $admin_message .= "Membership Type: " . ucfirst($membership_type) . "\n";
                    $admin_message .= "Status: Active (immediate access granted)\n\n";
                    $admin_message .= "View member details in the admin panel:\n";
                    $admin_message .= admin_url('admin.php?page=juniorgolfkenya-members&action=edit&id=' . $member_id);
                    
                    wp_mail($admin_email, $admin_subject, $admin_message);
                    
                    // Auto-login the user after successful registration
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);
                    
                    // Redirect to Member Portal after successful registration
                    $portal_page_id = get_option('jgk_page_member_portal');
                    if ($portal_page_id) {
                        $redirect_url = get_permalink($portal_page_id);
                    } else {
                        // Fallback to member dashboard if portal not found
                        $dashboard_page_id = get_option('jgk_page_member_dashboard');
                        $redirect_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/member-portal');
                    }
                    
                    // Perform redirect
                    wp_redirect($redirect_url);
                    exit;
                }
            }
        }
    }
}
?>

<div class="jgk-registration-form">
    <?php if ($registration_success): ?>
        <!-- Success Message -->
        <div class="jgk-registration-success">
            <div class="jgk-success-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <h2>Welcome to Junior Golf Kenya!</h2>
            <p>Your account has been created successfully.</p>
            <div class="jgk-success-details">
                <p><strong>Congratulations!</strong> Your membership is now active and ready to use.</p>
                <p><strong>Membership Number:</strong> You will find this in your dashboard.</p>
                <p>You can now access your member dashboard to:</p>
                <ul>
                    <li>✅ View your membership details</li>
                    <li>✅ Connect with your assigned coaches</li>
                    <li>✅ Access training schedules and events</li>
                    <li>✅ Update your profile information</li>
                    <li>✅ Track your progress and achievements</li>
                </ul>
                <p>A confirmation email has been sent to <strong><?php echo esc_html($email ?? ''); ?></strong> with your login details.</p>
            </div>
            <div class="jgk-success-actions">
                <?php 
                // Get dashboard URL from saved page ID
                $dashboard_page_id = get_option('jgk_page_member_dashboard');
                $dashboard_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/member-dashboard');
                ?>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="jgk-btn jgk-btn-primary jgk-btn-large">
                    <span class="dashicons dashicons-dashboard"></span>
                    Go to My Dashboard
                </a>
                <a href="<?php echo home_url(); ?>" class="jgk-btn jgk-btn-secondary">Return to Home</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Registration Form -->
        <div class="jgk-form-header">
            <h2>Member Registration</h2>
            <p>Join Junior Golf Kenya and become part of our golfing community.</p>
        </div>

        <?php if (!empty($registration_errors)): ?>
            <div class="jgk-form-errors">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong>Please correct the following errors:</strong>
                    <ul>
                        <?php foreach ($registration_errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="jgk-member-form">
            <?php wp_nonce_field('jgk_member_registration', 'jgk_register_nonce'); ?>

            <!-- Personal Information -->
            <div class="jgk-form-section">
                <h3><span class="dashicons dashicons-admin-users"></span> Personal Information</h3>
                
                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($_POST['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="jgk-form-field">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($_POST['last_name'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" required>
                        <small>This will be used for your login credentials</small>
                    </div>
                    <div class="jgk-form-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>" placeholder="+254...">
                    </div>
                </div>

                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required minlength="8">
                        <small>Minimum 8 characters</small>
                    </div>
                    <div class="jgk-form-field">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                        <small>Re-enter your password</small>
                    </div>
                </div>

                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="date_of_birth">Date of birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" 
                               value="<?php echo esc_attr($_POST['date_of_birth'] ?? ''); ?>" 
                               required 
                               max="<?php echo date('Y-m-d', strtotime('-2 years')); ?>"
                               min="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                        <small style="color: #666;">The child must be between 2 and 17 years old</small>
                        <div id="age-validation-message" style="margin-top: 10px;"></div>
                    </div>
                    <div class="jgk-form-field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?php selected($_POST['gender'] ?? '', 'male'); ?>>Male</option>
                            <option value="female" <?php selected($_POST['gender'] ?? '', 'female'); ?>>Female</option>
                            <option value="other" <?php selected($_POST['gender'] ?? '', 'other'); ?>>Other</option>
                            <option value="prefer_not_to_say" <?php selected($_POST['gender'] ?? '', 'prefer_not_to_say'); ?>>Prefer not to say</option>
                        </select>
                    </div>
                </div>

                <div class="jgk-form-row">
                    <div class="jgk-form-field jgk-form-field-full">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo esc_textarea($_POST['address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Membership Details -->
            <div class="jgk-form-section jgk-membership-section">
                <h3><span class="dashicons dashicons-id-alt"></span> Membership Details</h3>
                
                <div class="jgk-form-row">
                    <div class="jgk-form-field jgk-form-field-full">
                        <div class="jgk-membership-info">
                            <h4>Junior Golf Kenya Membership</h4>
                            <p>Join Kenya's premier junior golf development program. Our membership includes access to coaching, tournaments, and exclusive training facilities.</p>
                            <span class="jgk-membership-price">KES 5,000 / Year</span>
                        </div>
                    </div>
                </div>
                
                <div class="jgk-form-row">
                        <label for="club_affiliation">Club Affiliation</label>
                        <input type="text" id="club_affiliation" name="club_affiliation" value="<?php echo esc_attr($_POST['club_affiliation'] ?? ''); ?>" placeholder="Your golf club (if any)">
                    </div>
                </div>

                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="handicap">Golf Handicap (Optional)</label>
                        <input type="number" id="handicap" name="handicap" step="0.1" min="0" max="54" value="<?php echo esc_attr($_POST['handicap'] ?? ''); ?>" placeholder="e.g., 18.5">
                        <small>Leave blank if you don't have a handicap yet</small>
                    </div>
                    <div class="jgk-form-field">
                        <label for="medical_conditions">Medical Conditions</label>
                        <textarea id="medical_conditions" name="medical_conditions" rows="2" placeholder="Any medical conditions we should be aware of..."><?php echo esc_textarea($_POST['medical_conditions'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Parent/Guardian Information (for minors) -->
            <div class="jgk-form-section" id="parent-section" style="display: block;">
                <h3><span class="dashicons dashicons-groups"></span> Parent/Guardian Information</h3>
                <p class="jgk-section-description" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; color: #856404; margin: 10px 0 20px 0; border-radius: 4px;">
                    <strong>⚠️ Required</strong> - Parent or legal guardian information is required for all junior members.
                </p>
                
                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="parent_first_name">Parent/Guardian First Name *</label>
                        <input type="text" id="parent_first_name" name="parent_first_name" value="<?php echo esc_attr($_POST['parent_first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="jgk-form-field">
                        <label for="parent_last_name">Parent/Guardian Last Name *</label>
                        <input type="text" id="parent_last_name" name="parent_last_name" value="<?php echo esc_attr($_POST['parent_last_name'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="parent_email">Parent/Guardian Email *</label>
                        <input type="email" id="parent_email" name="parent_email" value="<?php echo esc_attr($_POST['parent_email'] ?? ''); ?>">
                        <small>At least email OR phone required</small>
                    </div>
                    <div class="jgk-form-field">
                        <label for="parent_phone">Parent/Guardian Phone *</label>
                        <input type="tel" id="parent_phone" name="parent_phone" value="<?php echo esc_attr($_POST['parent_phone'] ?? ''); ?>" placeholder="+254...">
                        <small>At least email OR phone required</small>
                    </div>
                </div>

                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="parent_relationship">Relationship *</label>
                        <select id="parent_relationship" name="parent_relationship" required>
                            <option value="">Select Relationship</option>
                            <option value="mother" <?php selected($_POST['parent_relationship'] ?? '', 'mother'); ?>>Mother</option>
                            <option value="father" <?php selected($_POST['parent_relationship'] ?? '', 'father'); ?>>Father</option>
                            <option value="guardian" <?php selected($_POST['parent_relationship'] ?? '', 'guardian'); ?>>Guardian</option>
                            <option value="other" <?php selected($_POST['parent_relationship'] ?? '', 'other'); ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="jgk-form-section">
                <h3><span class="dashicons dashicons-sos"></span> Emergency Contact</h3>
                
                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="emergency_contact_name">Emergency Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo esc_attr($_POST['emergency_contact_name'] ?? ''); ?>">
                    </div>
                    <div class="jgk-form-field">
                        <label for="emergency_contact_phone">Emergency Contact Phone</label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo esc_attr($_POST['emergency_contact_phone'] ?? ''); ?>" placeholder="+254...">
                    </div>
                </div>
            </div>

            <!-- Consent & Agreements -->
            <div class="jgk-form-section">
                <h3><span class="dashicons dashicons-yes"></span> Consent & Agreements</h3>
                
                <div class="jgk-form-checkbox">
                    <label>
                        <input type="checkbox" name="consent_photography" value="1" <?php checked(isset($_POST['consent_photography'])); ?>>
                        <span>I consent to photography and use of images for promotional purposes</span>
                    </label>
                </div>

                <div class="jgk-form-checkbox">
                    <label>
                        <input type="checkbox" name="parental_consent" value="1" <?php checked(isset($_POST['parental_consent'])); ?>>
                        <span>I give parental consent for my child under 18 to participate in the Junior Golf Kenya program</span>
                    </label>
                </div>

                <div class="jgk-form-checkbox">
                    <label>
                        <input type="checkbox" name="terms_conditions" required>
                        <span>I agree to the <a href="#" target="_blank">Terms & Conditions</a> and <a href="#" target="_blank">Privacy Policy</a> *</span>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="jgk-form-submit">
                <button type="submit" name="jgk_register_member" class="jgk-btn jgk-btn-primary jgk-btn-large">
                    <span class="dashicons dashicons-yes"></span>
                    Complete Registration
                </button>
                <p class="jgk-form-note">
                    <span class="dashicons dashicons-info"></span>
                    Your registration will be reviewed by our team and you'll receive a confirmation email once approved.
                </p>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
/* Minimalist Modern Registration Form */
.jgk-registration-form {
    max-width: 800px;
    margin: 40px auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Form Header */
.jgk-form-header {
    text-align: center;
    padding: 60px 40px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
}

.jgk-form-header h2 {
    margin: 0 0 12px 0;
    font-size: 28px;
    font-weight: 600;
    color: #111827;
    letter-spacing: -0.025em;
}

.jgk-form-header p {
    margin: 0;
    font-size: 16px;
    color: #6b7280;
    line-height: 1.5;
}

/* Error Messages */
.jgk-form-errors {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 8px;
    margin: 24px 0;
}

.jgk-form-errors .dashicons {
    font-size: 20px;
    margin-top: 2px;
    color: #dc2626;
}

.jgk-form-errors strong {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
}

.jgk-form-errors ul {
    margin: 0;
    padding-left: 16px;
}

.jgk-form-errors li {
    margin-bottom: 4px;
    line-height: 1.4;
}

/* Form Container */
.jgk-member-form {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}

/* Form Sections */
.jgk-form-section {
    padding: 48px 40px;
    border-bottom: 1px solid #f3f4f6;
}

.jgk-form-section:last-of-type {
    border-bottom: none;
}

.jgk-form-section h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 32px 0;
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    letter-spacing: -0.025em;
}

.jgk-form-section h3 .dashicons {
    font-size: 20px;
    color: #3b82f6;
}

.jgk-section-description {
    margin: -16px 0 32px 0;
    padding: 16px 20px;
    background: #fefce8;
    border: 1px solid #fde047;
    border-radius: 8px;
    color: #a16207;
    font-size: 14px;
    line-height: 1.5;
}

/* Membership Details Section */
.jgk-membership-section {
    padding: 48px 40px;
    background: #f9fafb;
    border-bottom: 1px solid #f3f4f6;
}

/* Shorten handicap and medical condition inputs */
.jgk-membership-section .jgk-form-field input[type="number"],
.jgk-membership-section .jgk-form-field textarea {
    max-width: 200px;
}

/* Form Rows */
.jgk-form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 24px;
}

.jgk-form-row:last-child {
    margin-bottom: 0;
}

/* Form Fields */
.jgk-form-field {
    display: flex;
    flex-direction: column;
    position: relative;
}

.jgk-form-field-full {
    grid-column: 1 / -1;
}

.jgk-form-field label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    letter-spacing: -0.01em;
}

.jgk-form-field input[type="text"],
.jgk-form-field input[type="email"],
.jgk-form-field input[type="tel"],
.jgk-form-field input[type="date"],
.jgk-form-field input[type="number"],
.jgk-form-field input[type="password"],
.jgk-form-field select,
.jgk-form-field textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    background: #ffffff;
    color: #111827;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.jgk-form-field input:focus,
.jgk-form-field select:focus,
.jgk-form-field textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.jgk-form-field input:hover,
.jgk-form-field select:hover,
.jgk-form-field textarea:hover {
    border-color: #b8c6db;
}

.jgk-form-field small {
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
}

/* Checkboxes */
.jgk-form-checkbox {
    margin-bottom: 16px;
    padding: 16px 20px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.jgk-form-checkbox label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
    line-height: 1.5;
    margin: 0;
}

.jgk-form-checkbox input[type="checkbox"] {
    margin-top: 2px;
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #3b82f6;
}

.jgk-form-checkbox a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.jgk-form-checkbox a:hover {
    text-decoration: underline;
}

/* Submit Section */
.jgk-form-submit {
    padding: 48px 40px;
    text-align: center;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.jgk-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.jgk-btn-primary {
    background: #3b82f6;
    color: white;
}

.jgk-btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.jgk-btn-secondary {
    background: #6b7280;
    color: white;
}

.jgk-btn-secondary:hover {
    background: #4b5563;
}

.jgk-btn-large {
    padding: 16px 40px;
    font-size: 16px;
}

.jgk-form-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 16px 0 0 0;
    font-size: 14px;
    color: #6b7280;
    padding: 12px 16px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

/* Success Message */
.jgk-registration-success {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 64px 40px;
    text-align: center;
    margin: 40px auto;
    max-width: 800px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.jgk-success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #10b981;
    border-radius: 50%;
    color: white;
    font-size: 40px;
}

.jgk-registration-success h2 {
    margin: 0 0 16px 0;
    font-size: 24px;
    font-weight: 600;
    color: #111827;
}

.jgk-registration-success > p {
    margin: 0 0 32px 0;
    font-size: 16px;
    color: #6b7280;
    line-height: 1.6;
}

.jgk-success-details {
    max-width: 600px;
    margin: 0 auto 32px;
    padding: 24px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    text-align: left;
}

.jgk-success-details p {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #374151;
    line-height: 1.6;
}

.jgk-success-details p:last-child {
    margin-bottom: 0;
}

.jgk-success-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

/* Error Message */
.jgk-registration-error {
    background: #ffffff;
    border: 1px solid #ef4444;
    border-radius: 12px;
    padding: 32px 40px;
    text-align: center;
    margin: 40px auto;
    max-width: 800px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.jgk-error-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    border-radius: 50%;
    color: white;
    font-size: 32px;
}

.jgk-registration-error h2 {
    margin: 0 0 16px 0;
    font-size: 24px;
    font-weight: 600;
    color: #111827;
}

.jgk-registration-error > p {
    margin: 0 0 24px 0;
    font-size: 16px;
    color: #6b7280;
    line-height: 1.6;
}

.jgk-error-details {
    max-width: 600px;
    margin: 0 auto 24px;
    padding: 20px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    text-align: left;
}

.jgk-error-details p {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #dc2626;
    line-height: 1.6;
}

.jgk-error-details p:last-child {
    margin-bottom: 0;
}

.jgk-error-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 768px) {
    .jgk-registration-form {
        margin: 20px 10px;
    }
    
    .jgk-form-header {
        padding: 30px 20px;
    }
    
    .jgk-form-header h2 {
        font-size: 24px;
    }
    
    .jgk-form-section {
        padding: 20px 15px;
    }
    
    .jgk-form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .jgk-form-field {
        margin-bottom: 20px;
    }
    
    .jgk-form-submit {
        padding: 20px 15px;
    }
    
    .jgk-btn-large {
        width: 100%;
        padding: 15px 30px;
        font-size: 16px;
    }
    
    .jgk-registration-success {
        padding: 40px 20px;
    }
    
    .jgk-registration-success h2 {
        font-size: 24px;
    }
    
    .jgk-registration-error {
        padding: 32px 20px;
    }
    
    .jgk-registration-error h2 {
        font-size: 24px;
    }
}

/* Show/hide parent section based on membership type */
#parent-section {
    display: block; /* Toujours visible pour les juniors */
}

#parent-section.show {
    display: block;
}

/* Membership Info Card */
.jgk-membership-info {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.jgk-membership-info h4 {
    margin: 0 0 16px 0;
    font-size: 20px;
    font-weight: 600;
    color: #111827;
}

.jgk-membership-info p {
    margin: 0 0 20px 0;
    font-size: 15px;
    color: #6b7280;
    line-height: 1.6;
}

.jgk-membership-info .jgk-membership-price {
    display: inline-block;
    padding: 12px 24px;
    background: #3b82f6;
    color: white;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
}
</style>

<script>
// Validation d'âge en temps réel (2-17 ans)
document.getElementById('date_of_birth')?.addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    
    const messageDiv = document.getElementById('age-validation-message');
    
    if (!messageDiv) return;
    
    if (age < 2) {
        messageDiv.style.background = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.style.padding = '10px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.border = '1px solid #f5c6cb';
        messageDiv.innerHTML = '❌ The child must be at least 2 years old to register.';
        this.setCustomValidity('Âge minimum : 2 ans');
    } else if (age >= 18) {
        messageDiv.style.background = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.style.padding = '10px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.border = '1px solid #f5c6cb';
        messageDiv.innerHTML = '❌ This program is reserved for juniors under 18 years old.';
        this.setCustomValidity('Âge maximum : 17 ans');
    } else {
        messageDiv.style.background = '#d4edda';
        messageDiv.style.color = '#155724';
        messageDiv.style.padding = '10px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.border = '1px solid #c3e6cb';
        messageDiv.innerHTML = `✅ Valid age: ${age} years old`;
        this.setCustomValidity('');
    }
});

// Trigger validation on page load if date is already filled
document.addEventListener('DOMContentLoaded', function() {
    const dobField = document.getElementById('date_of_birth');
    if (dobField && dobField.value) {
        dobField.dispatchEvent(new Event('change'));
    }
    
    // Parent section toujours visible (programme juniors uniquement)
    const parentSection = document.getElementById('parent-section');
    if (parentSection) {
        parentSection.style.display = 'block';
    }
});

// Password match validation
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');

function validatePassword() {
    if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('Passwords do not match');
    } else {
        confirmPassword.setCustomValidity('');
    }
}

password?.addEventListener('change', validatePassword);
confirmPassword?.addEventListener('keyup', validatePassword);

// Password strength indicator
password?.addEventListener('input', function() {
    const value = this.value;
    const strength = {
        0: 'Very Weak',
        1: 'Weak',
        2: 'Fair',
        3: 'Good',
        4: 'Strong'
    };
    
    let score = 0;
    
    if (value.length >= 8) score++;
    if (value.length >= 12) score++;
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
    if (/\d/.test(value)) score++;
    if (/[^a-zA-Z\d]/.test(value)) score++;
    
    const strengthText = strength[Math.min(score, 4)];
    const colors = ['#d63638', '#d63638', '#f0b849', '#46b450', '#46b450'];
    
    // Remove existing indicator
    let indicator = this.parentElement.querySelector('.password-strength');
    if (indicator) {
        indicator.remove();
    }
    
    // Add new indicator if there's a password
    if (value.length > 0) {
        indicator = document.createElement('small');
        indicator.className = 'password-strength';
        indicator.style.color = colors[Math.min(score, 4)];
        indicator.style.fontWeight = 'bold';
        indicator.textContent = 'Password Strength: ' + strengthText;
        this.parentElement.appendChild(indicator);
    }
});
</script>
