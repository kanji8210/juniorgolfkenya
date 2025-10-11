<?php
/**
 * Script de correction : Réassigner les rôles JGF vers JGK
 * 
 * Ce script corrige l'incohérence de nommage des rôles :
 * - jgf_member → jgk_member
 * - jgf_coach → jgk_coach
 * - jgf_staff → jgk_staff
 * 
 * À exécuter UNE SEULE FOIS après la mise à jour du plugin
 */

// Charger WordPress
require_once('../../../wp-load.php');

// Vérifier les permissions admin
if (!current_user_can('administrator')) {
    wp_die('Accès refusé. Vous devez être administrateur pour exécuter ce script.');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction des rôles JGK</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.13);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        .notice {
            padding: 12px 20px;
            margin: 20px 0;
            border-left: 4px solid #2271b1;
            background: #f0f6fc;
            border-radius: 4px;
        }
        .success {
            border-left-color: #00a32a;
            background: #f0f6fc;
        }
        .warning {
            border-left-color: #dba617;
            background: #fcf9e8;
        }
        .error {
            border-left-color: #d63638;
            background: #fcf0f1;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #135e96;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2271b1;
        }
        .stat-label {
            font-size: 14px;
            color: #50575e;
            margin-top: 5px;
        }
        pre {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correction des rôles Junior Golf Kenya</h1>
        
        <div class="notice warning">
            <strong>⚠️ Important :</strong> Ce script va corriger l'incohérence de nommage des rôles utilisateurs. 
            Il va créer les nouveaux rôles (jgk_*) et réassigner tous les utilisateurs existants.
        </div>

<?php
if (isset($_POST['execute_fix'])) {
    echo '<h2>📊 Exécution de la correction...</h2>';
    
    $stats = array(
        'members_fixed' => 0,
        'coaches_fixed' => 0,
        'staff_fixed' => 0,
        'errors' => 0
    );
    
    // Étape 1 : Créer les nouveaux rôles s'ils n'existent pas
    echo '<div class="notice"><strong>Étape 1 :</strong> Création des nouveaux rôles (jgk_*)</div>';
    
    if (!get_role('jgk_member')) {
        add_role('jgk_member', 'JGK Member', array(
            'read' => true,
            'view_member_dashboard' => true,
            'manage_own_profile' => true,
        ));
        echo '<p>✅ Rôle <code>jgk_member</code> créé</p>';
    } else {
        echo '<p>ℹ️ Rôle <code>jgk_member</code> existe déjà</p>';
    }
    
    if (!get_role('jgk_coach')) {
        add_role('jgk_coach', 'JGK Coach', array(
            'read' => true,
            'view_member_dashboard' => true,
            'coach_rate_player' => true,
            'coach_recommend_competition' => true,
            'coach_recommend_training' => true,
            'manage_own_profile' => true,
        ));
        echo '<p>✅ Rôle <code>jgk_coach</code> créé</p>';
    } else {
        echo '<p>ℹ️ Rôle <code>jgk_coach</code> existe déjà</p>';
    }
    
    if (!get_role('jgk_staff')) {
        add_role('jgk_staff', 'JGK Staff', array(
            'read' => true,
            'view_member_dashboard' => true,
            'edit_members' => true,
            'manage_payments' => true,
            'manage_competitions' => true,
            'view_reports' => true,
            'approve_role_requests' => true,
            'manage_certifications' => true,
        ));
        echo '<p>✅ Rôle <code>jgk_staff</code> créé</p>';
    } else {
        echo '<p>ℹ️ Rôle <code>jgk_staff</code> existe déjà</p>';
    }
    
    // Étape 2 : Trouver et réassigner les utilisateurs avec anciens rôles
    echo '<div class="notice"><strong>Étape 2 :</strong> Réassignation des utilisateurs</div>';
    
    // Trouver tous les utilisateurs avec jgf_member
    $jgf_members = get_users(array('role' => 'jgf_member'));
    foreach ($jgf_members as $user) {
        $user_obj = new WP_User($user->ID);
        $user_obj->remove_role('jgf_member');
        $user_obj->add_role('jgk_member');
        $stats['members_fixed']++;
        echo '<p>✅ Utilisateur <strong>' . esc_html($user->display_name) . '</strong> : jgf_member → jgk_member</p>';
    }
    
    // Trouver tous les utilisateurs avec jgf_coach
    $jgf_coaches = get_users(array('role' => 'jgf_coach'));
    foreach ($jgf_coaches as $user) {
        $user_obj = new WP_User($user->ID);
        $user_obj->remove_role('jgf_coach');
        $user_obj->add_role('jgk_coach');
        $stats['coaches_fixed']++;
        echo '<p>✅ Utilisateur <strong>' . esc_html($user->display_name) . '</strong> : jgf_coach → jgk_coach</p>';
    }
    
    // Trouver tous les utilisateurs avec jgf_staff
    $jgf_staff = get_users(array('role' => 'jgf_staff'));
    foreach ($jgf_staff as $user) {
        $user_obj = new WP_User($user->ID);
        $user_obj->remove_role('jgf_staff');
        $user_obj->add_role('jgk_staff');
        $stats['staff_fixed']++;
        echo '<p>✅ Utilisateur <strong>' . esc_html($user->display_name) . '</strong> : jgf_staff → jgk_staff</p>';
    }
    
    // Étape 3 : Supprimer les anciens rôles (optionnel)
    echo '<div class="notice"><strong>Étape 3 :</strong> Nettoyage des anciens rôles</div>';
    
    if (get_role('jgf_member')) {
        remove_role('jgf_member');
        echo '<p>✅ Rôle <code>jgf_member</code> supprimé</p>';
    }
    
    if (get_role('jgf_coach')) {
        remove_role('jgf_coach');
        echo '<p>✅ Rôle <code>jgf_coach</code> supprimé</p>';
    }
    
    if (get_role('jgf_staff')) {
        remove_role('jgf_staff');
        echo '<p>✅ Rôle <code>jgf_staff</code> supprimé</p>';
    }
    
    // Afficher les statistiques
    echo '<h2>📊 Résumé de la correction</h2>';
    echo '<div class="stats">';
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . $stats['members_fixed'] . '</div>';
    echo '<div class="stat-label">Membres corrigés</div>';
    echo '</div>';
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . $stats['coaches_fixed'] . '</div>';
    echo '<div class="stat-label">Coachs corrigés</div>';
    echo '</div>';
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . $stats['staff_fixed'] . '</div>';
    echo '<div class="stat-label">Staff corrigés</div>';
    echo '</div>';
    echo '</div>';
    
    $total = $stats['members_fixed'] + $stats['coaches_fixed'] + $stats['staff_fixed'];
    
    if ($total > 0) {
        echo '<div class="notice success">';
        echo '<strong>✅ Correction terminée avec succès !</strong><br>';
        echo 'Total : <strong>' . $total . '</strong> utilisateur(s) corrigé(s).';
        echo '</div>';
    } else {
        echo '<div class="notice">';
        echo '<strong>ℹ️ Aucun utilisateur à corriger.</strong><br>';
        echo 'Tous les utilisateurs ont déjà les bons rôles (jgk_*).';
        echo '</div>';
    }
    
    echo '<h2>🔄 Prochaines étapes</h2>';
    echo '<ol>';
    echo '<li>Déconnectez-vous et reconnectez-vous</li>';
    echo '<li>Testez l\'accès au dashboard membre ou coach</li>';
    echo '<li><strong>Supprimez ce fichier (fix-roles.php)</strong> pour des raisons de sécurité</li>';
    echo '</ol>';
    
    echo '<p><a href="' . admin_url() . '" class="btn">← Retour au tableau de bord</a></p>';
    
} else {
    // Afficher le formulaire de confirmation
    
    // Vérifier l'état actuel
    $jgf_members = get_users(array('role' => 'jgf_member'));
    $jgf_coaches = get_users(array('role' => 'jgf_coach'));
    $jgf_staff = get_users(array('role' => 'jgf_staff'));
    
    $jgk_members = get_users(array('role' => 'jgk_member'));
    $jgk_coaches = get_users(array('role' => 'jgk_coach'));
    $jgk_staff = get_users(array('role' => 'jgk_staff'));
    
    echo '<h2>📊 État actuel</h2>';
    echo '<div class="stats">';
    
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . count($jgf_members) . '</div>';
    echo '<div class="stat-label">jgf_member (ancien)</div>';
    echo '</div>';
    
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . count($jgf_coaches) . '</div>';
    echo '<div class="stat-label">jgf_coach (ancien)</div>';
    echo '</div>';
    
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . count($jgf_staff) . '</div>';
    echo '<div class="stat-label">jgf_staff (ancien)</div>';
    echo '</div>';
    
    echo '</div>';
    
    echo '<div class="stats">';
    
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . count($jgk_members) . '</div>';
    echo '<div class="stat-label">jgk_member (nouveau)</div>';
    echo '</div>';
    
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . count($jgk_coaches) . '</div>';
    echo '<div class="stat-label">jgk_coach (nouveau)</div>';
    echo '</div>';
    
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . count($jgk_staff) . '</div>';
    echo '<div class="stat-label">jgk_staff (nouveau)</div>';
    echo '</div>';
    
    echo '</div>';
    
    $total_old = count($jgf_members) + count($jgf_coaches) + count($jgf_staff);
    
    if ($total_old > 0) {
        echo '<div class="notice warning">';
        echo '<strong>⚠️ Action requise :</strong> ' . $total_old . ' utilisateur(s) avec ancien(s) rôle(s) détecté(s).';
        echo '</div>';
        
        echo '<h2>🔧 Ce que fait ce script :</h2>';
        echo '<ol>';
        echo '<li>Crée les nouveaux rôles (jgk_member, jgk_coach, jgk_staff)</li>';
        echo '<li>Trouve tous les utilisateurs avec les anciens rôles (jgf_*)</li>';
        echo '<li>Réassigne chaque utilisateur au nouveau rôle correspondant</li>';
        echo '<li>Supprime les anciens rôles (jgf_*)</li>';
        echo '</ol>';
        
        echo '<form method="post">';
        echo '<p><button type="submit" name="execute_fix" class="btn">🚀 Lancer la correction</button></p>';
        echo '</form>';
    } else {
        echo '<div class="notice success">';
        echo '<strong>✅ Tout est bon !</strong><br>';
        echo 'Aucun utilisateur avec ancien rôle détecté. Tous les utilisateurs ont les bons rôles (jgk_*).';
        echo '</div>';
        
        echo '<p>Vous pouvez supprimer ce fichier en toute sécurité.</p>';
        echo '<p><a href="' . admin_url() . '" class="btn">← Retour au tableau de bord</a></p>';
    }
}
?>

    </div>
</body>
</html>
