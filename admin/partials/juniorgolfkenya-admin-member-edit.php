<?php
/**
 * Edit member form
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

// $edit_member and $member_parents should be defined by the including file
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">
        Edit Member: <?php echo esc_html($edit_member->first_name . ' ' . $edit_member->last_name); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members'); ?>" class="page-title-action">
        ← Back to Members List
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['updated'])): ?>
    <div class="notice notice-success is-dismissible">
        <p>Member updated successfully!</p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
    <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <div class="jgk-form-section">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('jgk_members_action'); ?>
            <input type="hidden" name="action" value="edit_member">
            <input type="hidden" name="member_id" value="<?php echo $edit_member->id; ?>">
            
            <!-- Profile Image Section -->
            <h2>Profile Photo</h2>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label>Current Photo</label>
                    <div style="margin: 10px 0;">
                        <?php echo JuniorGolfKenya_Media::get_profile_image_html($edit_member->id, 'medium', array('style' => 'max-width: 200px; border-radius: 10px;')); ?>
                    </div>
                    <?php if (!empty($edit_member->profile_image_id)): ?>
                    <label style="display: flex; align-items: center; gap: 5px; margin-top: 10px;">
                        <input type="checkbox" name="delete_profile_image" value="1">
                        Delete current profile photo
                    </label>
                    <?php endif; ?>
                </div>
                <div class="jgk-form-field">
                    <label for="profile_image">Upload New Photo</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                    <small>Max 5MB. JPG, PNG, GIF or WebP format.</small>
                    <div id="profile_image_preview" style="margin-top: 10px;"></div>
                </div>
            </div>

            <!-- Birth Certificate Section -->
            <h2>Birth Certificate</h2>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label>Current Birth Certificate</label>
                    <div style="margin: 10px 0;">
                        <?php
                        $birth_certificate_id = get_user_meta($edit_member->user_id, 'jgk_birth_certificate', true);
                        if (!empty($birth_certificate_id)) {
                            $attachment_url = wp_get_attachment_url($birth_certificate_id);
                            $file_type = get_post_mime_type($birth_certificate_id);
                            $file_name = basename(get_attached_file($birth_certificate_id));
                            
                            echo '<div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">';
                            if (strpos($file_type, 'image/') === 0) {
                                echo '<img src="' . esc_url($attachment_url) . '" style="max-width: 100px; max-height: 100px; border-radius: 5px;" alt="Birth Certificate">';
                            } else {
                                echo '<span class="dashicons dashicons-media-document" style="font-size: 48px; color: #666;"></span>';
                            }
                            echo '<div>';
                            echo '<strong>' . esc_html($file_name) . '</strong><br>';
                            echo '<small>' . esc_html($file_type) . '</small><br>';
                            echo '<a href="' . esc_url($attachment_url) . '" target="_blank" class="button button-small">View/Download</a>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<p style="color: #666; font-style: italic;">No birth certificate uploaded</p>';
                        }
                        ?>
                    </div>
                    <?php if (!empty($birth_certificate_id)): ?>
                    <label style="display: flex; align-items: center; gap: 5px; margin-top: 10px;">
                        <input type="checkbox" name="delete_birth_certificate" value="1">
                        Delete current birth certificate
                    </label>
                    <?php endif; ?>
                </div>
                <div class="jgk-form-field">
                    <label for="birth_certificate">Upload New Birth Certificate</label>
                    <input type="file" id="birth_certificate" name="birth_certificate" accept=".pdf,image/*">
                    <small>Max 10MB. PDF, JPG, PNG, GIF or WebP format.</small>
                    <div id="birth_certificate_preview" style="margin-top: 10px;"></div>
                </div>
            </div>

            <h2>Personal Information</h2>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($edit_member->first_name); ?>" required>
                </div>
                <div class="jgk-form-field">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($edit_member->last_name); ?>" required>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($edit_member->user_email ?? ''); ?>" required>
                </div>
                <div class="jgk-form-field">
                    <label for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($edit_member->display_name ?? ''); ?>">
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="date_of_birth">Date de naissance *</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" 
                           value="<?php echo esc_attr($edit_member->date_of_birth ?? ''); ?>" 
                           required 
                           max="<?php echo date('Y-m-d', strtotime('-2 years')); ?>"
                           min="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                    <small style="color: #666;">Âge requis : 2-17 ans</small>
                </div>   
                <div class="jgk-f rm-field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="male" <?php selected($edit_member->gender ?? '', 'male'); ?>>Male</option>
                        <option value="female" <?php selected($edit_member->gender ?? '', 'female'); ?>>Female</option>
                        <option value="other" <?php selected($edit_member->gender ?? '', 'other'); ?>>Other</option>
                        <option value="prefer_not_to_say" <?php selected($edit_member->gender ?? '', 'prefer_not_to_say'); ?>>Prefer not to say</option>
                    </select>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($edit_member->phone ?? ''); ?>" placeholder="+254...">
                </div>
                <div class="jgk-form-field">
                    <label for="handicap">Golf Handicap</label>
                    <input type="number" id="handicap" name="handicap" step="0.1" min="0" max="54" value="<?php echo esc_attr($edit_member->handicap ?? ''); ?>">
                </div>
            </div>
            
            <h2>Membership Details</h2>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <div class="jgk-form-field-info" style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; border-radius: 5px;">
                        <label style="font-weight: 600; color: #0073aa; display: block; margin-bottom: 5px;">
                            Membership Type
                        </label>
                        <p style="margin: 0; color: #555;">
                            <strong>Junior</strong> 
                            <?php 
                            if (!empty($edit_member->date_of_birth)) {
                                try {
                                    $birthdate = new DateTime($edit_member->date_of_birth);
                                    $today = new DateTime();
                                    $age = $today->diff($birthdate)->y;
                                    echo "({$age} ans)";
                                } catch (Exception $e) {
                                    echo "(âge non calculable)";
                                }
                            }
                            ?>
                        </p>
                        <input type="hidden" name="membership_type" value="junior">
                        <?php if (!empty($edit_member->membership_type) && $edit_member->membership_type !== 'junior'): ?>
                        <p style="color: #d63638; font-size: 12px; margin: 5px 0 0 0;">
                            ⚠️ Ancien type : <?php echo esc_html(ucfirst($edit_member->membership_type)); ?> (sera converti en Junior lors de la sauvegarde)
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="jgk-form-field">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php selected($edit_member->status, 'active'); ?>>Active</option>
                        <option value="pending" <?php selected($edit_member->status, 'pending'); ?>>Pending Approval</option>
                        <option value="suspended" <?php selected($edit_member->status, 'suspended'); ?>>Suspended</option>
                        <option value="expired" <?php selected($edit_member->status, 'expired'); ?>>Expired</option>
                    </select>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="club_affiliation">Club Affiliation</label>
                    <input type="text" id="club_affiliation" name="club_affiliation" value="<?php echo esc_attr($edit_member->club_affiliation ?? ''); ?>">
                </div>
                <div class="jgk-form-field">
                    <label for="medical_conditions">Medical Conditions</label>
                    <textarea id="medical_conditions" name="medical_conditions" rows="2"><?php echo esc_textarea($edit_member->medical_conditions ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="coach_id">Assigned Coach</label>
                    <select id="coach_id" name="coach_id">
                        <option value="">No coach assigned</option>
                        <?php if (!empty($coaches)): ?>
                            <?php foreach ($coaches as $coach): ?>
                            <option value="<?php echo esc_attr($coach->ID); ?>" <?php selected($edit_member->coach_id ?? 0, $coach->ID); ?>>
                                <?php echo esc_html($coach->display_name); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No coaches available</option>
                        <?php endif; ?>
                    </select>
                    <small>Select a coach to assign to this member</small>
                    <?php if (empty($coaches)): ?>
                    <p style="color: #d63638; font-size: 11px; margin-top: 5px;">
                        ℹ️ No coaches found. Please create a coach first in <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-coaches'); ?>">Coaches Management</a>.
                    </p>
                    <?php endif; ?>
                </div>
                <div class="jgk-form-field" style="background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa; border-radius: 4px;">
                    <label for="is_public" style="font-weight: 600; color: #0073aa; font-size: 14px;">
                        🌐 Public Visibility Control
                    </label>
                    <select id="is_public" name="is_public" style="width: 100%; padding: 8px; margin-top: 8px;">
                        <option value="1" <?php selected($edit_member->is_public ?? 1, 1); ?>>✅ Visible Publicly - Show in directories, galleries, and public listings</option>
                        <option value="0" <?php selected($edit_member->is_public ?? 1, 0); ?>>🔒 Hidden from Public - Only visible to administrators and coaches</option>
                    </select>
                    <small style="display: block; margin-top: 8px; color: #666; font-style: italic;">
                        <strong>Important:</strong> Controls whether this member appears on public pages, member directories, galleries, and team listings. 
                        When hidden, the member profile is only accessible to logged-in administrators and coaches.
                    </small>
                </div>
            </div>
            
            <h2>Emergency Contact</h2>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo esc_attr($edit_member->emergency_contact_name ?? ''); ?>">
                </div>
                <div class="jgk-form-field">
                    <label for="emergency_contact_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo esc_attr($edit_member->emergency_contact_phone ?? ''); ?>">
                </div>
            </div>
            
            <h2>Additional Information</h2>
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo esc_textarea($edit_member->address ?? ''); ?></textarea>
                </div>
                <div class="jgk-form-field">
                    <label for="biography">Biography</label>
                    <textarea id="biography" name="biography" rows="3" placeholder="Tell us about the member..."><?php echo esc_textarea($edit_member->biography ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label>
                        <input type="checkbox" name="consent_photography" value="yes" <?php checked($edit_member->consent_photography ?? 'no', 'yes'); ?>>
                        Consent to Photography
                    </label>
                    <small>Permission to use photos for promotional purposes</small>
                </div>
                <div class="jgk-form-field">
                    <label>
                        <input type="checkbox" name="parental_consent" value="1" <?php checked($edit_member->parental_consent ?? 0, 1); ?>>
                        Parental Consent (for minors)
                    </label>
                    <small>Confirmation of parent/guardian approval</small>
                </div>
            </div>
            
            <?php if (!empty($member_parents)): ?>
            <h2>Parents/Guardians</h2>
            <div class="parents-list">
                <?php foreach ($member_parents as $parent): ?>
                <div class="parent-entry" style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                        <?php echo esc_html($parent->first_name . ' ' . $parent->last_name); ?>
                        <span style="font-weight: normal; font-size: 14px; color: #666;">
                            (<?php echo ucfirst($parent->relationship); ?>)
                        </span>
                    </h4>
                    <p style="margin: 5px 0;">
                        <strong>Phone:</strong> <?php echo esc_html($parent->phone ?: 'N/A'); ?><br>
                        <strong>Email:</strong> <?php echo esc_html($parent->email ?: 'N/A'); ?><br>
                        <?php if ($parent->occupation): ?>
                        <strong>Occupation:</strong> <?php echo esc_html($parent->occupation); ?><br>
                        <?php endif; ?>
                        <?php if ($parent->is_primary_contact): ?>
                        <span style="color: #46b450; font-weight: bold;">✓ Primary Contact</span>
                        <?php endif; ?>
                        <?php if ($parent->emergency_contact): ?>
                        <span style="color: #d54e21; font-weight: bold;">⚠ Emergency Contact</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endforeach; ?>
                <p><em>Note: To edit parent/guardian information, please use the dedicated parent management interface (coming soon).</em></p>
            </div>
            <?php endif; ?>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="Update Member">
                <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members'); ?>" class="button">Cancel</a>
            </p>
        </form>
    </div>
</div>

<script>
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
            preview.innerHTML = '<p><strong>New photo preview:</strong></p><img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px; border-radius: 10px; border: 3px solid #0073aa;">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});
</script>
