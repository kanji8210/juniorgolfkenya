<?php
/**
 * Script de vérification de la structure de la table wp_jgk_members
 * À exécuter via navigateur: http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/check_table_structure.php
 */

// Trouver wp-load.php - plusieurs chemins possibles
$wp_load_paths = array(
    __DIR__ . '/../../../../../wp-load.php',  // Standard WordPress
    __DIR__ . '/../../../../wp-load.php',      // Si dans mu-plugins
    __DIR__ . '/../../../wp-load.php',         // Si structure différente
    $_SERVER['DOCUMENT_ROOT'] . '/wordpress/wp-load.php',  // XAMPP standard
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',  // WordPress à la racine
);

$wp_load_found = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_load_found = true;
        break;
    }
}

if (!$wp_load_found) {
    die('<h1>❌ Erreur</h1><p>Impossible de trouver wp-load.php</p><p>Chemins testés :<br>' . implode('<br>', $wp_load_paths) . '</p>');
}

if (!current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

global $wpdb;
$table = $wpdb->prefix . 'jgk_members';

echo "<h1>Structure de la table: {$table}</h1>";

// Vérifier si la table existe
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

if (!$table_exists) {
    echo "<p style='color: red;'>❌ La table {$table} n'existe pas !</p>";
    exit;
}

echo "<p style='color: green;'>✅ La table existe</p>";

// Récupérer toutes les colonnes
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}");

echo "<h2>Colonnes existantes (" . count($columns) . " total) :</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #0073aa; color: white;'>";
echo "<th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th>";
echo "</tr>";

$has_is_public = false;

foreach ($columns as $col) {
    if ($col->Field === 'is_public') {
        $has_is_public = true;
        echo "<tr style='background: #d4edda;'>";
    } else {
        echo "<tr>";
    }
    
    echo "<td><strong>{$col->Field}</strong></td>";
    echo "<td>{$col->Type}</td>";
    echo "<td>{$col->Null}</td>";
    echo "<td>{$col->Key}</td>";
    echo "<td>" . ($col->Default ?? 'NULL') . "</td>";
    echo "<td>{$col->Extra}</td>";
    echo "</tr>";
}

echo "</table>";

// Vérifier is_public
echo "<hr>";
if ($has_is_public) {
    echo "<h2 style='color: green;'>✅ La colonne 'is_public' existe déjà !</h2>";
    echo "<p>Aucune action nécessaire.</p>";
} else {
    echo "<h2 style='color: red;'>❌ La colonne 'is_public' n'existe PAS !</h2>";
    echo "<p><strong>Action requise :</strong> Vous devez ajouter cette colonne à la table.</p>";
    echo "<h3>Script SQL à exécuter :</h3>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;'>";
    echo "ALTER TABLE {$table}\n";
    echo "ADD COLUMN is_public tinyint(1) NOT NULL DEFAULT 0\n";
    echo "AFTER parental_consent;";
    echo "</pre>";
    
    echo "<h3>Ou exécuter via ce bouton :</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='add_is_public_column' value='1'>";
    echo "<button type='submit' style='background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px;'>";
    echo "🔧 Ajouter la colonne is_public maintenant";
    echo "</button>";
    echo "</form>";
}

// Traiter l'ajout de colonne
if (isset($_POST['add_is_public_column'])) {
    echo "<hr>";
    echo "<h2>Exécution de la migration...</h2>";
    
    $sql = "ALTER TABLE {$table} 
            ADD COLUMN is_public tinyint(1) NOT NULL DEFAULT 0 
            AFTER parental_consent";
    
    $result = $wpdb->query($sql);
    
    if ($result === false) {
        echo "<p style='color: red;'>❌ Erreur lors de l'ajout de la colonne :</p>";
        echo "<pre style='background: #f8d7da; padding: 10px;'>{$wpdb->last_error}</pre>";
    } else {
        echo "<p style='color: green; font-size: 18px;'>✅ Colonne 'is_public' ajoutée avec succès !</p>";
        echo "<p><a href='check_table_structure.php'>🔄 Recharger pour vérifier</a></p>";
    }
}

// Compter les membres
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
echo "<hr>";
echo "<h2>Statistiques :</h2>";
echo "<p>Total de membres : <strong>{$total}</strong></p>";

if ($has_is_public) {
    $public_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_public = 1");
    $private_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_public = 0");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #0073aa; color: white;'>";
    echo "<th>Visibilité</th><th>Nombre</th><th>Pourcentage</th>";
    echo "</tr>";
    
    $public_pct = $total > 0 ? round(($public_count / $total) * 100, 1) : 0;
    $private_pct = $total > 0 ? round(($private_count / $total) * 100, 1) : 0;
    
    echo "<tr style='background: #d4edda;'>";
    echo "<td>✅ Publics (is_public = 1)</td>";
    echo "<td><strong>{$public_count}</strong></td>";
    echo "<td>{$public_pct}%</td>";
    echo "</tr>";
    
    echo "<tr style='background: #fff3cd;'>";
    echo "<td>🔒 Privés (is_public = 0)</td>";
    echo "<td><strong>{$private_count}</strong></td>";
    echo "<td>{$private_pct}%</td>";
    echo "</tr>";
    
    echo "</table>";
}
