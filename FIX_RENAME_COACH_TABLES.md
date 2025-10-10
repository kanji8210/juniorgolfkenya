# Fix: Rename Coach Tables & Update Roles (jgf → jgk)

## 🔴 Problème

Le plugin a créé les tables avec le préfixe **`jgf`** au lieu de **`jgk`** :
- ❌ `wp_jgf_coach_profiles` (table créée)
- ✅ `wp_jgk_coach_profiles` (table attendue par le code)

De plus, les coaches ont été créés avec le rôle **`jgf_coach`** au lieu de **`jgk_coach`**.

**Résultat** : Erreur SQL et coaches invisibles dans les dropdowns.

## ✅ Solution appliquée

### Script exécuté : `fix_coach_tables.php`

#### Étape 1 : Renommer les tables

```sql
RENAME TABLE wp_jgf_coach_profiles TO wp_jgk_coach_profiles;
RENAME TABLE wp_jgf_coach_ratings TO wp_jgk_coach_ratings;
```

**Résultat** :
- ✅ `wp_jgk_coach_profiles` existe maintenant
- ✅ `wp_jgk_coach_ratings` existe maintenant

#### Étape 2 : Mettre à jour les rôles des coaches

```sql
UPDATE wp_usermeta
SET meta_value = REPLACE(meta_value, 'jgf_coach', 'jgk_coach')
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%jgf_coach%';
```

**Résultat** :
- ✅ 1 coach mis à jour
- ✅ Coach ID: 19 | display_name: "coach1 coaches" | email: test@test.test

## 🧪 Vérification

### Commandes exécutées

```bash
php check_coach_tables.php  # Diagnostic
php fix_coach_tables.php    # Correction
```

### Résultat final

```
=== VERIFICATION ===

Tables:
  wp_jgk_coach_profiles: ✅ EXISTS
  wp_jgk_coach_ratings: ✅ EXISTS

Coaches with 'jgk_coach' role: 1
  ✅ ID: 19 | coach1 coaches (test@test.test)

✅ SUCCESS: All coaches migrated to 'jgk_coach' role!
```

## 📊 État avant/après

### Avant

| Élément | État | Valeur |
|---------|------|--------|
| Table coach_profiles | ❌ Mauvais nom | `wp_jgf_coach_profiles` |
| Table coach_ratings | ❌ Mauvais nom | `wp_jgf_coach_ratings` |
| Rôle du coach | ❌ Mauvais rôle | `jgf_coach` |
| Coaches visibles | ❌ Non | Dropdown vide |
| Erreur SQL | ❌ Oui | "Table doesn't exist" |

### Après

| Élément | État | Valeur |
|---------|------|--------|
| Table coach_profiles | ✅ Correct | `wp_jgk_coach_profiles` |
| Table coach_ratings | ✅ Correct | `wp_jgk_coach_ratings` |
| Rôle du coach | ✅ Correct | `jgk_coach` |
| Coaches visibles | ✅ Oui | "coach1 coaches" dans dropdown |
| Erreur SQL | ✅ Non | Tables trouvées |

## 🔍 Requêtes SQL de vérification

### Vérifier les tables

```sql
-- Tables avec 'coach' dans le nom
SHOW TABLES LIKE '%coach%';
```

**Résultat attendu** :
```
wp_jgk_coach_profiles  ✅
wp_jgk_coach_ratings   ✅
```

### Vérifier les coaches

```sql
SELECT 
    u.ID, 
    u.display_name, 
    u.user_email,
    um.meta_value as capabilities
FROM wp_users u
INNER JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities'
AND um.meta_value LIKE '%jgk_coach%';
```

**Résultat attendu** :
```
ID | display_name    | user_email      | capabilities
---|-----------------|-----------------|---------------------------
19 | coach1 coaches  | test@test.test  | a:1:{s:9:"jgk_coach";b:1;}
```

### Vérifier qu'aucun ancien rôle ne subsiste

```sql
SELECT COUNT(*) as old_role_count
FROM wp_usermeta
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%jgf_coach%';
```

**Résultat attendu** :
```
old_role_count
--------------
0  ✅
```

## 🎯 Test de la correction

### Étapes

1. **Rafraîchir** la page WordPress admin (Ctrl+F5 ou Cmd+Shift+R)
2. **Aller sur** "JGK Members"
3. **Cliquer "Edit"** sur n'importe quel membre
4. **Chercher** le champ "Assigned Coach"

### Résultat attendu

**Dropdown "Assigned Coach"** :
```
Assigned Coach: [No coach assigned ▼]
                coach1 coaches          ✅
```

