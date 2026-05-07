<?php
/**
 * Admin page: Parents & Guardians
 *
 * Lists unique parent contacts derived from member parent/guardian records,
 * shows WordPress account linkage and children, and exposes the parent
 * account sync action.
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/admin/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('edit_members') && !current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

require_once JUNIORGOLFKENYA_PLUGIN_PATH . 'includes/class-juniorgolfkenya-parents.php';

global $wpdb;

$message = '';
$message_type = '';
$sync_results = null;

// Handle parent sync trigger
if (isset($_POST['jgk_sync_parents']) && check_admin_referer('jgk_sync_parents_action', 'jgk_sync_parents_nonce')) {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.'));
    }
    $limit = isset($_POST['parent_sync_limit']) ? max(0, intval($_POST['parent_sync_limit'])) : 0;
    $sync_results = JuniorGolfKenya_Parents::sync_parent_accounts_from_existing_data($limit);
    $message = sprintf(
        'Sync complete. Processed: %d, Created: %d, Updated: %d, Failed: %d.',
        $sync_results['processed'],
        $sync_results['created'],
        $sync_results['updated_existing'],
        $sync_results['failed']
    );
    $message_type = $sync_results['failed'] > 0 ? 'warning' : 'success';
}

$summary = JuniorGolfKenya_Parents::get_parent_account_summary(0);
$grouped = JuniorGolfKenya_Parents::get_parent_contacts_grouped_by_email();

// Search filter
$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
if ($search !== '') {
    $needle = strtolower($search);
    $grouped = array_values(array_filter($grouped, function ($entry) use ($needle) {
        $hay = strtolower(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? '') . ' ' . ($entry['email'] ?? '') . ' ' . ($entry['relationship'] ?? ''));
        return strpos($hay, $needle) !== false;
    }));
}

// Pagination
$per_page = 25;
$total = count($grouped);
$total_pages = max(1, (int) ceil($total / $per_page));
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$paged = min($paged, $total_pages);
$offset = ($paged - 1) * $per_page;
$page_rows = array_slice($grouped, $offset, $per_page);

$members_table = $wpdb->prefix . 'jgk_members';
?>
<div class="wrap jgk-admin-container">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        Parents &amp; Guardians
    </h1>
    <hr class="wp-header-end">

    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="jgk-summary-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin:16px 0;">
        <div class="jgk-summary-card" style="background:#fff;border:1px solid #ccd0d4;padding:12px;border-radius:4px;">
            Parent Records<br><strong style="font-size:20px;"><?php echo number_format($summary['total_parent_records']); ?></strong>
        </div>
        <div class="jgk-summary-card" style="background:#fff;border:1px solid #ccd0d4;padding:12px;border-radius:4px;">
            Unique Emails<br><strong style="font-size:20px;"><?php echo number_format($summary['unique_parent_emails']); ?></strong>
        </div>
        <div class="jgk-summary-card" style="background:#fff;border:1px solid #ccd0d4;padding:12px;border-radius:4px;">
            With WP Accounts<br><strong style="font-size:20px;color:#46b450;"><?php echo number_format($summary['emails_with_accounts']); ?></strong>
        </div>
        <div class="jgk-summary-card" style="background:#fff;border:1px solid #ccd0d4;padding:12px;border-radius:4px;">
            Missing Accounts<br><strong style="font-size:20px;color:#dc3232;"><?php echo number_format($summary['emails_missing_accounts']); ?></strong>
        </div>
        <div class="jgk-summary-card" style="background:#fff;border:1px solid #ccd0d4;padding:12px;border-radius:4px;">
            Tagged As Parent<br><strong style="font-size:20px;"><?php echo number_format($summary['emails_with_parent_role']); ?></strong>
        </div>
        <div class="jgk-summary-card" style="background:#fff;border:1px solid #ccd0d4;padding:12px;border-radius:4px;">
            Invalid/Missing Email<br><strong style="font-size:20px;"><?php echo number_format($summary['invalid_or_missing_email_records']); ?></strong>
        </div>
    </div>

    <?php if (current_user_can('manage_options')): ?>
    <div style="background:#fff;border:1px solid #ccd0d4;padding:16px;border-radius:4px;margin-bottom:20px;">
        <h2 style="margin-top:0;"><span class="dashicons dashicons-update"></span> Sync Parent Accounts</h2>
        <p>Create WordPress users for parent emails that do not yet have an account, and tag matching users with the JGK Parent role.</p>
        <form method="post" action="">
            <?php wp_nonce_field('jgk_sync_parents_action', 'jgk_sync_parents_nonce'); ?>
            <label for="parent_sync_limit">Max emails to process:</label>
            <input type="number" id="parent_sync_limit" name="parent_sync_limit" value="0" min="0" style="width:100px;">
            <span class="description">Use 0 to process all.</span>
            <button type="submit" name="jgk_sync_parents" class="button button-primary">
                <span class="dashicons dashicons-update" style="vertical-align:middle;"></span> Generate / Sync Parent Accounts
            </button>
        </form>
    </div>
    <?php endif; ?>

    <form method="get" style="margin-bottom:12px;">
        <input type="hidden" name="page" value="juniorgolfkenya-parents">
        <p class="search-box">
            <label class="screen-reader-text" for="parent-search-input">Search parents:</label>
            <input type="search" id="parent-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, email, relationship...">
            <input type="submit" class="button" value="Search">
            <?php if ($search !== ''): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=juniorgolfkenya-parents')); ?>" class="button">Clear</a>
            <?php endif; ?>
        </p>
    </form>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Relationship</th>
                <th>Children</th>
                <th>WP Account</th>
                <th>Parent Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($page_rows)): ?>
            <tr><td colspan="7">No parent records found.</td></tr>
        <?php else: foreach ($page_rows as $entry):
            $status = JuniorGolfKenya_Parents::get_parent_account_status($entry['email']);
            $children = array();
            if (!empty($entry['member_ids'])) {
                $ids = array_map('intval', $entry['member_ids']);
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $children = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, first_name, last_name, membership_number FROM {$members_table} WHERE id IN ($placeholders)",
                    ...$ids
                ));
            }
        ?>
            <tr>
                <td><strong><?php echo esc_html(trim($entry['first_name'] . ' ' . $entry['last_name'])); ?></strong></td>
                <td><a href="mailto:<?php echo esc_attr($entry['email']); ?>"><?php echo esc_html($entry['email']); ?></a></td>
                <td><?php echo esc_html(ucfirst($entry['relationship'] ?: '—')); ?></td>
                <td>
                    <?php echo intval($entry['children_count']); ?>
                    <?php if (!empty($children)): ?>
                        <br><small>
                        <?php foreach ($children as $i => $c):
                            if ($i > 0) echo ', ';
                            $url = admin_url('admin.php?page=juniorgolfkenya-members&action=edit&member_id=' . intval($c->id));
                            echo '<a href="' . esc_url($url) . '">' . esc_html(trim($c->first_name . ' ' . $c->last_name)) . '</a>';
                        endforeach; ?>
                        </small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($status['exists']): ?>
                        <span style="color:#46b450;">✓</span>
                        <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $status['user_id'])); ?>">
                            #<?php echo intval($status['user_id']); ?> <?php echo esc_html($status['username']); ?>
                        </a>
                    <?php else: ?>
                        <span style="color:#dc3232;">Missing</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($status['exists']): ?>
                        <?php echo $status['has_parent_role'] ? '<span style="color:#46b450;">Yes</span>' : '<span style="color:#dba617;">No</span>'; ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($status['exists']): ?>
                        <a class="button button-small" href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $status['user_id'])); ?>">Edit User</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
    <div class="tablenav" style="margin-top:12px;">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo number_format($total); ?> items</span>
            <?php
            $base_url = add_query_arg(array('page' => 'juniorgolfkenya-parents', 's' => $search), admin_url('admin.php'));
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%', $base_url),
                'format' => '',
                'current' => $paged,
                'total' => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ));
            ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($sync_results && !empty($sync_results['details'])): ?>
        <details style="margin-top:24px;">
            <summary><strong>Last sync details (<?php echo count($sync_results['details']); ?>)</strong></summary>
            <table class="widefat striped" style="margin-top:8px;">
                <thead><tr><th>Email</th><th>Status</th><th>Children</th><th>WP User</th><th>Message</th></tr></thead>
                <tbody>
                <?php foreach ($sync_results['details'] as $d): ?>
                    <tr>
                        <td><?php echo esc_html($d['email']); ?></td>
                        <td><?php echo esc_html(ucfirst($d['status'])); ?></td>
                        <td><?php echo intval($d['children_count'] ?? 0); ?></td>
                        <td>
                            <?php
                            $uid = intval($d['user_id'] ?? 0);
                            if ($uid > 0) {
                                echo '#' . esc_html($uid);
                                if (!empty($d['username'])) echo ' (' . esc_html($d['username']) . ')';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($d['message'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </details>
    <?php endif; ?>
</div>
