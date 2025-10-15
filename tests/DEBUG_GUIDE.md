# Payment System Debug Guide

## Problème identifié
Les paiements fonctionnent en local mais pas en ligne, et la liste des paiements ne s'affiche pas malgré la présence de données dans la base.

## Scripts de diagnostic créés

### 1. `production_diagnostic.php`
Script principal à uploader sur le serveur de production pour identifier les différences d'environnement.

**Instructions d'utilisation :**
1. Uploadez ce fichier sur votre serveur dans le dossier `wp-content/plugins/juniorgolfkenya/tests/`
2. Exécutez-le via SSH : `php production_diagnostic.php`
3. Ou accédez via navigateur : `https://votre-site.com/wp-content/plugins/juniorgolfkenya/tests/production_diagnostic.php`
4. Comparez les résultats avec votre environnement local

### 2. `admin_interface_diagnostic.php`
Teste spécifiquement les problèmes d'affichage dans l'interface d'administration.

### 3. `environment_diagnostic.php`
Diagnostic complet de l'environnement (local et production).

### 4. `admin_simulation.php`
Simule exactement la logique de l'interface d'administration.

## Résultats du diagnostic local

### ✅ Ce qui fonctionne en local :
- 7 paiements présents dans la base de données
- `get_payments()` retourne correctement les données
- Enregistrement de nouveaux paiements fonctionne
- Structure de la base de données correcte
- Plugin actif et fichiers accessibles

### ⚠️ Problèmes mineurs identifiés :
1. **Liaison membre incomplète** : 1 paiement avec `member_name` NULL/incomplet
2. **WooCommerce non installé** : Tables WooCommerce manquantes (normal si pas utilisé)
3. **Pas de produit d'adhésion configuré** : `membership_product_id = 0`

## Points de vérification pour l'environnement de production

### A. Configuration de base de données
```bash
# Vérifiez ces paramètres dans wp-config.php production
DB_HOST = ?
DB_NAME = ?
DB_USER = ?
```

### B. Tables requises
```sql
-- Vérifiez l'existence de ces tables :
SHOW TABLES LIKE 'wp_jgk_payments';
SHOW TABLES LIKE 'wp_jgk_members';

-- Vérifiez la structure :
DESCRIBE wp_jgk_payments;
```

### C. Plugin et permissions
- Plugin actif : ✅/❌
- Fichiers accessibles : ✅/❌
- Permissions utilisateur `manage_payments` : ✅/❌

### D. Environnement PHP
- Version PHP ≥ 7.4 : ✅/❌
- Mémoire suffisante : ✅/❌
- Logs d'erreurs accessibles : ✅/❌

## Actions de débogage recommandées

### 1. Immédiat
1. **Uploadez `production_diagnostic.php`** sur votre serveur
2. **Exécutez-le** et comparez avec les résultats locaux
3. **Vérifiez les logs d'erreurs** PHP/WordPress

### 2. Si les tables existent mais vides
```php
// Testez directement dans l'admin WordPress ou via script :
$payments = JuniorGolfKenya_Database::get_payments();
var_dump(count($payments));
```

### 3. Si erreurs de base de données
- Vérifiez les permissions MySQL
- Testez une requête simple : `SELECT COUNT(*) FROM wp_jgk_payments`
- Vérifiez les logs MySQL

### 4. Si problèmes d'affichage uniquement
- Inspectez la console JavaScript du navigateur
- Vérifiez l'onglet Network pour les requêtes AJAX échouées
- Testez avec un autre utilisateur admin

## Scripts de test rapides

### Test rapide de base de données
```bash
# Sur le serveur, créez un fichier quick_test.php :
<?php
require_once('../../../../wp-load.php');
global $wpdb;
$count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jgk_payments");
echo "Payments in database: $count\n";
?>
```

### Test de la fonction get_payments
```php
<?php
require_once('../../../../wp-load.php');
require_once('../includes/class-juniorgolfkenya-database.php');
$payments = JuniorGolfKenya_Database::get_payments();
echo "get_payments() returned: " . count($payments) . " payments\n";
?>
```

## Corrections possibles

### 1. Si membre_name NULL
```sql
-- Vérifiez les liaisons membre/utilisateur :
SELECT p.id, p.member_id, m.id as member_exists, u.display_name 
FROM wp_jgk_payments p 
LEFT JOIN wp_jgk_members m ON p.member_id = m.id 
LEFT JOIN wp_users u ON m.user_id = u.ID 
WHERE m.id IS NULL OR u.ID IS NULL;
```

### 2. Si permissions manquantes
```php
// Ajoutez la capacité à l'utilisateur admin :
$user = get_user_by('login', 'your_admin_username');
$user->add_cap('manage_payments');
```

### 3. Si tables manquantes
```php
// Réactivez le plugin pour recréer les tables :
deactivate_plugins('juniorgolfkenya/juniorgolfkenya.php');
activate_plugin('juniorgolfkenya/juniorgolfkenya.php');
```

## Logs à surveiller

### Fichiers de logs importants
1. `/wp-content/debug.log`
2. Logs d'erreur PHP du serveur
3. Logs MySQL
4. Console JavaScript du navigateur

### Messages d'erreur à rechercher
- `JGK PAYMENT`
- `juniorgolf`
- `Fatal error`
- `Database connection`
- `Permission denied`

## Contact et support

Si le problème persiste après ces vérifications :

1. **Partagez les résultats** du `production_diagnostic.php`
2. **Copiez les logs d'erreurs** pertinents
3. **Précisez l'environnement** (hébergeur, version PHP, etc.)
4. **Testez en mode debug** avec `WP_DEBUG = true`

## Fichiers créés pour le débogage

- `production_diagnostic.php` - Script principal pour production
- `admin_interface_diagnostic.php` - Test interface admin
- `environment_diagnostic.php` - Diagnostic environnement complet
- `admin_simulation.php` - Simulation interface admin
- `debug_payments.php` - Test de base paiements
- `debug_payment_display.php` - Test affichage paiements

Tous ces fichiers sont dans le dossier `tests/` et peuvent être exécutés indépendamment pour des tests spécifiques.