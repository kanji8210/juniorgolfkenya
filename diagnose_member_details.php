<?php
/**
 * Script de Diagnostic - Network Error Member Details
 * 
 * Instructions:
 * 1. Copier ce fichier dans le dossier du plugin
 * 2. Accéder via: http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/diagnose_member_details.php
 * 3. Lire les résultats du diagnostic
 */

// Charger WordPress
require_once('../../../wp-load.php');

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    die('Vous devez être administrateur pour exécuter ce diagnostic.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic - Member Details Network Error</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #0073aa; margin-top: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #0073aa; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        .code-block { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 10px 0; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-error { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnostic - Member Details Network Error</h1>
    
    <?php
    global $wpdb;
    
    // Test 1: Vérifier les permissions de l'utilisateur actuel
    echo "<h2>1. Vérification des Permissions</h2>";
    
    $current_user = wp_get_current_user();
    echo "<div class='info'>";
    echo "<strong>Utilisateur actuel:</strong> " . $current_user->user_login . " (ID: " . $current_user->ID . ")<br>";
    echo "<strong>Rôles:</strong> " . implode(', ', $current_user->roles) . "<br><br>";
    
    $permissions = array(
        'edit_members' => current_user_can('edit_members'),
        'manage_coaches' => current_user_can('manage_coaches'),
        'manage_options' => current_user_can('manage_options')
    );
    
    echo "<strong>Permissions détectées:</strong><br>";
    foreach ($permissions as $perm => $has) {
        $badge = $has ? 'badge-success' : 'badge-error';
        $text = $has ? '✓ OUI' : '✗ NON';
        echo "<span class='badge {$badge}'>{$text}</span> <code>{$perm}</code><br>";
    }
    echo "</div>";
    
    if (!$permissions['edit_members'] && !$permissions['manage_coaches'] && !$permissions['manage_options']) {
        echo "<div class='error'>";
        echo "<strong>⚠️ PROBLÈME DÉTECTÉ:</strong> Vous n'avez aucune des permissions requises !<br>";
        echo "La fonction AJAX va échouer avec une erreur 'Insufficient permissions'.";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "✓ Vous avez au moins une des permissions requises.";
        echo "</div>";
    }
    
    // Test 2: Vérifier la structure de la table
    echo "<h2>2. Vérification de la Table wp_jgk_members</h2>";
    
    $members_table = $wpdb->prefix . 'jgk_members';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$members_table}'") == $members_table;
    
    if ($table_exists) {
        echo "<div class='success'>✓ La table <code>{$members_table}</code> existe.</div>";
        
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$members_table}");
        
        echo "<h3>Colonnes de la table:</h3>";
        echo "<table>";
        echo "<tr><th>Nom de la Colonne</th><th>Type</th><th>Null</th><th>Défaut</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><code>{$col->Field}</code></td>";
            echo "<td>{$col->Type}</td>";
            echo "<td>{$col->Null}</td>";
            echo "<td>" . ($col->Default ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Vérifier les colonnes requises
        $required_columns = array(
            'id', 'user_id', 'first_name', 'last_name', 'phone', 'date_of_birth',
            'gender', 'status', 'membership_type', 'membership_number', 'club_name',
            'handicap_index', 'date_joined', 'address', 'biography',
            'emergency_contact_name', 'emergency_contact_phone'
        );
        
        $existing_columns = array_map(function($col) { return $col->Field; }, $columns);
        $missing_columns = array_diff($required_columns, $existing_columns);
        
        if (empty($missing_columns)) {
            echo "<div class='success'>✓ Toutes les colonnes requises sont présentes.</div>";
        } else {
            echo "<div class='error'>";
            echo "<strong>⚠️ PROBLÈME:</strong> Colonnes manquantes:<br>";
            foreach ($missing_columns as $col) {
                echo "- <code>{$col}</code><br>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<div class='error'>✗ La table <code>{$members_table}</code> n'existe pas !</div>";
    }
    
    // Test 3: Tester une requête membre
    echo "<h2>3. Test de Requête SQL</h2>";
    
    if ($table_exists) {
        $test_member = $wpdb->get_row("SELECT * FROM {$members_table} LIMIT 1");
        
        if ($test_member) {
            echo "<div class='success'>✓ Requête SELECT réussie. Membre trouvé (ID: {$test_member->id})</div>";
            
            echo "<h3>Exemple de données membre:</h3>";
            echo "<table>";
            echo "<tr><th>Champ</th><th>Valeur</th></tr>";
            foreach ($test_member as $key => $value) {
                echo "<tr><td><code>{$key}</code></td><td>" . esc_html($value) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ Aucun membre trouvé dans la base de données.</div>";
        }
        
        if ($wpdb->last_error) {
            echo "<div class='error'>";
            echo "<strong>Erreur SQL:</strong> " . $wpdb->last_error;
            echo "</div>";
        }
    }
    
    // Test 4: Vérifier les tables liées
    echo "<h2>4. Vérification des Tables Liées</h2>";
    
    $related_tables = array(
        'jgk_parents_guardians' => 'Parents/Tuteurs',
        'jgk_coach_members' => 'Assignations Coach-Membre',
    );
    
    foreach ($related_tables as $table_suffix => $description) {
        $table_name = $wpdb->prefix . $table_suffix;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
        
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            echo "<div class='success'>✓ <code>{$table_name}</code> existe ({$count} enregistrements) - {$description}</div>";
        } else {
            echo "<div class='warning'>⚠️ <code>{$table_name}</code> n'existe pas - {$description}</div>";
        }
    }
    
    // Test 5: Vérifier le handler AJAX
    echo "<h2>5. Vérification du Handler AJAX</h2>";
    
    $ajax_url = admin_url('admin-ajax.php');
    echo "<div class='info'>";
    echo "<strong>URL AJAX:</strong> <code>{$ajax_url}</code><br>";
    echo "<strong>Action:</strong> <code>jgk_get_member_details</code><br>";
    echo "</div>";
    
    if (has_action('wp_ajax_jgk_get_member_details')) {
        echo "<div class='success'>✓ Le hook AJAX <code>wp_ajax_jgk_get_member_details</code> est enregistré.</div>";
    } else {
        echo "<div class='error'>✗ Le hook AJAX <code>wp_ajax_jgk_get_member_details</code> n'est PAS enregistré !</div>";
    }
    
    // Test 6: Simulation AJAX (si un membre existe)
    if (isset($test_member) && $test_member) {
        echo "<h2>6. Test AJAX Simulé</h2>";
        
        echo "<div class='info'>";
        echo "Test avec membre ID: <strong>{$test_member->id}</strong><br>";
        echo "</div>";
        
        // Simuler la requête AJAX
        $_POST['member_id'] = $test_member->id;
        $_POST['nonce'] = wp_create_nonce('jgk_get_member_details');
        
        ob_start();
        
        try {
            // Appeler directement la fonction
            if (function_exists('jgk_ajax_get_member_details')) {
                jgk_ajax_get_member_details();
            } else {
                echo json_encode(array('error' => 'Function jgk_ajax_get_member_details not found'));
            }
        } catch (Exception $e) {
            echo json_encode(array('error' => $e->getMessage()));
        }
        
        $ajax_response = ob_get_clean();
        
        // Nettoyer le buffer pour éviter l'envoi JSON automatique
        unset($_POST['member_id']);
        unset($_POST['nonce']);
        
        if (!empty($ajax_response)) {
            $response_data = json_decode($ajax_response, true);
            
            if (isset($response_data['success']) && $response_data['success']) {
                echo "<div class='success'>✓ Requête AJAX simulée réussie !</div>";
                
                echo "<h3>Réponse AJAX:</h3>";
                echo "<div class='code-block'><pre>" . json_encode($response_data, JSON_PRETTY_PRINT) . "</pre></div>";
            } else {
                echo "<div class='error'>";
                echo "<strong>✗ Erreur dans la réponse AJAX:</strong><br>";
                echo "<div class='code-block'><pre>" . esc_html($ajax_response) . "</pre></div>";
                echo "</div>";
            }
        } else {
            echo "<div class='warning'>⚠️ Aucune réponse de la fonction AJAX (peut être normale si wp_send_json est utilisé)</div>";
        }
    }
    
    // Test 7: Vérifier les rôles WordPress
    echo "<h2>7. Vérification des Rôles Personnalisés</h2>";
    
    $custom_roles = array('jgk_member', 'jgk_coach', 'jgk_committee');
    
    foreach ($custom_roles as $role_name) {
        $role = get_role($role_name);
        
        if ($role) {
            echo "<div class='success'>✓ Rôle <code>{$role_name}</code> existe</div>";
            
            // Vérifier si le rôle a les permissions nécessaires
            $has_edit_members = isset($role->capabilities['edit_members']) && $role->capabilities['edit_members'];
            $has_manage_coaches = isset($role->capabilities['manage_coaches']) && $role->capabilities['manage_coaches'];
            
            if ($has_edit_members || $has_manage_coaches) {
                echo "<div class='info'>Permissions: ";
                if ($has_edit_members) echo "<span class='badge badge-success'>edit_members</span> ";
                if ($has_manage_coaches) echo "<span class='badge badge-success'>manage_coaches</span> ";
                echo "</div>";
            } else {
                echo "<div class='warning'>⚠️ Le rôle n'a pas les permissions <code>edit_members</code> ou <code>manage_coaches</code></div>";
            }
        } else {
            echo "<div class='warning'>⚠️ Rôle <code>{$role_name}</code> n'existe pas</div>";
        }
    }
    
    // Test 8: Vérifier les anciens rôles (jgf_*)
    echo "<h2>8. Vérification des Anciens Rôles (jgf_*)</h2>";
    
    $old_roles_query = $wpdb->get_results("
        SELECT user_id, meta_value 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = 'wp_capabilities' 
        AND meta_value LIKE '%jgf_%'
        LIMIT 5
    ");
    
    if (empty($old_roles_query)) {
        echo "<div class='success'>✓ Aucun ancien rôle jgf_* détecté. Tous les rôles utilisent jgk_*.</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>⚠️ PROBLÈME:</strong> Des anciens rôles jgf_* ont été détectés !<br>";
        echo "Nombre d'utilisateurs concernés: " . count($old_roles_query) . "<br><br>";
        echo "<strong>Solution:</strong> Exécuter le script SQL de correction des rôles.<br>";
        echo "<a href='fix-roles.sql' target='_blank'>Télécharger fix-roles.sql</a> et l'exécuter dans phpMyAdmin.";
        echo "</div>";
    }
    
    // Résumé et Recommandations
    echo "<h2>📋 Résumé et Recommandations</h2>";
    
    $issues = array();
    
    if (!$permissions['edit_members'] && !$permissions['manage_coaches'] && !$permissions['manage_options']) {
        $issues[] = "Permissions manquantes pour l'utilisateur actuel";
    }
    
    if (!$table_exists) {
        $issues[] = "Table wp_jgk_members manquante";
    }
    
    if (!empty($missing_columns)) {
        $issues[] = "Colonnes manquantes dans la table";
    }
    
    if (!empty($old_roles_query)) {
        $issues[] = "Anciens rôles jgf_* non migrés vers jgk_*";
    }
    
    if (empty($issues)) {
        echo "<div class='success'>";
        echo "<h3>✓ Aucun problème majeur détecté !</h3>";
        echo "<p>Le système devrait fonctionner correctement. Si vous avez toujours une erreur 'Network Error', vérifiez:</p>";
        echo "<ul>";
        echo "<li>La console JavaScript du navigateur (F12) pour l'erreur exacte</li>";
        echo "<li>Le fichier <code>wp-content/debug.log</code> pour les erreurs PHP</li>";
        echo "<li>Vider le cache du navigateur et recharger la page</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>⚠️ Problèmes détectés:</h3>";
        echo "<ol>";
        foreach ($issues as $issue) {
            echo "<li>{$issue}</li>";
        }
        echo "</ol>";
        
        echo "<h3>Actions recommandées:</h3>";
        echo "<ol>";
        echo "<li>Si problème de permissions: Connectez-vous en tant qu'administrateur</li>";
        echo "<li>Si anciens rôles jgf_*: Exécutez le script SQL de correction</li>";
        echo "<li>Si colonnes manquantes: Contactez le support technique</li>";
        echo "<li>Consultez le fichier <code>NETWORK_ERROR_FIX.md</code> pour plus de détails</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    ?>
    
    <hr style="margin: 30px 0;">
    
    <p style="text-align: center; color: #666;">
        <small>
            Diagnostic généré le <?php echo date('d/m/Y à H:i:s'); ?><br>
            Plugin: Junior Golf Kenya v1.0.0
        </small>
    </p>
</div>
</body>
</html>
