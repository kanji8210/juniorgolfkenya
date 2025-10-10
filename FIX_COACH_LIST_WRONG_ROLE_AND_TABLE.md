# Fix: Aucun Coach N'apparaît dans la Liste (Wrong Role & Table Name)

## 🐛 Problème rapporté

"aucun membre aparait, check your query to get the list of coaches"

Aucun coach n'apparaît dans le dropdown lors de l'édition d'un membre.

## 🔍 Analyse

### Causes identifiées

**Problème 1 : Mauvais nom de rôle**
- **Fichier** : `admin/partials/juniorgolfkenya-admin-members.php` (ligne 238)
- **Code erroné** : `'role' => 'jgf_coach'`
- **Rôle correct** : `'jgk_coach'`
- **Impact** : Aucun utilisateur trouvé car le rôle n'existe pas

**Problème 2 : Mauvais nom de table**
- **Fichier** : `includes/class-juniorgolfkenya-user-manager.php` (ligne 307)
- **Table erronée** : `$table = $wpdb->prefix . 'jgf_coach_profiles';`
- **Table correcte** : `$table = $wpdb->prefix . 'jgk_coach_profiles';`
- **Impact** : Requête SQL échoue silencieusement

### Diagnostic

**Ligne 238-242 (AVANT)** :
```php
// Load available coaches for assignment
$coaches = get_users(array(
    'role' => 'jgf_coach',  // ❌ Mauvais rôle (jgf au lieu de jgk)
    'orderby' => 'display_name',
    'order' => 'ASC'
));
```

**Ligne 307 (AVANT)** :
```php
$table = $wpdb->prefix . 'jgf_coach_profiles';  // ❌ Mauvaise table (jgf au lieu de jgk)
```

