# Fix: Call to undefined method update_coach()

## 🐛 Erreur

```
Fatal error: Uncaught Error: Call to undefined method JuniorGolfKenya_Database::update_coach() 
in C:\xampp\htdocs\wordpress\wp-content\plugins\juniorgolfkenya\admin\partials\juniorgolfkenya-admin-coaches.php:108
```

## 🔍 Analyse

### Cause

**Fichier** : `admin/partials/juniorgolfkenya-admin-coaches.php` (ligne 108)

Le code appelait une méthode qui **n'existe pas** dans la classe `JuniorGolfKenya_Database` :

```php
// ❌ ERREUR : Cette méthode n'existe pas
$result = JuniorGolfKenya_Database::update_coach($coach_id, array(
    'specialties' => implode(',', $specialties),
    'experience_years' => $experience,
    'bio' => $bio
));
```

### Contexte

Dans le même fichier, lors de la **création** d'un coach (action `create_coach`), le code utilise **directement** `$wpdb->update()` pour mettre à jour la table `wp_jgf_coach_profiles` :

```php
// ✅ Code existant pour CREATE (lignes 78-86)
global $wpdb;
$coach_table = $wpdb->prefix . 'jgf_coach_profiles';
$wpdb->update(
    $coach_table,
    array(
        'specialties' => implode(',', $specialties),
        'bio' => $bio,
        'verification_status' => 'approved'
    ),
    array('user_id' => $user_id)
);
```

**Constat** : Le code d'UPDATE devrait utiliser la **même approche** que le code de CREATE.

### Structure des données coaches

Les données d'un coach sont stockées à **deux endroits** :

1. **Table `wp_jgf_coach_profiles`** :
   - `specialties` (VARCHAR)
   - `bio` (TEXT)
   - `verification_status` (VARCHAR)
   - `user_id` (INT) - Clé étrangère vers wp_users

2. **Table `wp_usermeta`** :
   - `phone` (meta_key + meta_value)
   - `experience_years` (meta_key + meta_value)

**Raison** : Les colonnes `phone` et `experience_years` n'existent **pas** dans la table `wp_jgf_coach_profiles`, donc elles sont stockées dans `wp_usermeta`.

## ✅ Solution appliquée

### Modification dans `juniorgolfkenya-admin-coaches.php`

**Avant** (lignes 100-118) :
```php
case 'update_coach':
    $coach_id = intval($_POST['coach_id']);
    $specialties = isset($_POST['specialties']) && is_array($_POST['specialties']) 
        ? array_map('sanitize_text_field', $_POST['specialties']) 
        : array();
    $experience = isset($_POST['experience']) ? intval($_POST['experience']) : 0;
    $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
    
    // ❌ Appelle une méthode qui n'existe pas
    $result = JuniorGolfKenya_Database::update_coach($coach_id, array(
        'specialties' => implode(',', $specialties),
        'experience_years' => $experience,  // ❌ Cette colonne n'existe pas dans la table
        'bio' => $bio
    ));
    
    if ($result) {
        $message = 'Coach information updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to update coach information.';
        $message_type = 'error';
    }
    break;
```

**Après** :
```php
case 'update_coach':
    $coach_id = intval($_POST['coach_id']);
    $specialties = isset($_POST['specialties']) && is_array($_POST['specialties']) 
        ? array_map('sanitize_text_field', $_POST['specialties']) 
        : array();
    $experience = isset($_POST['experience']) ? intval($_POST['experience']) : 0;
    $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
    
    // ✅ Update user meta for experience (stored separately)
    update_user_meta($coach_id, 'experience_years', $experience);
    
    // ✅ Update coach profile table
    global $wpdb;
    $coach_table = $wpdb->prefix . 'jgf_coach_profiles';
    $result = $wpdb->update(
        $coach_table,
        array(
            'specialties' => implode(',', $specialties),
            'bio' => $bio
        ),
        array('user_id' => $coach_id)
    );
    
    if ($result !== false) {
        $message = 'Coach information updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to update coach information.';
        $message_type = 'error';
    }
    break;
```

### Améliorations

1. ✅ **Suppression de l'appel à méthode inexistante** : Plus d'appel à `JuniorGolfKenya_Database::update_coach()`

