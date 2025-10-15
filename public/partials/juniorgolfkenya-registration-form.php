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

                $profile_image_id = 0;

                if (!empty($_FILES['profile_image']['name'])) {
                    if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
                        $registration_errors[] = 'Profile image upload failed. Please try again.';
                    } else {
                        $max_file_size = 5 * 1024 * 1024;
                        if ($_FILES['profile_image']['size'] > $max_file_size) {
                            $registration_errors[] = 'Profile image must be 5MB or smaller.';
                        } else {
                            $file_info = wp_check_filetype($_FILES['profile_image']['name']);
                            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                            if (empty($file_info['ext']) || !in_array(strtolower($file_info['ext']), $allowed_extensions, true)) {
                                $registration_errors[] = 'Profile image must be JPG, PNG, GIF, or WebP.';
                            } else {
                                if (!function_exists('media_handle_upload')) {
                                    require_once ABSPATH . 'wp-admin/includes/file.php';
                                    require_once ABSPATH . 'wp-admin/includes/media.php';
                                    require_once ABSPATH . 'wp-admin/includes/image.php';
                                }
                                $profile_image_id = media_handle_upload('profile_image', 0);
                                if (is_wp_error($profile_image_id)) {
                                    $registration_errors[] = 'Failed to upload profile image: ' . $profile_image_id->get_error_message();
                                    $profile_image_id = 0;
                                } else {
                                    update_user_meta($user_id, 'jgk_profile_image', $profile_image_id);
                                }
                            }
                        }
                    }
                }

                if (!empty($registration_errors)) {
                    if ($profile_image_id) {
                        wp_delete_attachment($profile_image_id, true);
                    }
                    wp_delete_user($user_id);
                } else {
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
                            'status' => 'pending', // Await manual approval before activation
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
                        if ($profile_image_id) {
                            wp_delete_attachment($profile_image_id, true);
                        }
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
                        $message .= "Your application is currently pending review. We'll notify you once it's approved and active.\n\n";
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
                        $admin_message .= "Status: Pending approval\n\n";
                        $admin_message .= "View member details in the admin panel:\n";
                        $admin_message .= admin_url('admin.php?page=juniorgolfkenya-members&action=edit&id=' . $member_id);

                        wp_mail($admin_email, $admin_subject, $admin_message);

                        // Auto-login the user after successful registration
                        wp_set_current_user($user_id);
                        wp_set_auth_cookie($user_id);

                        // Redirect to Member Portal after successful registration
                        $portal_page_id = get_option('jgk_page_member_portal');
                
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
                        'status' => 'pending', // Await manual approval before activation
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
                    $message .= "Your application is currently pending review. We'll notify you once it's approved and active.\n\n";
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
                    $admin_message .= "Status: Pending approval\n\n";
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
                <p><strong>Thank you!</strong> Your membership application is pending review.</p>
                <p><strong>What happens next?</strong> Our team will verify your details and activate the membership shortly.</p>
                <p>Once approved you will be able to:</p>
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
        <!-- Registration Form with Steps -->
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

        <!-- Progress Steps -->
        <div class="jgk-progress-steps">
            <div class="jgk-step active" data-step="1">
                <div class="jgk-step-number">1</div>
                <div class="jgk-step-label">Personal Info</div>
            </div>
            <div class="jgk-step" data-step="2">
                <div class="jgk-step-number">2</div>
                <div class="jgk-step-label">Membership</div>
            </div>
            <div class="jgk-step" data-step="3">
                <div class="jgk-step-number">3</div>
                <div class="jgk-step-label">Parent Info</div>
            </div>
            <div class="jgk-step" data-step="4">
                <div class="jgk-step-number">4</div>
                <div class="jgk-step-label">Emergency</div>
            </div>
            <div class="jgk-step" data-step="5">
                <div class="jgk-step-number">5</div>
                <div class="jgk-step-label">Consent</div>
            </div>
        </div>

        <!-- Debug Console -->
        <div class="jgk-debug-console" style="display: none; background: #f3f4f6; padding: 15px; margin: 15px 40px; border-radius: 8px; border-left: 4px solid #3b82f6; font-family: monospace; font-size: 12px;">
            <strong>Debug Console:</strong>
            <div id="debug-output" style="margin-top: 8px; max-height: 200px; overflow-y: auto;"></div>
        </div>

    <form method="post" class="jgk-member-form" id="jgk-registration-form" enctype="multipart/form-data">
            <?php wp_nonce_field('jgk_member_registration', 'jgk_register_nonce'); ?>

            <!-- Step 1: Personal Information -->
            <div class="jgk-form-step active" data-step="1">
                <div class="jgk-step-header">
                    <h3>Personal Information</h3>
                    <p>Tell us about the junior golfer</p>
                </div>

                <div class="jgk-form-grid">
                    <div class="jgk-form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($_POST['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="jgk-form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($_POST['last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="jgk-form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" required>
                        <small>This will be used for login</small>
                    </div>
                    <div class="jgk-form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>" placeholder="+254...">
                    </div>
                    <div class="jgk-form-group">
                        <label for="date_of_birth">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" 
                               value="<?php echo esc_attr($_POST['date_of_birth'] ?? ''); ?>" 
                               required 
                               max="<?php echo date('Y-m-d', strtotime('-2 years')); ?>"
                               min="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                        <small style="color: #666;">Must be between 2 and 17 years old</small>
                        <div id="age-validation-message" style="margin-top: 10px;"></div>
                    </div>
                    <div class="jgk-form-group jgk-select-group">
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

                <div class="jgk-textarea-section">
                    <div class="jgk-form-group full-width">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" placeholder="Enter complete address"><?php echo esc_textarea($_POST['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="jgk-form-group">
                    <label for="profile_image">Profile Photo (optional)</label>
                    <div class="jgk-file-upload">
                        <span class="dashicons dashicons-upload"></span>
                        <span id="profile_image_label">Choose an image</span>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                    </div>
                    <small>Supported formats: JPG, PNG, GIF, or WebP. Maximum size 5MB.</small>
                </div>

                <div class="jgk-step-actions">
                    <button type="button" class="jgk-btn jgk-btn-next" data-next="2">Next</button>
                </div>
            </div>

            <!-- Step 2: Membership Details -->
            <div class="jgk-form-step" data-step="2">
                <div class="jgk-step-header">
                    <h3>Membership Details</h3>
                    <p>Golf experience and membership information</p>
                </div>

                <div class="jgk-membership-card">
                    <div class="jgk-membership-header">
                        <h4>Junior Golf Kenya Membership</h4>
                        <span class="jgk-membership-price">KES 5,000 / Year</span>
                    </div>
                    <p>Join Kenya's premier junior golf development program with access to coaching, tournaments, and exclusive training facilities.</p>
                </div>

                <div class="jgk-form-grid">
                    <div class="jgk-form-group">
                        <label for="club_affiliation">Club Affiliation</label>
                        <input type="text" id="club_affiliation" name="club_affiliation" value="<?php echo esc_attr($_POST['club_affiliation'] ?? ''); ?>" placeholder="Your golf club (if any)">
                    </div>
                    <div class="jgk-form-group">
                        <label for="handicap">Golf Handicap</label>
                        <input type="number" id="handicap" name="handicap" step="0.1" min="0" max="54" value="<?php echo esc_attr($_POST['handicap'] ?? ''); ?>" placeholder="e.g., 18.5">
                        <small>Leave blank if no handicap</small>
                    </div>
                </div>

                <div class="jgk-textarea-section">
                    <div class="jgk-form-group full-width">
                        <label for="medical_conditions">Medical Conditions</label>
                        <textarea id="medical_conditions" name="medical_conditions" rows="3" placeholder="Any medical conditions we should be aware of..."><?php echo esc_textarea($_POST['medical_conditions'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="jgk-step-actions">
                    <button type="button" class="jgk-btn jgk-btn-prev" data-prev="1">Previous</button>
                    <button type="button" class="jgk-btn jgk-btn-next" data-next="3">Next</button>
                </div>
            </div>

            <!-- Step 3: Parent/Guardian Information -->
            <div class="jgk-form-step" data-step="3">
                <div class="jgk-step-header">
                    <h3>Parent/Guardian Information</h3>
                    <p>Required for all junior members under 18</p>
                </div>

                <div class="jgk-alert-box">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong>Required Information</strong>
                        <p>Parent or legal guardian information is mandatory for all junior members.</p>
                    </div>
                </div>

                <div class="jgk-form-grid">
                    <div class="jgk-form-group">
                        <label for="parent_first_name">First Name *</label>
                        <input type="text" id="parent_first_name" name="parent_first_name" value="<?php echo esc_attr($_POST['parent_first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="jgk-form-group">
                        <label for="parent_last_name">Last Name *</label>
                        <input type="text" id="parent_last_name" name="parent_last_name" value="<?php echo esc_attr($_POST['parent_last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="jgk-form-group">
                        <label for="parent_email">Email *</label>
                        <input type="email" id="parent_email" name="parent_email" value="<?php echo esc_attr($_POST['parent_email'] ?? ''); ?>">
                        <small>At least email OR phone required</small>
                    </div>
                    <div class="jgk-form-group">
                        <label for="parent_phone">Phone *</label>
                        <input type="tel" id="parent_phone" name="parent_phone" value="<?php echo esc_attr($_POST['parent_phone'] ?? ''); ?>" placeholder="+254...">
                        <small>At least email OR phone required</small>
                    </div>
                    <div class="jgk-form-group full-width jgk-select-group">
                        <label for="parent_relationship">Relationship to Child *</label>
                        <select id="parent_relationship" name="parent_relationship" required>
                            <option value="">Select Relationship</option>
                            <option value="mother" <?php selected($_POST['parent_relationship'] ?? '', 'mother'); ?>>Mother</option>
                            <option value="father" <?php selected($_POST['parent_relationship'] ?? '', 'father'); ?>>Father</option>
                            <option value="guardian" <?php selected($_POST['parent_relationship'] ?? '', 'guardian'); ?>>Guardian</option>
                            <option value="other" <?php selected($_POST['parent_relationship'] ?? '', 'other'); ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="jgk-step-actions">
                    <button type="button" class="jgk-btn jgk-btn-prev" data-prev="2">Previous</button>
                    <button type="button" class="jgk-btn jgk-btn-next" data-next="4">Next</button>
                </div>
            </div>

            <!-- Step 4: Emergency Contact -->
            <div class="jgk-form-step" data-step="4">
                <div class="jgk-step-header">
                    <h3>Emergency Contact</h3>
                    <p>Contact information for emergencies</p>
                </div>

                <div class="jgk-form-grid">
                    <div class="jgk-form-group">
                        <label for="emergency_contact_name">Emergency Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo esc_attr($_POST['emergency_contact_name'] ?? ''); ?>">
                    </div>
                    <div class="jgk-form-group">
                        <label for="emergency_contact_phone">Emergency Contact Phone</label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo esc_attr($_POST['emergency_contact_phone'] ?? ''); ?>" placeholder="+254...">
                    </div>
                </div>

                <div class="jgk-step-actions">
                    <button type="button" class="jgk-btn jgk-btn-prev" data-prev="3">Previous</button>
                    <button type="button" class="jgk-btn jgk-btn-next" data-next="5">Next</button>
                </div>
            </div>

            <!-- Step 5: Consent & Agreements -->
            <div class="jgk-form-step" data-step="5">
                <div class="jgk-step-header">
                    <h3>Consent & Agreements</h3>
                    <p>Review and accept the terms</p>
                </div>

                <div class="jgk-form-grid">
                    <div class="jgk-form-group full-width">
                        <label for="password">Create Password *</label>
                        <input type="password" id="password" name="password" required minlength="8">
                        <small>Minimum 8 characters</small>
                    </div>
                    <div class="jgk-form-group full-width">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                        <small>Re-enter your password</small>
                    </div>
                </div>

                <div class="jgk-consent-section">
                    <div class="jgk-consent-item">
                        <input type="checkbox" id="consent_photography" name="consent_photography" value="1" <?php checked(isset($_POST['consent_photography'])); ?>>
                        <label for="consent_photography">
                            <span class="jgk-consent-title">Photography Consent</span>
                            <span class="jgk-consent-desc">I consent to photography and use of images for promotional purposes</span>
                        </label>
                    </div>

                    <div class="jgk-consent-item">
                        <input type="checkbox" id="parental_consent" name="parental_consent" value="1" <?php checked(isset($_POST['parental_consent'])); ?>>
                        <label for="parental_consent">
                            <span class="jgk-consent-title">Parental Consent</span>
                            <span class="jgk-consent-desc">I give parental consent for my child under 18 to participate in the Junior Golf Kenya program</span>
                        </label>
                    </div>

                    <div class="jgk-consent-item required">
                        <input type="checkbox" id="terms_conditions" name="terms_conditions" required>
                        <label for="terms_conditions">
                            <span class="jgk-consent-title">Terms & Conditions *</span>
                            <span class="jgk-consent-desc">I agree to the <a href="#" target="_blank">Terms & Conditions</a> and <a href="#" target="_blank">Privacy Policy</a></span>
                        </label>
                    </div>
                </div>

                <div class="jgk-step-actions">
                    <button type="button" class="jgk-btn jgk-btn-prev" data-prev="4">Previous</button>
                    <button type="submit" name="jgk_register_member" class="jgk-btn jgk-btn-primary jgk-btn-complete">
                        <span class="dashicons dashicons-yes"></span>
                        Complete Registration
                    </button>
                </div>
            </div>
        </form>

        <!-- Debug Toggle -->
        <div style="text-align: center; margin-top: 20px;">
            <button type="button" id="debug-toggle" class="jgk-btn jgk-btn-secondary" style="font-size: 12px; padding: 8px 16px;">
                🐛 Toggle Debug Console
            </button>
        </div>
    <?php endif; ?>
</div>

<style>
/* Modern Registration Form with Steps */
.jgk-registration-form {
    max-width: 800px;
    margin: 40px auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

/* Form Header */
.jgk-form-header {
    text-align: center;
    padding: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.jgk-form-header h2 {
    margin: 0 0 12px 0;
    font-size: 28px;
    font-weight: 600;
    letter-spacing: -0.025em;
}

.jgk-form-header p {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
    line-height: 1.5;
}

/* Progress Steps */
.jgk-progress-steps {
    display: flex;
    justify-content: space-between;
    padding: 30px 40px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.jgk-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.jgk-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 60%;
    right: -40%;
    height: 2px;
    background: #e2e8f0;
    z-index: 1;
}

.jgk-step.active:not(:last-child)::after {
    background: #3b82f6;
}

.jgk-step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 8px;
    position: relative;
    z-index: 2;
    transition: all 0.3s ease;
}

.jgk-step.active .jgk-step-number {
    background: #3b82f6;
    color: white;
    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
}

.jgk-step-label {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    text-align: center;
}

.jgk-step.active .jgk-step-label {
    color: #3b82f6;
    font-weight: 600;
}

/* Form Steps */
.jgk-form-step {
    display: none;
    padding: 40px;
}

.jgk-form-step.active {
    display: block;
}

.jgk-step-header {
    text-align: center;
    margin-bottom: 32px;
}

.jgk-step-header h3 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 600;
    color: #1e293b;
    letter-spacing: -0.025em;
}

.jgk-step-header p {
    margin: 0;
    color: #64748b;
    font-size: 16px;
}

/* Form Grid */
.jgk-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 24px;
}

.jgk-form-group {
    display: flex;
    flex-direction: column;
}

.jgk-form-group.full-width {
    grid-column: 1 / -1;
}

.jgk-form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    letter-spacing: -0.01em;
}

.jgk-form-group input,
.jgk-form-group select,
.jgk-form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    background: #ffffff;
    color: #1e293b;
    transition: all 0.2s ease;
}

.jgk-form-group textarea {
    border-radius: 5px;
    resize: vertical;
    min-height: 80px;
}

.jgk-form-group input:focus,
.jgk-form-group select:focus,
.jgk-form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.jgk-form-group input:hover,
.jgk-form-group select:hover,
.jgk-form-group textarea:hover {
    border-color: #9ca3af;
}

.jgk-form-group small {
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
}

.jgk-file-upload {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    width: 100%;
    border: 1px dashed #cbd5f5;
    border-radius: 10px;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color 0.2s ease, background 0.2s ease;
}

.jgk-file-upload .dashicons {
    font-size: 20px;
    color: #2563eb;
}

.jgk-file-upload span {
    font-weight: 500;
    color: #1f2937;
}

.jgk-file-upload input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.jgk-file-upload:hover {
    border-color: #94a3f6;
    background: #eef2ff;
}

.jgk-select-group {
    position: relative;
}

.jgk-select-group select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
    padding-right: 52px;
    background-color: #ffffff;
}

.jgk-select-group::after {
    content: '\25BC';
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 12px;
    pointer-events: none;
    transition: color 0.2s ease;
}

.jgk-select-group::before {
    content: '\2713';
    position: absolute;
    right: 42px;
    top: 50%;
    transform: translateY(-50%) scale(0.8);
    color: #10b981;
    font-size: 14px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.jgk-select-group.has-value::before {
    opacity: 1;
    transform: translateY(-50%) scale(1);
}

.jgk-select-group.has-value select {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
}

.jgk-select-group.has-value::after {
    color: #2563eb;
}

/* Textarea Section */
.jgk-textarea-section {
    margin-bottom: 24px;
}

/* Membership Card */
.jgk-membership-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
    text-align: center;
}

.jgk-membership-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.jgk-membership-header h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #0369a1;
}

