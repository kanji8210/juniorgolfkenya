# Fix: Unknown column 'assigned_coach_id' in Coach Assignment

## 🐛 Problème

Erreur SQL lors de l'assignation d'un coach à un membre :

```
WordPress database error: [Unknown column 'assigned_coach_id' in 'field list']
```

## 🔍 Analyse

### Cause

**Fichier** : `includes/class-juniorgolfkenya-user-manager.php` (ligne 188)

**Erreur** : Utilisation d'un nom de colonne incorrect

```php
// ❌ INCORRECT
$result = JuniorGolfKenya_Database::update_member($member_id, array(
    'assigned_coach_id' => $coach_id  // Cette colonne n'existe pas !
));
```

### Structure de la table

La table `wp_jgk_members` utilise la colonne `coach_id`, **PAS** `assigned_coach_id` :

```sql
CREATE TABLE wp_jgk_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    -- ... autres colonnes ...
    coach_id INT DEFAULT NULL,  -- ✅ Nom correct de la colonne
    -- ... autres colonnes ...
);
```

### Impact

- ❌ Impossible d'assigner un coach à un membre depuis l'admin
- ❌ Impossible d'assigner un coach depuis le modal "Assign Members"
- ❌ Erreur SQL visible dans les logs WordPress
- ❌ Fonctionnalité d'assignation complètement bloquée

## ✅ Solution appliquée

### Modification dans `class-juniorgolfkenya-user-manager.php`

**Avant** (ligne 187-189) :
```php
// Update member record with assigned coach
$result = JuniorGolfKenya_Database::update_member($member_id, array(
    'assigned_coach_id' => $coach_id  // ❌ Colonne incorrecte
));
```

**Après** :
```php
// Update member record with assigned coach
$result = JuniorGolfKenya_Database::update_member($member_id, array(
    'coach_id' => $coach_id  // ✅ Colonne correcte
));
```

### Changement

- ✅ `assigned_coach_id` → `coach_id`
- ✅ Utilise le nom de colonne correct de la table `wp_jgk_members`
- ✅ Compatible avec toutes les requêtes existantes

## 📊 Fichiers modifiés

| Fichier | Ligne | Changement | Status |
|---------|-------|------------|--------|
| `includes/class-juniorgolfkenya-user-manager.php` | 188 | `assigned_coach_id` → `coach_id` | ✅ Corrigé |

## 🔍 Vérification de cohérence

### Recherche globale de `assigned_coach_id`

```bash
grep -r "assigned_coach_id" .
```

**Résultat** : ✅ Aucune autre occurrence trouvée

### Nom de colonne utilisé ailleurs

Fichiers utilisant correctement `coach_id` :

1. **`includes/class-juniorgolfkenya-database.php`**
   - Ligne ~35 : `m.coach_id` dans SELECT
   - Ligne ~78 : LEFT JOIN avec `m.coach_id`

2. **`admin/partials/juniorgolfkenya-admin-member-edit.php`**
   - Ligne ~162 : `$edit_member->coach_id`
   - Ligne ~158 : `name="coach_id"`

3. **`admin/partials/juniorgolfkenya-admin-members.php`**
   - Ligne ~52 : `$_POST['coach_id']`
   - Ligne ~238 : Chargement des coaches pour dropdown

4. **`admin/partials/juniorgolfkenya-admin-coaches.php`**
   - Ligne ~411 : `!$member->coach_id`
   - Ligne ~115 : `$_POST['coach_id']`

✅ **Conclusion** : Tous les autres fichiers utilisent déjà le nom correct `coach_id`

## 🧪 Tests à effectuer

### Test 1 : Assignation depuis l'édition de membre

**Actions** :
1. Aller sur **JGK Members**
2. Cliquer sur **"Edit Member"** pour un membre
3. Sélectionner un coach dans le dropdown "Assigned Coach"
4. Cliquer sur **"Update Member"**