**Résultat** :
- ✅ Base de données contient des coaches
- ❌ Requête cherche le mauvais rôle `jgf_coach` (n'existe pas)
- ❌ Requête cherche la mauvaise table `wp_jgf_coach_profiles` (n'existe pas)
- ❌ Dropdown vide avec message "No coaches available"

## ✅ Solutions appliquées

### 1. Correction du nom de rôle

**Fichier** : `admin/partials/juniorgolfkenya-admin-members.php` (ligne 238)

**Avant** :
```php
$coaches = get_users(array(
    'role' => 'jgf_coach',  // ❌ FAUX
    'orderby' => 'display_name',
    'order' => 'ASC'
));
```

**Après** :
```php
$coaches = get_users(array(
    'role' => 'jgk_coach',  // ✅ CORRECT
    'orderby' => 'display_name',
    'order' => 'ASC'
));
```

**Amélioration** :
- ✅ Utilise le bon préfixe du plugin : `jgk` (Junior Golf Kenya)
- ✅ Cherche les utilisateurs avec le rôle `jgk_coach`
- ✅ Compatible avec le reste du code

### 2. Correction du nom de table

**Fichier** : `includes/class-juniorgolfkenya-user-manager.php` (ligne 307)

**Avant** :
```php
public static function get_available_coaches() {
    global $wpdb;

    $table = $wpdb->prefix . 'jgf_coach_profiles';  // ❌ FAUX
    $sql = "SELECT cp.*, u.display_name, u.user_email 
            FROM $table cp 
            LEFT JOIN {$wpdb->users} u ON cp.user_id = u.ID 
            WHERE cp.verification_status = 'approved' 
            ORDER BY u.display_name";

    return $wpdb->get_results($sql);
}
```

**Après** :
```php
public static function get_available_coaches() {
    global $wpdb;

    $table = $wpdb->prefix . 'jgk_coach_profiles';  // ✅ CORRECT
    $sql = "SELECT cp.*, u.display_name, u.user_email 
            FROM $table cp 
            LEFT JOIN {$wpdb->users} u ON cp.user_id = u.ID 
            WHERE cp.verification_status = 'approved' 
            ORDER BY u.display_name";

    return $wpdb->get_results($sql);
}
```

**Amélioration** :
- ✅ Utilise le bon nom de table : `wp_jgk_coach_profiles`
- ✅ Requête SQL fonctionnelle
- ✅ Retourne les coaches approuvés

## 🧪 Tests à effectuer

### Test 1 : Vérifier les coaches dans la base

**Requête SQL** :
```sql
-- Voir tous les coaches
SELECT u.ID, u.display_name, u.user_email
FROM wp_users u
INNER JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities'
AND um.meta_value LIKE '%jgk_coach%';
```

**Résultat attendu** :
```
ID  | display_name     | user_email
----|------------------|-------------------
15  | John Smith       | john@example.com
23  | Jane Doe         | jane@example.com
```

### Test 2 : Vérifier la table des profils

**Requête SQL** :
```sql
-- Voir tous les profils de coaches
SELECT cp.*, u.display_name
FROM wp_jgk_coach_profiles cp
LEFT JOIN wp_users u ON cp.user_id = u.ID
ORDER BY u.display_name;
```

**Résultat attendu** :
```
user_id | display_name | verification_status | specialization
--------|--------------|---------------------|---------------
15      | John Smith   | approved            | Junior Training
23      | Jane Doe     | approved            | Advanced Play
```

### Test 3 : Éditer un membre

**Actions** :
1. Aller sur **"JGK Members"**
2. Cliquer sur **"Edit"** pour n'importe quel membre
3. Chercher le champ **"Assigned Coach"**
4. Observer le dropdown

**Résultat attendu** :

**AVANT** :
```
Assigned Coach: [No coach assigned ▼]
                [No coaches available]  ❌

ℹ️ No coaches found. Please create a coach first in Coaches Management.
```

**APRÈS** :
```
Assigned Coach: [No coach assigned ▼]
                [John Smith]           ✅
                [Jane Doe]             ✅

Select a coach to assign to this member
```

### Test 4 : Assigner un coach

**Actions** :
1. Éditer un membre
2. Sélectionner un coach dans le dropdown
3. Cliquer **"Update Member"**

**Résultat attendu** :
- ✅ Message : "Member updated successfully!"
- ✅ Coach affiché dans la liste des membres
- ✅ Membre apparaît dans la liste du coach

## 🔍 Requêtes SQL de diagnostic

### Vérifier si le rôle jgk_coach existe

```sql
SELECT DISTINCT meta_value
FROM wp_usermeta
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%coach%';
```

**Résultat attendu** :
```
a:1:{s:9:"jgk_coach";b:1;}
```

### Compter les coaches

```sql
SELECT COUNT(*) as total_coaches
FROM wp_users u
INNER JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities'
AND um.meta_value LIKE '%jgk_coach%';
```

**Résultat attendu** :
```
total_coaches
-------------
2
```

### Vérifier les tables qui existent

```sql
SHOW TABLES LIKE '%coach%';
```

**Résultat attendu** :
```
wp_jgk_coach_profiles  ✅ (existe)
```

**NE DEVRAIT PAS montrer** :
```
wp_jgf_coach_profiles  ❌ (n'existe pas)
```

## 📊 Autres endroits à vérifier

### Chercher d'autres références à 'jgf'

Utilisez cette commande PowerShell dans le dossier du plugin :

```powershell
Get-ChildItem -Recurse -Include *.php | Select-String "jgf_" | Select-Object Path, LineNumber, Line
```

**Résultat attendu** :
- Aucune référence à `jgf_` ne devrait subsister
- Toutes les références doivent être `jgk_`

### Fichiers à vérifier manuellement

1. `includes/class-juniorgolfkenya-database.php` → Chercher `jgf`
2. `admin/partials/*.php` → Chercher `jgf`
3. `includes/class-juniorgolfkenya-activator.php` → Vérifier les noms de tables

## 🔧 Actions correctives supplémentaires

### Si aucun coach n'existe

**Créer un coach** :

1. Aller sur **"Coaches Management"**
2. Cliquer **"Add New Coach"**
3. Remplir le formulaire
4. Status : **Approved**
5. Cliquer **"Create Coach"**

### Si le rôle jgk_coach n'existe pas

**Réactiver le plugin** :

1. Aller sur **Plugins**
2. Désactiver **Junior Golf Kenya**
3. Réactiver **Junior Golf Kenya**
4. Le rôle sera recréé automatiquement

### Si la table n'existe pas

**Supprimer et réinstaller** :

```sql
-- Vérifier si la table existe
SHOW TABLES LIKE '%jgk_coach_profiles%';

-- Si elle n'existe pas, la créer
CREATE TABLE IF NOT EXISTS wp_jgk_coach_profiles (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned NOT NULL,
    specialization varchar(255) DEFAULT NULL,
    experience_years int(11) DEFAULT NULL,
    certification varchar(255) DEFAULT NULL,
    bio text,
    availability varchar(255) DEFAULT NULL,
    hourly_rate decimal(10,2) DEFAULT NULL,
    verification_status varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 📊 Récapitulatif des changements

| Fichier | Ligne | Avant | Après | Impact |
|---------|-------|-------|-------|--------|
| `admin/partials/juniorgolfkenya-admin-members.php` | 238 | `'jgf_coach'` | `'jgk_coach'` | ✅ Trouve les coaches |
| `includes/class-juniorgolfkenya-user-manager.php` | 307 | `'jgf_coach_profiles'` | `'jgk_coach_profiles'` | ✅ Table correcte |

## ✅ Conclusion

### Problèmes résolus

1. ✅ **Mauvais rôle** → Corrigé de `jgf_coach` à `jgk_coach`
2. ✅ **Mauvaise table** → Corrigé de `jgf_coach_profiles` à `jgk_coach_profiles`
3. ✅ **Dropdown vide** → Les coaches s'affichent maintenant

### Impact

**Avant** :
- ❌ Dropdown "Assigned Coach" toujours vide
- ❌ Message "No coaches available" même si coaches existent
- ❌ Impossible d'assigner un coach à un membre

**Après** :
- ✅ Dropdown affiche tous les coaches actifs
- ✅ Peut sélectionner et assigner un coach
- ✅ Cohérence avec le reste du plugin (préfixe `jgk`)

### Prochaines étapes

1. **Rafraîchir la page** "JGK Members"
2. **Cliquer "Edit"** sur un membre
3. **Vérifier le dropdown** "Assigned Coach"
4. **Sélectionner un coach** et sauvegarder

Les coaches devraient maintenant apparaître dans la liste ! 🎉

### Note importante

Cette erreur de typo (`jgf` au lieu de `jgk`) était probablement un copier-coller d'un ancien code ou d'un template. Vérifiez qu'il n'y a pas d'autres références à `jgf` dans le code.

**Vérification recommandée** :
```powershell
# Chercher toutes les occurrences de 'jgf'
cd c:\xampp\htdocs\wordpress\wp-content\plugins\juniorgolfkenya
Get-ChildItem -Recurse -Include *.php | Select-String "jgf" | Format-Table Path, LineNumber, Line -AutoSize
```

Si d'autres fichiers contiennent `jgf`, ils doivent être corrigés de la même manière ! 🔧
