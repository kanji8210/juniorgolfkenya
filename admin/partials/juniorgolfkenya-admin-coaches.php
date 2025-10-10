<?php
/**
 * Provide a admin area view for coaches management
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
if (!current_user_can('manage_coaches')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Load required classes
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-database.php';
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-user-manager.php';

// Handle AJAX request for getting coach members
if (isset($_POST['action']) && $_POST['action'] === 'jgk_get_coach_members' && wp_doing_ajax()) {
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'jgk_coach_members')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check permissions
    if (!current_user_can('manage_coaches')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $coach_id = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;
    
    if (!$coach_id) {
        wp_send_json_error(array('message' => 'Invalid coach ID'));
    }
    
    global $wpdb;
    $members_table = $wpdb->prefix . 'jgk_members';
    $junction_table = $wpdb->prefix . 'jgk_coach_members';
    
    // Get assigned members with their details
    $query = $wpdb->prepare("
        SELECT 
            m.id,
            m.first_name,
            m.last_name,
            m.membership_type,
            cm.is_primary,
            cm.assigned_date
        FROM {$junction_table} cm
        INNER JOIN {$members_table} m ON cm.member_id = m.id
        WHERE cm.coach_id = %d AND cm.status = 'active'
        ORDER BY cm.is_primary DESC, cm.assigned_date DESC
    ", $coach_id);
    
    $results = $wpdb->get_results($query);
    
    $members = array();
    foreach ($results as $row) {
        $members[] = array(
            'id' => $row->id,
            'name' => $row->first_name . ' ' . $row->last_name,
            'membership_type' => $row->membership_type,
            'is_primary' => (bool) $row->is_primary,
            'assigned_date' => $row->assigned_date
        );
    }
    
    wp_send_json_success(array('members' => $members));
}

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_coach_action')) {
        wp_die(__('Security check failed.'));
    }

    switch ($_POST['action']) {
        case 'create_coach':
            $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
            $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
            $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            $experience = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : 0;
            $specialties = isset($_POST['specialties']) && is_array($_POST['specialties']) ? array_map('sanitize_text_field', $_POST['specialties']) : array();
            $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
            
            // Validate required fields
            if (empty($first_name) || empty($last_name) || empty($email)) {
                $message = 'First name, last name, and email are required fields.';
                $message_type = 'error';
                break;
            }
            
            // Create WordPress user
            $user_id = wp_create_user($email, wp_generate_password(), $email);
            
            if (is_wp_error($user_id)) {
                $message = 'Failed to create coach: ' . $user_id->get_error_message();
                $message_type = 'error';
            } else {
                // Update user details
                wp_update_user(array(
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'display_name' => $first_name . ' ' . $last_name,
                    'role' => 'jgk_coach' // Fixed: Changed from jgf_coach to jgk_coach
                ));
                
                // Create coach profile
                JuniorGolfKenya_User_Manager::create_coach_profile($user_id);
                
                // Store phone and experience in user meta (not in coach_profiles table)
                update_user_meta($user_id, 'phone', $phone);
                update_user_meta($user_id, 'experience_years', $experience);
                
                // Update coach profile (only columns that exist in jgk_coach_profiles)
                global $wpdb;
                $coach_table = $wpdb->prefix . 'jgk_coach_profiles'; // Fixed: Changed from jgf to jgk
                $wpdb->update(
                    $coach_table,
                    array(
                        'specialties' => implode(',', $specialties),
                        'bio' => $bio,
                        'verification_status' => 'approved'
                    ),
                    array('user_id' => $user_id)
                );
                
                // Send new user notification
                wp_new_user_notification($user_id, null, 'both');
                
                $message = 'Coach created successfully! Login credentials sent to ' . $email;
                $message_type = 'success';
                
                // Redirect to coaches list
                $_GET['action'] = '';
            }
            break;
            
        case 'update_coach':
            $coach_id = intval($_POST['coach_id']);
            $specialties = isset($_POST['specialties']) && is_array($_POST['specialties']) 
                ? array_map('sanitize_text_field', $_POST['specialties']) 
                : array();
            $experience = isset($_POST['experience']) ? intval($_POST['experience']) : 0;
            $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
            
            // Update user meta for experience (stored separately)
            update_user_meta($coach_id, 'experience_years', $experience);
            
            // Update coach profile table
            global $wpdb;
            $coach_table = $wpdb->prefix . 'jgk_coach_profiles'; // Fixed: Changed from jgf to jgk
            $result = $wpdb->update(
                $coach_table,
                array(
                    'specialties' => implode(',', $specialties),
                    'bio' => $bio
                ),
                array('user_id' => $coach_id)
            );
            
            if ($result !== false) {
                $message = 'Coach information updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update coach information.';
                $message_type = 'error';
            }
            break;

        case 'assign_members':
            $coach_id = intval($_POST['coach_id']);
            $member_ids = isset($_POST['member_ids']) && is_array($_POST['member_ids']) 
                ? array_map('intval', $_POST['member_ids']) 
                : array();
            
            if (empty($member_ids)) {
                $message = "Please select at least one member to assign.";
                $message_type = 'error';
            } else {
                global $wpdb;
                $table = $wpdb->prefix . 'jgk_coach_members';
                $success_count = 0;
                
                foreach ($member_ids as $member_id) {
                    // Check if assignment already exists
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $table WHERE coach_id = %d AND member_id = %d",
                        $coach_id, $member_id
                    ));
                    
                    if (!$exists) {
                        $result = $wpdb->insert($table, array(
                            'coach_id' => $coach_id,
                            'member_id' => $member_id,
                            'assigned_by' => get_current_user_id(),
                            'status' => 'active'
                        ));
                        
                        if ($result) {
                            $success_count++;
                        }
                    }
                }
                
                $message = "Assigned {$success_count} member(s) to coach successfully!";
                $message_type = 'success';
            }
            break;
            
        case 'remove_member':
            $coach_id = intval($_POST['coach_id']);
            $member_id = intval($_POST['member_id']);
            
            global $wpdb;
            $table = $wpdb->prefix . 'jgk_coach_members';
            
            $deleted = $wpdb->delete($table, array(
                'coach_id' => $coach_id,
                'member_id' => $member_id
            ));
            
            if ($deleted) {
                $message = "Member removed from coach successfully!";
                $message_type = 'success';
            } else {
                $message = "Failed to remove member from coach.";
                $message_type = 'error';
            }
            break;
    }
}

// Get coaches
$coaches = JuniorGolfKenya_Database::get_coaches();

// Get ALL members (not just first page) for assignment modal
$all_members = JuniorGolfKenya_Database::get_members(1, 999, ''); // Load up to 999 members

// Check if we're in add mode
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

if ($action === 'add') {
    // Display add coach form
    ?>
    <div class="wrap jgk-admin-container">
        <h1 class="wp-heading-inline">Add New Coach</h1>
        <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-coaches'); ?>" class="page-title-action">
            ← Back to Coaches List
        </a>
        <hr class="wp-header-end">
        
        <?php if ($message): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="jgk-form-section">
            <form method="post">
                <?php wp_nonce_field('jgk_coach_action'); ?>
                <input type="hidden" name="action" value="create_coach">
                
                <h2>Coach Information</h2>
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
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                        <small>This will be used as the username for login</small>
                    </div>
                    <div class="jgk-form-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+254...">
                    </div>
                </div>
                
                <div class="jgk-form-row">
                    <div class="jgk-form-field">
                        <label for="experience_years">Years of Experience *</label>
                        <input type="number" id="experience_years" name="experience_years" min="0" max="50" required>
                    </div>
                    <div class="jgk-form-field">
                        <label for="specialties">Specialties</label>
                        <select id="specialties" name="specialties[]" multiple style="height: 100px;">
                            <option value="junior_coaching">Junior Coaching</option>
                            <option value="swing_technique">Swing Technique</option>
                            <option value="putting">Putting</option>
                            <option value="short_game">Short Game</option>
                            <option value="mental_game">Mental Game</option>
                            <option value="fitness">Fitness & Conditioning</option>
                            <option value="competition">Competition Preparation</option>
                        </select>
                        <small>Hold Ctrl (Cmd on Mac) to select multiple specialties</small>
                    </div>
                </div>
                
                <div class="jgk-form-row">
                    <div class="jgk-form-field" style="width: 100%;">
                        <label for="bio">Biography</label>
                        <textarea id="bio" name="bio" rows="5" placeholder="Brief biography and coaching philosophy..."></textarea>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Create Coach">
                    <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-coaches'); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
    </div>
    <?php
    return; // Stop execution here, don't show the coaches list
}
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">Coaches Management</h1>
    <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-coaches&action=add'); ?>" class="page-title-action">
        Add New Coach
    </a>
    <hr class="wp-header-end">

    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <!-- Coaches Overview -->
    <div class="jgk-coach-stats">
        <div class="jgk-stat-card">
            <h3><?php echo count($coaches); ?></h3>
            <p>Total Coaches</p>
        </div>
        <div class="jgk-stat-card">
            <h3><?php echo count(array_filter($coaches, function($c) { return $c->status === 'active'; })); ?></h3>
            <p>Active Coaches</p>
        </div>
        <div class="jgk-stat-card">
            <h3><?php echo array_sum(array_map(function($c) { return $c->member_count; }, $coaches)); ?></h3>
            <p>Total Assignments</p>
        </div>
    </div>

    <!-- Coaches Table -->
    <div class="jgk-table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Coach</th>
                    <th>Email</th>
                    <th>Experience</th>
                    <th>Specialties</th>
                    <th>Assigned Members</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($coaches)): ?>
                <tr>
                    <td colspan="8">No coaches found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($coaches as $coach): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($coach->display_name); ?></strong>
                        <?php if ($coach->bio): ?>
                        <div class="coach-bio" title="<?php echo esc_attr($coach->bio); ?>">
                            <?php echo esc_html(strlen($coach->bio) > 40 ? substr($coach->bio, 0, 40) . '...' : $coach->bio); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($coach->user_email); ?></td>
                    <td><?php echo esc_html($coach->experience_years ?: 'N/A'); ?> years</td>
                    <td>
                        <?php 
                        $specialties = $coach->specialties ? explode(',', $coach->specialties) : array();
                        echo esc_html(implode(', ', $specialties) ?: 'None specified');
                        ?>
                    </td>
                    <td>
                        <span class="member-count"><?php echo intval($coach->member_count); ?></span>
                        <button class="button button-small" onclick="openAssignModal(<?php echo $coach->ID; ?>, '<?php echo esc_js($coach->display_name); ?>')">
                            Assign More
                        </button>
                    </td>
                    <td>
                        <?php if ($coach->avg_rating): ?>
                        <span class="rating-stars">
                            <?php 
                            $rating = floatval($coach->avg_rating);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '★' : '☆';
                            }
                            echo ' (' . number_format($rating, 1) . ')';
                            ?>
                        </span>
                        <?php else: ?>
                        <em>No ratings yet</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="jgk-status-<?php echo esc_attr($coach->status ?? 'pending'); ?>">
                            <?php echo ucfirst($coach->status ?? 'pending'); ?>
                        </span>
                    </td>
                    <td>
                        <button class="button button-small" onclick="openEditModal(<?php echo $coach->ID; ?>)">
                            Edit
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-reports&coach_id=' . $coach->ID); ?>" class="button button-small">
                            View Reports
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Coach Modal -->
<div id="edit-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Edit Coach Information</h2>
            <span class="jgk-modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="edit-form">
                <?php wp_nonce_field('jgk_coach_action'); ?>
                <input type="hidden" name="action" value="update_coach">
                <input type="hidden" name="coach_id" id="edit-coach-id">
                
                <div class="jgk-form-field">
                    <label for="edit-experience">Experience (years):</label>
                    <input type="number" id="edit-experience" name="experience" min="0" max="50">
                </div>
                
                <div class="jgk-form-field">
                    <label for="edit-specialties">Specialties:</label>
                    <div class="specialties-checkboxes">
                        <label><input type="checkbox" name="specialties[]" value="Putting"> Putting</label>
                        <label><input type="checkbox" name="specialties[]" value="Driving"> Driving</label>
                        <label><input type="checkbox" name="specialties[]" value="Short Game"> Short Game</label>
                        <label><input type="checkbox" name="specialties[]" value="Mental Game"> Mental Game</label>
                        <label><input type="checkbox" name="specialties[]" value="Youth Training"> Youth Training</label>
                        <label><input type="checkbox" name="specialties[]" value="Tournament Prep"> Tournament Prep</label>
                    </div>
                </div>
                
                <div class="jgk-form-field">
                    <label for="edit-bio">Bio:</label>
                    <textarea id="edit-bio" name="bio" rows="4" placeholder="Coach biography..."></textarea>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Update Coach">
                    <button type="button" class="button" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Members Modal -->
<div id="assign-modal" class="jgk-modal" style="display: none;">
    <div class="jgk-modal-content" style="max-width: 800px;">
        <div class="jgk-modal-header">
            <h2>Manage Members for Coach</h2>
            <span class="jgk-modal-close" onclick="closeAssignModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <!-- Currently Assigned Members -->
            <div style="margin-bottom: 30px;">
                <h3>Currently Assigned Members</h3>
                <div id="current-members-list" style="border: 1px solid #ddd; padding: 10px; max-height: 200px; overflow-y: auto;">
                    <p style="color: #999; font-style: italic;">Loading...</p>
                </div>
            </div>
            
            <!-- Add New Members -->
            <form method="post" id="assign-form">
                <?php wp_nonce_field('jgk_coach_action'); ?>
                <input type="hidden" name="action" value="assign_members">
                <input type="hidden" name="coach_id" id="assign-coach-id">
                <input type="hidden" name="member_ids[]" id="selected-member-ids">
                
                <h3>Add New Members</h3>
                <p><strong>Coach:</strong> <span id="assign-coach-name"></span></p>
                
                <div class="jgk-form-field">
                    <label for="member-search-input">Search Members:</label>
                    <div style="position: relative;">
                        <input type="text" 
                               id="member-search-input" 
                               placeholder="Type member name to search..." 
                               style="width: 100%; padding: 8px; font-size: 14px;">
                        <div id="member-search-results" 
                             style="display: none; position: absolute; z-index: 1000; background: white; border: 1px solid #ddd; max-height: 250px; overflow-y: auto; width: 100%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        </div>
                    </div>
                    <small>Start typing to search members by name, email, or membership number</small>
                </div>
                
                <div class="jgk-form-field" id="selected-members-display" style="margin-top: 15px; display: none;">
                    <label>Selected Members to Add:</label>
                    <div id="selected-members-list" style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9; max-height: 150px; overflow-y: auto;">
                    </div>
                </div>
                
                <div class="jgk-form-field" style="margin-top: 15px;">
                    <input type="submit" class="button-primary" value="Add Selected Members" id="add-members-btn" disabled>
                    <button type="button" class="button" onclick="closeAssignModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.jgk-coach-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.jgk-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    flex: 1;
}

.jgk-stat-card h3 {
    font-size: 2em;
    margin: 0 0 10px 0;
    color: #2271b1;
}

.jgk-stat-card p {
    margin: 0;
    color: #666;
}

.coach-bio {
    font-size: 12px;
    color: #666;
    font-style: italic;
}

.rating-stars {
    color: #ffb900;
}

.member-count {
    font-weight: bold;
    margin-right: 10px;
}

.specialties-checkboxes {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.specialties-checkboxes label {
    display: flex;
    align-items: center;
    gap: 5px;
}
</style>

<script>
function openEditModal(coachId) {
    // Fetch coach data and populate form
    const coaches = <?php echo json_encode($coaches); ?>;
    const coach = coaches.find(c => c.ID == coachId);
    
    if (coach) {
        document.getElementById('edit-coach-id').value = coachId;
        document.getElementById('edit-experience').value = coach.experience_years || '';
        document.getElementById('edit-bio').value = coach.bio || '';
        
        // Clear and set specialties
        const checkboxes = document.querySelectorAll('input[name="specialties[]"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        if (coach.specialties) {
            const specialties = coach.specialties.split(',');
            specialties.forEach(specialty => {
                const checkbox = document.querySelector(`input[name="specialties[]"][value="${specialty.trim()}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
    }
    
    document.getElementById('edit-modal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    document.getElementById('edit-form').reset();
}

function openAssignModal(coachId, coachName) {
    document.getElementById('assign-coach-id').value = coachId;
    document.getElementById('assign-coach-name').textContent = coachName;
    
    // Reset search and selection
    document.getElementById('member-search-input').value = '';
    document.getElementById('member-search-results').style.display = 'none';
    document.getElementById('selected-members-display').style.display = 'none';
    document.getElementById('selected-members-list').innerHTML = '';
    document.getElementById('selected-member-ids').value = '';
    document.getElementById('add-members-btn').disabled = true;
    
    // Store coach ID for search filtering
    window.currentCoachId = coachId;
    window.selectedMembers = [];
    window.assignedMemberIds = [];
    
    // Load currently assigned members
    loadAssignedMembers(coachId);
    
    document.getElementById('assign-modal').style.display = 'block';
}

// Member search functionality
let searchTimeout = null;
document.getElementById('member-search-input')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.trim();
    const resultsContainer = document.getElementById('member-search-results');
    
    if (searchTerm.length < 2) {
        resultsContainer.style.display = 'none';
        return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        // Show loading
        resultsContainer.innerHTML = '<div style="padding: 10px; color: #999;">Searching...</div>';
        resultsContainer.style.display = 'block';
        
        // Search members via AJAX
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=jgk_search_members&search=' + encodeURIComponent(searchTerm) + 
                  '&_wpnonce=<?php echo wp_create_nonce('jgk_search_members'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.members.length > 0) {
                let html = '';
                data.data.members.forEach(member => {
                    const isAssigned = window.assignedMemberIds.includes(member.id);
                    const isSelected = window.selectedMembers.some(m => m.id === member.id);
                    const disabled = isAssigned || isSelected;
                    
                    html += `
                        <div class="member-search-result" 
                             style="padding: 8px; border-bottom: 1px solid #eee; cursor: ${disabled ? 'not-allowed' : 'pointer'}; 
                                    background: ${disabled ? '#f5f5f5' : 'white'}; opacity: ${disabled ? '0.6' : '1'};"
                             onclick="${disabled ? '' : `selectMember(${member.id}, '${member.name.replace(/'/g, "\\'")}', '${member.type}')`}">
                            <strong>${member.name}</strong> 
                            <span style="color: #666; font-size: 12px;">(${member.type})</span>
                            ${isAssigned ? '<span style="color: #46b450; font-size: 11px;">✓ Already assigned</span>' : ''}
                            ${isSelected ? '<span style="color: #0073aa; font-size: 11px;">✓ Selected</span>' : ''}
                        </div>
                    `;
                });
                resultsContainer.innerHTML = html;
            } else {
                resultsContainer.innerHTML = '<div style="padding: 10px; color: #999;">No members found</div>';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            resultsContainer.innerHTML = '<div style="padding: 10px; color: #d63638;">Error searching members</div>';
        });
    }, 300);
});

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    const searchInput = document.getElementById('member-search-input');
    const resultsContainer = document.getElementById('member-search-results');
    if (searchInput && !searchInput.contains(e.target) && resultsContainer && !resultsContainer.contains(e.target)) {
        resultsContainer.style.display = 'none';
    }
});

function selectMember(memberId, memberName, memberType) {
    // Add to selected members
    if (!window.selectedMembers.some(m => m.id === memberId)) {
        window.selectedMembers.push({
            id: memberId,
            name: memberName,
            type: memberType
        });
        updateSelectedMembersList();
    }
    
    // Clear search
    document.getElementById('member-search-input').value = '';
    document.getElementById('member-search-results').style.display = 'none';
}

function removeSelectedMember(memberId) {
    window.selectedMembers = window.selectedMembers.filter(m => m.id !== memberId);
    updateSelectedMembersList();
}

function updateSelectedMembersList() {
    const container = document.getElementById('selected-members-list');
    const displayDiv = document.getElementById('selected-members-display');
    const submitBtn = document.getElementById('add-members-btn');
    const hiddenInput = document.getElementById('selected-member-ids');
    
    if (window.selectedMembers.length === 0) {
        displayDiv.style.display = 'none';
        submitBtn.disabled = true;
        hiddenInput.value = '';
        return;
    }
    
    displayDiv.style.display = 'block';
    submitBtn.disabled = false;
    
    let html = '';
    window.selectedMembers.forEach(member => {
        html += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 5px; margin-bottom: 5px; background: white; border: 1px solid #ddd; border-radius: 3px;">
                <span>
                    <strong>${member.name}</strong> 
                    <span style="color: #666; font-size: 11px;">(${member.type})</span>
                </span>
                <button type="button" class="button button-small" onclick="removeSelectedMember(${member.id})" style="color: #d63638;">
                    Remove
                </button>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Update hidden input with selected IDs
    const form = document.getElementById('assign-form');
    // Remove existing member_ids inputs
    form.querySelectorAll('input[name="member_ids[]"]').forEach(input => {
        if (input.id !== 'selected-member-ids') {
            input.remove();
        }
    });
    
    // Add hidden inputs for each selected member
    window.selectedMembers.forEach(member => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'member_ids[]';
        input.value = member.id;
        form.appendChild(input);
    });
}

function loadAssignedMembers(coachId) {
    const container = document.getElementById('current-members-list');
    container.innerHTML = '<p style="color: #999; font-style: italic;">Loading...</p>';
    
    // Make AJAX call to get assigned members
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=jgk_get_coach_members&coach_id=' + coachId + '&_wpnonce=<?php echo wp_create_nonce('jgk_coach_members'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.members.length > 0) {
            // Store assigned member IDs for filtering search results
            window.assignedMemberIds = data.data.members.map(m => m.id);
            
            let html = '<div style="display: flex; flex-direction: column; gap: 5px;">';
            data.data.members.forEach(member => {
                html += `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 5px; background: #f9f9f9; border-radius: 3px;">
                        <span>
                            <strong>${member.name}</strong> 
                            <span style="color: #666; font-size: 11px;">(${member.membership_type})</span>
                            ${member.is_primary ? '<span style="color: #46b450; font-size: 11px;">★ Primary</span>' : ''}
                        </span>
                        <button type="button" class="button button-small" onclick="removeMember(${coachId}, ${member.id}, '${member.name}')" style="color: #d63638;">
                            Remove
                        </button>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        } else {
            window.assignedMemberIds = [];
            container.innerHTML = '<p style="color: #999; font-style: italic;">No members currently assigned to this coach.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading members:', error);
        container.innerHTML = '<p style="color: #d63638;">Error loading members. Please try again.</p>';
    });
}

function removeMember(coachId, memberId, memberName) {
    if (!confirm(`Are you sure you want to remove ${memberName} from this coach?`)) {
        return;
    }
    
    // Create hidden form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <?php wp_nonce_field('jgk_coach_action'); ?>
        <input type="hidden" name="action" value="remove_member">
        <input type="hidden" name="coach_id" value="${coachId}">
        <input type="hidden" name="member_id" value="${memberId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function closeAssignModal() {
    document.getElementById('assign-modal').style.display = 'none';
    document.getElementById('assign-form').reset();
}

// Close modals when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('edit-modal');
    const assignModal = document.getElementById('assign-modal');
    
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === assignModal) {
        closeAssignModal();
    }
}
</script>