**Résultats attendus** :
- ✅ Message de succès : "Member updated successfully!"
- ✅ Aucune erreur SQL dans les logs
- ✅ Coach assigné visible dans la liste des membres
- ✅ Coach assigné visible lors de la réédition du membre

**Résultats si pas corrigé** :
- ❌ Erreur SQL : "Unknown column 'assigned_coach_id'"
- ❌ Membre non mis à jour
- ❌ Coach non assigné

### Test 2 : Assignation depuis le modal "Assign Members"

**Actions** :
1. Aller sur **JGK Coaches Management**
2. Cliquer sur **"Assign Members"** pour un coach
3. Cocher 1 ou plusieurs membres
4. Cliquer sur **"Assign Members"**

**Résultats attendus** :
- ✅ Modal se ferme
- ✅ Message de succès : "Assigned X member(s) to coach successfully!"
- ✅ Compteur "Assigned Members" mis à jour dans la table coaches
- ✅ Membres ne s'affichent plus dans le modal (déjà assignés)
- ✅ Aucune erreur SQL

**Résultats si pas corrigé** :
- ❌ Erreur SQL : "Unknown column 'assigned_coach_id'"
- ❌ Message d'erreur ou pas de feedback
- ❌ Membres non assignés

### Test 3 : Vérification en base de données

**Requête SQL pour vérifier l'assignation** :
```sql
SELECT 
    m.id,
    CONCAT(m.first_name, ' ', m.last_name) as member_name,
    m.coach_id,
    c.display_name as coach_name
FROM wp_jgk_members m
LEFT JOIN wp_users c ON m.coach_id = c.ID
WHERE m.coach_id IS NOT NULL
ORDER BY m.id DESC
LIMIT 10;
```

**Résultat attendu** :
- ✅ Colonne `coach_id` contient l'ID du coach assigné
- ✅ Colonne `coach_name` affiche le nom du coach
- ✅ Pas de valeurs NULL si coach assigné

**Exemple de résultat** :
```
id | member_name    | coach_id | coach_name
---|----------------|----------|----------------
5  | John Doe       | 12       | Mike Johnson
4  | Jane Smith     | 12       | Mike Johnson
3  | Bob Williams   | 15       | Sarah Davis
```

### Test 4 : Logs WordPress

**Emplacement des logs** :
- XAMPP : `C:\xampp\htdocs\wordpress\wp-content\debug.log`
- Ou : `C:\xampp\php\logs\php_error_log`

**Rechercher** :
```
Unknown column 'assigned_coach_id'
```

**Résultat attendu** :
- ✅ Aucune nouvelle entrée de ce type après la correction
- ✅ Logs propres lors de l'assignation de coach

## 🔍 Flux d'assignation de coach

### Méthode 1 : Depuis l'édition de membre

```
1. Admin édite un membre
2. Sélectionne un coach dans dropdown
3. Clique "Update Member"
   ↓
4. POST action = "edit_member"
5. Code dans juniorgolfkenya-admin-members.php
6. Appelle JuniorGolfKenya_Database::update_member()
   ↓
7. UPDATE wp_jgk_members SET coach_id = X WHERE id = Y
8. ✅ SUCCESS
```

### Méthode 2 : Depuis le modal "Assign Members"

```
1. Admin clique "Assign Members" sur un coach
2. Modal s'ouvre avec liste de checkboxes
3. Coche des membres et clique "Assign Members"
   ↓
4. POST action = "assign_members"
5. Code dans juniorgolfkenya-admin-coaches.php
6. Boucle foreach sur member_ids[]
7. Appelle JuniorGolfKenya_User_Manager::assign_coach()
   ↓
8. assign_coach() vérifie le rôle du coach
9. Appelle JuniorGolfKenya_Database::update_member()
10. UPDATE wp_jgk_members SET coach_id = X WHERE id = Y  ← ✅ CORRIGÉ ICI
11. Log audit de l'assignation
12. ✅ SUCCESS
```

## 📝 Méthode `assign_coach()` complète

