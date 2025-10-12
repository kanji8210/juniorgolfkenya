# Nouveau Système de Paramètres - Guide Complet

## 📋 Vue d'ensemble

Le système de paramètres a été amélioré pour permettre une configuration complète sans modifier le code. Tous les paramètres sont maintenant gérés depuis l'interface d'administration WordPress.

## 🎯 Fonctionnalités Ajoutées

### 1. **Gestion des Données de Test**
   - Génération automatique de 10+ membres de test
   - Mode Production : suppression en un clic de toutes les données de test
   - Identification facile des données de test (préfixe `TEST-`)

### 2. **Restrictions d'Âge Configurables**
   - Âge minimum configurable (par défaut : 2 ans)
   - Âge maximum configurable (par défaut : 17 ans)
   - Plus besoin de modifier le code pour changer les limites

### 3. **Configuration des Prix**
   - Prix d'abonnement modifiable
   - Sélection de devise (KSH, USD, EUR, GBP, etc.)
   - Symbole monétaire personnalisable
   - Fréquence de paiement (mensuel, trimestriel, annuel)

### 4. **Paramètres d'Organisation**
   - Nom de l'organisation
   - Email de contact
   - Numéro de téléphone
   - Adresse physique
   - Fuseau horaire

## 📂 Nouveaux Fichiers Créés

### 1. `admin/partials/juniorgolfkenya-admin-settings-enhanced.php`
**Page de paramètres améliorée avec 4 onglets :**

#### Onglet General
- Informations de l'organisation
- Configuration de base

#### Onglet Membership
- Âge minimum (2-10 ans)
- Âge maximum (10-21 ans)
- Visualisation de la plage d'âge actuelle

#### Onglet Pricing
- Prix d'abonnement
- Code de devise (KSH, USD, EUR, etc.)
- Symbole monétaire
- Fréquence de paiement
- Aperçu en temps réel du format de prix

#### Onglet Test Data
- **Génération de données de test :**
  - Nombre personnalisable (1-50 membres)
  - Crée des comptes utilisateurs complets
  - Génère des données réalistes (noms kenyans, clubs de golf, etc.)
  - Mot de passe : `TestPassword123!`
  - Emails : `*@testjgk.local`
  
- **Mode Production :**
  - Suppression de TOUTES les données de test
  - Confirmation requise (taper "DELETE")
  - Cascade de suppression sécurisée
  - Statistiques de suppression affichées

### 2. `includes/class-juniorgolfkenya-settings-helper.php`
**Classe utilitaire pour accéder aux paramètres :**

```php
// Récupérer les restrictions d'âge
$restrictions = JuniorGolfKenya_Settings_Helper::get_age_restrictions();
// Retourne: array('min' => 2, 'max' => 17)

// Âge minimum seul
$min = JuniorGolfKenya_Settings_Helper::get_min_age(); // 2

// Âge maximum seul
$max = JuniorGolfKenya_Settings_Helper::get_max_age(); // 17

// Vérifier si un âge est valide
$is_valid = JuniorGolfKenya_Settings_Helper::is_valid_age(15); // true

// Prix formaté
$price = JuniorGolfKenya_Settings_Helper::get_formatted_price();
// Retourne: "KSh 5,000.00"

// Valider une date de naissance
$validation = JuniorGolfKenya_Settings_Helper::validate_birthdate('2015-05-20');
// Retourne: array('valid' => true, 'message' => '...')

// Attributs HTML5 pour les dates
$max_date = JuniorGolfKenya_Settings_Helper::get_birthdate_max();
$min_date = JuniorGolfKenya_Settings_Helper::get_birthdate_min();
```

### 3. `includes/class-juniorgolfkenya-test-data.php`
**Classe de gestion des données de test :**

```php
// Générer 10 membres de test
$result = JuniorGolfKenya_Test_Data::generate_test_members(10);
print_r($result);
/* Retourne:
array(
    'members' => [id1, id2, ...],
    'users' => [user_id1, user_id2, ...],
    'parents' => [parent_id1, parent_id2, ...],
    'errors' => []
)
*/

// Supprimer toutes les données de test
$stats = JuniorGolfKenya_Test_Data::delete_all_test_data();
print_r($stats);
/* Retourne:
array(
    'users_deleted' => 10,
    'members_deleted' => 10,
    'parents_deleted' => 10,
    'coach_assignments_deleted' => 0
)
*/

// Vérifier si des données de test existent
$has_test = JuniorGolfKenya_Test_Data::has_test_data(); // true/false

// Compter les données de test
$counts = JuniorGolfKenya_Test_Data::count_test_data();
// Retourne: array('users' => 10, 'members' => 10)
```

## 🔧 Options WordPress Créées

Le système utilise 3 nouvelles options WordPress :

### 1. `jgk_junior_settings`
```php
array(
    'min_age' => 2,      // Âge minimum (ans)
    'max_age' => 17      // Âge maximum (ans)
)
```

