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

// Handle form submissions
$message = '';
$message_type = '';

if (isset($_POST['action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_coach_action')) {
        wp_die(__('Security check failed.'));
    }

    switch ($_POST['action']) {
        case 'update_coach':
            $coach_id = intval($_POST['coach_id']);
            $specialties = array_map('sanitize_text_field', $_POST['specialties']);
            $experience = intval($_POST['experience']);
            $bio = sanitize_textarea_field($_POST['bio']);
            
            $result = JuniorGolfKenya_Database::update_coach($coach_id, array(
                'specialties' => implode(',', $specialties),
                'experience_years' => $experience,
                'bio' => $bio
            ));
            
            if ($result) {
                $message = 'Coach information updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update coach information.';
                $message_type = 'error';
            }
            break;

        case 'assign_members':
            $coach_id = intval($_POST['coach_id']);
            $member_ids = array_map('intval', $_POST['member_ids']);
            
            $success_count = 0;
            foreach ($member_ids as $member_id) {
                if (JuniorGolfKenya_User_Manager::assign_coach($member_id, $coach_id)) {
                    $success_count++;
                }
            }
            
            $message = "Assigned {$success_count} member(s) to coach successfully!";
            $message_type = 'success';
            break;
    }
}

// Get coaches
$coaches = JuniorGolfKenya_Database::get_coaches();
$all_members = JuniorGolfKenya_Database::get_members();
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">Coaches Management</h1>
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
                        <span class="jgk-status-<?php echo esc_attr($coach->status); ?>">
                            <?php echo ucfirst($coach->status); ?>
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
    <div class="jgk-modal-content">
        <div class="jgk-modal-header">
            <h2>Assign Members to Coach</h2>
            <span class="jgk-modal-close" onclick="closeAssignModal()">&times;</span>
        </div>
        <div class="jgk-modal-body">
            <form method="post" id="assign-form">
                <?php wp_nonce_field('jgk_coach_action'); ?>
                <input type="hidden" name="action" value="assign_members">
                <input type="hidden" name="coach_id" id="assign-coach-id">
                
                <p><strong>Coach:</strong> <span id="assign-coach-name"></span></p>
                
                <div class="jgk-form-field">
                    <label>Select Members to Assign:</label>
                    <div class="members-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                        <?php foreach ($all_members as $member): ?>
                        <?php if ($member->status === 'approved' && !$member->coach_id): ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="member_ids[]" value="<?php echo $member->id; ?>">
                            <?php echo esc_html($member->full_name . ' (' . $member->membership_type . ')'); ?>
                        </label>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="jgk-form-field">
                    <input type="submit" class="button-primary" value="Assign Members">
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
    document.getElementById('assign-modal').style.display = 'block';
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