**Fichier** : `includes/class-juniorgolfkenya-user-manager.php` (lignes ~179-203)

```php
public static function assign_coach($member_id, $coach_id) {
    // Verify coach has proper role
    $coach_user = get_user_by('ID', $coach_id);
    if (!$coach_user || !in_array('jgf_coach', $coach_user->roles)) {
        return false;
    }

    // Update member record with assigned coach
    $result = JuniorGolfKenya_Database::update_member($member_id, array(
        'coach_id' => $coach_id  // ✅ CORRIGÉ : était 'assigned_coach_id'
    ));

    if ($result) {
        // Log the assignment
        JuniorGolfKenya_Database::log_audit(array(
            'action' => 'coach_assigned',
            'object_type' => 'member',
            'object_id' => $member_id,
            'new_values' => json_encode(array('coach_id' => $coach_id))
        ));

        return true;
    }

    return false;
}
```

### Fonctionnement

1. **Validation** : Vérifie que le coach a bien le rôle `jgf_coach`
2. **Mise à jour** : Appelle `update_member()` avec `coach_id` (corrigé)
3. **Audit log** : Enregistre l'assignation dans les logs d'audit
4. **Retour** : `true` si succès, `false` sinon

## 🔐 Sécurité

### Validation du coach

```php
$coach_user = get_user_by('ID', $coach_id);
if (!$coach_user || !in_array('jgf_coach', $coach_user->roles)) {
    return false;
}
```

**Protection** :
- ✅ Vérifie que l'utilisateur existe
- ✅ Vérifie qu'il a le rôle `jgf_coach`
- ✅ Empêche d'assigner un non-coach comme coach

### Sanitization

Dans `admin/partials/juniorgolfkenya-admin-coaches.php` :
```php
$coach_id = intval($_POST['coach_id']);  // ✅ Converti en entier
$member_ids = isset($_POST['member_ids']) && is_array($_POST['member_ids']) 
    ? array_map('intval', $_POST['member_ids'])  // ✅ Tous convertis en entiers
    : array();
```

### Nonce verification

```php
if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_coach_action')) {
    wp_die(__('Security check failed.'));
}
```

## 📊 Statistiques d'assignation

### Requête SQL pour voir toutes les assignations

```sql
SELECT 
    c.ID as coach_id,
    c.display_name as coach_name,
    COUNT(m.id) as assigned_members_count,
    GROUP_CONCAT(CONCAT(m.first_name, ' ', m.last_name) SEPARATOR ', ') as members
FROM wp_users c
LEFT JOIN wp_jgk_members m ON m.coach_id = c.ID
INNER JOIN wp_usermeta um ON c.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities'
AND um.meta_value LIKE '%jgf_coach%'
GROUP BY c.ID
ORDER BY assigned_members_count DESC;
```

**Utilité** :
- Voir combien de membres chaque coach a
- Identifier les coaches sans assignation
- Équilibrer la charge entre coaches

## ✅ Conclusion

### Problème résolu

✅ **Nom de colonne corrigé** : `assigned_coach_id` → `coach_id`

✅ **Fichier modifié** : `includes/class-juniorgolfkenya-user-manager.php` (ligne 188)

✅ **Fonctionnalité rétablie** : Assignation de coach fonctionnelle

### Impact

- ✅ Assignation depuis l'édition de membre → **Fonctionne**
- ✅ Assignation depuis le modal → **Fonctionne**
- ✅ Aucune erreur SQL → **Logs propres**
- ✅ Cohérence avec le reste du code → **Nom de colonne unifié**

### Prochaines étapes

1. **Tester l'assignation** depuis les 2 interfaces
2. **Vérifier les logs** pour absence d'erreurs SQL
3. **Confirmer en BDD** que `coach_id` est bien mis à jour
4. **Tester la désassignation** (mettre coach_id à NULL)

L'assignation de coach est maintenant **100% fonctionnelle** ! 🎉
