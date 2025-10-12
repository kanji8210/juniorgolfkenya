<?php
/**
 * SCRIPT SIMPLE: Mettre TOUS les membres en PUBLIC
 * Accédez à: http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/fix-visibility-now.php
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin required.');
}

global $wpdb;
$members_table = $wpdb->prefix . 'jgk_members';

// Simple update query
$result = $wpdb->query("UPDATE {$members_table} SET is_public = 1");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fix Visibility - DONE!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: #f0f0f1;
        }
        .box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #00a32a;
            font-size: 48px;
            margin: 0 0 20px 0;
        }
        .message {
            font-size: 24px;
            color: #1d2327;
            margin: 20px 0;
        }
        .count {
            font-size: 72px;
            font-weight: bold;
            color: #2271b1;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 18px;
            margin-top: 20px;
        }
        .button:hover {
            background: #135e96;
        }
        .sql-error {
            background: #f8d7da;
            color: #842029;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="box">
        <?php if ($wpdb->last_error): ?>
            <div class="sql-error">
                <h2>❌ Error</h2>
                <p><?php echo esc_html($wpdb->last_error); ?></p>
            </div>
        <?php else: ?>
            <h1>✅ DONE!</h1>
            <div class="message">Tous les membres sont maintenant PUBLIC</div>
            <div class="count"><?php echo $result; ?></div>
            <div class="message">membres mis à jour</div>
            
            <a href="<?php echo admin_url('admin.php?page=juniorgolfkenya-members'); ?>" class="button">
                ← Retour à la liste des membres
            </a>
            
            <p style="margin-top: 40px; color: #666; font-size: 14px;">
                Vous pouvez maintenant cliquer sur "View Details" pour voir les détails des membres.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
