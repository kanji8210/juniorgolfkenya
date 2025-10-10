# Fix: Coach Status NULL + Member-Coach Assignment

## 🔧 Problèmes résolus

### Problème #1 : Erreur ucfirst() avec status NULL

**Erreur originale** :
```
Deprecated: ucfirst(): Passing null to parameter #1 ($string) of type string is deprecated 
in juniorgolfkenya-admin-coaches.php on line 313
```

**Cause** : Le champ `status` d'un coach peut être NULL dans la base de données, et la fonction `ucfirst()` ne peut pas traiter NULL en PHP 8.1+.

**Solution appliquée** :
```php
// AVANT (ligne 313)
<span class="jgk-status-<?php echo esc_attr($coach->status); ?>">
    <?php echo ucfirst($coach->status); ?>
</span>

// APRÈS
<span class="jgk-status-<?php echo esc_attr($coach->status ?? 'pending'); ?>">
    <?php echo ucfirst($coach->status ?? 'pending'); ?>
</span>
```

**Résultat** : Si `status` est NULL, il sera affiché comme "Pending" par défaut.

---

### Problème #2 : Impossible d'assigner un coach à un membre

**Symptôme** : Le formulaire d'édition de membre ne permettait pas de sélectionner/changer le coach assigné.

**Cause** : Le champ `coach_id` n'existait pas dans le formulaire d'édition des membres.

**Solution appliquée** :

#### Étape 1 : Charger la liste des coaches (juniorgolfkenya-admin-members.php)

```php
// Avant l'inclusion du formulaire d'édition (ligne ~233)
// Load available coaches for assignment
$coaches = get_users(array(
    'role' => 'jgf_coach',
    'orderby' => 'display_name',
    'order' => 'ASC'
));
```

#### Étape 2 : Ajouter le champ dans le formulaire (juniorgolfkenya-admin-member-edit.php)

Nouveau champ ajouté après "Medical Conditions" :

```php
<div class="jgk-form-row">
    <div class="jgk-form-field">
        <label for="coach_id">Assigned Coach</label>
        <select id="coach_id" name="coach_id">
            <option value="">No coach assigned</option>
            <?php if (!empty($coaches)): ?>
                <?php foreach ($coaches as $coach): ?>
                <option value="<?php echo esc_attr($coach->ID); ?>" 
                        <?php selected($edit_member->coach_id ?? 0, $coach->ID); ?>>
                    <?php echo esc_html($coach->display_name); ?>
                </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <small>Select a coach to assign to this member</small>
    </div>
</div>
```

#### Étape 3 : Sauvegarder le coach_id (juniorgolfkenya-admin-members.php)

Ajout de `coach_id` dans les données de mise à jour (ligne ~148) :

```php
$member_data = array(
    // ... autres champs ...
    'club_affiliation' => sanitize_text_field($_POST['club_affiliation']),
    'coach_id' => !empty($_POST['coach_id']) ? intval($_POST['coach_id']) : null,
    'emergency_contact_name' => sanitize_text_field($_POST['emergency_contact_name']),
    // ... autres champs ...
);
```

---

## 📋 Fichiers modifiés

| Fichier | Modifications | Lignes |
|---------|---------------|--------|
| `admin/partials/juniorgolfkenya-admin-coaches.php` | Fix ucfirst() NULL | ~313 |
| `admin/partials/juniorgolfkenya-admin-members.php` | Charger coaches | ~233 |
| `admin/partials/juniorgolfkenya-admin-members.php` | Sauvegarder coach_id | ~148 |
| `admin/partials/juniorgolfkenya-admin-member-edit.php` | Ajouter champ coach | ~155 |

---

## ✅ Tests à effectuer

### Test 1 : Coach Status Display

1. Aller sur **JGK Coaches**
2. Observer la colonne "Status"
3. Vérifier qu'aucune erreur PHP n'apparaît
4. Les coaches sans status devraient afficher "Pending"

**Résultat attendu** :
- ✅ Aucune erreur "Deprecated: ucfirst()"
- ✅ Status affiché correctement pour tous les coaches
- ✅ Coaches sans status = "Pending"

### Test 2 : Member-Coach Assignment

1. Aller sur **JGK Members**
2. Cliquer sur **"Edit"** pour un membre
3. Descendre jusqu'à la section "Membership Details"
4. Observer le nouveau champ **"Assigned Coach"**

**Résultat attendu** :
- ✅ Dropdown avec liste de tous les coaches
- ✅ Option "No coach assigned" en premier
- ✅ Coach actuellement assigné est pré-sélectionné (si existant)
- ✅ Tous les coaches avec rôle `jgf_coach` sont listés

### Test 3 : Save Coach Assignment

1. Dans le formulaire d'édition de membre
2. Sélectionner un coach dans le dropdown
3. Cliquer sur **"Update Member"**
4. Vérifier le message de succès
5. Réouvrir le formulaire d'édition

**Résultat attendu** :
- ✅ Message : "Member updated successfully!"
- ✅ Le coach sélectionné est bien sauvegardé
- ✅ Lors de la réouverture, le coach est toujours sélectionné

### Test 4 : Remove Coach Assignment

1. Éditer un membre qui a un coach assigné
2. Sélectionner **"No coach assigned"**
3. Sauvegarder
4. Vérifier en base de données

