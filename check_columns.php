<?php
/**
 * Script de diagnostic pour vérifier la structure de la table wp_jgk_members
 * 
 * COMMENT L'UTILISER:
 * 1. Accédez à ce fichier via navigateur: http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/check_columns.php
 * 2. Il affichera toutes les colonnes de la table wp_jgk_members
 * 3. Vérifiez si vous avez club_name ou club_affiliation, handicap_index ou handicap
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

// Set header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>JGK Members Table Structure</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            max-width: 1200px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #2271b1;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:hover {
            background: #f6f7f7;
        }
        .highlight {
            background: #fff3cd;
            font-weight: 600;
        }
        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #0f5132;
        }
        .warning {
            background: #fff3cd;
            color: #664d03;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #664d03;
        }
        .error {
            background: #f8d7da;
            color: #842029;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #842029;
        }
        .info {
            background: #cfe2ff;
            color: #084298;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #084298;
        }
        code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: "Courier New", monospace;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #135e96;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 JGK Members Table Structure Diagnostic</h1>
        
        <?php
        global $wpdb;
        $members_table = $wpdb->prefix . 'jgk_members';
        
        echo '<div class="info">';
        echo '<strong>📋 Table Name:</strong> <code>' . esc_html($members_table) . '</code>';
        echo '</div>';
        
        // Get columns
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$members_table}");
        
        if ($wpdb->last_error) {
            echo '<div class="error">';
            echo '<strong>❌ Database Error:</strong><br>';
            echo esc_html($wpdb->last_error);
            echo '</div>';
        } else {
            // Check for specific columns
            $column_names = array_column($columns, 'Field');
            
            $checks = array(
                'is_public' => in_array('is_public', $column_names),
                'club_name' => in_array('club_name', $column_names),
                'club_affiliation' => in_array('club_affiliation', $column_names),
                'handicap_index' => in_array('handicap_index', $column_names),
                'handicap' => in_array('handicap', $column_names)
            );
            
            echo '<h2>🎯 Critical Columns Status</h2>';
            
            // is_public check
            if ($checks['is_public']) {
                echo '<div class="success">';
                echo '✅ <strong>is_public</strong> column EXISTS - Visibility control is available';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '❌ <strong>is_public</strong> column MISSING - Visibility control won\'t work';
                echo '</div>';
            }
            
            // Club name check
            if ($checks['club_name']) {
                echo '<div class="success">';
                echo '✅ <strong>club_name</strong> column EXISTS - Using NEW column name';
                echo '</div>';
            } elseif ($checks['club_affiliation']) {
                echo '<div class="warning">';
                echo '⚠️ <strong>club_affiliation</strong> exists but <strong>club_name</strong> is MISSING';
                echo '<br>The code expects <code>club_name</code>. Migration may be incomplete.';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '❌ Neither <strong>club_name</strong> nor <strong>club_affiliation</strong> exists';
                echo '</div>';
            }
            
            // Handicap check
            if ($checks['handicap_index']) {
                echo '<div class="success">';
                echo '✅ <strong>handicap_index</strong> column EXISTS - Using NEW column name';
                echo '</div>';
            } elseif ($checks['handicap']) {
                echo '<div class="warning">';
                echo '⚠️ <strong>handicap</strong> exists but <strong>handicap_index</strong> is MISSING';
                echo '<br>The code expects <code>handicap_index</code>. Migration may be incomplete.';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '❌ Neither <strong>handicap_index</strong> nor <strong>handicap</strong> exists';
                echo '</div>';
            }
            
            // Display all columns
            echo '<h2>📊 Complete Table Structure</h2>';
            echo '<p>Total columns: <strong>' . count($columns) . '</strong></p>';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>#</th>';
            echo '<th>Field Name</th>';
            echo '<th>Type</th>';
            echo '<th>Null</th>';
            echo '<th>Key</th>';
            echo '<th>Default</th>';
            echo '<th>Extra</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            $index = 1;
            foreach ($columns as $column) {
                $is_critical = in_array($column->Field, ['is_public', 'club_name', 'club_affiliation', 'handicap_index', 'handicap']);
                $row_class = $is_critical ? 'class="highlight"' : '';
                
                echo '<tr ' . $row_class . '>';
                echo '<td>' . $index++ . '</td>';
                echo '<td><strong>' . esc_html($column->Field) . '</strong></td>';
                echo '<td>' . esc_html($column->Type) . '</td>';
                echo '<td>' . esc_html($column->Null) . '</td>';
                echo '<td>' . esc_html($column->Key) . '</td>';
                echo '<td>' . esc_html($column->Default ?? 'NULL') . '</td>';
                echo '<td>' . esc_html($column->Extra) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            
            // Recommendations
            echo '<h2>💡 Recommendations</h2>';
            
            if (!$checks['is_public']) {
                echo '<div class="error">';
                echo '<strong>Action Required:</strong> Run the migration to add <code>is_public</code> column.<br>';
                echo 'Deactivate and reactivate the plugin to trigger automatic migration.';
                echo '</div>';
            }
            
            if (!$checks['club_name'] && $checks['club_affiliation']) {
                echo '<div class="warning">';
                echo '<strong>Action Required:</strong> Add <code>club_name</code> column and copy data from <code>club_affiliation</code>.<br>';
                echo 'SQL: <code>ALTER TABLE ' . $members_table . ' ADD COLUMN club_name varchar(100); UPDATE ' . $members_table . ' SET club_name = club_affiliation;</code>';
                echo '</div>';
            }
            
            if (!$checks['handicap_index'] && $checks['handicap']) {
                echo '<div class="warning">';
                echo '<strong>Action Required:</strong> Add <code>handicap_index</code> column and copy data from <code>handicap</code>.<br>';
                echo 'SQL: <code>ALTER TABLE ' . $members_table . ' ADD COLUMN handicap_index varchar(10); UPDATE ' . $members_table . ' SET handicap_index = handicap;</code>';
                echo '</div>';
            }
            
            if ($checks['is_public'] && 
                ($checks['club_name'] || $checks['club_affiliation']) && 
                ($checks['handicap_index'] || $checks['handicap'])) {
                echo '<div class="success">';
                echo '✅ <strong>All critical columns present!</strong><br>';
                echo 'The AJAX handler has been updated to support both old and new column names.';
                echo '<br>The modal should work correctly now.';
                echo '</div>';
            }
        }
        ?>
        
        <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members'); ?>" class="back-link">
            ← Back to Members Page
        </a>
    </div>
</body>
</html>
