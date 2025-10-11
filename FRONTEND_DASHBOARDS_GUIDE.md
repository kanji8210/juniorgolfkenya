# Frontend Dashboards Guide

## Vue d'ensemble

Ce guide explique comment utiliser les dashboards frontend pour les **coaches** et les **membres** du Junior Golf Kenya plugin.

## 📋 Contenu

- [Installation](#installation)
- [Shortcodes disponibles](#shortcodes-disponibles)
- [Coach Dashboard](#coach-dashboard)
- [Member Dashboard](#member-dashboard)
- [Configuration](#configuration)
- [Personnalisation](#personnalisation)

---

## Installation

Les dashboards sont automatiquement activés avec le plugin. Aucune configuration supplémentaire n'est nécessaire.

### Classes créées

1. **`JuniorGolfKenya_Coach_Dashboard`** (includes/class-juniorgolfkenya-coach-dashboard.php)
2. **`JuniorGolfKenya_Member_Dashboard`** (includes/class-juniorgolfkenya-member-dashboard.php)

### Fichiers de vue

1. **Coach Dashboard** : public/partials/juniorgolfkenya-coach-dashboard.php
2. **Member Dashboard** : public/partials/juniorgolfkenya-member-dashboard.php

---

## Shortcodes disponibles

### 1. Coach Dashboard
```
[jgk_coach_dashboard]
```

### 2. Member Dashboard
```
[jgk_member_dashboard]
```

---

## Coach Dashboard

### Fonctionnalités

#### 📊 Statistiques principales
- **Total Members** : Nombre total de membres assignés
- **Active Members** : Membres avec statut "active"
- **Primary Members** : Membres dont le coach est principal
- **This Month** : Changement net ce mois-ci (+/-)

#### 👥 Liste des membres
- Affiche les 10 premiers membres assignés
- Badge "★" pour les membres principaux
- Statut et type de membership visibles
- Bouton "View All" pour voir tous les membres

#### 📈 Performance (Sidebar)
- **New Members** : Nouveaux membres ce mois
- **Removed** : Membres retirés
- **Net Change** : Changement net

#### 📁 Members by Type
- Junior, Youth, Adult, Senior, Family
- Compte par catégorie

#### 🕐 Recent Activity
- Historique des 5 dernières assignations
- Distinction primary/secondary
- Temps écoulé depuis l'action

### Données retournées par la classe

```php
// Statistiques
$stats = JuniorGolfKenya_Coach_Dashboard::get_stats($coach_id);
// Returns:
// - total_members
// - primary_members
// - secondary_members
// - active_members
// - pending_members
// - members_by_type (array)
// - members_by_status (array)
// - gender_distribution (array)
// - recent_activities (array)

// Membres assignés
$members = JuniorGolfKenya_Coach_Dashboard::get_assigned_members(
    $coach_id, 
    $status = '',      // 'active', 'pending', etc.
    $limit = 50, 
    $offset = 0
);

// Performance metrics
$performance = JuniorGolfKenya_Coach_Dashboard::get_performance_metrics(
    $coach_id, 
    $period = 'month'  // 'week', 'month', 'year'
);

// Profil du coach
$profile = JuniorGolfKenya_Coach_Dashboard::get_coach_profile($coach_id);
```

### Comment l'utiliser

1. **Créer une page WordPress** (ex: "Coach Dashboard")
2. **Ajouter le shortcode** : `[jgk_coach_dashboard]`
3. **Publier la page**
4. **Partager l'URL** avec les coaches

### Sécurité

- ✅ Vérifie que l'utilisateur est connecté
- ✅ Vérifie que l'utilisateur a le rôle `jgk_coach`
- ✅ Affiche uniquement les données du coach connecté
- ❌ Les non-connectés voient : "You must be logged in to view this page."
- ❌ Les non-coaches voient : "You do not have permission to view this page."

---

## Member Dashboard

### Fonctionnalités

#### 📊 Statistiques principales
- **Assigned Coaches** : Nombre de coaches assignés
- **Member Since** : Durée d'adhésion (ex: "2 years, 3 months")
- **Profile Completion** : Pourcentage de complétion (0-100%)
- **Handicap Index** : Index handicap du membre

#### 🆔 Personal Information
Affiche les informations personnelles :
- Nom complet
- Date de naissance (avec âge calculé)
- Genre
- Téléphone
- Club
- Numéro de membre

#### 👨‍🏫 Your Coaches
- Liste de tous les coaches assignés
- Badge "Primary Coach" pour le coach principal
- Spécialisation du coach
- Contacts (email, téléphone)
- Date d'assignation

#### 👨‍👩‍👧 Parents/Guardians
- Nom et relation (Father, Mother, Guardian)
- Contacts cliquables (tel:, mailto:)
- Badges "Primary Contact" et "Emergency Contact"

#### ⭐ Primary Coach Widget (Sidebar)
- Photo du coach principal
- Nom et email
- Bouton "Contact Coach" (ouvre l'email)

#### 🔗 Quick Links (Sidebar)
- Edit Profile (à implémenter)
- My Schedule (à implémenter)
- My Progress (à implémenter)
- Payment History (à implémenter)

#### 🕐 Recent Activity (Sidebar)
- Historique des 5 dernières activités
- Type d'activité avec icône
- Temps écoulé

### Données retournées par la classe

```php
// Statistiques
$stats = JuniorGolfKenya_Member_Dashboard::get_stats($member_id);
// Returns:
// - member (object) : Full member data
// - coaches_count (int)
// - primary_coach (object)
// - membership_duration (string) : "2 years, 3 months"
// - age (int)
// - profile_completion (int) : 0-100%

// Coaches assignés
$coaches = JuniorGolfKenya_Member_Dashboard::get_assigned_coaches($member_id);

// Activités récentes
$activities = JuniorGolfKenya_Member_Dashboard::get_recent_activities($member_id, $limit = 10);

// Parents/Tuteurs
$parents = JuniorGolfKenya_Member_Dashboard::get_parents($member_id);

// Image de profil
$image_url = JuniorGolfKenya_Member_Dashboard::get_profile_image($member_id, $size = 'medium');
```

### Calcul de la complétion du profil

Le pourcentage est calculé sur **15 champs** :
1. first_name
2. last_name
3. phone
4. date_of_birth
5. gender
6. address
7. club_name
8. handicap_index
9. emergency_contact_name
10. emergency_contact_phone
11. biography
12. membership_number
13. coach_id (a des coaches)
14. profile_image (photo de profil)
15. user_email

**Formule** : `(champs_remplis / 15) × 100`

### Comment l'utiliser

1. **Créer une page WordPress** (ex: "Member Dashboard" ou "My Dashboard")
2. **Ajouter le shortcode** : `[jgk_member_dashboard]`
3. **Publier la page**
4. **Partager l'URL** avec les membres

### Sécurité

- ✅ Vérifie que l'utilisateur est connecté
- ✅ Vérifie que l'utilisateur a le rôle `jgk_member`
- ✅ Affiche uniquement les données du membre connecté
- ✅ Vérifie que le profil membre existe dans la base de données
- ❌ Les non-connectés voient : "You must be logged in to view this page."
- ❌ Les non-membres voient : "You do not have permission to view this page."
- ❌ Si profil introuvable : "Member profile not found. Please contact the administrator."

---

## Configuration

### Créer les pages dashboard

#### Option 1 : Manuellement
1. Aller dans **Pages** > **Add New**
2. Titre : "Coach Dashboard" ou "Member Dashboard"
3. Contenu : Ajouter le shortcode approprié
4. Publier

#### Option 2 : Via code (programmation)
```php
// Dans votre fichier d'activation (class-juniorgolfkenya-activator.php)
$coach_page = array(
    'post_title'   => 'Coach Dashboard',
    'post_content' => '[jgk_coach_dashboard]',
    'post_status'  => 'publish',
    'post_type'    => 'page',
);
wp_insert_post($coach_page);

$member_page = array(
    'post_title'   => 'Member Dashboard',
    'post_content' => '[jgk_member_dashboard]',
    'post_status'  => 'publish',
    'post_type'    => 'page',
);
wp_insert_post($member_page);
```

### Redirection après connexion

Pour rediriger automatiquement les utilisateurs vers leur dashboard après connexion :

```php
add_filter('login_redirect', 'jgk_custom_login_redirect', 10, 3);
function jgk_custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('jgk_coach', $user->roles)) {
            // Redirect coaches to coach dashboard
            return home_url('/coach-dashboard/');
        } elseif (in_array('jgk_member', $user->roles)) {
            // Redirect members to member dashboard
            return home_url('/member-dashboard/');
        }
    }
    return $redirect_to;
}
```

---

## Personnalisation

### Modifier les couleurs

Les dashboards utilisent des gradients CSS. Pour les personnaliser :

#### Coach Dashboard (Violet/Purple)
```css
/* Header gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Pour changer en bleu : */
background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
```

#### Member Dashboard (Rose/Red)
```css
/* Header gradient */
background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);

/* Pour changer en vert : */
background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
```

### Ajouter des widgets personnalisés

Dans le fichier de vue (juniorgolfkenya-coach-dashboard.php ou juniorgolfkenya-member-dashboard.php), ajoutez une nouvelle section :

```php
<!-- Custom Widget -->
<div class="jgk-dashboard-section">
    <div class="jgk-section-header">
        <h3>
            <span class="dashicons dashicons-star-filled"></span>
            Mon Widget
        </h3>
    </div>
    <div class="jgk-widget-content">
        <!-- Votre contenu ici -->
    </div>
</div>
```

### Modifier les méthodes de classe

Pour ajouter des méthodes personnalisées, éditez les classes :
- `includes/class-juniorgolfkenya-coach-dashboard.php`
- `includes/class-juniorgolfkenya-member-dashboard.php`

Exemple :
```php
public static function get_custom_stat($user_id) {
    global $wpdb;
    // Votre logique ici
    return $result;
}
```

---

## Fonctionnalités futures (Placeholders)

Les méthodes suivantes sont des placeholders pour de futures fonctionnalités :

### Coach Dashboard
- `get_upcoming_events($coach_id, $days)` - Événements à venir
- `get_member_progress($coach_id, $member_id)` - Suivi de progrès

### Member Dashboard
- `get_upcoming_events($member_id, $days)` - Sessions à venir
- `get_progress_data($member_id)` - Données de progrès
- `get_payment_history($member_id, $limit)` - Historique des paiements

Ces méthodes retournent actuellement des tableaux vides et seront implémentées lorsque les fonctionnalités correspondantes seront ajoutées.

---

## Design & UX

### Responsive Design
Les dashboards sont **100% responsive** :
- **Desktop** : Grille 2 colonnes (main + sidebar)
- **Tablette** : Grille 1 colonne (sidebar en haut)
- **Mobile** : Tout en colonne unique

### Couleurs par rôle
- **Coach** : Gradient violet/purple (#667eea → #764ba2)
- **Member** : Gradient rose/red (#f093fb → #f5576c)

### Icônes
Utilise **Dashicons** de WordPress (inclus par défaut).

### Animations
- Hover effects sur les cartes
- Transform translateY(-5px) au survol
- Transitions fluides (0.3s ease)

---

## Dépannage

### Le shortcode n'affiche rien
- ✅ Vérifiez que vous êtes connecté
- ✅ Vérifiez votre rôle utilisateur (jgk_coach ou jgk_member)
- ✅ Vérifiez que le plugin est activé

### Erreur "Member profile not found"
- Le user_id n'est pas lié à un membre dans wp_jgk_members
- Créez un profil membre pour cet utilisateur

### Les statistiques sont vides
- Vérifiez que des données existent dans la base de données
- Vérifiez les relations coach-member dans wp_jgk_coach_members

### Les styles ne s'appliquent pas
- Les styles sont inclus directement dans les fichiers de vue
- Vérifiez qu'aucun CSS de thème ne les écrase
- Utilisez l'inspecteur du navigateur pour débugger

---

## Support

Pour toute question ou problème :
1. Consultez ce guide
2. Vérifiez les logs WordPress (wp-content/debug.log)
3. Contactez l'administrateur du plugin

---

**Version** : 1.0.0  
**Dernière mise à jour** : 11 octobre 2025  
**Auteur** : Junior Golf Kenya
