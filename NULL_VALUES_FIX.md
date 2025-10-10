# Correction des valeurs NULL - Formulaire d'édition

## Problème identifié

**Erreur PHP Deprecated** :
```
Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated 
in C:\xampp\htdocs\wordpress\wp-includes\formatting.php on line 4724
```

**Cause** : Les fonctions `esc_attr()`, `esc_textarea()`, et `esc_html()` ne gèrent pas les valeurs NULL en PHP 8.1+.

**Localisation** : Formulaire d'édition des membres (`juniorgolfkenya-admin-member-edit.php`)

## Solutions appliquées

### 1. Utilisation de l'opérateur Null Coalescing (`??`)

L'opérateur `??` retourne une chaîne vide si la valeur est NULL, évitant ainsi l'erreur.

**Avant** :
```php
<input type="tel" name="phone" value="<?php echo esc_attr($edit_member->phone); ?>">
<textarea name="medical_conditions"><?php echo esc_textarea($edit_member->medical_conditions); ?></textarea>
```

**Après** :
```php
<input type="tel" name="phone" value="<?php echo esc_attr($edit_member->phone ?? ''); ?>">
<textarea name="medical_conditions"><?php echo esc_textarea($edit_member->medical_conditions ?? ''); ?></textarea>
```

### 2. Champs corrigés dans le formulaire d'édition

Tous les champs suivants utilisent maintenant `?? ''` pour gérer les valeurs NULL :

#### Informations personnelles
- ✅ `$edit_member->phone ?? ''`
- ✅ `$edit_member->handicap ?? ''`
- ✅ `$edit_member->date_of_birth ?? ''`
- ✅ `$edit_member->gender ?? ''`
- ✅ `$edit_member->user_email ?? ''`
- ✅ `$edit_member->display_name ?? ''`

#### Détails d'adhésion
- ✅ `$edit_member->club_affiliation ?? ''`
- ✅ `$edit_member->medical_conditions ?? ''`

#### Contact d'urgence
- ✅ `$edit_member->emergency_contact_name ?? ''`
- ✅ `$edit_member->emergency_contact_phone ?? ''`

#### Nouvelles informations ajoutées
- ✅ `$edit_member->address ?? ''`
- ✅ `$edit_member->biography ?? ''`
- ✅ `$edit_member->consent_photography ?? 'no'`
- ✅ `$edit_member->parental_consent ?? 0`

### 3. Nouveaux champs ajoutés au formulaire

Pour afficher **TOUTES** les colonnes de la base de données :

#### Section "Additional Information" (NOUVEAU)

**Address** (Adresse)
```php
<textarea id="address" name="address" rows="3">
    <?php echo esc_textarea($edit_member->address ?? ''); ?>
</textarea>
```

**Biography** (Biographie)
```php
<textarea id="biography" name="biography" rows="3" placeholder="Tell us about the member...">
    <?php echo esc_textarea($edit_member->biography ?? ''); ?>
</textarea>
```

**Consent to Photography** (Consentement photo)
```php
<input type="checkbox" name="consent_photography" value="yes" 
    <?php checked($edit_member->consent_photography ?? 'no', 'yes'); ?>>
```

**Parental Consent** (Consentement parental - pour mineurs)
```php
<input type="checkbox" name="parental_consent" value="1" 
    <?php checked($edit_member->parental_consent ?? 0, 1); ?>>
```

### 4. Traitement du formulaire mis à jour

Le traitement du formulaire (`case 'edit_member'`) inclut maintenant tous les nouveaux champs :

```php
$member_data = array(
    // ... champs existants ...
    'address' => sanitize_textarea_field($_POST['address']),
    'biography' => sanitize_textarea_field($_POST['biography']),
    'consent_photography' => isset($_POST['consent_photography']) ? 'yes' : 'no',
    'parental_consent' => isset($_POST['parental_consent']) ? 1 : 0
);
```

**Note** : `handicap` gère maintenant aussi les valeurs vides :
```php
'handicap' => !empty($_POST['handicap']) ? floatval($_POST['handicap']) : null
```

## Structure complète de la table jgk_members

### Colonnes affichées dans le formulaire d'édition