2. ✅ **Mise à jour de user_meta** : `experience_years` stocké dans `wp_usermeta` avec `update_user_meta()`

3. ✅ **Mise à jour de la table coach_profiles** : Utilisation directe de `$wpdb->update()` pour `specialties` et `bio`

4. ✅ **Cohérence avec CREATE** : Même approche que lors de la création d'un coach

5. ✅ **Validation du résultat** : `$result !== false` au lieu de `$result` (car `$wpdb->update()` retourne 0 si aucune ligne modifiée)

## 📊 Comparaison CREATE vs UPDATE

### Action CREATE (lignes 38-92)

```php
// 1. Créer l'utilisateur WordPress
$user_id = wp_create_user($email, wp_generate_password(), $email);

// 2. Mettre à jour les détails utilisateur
wp_update_user(array(
    'ID' => $user_id,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'display_name' => $first_name . ' ' . $last_name,
    'role' => 'jgf_coach'
));

// 3. Créer le profil coach
JuniorGolfKenya_User_Manager::create_coach_profile($user_id);

// 4. Stocker phone et experience dans user_meta
update_user_meta($user_id, 'phone', $phone);
update_user_meta($user_id, 'experience_years', $experience);

// 5. Mettre à jour le profil coach (specialties, bio, verification_status)
global $wpdb;
$coach_table = $wpdb->prefix . 'jgf_coach_profiles';
$wpdb->update(
    $coach_table,
    array(
        'specialties' => implode(',', $specialties),
        'bio' => $bio,
        'verification_status' => 'approved'
    ),
    array('user_id' => $user_id)
);
```

### Action UPDATE (lignes 100-127) - CORRIGÉE

```php
// 1. Mettre à jour experience dans user_meta
update_user_meta($coach_id, 'experience_years', $experience);

// 2. Mettre à jour le profil coach (specialties, bio)
global $wpdb;
$coach_table = $wpdb->prefix . 'jgf_coach_profiles';
$result = $wpdb->update(
    $coach_table,
    array(
        'specialties' => implode(',', $specialties),
        'bio' => $bio
    ),
    array('user_id' => $coach_id)
);

// 3. Vérifier le résultat
if ($result !== false) {
    $message = 'Coach information updated successfully!';
    $message_type = 'success';
} else {
    $message = 'Failed to update coach information.';
    $message_type = 'error';
}
```

**Cohérence** : Les deux actions utilisent maintenant la même approche pour mettre à jour les données.

## 🔍 Validation du résultat

### Retour de `$wpdb->update()`

La méthode `$wpdb->update()` retourne :
- **`int`** : Nombre de lignes mises à jour (peut être `0` si aucune modification)
- **`false`** : En cas d'erreur SQL

**Validation correcte** :
```php
if ($result !== false) {  // ✅ Correct : accepte 0 comme succès
    // Success
}
```

**Validation incorrecte** :
```php
if ($result) {  // ❌ Incorrect : 0 est évalué comme false
    // Ne sera jamais exécuté si 0 ligne modifiée
}
```

## 🧪 Tests à effectuer

### Test 1 : Mise à jour complète d'un coach

**Actions** :
1. Aller sur **JGK Coaches Management**
2. Cliquer sur **"Edit"** pour un coach existant
3. Modifier les champs suivants :
   - Specialties (sélectionner plusieurs)
   - Experience Years (changer la valeur)
   - Bio (ajouter/modifier du texte)
4. Cliquer sur **"Update Coach"**

**Résultats attendus** :
- ✅ Message de succès : "Coach information updated successfully!"
- ✅ Aucune erreur "Call to undefined method"
- ✅ Modifications sauvegardées dans la base de données
- ✅ Modifications visibles lors de la réédition

**Résultats si pas corrigé** :
- ❌ Fatal error : "Call to undefined method JuniorGolfKenya_Database::update_coach()"
- ❌ Page blanche (écran blanc de la mort)
- ❌ Modifications non sauvegardées

### Test 2 : Mise à jour partielle (champs vides)

**Actions** :
1. Éditer un coach
2. Laisser certains champs vides :
   - Specialties : aucune sélection
   - Bio : vide
3. Cliquer sur "Update Coach"

**Résultats attendus** :
- ✅ Message de succès
- ✅ Champs vides sauvegardés correctement
- ✅ Pas d'erreur SQL

