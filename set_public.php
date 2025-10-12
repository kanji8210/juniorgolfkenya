<?php
/**
 * Script pour mettre tous les membres en PUBLIC
 * 
 * COMMENT L'UTILISER:
 * 1. Accédez à ce fichier via navigateur: http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/set_public.php
 * 2. Cliquez sur le bouton pour mettre tous les membres en PUBLIC
 * 3. Vérifiez le résultat
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

global $wpdb;
$members_table = $wpdb->prefix . 'jgk_members';

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Set header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>JGK - Set All Members Public</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 3px solid #2271b1;
            padding-bottom: 15px;
        }
        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #0f5132;
            font-size: 16px;
        }
        .warning {
            background: #fff3cd;
            color: #664d03;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #664d03;
        }
        .info {
            background: #cfe2ff;
            color: #084298;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #084298;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin: 10px 5px 10px 0;
        }
        .button:hover {
            background: #135e96;
        }
        .button-danger {
            background: #d63638;
        }
        .button-danger:hover {
            background: #b32d2e;
        }
        .button-success {
            background: #00a32a;
        }
        .button-success:hover {
            background: #008a20;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #f0f0f1;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .stats {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }
        .badge-public {
            background: #46b450;
            color: white;
        }
        .badge-hidden {
            background: #999;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👁️ Set All Members to PUBLIC</h1>
        
        <?php
        if ($action === 'set_public') {
            // Execute the update
            echo '<div class="info">';
            echo '⏳ <strong>Updating members...</strong>';
            echo '</div>';
            
            // Get current stats before update
            $stats_before = $wpdb->get_results("
                SELECT is_public, COUNT(*) as count 
                FROM {$members_table} 
                GROUP BY is_public
            ");
            
            // Update all members to public
            $updated = $wpdb->query("
                UPDATE {$members_table} 
                SET is_public = 1 
                WHERE is_public = 0
            ");
            
            if ($wpdb->last_error) {
                echo '<div class="warning">';
                echo '❌ <strong>Error:</strong> ' . esc_html($wpdb->last_error);
                echo '</div>';
            } else {
                echo '<div class="success">';
                echo '✅ <strong>SUCCESS!</strong><br>';
                echo 'Updated <strong>' . $updated . '</strong> members from HIDDEN to PUBLIC.<br>';
                echo 'All members are now visible publicly.';
                echo '</div>';
                
                // Get updated stats
                $stats_after = $wpdb->get_results("
                    SELECT is_public, COUNT(*) as count 
                    FROM {$members_table} 
                    GROUP BY is_public
                ");
                
                echo '<h2>📊 Statistics After Update</h2>';
                echo '<table>';
                echo '<tr><th>Visibility</th><th>Count</th></tr>';
                foreach ($stats_after as $stat) {
                    $visibility = $stat->is_public == 1 ? '👁️ PUBLIC' : '🔒 HIDDEN';
                    $badge_class = $stat->is_public == 1 ? 'badge-public' : 'badge-hidden';
                    echo '<tr>';
                    echo '<td><span class="badge ' . $badge_class . '">' . $visibility . '</span></td>';
                    echo '<td><strong>' . $stat->count . '</strong></td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            echo '<a href="set_public.php" class="button">← Back</a>';
            echo '<a href="' . admin_url('admin.php?page=juniorgolfkenya-members') . '" class="button button-success">Go to Members Page</a>';
            
        } else {
            // Show current stats and confirmation
            $stats = $wpdb->get_results("
                SELECT is_public, COUNT(*) as count 
                FROM {$members_table} 
                GROUP BY is_public
            ");
            
            $total = 0;
            $public_count = 0;
            $hidden_count = 0;
            
            foreach ($stats as $stat) {
                $total += $stat->count;
                if ($stat->is_public == 1) {
                    $public_count = $stat->count;
                } else {
                    $hidden_count = $stat->count;
                }
            }
            
            echo '<div class="info">';
            echo '<strong>📋 Current Statistics:</strong><br>';
            echo 'Total Members: <strong>' . $total . '</strong><br>';
            echo '👁️ PUBLIC: <strong>' . $public_count . '</strong><br>';
            echo '🔒 HIDDEN: <strong>' . $hidden_count . '</strong>';
            echo '</div>';
            
            if ($hidden_count > 0) {
                echo '<div class="warning">';
                echo '⚠️ <strong>' . $hidden_count . '</strong> members are currently HIDDEN.<br>';
                echo 'They cannot be viewed in the member details modal or public listings.<br><br>';
                echo '<strong>Do you want to make all members PUBLIC?</strong>';
                echo '</div>';
                
                echo '<a href="set_public.php?action=set_public" class="button button-success" onclick="return confirm(\'Are you sure you want to make ALL ' . $hidden_count . ' hidden members PUBLIC?\');">';
                echo '👁️ Yes, Make All Members PUBLIC';
                echo '</a>';
            } else {
                echo '<div class="success">';
                echo '✅ <strong>All members are already PUBLIC!</strong><br>';
                echo 'Nothing to update.';
                echo '</div>';
            }
            
            echo '<h2>📊 Detailed Breakdown</h2>';
            echo '<table>';
            echo '<tr><th>Visibility Status</th><th>Count</th><th>Percentage</th></tr>';
            foreach ($stats as $stat) {
                $visibility = $stat->is_public == 1 ? '👁️ PUBLIC' : '🔒 HIDDEN';
                $badge_class = $stat->is_public == 1 ? 'badge-public' : 'badge-hidden';
                $percentage = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
                
                echo '<tr>';
                echo '<td><span class="badge ' . $badge_class . '">' . $visibility . '</span></td>';
                echo '<td><strong>' . $stat->count . '</strong></td>';
                echo '<td>' . $percentage . '%</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            echo '<a href="' . admin_url('admin.php?page=juniorgolfkenya-members') . '" class="button">← Back to Members Page</a>';
        }
        ?>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
        
        <h2>ℹ️ About Visibility</h2>
        <div class="info">
            <strong>👁️ PUBLIC (is_public = 1):</strong><br>
            • Members can be viewed in member details modal<br>
            • Members appear in public listings and galleries<br>
            • Coaches and administrators can see all details<br><br>
            
            <strong>🔒 HIDDEN (is_public = 0):</strong><br>
            • Members are hidden from public view<br>
            • Only administrators can see them in the admin area<br>
            • Cannot view details in modal (causes "Network error")<br><br>
            
            <strong>💡 Recommendation:</strong> Set members to PUBLIC by default so they can be viewed normally.
        </div>
    </div>
</body>
</html>
