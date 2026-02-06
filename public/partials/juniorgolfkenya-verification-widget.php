<?php
/**
 * Membership Verification Widget - Public View
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

// Handle verification request
$verification_result = null;
$search_query = '';
$error_message = '';

if (isset($_POST['verify_membership']) && !empty($_POST['search_query'])) {
    // Verify nonce
    if (!isset($_POST['jgk_verify_nonce']) || !wp_verify_nonce($_POST['jgk_verify_nonce'], 'jgk_verify_membership')) {
        $error_message = 'Security check failed. Please try again.';
    } else {
        $search_query = sanitize_text_field($_POST['search_query']);
        
        global $wpdb;
        $members_table = $wpdb->prefix . 'jgk_members';
        $users_table = $wpdb->users;
        
        // Search for member by membership number, name, or email
        $member = $wpdb->get_row($wpdb->prepare("
            SELECT 
                m.*,
                COALESCE(m.email, u.user_email) as user_email,
                u.display_name
            FROM {$members_table} m
            LEFT JOIN {$users_table} u ON m.user_id = u.ID
            WHERE m.membership_number = %s
            OR CONCAT(m.first_name, ' ', m.last_name) LIKE %s
            OR m.email = %s
            OR u.user_email = %s
            LIMIT 1
        ", $search_query, '%' . $wpdb->esc_like($search_query) . '%', $search_query, $search_query));
        
        if ($member) {
            $verification_result = $member;
        } else {
            $error_message = 'No member found with the provided information.';
        }
    }
}
?>

<div class="jgk-verification-widget">
    <div class="jgk-widget-header">
        <h2>Verify Membership</h2>
        <p>Enter a membership number, name, or email to verify membership status.</p>
    </div>

    <!-- Search Form -->
    <div class="jgk-verification-form">
        <form method="post" class="jgk-search-form">
            <?php wp_nonce_field('jgk_verify_membership', 'jgk_verify_nonce'); ?>
            <div class="jgk-form-group">
                <label for="search_query">Membership Number, Name, or Email</label>
                <div class="jgk-search-input-group">
                    <input 
                        type="text" 
                        id="search_query" 
                        name="search_query" 
                        value="<?php echo esc_attr($search_query); ?>" 
                        placeholder="Enter membership number, name, or email..."
                        required
                    >
                    <button type="submit" name="verify_membership" class="jgk-btn jgk-btn-primary">
                        <span class="dashicons dashicons-search"></span>
                        Verify
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Error Message -->
    <?php if ($error_message): ?>
        <div class="jgk-notice jgk-notice-error">
            <span class="dashicons dashicons-warning"></span>
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Verification Result -->
    <?php if ($verification_result): ?>
        <div class="jgk-verification-result">
            <div class="jgk-result-header <?php echo esc_attr('jgk-status-' . $verification_result->status); ?>">
                <span class="jgk-status-icon">
                    <?php if ($verification_result->status === 'active'): ?>
                        <span class="dashicons dashicons-yes-alt"></span>
                    <?php elseif ($verification_result->status === 'expired'): ?>
                        <span class="dashicons dashicons-clock"></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-warning"></span>
                    <?php endif; ?>
                </span>
                <div class="jgk-status-text">
                    <h3>Membership <?php echo ucfirst($verification_result->status); ?></h3>
                    <p class="jgk-status-description">
                        <?php
                        switch ($verification_result->status) {
                            case 'active':
                                echo 'This member has an active membership.';
                                break;
                            case 'expired':
                                echo 'This membership has expired.';
                                break;
                            case 'pending':
                                echo 'This membership is pending approval.';
                                break;
                            case 'suspended':
                                echo 'This membership is currently suspended.';
                                break;
                            default:
                                echo 'Membership status: ' . ucfirst($verification_result->status);
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="jgk-result-details">
                <div class="jgk-detail-row">
                    <span class="jgk-detail-label">Member Name:</span>
                    <span class="jgk-detail-value">
                        <?php echo esc_html($verification_result->first_name . ' ' . $verification_result->last_name); ?>
                    </span>
                </div>

                <div class="jgk-detail-row">
                    <span class="jgk-detail-label">Membership Number:</span>
                    <span class="jgk-detail-value">
                        <?php echo esc_html($verification_result->membership_number); ?>
                    </span>
                </div>

                <div class="jgk-detail-row">
                    <span class="jgk-detail-label">Membership Type:</span>
                    <span class="jgk-detail-value">
                        <?php echo esc_html(ucfirst($verification_result->membership_type)); ?>
                    </span>
                </div>

                <?php if ($verification_result->date_joined): ?>
                <div class="jgk-detail-row">
                    <span class="jgk-detail-label">Member Since:</span>
                    <span class="jgk-detail-value">
                        <?php echo date('F j, Y', strtotime($verification_result->date_joined)); ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($verification_result->expiry_date): ?>
                <div class="jgk-detail-row">
                    <span class="jgk-detail-label">Expiry Date:</span>
                    <span class="jgk-detail-value">
                        <?php 
                        $expiry = strtotime($verification_result->expiry_date);
                        echo date('F j, Y', $expiry);
                        
                        // Show days until expiry if active
                        if ($verification_result->status === 'active' && $expiry > time()) {
                            $days_until_expiry = floor(($expiry - time()) / (60 * 60 * 24));
                            if ($days_until_expiry <= 30) {
                                echo ' <span class="jgk-expiry-warning">(' . $days_until_expiry . ' days remaining)</span>';
                            }
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($verification_result->club_affiliation): ?>
                <div class="jgk-detail-row">
                    <span class="jgk-detail-label">Club:</span>
                    <span class="jgk-detail-value">
                        <?php echo esc_html($verification_result->club_affiliation); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div class="jgk-result-footer">
                <p class="jgk-verification-note">
                    <span class="dashicons dashicons-info"></span>
                    This verification is provided for informational purposes only. 
                    For official inquiries, please contact the administration.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$verification_result && !$error_message && empty($search_query)): ?>
        <div class="jgk-verification-info">
            <div class="jgk-info-card">
                <span class="dashicons dashicons-id"></span>
                <h3>Verify Member Status</h3>
                <p>You can verify a member's status by entering:</p>
                <ul>
                    <li>Membership Number (e.g., JGK-2024-001)</li>
                    <li>Full Name (First and Last name)</li>
                    <li>Email Address</li>
                </ul>
            </div>

            <div class="jgk-info-card">
                <span class="dashicons dashicons-privacy"></span>
                <h3>Privacy Notice</h3>
                <p>Only limited public information is displayed for privacy protection. Sensitive personal details are not shown.</p>
            </div>

            <div class="jgk-info-card">
                <span class="dashicons dashicons-phone"></span>
                <h3>Need Help?</h3>
                <p>If you have questions about membership verification or status, please contact our office.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
