# Dashboard Setup Instructions
# Instructions de configuration des dashboards frontend

## Résumé

Vous avez maintenant **2 dashboards frontend** pour votre plugin Junior Golf Kenya :

1. **Coach Dashboard** - Pour les coaches (`jgk_coach` role)
2. **Member Dashboard** - Pour les membres (`jgk_member` role)

## 🚀 Étapes de mise en place

### Étape 1 : Créer les pages WordPress

1. **Aller dans WordPress Admin** → Pages → Add New

2. **Créer la page Coach Dashboard** :
   - Titre : `Coach Dashboard`
   - Contenu : `[jgk_coach_dashboard]`
   - Slug : `coach-dashboard`
   - Publier

3. **Créer la page Member Dashboard** :
   - Titre : `Member Dashboard` ou `My Dashboard`
   - Contenu : `[jgk_member_dashboard]`
   - Slug : `member-dashboard`
   - Publier

### Étape 2 : Tester les dashboards

#### Test Coach Dashboard :
1. Connectez-vous avec un compte coach (role: `jgk_coach`)
2. Visitez : `https://your-site.com/coach-dashboard/`
3. Vous devriez voir :
   - Header avec photo et nom du coach
   - 4 cartes de statistiques
   - Liste des membres assignés
   - Sidebar avec performance et activités

#### Test Member Dashboard :
1. Connectez-vous avec un compte membre (role: `jgk_member`)
2. Visitez : `https://your-site.com/member-dashboard/`
3. Vous devriez voir :
   - Header avec photo et nom du membre
   - 4 cartes de statistiques (coaches, durée, complétion, handicap)
   - Informations personnelles
   - Liste des coaches assignés
   - Parents/Guardians (si disponibles)
   - Sidebar avec coach principal et quick links

### Étape 3 : Ajouter au menu (optionnel)

Pour ajouter les dashboards au menu principal :

1. **Apparence** → **Menus**
2. Sélectionnez votre menu principal
3. Ajoutez les pages "Coach Dashboard" et "Member Dashboard"
4. Utilisez les **Conditional Menus** pour afficher uniquement aux utilisateurs concernés

### Étape 4 : Redirection après connexion (optionnel)

Pour rediriger automatiquement après connexion, ajoutez ce code à votre `functions.php` ou dans un plugin personnalisé :

```php
add_filter('login_redirect', 'jgk_redirect_after_login', 10, 3);
function jgk_redirect_after_login($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        // Redirect coaches
        if (in_array('jgk_coach', $user->roles)) {
            return home_url('/coach-dashboard/');
        }
        // Redirect members
        if (in_array('jgk_member', $user->roles)) {
            return home_url('/member-dashboard/');
        }
    }
    return $redirect_to;
}
```

## 📁 Fichiers créés

### Classes Dashboard (Backend Logic)

1. **`includes/class-juniorgolfkenya-coach-dashboard.php`**
   - Méthodes pour récupérer les données du coach
   - Statistiques, membres, performance, activités

2. **`includes/class-juniorgolfkenya-member-dashboard.php`**
   - Méthodes pour récupérer les données du membre
   - Statistiques, coaches, parents, complétion profil

### Vues Dashboard (Frontend Display)

3. **`public/partials/juniorgolfkenya-coach-dashboard.php`**
   - Interface dashboard coach avec styles CSS inclus
   - Design violet/purple moderne et responsive

4. **`public/partials/juniorgolfkenya-member-dashboard.php`**
   - Interface dashboard membre avec styles CSS inclus
   - Design rose/red moderne et responsive

### Classe publique mise à jour

5. **`public/class-juniorgolfkenya-public.php`**
   - Ajout des méthodes shortcode
   - Vérification des permissions (login + role)
   - Chargement des vues

### Documentation

6. **`FRONTEND_DASHBOARDS_GUIDE.md`**
   - Guide complet d'utilisation
   - Documentation des méthodes
   - Exemples de personnalisation

7. **`DASHBOARD_SETUP_INSTRUCTIONS.md`** (ce fichier)
   - Instructions rapides de mise en place

## 🔐 Sécurité

Les dashboards sont sécurisés :

✅ **Vérification de connexion** : Seuls les utilisateurs connectés peuvent voir les dashboards
✅ **Vérification de rôle** : Coach dashboard → role `jgk_coach` uniquement, Member dashboard → role `jgk_member` uniquement
✅ **Données isolées** : Chaque utilisateur voit uniquement ses propres données
✅ **Pas d'accès backend** : Les coaches et membres n'ont pas accès à l'admin WordPress

## 🎨 Design

