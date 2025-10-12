<?php
/**
 * DIAGNOSTIC COMPLET DU SYSTEME MODAL
 * URL: http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/diagnose-modal.php
 */

require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;
$members_table = $wpdb->prefix . 'jgk_members';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>JGK Modal - Diagnostic Complet</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .section {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 3px solid #2271b1;
            padding-bottom: 15px;
        }
        h2 {
            color: #2271b1;
            margin-top: 0;
            font-size: 20px;
        }
        .success { background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #0f5132; }
        .error { background: #f8d7da; color: #842029; padding: 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #842029; }
        .warning { background: #fff3cd; color: #664d03; padding: 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #664d03; }
        .info { background: #cfe2ff; color: #084298; padding: 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #084298; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: 600; }
        code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 13px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .button { display: inline-block; padding: 10px 20px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .button:hover { background: #135e96; }
        .test-result { padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 600; }
        .test-pass { background: #d1e7dd; color: #0f5132; }
        .test-fail { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic Complet du Système Modal JGK</h1>
    
    <?php
    $issues = array();
    $warnings = array();
    $success = array();
    
    // TEST 1: Check if is_public column exists
    echo '<div class="section">';
    echo '<h2>TEST 1: Vérification de la colonne is_public</h2>';
    
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$members_table} LIKE 'is_public'");
    
    if (empty($columns)) {
        echo '<div class="error">❌ <strong>ERREUR CRITIQUE:</strong> La colonne <code>is_public</code> n\'existe PAS dans la table!</div>';
        $issues[] = 'Colonne is_public manquante';
        
        echo '<div class="info">';
        echo '<strong>Solution:</strong> Ajoutons-la maintenant...<br>';
        $result = $wpdb->query("ALTER TABLE {$members_table} ADD COLUMN is_public tinyint(1) NOT NULL DEFAULT 1 AFTER parental_consent");
        if ($wpdb->last_error) {
            echo '<div class="error">Erreur SQL: ' . esc_html($wpdb->last_error) . '</div>';
        } else {
            echo '<div class="success">✅ Colonne ajoutée avec succès!</div>';
            $success[] = 'Colonne is_public créée';
        }
        echo '</div>';
    } else {
        echo '<div class="success">✅ La colonne <code>is_public</code> existe</div>';
        $col = $columns[0];
        echo '<table>';
        echo '<tr><th>Propriété</th><th>Valeur</th></tr>';
        echo '<tr><td>Type</td><td><code>' . esc_html($col->Type) . '</code></td></tr>';
        echo '<tr><td>Défaut</td><td><code>' . esc_html($col->Default) . '</code></td></tr>';
        echo '<tr><td>Null autorisé</td><td><code>' . esc_html($col->Null) . '</code></td></tr>';
        echo '</table>';
        $success[] = 'Colonne is_public présente';
    }
    echo '</div>';
    
    // TEST 2: Check member visibility statistics
    echo '<div class="section">';
    echo '<h2>TEST 2: Statistiques de Visibilité</h2>';
    
    $stats = $wpdb->get_results("SELECT is_public, COUNT(*) as count FROM {$members_table} GROUP BY is_public");
    $total_members = $wpdb->get_var("SELECT COUNT(*) FROM {$members_table}");
    
    echo '<table>';
    echo '<tr><th>Visibilité</th><th>Nombre</th><th>Pourcentage</th><th>Status</th></tr>';
    
    $public_count = 0;
    $hidden_count = 0;
    
    foreach ($stats as $stat) {
        $visibility = $stat->is_public == 1 ? '👁️ PUBLIC' : '🔒 HIDDEN';
        $percentage = round(($stat->count / $total_members) * 100, 1);
        $status_class = $stat->is_public == 1 ? 'test-pass' : 'test-fail';
        
        if ($stat->is_public == 1) {
            $public_count = $stat->count;
        } else {
            $hidden_count = $stat->count;
        }
        
        echo '<tr>';
        echo '<td><strong>' . $visibility . '</strong></td>';
        echo '<td>' . $stat->count . '</td>';
        echo '<td>' . $percentage . '%</td>';
        echo '<td><span class="' . $status_class . '">' . ($stat->is_public == 1 ? 'OK' : 'PROBLÈME') . '</span></td>';
        echo '</tr>';
    }
    echo '</table>';
    
    if ($hidden_count > 0) {
        echo '<div class="warning">';
        echo '⚠️ <strong>' . $hidden_count . ' membres sont HIDDEN</strong><br>';
        echo 'Le modal ne fonctionnera PAS pour ces membres!<br>';
        echo '<a href="fix-visibility-now.php" class="button">🔧 Corriger Maintenant</a>';
        echo '</div>';
        $warnings[] = $hidden_count . ' membres cachés';
    } else {
        echo '<div class="success">✅ Tous les membres sont PUBLIC</div>';
        $success[] = 'Tous les membres visibles';
    }
    echo '</div>';
    
    // TEST 3: Check AJAX handler existence
    echo '<div class="section">';
    echo '<h2>TEST 3: Vérification du Handler AJAX</h2>';
    
    $ajax_functions = array('jgk_get_member_details');
    $ajax_file = dirname(__FILE__) . '/juniorgolfkenya.php';
    
    if (file_exists($ajax_file)) {
        $content = file_get_contents($ajax_file);
        
        foreach ($ajax_functions as $func) {
            if (strpos($content, "function {$func}") !== false) {
                echo '<div class="success">✅ Fonction <code>' . $func . '()</code> trouvée</div>';
                $success[] = "Fonction $func existe";
            } else {
                echo '<div class="error">❌ Fonction <code>' . $func . '()</code> MANQUANTE</div>';
                $issues[] = "Fonction $func manquante";
            }
        }
        
        // Check for WordPress hooks
        if (strpos($content, "add_action('wp_ajax_jgk_get_member_details'") !== false) {
            echo '<div class="success">✅ Hook AJAX <code>wp_ajax_jgk_get_member_details</code> enregistré</div>';
            $success[] = 'Hook AJAX configuré';
        } else {
            echo '<div class="error">❌ Hook AJAX <code>wp_ajax_jgk_get_member_details</code> MANQUANT</div>';
            $issues[] = 'Hook AJAX manquant';
        }
    } else {
        echo '<div class="error">❌ Fichier principal introuvable</div>';
        $issues[] = 'Fichier principal manquant';
    }
    echo '</div>';
    
    // TEST 4: Check column names compatibility
    echo '<div class="section">';
    echo '<h2>TEST 4: Compatibilité des Noms de Colonnes</h2>';
    
    $all_columns = $wpdb->get_results("SHOW COLUMNS FROM {$members_table}");
    $column_names = array_map(function($col) { return $col->Field; }, $all_columns);
    
    $column_checks = array(
        'club_name' => 'club_affiliation',
        'handicap_index' => 'handicap'
    );
    
    echo '<table>';
    echo '<tr><th>Colonne Attendue</th><th>Existe?</th><th>Alternative</th><th>Existe?</th><th>Status</th></tr>';
    
    foreach ($column_checks as $new_name => $old_name) {
        $new_exists = in_array($new_name, $column_names);
        $old_exists = in_array($old_name, $column_names);
        
        $status = '';
        $status_class = '';
        
        if ($new_exists) {
            $status = '✅ Nouveau nom OK';
            $status_class = 'test-pass';
        } elseif ($old_exists) {
            $status = '⚠️ Ancien nom (compatible)';
            $status_class = 'test-pass';
            $warnings[] = "Colonne $new_name utilise ancien nom $old_name";
        } else {
            $status = '❌ Manquante';
            $status_class = 'test-fail';
            $issues[] = "Colonne $new_name et $old_name manquantes";
        }
        
        echo '<tr>';
        echo '<td><code>' . $new_name . '</code></td>';
        echo '<td>' . ($new_exists ? '✅' : '❌') . '</td>';
        echo '<td><code>' . $old_name . '</code></td>';
        echo '<td>' . ($old_exists ? '✅' : '❌') . '</td>';
        echo '<td><span class="' . $status_class . '">' . $status . '</span></td>';
        echo '</tr>';
    }
    echo '</table>';
    
    if ($new_exists || $old_exists) {
        echo '<div class="success">✅ Le code AJAX supporte les deux noms de colonnes</div>';
    }
    echo '</div>';
    
    // TEST 5: Test AJAX call simulation
    echo '<div class="section">';
    echo '<h2>TEST 5: Simulation d\'Appel AJAX</h2>';
    
    $test_member = $wpdb->get_row("SELECT * FROM {$members_table} LIMIT 1");
    
    if ($test_member) {
        echo '<div class="info">Test avec le membre: <strong>' . esc_html($test_member->first_name . ' ' . $test_member->last_name) . '</strong> (ID: ' . $test_member->id . ')</div>';
        
        // Simulate AJAX response building
        $test_response = array(
            'id' => $test_member->id,
            'first_name' => $test_member->first_name ?? '',
            'last_name' => $test_member->last_name ?? '',
            'email' => $test_member->user_email ?? '',
            'club_name' => $test_member->club_name ?? $test_member->club_affiliation ?? '',
            'handicap' => $test_member->handicap_index ?? $test_member->handicap ?? '',
            'is_public' => $test_member->is_public ?? 0
        );
        
        echo '<pre>' . json_encode($test_response, JSON_PRETTY_PRINT) . '</pre>';
        
        if ($test_member->is_public == 0) {
            echo '<div class="error">❌ Ce membre est HIDDEN - le modal échouera!</div>';
        } else {
            echo '<div class="success">✅ Ce membre est PUBLIC - le modal devrait fonctionner</div>';
        }
    } else {
        echo '<div class="warning">⚠️ Aucun membre trouvé pour le test</div>';
    }
    echo '</div>';
    
    // FINAL SUMMARY
    echo '<div class="section">';
    echo '<h2>📊 Résumé Final</h2>';
    
    echo '<table>';
    echo '<tr><th>Catégorie</th><th>Nombre</th></tr>';
    echo '<tr><td>✅ Tests réussis</td><td><strong>' . count($success) . '</strong></td></tr>';
    echo '<tr><td>⚠️ Avertissements</td><td><strong>' . count($warnings) . '</strong></td></tr>';
    echo '<tr><td>❌ Erreurs critiques</td><td><strong>' . count($issues) . '</strong></td></tr>';
    echo '</table>';
    
    if (count($issues) > 0) {
        echo '<div class="error">';
        echo '<strong>ERREURS À CORRIGER:</strong><ul>';
        foreach ($issues as $issue) {
            echo '<li>' . esc_html($issue) . '</li>';
        }
        echo '</ul></div>';
    }
    
    if (count($warnings) > 0) {
        echo '<div class="warning">';
        echo '<strong>AVERTISSEMENTS:</strong><ul>';
        foreach ($warnings as $warning) {
            echo '<li>' . esc_html($warning) . '</li>';
        }
        echo '</ul></div>';
    }
    
    if (count($issues) == 0 && count($warnings) == 0) {
        echo '<div class="success">';
        echo '<h3 style="margin-top:0;">🎉 TOUT EST BON!</h3>';
        echo '<p>Le système devrait fonctionner correctement.</p>';
        echo '<p>Si le modal ne fonctionne toujours pas:</p>';
        echo '<ol>';
        echo '<li>Videz le cache du navigateur (Ctrl+Shift+Delete)</li>';
        echo '<li>Rechargez la page des membres (Ctrl+F5)</li>';
        echo '<li>Ouvrez la console du navigateur (F12) et cliquez sur "View Details"</li>';
        echo '<li>Regardez les erreurs dans la console</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="warning">';
        echo '<strong>ACTIONS RECOMMANDÉES:</strong><br>';
        if ($hidden_count > 0) {
            echo '1. <a href="fix-visibility-now.php" class="button">Mettre tous les membres PUBLIC</a><br>';
        }
        echo '2. Ouvrir la console du navigateur (F12) pour voir les logs<br>';
        echo '3. Tester "View Details" et vérifier les erreurs JavaScript<br>';
        echo '</div>';
    }
    echo '</div>';
    
    // Quick actions
    echo '<div class="section">';
    echo '<h2>🔧 Actions Rapides</h2>';
    echo '<a href="' . admin_url('admin.php?page=juniorgolfkenya-members') . '" class="button">← Retour aux Membres</a>';
    echo '<a href="fix-visibility-now.php" class="button">🔧 Corriger Visibilité</a>';
    echo '<a href="check-is-public.php" class="button">🔍 Vérifier is_public</a>';
    echo '<a href="?" class="button">🔄 Relancer Diagnostic</a>';
    echo '</div>';
    ?>
</body>
</html>
