<?php
/**
 * Provide a admin area view for importing ARMember data
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Ensure user has proper capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Load the importer class
require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-armember-importer.php';

$message = '';
$message_type = 'info';
$import_stats = null;
$preview_data = null;

// Check if ARMember is active
$armember_active = JuniorGolfKenya_ARMember_Importer::is_armember_active();
$armember_count = $armember_active ? JuniorGolfKenya_ARMember_Importer::get_armember_members_count() : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jgk_import_action'])) {
    // Verify nonce
    if (!isset($_POST['jgk_import_nonce']) || !wp_verify_nonce($_POST['jgk_import_nonce'], 'jgk_import_armember')) {
        $message = 'Security check failed. Please try again.';
        $message_type = 'error';
    } else {
        $action = sanitize_text_field($_POST['jgk_import_action']);

        if ($action === 'preview') {
            // Preview import
            $preview_limit = isset($_POST['preview_limit']) ? intval($_POST['preview_limit']) : 10;
            $preview_data = JuniorGolfKenya_ARMember_Importer::preview_import($preview_limit);
            $message = 'Preview loaded. Review the data below before importing.';
            $message_type = 'info';

        } elseif ($action === 'import') {
            // Perform actual import
            $options = array(
                'skip_existing' => isset($_POST['skip_existing']) && $_POST['skip_existing'] === '1',
                'update_existing' => isset($_POST['update_existing']) && $_POST['update_existing'] === '1',
                'force_junior_type' => isset($_POST['force_junior_type']) && $_POST['force_junior_type'] === '1',
                'default_status' => sanitize_text_field($_POST['default_status'] ?? 'active')
            );

            $import_stats = JuniorGolfKenya_ARMember_Importer::batch_import($options);
            
            if ($import_stats['imported'] > 0 || $import_stats['updated'] > 0) {
                $message = sprintf(
                    'Import completed! Imported: %d, Updated: %d, Skipped: %d, Errors: %d',
                    $import_stats['imported'],
                    $import_stats['updated'],
                    $import_stats['skipped'],
                    $import_stats['errors']
                );
                $message_type = 'success';
            } else {
                $message = 'No members were imported. Check the settings and try again.';
                $message_type = 'warning';
            }
        }
    }
}
?>

<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-download" style="font-size: 28px; margin-right: 10px;"></span>
        Import from ARMember
    </h1>
    <hr class="wp-header-end">

    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
        <?php if ($import_stats && !empty($import_stats['messages'])): ?>
            <details>
                <summary>View detailed messages (<?php echo count($import_stats['messages']); ?>)</summary>
                <ul style="margin-top: 10px;">
                    <?php foreach ($import_stats['messages'] as $msg): ?>
                        <li><?php echo esc_html($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ARMember Status -->
    <div class="jgk-info-card" style="background: <?php echo $armember_active ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo $armember_active ? '#28a745' : '#dc3545'; ?>; padding: 20px; margin: 20px 0; border-radius: 4px;">
        <h2 style="margin-top: 0; color: <?php echo $armember_active ? '#155724' : '#721c24'; ?>;">
            <span class="dashicons dashicons-<?php echo $armember_active ? 'yes-alt' : 'warning'; ?>" style="font-size: 24px;"></span>
            ARMember Plugin Status
        </h2>
        <?php if ($armember_active): ?>
            <p style="color: #155724; margin: 10px 0;">
                <strong>✓ ARMember is active</strong><br>
                Found <strong><?php echo number_format($armember_count); ?></strong> member(s) available for import.
            </p>
        <?php else: ?>
            <p style="color: #721c24; margin: 10px 0;">
                <strong>✗ ARMember plugin is not active</strong><br>
                Please install and activate ARMember plugin to use this import feature.
            </p>
        <?php endif; ?>
    </div>

    <?php if ($armember_active && $armember_count > 0): ?>

    <!-- Import Statistics Summary -->
    <?php if ($import_stats): ?>
    <div class="jgk-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="jgk-stat-card" style="background: #28a745; color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 36px; font-weight: bold;"><?php echo $import_stats['imported']; ?></div>
            <div style="font-size: 14px; opacity: 0.9;">Imported</div>
        </div>
        <div class="jgk-stat-card" style="background: #17a2b8; color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 36px; font-weight: bold;"><?php echo $import_stats['updated']; ?></div>
            <div style="font-size: 14px; opacity: 0.9;">Updated</div>
        </div>
        <div class="jgk-stat-card" style="background: #ffc107; color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 36px; font-weight: bold;"><?php echo $import_stats['skipped']; ?></div>
            <div style="font-size: 14px; opacity: 0.9;">Skipped</div>
        </div>
        <div class="jgk-stat-card" style="background: #dc3545; color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div style="font-size: 36px; font-weight: bold;"><?php echo $import_stats['errors']; ?></div>
            <div style="font-size: 14px; opacity: 0.9;">Errors</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Import Options Form -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2 style="margin-top: 0; border-bottom: 3px solid #0073aa; padding-bottom: 10px; color: #0073aa;">
            <span class="dashicons dashicons-admin-settings"></span> Import Options
        </h2>

        <form method="post" id="import-form">
            <?php wp_nonce_field('jgk_import_armember', 'jgk_import_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="skip_existing">Skip Existing Members</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="skip_existing" id="skip_existing" value="1" checked>
                            Do not import members that already exist in Junior Golf Kenya
                        </label>
                        <p class="description">Recommended to avoid duplicates</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="update_existing">Update Existing Members</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="update_existing" id="update_existing" value="1">
                            Update existing JGK members with ARMember data
                        </label>
                        <p class="description">Only updates empty fields (phone, date of birth, gender)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="force_junior_type">Force Junior Membership Type</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="force_junior_type" id="force_junior_type" value="1" checked>
                            Force all imported members to have "junior" membership type
                        </label>
                        <p class="description">This is a Junior Golf program - all members should be juniors (2-17 years old)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="default_status">Default Status</label></th>
                    <td>
                        <select name="default_status" id="default_status">
                            <option value="active">Active</option>
                            <option value="pending">Pending Approval</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        <p class="description">Status to assign if ARMember status cannot be mapped</p>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <button type="submit" name="jgk_import_action" value="preview" class="button button-secondary" style="margin-right: 10px;">
                    <span class="dashicons dashicons-visibility"></span> Preview Import (First 10)
                </button>
                <button type="submit" name="jgk_import_action" value="import" class="button button-primary" 
                        onclick="return confirm('Are you sure you want to import <?php echo $armember_count; ?> member(s)? This action cannot be undone.');">
                    <span class="dashicons dashicons-download"></span> Start Import
                </button>
                <input type="hidden" name="preview_limit" value="10">
            </div>
        </form>
    </div>

    <!-- Preview Data Table -->
    <?php if ($preview_data && !empty($preview_data)): ?>
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2 style="margin-top: 0; border-bottom: 3px solid #667eea; padding-bottom: 10px; color: #667eea;">
            <span class="dashicons dashicons-list-view"></span> Import Preview (First <?php echo count($preview_data); ?> Members)
        </h2>
        
        <div style="overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_data as $row): ?>
                    <tr>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><strong><?php echo esc_html($row['name']); ?></strong></td>
                        <td><?php echo esc_html($row['email']); ?></td>
                        <td><?php echo esc_html($row['phone'] ?: '—'); ?></td>
                        <td>
                            <?php if ($row['age'] !== null): ?>
                                <span style="padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; 
                                      background: <?php echo ($row['age'] >= 2 && $row['age'] < 18) ? '#28a745' : '#dc3545'; ?>; color: white;">
                                    <?php echo $row['age']; ?> years
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">No DOB</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($row['gender'] ?: '—'); ?></td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 12px; font-size: 11px;">
                                ARM Status: <?php echo $row['arm_status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['exists_in_jgk']): ?>
                                <span style="color: #ffc107;">⚠️ Already exists</span>
                            <?php elseif ($row['will_import']): ?>
                                <span style="color: #28a745;">✓ Will import</span>
                            <?php else: ?>
                                <span style="color: #dc3545;">✗ Will skip (age)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Information Box -->
    <div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 20px; margin: 20px 0; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #0073aa;">
            <span class="dashicons dashicons-info"></span> Import Information
        </h3>
        <ul style="line-height: 1.8;">
            <li><strong>Data Mapping:</strong> ARMember user accounts will be mapped to Junior Golf Kenya members</li>
            <li><strong>Age Validation:</strong> Only members aged 2-17 years will be imported (unless force junior type is enabled)</li>
            <li><strong>Status Mapping:</strong> ARMember status codes will be automatically mapped to JGK statuses</li>
            <li><strong>User Meta:</strong> First name, last name, phone, date of birth, and gender will be imported</li>
            <li><strong>Safe Operation:</strong> The import will not modify existing WordPress user accounts</li>
            <li><strong>Audit Trail:</strong> All imports are logged in the JGK audit log</li>
        </ul>
    </div>

    <?php elseif ($armember_active && $armember_count === 0): ?>
    
    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #856404;">
            <span class="dashicons dashicons-warning"></span> No ARMember Data Found
        </h3>
        <p style="color: #856404;">
            ARMember plugin is active but no member data was found. Please ensure ARMember has members registered before importing.
        </p>
    </div>

    <?php endif; ?>
</div>

<style>
.jgk-admin-container h1 {
    display: flex;
    align-items: center;
}

.form-table th {
    width: 250px;
    font-weight: 600;
}

.form-table td label {
    display: flex;
    align-items: center;
    gap: 8px;
}

.button .dashicons {
    margin-right: 5px;
    font-size: 16px;
    line-height: 1;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Disable update_existing if skip_existing is checked
    $('#skip_existing').on('change', function() {
        if ($(this).is(':checked')) {
            $('#update_existing').prop('checked', false).prop('disabled', true);
        } else {
            $('#update_existing').prop('disabled', false);
        }
    });

    // Disable skip_existing if update_existing is checked
    $('#update_existing').on('change', function() {
        if ($(this).is(':checked')) {
            $('#skip_existing').prop('checked', false).prop('disabled', true);
        } else {
            $('#skip_existing').prop('disabled', false);
        }
    });
});
</script>