### Coach Dashboard
- **Couleurs** : Violet/Purple gradient (#667eea → #764ba2)
- **Icônes** : Dashicons WordPress
- **Layout** : 2 colonnes (main + sidebar) responsive

### Member Dashboard
- **Couleurs** : Rose/Red gradient (#f093fb → #f5576c)
- **Icônes** : Dashicons WordPress
- **Layout** : 2 colonnes (main + sidebar) responsive

### Responsive
- **Desktop** (1024px+) : Grille 2 colonnes
- **Tablet** (768px-1024px) : 1 colonne, sidebar en haut
- **Mobile** (<768px) : Tout en colonne unique

## 📊 Données affichées

### Coach Dashboard

**Statistiques** :
- Total Members
- Active Members
- Primary Members
- Net Change (This Month)

**Sections** :
- Your Members (liste paginée)
- Performance (new, removed, net)
- Members by Type (junior, youth, adult, etc.)
- Recent Activity (assignations)

### Member Dashboard

**Statistiques** :
- Assigned Coaches
- Member Since (durée)
- Profile Completion (0-100%)
- Handicap Index

**Sections** :
- Personal Information (nom, DOB, genre, phone, club, etc.)
- Your Coaches (liste avec primary badge)
- Parents/Guardians (avec contacts cliquables)
- Primary Coach Widget (sidebar)
- Quick Links (à implémenter)
- Recent Activity (sidebar)

## 🔧 Personnalisation

Pour personnaliser les dashboards, éditez les fichiers de vue :
- `public/partials/juniorgolfkenya-coach-dashboard.php`
- `public/partials/juniorgolfkenya-member-dashboard.php`

Les styles CSS sont inclus directement dans les fichiers (balise `<style>`).

## 📝 Shortcodes

Deux shortcodes sont disponibles :

```
[jgk_coach_dashboard]
[jgk_member_dashboard]
```

Utilisez-les dans n'importe quelle page WordPress.

## ⚙️ Méthodes disponibles

### Coach Dashboard Class

```php
JuniorGolfKenya_Coach_Dashboard::get_stats($coach_id);
JuniorGolfKenya_Coach_Dashboard::get_assigned_members($coach_id, $status, $limit, $offset);
JuniorGolfKenya_Coach_Dashboard::get_performance_metrics($coach_id, $period);
JuniorGolfKenya_Coach_Dashboard::get_coach_profile($coach_id);
JuniorGolfKenya_Coach_Dashboard::get_upcoming_events($coach_id, $days); // Placeholder
JuniorGolfKenya_Coach_Dashboard::get_member_progress($coach_id, $member_id); // Placeholder
```

### Member Dashboard Class

```php
JuniorGolfKenya_Member_Dashboard::get_stats($member_id);
JuniorGolfKenya_Member_Dashboard::get_assigned_coaches($member_id);
JuniorGolfKenya_Member_Dashboard::get_recent_activities($member_id, $limit);
JuniorGolfKenya_Member_Dashboard::get_parents($member_id);
JuniorGolfKenya_Member_Dashboard::get_profile_image($member_id, $size);
JuniorGolfKenya_Member_Dashboard::get_upcoming_events($member_id, $days); // Placeholder
JuniorGolfKenya_Member_Dashboard::get_progress_data($member_id); // Placeholder
JuniorGolfKenya_Member_Dashboard::get_payment_history($member_id, $limit); // Placeholder
```

## 🚦 Statut des fonctionnalités

### ✅ Implémenté
- Dashboards coach et membre (frontend)
- Shortcodes avec sécurité
- Classes de données avec méthodes statiques
- Design responsive et moderne
- Statistiques en temps réel
- Liste des membres/coaches
- Performance metrics
- Recent activities
- Profile completion calculator
- Parents/Guardians display

### ⏳ À implémenter (Placeholders)
- Upcoming events/sessions
- Member progress tracking
- Payment history
- Session attendance
- Goals and achievements
- Edit profile (frontend)
- Schedule management

## 🆘 Dépannage

### Le shortcode affiche le texte brut au lieu du dashboard
→ Le plugin n'est pas activé ou le shortcode n'est pas enregistré

### "You must be logged in"
→ L'utilisateur n'est pas connecté

### "You do not have permission"
→ L'utilisateur n'a pas le bon rôle (`jgk_coach` ou `jgk_member`)

### "Member profile not found"
→ Le user_id n'est pas lié à un membre dans `wp_jgk_members`

### Les statistiques sont vides
→ Aucune donnée dans la base de données (coaches, members, assignations)

### Les styles ne s'appliquent pas
→ Conflit CSS avec le thème, utilisez l'inspecteur pour débugger

## 📞 Support

Pour plus d'informations, consultez :
- `FRONTEND_DASHBOARDS_GUIDE.md` - Guide complet
- Code source des classes dashboard
- Code source des vues dashboard

---

**Version** : 1.0.0  
**Date** : 11 octobre 2025  
**Plugin** : Junior Golf Kenya  
**Auteur** : Junior Golf Kenya Team
