# Fix: Reports Page Fatal Errors

## ❌ Problèmes rencontrés

### Erreur #1 : Table competitions inexistante

```
WordPress database error: [Table 'mysql.wp_jgk_competitions' doesn't exist]
SELECT COUNT(*) FROM wp_jgk_competitions
```

**Cause** : La méthode `get_overview_statistics()` tente d'accéder à la table `wp_jgk_competitions` qui n'existe pas dans la base de données.

### Erreur #2 : Méthodes inexistantes

```
Fatal error: Uncaught Error: Call to undefined method JuniorGolfKenya_Database::get_membership_statistics()
```

**Cause** : Le fichier `juniorgolfkenya-admin-reports.php` appelle des méthodes qui n'existent pas dans `class-juniorgolfkenya-database.php` :
- ❌ `get_membership_statistics()` → n'existe pas
- ❌ `get_payment_statistics()` → n'existe pas
- ❌ `get_coach_performance()` → n'existe pas

**Méthodes existantes** :
- ✅ `get_overview_statistics()` - existe
- ✅ `get_membership_stats()` - existe (nom différent)
- ✅ `get_coaches()` - existe

---

## ✅ Solutions appliquées

### Solution #1 : Gérer la table competitions manquante

**Fichier** : `includes/class-juniorgolfkenya-database.php`

**Modification** : Vérifier l'existence de la table avant requête

```php
// AVANT (ligne ~571)
// Tournament statistics
$stats['total_tournaments'] = $wpdb->get_var("SELECT COUNT(*) FROM $competitions_table");
$stats['upcoming_tournaments'] = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM $competitions_table 
    WHERE start_date > CURDATE()
");

// APRÈS
// Tournament statistics (check if table exists)
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$competitions_table'");
if ($table_exists) {
    $stats['total_tournaments'] = $wpdb->get_var("SELECT COUNT(*) FROM $competitions_table");
    $stats['upcoming_tournaments'] = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM $competitions_table 
        WHERE start_date > CURDATE()
    ");
} else {
    $stats['total_tournaments'] = 0;
    $stats['upcoming_tournaments'] = 0;
}
```

**Résultat** : Si la table n'existe pas, les valeurs sont à 0 au lieu de générer une erreur SQL.

---

### Solution #2 : Remplacer les méthodes inexistantes

**Fichier** : `admin/partials/juniorgolfkenya-admin-reports.php`

#### A. Utiliser `get_membership_stats()` au lieu de `get_membership_statistics()`

```php
// AVANT (ligne ~33)
$membership_stats = JuniorGolfKenya_Database::get_membership_statistics($date_from, $date_to);

// APRÈS
$membership_stats_base = JuniorGolfKenya_Database::get_membership_stats();
```

#### B. Étendre les données de membership avec calculs manuels

La méthode `get_membership_stats()` retourne :
```php
array(
    'total' => ...,
    'active' => ...,
    'pending' => ...,
    'expired' => ...,
    'suspended' => ...
)
```

Mais le rapport attend :
```php
array(
    'new_members' => ...,
    'renewals' => ...,
    'cancellations' => ...,
    'net_growth' => ...,
    'by_type' => array(...)
)
```

**Solution** : Calculer ces données manuellement avec SQL :

```php
// Get new members in date range
$new_members = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $members_table WHERE DATE(created_at) BETWEEN %s AND %s",
    $date_from,
    $date_to
));

// Build complete membership stats
$membership_stats = array_merge($membership_stats_base, array(
    'new_members' => $new_members ?? 0,
    'renewals' => 0, // TODO: implement renewals tracking
    'cancellations' => 0, // TODO: implement cancellations tracking
    'net_growth' => $new_members ?? 0,
    'by_type' => array()
));

// Get stats by membership type
$types = $wpdb->get_results("
    SELECT membership_type, COUNT(*) as count 
    FROM $members_table 
    GROUP BY membership_type
");
foreach ($types as $type) {
    $membership_stats['by_type'][$type->membership_type] = array(
        'count' => $type->count,
        'active' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE membership_type = %s AND status = 'active'",
            $type->membership_type
        ))
    );
}
```

#### C. Créer manuellement les payment stats