### 2. `jgk_pricing_settings`
```php
array(
    'subscription_price' => 5000,        // Prix
    'currency' => 'KSH',                 // Code devise
    'currency_symbol' => 'KSh',          // Symbole
    'payment_frequency' => 'yearly'      // Fréquence
)
```

### 3. `jgk_general_settings`
```php
array(
    'organization_name' => 'Junior Golf Kenya',
    'organization_email' => 'admin@example.com',
    'organization_phone' => '+254700000000',
    'organization_address' => '...',
    'timezone' => 'Africa/Nairobi'
)
```

## 📝 Migration du Code Existant

### Avant (Code Hardcodé)
```php
// Fichier: registration-form.php
if ($age < 2) {
    $errors[] = "Member must be at least 2 years old.";
}
if ($age >= 18) {
    $errors[] = "Member must be younger than 18 years.";
}

// HTML5 date constraints
max="<?php echo date('Y-m-d', strtotime('-2 years')); ?>"
min="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
```

### Après (Code Configurable)
```php
// Inclure le helper
require_once plugin_dir_path(__FILE__) . '../includes/class-juniorgolfkenya-settings-helper.php';

// Validation PHP
$validation = JuniorGolfKenya_Settings_Helper::validate_birthdate($birthdate);
if (!$validation['valid']) {
    $errors[] = $validation['message'];
}

// HTML5 date constraints
max="<?php echo JuniorGolfKenya_Settings_Helper::get_birthdate_max(); ?>"
min="<?php echo JuniorGolfKenya_Settings_Helper::get_birthdate_min(); ?>"
```

## 🔄 Fichiers à Mettre à Jour

Pour remplacer les valeurs hardcodées par les paramètres configurables :

### 1. `public/partials/juniorgolfkenya-registration-form.php`
**Lignes à modifier : 71-95**

```php
// ANCIEN CODE (lignes 71-95)
if ($age < 2) {
    $errors[] = "Member must be at least 2 years old. You entered: $age years.";
}
if ($age >= 18) {
    $errors[] = "This system is for juniors only (under 18 years). You entered: $age years.";
}

// NOUVEAU CODE
$validation = JuniorGolfKenya_Settings_Helper::validate_birthdate($birthdate);
if (!$validation['valid']) {
    $errors[] = $validation['message'];
}
```

**HTML5 constraints (lignes ~40-50)**
```php
<!-- ANCIEN -->
<input type="date" 
       max="<?php echo date('Y-m-d', strtotime('-2 years')); ?>"
       min="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
       required>

<!-- NOUVEAU -->
<input type="date" 
       max="<?php echo JuniorGolfKenya_Settings_Helper::get_birthdate_max(); ?>"
       min="<?php echo JuniorGolfKenya_Settings_Helper::get_birthdate_min(); ?>"
       required>
```

### 2. `admin/partials/juniorgolfkenya-admin-member-edit.php`
**Même type de modifications pour le formulaire d'édition admin**

### 3. `admin/partials/juniorgolfkenya-admin-members.php`
**Même type de modifications pour le formulaire de création admin**

### 4. JavaScript de validation (si applicable)
```javascript
// ANCIEN
const minAge = 2;
const maxAge = 17;

// NOUVEAU - Récupérer depuis data attributes
const minAge = parseInt(document.getElementById('birthdate').dataset.minAge);
const maxAge = parseInt(document.getElementById('birthdate').dataset.maxAge);
```

## 🚀 Utilisation

### Pour les Développeurs

#### 1. Générer des données de test
1. Aller dans **JGK Dashboard > Settings**
2. Cliquer sur l'onglet **Test Data**
3. Choisir le nombre de membres (1-50)
4. Cliquer sur **Generate Test Members**
5. Les membres de test apparaissent dans la liste avec `TEST-JGK-XXXX`

#### 2. Ajuster les restrictions d'âge
1. Aller dans **Settings > Membership**
2. Modifier **Minimum Age** (1-10 ans)
3. Modifier **Maximum Age** (10-21 ans)
4. Cliquer sur **Save Membership Settings**
5. Les nouvelles limites s'appliquent immédiatement

#### 3. Configurer les prix
1. Aller dans **Settings > Pricing**
2. Entrer le **Subscription Price**
3. Sélectionner la **Currency** (le symbole se remplit automatiquement)
4. Choisir la **Payment Frequency**
5. Vérifier l'aperçu en temps réel
6. Cliquer sur **Save Pricing Settings**

### Pour le Déploiement en Production

#### Étape 1 : Développement Local
- Générer des données de test pour développer/tester
- Ajuster les paramètres selon les besoins
- Tester toutes les fonctionnalités

#### Étape 2 : Avant le Déploiement
1. Aller dans **Settings > Test Data**
2. Vérifier le nombre de données de test
3. Cliquer sur **Go to Production Mode**
4. Taper `DELETE` dans le champ de confirmation
5. Cliquer sur **Delete All Test Data**
6. Vérifier que le badge disparaît de l'onglet

