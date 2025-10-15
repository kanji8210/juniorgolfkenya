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

                $birth_certificate_id = 0;

                if (!empty($_FILES['birth_certificate']['name'])) {
                    if ($_FILES['birth_certificate']['error'] !== UPLOAD_ERR_OK) {
                        $registration_errors[] = 'Birth certificate upload failed. Please try again.';
                    } else {
                        $max_file_size = 10 * 1024 * 1024; // 10MB for birth certificates
                        if ($_FILES['birth_certificate']['size'] > $max_file_size) {
                            $registration_errors[] = 'Birth certificate must be 10MB or smaller.';
                        } else {
                            $file_info = wp_check_filetype($_FILES['birth_certificate']['name']);
                            $allowed_extensions = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp');
                            if (empty($file_info['ext']) || !in_array(strtolower($file_info['ext']), $allowed_extensions, true)) {
                                $registration_errors[] = 'Birth certificate must be PDF, JPG, PNG, GIF, or WebP.';
                            } else {
                                if (!function_exists('media_handle_upload')) {
                                    require_once ABSPATH . 'wp-admin/includes/file.php';
                                    require_once ABSPATH . 'wp-admin/includes/media.php';
                                    require_once ABSPATH . 'wp-admin/includes/image.php';
                                }
                                $birth_certificate_id = media_handle_upload('birth_certificate', 0);
                                if (is_wp_error($birth_certificate_id)) {
                                    $registration_errors[] = 'Failed to upload birth certificate: ' . $birth_certificate_id->get_error_message();
                                    $birth_certificate_id = 0;
                                } else {
                                    update_user_meta($user_id, 'jgk_birth_certificate', $birth_certificate_id);
                                }
                            }
                        }
                    }
                }

                if (!empty($registration_errors)) {
                    if ($profile_image_id) {
                        wp_delete_attachment($profile_image_id, true);
                    }
                    if ($birth_certificate_id) {
                        wp_delete_attachment($birth_certificate_id, true);
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
                        if ($birth_certificate_id) {
                            wp_delete_attachment($birth_certificate_id, true);
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

                        // Set registration success flag and redirect
                        $registration_success = true;
                        
                        // Auto-login the user after successful registration  
                        wp_set_current_user($user_id);
                        wp_set_auth_cookie($user_id);

                        // Determine redirect URL
                        $portal_page_id = get_option('jgk_page_member_portal');
                        if ($portal_page_id) {
                            $redirect_url = get_permalink($portal_page_id);
                        } else {
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
        <div class="jgk-container">
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

                <div class="jgk-form-group">
                    <label for="birth_certificate">Birth Certificate *</label>
                    <div class="jgk-file-upload">
                        <span class="dashicons dashicons-media-document"></span>
                        <span id="birth_certificate_label">Choose birth certificate</span>
                        <input type="file" id="birth_certificate" name="birth_certificate" accept=".pdf,image/*" required>
                    </div>
                    <small>Supported formats: PDF, JPG, PNG, GIF, or WebP. Maximum size 10MB. Required for age verification.</small>
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
    </div> <!-- End jgk-container -->
    <?php endif; ?>
</div>