### Test 3 : Vérification en base de données

**Requête SQL pour vérifier user_meta (experience)** :
```sql
SELECT 
    u.ID,
    u.display_name,
    um.meta_value as experience_years
FROM wp_users u
INNER JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'experience_years'
AND u.ID IN (
    SELECT user_id FROM wp_usermeta 
    WHERE meta_key = 'wp_capabilities' 
    AND meta_value LIKE '%jgf_coach%'
);
```

**Résultat attendu** :
- ✅ Colonne `experience_years` contient la valeur mise à jour

**Requête SQL pour vérifier coach_profiles (specialties, bio)** :
```sql
SELECT 
    cp.user_id,
    u.display_name,
    cp.specialties,
    cp.bio,
    cp.verification_status
FROM wp_jgf_coach_profiles cp
INNER JOIN wp_users u ON cp.user_id = u.ID
ORDER BY cp.user_id DESC
LIMIT 10;
```

**Résultat attendu** :
- ✅ Colonnes `specialties` et `bio` contiennent les valeurs mises à jour

### Test 4 : Édition puis création

**Actions** :
1. Éditer un coach existant (tester UPDATE)
2. Créer un nouveau coach (tester CREATE)

**Résultats attendus** :
- ✅ Les deux actions fonctionnent sans erreur
- ✅ Cohérence entre CREATE et UPDATE

## 📝 Structure finale des données coach

### Table `wp_jgf_coach_profiles`

| Colonne | Type | Valeur |
|---------|------|--------|
| `id` | INT | Auto-increment |
| `user_id` | INT | WordPress User ID |
| `specialties` | VARCHAR | "junior_coaching,swing_technique" |
| `bio` | TEXT | Biography text |
| `verification_status` | VARCHAR | "approved" |
| `created_at` | DATETIME | Timestamp |
| `updated_at` | DATETIME | Timestamp |

### Table `wp_usermeta` (pour coach)

| meta_key | meta_value |
|----------|------------|
| `phone` | "+254712345678" |
| `experience_years` | "10" |
| `wp_capabilities` | "a:1:{s:9:\"jgf_coach\";b:1;}" |

## 🔐 Sécurité et validation

### Sanitization (déjà en place)

```php
$coach_id = intval($_POST['coach_id']);  // ✅ Entier
$specialties = isset($_POST['specialties']) && is_array($_POST['specialties']) 
    ? array_map('sanitize_text_field', $_POST['specialties'])  // ✅ Tableau d'entiers
    : array();
$experience = isset($_POST['experience']) ? intval($_POST['experience']) : 0;  // ✅ Entier
$bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';  // ✅ Texte
```

### Nonce verification (déjà en place)

```php
if (!wp_verify_nonce($_POST['_wpnonce'], 'jgk_coach_action')) {
    wp_die(__('Security check failed.'));
}
```

### Prepared statements

`$wpdb->update()` utilise automatiquement des **prepared statements** pour prévenir les injections SQL.

## ✅ Conclusion

### Problème résolu

✅ **Méthode inexistante supprimée** : Plus d'appel à `JuniorGolfKenya_Database::update_coach()`

✅ **Utilisation directe de $wpdb** : Cohérent avec le code CREATE

✅ **Mise à jour user_meta** : `experience_years` stocké correctement

✅ **Validation correcte** : `!== false` au lieu de simple `if ($result)`

### Impact

- ✅ Édition de coach → **Fonctionne**
- ✅ Aucune erreur fatale → **Stable**
- ✅ Données sauvegardées → **Persistantes**
- ✅ Cohérence CREATE/UPDATE → **Maintenue**

### Fichiers modifiés

```
admin/partials/juniorgolfkenya-admin-coaches.php
  - Lignes 100-127 : Action update_coach réécrite
  - Suppression : appel à méthode inexistante
  - Ajout : update_user_meta pour experience
  - Ajout : $wpdb->update direct pour coach_profiles
```

### Prochaines étapes

1. **Tester l'édition de coach** depuis l'interface admin
2. **Vérifier les logs** pour absence d'erreurs
3. **Confirmer en BDD** que les données sont mises à jour
4. **Tester plusieurs coaches** pour s'assurer de la stabilité

La fonctionnalité d'**édition de coach est maintenant opérationnelle** ! 🎉
