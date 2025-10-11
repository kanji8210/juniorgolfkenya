# Auto-Created Pages Documentation
# Documentation des pages créées automatiquement

## Vue d'ensemble

Lors de l'activation du plugin Junior Golf Kenya, **6 pages WordPress** sont créées automatiquement avec leurs shortcodes respectifs. Ces pages fournissent une expérience complète pour les membres et coaches.

## 📄 Pages créées automatiquement

### 1. Coach Dashboard (`/coach-dashboard/`)

**Shortcode** : `[jgk_coach_dashboard]`

**Description** : Dashboard personnel pour les coaches avec statistiques et gestion des membres.

**Accès** :
- Utilisateurs connectés avec le rôle `jgk_coach` uniquement
- Redirection vers login si non connecté
- Message d'erreur si rôle incorrect

**Fonctionnalités** :
- Statistiques (total members, active, primary, net change)
- Liste des membres assignés
- Performance metrics (new/removed members)
- Breakdown par type de membership
- Recent activities
- Design responsive violet/purple

**URL** : `https://your-site.com/coach-dashboard/`

---

### 2. Member Dashboard (`/member-dashboard/`)

**Shortcode** : `[jgk_member_dashboard]`

**Nom affiché** : "My Dashboard"

**Description** : Dashboard personnel pour les membres avec profil et informations coaches.

**Accès** :
- Utilisateurs connectés avec le rôle `jgk_member` uniquement
- Redirection vers login si non connecté
- Message d'erreur si rôle incorrect

**Fonctionnalités** :
- Statistiques (coaches count, duration, profile completion, handicap)
- Informations personnelles complètes
- Liste des coaches assignés
- Parents/Guardians avec contacts
- Primary coach widget
- Quick links
- Recent activities
- Design responsive rose/red

**URL** : `https://your-site.com/member-dashboard/`

---

### 3. Member Registration (`/member-registration/`)

**Shortcode** : `[jgk_registration_form]`

**Nom affiché** : "Become a Member"

**Description** : Formulaire d'inscription pour les nouveaux membres.

**Accès** : Public (tous les visiteurs)

**Fonctionnalités** :
- Formulaire d'inscription complet
- Création de compte WordPress
- Création de profil membre dans wp_jgk_members
- Assignment automatique du rôle `jgk_member`
- Email de confirmation

**Champs du formulaire** :
- Personal Information (first name, last name, email, phone)
- Date of Birth & Gender
- Membership Type (junior, youth, adult, senior, family)
- Club Affiliation
- Emergency Contact
- Parents/Guardians information (pour minors)
- Profile Image upload
- Terms & Conditions agreement

**URL** : `https://your-site.com/member-registration/`

---

### 4. Coach Role Request (`/coach-role-request/`)

**Nom affiché** : "Apply as Coach"

**Description** : Formulaire de demande pour devenir coach.

**Accès** :
- Utilisateurs connectés uniquement
- Vérifie qu'ils ne sont pas déjà coaches
- Vérifie qu'ils n'ont pas déjà une demande pending

**Fonctionnalités** :
- Formulaire de demande détaillé
- Insertion dans wp_jgf_role_requests
- Email de notification à l'admin
- AJAX submission avec fallback
- Messages de succès/erreur

**Champs du formulaire** :

**Personal Information** :
- First Name (pré-rempli)
- Last Name (pré-rempli)
- Email (readonly, depuis le compte)
- Phone Number

**Coaching Experience** :
- Years of Experience (dropdown: 0-1, 1-3, 3-5, 5-10, 10+)
- Specialization (text)
- Certifications & Qualifications (textarea)
- Coaching Experience Details (textarea)

**References** :
- Reference Name (optional)
- Reference Contact (optional)

**Terms** :
- Agreement checkbox (required)

**Statuts possibles** :
- `pending` - En attente de review
- `approved` - Approuvé (rôle ajouté)
- `rejected` - Rejeté

**Process** :
1. Utilisateur connecté remplit le formulaire
2. Soumission → Insert dans `wp_jgf_role_requests`
3. Email envoyé à l'admin
4. Admin review dans backend (page Role Requests)
5. Approbation → Rôle `jgk_coach` ajouté automatiquement
6. Rejet → Email de notification

**URL** : `https://your-site.com/coach-role-request/`

---