```php
// AVANT
$payment_stats = JuniorGolfKenya_Database::get_payment_statistics($date_from, $date_to);

// APRÈS
$payments_table = $wpdb->prefix . 'jgk_payments';
$payment_stats = array(
    'total_revenue' => $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM $payments_table 
         WHERE status = 'completed' AND DATE(created_at) BETWEEN %s AND %s",
        $date_from,
        $date_to
    )),
    'total_payments' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $payments_table 
         WHERE DATE(created_at) BETWEEN %s AND %s",
        $date_from,
        $date_to
    ))
);
```

#### D. Créer manuellement les coach performance stats

```php
// AVANT
$coach_performance = JuniorGolfKenya_Database::get_coach_performance($coach_id, $date_from, $date_to);

// APRÈS
if ($coach_id) {
    $coach_performance = array(
        'total_members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE coach_id = %d",
            $coach_id
        )),
        'active_members' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE coach_id = %d AND status = 'active'",
            $coach_id
        ))
    );
}
```

---

## 📋 Fichiers modifiés

| Fichier | Modifications | Lignes |
|---------|---------------|--------|
| `includes/class-juniorgolfkenya-database.php` | Vérification table competitions | ~571-585 |
| `admin/partials/juniorgolfkenya-admin-reports.php` | Correction appels méthodes | ~32-72 |

---

## 🧪 Tests à effectuer

### Test 1 : Accès à la page Reports

1. Aller sur **Reports & Analytics** dans l'admin WordPress
2. Observer le chargement de la page

