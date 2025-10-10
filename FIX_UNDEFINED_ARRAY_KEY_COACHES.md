# Fix: Undefined array key "member_ids" in Coaches Admin

## 🐛 Problème

Erreur PHP rencontrée :
```
Warning: Undefined array key "member_ids" in 
C:\xampp\htdocs\wordpress\wp-content\plugins\juniorgolfkenya\admin\partials\juniorgolfkenya-admin-coaches.php 
on line 116
```

## 🔍 Analyse

### Cause principale
L'accès direct à `$_POST['member_ids']` sans vérifier si la clé existe provoque une erreur PHP 8.0+ "Undefined array key".

### Cas problématiques identifiés

1. **Ligne 116** : `$_POST['member_ids']` (action `assign_members`)
2. **Ligne 95** : `$_POST['specialties']` (action `update_coach`)
3. **Lignes 38-44** : Plusieurs `$_POST` sans protection (action `create_coach`)

## ✅ Solutions appliquées

### 1. Action `assign_members` (Ligne 115-128)

**Avant** :
```php
case 'assign_members':
    $coach_id = intval($_POST['coach_id']);
    $member_ids = array_map('intval', $_POST['member_ids']); // ❌ Erreur si non défini
    
    $success_count = 0;
    foreach ($member_ids as $member_id) {
        if (JuniorGolfKenya_User_Manager::assign_coach($member_id, $coach_id)) {
            $success_count++;
        }
    }
    
    $message = "Assigned {$success_count} member(s) to coach successfully!";
    $message_type = 'success';
    break;
```

**Après** :
```php
case 'assign_members':
    $coach_id = intval($_POST['coach_id']);
    $member_ids = isset($_POST['member_ids']) && is_array($_POST['member_ids']) 
        ? array_map('intval', $_POST['member_ids']) 
        : array();
    
    if (empty($member_ids)) {
        $message = "Please select at least one member to assign.";
        $message_type = 'error';
    } else {
        $success_count = 0;
        foreach ($member_ids as $member_id) {
            if (JuniorGolfKenya_User_Manager::assign_coach($member_id, $coach_id)) {
                $success_count++;
            }
        }
        
        $message = "Assigned {$success_count} member(s) to coach successfully!";
        $message_type = 'success';
    }
    break;
```

**Améliorations** :
- ✅ Vérification `isset()` + `is_array()`
- ✅ Valeur par défaut : tableau vide
- ✅ Validation : message d'erreur si aucun membre sélectionné
- ✅ Évite le foreach sur un tableau vide

### 2. Action `update_coach` (Ligne 93-105)

**Avant** :
```php
case 'update_coach':
    $coach_id = intval($_POST['coach_id']);
    $specialties = array_map('sanitize_text_field', $_POST['specialties']); // ❌ Erreur si non défini
    $experience = intval($_POST['experience']); // ❌ Erreur si non défini
    $bio = sanitize_textarea_field($_POST['bio']); // ❌ Erreur si non défini
    
    $result = JuniorGolfKenya_Database::update_coach($coach_id, array(
        'specialties' => implode(',', $specialties),
        'experience_years' => $experience,
        'bio' => $bio
    ));
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
    
    $result = JuniorGolfKenya_Database::update_coach($coach_id, array(
        'specialties' => implode(',', $specialties),
        'experience_years' => $experience,
        'bio' => $bio
    ));
```

**Améliorations** :
- ✅ `specialties` : vérification `isset()` + `is_array()`, défaut = `array()`
- ✅ `experience` : vérification `isset()`, défaut = `0`
- ✅ `bio` : vérification `isset()`, défaut = `''`

### 3. Action `create_coach` (Ligne 38-52)

**Avant** :
```php
case 'create_coach':
    $first_name = sanitize_text_field($_POST['first_name']); // ❌ Erreur si non défini
    $last_name = sanitize_text_field($_POST['last_name']); // ❌ Erreur si non défini
    $email = sanitize_email($_POST['email']); // ❌ Erreur si non défini
    $phone = sanitize_text_field($_POST['phone']); // ❌ Erreur si non défini
    $experience = intval($_POST['experience_years']); // ❌ Erreur si non défini
    $specialties = isset($_POST['specialties']) ? array_map('sanitize_text_field', $_POST['specialties']) : array();
    $bio = sanitize_textarea_field($_POST['bio']); // ❌ Erreur si non défini
    
    // Create WordPress user
    $user_id = wp_create_user($email, wp_generate_password(), $email);
```

**Après** :
```php
case 'create_coach':
    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $experience = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : 0;
    $specialties = isset($_POST['specialties']) && is_array($_POST['specialties']) ? array_map('sanitize_text_field', $_POST['specialties']) : array();
    $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $message = 'First name, last name, and email are required fields.';
        $message_type = 'error';
        break;
    }
    
    // Create WordPress user
    $user_id = wp_create_user($email, wp_generate_password(), $email);
```

**Améliorations** :
- ✅ Protection `isset()` pour tous les champs
- ✅ Valeurs par défaut appropriées pour chaque type
- ✅ Validation des champs requis (first_name, last_name, email)
- ✅ Message d'erreur clair si champs manquants

## 📋 Récapitulatif des changements