.jgk-membership-price {
    background: #0ea5e9;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.jgk-membership-card p {
    margin: 0;
    color: #475569;
    line-height: 1.6;
}

/* Alert Box */
.jgk-alert-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    margin-bottom: 24px;
}

.jgk-alert-box .dashicons {
    color: #d97706;
    font-size: 20px;
    margin-top: 2px;
}

.jgk-alert-box strong {
    display: block;
    margin-bottom: 4px;
    color: #92400e;
    font-weight: 600;
}

.jgk-alert-box p {
    margin: 0;
    color: #92400e;
    font-size: 14px;
    line-height: 1.5;
}

/* Consent Section */
.jgk-consent-section {
    margin: 32px 0;
}

.jgk-consent-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}

.jgk-consent-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.jgk-consent-item.required {
    border-color: #3b82f6;
    background: #f0f9ff;
}

.jgk-consent-item input[type="checkbox"] {
    margin-top: 2px;
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #3b82f6;
}

.jgk-consent-item label {
    cursor: pointer;
    margin: 0;
    flex: 1;
}

.jgk-consent-title {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.jgk-consent-desc {
    display: block;
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
}

.jgk-consent-desc a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.jgk-consent-desc a:hover {
    text-decoration: underline;
}

/* Step Actions */
.jgk-step-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 32px;
    border-top: 1px solid #e2e8f0;
}