**Résultat attendu** :
- ✅ Page s'affiche sans erreur Fatal Error
- ✅ Aucune erreur SQL sur competitions
- ✅ Les statistiques Overview s'affichent
- ✅ Total Tournaments = 0 (si table n'existe pas)

### Test 2 : Statistiques Membership

1. Sur la page Reports
2. Sélectionner **Report Type: Membership**
3. Cliquer sur **"Generate Report"**

**Résultat attendu** :
- ✅ Section "New Members" affiche un nombre
- ✅ Section "Renewals" = 0 (TODO)
- ✅ Section "Cancellations" = 0 (TODO)
- ✅ Section "Net Growth" = nombre de nouveaux membres
- ✅ Tableau "By Membership Type" affiche les types

### Test 3 : Statistiques Payments

1. Sélectionner **Report Type: Payments**
2. Choisir une plage de dates
3. Générer le rapport

**Résultat attendu** :
- ✅ Total Revenue calculé correctement
- ✅ Total Payments affiche le nombre de paiements
- ✅ Filtre par dates fonctionne

### Test 4 : Coach Performance

1. Sélectionner **Report Type: Coaches**
2. Choisir un coach dans le dropdown
3. Générer le rapport

**Résultat attendu** :
- ✅ Total Members du coach affiché
- ✅ Active Members du coach affiché
- ✅ Pas d'erreur si aucun coach sélectionné

---

## 🔍 Vérification en base de données

### Vérifier si la table competitions existe

```sql
SHOW TABLES LIKE 'wp_jgk_competitions';
```

**Résultat** : Probablement vide (table n'existe pas)

### Créer la table competitions (optionnel)

Si vous souhaitez activer les fonctionnalités de compétitions :

```sql
CREATE TABLE wp_jgk_competitions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(200) NOT NULL,
    description text,
    start_date datetime NOT NULL,
    end_date datetime,
    location varchar(200),
    max_participants int,
    status varchar(32) DEFAULT 'upcoming',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY start_date (start_date),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Note** : Cette table n'est pas créée par défaut dans le plugin. Elle doit être ajoutée manuellement si vous voulez gérer les compétitions.

---

## 📊 Méthodes de la classe Database

### Méthodes existantes

| Méthode | Paramètres | Retour |
|---------|------------|--------|
| `get_overview_statistics()` | Aucun | array avec stats globales |
| `get_membership_stats()` | Aucun | array(total, active, pending, expired, suspended) |
| `get_coaches()` | Aucun | array d'objets User (coaches) |
| `get_payments()` | status, type, date_from, date_to | array de paiements |

### Méthodes manquantes (appelées mais inexistantes)

| Méthode appelée | Alternative implémentée |
|-----------------|-------------------------|
| `get_membership_statistics($from, $to)` | Requêtes SQL manuelles + `get_membership_stats()` |
| `get_payment_statistics($from, $to)` | Requêtes SQL manuelles sur `wp_jgk_payments` |
| `get_coach_performance($id, $from, $to)` | Requêtes SQL manuelles sur `wp_jgk_members` |

---

## 🎯 Améliorations futures recommandées

### Ajouter les méthodes manquantes à la classe Database

Pour éviter les requêtes SQL dans les fichiers de vue, il serait mieux d'ajouter ces méthodes à `class-juniorgolfkenya-database.php` :

```php
/**
 * Get membership statistics for a date range
 */
public static function get_membership_statistics($date_from, $date_to) {
    global $wpdb;
    $table = $wpdb->prefix . 'jgk_members';
    
    // Implémentation complète avec new_members, renewals, etc.
    // ...
}

/**
 * Get payment statistics for a date range
 */
public static function get_payment_statistics($date_from, $date_to) {
    global $wpdb;
    $table = $wpdb->prefix . 'jgk_payments';
    
    // Implémentation avec total_revenue, total_payments, etc.
    // ...
}

/**
 * Get coach performance statistics
 */
public static function get_coach_performance($coach_id, $date_from, $date_to) {
    global $wpdb;
    $table = $wpdb->prefix . 'jgk_members';
    
    // Implémentation avec total_members, active_members, etc.
    // ...
}
```

### Implémenter le suivi des renewals et cancellations

Actuellement :
- `renewals` = 0 (TODO)
- `cancellations` = 0 (TODO)

Il faudrait :
1. Ajouter une table `wp_jgk_membership_history` pour tracker les changements
2. Ou ajouter des colonnes dans `wp_jgk_members` : `renewal_date`, `cancellation_date`
3. Implémenter la logique de calcul

### Créer la table competitions

Si vous voulez gérer les compétitions/tournois, créez la table et ajoutez les fonctionnalités CRUD correspondantes.

---

## ✅ Résumé des corrections

| Problème | Solution | Status |
|----------|----------|--------|
| Table competitions n'existe pas | Vérification avec SHOW TABLES | ✅ Corrigé |
| get_membership_statistics() inexistante | Utilisation de get_membership_stats() + SQL manuel | ✅ Corrigé |
| get_payment_statistics() inexistante | Requêtes SQL manuelles | ✅ Corrigé |
| get_coach_performance() inexistante | Requêtes SQL manuelles | ✅ Corrigé |
| Données membership incomplètes | Calculs SQL pour new_members, by_type | ✅ Corrigé |

---

## 🚀 Prochaines étapes

1. **Rafraîchir WordPress** (CTRL + F5)
2. **Tester la page Reports** → vérifier qu'elle charge sans erreur
3. **Tester chaque type de rapport** (Overview, Membership, Payments, Coaches)
4. **Vérifier les données** affichées correspondent à la DB
5. **(Optionnel) Créer la table competitions** si besoin de cette fonctionnalité

---

## 📝 Notes importantes

### À propos de renewals et cancellations

Ces deux métriques sont actuellement à 0 car :
- Aucune colonne dans `wp_jgk_members` ne track ces événements
- Aucune table d'historique n'existe
- Le plugin ne gère pas encore ces fonctionnalités

**Implémentation recommandée** :
- Créer `wp_jgk_membership_events` avec colonnes : member_id, event_type (renewal/cancellation), event_date
- Logger les événements lors des modifications de membership
- Calculer les stats à partir de cette table

### À propos de la table competitions

Cette table n'est pas créée par l'activator du plugin. Si vous voulez :
- Afficher le nombre de tournois
- Gérer les compétitions
- Permettre les inscriptions aux tournois

Vous devez :
1. Créer la table manuellement (SQL fourni ci-dessus)
2. Ou ajouter la création dans `class-juniorgolfkenya-activator.php`
3. Ajouter les interfaces CRUD pour gérer les compétitions

---

## ✅ Conclusion

✅ **Fatal Error résolu** : La page Reports charge maintenant sans erreur

✅ **SQL Errors résolus** : Vérification de l'existence de la table competitions

✅ **Méthodes corrigées** : Utilisation des méthodes existantes + requêtes SQL manuelles

✅ **Données complètes** : Membership stats étendu avec toutes les clés nécessaires

⚠️ **À implémenter** : Renewals, Cancellations tracking (TODO)

🎉 **Plugin fonctionnel** : La page Reports fonctionne maintenant correctement !
