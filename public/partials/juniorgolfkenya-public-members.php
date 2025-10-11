<?php
/**
 * Public Members Gallery Shortcode
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

/**
 * Display public members gallery
 * 
 * Shortcode: [jgk_public_members]
 * 
 * Attributes:
 * - limit: Number of members to display (default: 12)
 * - columns: Number of columns (default: 4)
 * - orderby: Order by field (default: first_name)
 * - order: ASC or DESC (default: ASC)
 * - type: Filter by membership type (optional)
 */

global $wpdb;

// Get shortcode attributes
$atts = shortcode_atts(array(
    'limit' => 12,
    'columns' => 4,
    'orderby' => 'first_name',
    'order' => 'ASC',
    'type' => ''
), $atts);

// Build query
$table_name = $wpdb->prefix . 'jgk_members';
$query = "SELECT * FROM {$table_name} WHERE is_public = 1 AND status = 'active'";

// Add type filter if specified
if (!empty($atts['type'])) {
    $query .= $wpdb->prepare(" AND membership_type = %s", sanitize_text_field($atts['type']));
}

// Add ordering
$allowed_orderby = array('first_name', 'last_name', 'handicap', 'created_at');
$orderby = in_array($atts['orderby'], $allowed_orderby) ? $atts['orderby'] : 'first_name';
$order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';
$query .= " ORDER BY {$orderby} {$order}";

// Add limit
$limit = intval($atts['limit']);
$query .= $wpdb->prepare(" LIMIT %d", $limit);

// Execute query
$members = $wpdb->get_results($query);

// Start output
ob_start();
?>

<div class="jgk-public-members-gallery" data-columns="<?php echo esc_attr($atts['columns']); ?>">
    
    <?php if (empty($members)): ?>
    
    <div class="jgk-no-members">
        <p>No members to display at this time.</p>
    </div>
    
    <?php else: ?>
    
    <div class="jgk-members-grid" style="--columns: <?php echo esc_attr($atts['columns']); ?>;">
        
        <?php foreach ($members as $member): ?>
        
        <div class="jgk-member-card">
            <div class="jgk-member-photo">
                <?php 
                if (!empty($member->profile_image_id)) {
                    echo wp_get_attachment_image($member->profile_image_id, 'medium', false, array(
                        'class' => 'jgk-member-img',
                        'alt' => esc_attr($member->first_name . ' ' . $member->last_name)
                    ));
                } else {
                    echo '<div class="jgk-member-img-placeholder">';
                    echo '<span>' . strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <div class="jgk-member-info">
                <h3 class="jgk-member-name">
                    <?php echo esc_html($member->first_name . ' ' . $member->last_name); ?>
                </h3>
                
                <?php if (!empty($member->handicap)): ?>
                <div class="jgk-member-handicap">
                    <span class="jgk-label">Handicap:</span>
                    <span class="jgk-value"><?php echo esc_html($member->handicap); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($member->club_affiliation)): ?>
                <div class="jgk-member-club">
                    <span class="jgk-label">Club:</span>
                    <span class="jgk-value"><?php echo esc_html($member->club_affiliation); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($member->biography)): ?>
                <div class="jgk-member-bio">
                    <?php echo wp_trim_words(esc_html($member->biography), 20, '...'); ?>
                </div>
                <?php endif; ?>
                
                <div class="jgk-member-meta">
                    <span class="jgk-member-type">
                        <?php echo ucfirst(str_replace('_', ' ', $member->membership_type)); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <?php endforeach; ?>
        
    </div>
    
    <?php endif; ?>
    
</div>

<style>
.jgk-public-members-gallery {
    margin: 30px 0;
    padding: 20px 0;
}

.jgk-members-grid {
    display: grid;
    grid-template-columns: repeat(var(--columns, 4), 1fr);
    gap: 30px;
    margin: 20px 0;
}

.jgk-member-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.jgk-member-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.jgk-member-photo {
    position: relative;
    padding-top: 100%; /* 1:1 Aspect Ratio */
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.jgk-member-photo img.jgk-member-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.jgk-member-img-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.jgk-member-img-placeholder span {
    font-size: 48px;
    font-weight: bold;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.jgk-member-info {
    padding: 20px;
}

.jgk-member-name {
    margin: 0 0 12px 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.jgk-member-handicap,
.jgk-member-club {
    margin: 8px 0;
    font-size: 14px;
    color: #666;
}

.jgk-label {
    font-weight: 600;
    color: #333;
}

.jgk-value {
    color: #667eea;
    font-weight: 500;
}

.jgk-member-bio {
    margin: 12px 0;
    font-size: 14px;
    line-height: 1.6;
    color: #555;
}

.jgk-member-meta {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.jgk-member-type {
    display: inline-block;
    padding: 4px 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.jgk-no-members {
    text-align: center;
    padding: 60px 20px;
    background: #f5f5f5;
    border-radius: 12px;
}

.jgk-no-members p {
    margin: 0;
    font-size: 18px;
    color: #666;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .jgk-members-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .jgk-members-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px;
    }
    
    .jgk-member-name {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .jgk-members-grid {
        grid-template-columns: 1fr !important;
        gap: 20px;
    }
    
    .jgk-member-card {
        max-width: 400px;
        margin: 0 auto;
    }
}
</style>

<?php
return ob_get_clean();