**Résultat attendu** :
- ✅ `coach_id` devient NULL dans la base
- ✅ Lors de la réouverture, "No coach assigned" est sélectionné

---

## 🔍 Vérification en base de données

### Requête 1 : Vérifier les coaches sans status

```sql
SELECT 
    u.ID,
    u.display_name,
    u.user_email,
    cp.verification_status,
    'pending' as default_status
FROM wp_users u
LEFT JOIN wp_jgf_coach_profiles cp ON u.ID = cp.user_id
INNER JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities'
  AND um.meta_value LIKE '%jgf_coach%';
```

**Note** : Les coaches n'ont pas de colonne `status` dans `wp_users`. Le "status" affiché vient probablement de `verification_status` dans `jgf_coach_profiles`.

### Requête 2 : Vérifier les assignments member-coach

```sql
SELECT 
    m.id,
    m.first_name,
    m.last_name,
    m.coach_id,
    c.display_name as coach_name
FROM wp_jgk_members m
LEFT JOIN wp_users c ON m.coach_id = c.ID
WHERE m.coach_id IS NOT NULL
ORDER BY m.id DESC
LIMIT 10;
```

### Requête 3 : Lister tous les coaches disponibles

```sql
SELECT 
    u.ID,
    u.display_name,
    u.user_email,
    COUNT(m.id) as member_count
FROM wp_users u
INNER JOIN wp_usermeta um ON u.ID = um.user_id
LEFT JOIN wp_jgk_members m ON m.coach_id = u.ID
WHERE um.meta_key = 'wp_capabilities'
  AND um.meta_value LIKE '%jgf_coach%'
GROUP BY u.ID
ORDER BY u.display_name ASC;
```

---

## 🎯 Fonctionnement complet

### Workflow : Assigner un coach à un membre

```
1. Admin ouvre l'édition d'un membre
   ↓
2. Le système charge tous les users avec role 'jgf_coach'
   ↓
3. Ces coaches sont affichés dans le dropdown
   ↓
4. Admin sélectionne un coach
   ↓
5. Lors de la sauvegarde, coach_id est enregistré dans wp_jgk_members
   ↓
6. Le membre est maintenant lié au coach dans la DB
```

### Structure des données

```
wp_users
├── ID (coach user ID)
├── display_name
└── user_email

wp_usermeta
├── user_id
├── meta_key = 'wp_capabilities'
└── meta_value LIKE '%jgf_coach%'

wp_jgk_members
├── id (member ID)
├── first_name
├── last_name
└── coach_id (FK → wp_users.ID)  ← UPDATED!

wp_jgf_coach_profiles
├── user_id (FK → wp_users.ID)
├── specialties
├── bio
└── verification_status
```

---

## 🐛 Notes importantes

### À propos du champ "status" pour les coaches

Le champ `$coach->status` dans la liste des coaches vient probablement de la requête SQL qui récupère les coaches. Il peut être :
- Soit `verification_status` de `jgf_coach_profiles` (pending/approved/rejected)
- Soit un champ ajouté dynamiquement

**Protection ajoutée** : `$coach->status ?? 'pending'` garantit qu'il y a toujours une valeur.

### À propos de coach_id dans jgk_members

Le champ `coach_id` dans la table `wp_jgk_members` :
- ✅ Est une clé étrangère vers `wp_users.ID`
- ✅ Peut être NULL (aucun coach assigné)
- ✅ Doit pointer vers un utilisateur avec le rôle `jgf_coach`

### Validation recommandée

Pour éviter d'assigner un utilisateur non-coach :

```php
// Validation additionnelle (optionnelle)
if (!empty($_POST['coach_id'])) {
    $coach_user = get_userdata(intval($_POST['coach_id']));
    if ($coach_user && in_array('jgf_coach', $coach_user->roles)) {
        $member_data['coach_id'] = intval($_POST['coach_id']);
    } else {
        $member_data['coach_id'] = null;
        $message = 'Invalid coach selected.';
        $message_type = 'warning';
    }
}
```

---

## 📊 Résumé des corrections

| Problème | Solution | Status |
|----------|----------|--------|
| ucfirst() reçoit NULL | Ajout de `?? 'pending'` | ✅ Corrigé |
| Pas de champ coach dans edit form | Ajout du dropdown coach_id | ✅ Corrigé |
| coach_id non sauvegardé | Ajout dans $member_data | ✅ Corrigé |
| Coaches non chargés | get_users('jgf_coach') | ✅ Corrigé |

---

## 🚀 Prochaines étapes

1. **Rafraîchir WordPress** (CTRL + F5)
2. **Tester l'affichage des coaches** (vérifier "Status" sans erreur)
3. **Éditer un membre** et assigner un coach
4. **Vérifier en DB** que coach_id est bien sauvegardé
5. **Tester la déassignation** (sélectionner "No coach assigned")

---

## ✅ Conclusion

✅ **Coach Status** : Gestion correcte des valeurs NULL avec `?? 'pending'`

✅ **Member-Coach Link** : Interface complète pour assigner/modifier/supprimer l'assignation

✅ **Compatibilité PHP 8.1+** : Pas d'erreurs Deprecated

✅ **UX améliorée** : Dropdown clair avec option "No coach assigned"

🎉 **Plugin fonctionnel** : Les membres peuvent maintenant être liés à leurs coaches via l'interface admin !
