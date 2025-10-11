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

        case 'assign_coach':
            if (isset($_POST['member_id']) && isset($_POST['coach_id'])) {
                $result = JuniorGolfKenya_User_Manager::assign_coach($_POST['member_id'], $_POST['coach_id']);
                if ($result) {
                    $message = 'Coach assigned successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to assign coach.';
                    $message_type = 'error';
                }
            }
            break;

        case 'create_member':
            $user_data = array(
                'user_login' => sanitize_user($_POST['username']),
                'user_email' => sanitize_email($_POST['email']),
                'display_name' => sanitize_text_field($_POST['display_name']),
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'user_pass' => $_POST['password']
            );
            
            $member_data = array(
                'membership_type' => sanitize_text_field($_POST['membership_type']),
                'status' => sanitize_text_field($_POST['status']),
                'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
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
            break;

        case 'edit_member':
            if (isset($_POST['member_id'])) {
                $member_id = intval($_POST['member_id']);
                
                // Update basic member data
                $member_data = array(
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                    'membership_type' => sanitize_text_field($_POST['membership_type']),
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

        case 'update_member_status':
            if (isset($_POST['member_id']) && isset($_POST['new_status'])) {
                $member_id = intval($_POST['member_id']);
                $new_status = sanitize_text_field($_POST['new_status']);
                $reason = sanitize_textarea_field($_POST['reason']);
                
                $result = JuniorGolfKenya_Database::update_member_status($member_id, $new_status, $reason);
                if ($result) {
                    $message = 'Member status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update member status.';
                    $message_type = 'error';
                }
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

// Get available coaches
$coaches = JuniorGolfKenya_User_Manager::get_available_coaches();
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
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth">
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
                    <label for="membership_type">Membership Type *</label>
                    <select id="membership_type" name="membership_type" required>
                        <option value="junior">Junior (Under 18)</option>
                        <option value="youth">Youth (18-25)</option>
                        <option value="adult">Adult (26+)</option>
                        <option value="senior">Senior (65+)</option>
                        <option value="family">Family Package</option>
                    </select>
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
                            
                            <!-- Change Status Button -->
                            <button class="button button-small jgk-button-status" 
                                    onclick="openStatusModal(<?php echo $member->id; ?>, '<?php echo esc_js($member->display_name); ?>', '<?php echo esc_js($member->status); ?>')">
                                Change Status
                            </button>
                            
                            <!-- View Details Button -->
                            <button class="button button-small jgk-button-view" 
                                    onclick="openMemberDetailsModal(<?php echo $member->id; ?>)">
                                View Details
                            </button>
                            
                            <!-- Assign Coach Button -->
                            <button class="button button-small jgk-button-coach" 
                                    onclick="openCoachModal(<?php echo $member->id; ?>, '<?php echo esc_js($member->display_name); ?>')">
                                <?php echo $member->all_coaches ? 'Manage Coaches' : 'Assign Coach'; ?>
                            </button>
                            
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

<!-- Change Status Modal -->
<div id="status-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Change Member Status</h2>
            <span class="jgk-modal-close" onclick="closeStatusModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="status-form">
                <?php wp_nonce_field('jgk_members_action'); ?>
                <input type="hidden" name="action" value="update_member_status">
                <input type="hidden" name="member_id" id="status-member-id">
                
                <p><strong>Member:</strong> <span id="status-member-name"></span></p>
                <p><strong>Current Status:</strong> <span id="status-current"></span></p>
                
                <div class="jgk-form-field">
                    <label for="new_status">New Status:</label>
                    <select id="new_status" name="new_status" required>
                        <option value="active">Active</option>
                        <option value="pending">Pending Approval</option>
                        <option value="expired">Expired</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                
                <div class="jgk-form-field">
                    <label for="status_reason">Reason for change:</label>
                    <textarea id="status_reason" name="reason" rows="3" placeholder="Optional reason for status change..."></textarea>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Update Status">
                    <button type="button" class="button" onclick="closeStatusModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Coach Modal -->
<div id="coach-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Assign Coach</h2>
            <span class="jgk-modal-close" onclick="closeCoachModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="coach-form">
                <?php wp_nonce_field('jgk_members_action'); ?>
                <input type="hidden" name="action" value="assign_coach">
                <input type="hidden" name="member_id" id="coach-member-id">
                
                <p><strong>Member:</strong> <span id="coach-member-name"></span></p>
                
                <div class="jgk-form-field">
                    <label for="coach_id">Select Coach:</label>
                    <select id="coach_id" name="coach_id" required>
                        <option value="">Choose a coach...</option>
                        <?php foreach ($coaches as $coach): ?>
                        <option value="<?php echo $coach->user_id; ?>">
                            <?php echo esc_html($coach->display_name); ?>
                            <?php if (isset($coach->member_count)): ?>
                            (<?php echo intval($coach->member_count); ?> members)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Assign Coach">
                    <button type="button" class="button" onclick="closeCoachModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Member Details Modal -->
<div id="member-details-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Member Details</h2>
            <span class="jgk-modal-close" onclick="closeMemberDetailsModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <div id="member-details-content">
                <div style="text-align: center; padding: 40px; color: #999;">
                    <div class="spinner" style="float: none; margin: 0 auto 10px;"></div>
                    <p>Loading member details...</p>
                </div>
            </div>
        </div>
    </div>
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
.jgk-button-status { background: #2271b1; color: white; border-color: #2271b1; }
.jgk-button-coach { background: #dba617; color: white; border-color: #dba617; }
.jgk-button-view { background: #666; color: white; border-color: #666; }

.jgk-status-active { color: #00a32a; font-weight: bold; }
.jgk-status-pending { color: #b32d2e; font-weight: bold; }
.jgk-status-expired { color: #d63638; font-weight: bold; }
.jgk-status-suspended { color: #dba617; font-weight: bold; }

.jgk-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.jgk-modal-content {
    background-color: #fff;
    margin: 10% auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 60%;
    max-width: 500px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.jgk-modal-header {
    padding: 20px;
    background-color: #f7f7f7;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.jgk-modal-header h2 {
    margin: 0;
}

.jgk-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.jgk-modal-close:hover {
    color: #000;
}

.jgk-modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

/* Scrollbar styling for modal body */
.jgk-modal-body::-webkit-scrollbar {
    width: 8px;
}

.jgk-modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.jgk-modal-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.jgk-modal-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

@media (max-width: 768px) {
    .jgk-form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .jgk-action-buttons {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .jgk-modal-content {
        width: 90%;
        margin: 5% auto;
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

function openStatusModal(memberId, memberName, currentStatus) {
    document.getElementById('status-member-id').value = memberId;
    document.getElementById('status-member-name').textContent = memberName;
    document.getElementById('status-current').textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
    document.getElementById('new_status').value = currentStatus;
    document.getElementById('status-modal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('status-modal').style.display = 'none';
    document.getElementById('status-form').reset();
}

function openCoachModal(memberId, memberName) {
    document.getElementById('coach-member-id').value = memberId;
    document.getElementById('coach-member-name').textContent = memberName;
    document.getElementById('coach-modal').style.display = 'block';
}

function closeCoachModal() {
    document.getElementById('coach-modal').style.display = 'none';
    document.getElementById('coach-form').reset();
}

function viewMemberDetails(memberId) {
    // Open member details in new tab/window
    const url = '<?php echo admin_url('admin.php?page=juniorgolfkenya-member-details&member_id='); ?>' + memberId;
    window.open(url, '_blank');
}

function openMemberDetailsModal(memberId) {
    const modal = document.getElementById('member-details-modal');
    const contentDiv = document.getElementById('member-details-content');
    
    // Show modal with loading state
    modal.style.display = 'block';
    contentDiv.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;"><div class="spinner" style="float: none; margin: 0 auto 10px;"></div><p>Loading member details...</p></div>';
    
    // Get AJAX URL (fallback for compatibility)
    const ajaxUrl = (typeof jgkAjax !== 'undefined') ? jgkAjax.ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Fetch member details via AJAX
    jQuery.post(ajaxUrl, {
        action: 'jgk_get_member_details',
        member_id: memberId,
        nonce: '<?php echo wp_create_nonce('jgk_get_member_details'); ?>'
    }, function(response) {
        if (response.success) {
            const member = response.data;
            let html = '<div class="member-details-wrapper">';
            
            // Profile section with larger photo and more details
            html += '<div class="member-profile-section" style="text-align: center; padding: 30px; border-bottom: 2px solid #2271b1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">';
            if (member.profile_image) {
                html += '<img src="' + member.profile_image + '" alt="Profile" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 20px; border: 5px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">';
            } else {
                html += '<div style="width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,0.2); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 60px; color: white; border: 5px solid white;"><span class="dashicons dashicons-admin-users"></span></div>';
            }
            html += '<h2 style="margin: 0 0 10px; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">' + member.display_name + '</h2>';
            if (member.membership_number) {
                html += '<div style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">Member #' + member.membership_number + '</div>';
            }
            html += '<span class="member-status-badge status-' + member.status + '" style="display: inline-block; padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: 700; background: white; color: #667eea; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">' + member.status.charAt(0).toUpperCase() + member.status.slice(1) + '</span>';
            html += '</div>';
            
            // Three-column layout for comprehensive details
            html += '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; padding: 25px; background: #f8f9fa;">';
            
            // Column 1 - Personal Information
            html += '<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
            html += '<h4 style="margin-top: 0; padding-bottom: 12px; border-bottom: 3px solid #667eea; color: #667eea; display: flex; align-items: center;"><span class="dashicons dashicons-admin-users" style="margin-right: 8px;"></span>Personal Information</h4>';
            html += '<table class="member-details-table" style="width: 100%; margin-top: 15px; font-size: 14px;">';
            if (member.first_name || member.last_name) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Full Name:</td><td style="padding: 10px 0; color: #333;">' + member.first_name + ' ' + member.last_name + '</td></tr>';
            if (member.email) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Email:</td><td style="padding: 10px 0;"><a href="mailto:' + member.email + '" style="color: #667eea; text-decoration: none;">' + member.email + '</a></td></tr>';
            if (member.phone) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Phone:</td><td style="padding: 10px 0; color: #333;"><a href="tel:' + member.phone + '" style="color: #667eea; text-decoration: none;">' + member.phone + '</a></td></tr>';
            if (member.date_of_birth) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Date of Birth:</td><td style="padding: 10px 0; color: #333;">' + member.date_of_birth + '</td></tr>';
            if (member.age) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Age:</td><td style="padding: 10px 0; color: #333;"><strong>' + member.age + ' years old</strong></td></tr>';
            if (member.gender) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Gender:</td><td style="padding: 10px 0; color: #333;">' + member.gender.charAt(0).toUpperCase() + member.gender.slice(1) + '</td></tr>';
            html += '</table>';
            
            // Address if available
            if (member.address) {
                html += '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0;">';
                html += '<div style="font-weight: 600; color: #555; margin-bottom: 8px; display: flex; align-items: center;"><span class="dashicons dashicons-location" style="margin-right: 5px;"></span>Address:</div>';
                html += '<p style="margin: 0; line-height: 1.6; color: #333; font-size: 13px;">' + member.address.replace(/\n/g, '<br>') + '</p>';
                html += '</div>';
            }
            html += '</div>';
            
            // Column 2 - Membership & Golf Details
            html += '<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
            html += '<h4 style="margin-top: 0; padding-bottom: 12px; border-bottom: 3px solid #667eea; color: #667eea; display: flex; align-items: center;"><span class="dashicons dashicons-admin-site" style="margin-right: 8px;"></span>Membership & Golf</h4>';
            html += '<table class="member-details-table" style="width: 100%; margin-top: 15px; font-size: 14px;">';
            if (member.membership_type) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Type:</td><td style="padding: 10px 0; color: #333;"><span style="background: #667eea; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px;">' + member.membership_type.charAt(0).toUpperCase() + member.membership_type.slice(1) + '</span></td></tr>';
            if (member.membership_number) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Number:</td><td style="padding: 10px 0; color: #333; font-family: monospace;"><strong>' + member.membership_number + '</strong></td></tr>';
            if (member.club_name) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Club:</td><td style="padding: 10px 0; color: #333;">' + member.club_name + '</td></tr>';
            if (member.date_joined) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Joined:</td><td style="padding: 10px 0; color: #333;">' + member.date_joined + '</td></tr>';
            if (member.handicap) html += '<tr><td style="padding: 10px 0; font-weight: 600; color: #555;">Handicap:</td><td style="padding: 10px 0; color: #333;"><strong style="font-size: 18px; color: #667eea;">' + member.handicap + '</strong></td></tr>';
            html += '</table>';
            
            // Assigned Coaches
            html += '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0;">';
            html += '<div style="font-weight: 600; color: #555; margin-bottom: 10px; display: flex; align-items: center;"><span class="dashicons dashicons-groups" style="margin-right: 5px;"></span>Assigned Coaches:</div>';
            if (member.coaches && member.coaches.length > 0) {
                member.coaches.forEach(function(coach) {
                    html += '<div style="padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);">';
                    html += '<div style="display: flex; align-items: center;"><span class="dashicons dashicons-admin-users" style="margin-right: 10px; font-size: 20px;"></span>';
                    html += '<span style="font-weight: 600; font-size: 14px;">' + coach.name + '</span></div>';
                    if (coach.is_primary) {
                        html += '<span style="background: rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; border: 1px solid rgba(255,255,255,0.5);">PRIMARY</span>';
                    }
                    html += '</div>';
                });
            } else {
                html += '<p style="margin: 0; color: #999; font-style: italic; font-size: 13px;">No coaches assigned yet</p>';
            }
            html += '</div>';
            html += '</div>';
            
            // Column 3 - Emergency & Additional
            html += '<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
            html += '<h4 style="margin-top: 0; padding-bottom: 12px; border-bottom: 3px solid #d63638; color: #d63638; display: flex; align-items: center;"><span class="dashicons dashicons-sos" style="margin-right: 8px;"></span>Emergency Contact</h4>';
            if (member.emergency_contact_name || member.emergency_contact_phone) {
                html += '<div style="padding: 15px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; border-radius: 8px; margin-top: 15px; box-shadow: 0 2px 6px rgba(214, 54, 56, 0.3);">';
                if (member.emergency_contact_name) html += '<div style="font-weight: 700; margin-bottom: 10px; font-size: 15px; display: flex; align-items: center;"><span class="dashicons dashicons-admin-users" style="margin-right: 8px;"></span>' + member.emergency_contact_name + '</div>';
                if (member.emergency_contact_phone) html += '<div style="display: flex; align-items: center; font-size: 14px;"><span class="dashicons dashicons-phone" style="margin-right: 8px; font-size: 16px;"></span><a href="tel:' + member.emergency_contact_phone + '" style="color: white; text-decoration: none; font-weight: 600;">' + member.emergency_contact_phone + '</a></div>';
                html += '</div>';
            } else {
                html += '<p style="margin-top: 15px; color: #999; font-style: italic; font-size: 13px;">No emergency contact provided</p>';
            }
            
            // Additional info
            if (member.biography) {
                html += '<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0;">';
                html += '<div style="font-weight: 600; color: #555; margin-bottom: 8px; display: flex; align-items: center;"><span class="dashicons dashicons-edit" style="margin-right: 5px;"></span>Biography:</div>';
                html += '<p style="margin: 0; line-height: 1.6; color: #333; font-size: 13px; max-height: 150px; overflow-y: auto;">' + member.biography.replace(/\n/g, '<br>') + '</p>';
                html += '</div>';
            }
            html += '</div>';
            
            html += '</div>'; // End three-column grid
            
            // Parents/Guardians section (full width with enhanced styling)
            if (member.parents && member.parents.length > 0) {
                html += '<div style="padding: 25px; background: white; margin: 0 25px 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
                html += '<h4 style="margin-top: 0; padding-bottom: 12px; border-bottom: 3px solid #667eea; color: #667eea; display: flex; align-items: center; font-size: 18px;"><span class="dashicons dashicons-groups" style="margin-right: 10px; font-size: 24px;"></span>Parents/Guardians (' + member.parents.length + ')</h4>';
                html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 20px;">';
                member.parents.forEach(function(parent) {
                    html += '<div style="padding: 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">';
                    html += '<div style="display: flex; align-items: center; margin-bottom: 15px;">';
                    html += '<div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; margin-right: 12px; border: 2px solid rgba(255,255,255,0.5);"><span class="dashicons dashicons-admin-users" style="font-size: 24px;"></span></div>';
                    html += '<div style="flex: 1;"><div style="font-weight: 700; font-size: 16px; margin-bottom: 3px;">' + parent.name + '</div>';
                    if (parent.relationship) html += '<div style="font-size: 12px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">' + parent.relationship + '</div>';
                    html += '</div></div>';
                    if (parent.phone || parent.email) {
                        html += '<div style="padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.3);">';
                        if (parent.phone) html += '<div style="margin-bottom: 8px; display: flex; align-items: center;"><span class="dashicons dashicons-phone" style="font-size: 16px; margin-right: 8px;"></span><a href="tel:' + parent.phone + '" style="color: white; text-decoration: none; font-weight: 500;">' + parent.phone + '</a></div>';
                        if (parent.email) html += '<div style="display: flex; align-items: center;"><span class="dashicons dashicons-email" style="font-size: 16px; margin-right: 8px;"></span><a href="mailto:' + parent.email + '" style="color: white; text-decoration: none; font-weight: 500; word-break: break-all;">' + parent.email + '</a></div>';
                        html += '</div>';
                    }
                    html += '</div>';
                });
                html += '</div>';
                html += '</div>';
            }
            
            html += '</div>'; // End wrapper
            
            contentDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = '<div style="text-align: center; padding: 40px; color: #d63638;"><span class="dashicons dashicons-warning" style="font-size: 48px; margin-bottom: 10px;"></span><p>' + (response.data || 'Failed to load member details') + '</p></div>';
        }
    }).fail(function() {
        contentDiv.innerHTML = '<div style="text-align: center; padding: 40px; color: #d63638;"><span class="dashicons dashicons-warning" style="font-size: 48px; margin-bottom: 10px;"></span><p>Network error. Please try again.</p></div>';
    });
}

function closeMemberDetailsModal() {
    document.getElementById('member-details-modal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const statusModal = document.getElementById('status-modal');
    const coachModal = document.getElementById('coach-modal');
    const memberDetailsModal = document.getElementById('member-details-modal');
    
    if (event.target === statusModal) {
        closeStatusModal();
    }
    if (event.target === coachModal) {
        closeCoachModal();
    }
    if (event.target === memberDetailsModal) {
        closeMemberDetailsModal();
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