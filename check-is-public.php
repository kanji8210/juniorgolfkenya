<?php
/**
 * Vérification rapide de la colonne is_public
 */
require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;
$table = $wpdb->prefix . 'jgk_members';

// Check if column exists
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'is_public'");

header('Content-Type: text/plain; charset=utf-8');

echo "=== VERIFICATION COLONNE is_public ===\n\n";

if (empty($columns)) {
    echo "❌ PROBLEME: La colonne 'is_public' N'EXISTE PAS!\n\n";
    echo "SOLUTION: Ajoutons-la maintenant...\n\n";
    
    $result = $wpdb->query("ALTER TABLE {$table} ADD COLUMN is_public tinyint(1) NOT NULL DEFAULT 1 AFTER parental_consent");
    
    if ($wpdb->last_error) {
        echo "❌ ERREUR SQL: " . $wpdb->last_error . "\n";
    } else {
        echo "✅ Colonne 'is_public' ajoutée avec succès!\n";
        echo "✅ DEFAULT = 1 (PUBLIC)\n\n";
        
        // Count members
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        echo "📊 Tous les $count membres sont maintenant PUBLIC par défaut.\n\n";
    }
} else {
    echo "✅ La colonne 'is_public' existe!\n\n";
    
    $col = $columns[0];
    echo "Type: " . $col->Type . "\n";
    echo "Default: " . $col->Default . "\n";
    echo "Null: " . $col->Null . "\n\n";
    
    // Count by visibility
    $stats = $wpdb->get_results("SELECT is_public, COUNT(*) as count FROM {$table} GROUP BY is_public");
    
    echo "STATISTIQUES:\n";
    foreach ($stats as $stat) {
        $visibility = $stat->is_public == 1 ? 'PUBLIC' : 'HIDDEN';
        echo "  $visibility: " . $stat->count . " membres\n";
    }
    echo "\n";
    
    // Check if any are hidden
    $hidden_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_public = 0");
    
    if ($hidden_count > 0) {
        echo "⚠️  $hidden_count membres sont HIDDEN\n";
        echo "📝 Pour les mettre PUBLIC, allez sur:\n";
        echo "   http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/fix-visibility-now.php\n\n";
    } else {
        echo "✅ Tous les membres sont PUBLIC!\n";
        echo "✅ Le modal 'View Details' devrait fonctionner.\n\n";
    }
}

echo "===========================================\n";
