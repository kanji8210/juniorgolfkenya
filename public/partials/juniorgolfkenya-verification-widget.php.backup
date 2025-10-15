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
                u.user_email,
                u.display_name
            FROM {$members_table} m
            LEFT JOIN {$users_table} u ON m.user_id = u.ID
            WHERE m.membership_number = %s
            OR CONCAT(m.first_name, ' ', m.last_name) LIKE %s
            OR u.user_email = %s
            LIMIT 1
        ", $search_query, '%' . $wpdb->esc_like($search_query) . '%', $search_query));
        
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

<style>
/* Verification Widget Styles */
.jgk-verification-widget {
    max-width: 900px;
    margin: 40px auto;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
}

.jgk-widget-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
}

.jgk-widget-header h2 {
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 700;
}

.jgk-widget-header p {
    margin: 0;
    font-size: 16px;
    opacity: 0.95;
}

/* Search Form */
.jgk-verification-form {
    background: white;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.jgk-search-form {
    max-width: 600px;
    margin: 0 auto;
}

.jgk-form-group {
    margin-bottom: 0;
}

.jgk-form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 15px;
}

.jgk-search-input-group {
    display: flex;
    gap: 10px;
}

.jgk-search-input-group input[type="text"] {
    flex: 1;
    padding: 14px 18px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s;
}

.jgk-search-input-group input[type="text"]:focus {
    outline: none;
    border-color: #667eea;
}

.jgk-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 24px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.jgk-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.jgk-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

/* Notice */
.jgk-notice {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 8px;
    margin: 20px 30px;
}

.jgk-notice-error {
    background: #fee;
    color: #c33;
    border-left: 4px solid #c33;
}

.jgk-notice .dashicons {
    font-size: 20px;
}

.jgk-notice p {
    margin: 0;
}

/* Verification Result */
.jgk-verification-result {
    background: white;
    margin: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.jgk-result-header {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 30px;
    color: white;
}

.jgk-status-active {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.jgk-status-expired {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.jgk-status-pending {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.jgk-status-suspended {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
}

.jgk-status-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    font-size: 28px;
}

.jgk-status-text h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 700;
}

.jgk-status-text p {
    margin: 0;
    opacity: 0.95;
}

/* Result Details */
.jgk-result-details {
    padding: 30px;
    background: #f8f9fa;
}

.jgk-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 15px 0;
    border-bottom: 1px solid #e9ecef;
}

.jgk-detail-row:last-child {
    border-bottom: none;
}

.jgk-detail-label {
    font-weight: 600;
    color: #7f8c8d;
}

.jgk-detail-value {
    color: #2c3e50;
    font-weight: 500;
}

.jgk-expiry-warning {
    color: #e74c3c;
    font-size: 13px;
    font-weight: 600;
}

/* Result Footer */
.jgk-result-footer {
    padding: 20px 30px;
    background: #fff;
    border-top: 2px solid #f0f0f0;
}

.jgk-verification-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 0;
    color: #7f8c8d;
    font-size: 13px;
    line-height: 1.6;
}

.jgk-verification-note .dashicons {
    margin-top: 2px;
}

/* Info Cards */
.jgk-verification-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    padding: 30px;
    background: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.jgk-info-card {
    padding: 25px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #667eea;
}

.jgk-info-card .dashicons {
    font-size: 32px;
    color: #667eea;
    margin-bottom: 15px;
}

.jgk-info-card h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 18px;
}

.jgk-info-card p {
    margin: 0 0 10px 0;
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.6;
}

.jgk-info-card ul {
    margin: 10px 0 0 20px;
    padding: 0;
    color: #7f8c8d;
    font-size: 14px;
}

.jgk-info-card li {
    margin-bottom: 5px;
}

/* Responsive */
@media (max-width: 768px) {
    .jgk-verification-widget {
        margin: 20px 10px;
    }
    
    .jgk-widget-header {
        padding: 20px 15px;
    }
    
    .jgk-widget-header h2 {
        font-size: 24px;
    }
    
    .jgk-verification-form {
        padding: 20px 15px;
    }
    
    .jgk-search-input-group {
        flex-direction: column;
    }
    
    .jgk-search-input-group input[type="text"],
    .jgk-btn {
        width: 100%;
    }
    
    .jgk-result-header {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .jgk-result-details {
        padding: 20px 15px;
    }
    
    .jgk-detail-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .jgk-verification-info {
        grid-template-columns: 1fr;
        padding: 20px 15px;
    }
}
</style>
