# URGENT: Fix Existing Coaches Role (jgf_coach → jgk_coach)

## 🔴 Problème Critique

Votre plugin a créé les coaches avec le **mauvais rôle** : `jgf_coach` au lieu de `jgk_coach`.

Résultat : Les coaches créés **NE PEUVENT PAS être trouvés** par les requêtes qui cherchent `jgk_coach`.

## 🔧 Solution SQL Immédiate

### Étape 1 : Vérifier les coaches avec le mauvais rôle

Exécutez cette requête SQL pour voir les coaches affectés :

```sql
SELECT 
    u.ID, 
    u.user_login, 
    u.user_email, 
    u.display_name,
    um.meta_value as capabilities
FROM wp_users u
INNER JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities'
AND um.meta_value LIKE '%jgf_coach%';
```

**Résultat attendu** :
```
ID  | user_login         | display_name | capabilities
----|--------------------|--------------|---------------------------------
15  | john@example.com   | John Smith   | a:1:{s:9:"jgf_coach";b:1;}  ❌
```

### Étape 2 : Corriger le rôle des coaches existants

**⚠️ IMPORTANT : Cette requête corrigera TOUS les coaches en une seule fois !**

```sql
-- Mise à jour du rôle de jgf_coach vers jgk_coach
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 'jgf_coach', 'jgk_coach')
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%jgf_coach%';
```

### Étape 3 : Vérifier la correction

Exécutez cette requête pour confirmer que les coaches ont maintenant le bon rôle :

```sql
SELECT 
    u.ID, 
    u.user_login, 
    u.user_email, 
    u.display_name,
    um.meta_value as capabilities
FROM wp_users u
INNER JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities'
AND um.meta_value LIKE '%jgk_coach%';
```

**Résultat attendu** :
```
ID  | user_login         | display_name | capabilities
----|--------------------|--------------|---------------------------------
15  | john@example.com   | John Smith   | a:1:{s:9:"jgk_coach";b:1;}  ✅
```

## 🧪 Test Final

Après avoir exécuté la requête SQL :

1. **Rafraîchissez** la page "JGK Members"
2. **Cliquez "Edit"** sur un membre
3. **Vérifiez le dropdown** "Assigned Coach"

**Résultat attendu** :
```
Assigned Coach: [No coach assigned ▼]
                John Smith              ✅
                Jane Doe                ✅
```

## 📊 Diagnostic Complet

### Compter les coaches par rôle

```sql
-- Coaches avec l'ancien rôle (jgf_coach) - À CORRIGER
SELECT COUNT(*) as total_jgf_coaches
FROM wp_usermeta
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%jgf_coach%';

-- Coaches avec le nouveau rôle (jgk_coach) - CORRECT
SELECT COUNT(*) as total_jgk_coaches
FROM wp_usermeta
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%jgk_coach%';
```

**Avant correction** :
```
total_jgf_coaches: 2  ❌
total_jgk_coaches: 0  ❌
```

**Après correction** :
```
total_jgf_coaches: 0  ✅
total_jgk_coaches: 2  ✅
```

## 🔍 Vérifier les données du coach

Une fois le rôle corrigé, vérifiez que toutes les données du coach sont présentes :

```sql
SELECT 
    u.ID,
    u.display_name,
    u.user_email,
    cp.specialties,
    cp.bio,
    cp.verification_status,
    phone.meta_value as phone,
    exp.meta_value as experience_years
FROM wp_users u
INNER JOIN wp_usermeta um ON u.ID = um.user_id
LEFT JOIN wp_jgk_coach_profiles cp ON u.ID = cp.user_id
LEFT JOIN wp_usermeta phone ON u.ID = phone.user_id AND phone.meta_key = 'phone'
LEFT JOIN wp_usermeta exp ON u.ID = exp.user_id AND exp.meta_key = 'experience_years'
WHERE um.meta_key = 'wp_capabilities'
AND um.meta_value LIKE '%jgk_coach%';
```

**Résultat attendu** :
```
ID | display_name | specialties      | verification_status | phone        | experience_years
---|--------------|------------------|---------------------|--------------|------------------
15 | John Smith   | junior,beginner  | approved            | +254123456   | 5
```

## ⚠️ Si vous créez de nouveaux coaches AVANT la correction du code