### 5. Member Portal (`/member-portal/`)

**Shortcode** : `[jgk_member_portal]`

**Description** : Portail central pour accéder aux services membres.

**Accès** : Public, mais certaines fonctionnalités nécessitent connexion

**Fonctionnalités** :
- Vue d'ensemble des services
- Liens vers dashboard, profile, etc.
- Informations sur l'adhésion
- News & Updates (à implémenter)

**URL** : `https://your-site.com/member-portal/`

---

### 6. Verify Membership (`/verify-membership/`)

**Shortcode** : `[jgk_verification_widget]`

**Description** : Widget public pour vérifier le statut d'adhésion d'un membre.

**Accès** : Public (tous les visiteurs)

**Fonctionnalités** :
- Recherche par membership number, nom, ou email
- Vérification du statut (active, expired, suspended)
- Affichage des informations publiques
- Protection de la vie privée (infos limitées)

**Informations affichées** :
- Nom du membre
- Membership number
- Status (active/expired)
- Date d'expiration
- Type de membership

**Informations cachées** :
- Email complet
- Phone
- Address
- Personal details

**URL** : `https://your-site.com/verify-membership/`

---

## 🔧 Configuration technique

### Comment les pages sont créées

Les pages sont créées dans la méthode `create_pages()` de la classe `JuniorGolfKenya_Activator` :

```php
private static function create_pages() {
    $pages = array(
        'coach-dashboard' => array(
            'title' => 'Coach Dashboard',
            'content' => '[jgk_coach_dashboard]',
            'description' => 'Dashboard for coaches...'
        ),
        // ... autres pages
    );
    
    foreach ($pages as $slug => $page_data) {
        $existing_page = get_page_by_path($slug);
        
        if (!$existing_page) {
            $page_id = wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_content' => $page_data['content'],
                'post_excerpt' => $page_data['description'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug,
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));
            
            // Store page ID in options
            update_option('jgk_page_' . str_replace('-', '_', $slug), $page_id);
        }
    }
}
```

### Options WordPress créées

Pour chaque page, une option est enregistrée :

```php
jgk_page_coach_dashboard        => Page ID
jgk_page_member_dashboard       => Page ID
jgk_page_member_registration    => Page ID
jgk_page_coach_role_request     => Page ID
jgk_page_member_portal          => Page ID
jgk_page_verify_membership      => Page ID
```

**Usage** :
```php
$coach_dashboard_id = get_option('jgk_page_coach_dashboard');
$coach_dashboard_url = get_permalink($coach_dashboard_id);
```

---

## 🚀 Utilisation après activation

### Étape 1 : Activer le plugin

Le plugin crée automatiquement toutes les pages lors de l'activation.

```
Plugins → Junior Golf Kenya → Activate
```

### Étape 2 : Vérifier les pages

Allez dans **Pages** → **All Pages** et vous devriez voir les 6 nouvelles pages :

- ✅ Coach Dashboard
- ✅ My Dashboard
- ✅ Become a Member
- ✅ Apply as Coach
- ✅ Member Portal
- ✅ Verify Membership

### Étape 3 : Ajouter au menu (optionnel)

Pour afficher les pages dans le menu de navigation :

1. **Apparence** → **Menus**
2. Sélectionnez votre menu
3. Ajoutez les pages pertinentes
4. Pour afficher conditionnellement :
   - Dashboard pages → Uniquement aux utilisateurs connectés
   - Registration → À tous
   - Verification → À tous

### Étape 4 : Configuration des redirections (optionnel)

Pour rediriger après login :

```php
// Dans functions.php de votre thème
add_filter('login_redirect', 'jgk_custom_login_redirect', 10, 3);
function jgk_custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('jgk_coach', $user->roles)) {
            return get_permalink(get_option('jgk_page_coach_dashboard'));
        }
        if (in_array('jgk_member', $user->roles)) {
            return get_permalink(get_option('jgk_page_member_dashboard'));
        }
    }
    return $redirect_to;
}
```

---

## 📋 Checklist de vérification

Après activation du plugin, vérifiez :

