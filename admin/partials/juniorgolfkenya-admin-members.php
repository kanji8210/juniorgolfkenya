<?php
/**
 * Provide a admin area view for members management
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

// Load required classes
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-user-manager.php';
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-media.php';

// Check user permissions
if (!current_user_can('edit_members')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_members_action')) {
        wp_die(__('Security check failed.'));
    }

    switch ($_POST['action']) {
        case 'approve_member':
            if (isset($_POST['member_id'])) {
                $result = JuniorGolfKenya_User_Manager::approve_member($_POST['member_id']);
                if ($result) {
                    $message = 'Member approved successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to approve member.';
                    $message_type = 'error';
                }
            }
            break;

        case 'create_member':
            // Validation de l'âge (2-17 ans)
            $date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? '');
            $create_error = false;
            
            if (!empty($date_of_birth)) {
                try {
                    $birthdate = new DateTime($date_of_birth);
                    $today = new DateTime();
                    $age = $today->diff($birthdate)->y;
                    
                    if ($age < 2) {
                        $message = 'Erreur : L\'âge minimum est de 2 ans.';
                        $message_type = 'error';
                        $create_error = true;
                    }
                    
                    if ($age >= 18) {
                        $message = 'Erreur : Ce programme est réservé aux juniors de moins de 18 ans.';
                        $message_type = 'error';
                        $create_error = true;
                    }
                } catch (Exception $e) {
                    $message = 'Erreur : Format de date de naissance invalide.';
                    $message_type = 'error';
                    $create_error = true;
                }
            } else {
                $message = 'Erreur : La date de naissance est obligatoire.';
                $message_type = 'error';
                $create_error = true;
            }
            
            if (!$create_error) {
                $user_data = array(
                    'user_login' => sanitize_user($_POST['username']),
                    'user_email' => sanitize_email($_POST['email']),
                    'display_name' => sanitize_text_field($_POST['display_name']),
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                    'user_pass' => $_POST['password']
                );
                
                $member_data = array(
                    'membership_type' => 'junior', // Forcé : programme juniors uniquement
                    'status' => sanitize_text_field($_POST['status']),
                    'date_of_birth' => $date_of_birth,
                    'gender' => sanitize_text_field($_POST['gender']),
                    'phone' => sanitize_text_field($_POST['phone']),
                    'handicap' => floatval($_POST['handicap']),
                    'club_affiliation' => sanitize_text_field($_POST['club_affiliation']),
                    'emergency_contact_name' => sanitize_text_field($_POST['emergency_contact_name']),
                    'emergency_contact_phone' => sanitize_text_field($_POST['emergency_contact_phone']),
                    'medical_conditions' => sanitize_textarea_field($_POST['medical_conditions']),
                    'join_date' => current_time('Y-m-d'),
                    'expiry_date' => date('Y-m-d', strtotime('+1 year'))
                );

                // Collect parent/guardian data if provided
                $parent_data = array();
                if (isset($_POST['parent_first_name']) && is_array($_POST['parent_first_name'])) {
                    $parent_count = count($_POST['parent_first_name']);
                    for ($i = 0; $i < $parent_count; $i++) {
                        // Skip empty entries
                        if (empty($_POST['parent_first_name'][$i]) && empty($_POST['parent_last_name'][$i])) {
                            continue;
                        }
                        
                        $parent_data[] = array(
                            'first_name' => sanitize_text_field($_POST['parent_first_name'][$i]),
                            'last_name' => sanitize_text_field($_POST['parent_last_name'][$i]),
                            'relationship' => sanitize_text_field($_POST['parent_relationship'][$i] ?? 'parent'),
                            'phone' => sanitize_text_field($_POST['parent_phone'][$i] ?? ''),
                            'email' => sanitize_email($_POST['parent_email'][$i] ?? ''),
                            'occupation' => sanitize_text_field($_POST['parent_occupation'][$i] ?? ''),
                            'is_primary_contact' => isset($_POST['parent_is_primary'][$i]) ? 1 : 0,
                            'emergency_contact' => isset($_POST['parent_is_emergency'][$i]) ? 1 : 0,
                            'can_pickup' => isset($_POST['parent_can_pickup'][$i]) ? 1 : 0
                        );
                    }
                }

                $result = JuniorGolfKenya_User_Manager::create_member_user($user_data, $member_data, $parent_data);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                
                // Add warnings if any
                if (isset($result['warnings'])) {
                    $message .= ' ' . implode(' ', $result['warnings']);
                }
                
                // Handle profile image upload if member was created successfully
                if ($result['success'] && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = JuniorGolfKenya_Media::upload_profile_image($result['member_id'], $_FILES['profile_image']);
                    if ($upload_result['success']) {
                        $message .= ' Profile photo uploaded successfully.';
                    } else {
                        $message .= ' Warning: ' . $upload_result['message'];
                        $message_type = 'warning';
                    }
                }
            }
            break;

        case 'edit_member':
            if (isset($_POST['member_id'])) {
                $member_id = intval($_POST['member_id']);
                
                // Update basic member data
                $member_data = array(
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                    'membership_type' => 'junior', // Forcé : programme juniors uniquement
                    'status' => sanitize_text_field($_POST['status']),
                    'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
                    'gender' => sanitize_text_field($_POST['gender']),
                    'phone' => sanitize_text_field($_POST['phone']),
                    'handicap' => !empty($_POST['handicap']) ? floatval($_POST['handicap']) : null,
                    'club_affiliation' => sanitize_text_field($_POST['club_affiliation']),
                    'coach_id' => !empty($_POST['coach_id']) ? intval($_POST['coach_id']) : null,
                    'emergency_contact_name' => sanitize_text_field($_POST['emergency_contact_name']),
                    'emergency_contact_phone' => sanitize_text_field($_POST['emergency_contact_phone']),
                    'medical_conditions' => sanitize_textarea_field($_POST['medical_conditions']),
                    'address' => sanitize_textarea_field($_POST['address']),
                    'biography' => sanitize_textarea_field($_POST['biography']),
                    'consent_photography' => isset($_POST['consent_photography']) ? 'yes' : 'no',
                    'parental_consent' => isset($_POST['parental_consent']) ? 1 : 0,
                    'is_public' => isset($_POST['is_public']) ? intval($_POST['is_public']) : 0
                );
                
                // Log for debugging
                error_log('JGK Member Update - is_public value: ' . (isset($_POST['is_public']) ? $_POST['is_public'] : 'not set'));
                error_log('JGK Member Update - Data: ' . print_r($member_data, true));
                
                $result = JuniorGolfKenya_Database::update_member($member_id, $member_data);
                
                if ($result) {
                    // Update WordPress user data
                    $member = JuniorGolfKenya_Database::get_member($member_id);
                    if ($member && $member->user_id) {
                        wp_update_user(array(
                            'ID' => $member->user_id,
                            'user_email' => sanitize_email($_POST['email']),
                            'display_name' => sanitize_text_field($_POST['display_name'])
                        ));
                    }
                    
                    // Handle profile image upload
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        $upload_result = JuniorGolfKenya_Media::upload_profile_image($member_id, $_FILES['profile_image']);
                        if (!$upload_result['success']) {
                            $message = 'Member updated but profile image failed: ' . $upload_result['message'];
                            $message_type = 'warning';
                        }
                    }
                    
                    // Handle profile image deletion
                    if (isset($_POST['delete_profile_image']) && $_POST['delete_profile_image'] === '1') {
                        JuniorGolfKenya_Media::delete_profile_image($member_id);
                    }
                    
                    if (empty($message)) {
                        $message = 'Member updated successfully!';
                        $message_type = 'success';
                    }
                } else {
                    $message = 'Failed to update member.';
                    $message_type = 'error';
                }
                
                // Set action to edit to reload the edit form with message
                $_GET['action'] = 'edit';
                $_GET['member_id'] = $member_id;
                // Don't redirect - let the page render with the success message
            }
            break;
    }
}

// Check if we're in edit mode
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$member_id_to_edit = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;

if ($action === 'edit' && $member_id_to_edit > 0) {
    // Load edit form
    $edit_member = JuniorGolfKenya_Database::get_member($member_id_to_edit);
    if (!$edit_member) {
        wp_die(__('Member not found.'));
    }
    
    // Load parents if member is under 18
    require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-parents.php';
    $parents_manager = new JuniorGolfKenya_Parents();
    $member_parents = $parents_manager->get_member_parents($member_id_to_edit);
    
    // Load available coaches for assignment
    $coaches = get_users(array(
        'role' => 'jgk_coach',
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    // Include edit form
    include_once JUNIORGOLFKENYA_PLUGIN_PATH . 'admin/partials/juniorgolfkenya-admin-member-edit.php';
    return;
}

// Get members data
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

if ($search) {
    $members = JuniorGolfKenya_Database::search_members($search, $page, $per_page);
    $total_members = count($members); // Simplified for search
} else {
    $members = JuniorGolfKenya_Database::get_members($page, $per_page, $status_filter);
    $total_members = JuniorGolfKenya_Database::get_members_count($status_filter);
}

$total_pages = ceil($total_members / $per_page);

// Get statistics
$stats = JuniorGolfKenya_Database::get_membership_stats();
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">JGK Members</h1>
    <a href="#" class="page-title-action" onclick="toggleAddMemberForm()">Add New Member</a>
    <hr class="wp-header-end">

    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="jgk-stats-grid">
        <div class="jgk-stat-card">
            <div class="jgk-stat-number"><?php echo number_format($stats['total']); ?></div>
            <div class="jgk-stat-label">Total Members</div>
        </div>
        <div class="jgk-stat-card">
            <div class="jgk-stat-number"><?php echo number_format($stats['active']); ?></div>
            <div class="jgk-stat-label">Active Members</div>
        </div>
        <div class="jgk-stat-card">
            <div class="jgk-stat-number"><?php echo number_format($stats['pending']); ?></div>
            <div class="jgk-stat-label">Pending Approval</div>
        </div>
        <div class="jgk-stat-card">
            <div class="jgk-stat-number"><?php echo number_format($stats['expired']); ?></div>
            <div class="jgk-stat-label">Expired</div>
        </div>
    </div>

    <!-- Add Member Form (Hidden by default) -->
    <div id="add-member-form" class="jgk-form-section" style="display: none;">
        <h2>Add New Member</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('jgk_members_action'); ?>
            <input type="hidden" name="action" value="create_member">
            
            <h3>Personal Information</h3>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="profile_image">Profile Photo</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                    <small>Max 5MB. JPG, PNG, GIF or WebP format.</small>
                    <div id="profile_image_preview" style="margin-top: 10px;"></div>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="jgk-form-field">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="jgk-form-field">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" placeholder="Auto-generated from first/last name">
                </div>
                <div class="jgk-form-field">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <small>Minimum 8 characters</small>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="date_of_birth">Date de naissance *</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" 
                           required 
                           max="<?php echo date('Y-m-d', strtotime('-2 years')); ?>"
                           min="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                    <small style="color: #666;">Âge requis : 2-17 ans</small>
                </div>
                <div class="jgk-form-field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                        <option value="prefer_not_to_say">Prefer not to say</option>
                    </select>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+254...">
                </div>
                <div class="jgk-form-field">
                    <label for="handicap">Golf Handicap</label>
                    <input type="number" id="handicap" name="handicap" step="0.1" min="0" max="54" placeholder="0.0">
                </div>
            </div>
            
            <h3>Membership Details</h3>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <div class="jgk-form-field-info" style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; border-radius: 5px;">
                        <label style="font-weight: 600; color: #0073aa; display: block; margin-bottom: 5px;">
                            Membership Type
                        </label>
                        <p style="margin: 0; color: #555;">
                            <strong>Junior Golf Kenya</strong> - Programme réservé aux 2-17 ans
                        </p>
                        <input type="hidden" name="membership_type" value="junior">
                    </div>
                </div>
                <div class="jgk-form-field">
                    <label for="status">Initial Status *</label>
                    <select id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="pending" selected>Pending Approval</option>
                        <option value="suspended">Suspended</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="club_affiliation">Club Affiliation</label>
                    <input type="text" id="club_affiliation" name="club_affiliation" placeholder="Current golf club">
                </div>
                <div class="jgk-form-field">
                    <label for="medical_conditions">Medical Conditions</label>
                    <textarea id="medical_conditions" name="medical_conditions" rows="2" placeholder="Any medical conditions to be aware of..."></textarea>
                </div>
            </div>
            
            <h3>Emergency Contact</h3>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" placeholder="Full name">
                </div>
                <div class="jgk-form-field">
                    <label for="emergency_contact_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" placeholder="+254...">
                </div>
            </div>
            
            <h3>Parent/Guardian Information <span style="font-weight: normal; font-size: 14px;">(Required for members under 18)</span></h3>
            <div id="parents-container">
                <div class="parent-entry" data-parent-index="0">
                    <h4>Parent/Guardian 1</h4>
                    <div class="jgk-form-row">
                        <div class="jgk-form-field">
                            <label>First Name</label>
                            <input type="text" name="parent_first_name[]" placeholder="Parent's first name">
                        </div>
                        <div class="jgk-form-field">
                            <label>Last Name</label>
                            <input type="text" name="parent_last_name[]" placeholder="Parent's last name">
                        </div>
                    </div>
                    <div class="jgk-form-row">
                        <div class="jgk-form-field">
                            <label>Relationship</label>
                            <select name="parent_relationship[]">
                                <option value="parent">Parent</option>
                                <option value="father">Father</option>
                                <option value="mother">Mother</option>
                                <option value="guardian">Legal Guardian</option>
                                <option value="grandparent">Grandparent</option>
                                <option value="aunt_uncle">Aunt/Uncle</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="jgk-form-field">
                            <label>Phone Number</label>
                            <input type="tel" name="parent_phone[]" placeholder="+254...">
                        </div>
                    </div>
                    <div class="jgk-form-row">
                        <div class="jgk-form-field">
                            <label>Email</label>
                            <input type="email" name="parent_email[]" placeholder="parent@example.com">
                        </div>
                        <div class="jgk-form-field">
                            <label>Occupation</label>
                            <input type="text" name="parent_occupation[]" placeholder="Occupation">
                        </div>
                    </div>
                    <div class="jgk-form-row">
                        <div class="jgk-form-field" style="flex-direction: row; gap: 15px;">
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="parent_is_primary[]" value="1">
                                Primary Contact
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="parent_is_emergency[]" value="1">
                                Emergency Contact
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="parent_can_pickup[]" value="1">
                                Can Pick Up
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <p>
                <button type="button" class="button" onclick="addParentEntry()">+ Add Another Parent/Guardian</button>
            </p>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="Create Member">
                <button type="button" class="button" onclick="toggleAddMemberForm()">Cancel</button>
            </p>
        </form>
    </div>

    <script>
    let parentIndex = 1;
    
    // Profile image preview
    document.getElementById('profile_image')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('profile_image_preview');
        
        if (file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File too large. Maximum size is 5MB.');
                this.value = '';
                preview.innerHTML = '';
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Invalid file type. Please use JPG, PNG, GIF or WebP.');
                this.value = '';
                preview.innerHTML = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px; border-radius: 50%; border: 3px solid #0073aa;">';
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
        }
    });
    
    function addParentEntry() {
        const container = document.getElementById('parents-container');
        const newEntry = document.createElement('div');
        newEntry.className = 'parent-entry';
        newEntry.setAttribute('data-parent-index', parentIndex);
        newEntry.innerHTML = `
            <h4>Parent/Guardian ${parentIndex + 1} <button type="button" class="button button-small" onclick="removeParentEntry(this)" style="margin-left: 10px;">Remove</button></h4>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label>First Name</label>
                    <input type="text" name="parent_first_name[]" placeholder="Parent's first name">
                </div>
                <div class="jgk-form-field">
                    <label>Last Name</label>
                    <input type="text" name="parent_last_name[]" placeholder="Parent's last name">
                </div>
            </div>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label>Relationship</label>
                    <select name="parent_relationship[]">
                        <option value="parent">Parent</option>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="guardian">Legal Guardian</option>
                        <option value="grandparent">Grandparent</option>
                        <option value="aunt_uncle">Aunt/Uncle</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="jgk-form-field">
                    <label>Phone Number</label>
                    <input type="tel" name="parent_phone[]" placeholder="+254...">
                </div>
            </div>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label>Email</label>
                    <input type="email" name="parent_email[]" placeholder="parent@example.com">
                </div>
                <div class="jgk-form-field">
                    <label>Occupation</label>
                    <input type="text" name="parent_occupation[]" placeholder="Occupation">
                </div>
            </div>
            <div class="jgk-form-row">
                <div class="jgk-form-field" style="flex-direction: row; gap: 15px;">
                    <label style="display: inline-flex; align-items: center; gap: 5px;">
                        <input type="checkbox" name="parent_is_primary[]" value="1">
                        Primary Contact
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 5px;">
                        <input type="checkbox" name="parent_is_emergency[]" value="1">
                        Emergency Contact
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 5px;">
                        <input type="checkbox" name="parent_can_pickup[]" value="1">
                        Can Pick Up
                    </label>
                </div>
            </div>
        `;
        container.appendChild(newEntry);
        parentIndex++;
    }
    
    function removeParentEntry(button) {
        const entry = button.closest('.parent-entry');
        if (entry) {
            entry.remove();
        }
    }
    
    // Show/hide parent section based on date of birth
    document.getElementById('date_of_birth')?.addEventListener('change', function() {
        const dob = new Date(this.value);
        const today = new Date();
        const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
        
        const parentsSection = document.querySelector('h3:has(+ #parents-container)');
        if (parentsSection) {
            if (age < 18) {
                parentsSection.style.display = 'block';
                document.getElementById('parents-container').style.display = 'block';
                document.querySelector('button[onclick="addParentEntry()"]').parentElement.style.display = 'block';
                // Make first parent required
                document.querySelectorAll('.parent-entry:first-child input[name="parent_first_name[]"], .parent-entry:first-child input[name="parent_last_name[]"]').forEach(input => {
                    input.required = true;
                });
            } else {
                // Keep visible but not required
                document.querySelectorAll('.parent-entry input').forEach(input => {
                    input.required = false;
                });
            }
        }
    });
    </script>

    <!-- Filters and Search -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-flex; gap: 10px; align-items: center;">
                <input type="hidden" name="page" value="juniorgolfkenya-members">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending Approval</option>
                    <option value="expired" <?php selected($status_filter, 'expired'); ?>>Expired</option>
                    <option value="suspended" <?php selected($status_filter, 'suspended'); ?>>Suspended</option>
                </select>
                <input type="submit" class="button" value="Filter">
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members'); ?>" class="button">Clear Filter</a>
            </form>
        </div>
        <div class="alignright">
            <form method="get" style="display: inline-flex; gap: 5px;">
                <input type="hidden" name="page" value="juniorgolfkenya-members">
                <?php if ($status_filter): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                <?php endif; ?>
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search members...">
                <input type="submit" class="button" value="Search">
            </form>
        </div>
    </div>

    <!-- Members Table -->
    <div class="jgk-table-container">
        <table class="wp-list-table widefat fixed striped jgk-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Member #</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Visibility</th>
                    <th>Coach</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                <tr>
                    <td colspan="10">No members found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td>
                        <?php echo JuniorGolfKenya_Media::get_profile_image_html($member->id, 'thumbnail', array('style' => 'width: 40px; height: 40px; border-radius: 50%; object-fit: cover;')); ?>
                    </td>
                    <td><strong><?php echo esc_html($member->membership_number ?: 'JGK-' . str_pad($member->id, 4, '0', STR_PAD_LEFT)); ?></strong></td>
                    <td>
                        <strong><?php echo esc_html($member->display_name ?: ($member->first_name . ' ' . $member->last_name)); ?></strong>
                        <?php if ($member->age): ?>
                        <br><small>Age: <?php echo intval($member->age); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html($member->user_email); ?>
                        <?php if ($member->phone): ?>
                        <br><small><?php echo esc_html($member->phone); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo ucfirst($member->membership_type ?: 'standard'); ?></td>
                    <td>
                        <span class="jgk-status-<?php echo esc_attr($member->status); ?>">
                            <?php echo ucfirst($member->status); ?>
                        </span>
                        <?php if ($member->status === 'expired' && $member->expiry_date): ?>
                        <br><small>Expired: <?php echo date('M j, Y', strtotime($member->expiry_date)); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if (isset($member->is_public) && $member->is_public == 1): ?>
                        <span style="background: #46b450; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                            👁️ PUBLIC
                        </span>
                        <?php else: ?>
                        <span style="background: #999; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                            🔒 HIDDEN
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($member->all_coaches): ?>
                        <?php echo esc_html($member->all_coaches); ?>
                        <br><small style="color: #666;"><?php echo substr_count($member->all_coaches, ',') + 1; ?> coach(es)</small>
                        <?php else: ?>
                        <em>No coach assigned</em>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($member->created_at)); ?></td>
                    <td class="jgk-actions">
                        <div class="jgk-action-buttons">
                            <!-- Status Actions -->
                            <?php if ($member->status === 'pending'): ?>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('jgk_members_action'); ?>
                                <input type="hidden" name="action" value="approve_member">
                                <input type="hidden" name="member_id" value="<?php echo $member->id; ?>">
                                <input type="submit" class="button button-small jgk-button-approve" value="Approve" 
                                       onclick="return confirm('Are you sure you want to approve this member?')">
                            </form>
                            <?php endif; ?>
                            
                            <!-- Edit Member -->
                            <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members&action=edit&member_id=' . $member->id); ?>" 
                               class="button button-small jgk-button-edit">
                                Edit Member
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $page
            );
            echo paginate_links($pagination_args);
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.jgk-form-section {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.jgk-form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.jgk-form-field {
    flex: 1;
}

.jgk-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.jgk-form-field input,
.jgk-form-field select,
.jgk-form-field textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.jgk-form-field small {
    color: #666;
    font-size: 12px;
}

.jgk-form-section h3 {
    color: #2271b1;
    border-bottom: 2px solid #2271b1;
    padding-bottom: 5px;
    margin: 20px 0 15px 0;
}

.jgk-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.jgk-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.jgk-stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #2271b1;
}

.jgk-stat-label {
    color: #666;
    margin-top: 5px;
}

.jgk-actions {
    white-space: nowrap;
}

.jgk-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 5px;
    align-items: flex-start;
}

.jgk-action-buttons .button {
    margin: 0;
    white-space: nowrap;
}

.jgk-button-approve { background: #00a32a; color: white; border-color: #00a32a; }

.jgk-status-active { color: #00a32a; font-weight: bold; }
.jgk-status-pending { color: #b32d2e; font-weight: bold; }
.jgk-status-expired { color: #d63638; font-weight: bold; }
.jgk-status-suspended { color: #dba617; font-weight: bold; }

@media (max-width: 768px) {
    .jgk-form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .jgk-action-buttons {
        flex-direction: row;
        flex-wrap: wrap;
    }
}
</style>

<script>
function toggleAddMemberForm() {
    const form = document.getElementById('add-member-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    
    if (form.style.display === 'block') {
        // Auto-generate display name when first/last name are filled
        const firstName = document.getElementById('first_name');
        const lastName = document.getElementById('last_name');
        const displayName = document.getElementById('display_name');
        
        function updateDisplayName() {
            if (firstName.value && lastName.value && !displayName.value) {
                displayName.value = firstName.value + ' ' + lastName.value;
            }
        }
        
        firstName.addEventListener('blur', updateDisplayName);
        lastName.addEventListener('blur', updateDisplayName);
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const addMemberForm = document.querySelector('#add-member-form form');
    if (addMemberForm) {
        addMemberForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
        });
    }
});
</script>