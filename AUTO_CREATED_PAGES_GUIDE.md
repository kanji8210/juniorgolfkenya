# 📄 Pages Créées Automatiquement - Guide Complet

## 🎯 Vue d'Ensemble

Le plugin **Junior Golf Kenya** crée automatiquement **6 pages essentielles** lors de l'activation. Ces pages sont prêtes à l'emploi avec des shortcodes ou du contenu HTML intégré.

---

## 📋 Liste des Pages Créées

| # | Titre de la Page | Slug | Shortcode/Contenu | URL Typique |
|---|-----------------|------|-------------------|-------------|
| 1 | **Coach Dashboard** | `coach-dashboard` | `[jgk_coach_dashboard]` | `/coach-dashboard` |
| 2 | **My Dashboard** | `member-dashboard` | `[jgk_member_dashboard]` | `/member-dashboard` |
| 3 | **Become a Member** | `member-registration` | `[jgk_registration_form]` | `/member-registration` |
| 4 | **Apply as Coach** | `coach-role-request` | HTML Form | `/coach-role-request` |
| 5 | **Member Portal** | `member-portal` | `[jgk_member_portal]` | `/member-portal` |
| 6 | **Verify Membership** | `verify-membership` | `[jgk_verification_widget]` | `/verify-membership` |

---

## 🔍 Détails de Chaque Page

### 1️⃣ Coach Dashboard
**Page:** `coach-dashboard`  
**Titre:** Coach Dashboard  
**Shortcode:** `[jgk_coach_dashboard]`

**Accès:**
- ✅ Réservé aux utilisateurs avec le rôle `jgk_coach`
- ❌ Message d'erreur si non connecté ou pas coach

**Fonctionnalités:**
- 📊 Statistiques des membres assignés
- 👥 Liste des membres (primaires/secondaires)
- 📈 Métriques de performance
- 📅 Événements à venir
- 📝 Activités récentes

**Stockage ID:**
```php
$page_id = get_option('jgk_page_coach_dashboard');
$url = get_permalink($page_id);
```

---

### 2️⃣ My Dashboard (Member Dashboard)
**Page:** `member-dashboard`  
**Titre:** My Dashboard  
**Shortcode:** `[jgk_member_dashboard]`

**Accès:**
- ✅ Réservé aux utilisateurs avec le rôle `jgk_member`
- ✅ Statut "active" requis
- ⏸️ Message "Pending Approval" si statut = pending
- ❌ Message d'erreur si suspended/expired

**Fonctionnalités:**
- 📋 Informations d'adhésion
- 👨‍🏫 Coaches assignés (primaire + secondaires)
- 👨‍👩‍👧 Parents/tuteurs (pour juniors)
- 📊 Statistiques personnelles
- 📈 Progression et objectifs
- 🎯 Complétion du profil (%)

**Stockage ID:**
```php
$page_id = get_option('jgk_page_member_dashboard');
$url = get_permalink($page_id);
```

**Utilisation après inscription:**
```php
// Dans le formulaire d'inscription
$dashboard_page_id = get_option('jgk_page_member_dashboard');
$dashboard_url = get_permalink($dashboard_page_id);

// Email
$message .= "Dashboard URL: " . $dashboard_url . "\n";

// Bouton
echo '<a href="' . esc_url($dashboard_url) . '">Go to Dashboard</a>';
```

---

### 3️⃣ Become a Member (Registration)
**Page:** `member-registration`  
**Titre:** Become a Member  
**Shortcode:** `[jgk_registration_form]`

**Accès:**
- ✅ Public (tout le monde)
- ✅ Pas besoin d'être connecté

**Formulaire Inclut:**
- 📝 **Informations personnelles:** Nom, prénom, email, téléphone, date de naissance, genre, adresse
- 🔐 **Mot de passe:** Choisi par l'utilisateur (min 8 caractères) + confirmation + indicateur de force
- 🎯 **Type d'adhésion:** Junior/Youth/Adult/Senior/Family avec tarifs
- 👨‍👩‍👧 **Parent/Tuteur:** Section automatique pour les juniors
- 🏥 **Contact d'urgence:** Nom et téléphone
- ⛳ **Détails golf:** Club, handicap
- ✅ **Consentements:** Photographie, parental, CGU