| Colonne | Type | Affiché | Section |
|---------|------|---------|---------|
| `id` | mediumint(9) | Hidden | Form field |
| `user_id` | bigint(20) | ❌ Non éditable | Système |
| `membership_number` | varchar(50) | ❌ Non éditable | Généré auto |
| `membership_type` | varchar(50) | ✅ Oui | Membership Details |
| `status` | varchar(32) | ✅ Oui | Membership Details |
| `coach_id` | bigint(20) | ❌ Non (autre interface) | Assign Coach |
| `date_joined` | datetime | ❌ Non éditable | Auto |
| `date_expires` | datetime | ❌ Non (calculé) | Auto |
| `expiry_date` | date | ❌ Non (calculé) | Auto |
| `join_date` | date | ❌ Non éditable | Auto |
| `date_of_birth` | date | ✅ Oui | Personal Information |
| `gender` | varchar(20) | ✅ Oui | Personal Information |
| `first_name` | varchar(100) | ✅ Oui | Personal Information |
| `last_name` | varchar(100) | ✅ Oui | Personal Information |
| `phone` | varchar(20) | ✅ Oui | Personal Information |
| `email` | varchar(100) | ✅ Oui (via user table) | Personal Information |
| `address` | text | ✅ Oui | Additional Information |
| `biography` | text | ✅ Oui | Additional Information |
| `parents_guardians` | text | ❌ Non (deprecated) | Remplacé par table dédiée |
| `profile_image_url` | varchar(500) | ❌ Non (deprecated) | Remplacé par profile_image_id |
| `profile_image_id` | bigint(20) | ✅ Oui (upload) | Profile Photo |
| `consent_photography` | varchar(16) | ✅ Oui | Additional Information |
| `emergency_contact_name` | varchar(100) | ✅ Oui | Emergency Contact |
| `emergency_contact_phone` | varchar(20) | ✅ Oui | Emergency Contact |
| `club_affiliation` | varchar(100) | ✅ Oui | Membership Details |
| `handicap` | varchar(10) | ✅ Oui | Personal Information |
| `medical_conditions` | text | ✅ Oui | Membership Details |
| `parental_consent` | tinyint(1) | ✅ Oui | Additional Information |
| `created_at` | datetime | ❌ Non éditable | Auto |
| `updated_at` | datetime | ❌ Non éditable | Auto (ON UPDATE) |

**Total** : 30 colonnes
**Éditables** : 18 champs
**Système/Auto** : 12 champs

## Tests à effectuer

### Test 1 : Vérifier l'absence d'erreur PHP Deprecated
1. ✓ Aller sur JGK Members
2. ✓ Cliquer sur "Edit Member" pour un membre avec des champs NULL
3. ✓ Vérifier qu'aucune erreur PHP n'apparaît
4. ✓ Spécialement vérifier le champ `medical_conditions` qui était NULL

### Test 2 : Nouveaux champs "Additional Information"
1. ✓ Vérifier que la section "Additional Information" s'affiche
2. ✓ Remplir le champ "Address"
3. ✓ Remplir le champ "Biography"
4. ✓ Cocher "Consent to Photography"
5. ✓ Cocher "Parental Consent"
6. ✓ Soumettre et vérifier la sauvegarde

### Test 3 : Valeurs NULL préservées
1. ✓ Éditer un membre avec des champs vides
2. ✓ Laisser des champs vides (ne rien saisir)
3. ✓ Soumettre le formulaire
4. ✓ Vérifier que les champs restent vides (NULL) dans la DB

### Test 4 : Tous les champs de la DB affichés
1. ✓ Comparer la liste des champs du formulaire avec la structure de la table
2. ✓ Vérifier que tous les champs éditables sont présents
3. ✓ S'assurer que les champs système ne sont pas éditables

## Améliorations apportées

### Avant
- ❌ Erreur PHP Deprecated sur les champs NULL
- ❌ 4 colonnes manquantes dans le formulaire
- ❌ Handicap non géré si vide

### Après
- ✅ Aucune erreur PHP avec valeurs NULL
- ✅ Tous les champs éditables de la DB affichés
- ✅ Gestion correcte des checkboxes
- ✅ Handicap peut être NULL ou vide
- ✅ Documentation complète des 30 colonnes

## Compatibilité PHP

**PHP 7.4+** : L'opérateur `??` (Null Coalescing) est disponible depuis PHP 7.0.

**PHP 8.1+** : Les fonctions `esc_attr()` et autres ne tolèrent plus les valeurs NULL sans avertissement.

**Solution** : Utilisation de `?? ''` garantit la compatibilité et évite les deprecated warnings.

## Fichiers modifiés

1. **admin/partials/juniorgolfkenya-admin-member-edit.php**
   - Ajout de `?? ''` sur 13 champs
   - Ajout section "Additional Information" (4 nouveaux champs)
   - Total : 18 champs éditables

2. **admin/partials/juniorgolfkenya-admin-members.php**
   - Case 'edit_member' : Ajout de 4 champs dans `$member_data`
   - Gestion de `handicap` avec NULL si vide
   - Gestion des checkboxes (consent_photography, parental_consent)

## Résumé

✅ **Problème résolu** : L'erreur PHP Deprecated sur `htmlspecialchars()` est éliminée.

✅ **Affichage complet** : Tous les 18 champs éditables de la table sont maintenant dans le formulaire.

✅ **Données complètes** : Les administrateurs peuvent désormais voir et éditer TOUTES les informations des membres.

✅ **Compatibilité** : Le code fonctionne avec PHP 7.4+ et PHP 8.1+ sans warnings.
