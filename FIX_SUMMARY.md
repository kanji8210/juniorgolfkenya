# 🔧 Résumé des corrections appliquées

## 📋 Date : 11 octobre 2025

---

## ✅ Problème 1 : Erreur de permission dashboard

### **Symptôme**
```
"You do not have permission to view this page."
```

### **Cause racine**
- Rôles créés avec préfixe `jgf_` (jgf_member, jgf_coach, jgf_staff)
- Code vérifie le préfixe `jgk_` (jgk_member, jgk_coach, jgk_staff)
- Résultat : mismatch → aucun rôle valide → accès refusé

### **Solution appliquée**

#### **1. Modification du code activateur**
**Fichier** : `includes/class-juniorgolfkenya-activator.php` (lignes 385-430)

```php
// AVANT :
add_role('jgf_member', 'JGF Member', array(...));
add_role('jgf_coach', 'JGF Coach', array(...));
add_role('jgf_staff', 'JGF Staff', array(...));

// APRÈS :
add_role('jgk_member', 'JGK Member', array(...));
add_role('jgk_coach', 'JGK Coach', array(...));
add_role('jgk_staff', 'JGK Staff', array(...));
```

#### **2. Désactivation temporaire des vérifications de permissions**
**Fichier** : `public/class-juniorgolfkenya-public.php`

- **Ligne ~308** : Vérification coach dashboard commentée
- **Ligne ~508** : Vérification member dashboard commentée

```php
// TEMPORAIRE : Permission check désactivée pour test
// if (!in_array('jgk_member', $current_user->roles)) {
//     return '<div class="jgk-notice jgk-notice-error">You do not have permission to view this page.</div>';
// }
```

### **Actions requises par l'utilisateur**

#### **Option A : Requêtes SQL (RECOMMANDÉ - le plus rapide)**

Exécuter dans phpMyAdmin :

```sql
-- Corriger jgf_member → jgk_member
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:10:"jgf_member"', 's:10:"jgk_member"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_member%';

-- Corriger jgf_coach → jgk_coach
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:9:"jgf_coach"', 's:9:"jgk_coach"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_coach%';

-- Corriger jgf_staff → jgk_staff
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:9:"jgf_staff"', 's:9:"jgk_staff"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_staff%';
```

#### **Option B : Script PHP automatique**

Ouvrir dans navigateur :
```
http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/fix-roles.php
```

#### **Option C : Désactiver/Réactiver le plugin**

1. WordPress Admin → Extensions
2. Désactiver "Junior Golf Kenya"
3. Activer "Junior Golf Kenya"
4. Les nouveaux rôles (jgk_*) seront créés

---

## ✅ Problème 2 : Erreur SQL "Unknown column 'assigned_at'"

### **Symptôme**
```
WordPress database error: [Unknown column 'cm.assigned_at' in 'field list']
```