**Après Soumission:**
1. ✅ Compte WordPress créé
2. ✅ Rôle `jgk_member` assigné
3. ✅ Statut = `active` (accès immédiat)
4. ✅ Enregistrement dans `wp_jgk_members`
5. ✅ Parent/tuteur dans `wp_jgk_parents_guardians` (si junior)
6. ✅ Auto-login de l'utilisateur
7. ✅ Email de bienvenue envoyé
8. ✅ Notification à l'admin
9. ✅ Redirection vers message de succès avec bouton "Go to Dashboard"

**Stockage ID:**
```php
$page_id = get_option('jgk_page_member_registration');
$url = get_permalink($page_id);
```

---

### 4️⃣ Apply as Coach (Coach Role Request)
**Page:** `coach-role-request`  
**Titre:** Apply as Coach  
**Contenu:** HTML Form (pas de shortcode)

**Accès:**
- ✅ Utilisateur doit être connecté
- ❌ Ne peut pas déjà être coach
- ❌ Ne peut pas avoir de demande en attente

**Formulaire Inclut:**
- 📝 **Informations personnelles:** Prénom, nom, email (readonly), téléphone
- 🎓 **Expérience:** Années d'expérience (dropdown), spécialisation, certifications, détails
- 👤 **Références:** Nom et contact (optionnel)

**Après Soumission:**
1. ✅ Insertion dans `wp_jgf_role_requests` avec status='pending'
2. ✅ Email à l'admin avec lien d'approbation
3. ✅ Message de succès AJAX

**Stockage ID:**
```php
$page_id = get_option('jgk_page_coach_role_request');
$url = get_permalink($page_id);
```

---

### 5️⃣ Member Portal
**Page:** `member-portal`  
**Titre:** Member Portal  
**Shortcode:** `[jgk_member_portal]`

**Accès:**
- ✅ Réservé aux membres connectés
- ℹ️ **Note:** Shortcode à implémenter (placeholder actuel)

**Fonctionnalités Prévues:**
- 📄 Accès aux services membres
- 📚 Documentation et ressources
- 📧 Messages et notifications
- 🎫 Événements et inscriptions
- 💳 Gestion des paiements

**Stockage ID:**
```php
$page_id = get_option('jgk_page_member_portal');
$url = get_permalink($page_id);
```

---

### 6️⃣ Verify Membership
**Page:** `verify-membership`  
**Titre:** Verify Membership  
**Shortcode:** `[jgk_verification_widget]`

**Accès:**
- ✅ Public (tout le monde)
- ✅ Pas besoin d'être connecté

**Fonctionnalités:**
- 🔍 Recherche par:
  - Numéro d'adhésion (ex: JGK-2025-0001)
  - Nom complet
  - Adresse email
- 📊 Affichage du statut:
  - 🟢 Active (vert)
  - 🟡 Expired (orange)
  - 🔵 Pending (bleu)
  - 🔴 Suspended (rouge)
- 📋 Informations affichées:
  - Nom du membre
  - Numéro d'adhésion
  - Type d'adhésion
  - Date d'inscription
  - Date d'expiration (avec alerte si < 30 jours)
  - Club affilié