- [ ] Les 6 pages sont créées dans **Pages → All Pages**
- [ ] Chaque page a son shortcode correct
- [ ] Les pages sont **Published** (pas Draft)
- [ ] Les slugs sont corrects (`coach-dashboard`, `member-dashboard`, etc.)
- [ ] Les options WordPress sont enregistrées (vérifier avec query monitor)
- [ ] Test de chaque page :
  - [ ] Coach Dashboard (avec compte coach)
  - [ ] Member Dashboard (avec compte membre)
  - [ ] Member Registration (visiteur public)
  - [ ] Coach Role Request (utilisateur connecté)
  - [ ] Member Portal (tous)
  - [ ] Verify Membership (tous)

---

## 🔐 Sécurité des pages

### Pages publiques

- **Member Registration** : Public, form validation côté serveur
- **Member Portal** : Public, contenu limité
- **Verify Membership** : Public, données limitées

### Pages protégées (login required)

- **Coach Dashboard** : `jgk_coach` role only
- **Member Dashboard** : `jgk_member` role only
- **Coach Role Request** : Logged in users only

### Vérifications de sécurité

Toutes les pages protégées vérifient :
1. Utilisateur connecté (`is_user_logged_in()`)
2. Rôle approprié (`in_array('jgk_coach', $user->roles)`)
3. Nonce pour les formulaires (`wp_verify_nonce()`)
4. Sanitization des données (`sanitize_text_field()`, etc.)

---

## 🎨 Personnalisation des pages

### Modifier le contenu

Vous pouvez modifier le contenu de n'importe quelle page :

1. **Pages** → Trouver la page
2. Cliquer **Edit**
3. Modifier le texte autour du shortcode
4. **NE PAS supprimer le shortcode** `[jgk_...]`
5. Update

### Modifier le design

Les styles sont inclus dans les fichiers de vue :

**Pour Coach Dashboard** :
- Fichier : `public/partials/juniorgolfkenya-coach-dashboard.php`
- Section `<style>` à la fin du fichier

**Pour Member Dashboard** :
- Fichier : `public/partials/juniorgolfkenya-member-dashboard.php`
- Section `<style>` à la fin du fichier

**Pour Coach Role Request** :
- Fichier : `includes/class-juniorgolfkenya-activator.php`
- Méthode : `get_coach_role_request_content()`
- Section `<style>` dans le HTML retourné

### Ajouter des sections

Vous pouvez ajouter du contenu avant ou après les shortcodes :

```
<h2>Welcome to Junior Golf Kenya</h2>
<p>Intro text here...</p>

[jgk_member_dashboard]

<p>Footer text here...</p>
```

---

## 🆘 Dépannage

### Les pages n'apparaissent pas

**Solution** : Réactiver le plugin
```
Plugins → Junior Golf Kenya → Deactivate → Activate
```

### Les shortcodes s'affichent en texte brut

**Problème** : Shortcodes non enregistrés

**Solution** :
1. Vérifier que le plugin est activé
2. Vider le cache (si plugin de cache actif)
3. Vérifier dans `public/class-juniorgolfkenya-public.php` que `init_shortcodes()` est appelé

### Erreur "Page not found" (404)

**Solution** : Flush rewrite rules
```php
// Dans wp-admin, aller sur Settings → Permalinks
// Cliquer "Save Changes" sans rien modifier
```

Ou via code :
```php
flush_rewrite_rules();
```

### Les pages existent déjà

Si les pages existent déjà (slugs identiques), le plugin ne les recrée pas mais enregistre leurs IDs dans les options.

---

## 📊 Logs & Monitoring

Le plugin enregistre les pages créées dans les logs WordPress :

```
error_log('JuniorGolfKenya: Created pages - ' . wp_json_encode($created_pages));
```

Pour voir les logs :
1. Activer WP_DEBUG dans wp-config.php
2. Consulter wp-content/debug.log

---

## 🔄 Réinitialisation

Pour recréer les pages :

1. **Supprimer les pages existantes** (Trash → Delete Permanently)
2. **Supprimer les options** :
```php
delete_option('jgk_page_coach_dashboard');
delete_option('jgk_page_member_dashboard');
delete_option('jgk_page_member_registration');
delete_option('jgk_page_coach_role_request');
delete_option('jgk_page_member_portal');
delete_option('jgk_page_verify_membership');
delete_option('jgk_created_pages');
```
3. **Réactiver le plugin**

---

**Version** : 1.0.0  
**Dernière mise à jour** : 11 octobre 2025  
**Plugin** : Junior Golf Kenya - Membership Management