/* Buttons */
.jgk-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    font-family: inherit;
}

.jgk-btn-prev {
    background: #6b7280;
    color: white;
}

.jgk-btn-prev:hover {
    background: #4b5563;
    transform: translateY(-1px);
}

.jgk-btn-next {
    background: #3b82f6;
    color: white;
}

.jgk-btn-next:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.jgk-btn-complete {
    background: #10b981;
    color: white;
    padding: 14px 32px;
}

.jgk-btn-complete:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.jgk-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* Error Messages */
.jgk-form-errors {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 20px;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 8px;
    margin: 20px 40px;
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

/* Success Message */
.jgk-registration-success {
    background: #ffffff;
    border-radius: 12px;
    padding: 64px 40px;
    text-align: center;
    margin: 40px auto;
    max-width: 800px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

.jgk-btn-primary {
    background: #3b82f6;
    color: white;
}

.jgk-btn-primary:hover {
    background: #2563eb;
}

.jgk-btn-secondary {
    background: #6b7280;
    color: white;
}

.jgk-btn-secondary:hover {
    background: #4b5563;
}

.jgk-btn-large {
    padding: 16px 32px;
    font-size: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .jgk-registration-form {
        margin: 20px 10px;
        border-radius: 8px;
    }
    
    .jgk-form-header {
        padding: 30px 20px;
    }
    
    .jgk-form-header h2 {
        font-size: 24px;
    }
    
    .jgk-progress-steps {
        padding: 20px 15px 15px;
    }
    
    .jgk-step:not(:last-child)::after {
        display: none;
    }
    
    .jgk-step-number {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
    
    .jgk-step-label {
        font-size: 10px;
    }
    
    .jgk-form-step {
        padding: 20px 15px;
    }
    
    .jgk-form-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .jgk-step-actions {
        flex-direction: column;
        gap: 12px;
    }
    
    .jgk-btn {
        width: 100%;
        justify-content: center;
    }
    
    .jgk-form-errors {
        margin: 15px;
        padding: 15px;
    }
    
    .jgk-registration-success {
        padding: 40px 20px;
        margin: 20px 10px;
    }
}

/* Password Strength Indicator */
.password-strength {
    margin-top: 6px;
    font-size: 12px;
    font-weight: 600;
}

/* Field Error Styling */
.field-error {
    color: #dc2626 !important;
    font-size: 12px;
    margin-top: 4px;
    font-weight: 500;
}

/* Debug Console */
.jgk-debug-console {
    transition: all 0.3s ease;
}

.jgk-debug-console.show {
    display: block !important;
}
</style>

<script>
// Debug logging function
function debugLog(message, type = 'info') {
    const debugOutput = document.getElementById('debug-output');
    const debugConsole = document.querySelector('.jgk-debug-console');
    
    if (debugOutput && debugConsole) {
        const timestamp = new Date().toLocaleTimeString();
        const color = type === 'error' ? '#dc2626' : type === 'success' ? '#10b981' : '#3b82f6';
        const logEntry = document.createElement('div');
        logEntry.style.color = color;
        logEntry.style.marginBottom = '4px';
        logEntry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
        debugOutput.appendChild(logEntry);
        debugOutput.scrollTop = debugOutput.scrollHeight;
        
        console.log(`[JGK Debug] ${message}`);
    }
}

// Multi-step form functionality
document.addEventListener('DOMContentLoaded', function() {
    debugLog('Form initialized - DOM loaded successfully', 'success');
    
    const form = document.getElementById('jgk-registration-form');
    const steps = document.querySelectorAll('.jgk-form-step');
    const progressSteps = document.querySelectorAll('.jgk-step');
    const nextButtons = document.querySelectorAll('.jgk-btn-next');
    const prevButtons = document.querySelectorAll('.jgk-btn-prev');
    const debugToggle = document.getElementById('debug-toggle');

    // Debug toggle functionality
    if (debugToggle) {
        debugToggle.addEventListener('click', function() {
            const debugConsole = document.querySelector('.jgk-debug-console');
            if (debugConsole) {
                debugConsole.style.display = debugConsole.style.display === 'none' ? 'block' : 'none';
                debugLog('Debug console toggled: ' + (debugConsole.style.display === 'none' ? 'hidden' : 'visible'));
            }
        });
    }

    // Initialize first step
    showStep(1);
    debugLog('Step 1 activated', 'info');

    // Next button click handlers
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            debugLog('Next button clicked for step: ' + this.dataset.next, 'info');
            const currentStep = getCurrentStep();
            const nextStep = parseInt(this.dataset.next);
            
            debugLog(`Validating step ${currentStep} before moving to step ${nextStep}`, 'info');
            
            if (validateStep(currentStep)) {
                debugLog(`Step ${currentStep} validation passed`, 'success');
                showStep(nextStep);
                updateProgress(nextStep);
                debugLog(`Moved to step ${nextStep}`, 'success');
            } else {
                debugLog(`Step ${currentStep} validation failed - cannot proceed`, 'error');
            }
        });
    });

    // Previous button click handlers
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            debugLog('Previous button clicked for step: ' + this.dataset.prev, 'info');
            const prevStep = parseInt(this.dataset.prev);
            showStep(prevStep);
            updateProgress(prevStep);
            debugLog(`Moved back to step ${prevStep}`, 'info');
        });
    });

    function getCurrentStep() {
        const currentStepElement = document.querySelector('.jgk-form-step.active');
        if (currentStepElement) {
            return parseInt(currentStepElement.dataset.step);
        }
        debugLog('Could not find current step element', 'error');
        return 1;
    }

    function showStep(stepNumber) {
        debugLog(`Attempting to show step ${stepNumber}`, 'info');
        
        // Hide all steps
        steps.forEach(step => {
            step.classList.remove('active');
            debugLog(`Hiding step ${step.dataset.step}`, 'info');
        });
        
        // Show current step
        const currentStep = document.querySelector(`.jgk-form-step[data-step="${stepNumber}"]`);
        if (currentStep) {
            currentStep.classList.add('active');
            debugLog(`Showing step ${stepNumber}`, 'success');
        } else {
            debugLog(`Step ${stepNumber} element not found!`, 'error');
        }
    }

    function updateProgress(stepNumber) {
        debugLog(`Updating progress to step ${stepNumber}`, 'info');
        progressSteps.forEach(step => {
            const stepNum = parseInt(step.dataset.step);
            if (stepNum <= stepNumber) {
                step.classList.add('active');
                debugLog(`Progress step ${stepNum} marked as active`, 'info');
            } else {
                step.classList.remove('active');
                debugLog(`Progress step ${stepNum} marked as inactive`, 'info');
            }
        });
    }

    function validateStep(stepNumber) {
        debugLog(`Starting validation for step ${stepNumber}`, 'info');
        const currentStep = document.querySelector(`.jgk-form-step[data-step="${stepNumber}"]`);
        const requiredFields = currentStep.querySelectorAll('[required]');
        let isValid = true;
        let validationErrors = [];

        debugLog(`Found ${requiredFields.length} required fields in step ${stepNumber}`, 'info');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                validationErrors.push(`Field '${field.name}' is required`);
                field.style.borderColor = '#ef4444';
                
                // Add error message
                if (!field.parentNode.querySelector('.field-error')) {
                    const errorMsg = document.createElement('small');
                    errorMsg.className = 'field-error';
                    errorMsg.style.color = '#ef4444';
                    errorMsg.textContent = 'This field is required';
                    field.parentNode.appendChild(errorMsg);
                    debugLog(`Added error message for field: ${field.name}`, 'error');
                }
            } else {
                field.style.borderColor = '#d1d5db';
                const errorMsg = field.parentNode.querySelector('.field-error');
                if (errorMsg) {
                    errorMsg.remove();
                    debugLog(`Removed error message for field: ${field.name}`, 'info');
                }
            }
        });

        // Special validation for step 1 (age)
        if (stepNumber === 1) {
            debugLog('Running special validation for step 1 (age)', 'info');
            const dobField = document.getElementById('date_of_birth');
            if (dobField && dobField.value) {
                const ageValid = validateAge(dobField.value);
                if (!ageValid) {
                    isValid = false;
                    validationErrors.push('Age validation failed');
                }
            }
        }

        // Special validation for step 3 (parent contact)
        if (stepNumber === 3) {
            debugLog('Running special validation for step 3 (parent contact)', 'info');
            const parentEmail = document.getElementById('parent_email');
            const parentPhone = document.getElementById('parent_phone');
            
            if ((!parentEmail.value.trim() && !parentPhone.value.trim())) {
                isValid = false;
                validationErrors.push('At least one parent contact method is required');
                const errorMsg = document.createElement('small');
                errorMsg.className = 'field-error';
                errorMsg.style.color = '#ef4444';
                errorMsg.textContent = 'At least one parent contact method is required';
                
                if (parentEmail.value.trim()) {
                    parentPhone.parentNode.appendChild(errorMsg.cloneNode(true));
                } else if (parentPhone.value.trim()) {
                    parentEmail.parentNode.appendChild(errorMsg.cloneNode(true));
                } else {
                    parentEmail.parentNode.appendChild(errorMsg.cloneNode(true));
                    parentPhone.parentNode.appendChild(errorMsg.cloneNode(true));
                }
                debugLog('Parent contact validation failed', 'error');
            } else {
                debugLog('Parent contact validation passed', 'success');
            }
        }

        if (!isValid) {
            debugLog(`Step ${stepNumber} validation failed with errors: ${validationErrors.join(', ')}`, 'error');
            // Scroll to first error
            const firstError = currentStep.querySelector('[required]:invalid') || currentStep.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                debugLog('Scrolled to first validation error', 'info');
            }
        } else {
            debugLog(`Step ${stepNumber} validation passed successfully`, 'success');
        }

        return isValid;
    }

    // Age validation
    function validateAge(dob) {
        debugLog(`Validating age for DOB: ${dob}`, 'info');
        const birthDate = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        const messageDiv = document.getElementById('age-validation-message');
        if (!messageDiv) {
            debugLog('Age validation message div not found', 'error');
            return false;
        }
        
        if (age < 2) {
            messageDiv.style.background = '#fef2f2';
            messageDiv.style.color = '#dc2626';
            messageDiv.style.padding = '12px';
            messageDiv.style.borderRadius = '8px';
            messageDiv.style.border = '1px solid #fecaca';
            messageDiv.innerHTML = '❌ The child must be at least 2 years old to register.';
            debugLog('Age validation failed: under 2 years', 'error');
            return false;
        } else if (age >= 18) {
            messageDiv.style.background = '#fef2f2';
            messageDiv.style.color = '#dc2626';
            messageDiv.style.padding = '12px';
            messageDiv.style.borderRadius = '8px';
            messageDiv.style.border = '1px solid #fecaca';
            messageDiv.innerHTML = '❌ This program is reserved for juniors under 18 years old.';
            debugLog('Age validation failed: 18 years or older', 'error');
            return false;
        } else {
            messageDiv.style.background = '#f0fdf4';
            messageDiv.style.color = '#166534';
            messageDiv.style.padding = '12px';
            messageDiv.style.borderRadius = '8px';
            messageDiv.style.border = '1px solid #bbf7d0';
            messageDiv.innerHTML = `✅ Valid age: ${age} years old`;
            debugLog(`Age validation passed: ${age} years old`, 'success');
            return true;
        }
    }

    const profileImageInput = document.getElementById('profile_image');
    const profileImageLabel = document.getElementById('profile_image_label');

    if (profileImageInput && profileImageLabel) {
        profileImageInput.addEventListener('change', function() {
            const fileName = this.files && this.files.length ? this.files[0].name : 'Choose an image';
            profileImageLabel.textContent = fileName;
            debugLog(this.files && this.files.length ? 'Profile image selected: ' + fileName : 'Profile image selection cleared', 'info');
        });
    }

    // Real-time age validation
    const dobField = document.getElementById('date_of_birth');
    if (dobField) {
        dobField.addEventListener('change', function() {
            debugLog('Date of birth changed: ' + this.value, 'info');
            validateAge(this.value);
        });
    }

    // Password match validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
            debugLog('Password validation failed: passwords do not match', 'error');
        } else {
            confirmPassword.setCustomValidity('');
            debugLog('Password validation passed', 'success');
        }
    }

    if (password && confirmPassword) {
        password.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
    }

    // Password strength indicator
    if (password) {
        password.addEventListener('input', function() {
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
            const colors = ['#dc2626', '#dc2626', '#f59e0b', '#10b981', '#10b981'];
            
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
                debugLog(`Password strength: ${strengthText} (score: ${score})`, 'info');
            }
        });
    }

    // Trigger validation on page load if fields are pre-filled
    if (dobField && dobField.value) {
        debugLog('Triggering age validation for pre-filled DOB', 'info');
        validateAge(dobField.value);
    }

    // Log initial state
    debugLog('Form initialization complete', 'success');
    debugLog('Next buttons found: ' + nextButtons.length, 'info');
    debugLog('Previous buttons found: ' + prevButtons.length, 'info');
    debugLog('Form steps found: ' + steps.length, 'info');
});
</script>