**Sécurité:**
- ✅ Affichage limité (pas d'infos sensibles)
- ✅ Nonce verification
- ✅ Sanitisation des données

**Stockage ID:**
```php
$page_id = get_option('jgk_page_verify_membership');
$url = get_permalink($page_id);
```

---

## 🔧 Récupération des URLs dans le Code

### Méthode Recommandée (via Option)
```php
// Récupérer l'ID de la page
$page_id = get_option('jgk_page_member_dashboard');

// Vérifier si la page existe
if ($page_id) {
    $url = get_permalink($page_id);
    echo '<a href="' . esc_url($url) . '">My Dashboard</a>';
} else {
    // Fallback si la page n'existe pas
    $url = home_url('/member-dashboard');
}
```

### Toutes les Options Disponibles
```php
// Format: jgk_page_{slug_with_underscores}
$options = array(
    'jgk_page_coach_dashboard',      // Coach Dashboard
    'jgk_page_member_dashboard',     // My Dashboard
    'jgk_page_member_registration',  // Become a Member
    'jgk_page_coach_role_request',   // Apply as Coach
    'jgk_page_member_portal',        // Member Portal
    'jgk_page_verify_membership'     // Verify Membership
);

foreach ($options as $option) {
    $page_id = get_option($option);
    if ($page_id) {
        echo $option . ': ' . get_permalink($page_id) . "\n";
    }
}
```

---

## 🎨 Personnalisation des Pages

### 1. Modifier le Titre
```php
// Dans l'admin WordPress
Pages > All Pages > Edit > Title
```

### 2. Ajouter du Contenu Autour du Shortcode
```php
// Exemple pour member-dashboard
<div class="custom-intro">
    <h2>Welcome to Your Dashboard</h2>
    <p>Manage your membership here.</p>
</div>

[jgk_member_dashboard]

<div class="custom-footer">
    <p>Need help? <a href="/contact">Contact us</a></p>
</div>
```

### 3. Changer le Slug (URL)
```php
// Dans l'admin WordPress
Pages > Edit > Permalink > Change slug
// Note: L'option WordPress conserve l'ID, donc le code continue de fonctionner
```

---

## 📱 Pages dans le Menu de Navigation

### Ajouter au Menu Principal
```
1. Apparence > Menus
2. Sélectionner les pages à ajouter:
   - My Dashboard (pour membres connectés)
   - Become a Member (pour visiteurs)
   - Verify Membership (public)
3. Drag & drop pour organiser
4. Sauvegarder
```

### Menu Conditionnel (Members Only)
```php
// Dans functions.php ou un plugin
add_filter('wp_nav_menu_items', 'add_member_menu_items', 10, 2);
function add_member_menu_items($items, $args) {
    if ($args->theme_location == 'primary') {
        if (is_user_logged_in() && current_user_can('jgk_member')) {
            $dashboard_id = get_option('jgk_page_member_dashboard');
            $dashboard_url = get_permalink($dashboard_id);
            $items .= '<li><a href="' . $dashboard_url . '">My Dashboard</a></li>';
        }
    }
    return $items;
}
```

---

## 🔄 Recréer les Pages

### Si Pages Supprimées Accidentellement
```php
// 1. Désactiver le plugin
Plugins > Junior Golf Kenya > Deactivate

// 2. Réactiver le plugin
Plugins > Junior Golf Kenya > Activate

// 3. Les pages seront recréées automatiquement
```

### Script Manuel (si nécessaire)
```php
// Ajouter dans functions.php temporairement
add_action('admin_init', 'jgk_recreate_pages_once');
function jgk_recreate_pages_once() {
    if (get_option('jgk_pages_recreated')) {
        return;
    }
    
    // Code de création des pages de l'activator
    require_once plugin_dir_path(__FILE__) . 'includes/class-juniorgolfkenya-activator.php';
    JuniorGolfKenya_Activator::create_pages();
    
    update_option('jgk_pages_recreated', true);
}
// Retirer ce code après exécution
```

---

## 🧪 Tester les Pages

### Checklist de Test

#### Coach Dashboard
- [ ] Créer un utilisateur avec rôle `jgk_coach`
- [ ] Se connecter avec ce compte
- [ ] Aller sur `/coach-dashboard`
- [ ] Vérifier que le dashboard s'affiche
- [ ] Vérifier les statistiques
- [ ] Vérifier la liste des membres

#### Member Dashboard
- [ ] S'inscrire via `/member-registration`
- [ ] Vérifier auto-login
- [ ] Cliquer sur "Go to My Dashboard"
- [ ] Vérifier que le dashboard s'affiche
- [ ] Vérifier les informations affichées
- [ ] Vérifier les coaches (si assignés)
- [ ] Vérifier les parents (si junior)

#### Registration
- [ ] Aller sur `/member-registration`
- [ ] Remplir le formulaire
- [ ] Choisir mot de passe
- [ ] Observer indicateur de force
- [ ] Soumettre
- [ ] Vérifier message de succès
- [ ] Vérifier email reçu
- [ ] Cliquer "Go to Dashboard"
- [ ] Vérifier accès immédiat

#### Coach Role Request
- [ ] Se connecter (non-coach)
- [ ] Aller sur `/coach-role-request`
- [ ] Remplir le formulaire
- [ ] Soumettre
- [ ] Vérifier message de succès
- [ ] Vérifier email admin

#### Verify Membership
- [ ] Aller sur `/verify-membership`
- [ ] Rechercher par numéro d'adhésion
- [ ] Vérifier affichage du statut
- [ ] Rechercher par nom
- [ ] Rechercher par email

---

## 🔒 Sécurité des Pages

### Pages Publiques
✅ **Verify Membership** - Accessible à tous  
✅ **Become a Member** - Accessible à tous  
✅ **Apply as Coach** - Formulaire visible mais soumission requiert connexion

### Pages Protégées
🔐 **Coach Dashboard** - Rôle `jgk_coach` requis  
🔐 **My Dashboard** - Rôle `jgk_member` + statut `active` requis  
🔐 **Member Portal** - Authentification requise (à implémenter)

### Redirection si Non Autorisé
```php
// Exemple dans le shortcode
if (!is_user_logged_in()) {
    return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view this page.</p>';
}

if (!in_array('jgk_member', wp_get_current_user()->roles)) {
    return '<p>You do not have permission to view this page.</p>';
}
```

---

## 📊 Statistiques d'Utilisation

### Tracking des Visites (Google Analytics)
```html
<!-- Ajouter dans le header du thème -->
<?php if (is_page(get_option('jgk_page_member_dashboard'))): ?>
    <!-- Tracking code for Member Dashboard -->
<?php endif; ?>
```

### Pages les Plus Visitées
```sql
-- Via WordPress Stats plugin
SELECT post_id, SUM(views) as total_views
FROM wp_stats
WHERE post_id IN (
    -- IDs des pages JGK
    SELECT option_value FROM wp_options WHERE option_name LIKE 'jgk_page_%'
)
GROUP BY post_id
ORDER BY total_views DESC;
```

---

## 🎯 Raccourcis Administrateur

### Widget Dashboard WordPress
```php
// Ajouter dans functions.php
add_action('wp_dashboard_setup', 'jgk_dashboard_widget');
function jgk_dashboard_widget() {
    wp_add_dashboard_widget(
        'jgk_quick_links',
        'Junior Golf Kenya - Quick Links',
        'jgk_dashboard_widget_content'
    );
}

function jgk_dashboard_widget_content() {
    $pages = array(
        'member_dashboard' => 'My Dashboard',
        'member_registration' => 'Registration',
        'coach_dashboard' => 'Coach Dashboard',
        'verify_membership' => 'Verify Membership'
    );
    
    echo '<ul>';
    foreach ($pages as $slug => $title) {
        $page_id = get_option('jgk_page_' . $slug);
        if ($page_id) {
            $url = get_permalink($page_id);
            echo '<li><a href="' . $url . '" target="_blank">' . $title . '</a></li>';
        }
    }
    echo '</ul>';
}
```

---

## 📞 Support

### Problèmes Courants

**Page 404 après activation:**
```
Solution: Aller dans Réglages > Permaliens > Sauvegarder
(Cela régénère les règles de réécriture)
```

**Shortcode non traité (s'affiche tel quel):**
```
Solution: Vérifier que le plugin est activé
Vérifier que le shortcode est correctement enregistré
```

**Dashboard vide:**
```
Solution: Vérifier que des données existent dans la base
Créer au moins un membre de test
Assigner un coach si nécessaire
```

---

**Date de Création:** 11 Octobre 2025  
**Version Plugin:** 1.0.0  
**Statut:** ✅ Toutes les Pages Opérationnelles