Si vous devez créer un nouveau coach **maintenant** (avant que tous les fichiers soient corrigés), utilisez cette requête SQL **immédiatement après la création** :

```sql
-- Remplacer USER_ID par l'ID du coach créé
UPDATE wp_usermeta 
SET meta_value = 'a:1:{s:9:"jgk_coach";b:1;}'
WHERE user_id = USER_ID  -- ⚠️ Remplacer par l'ID réel
AND meta_key = 'wp_capabilities';
```

**Exemple concret** :
```sql
-- Si le coach créé a l'ID 25
UPDATE wp_usermeta 
SET meta_value = 'a:1:{s:9:"jgk_coach";b:1;}'
WHERE user_id = 25
AND meta_key = 'wp_capabilities';
```

## 📝 Modifications de code déjà effectuées

J'ai corrigé les fichiers suivants pour utiliser `jgk_coach` au lieu de `jgf_coach` :

### ✅ Fichiers corrigés

1. **`admin/partials/juniorgolfkenya-admin-members.php`** (ligne 238)
   - Requête `get_users()` : `'jgf_coach'` → `'jgk_coach'`

2. **`includes/class-juniorgolfkenya-user-manager.php`** (ligne 307)
   - Table : `'jgf_coach_profiles'` → `'jgk_coach_profiles'`

3. **`admin/partials/juniorgolfkenya-admin-coaches.php`** (lignes 67, 78, 113)
   - Rôle lors de création : `'jgf_coach'` → `'jgk_coach'`
   - Table lors de création : `'jgf_coach_profiles'` → `'jgk_coach_profiles'`
   - Table lors de mise à jour : `'jgf_coach_profiles'` → `'jgk_coach_profiles'`

### ⚠️ Fichiers ENCORE À CORRIGER

Les fichiers suivants contiennent encore `jgf` et devront être corrigés :

1. `includes/class-juniorgolfkenya-activator.php`
   - Lignes 255, 325, 399, 400, 557, 561
   - **Impact** : Création des tables et rôles lors de l'activation
   
2. `includes/class-juniorgolfkenya-user-manager.php`
   - Lignes 182, 236, 272, 363, 403
   - **Impact** : Gestion des profils et requêtes
   
3. `includes/class-juniorgolfkenya-database.php`
   - Lignes 367, 380, 552
   - **Impact** : Requêtes de statistiques et ratings
   
4. `public/partials/juniorgolfkenya-member-portal.php`
   - Ligne 103
   - **Impact** : Portail membre public

## 🎯 Étapes Complètes de Résolution

### 1️⃣ Correction immédiate (SQL)

```sql
-- Corriger les coaches existants
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 'jgf_coach', 'jgf_coach')
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%jgf_coach%';
```

### 2️⃣ Test de la correction

- Rafraîchir la page d'édition de membre
- Vérifier que les coaches apparaissent dans le dropdown

### 3️⃣ Correction du code (à faire ensuite)

Tous les fichiers listés ci-dessus devront être mis à jour pour remplacer **systématiquement** :
- `jgf_coach` → `jgk_coach`
- `jgf_coach_profiles` → `jgk_coach_profiles`
- `jgf_coach_ratings` → `jgk_coach_ratings`

### 4️⃣ Tests complets

Après toutes les corrections :
- Créer un nouveau coach
- Éditer un membre et assigner le coach
- Vérifier la liste des membres d'un coach
- Tester le portail membre public

## 🔧 Script PowerShell pour vérifier toutes les occurrences

```powershell
# Chercher toutes les références à jgf dans le plugin
cd c:\xampp\htdocs\wordpress\wp-content\plugins\juniorgolfkenya
Get-ChildItem -Recurse -Include *.php | 
    Select-String "jgf" | 
    Group-Object Path | 
    Select-Object Name, Count | 
    Sort-Object Count -Descending | 
    Format-Table -AutoSize
```

## ✅ Conclusion

**Action immédiate requise** :

1. ✅ **Exécuter la requête SQL** pour corriger les coaches existants
2. ✅ **Tester** que les coaches apparaissent maintenant
3. ⏳ **Planifier** la correction complète de tous les fichiers PHP

Une fois la requête SQL exécutée, vos coaches devraient **immédiatement** apparaître dans le dropdown ! 🎉

---

**Dites-moi une fois que vous avez exécuté la requête SQL et je vous aiderai à corriger tous les autres fichiers si nécessaire.**
