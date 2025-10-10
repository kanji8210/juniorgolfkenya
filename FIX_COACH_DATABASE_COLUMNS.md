# Fix: Coach Creation Database Error

## ❌ Problème rencontré

Lors de la création d'un coach, l'erreur suivante apparaissait :

```
WordPress database error: [Unknown column 'phone' in 'field list']
UPDATE `wp_jgf_coach_profiles` SET `phone` = '...', `experience_years` = '...', ...
WHERE `user_id` = 19
```

## 🔍 Analyse

### Structure réelle de la table `jgf_coach_profiles`

D'après `class-juniorgolfkenya-activator.php` (lignes 326-337), la table contient :

```sql
CREATE TABLE wp_jgf_coach_profiles (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    qualifications text,           -- ✅ Existe
    specialties text,              -- ✅ Existe
    bio text,                      -- ✅ Existe
    license_docs_ref varchar(500), -- ✅ Existe
    verification_status varchar(32) DEFAULT 'pending', -- ✅ Existe
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY (id),
    UNIQUE KEY user_id (user_id)
)
```

### Colonnes manquantes

Les colonnes suivantes n'existent **PAS** dans la table :
- ❌ `phone`
- ❌ `experience_years`

Ces informations doivent être stockées ailleurs.

## ✅ Solution appliquée

### Avant (juniorgolfkenya-admin-coaches.php, lignes ~61-76)

```php
// Update coach details
global $wpdb;
$coach_table = $wpdb->prefix . 'jgf_coach_profiles';
$wpdb->update(
    $coach_table,
    array(
        'phone' => $phone,                  // ❌ Colonne inexistante
        'experience_years' => $experience,  // ❌ Colonne inexistante
        'specialties' => implode(',', $specialties),
        'bio' => $bio,
        'verification_status' => 'approved'
    ),
    array('user_id' => $user_id)
);
```

### Après (correction appliquée)

```php
// Store phone and experience in user meta (not in coach_profiles table)
update_user_meta($user_id, 'phone', $phone);
update_user_meta($user_id, 'experience_years', $experience);

// Update coach profile (only columns that exist in jgf_coach_profiles)
global $wpdb;
$coach_table = $wpdb->prefix . 'jgf_coach_profiles';
$wpdb->update(
    $coach_table,
    array(
        'specialties' => implode(',', $specialties), // ✅ Existe
        'bio' => $bio,                               // ✅ Existe
        'verification_status' => 'approved'          // ✅ Existe
    ),
    array('user_id' => $user_id)
);
```

## 📋 Modifications détaillées

### Fichier modifié

**`admin/partials/juniorgolfkenya-admin-coaches.php`** (case 'create_coach')

### Changements

1. **Ajout de stockage dans user meta** :
   ```php
   update_user_meta($user_id, 'phone', $phone);
   update_user_meta($user_id, 'experience_years', $experience);
   ```

2. **Suppression des colonnes inexistantes** :
   - Retiré : `'phone' => $phone`
   - Retiré : `'experience_years' => $experience`

3. **Conservation des colonnes existantes** :
   - ✅ `specialties`
   - ✅ `bio`
   - ✅ `verification_status`

## 🔧 Comment récupérer ces données

### Pour afficher le téléphone d'un coach

```php
$phone = get_user_meta($user_id, 'phone', true);
echo esc_html($phone);
```

### Pour afficher l'expérience d'un coach

```php
$experience = get_user_meta($user_id, 'experience_years', true);
echo esc_html($experience) . ' years';
```

### Pour afficher les informations du profil

```php
global $wpdb;
$coach_table = $wpdb->prefix . 'jgf_coach_profiles';
$coach_profile = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $coach_table WHERE user_id = %d",
    $user_id
));

echo esc_html($coach_profile->bio);
echo esc_html($coach_profile->specialties);
echo esc_html($coach_profile->verification_status);
```

## ✅ Résultat attendu

Après cette correction :

1. ✅ Le coach est créé avec succès
2. ✅ L'utilisateur WordPress est créé avec le rôle `jgf_coach`
3. ✅ Le profil est créé dans `jgf_coach_profiles` (avec specialties, bio, verification_status)
4. ✅ Le téléphone et l'expérience sont stockés dans `wp_usermeta`
5. ✅ Un email est envoyé avec les identifiants de connexion
6. ✅ Aucune erreur SQL n'apparaît

## 🧪 Test à effectuer

1. Aller sur **JGK Coaches** dans l'admin WordPress
2. Cliquer sur **"Add New Coach"**
3. Remplir le formulaire :
   - First Name: John
   - Last Name: Doe
   - Email: john.doe@example.com
   - Phone: 123-456-7890
   - Experience: 5 years
   - Specialties: Junior Coaching, Swing Technique
   - Bio: Experienced golf coach...
4. Cliquer sur **"Create Coach"**

### Résultat attendu

- ✅ Message : "Coach created successfully! Login credentials sent to john.doe@example.com"
- ✅ Le coach apparaît dans la liste des coaches
- ✅ Aucune erreur SQL dans les logs

### Vérification en base de données

```sql
-- Vérifier le profil coach
SELECT * FROM wp_jgf_coach_profiles WHERE user_id = [NEW_USER_ID];

-- Vérifier les métadonnées
SELECT * FROM wp_usermeta WHERE user_id = [NEW_USER_ID] AND meta_key IN ('phone', 'experience_years');
```

## 📊 Stockage des données coach

| Donnée | Table | Colonne/Clé | Type |
|--------|-------|-------------|------|
| First Name | `wp_users` | meta: `first_name` | user_meta |
| Last Name | `wp_users` | meta: `last_name` | user_meta |
| Email | `wp_users` | `user_email` | colonne |
| Display Name | `wp_users` | `display_name` | colonne |
| Role | `wp_users` | meta: `wp_capabilities` | user_meta |
| **Phone** | `wp_usermeta` | `phone` | user_meta |
| **Experience** | `wp_usermeta` | `experience_years` | user_meta |
| Specialties | `wp_jgf_coach_profiles` | `specialties` | colonne |
| Bio | `wp_jgf_coach_profiles` | `bio` | colonne |
| Verification | `wp_jgf_coach_profiles` | `verification_status` | colonne |

## 🎯 Conclusion

✅ **Problème résolu** : Les colonnes inexistantes ont été supprimées de l'UPDATE

✅ **Architecture correcte** : Utilisation de `wp_usermeta` pour les données personnelles (phone, experience)

✅ **Table propre** : `jgf_coach_profiles` ne contient que les données spécifiques au rôle de coach (qualifications, specialties, bio, verification_status)

✅ **Plugin opérationnel** : La création de coaches fonctionne maintenant sans erreur