#### Étape 3 : Configuration Finale
1. Onglet **General** : Renseigner les vraies informations de l'organisation
2. Onglet **Membership** : Confirmer les limites d'âge finales
3. Onglet **Pricing** : Définir les prix de production
4. Sauvegarder tous les paramètres

## ⚠️ Avertissements

### Mode Production
- **IRRÉVERSIBLE** : La suppression des données de test ne peut pas être annulée
- **CASCADE** : Supprime users, members, parents, coach assignments
- **BACKUP** : Faire une sauvegarde de la base de données avant

### Restrictions d'âge
- Les modifications s'appliquent **immédiatement**
- Les membres existants ne sont **pas** affectés
- Seulement les **nouvelles inscriptions** sont validées avec les nouveaux paramètres

### Prix et Devise
- Pas de conversion automatique des prix existants
- Le symbole doit correspondre à la devise
- Vérifier l'aperçu avant de sauvegarder

## 🐛 Dépannage

### "You do not have permission"
**Problème :** Rôles utilisateurs incorrects dans la base de données
**Solution :** Exécuter le script SQL de correction des rôles (voir `FIX_FINAL.md`)

### Données de test non supprimées
**Vérifier :**
1. Vous avez tapé `DELETE` exactement (majuscules)
2. Les numéros d'adhésion commencent par `TEST-`
3. Le meta `jgk_test_data` est présent

**Solution de secours :**
```sql
-- Trouver les IDs de test
SELECT user_id FROM wp_usermeta WHERE meta_key = 'jgk_test_data';

-- Ou par numéro d'adhésion
SELECT id FROM wp_jgk_members WHERE membership_number LIKE 'TEST-%';
```

### Âges non validés correctement
**Vérifier :**
1. Les paramètres sont sauvegardés dans la base de données
2. Le helper est inclus dans les fichiers de validation
3. Les anciennes validations hardcodées sont remplacées

**Test SQL :**
```sql
SELECT * FROM wp_options WHERE option_name = 'jgk_junior_settings';
```

## 📊 Données de Test Générées

### Profils Types
Chaque membre de test comprend :
- **Nom** : Sélection aléatoire parmi 20 prénoms (James, Emma, Oliver, etc.)
- **Nom de famille** : Noms kenyans (Mwangi, Kamau, Ochieng, etc.)
- **Âge** : Aléatoire entre 5 et 17 ans
- **Date de naissance** : Calculée à partir de l'âge
- **Email** : `prenom.nom@testjgk.local`
- **Téléphone** : Format kenyan `+2547XXXXXXXX`
- **Club de golf** : Parmi 8 clubs kenyans réels
- **Handicap** : Aléatoire 0-36
- **Numéro d'adhésion** : `TEST-JGK-0001` à `TEST-JGK-XXXX`

### Parent/Tuteur
- **Nom** : Nom de famille de l'enfant
- **Prénom** : "Parent of [Prénom]"
- **Email** : `parent.[nom]@testjgk.local`
- **Téléphone** : Format kenyan
- **Relation** : Aléatoire (Father, Mother, Guardian)

## 🔗 Fichiers Liés

- `includes/class-juniorgolfkenya-test-data.php` - Générateur de données
- `includes/class-juniorgolfkenya-settings-helper.php` - Helper de paramètres
- `admin/partials/juniorgolfkenya-admin-settings-enhanced.php` - Interface admin
- `public/partials/juniorgolfkenya-registration-form.php` - À mettre à jour
- `admin/partials/juniorgolfkenya-admin-member-edit.php` - À mettre à jour
- `admin/partials/juniorgolfkenya-admin-members.php` - À mettre à jour

## 📅 Prochaines Étapes

1. ✅ **Créé** : Page de paramètres améliorée
2. ✅ **Créé** : Helper de paramètres
3. ✅ **Créé** : Générateur de données de test
4. ⏳ **À faire** : Mettre à jour les formulaires de validation
5. ⏳ **À faire** : Remplacer les valeurs hardcodées
6. ⏳ **À faire** : Tester tous les scénarios
7. ⏳ **À faire** : Corriger les rôles SQL (jgf_* → jgk_*)
8. ⏳ **À faire** : Réactiver les vérifications de permissions

## 💡 Avantages

### Pour le Développement
- ✅ Génération rapide de données de test réalistes
- ✅ Nettoyage facile avant déploiement
- ✅ Pas besoin de créer manuellement des membres

### Pour l'Administration
- ✅ Configuration sans modifier le code
- ✅ Flexibilité pour différentes organisations
- ✅ Interface intuitive avec aperçus en temps réel

### Pour la Maintenance
- ✅ Code centralisé et réutilisable
- ✅ Validation cohérente dans toute l'application
- ✅ Facilite les mises à jour futures

---

**Version:** 1.0.0  
**Date:** 2024  
**Auteur:** Junior Golf Kenya Development Team