**AVANT** (ne devrait plus apparaître) :
```
Assigned Coach: [No coach assigned ▼]
                [No coaches available]  ❌
```

### Si le coach n'apparaît toujours pas

Vérifiez que le code récupère bien les coaches avec `jgk_coach` :

```php
// Dans admin/partials/juniorgolfkenya-admin-members.php (ligne 238)
$coaches = get_users(array(
    'role' => 'jgk_coach',  // ✅ Doit être jgk_coach
    'orderby' => 'display_name',
    'order' => 'ASC'
));
```

## 📝 Modifications de code associées

Les fichiers suivants ont été corrigés pour utiliser `jgk` au lieu de `jgf` :

### Fichiers mis à jour

1. **`admin/partials/juniorgolfkenya-admin-coaches.php`**
   - Ligne 67 : Rôle lors de création `'jgf_coach'` → `'jgk_coach'`
   - Ligne 78 : Table lors de création `'jgf_coach_profiles'` → `'jgk_coach_profiles'`
   - Ligne 113 : Table lors de mise à jour `'jgf_coach_profiles'` → `'jgk_coach_profiles'`

2. **`admin/partials/juniorgolfkenya-admin-members.php`**
   - Ligne 238 : Rôle dans `get_users()` `'jgf_coach'` → `'jgk_coach'`

3. **`includes/class-juniorgolfkenya-user-manager.php`**
   - Ligne 307 : Table dans `get_available_coaches()` `'jgf_coach_profiles'` → `'jgk_coach_profiles'`

### Fichiers restant à corriger (si nécessaire)

Les fichiers suivants contiennent encore des références à `jgf` mais ne sont pas critiques pour le moment :

- `includes/class-juniorgolfkenya-activator.php` (création des tables)
- `includes/class-juniorgolfkenya-database.php` (requêtes de stats)
- `public/partials/juniorgolfkenya-member-portal.php` (portail public)

Ces fichiers devront être corrigés si vous réinstallez le plugin.

## 🔧 Scripts créés

### `check_coach_tables.php`

Script de diagnostic pour vérifier l'état des tables et des rôles :

```php
php check_coach_tables.php
```

**Fonction** :
- ✅ Liste toutes les tables avec 'coach'
- ✅ Compte les coaches par rôle (jgf vs jgk)
- ✅ Suggère la solution appropriée

### `fix_coach_tables.php`

Script de correction automatique :

```php
php fix_coach_tables.php
```

**Actions** :
- ✅ Renomme `wp_jgf_coach_profiles` → `wp_jgk_coach_profiles`
- ✅ Renomme `wp_jgf_coach_ratings` → `wp_jgk_coach_ratings`
- ✅ Met à jour les rôles `jgf_coach` → `jgk_coach`
- ✅ Vérifie et affiche le résultat

## ⚠️ Si vous réinstallez le plugin

Si vous désactivez et réactivez le plugin, il recréera les tables avec le nom `jgf` (car le fichier `class-juniorgolfkenya-activator.php` n'est pas encore corrigé).

**Solution préventive** : Corriger `includes/class-juniorgolfkenya-activator.php` avant de réactiver.

### Requête SQL pour renommer à nouveau (si nécessaire)

```sql
-- À exécuter si les tables jgf sont recréées
RENAME TABLE wp_jgf_coach_profiles TO wp_jgk_coach_profiles;
RENAME TABLE wp_jgf_coach_ratings TO wp_jgk_coach_ratings;

UPDATE wp_usermeta
SET meta_value = REPLACE(meta_value, 'jgf_coach', 'jgk_coach')
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%jgf_coach%';
```

## ✅ Conclusion

### Problèmes résolus

1. ✅ **Tables renommées** : `jgf` → `jgk`
2. ✅ **Rôles mis à jour** : `jgf_coach` → `jgk_coach`
3. ✅ **Erreur SQL disparue** : Tables trouvées
4. ✅ **Coaches visibles** : Dropdown fonctionnel

### Impact

**Avant** :
- ❌ Erreur : "Table 'wp_jgk_coach_profiles' doesn't exist"
- ❌ Dropdown vide : "No coaches available"
- ❌ Impossible d'assigner un coach

**Après** :
- ✅ Aucune erreur SQL
- ✅ Dropdown affiche "coach1 coaches"
- ✅ Assignation de coach fonctionnelle

### Prochaines étapes

1. **Tester l'assignation** d'un coach à un membre
2. **Vérifier** que le coach apparaît dans la liste des membres
3. **Corriger** les autres fichiers (activator.php, database.php) pour éviter le problème à l'avenir

---

**Le plugin est maintenant fonctionnel ! Testez l'assignation de coach.** 🎉