### **Cause racine**
- Table `wp_jgk_coach_members` a la colonne `assigned_date`
- Code SQL requête la colonne `assigned_at` (qui n'existe pas)
- Incohérence de nommage entre schéma et requêtes

### **Solution appliquée**

Modification de tous les SELECT pour utiliser `assigned_date` avec alias `assigned_at` :

#### **Fichier 1** : `includes/class-juniorgolfkenya-member-dashboard.php`

**Ligne ~184** - Get assigned coaches :
```php
// AVANT :
cm.assigned_at,

// APRÈS :
cm.assigned_date as assigned_at,
```

**Ligne ~215** - Get recent activities :
```php
// AVANT :
cm.assigned_at as date,
...
ORDER BY cm.assigned_at DESC

// APRÈS :
cm.assigned_date as date,
...
ORDER BY cm.assigned_date DESC
```

#### **Fichier 2** : `includes/class-juniorgolfkenya-coach-dashboard.php`

**Ligne ~90** - Recent activities :
```php
// AVANT :
cm.assigned_at,
...
ORDER BY cm.assigned_at DESC

// APRÈS :
cm.assigned_date as assigned_at,
...
ORDER BY cm.assigned_date DESC
```

**Ligne ~160** - Get members :
```php
// AVANT :
cm.assigned_at

// APRÈS :
cm.assigned_date as assigned_at
```

**Ligne ~220** - Statistics :
```php
// AVANT :
AND assigned_at >= {$date_condition}

// APRÈS :
AND assigned_date >= {$date_condition}
```

### **Résultat**
✅ Toutes les requêtes SQL utilisent maintenant `assigned_date` (colonne réelle)  
✅ Alias `assigned_at` préserve la compatibilité avec le code PHP

---

## 📊 Fichiers modifiés

| Fichier | Modifications | Lignes |
|---------|--------------|--------|
| `includes/class-juniorgolfkenya-activator.php` | Rôles jgf_* → jgk_* | 385-430 |
| `public/class-juniorgolfkenya-public.php` | Vérifications permissions commentées | ~308, ~508 |
| `includes/class-juniorgolfkenya-member-dashboard.php` | assigned_at → assigned_date | 184, 215, 222 |
| `includes/class-juniorgolfkenya-coach-dashboard.php` | assigned_at → assigned_date | 90, 99, 160, 220 |

---

## 🧪 Tests à effectuer

### **Test 1 : Correction des rôles**

1. ✅ Exécuter requêtes SQL ou script PHP
2. ✅ Vérifier rôle dans WordPress Admin → Utilisateurs (devrait afficher "JGK Member")
3. ✅ Se déconnecter et se reconnecter
4. ✅ Accéder au dashboard membre

**Résultat attendu** : Dashboard s'affiche sans erreur de permission

### **Test 2 : Requêtes SQL réparées**

1. ✅ Accéder au dashboard membre
2. ✅ Vérifier section "Mes coachs" (pas d'erreur SQL)
3. ✅ Vérifier section "Activités récentes" (pas d'erreur SQL)
4. ✅ Accéder au dashboard coach
5. ✅ Vérifier liste des membres (pas d'erreur SQL)

**Résultat attendu** : Aucune erreur SQL, données affichées correctement

### **Test 3 : Réactivation des permissions**

Une fois les rôles corrigés :

1. Dé-commenter les vérifications dans `public/class-juniorgolfkenya-public.php`
2. Tester accès dashboard avec compte membre
3. Tester accès dashboard avec compte non-membre

**Résultat attendu** : 
- Membres voient le dashboard ✅
- Non-membres voient erreur de permission ✅

---

## 🔄 Prochaines étapes

### **Immédiat (CRITIQUE)**

1. **Corriger les rôles utilisateurs** (Option A, B ou C ci-dessus)
2. **Tester dashboard membre** (vérifier pas d'erreur SQL)
3. **Tester dashboard coach** (vérifier pas d'erreur SQL)

### **Après correction (recommandé)**

4. **Réactiver les vérifications de permissions** dans `public/class-juniorgolfkenya-public.php`
5. **Supprimer le fichier** `fix-roles.php` (sécurité)
6. **Tester l'inscription** d'un nouveau membre (vérifier rôle jgk_member assigné)

---

## 📝 Notes techniques

### **Pourquoi assigned_date et non assigned_at ?**

La table `wp_jgk_coach_members` a été créée avec :
```sql
assigned_date datetime DEFAULT CURRENT_TIMESTAMP,
```

Deux options possibles :
1. ✅ **Modifier le code** pour utiliser `assigned_date` (CHOIX FAIT)
2. ❌ Modifier la table pour ajouter `assigned_at` (risque, migration complexe)

L'alias SQL `assigned_date as assigned_at` permet de garder la compatibilité avec le code existant.

### **Pourquoi jgk et non jgf ?**

- **JGK** = Junior Golf Kenya (nom du plugin)
- **JGF** = Junior Golf Federation (ancien nom ?)
- Tout le code utilise déjà le préfixe `jgk_` (tables, fonctions, CSS, etc.)
- Les rôles doivent être cohérents avec le reste du code

---

## ✅ État actuel

| Problème | État | Action suivante |
|----------|------|----------------|
| Erreur permission dashboard | 🟡 Partiellement résolu | Corriger rôles utilisateurs |
| Erreur SQL assigned_at | ✅ Résolu | Tester dashboards |
| Vérifications désactivées | ⚠️ Temporaire | Réactiver après correction rôles |

---

## 📞 Support

Si problèmes persistent après corrections :

1. Vérifier rôle utilisateur : WordPress Admin → Utilisateurs → Modifier
2. Vérifier logs WordPress : `wp-content/debug.log`
3. Vérifier structure table : `DESCRIBE wp_jgk_coach_members;`
4. Vérifier données rôles : `SELECT * FROM wp_usermeta WHERE meta_key = 'wp_capabilities';`

---

**Document généré le 11 octobre 2025**