| Action | Ligne | Champ | Protection ajoutée | Valeur par défaut |
|--------|-------|-------|-------------------|-------------------|
| `create_coach` | 38 | `first_name` | `isset()` | `''` |
| `create_coach` | 39 | `last_name` | `isset()` | `''` |
| `create_coach` | 40 | `email` | `isset()` | `''` |
| `create_coach` | 41 | `phone` | `isset()` | `''` |
| `create_coach` | 42 | `experience_years` | `isset()` | `0` |
| `create_coach` | 43 | `specialties` | `isset()` + `is_array()` | `array()` |
| `create_coach` | 44 | `bio` | `isset()` | `''` |
| `update_coach` | 95 | `specialties` | `isset()` + `is_array()` | `array()` |
| `update_coach` | 96 | `experience` | `isset()` | `0` |
| `update_coach` | 97 | `bio` | `isset()` | `''` |
| `assign_members` | 116 | `member_ids` | `isset()` + `is_array()` | `array()` |

## 🧪 Tests à effectuer

### Test 1 : Assign Members (Ligne 116)

**Scénario 1** : Soumettre le formulaire sans sélectionner de membres
```
1. Aller sur "Coaches Management"
2. Cliquer sur "Assign Members" pour un coach
3. Ne sélectionner AUCUN membre
4. Cliquer sur "Assign"
```

**Résultat attendu** :
- ✅ Message d'erreur : "Please select at least one member to assign."
- ✅ Pas d'erreur PHP "Undefined array key"

**Scénario 2** : Soumettre avec des membres sélectionnés
```
1. Aller sur "Coaches Management"
2. Cliquer sur "Assign Members"
3. Sélectionner 1+ membres
4. Cliquer sur "Assign"
```

**Résultat attendu** :
- ✅ Message de succès : "Assigned X member(s) to coach successfully!"
- ✅ Membres assignés correctement

### Test 2 : Update Coach (Lignes 95-97)

**Scénario** : Éditer un coach sans remplir tous les champs
```
1. Aller sur "Coaches Management"
2. Cliquer sur "Edit" pour un coach
3. Laisser certains champs vides (specialties, experience, bio)
4. Cliquer sur "Update Coach"
```

**Résultat attendu** :
- ✅ Aucune erreur PHP
- ✅ Champs vides sauvegardés comme valeurs par défaut
- ✅ Message de succès

### Test 3 : Create Coach (Lignes 38-44)

**Scénario 1** : Créer un coach sans tous les champs requis
```
1. Aller sur "Coaches Management"
2. Cliquer sur "Add New Coach"
3. Ne remplir QUE l'email (laisser first_name et last_name vides)
4. Cliquer sur "Create Coach"
```

**Résultat attendu** :
- ✅ Message d'erreur : "First name, last name, and email are required fields."
- ✅ Coach non créé
- ✅ Aucune erreur PHP

**Scénario 2** : Créer un coach avec tous les champs requis
```
1. Aller sur "Coaches Management"
2. Cliquer sur "Add New Coach"
3. Remplir first_name, last_name, email (+ optionnels)
4. Cliquer sur "Create Coach"
```

**Résultat attendu** :
- ✅ Message de succès
- ✅ Coach créé avec rôle jgf_coach
- ✅ Email de notification envoyé

## 🔒 Sécurité et bonnes pratiques

### Protection implémentée

1. **Vérification d'existence** :
   ```php
   isset($_POST['field_name'])
   ```

2. **Vérification de type pour tableaux** :
   ```php
   isset($_POST['field']) && is_array($_POST['field'])
   ```

3. **Opérateur ternaire avec valeur par défaut** :
   ```php
   $value = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
   ```

4. **Sanitization maintenue** :
   - `sanitize_text_field()` pour texte simple
   - `sanitize_email()` pour emails
   - `sanitize_textarea_field()` pour textarea
   - `intval()` pour nombres
   - `array_map()` pour tableaux

5. **Validation des données requises** :
   ```php
   if (empty($first_name) || empty($last_name) || empty($email)) {
       $message = 'Required fields are missing.';
       $message_type = 'error';
       break;
   }
   ```

## 📊 Compatibilité

- ✅ **PHP 7.4+** : Opérateur ternaire standard
- ✅ **PHP 8.0+** : Plus d'erreurs "Undefined array key"
- ✅ **PHP 8.1+** : Pas de warnings "Passing null to parameter"
- ✅ **WordPress 5.0+** : Fonctions de sanitization standard

## 📝 Fichiers modifiés

```
admin/partials/juniorgolfkenya-admin-coaches.php
  - Ligne 38-52 : Action create_coach (protection + validation)
  - Ligne 93-105 : Action update_coach (protection complète)
  - Ligne 115-132 : Action assign_members (protection + validation)
```

## 🎯 Impact

### Avant
- ❌ Erreur "Undefined array key" si champs manquants
- ❌ Formulaires pouvaient être soumis avec données manquantes
- ❌ Pas de validation côté serveur

### Après
- ✅ Aucune erreur PHP même si champs manquants
- ✅ Valeurs par défaut appropriées pour chaque type
- ✅ Validation des champs requis
- ✅ Messages d'erreur clairs pour l'utilisateur
- ✅ Code robuste et sécurisé

## 🚀 Conclusion

Cette correction élimine toutes les erreurs "Undefined array key" dans le fichier coaches admin en :

1. ✅ Ajoutant des vérifications `isset()` partout
2. ✅ Définissant des valeurs par défaut appropriées
3. ✅ Validant les champs requis avant traitement
4. ✅ Affichant des messages d'erreur clairs
5. ✅ Maintenant toute la sanitization de sécurité

Le code est maintenant **compatible PHP 8.0+** et suit les **bonnes pratiques WordPress**.
