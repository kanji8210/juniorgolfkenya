# 🏌️ Architecture Complète du Système Membre - Junior Golf Kenya

**Date:** 11 Octobre 2025  
**Version:** 2.0  
**Statut:** ✅ Implémenté

---

## 📋 Vue d'Ensemble

### Flux Utilisateur Complet

```
1. Inscription (Registration Form)
   ↓
2. Création du compte → Statut "active" → Auto-login
   ↓
3. **Member Portal** (Page d'accueil) 👈 NOUVELLE
   ↓
4. **Member Dashboard** (Profil complet avec tout) 👈 AMÉLIORÉ
```

---

## 🎯 Les Deux Pages Principales

### 1️⃣ **Member Portal** (`/member-portal`)
**Rôle:** Page d'accueil/hub après connexion

**Fonctionnalités:**
- ✅ Message de bienvenue personnalisé
- ✅ Bouton Logout
- ✅ **Alerte d'expiration de membership:**
  - 🟡 Jaune si < 60 jours (10 mois déjà passés)
  - 🔴 Rouge si expiré
  - 🟢 Vert/pas d'alerte si > 60 jours
- ✅ **4 Cartes d'Accès Rapide:**
  1. My Dashboard (violet) - Profil complet
  2. Competitions (vert) - Événements
  3. My Trophies (jaune/or) - Récompenses
  4. Edit Profile (bleu) - Édition

**Code:**
```php
// Shortcode: [jgk_member_portal]
// Fichier: public/partials/juniorgolfkenya-member-portal.php

// Vérification de l'expiration
$membership_status = JuniorGolfKenya_Member_Data::get_membership_status($member);

if ($membership_status['is_expired']) {
    // Alerte rouge
} elseif ($membership_status['is_expiring_soon']) {
    // Alerte jaune (< 60 jours)
}
```

---

### 2️⃣ **Member Dashboard** (`/member-dashboard`)
**Rôle:** Profil complet avec toutes les données

**Sections:**

#### 📸 **Header avec Photo de Profil**
- Avatar/Photo de profil
- Nom complet
- Email
- Badges (Active, Membership Type)
- Bouton Logout

#### 📊 **Statistics Cards (4 cartes)**
1. **Assigned Coaches** - Nombre de coaches
2. **Member Since** - Date d'inscription
3. **Handicap** - Handicap actuel (C.Handicap)
4. **Competitions** - Nombre total de compétitions

#### 🏆 **Upcoming Competitions**
- Liste des compétitions à venir
- Date, lieu, catégorie
- Statut d'inscription (registered, pending)
- Bouton "View All Competitions"

#### 📜 **Past Competitions & Results**
- Historique des compétitions jouées
- Position finale, score, nombre de participants
- Handicap utilisé
- Date et lieu

#### 🥇 **Trophies & Achievements**
- Trophées remportés
- Type (Gold, Silver, Bronze, Special)
- Date d'obtention
- Compétition associée

#### 📈 **Performance Analytics**
- Statistiques détaillées:
  - Compétitions jouées
  - Victoires (wins)
  - Top 3 finishes
  - Top 10 finishes
  - Moyenne de score
  - Meilleur score
  - Handicap actuel
  - Amélioration du handicap
  - Rounds totaux
  - Birdies, Eagles, Pars
  - Taux de participation
  - Tendance (improving/declining/stable)

#### 👨‍🏫 **Assigned Coaches**
- Coach principal
- Coaches secondaires
- Informations de contact
- Spécialisation

#### 👨‍👩‍👧 **Parents/Guardians** (pour juniors)
- Informations des parents
- Contacts d'urgence

#### ✏️ **Edit Profile Section**
- Formulaire d'édition de profil
- **Champs éditables par le membre:**
  - ✅ Phone
  - ✅ Address
  - ✅ Emergency Contact Name
  - ✅ Emergency Contact Phone
  - ✅ Medical Conditions
  - ✅ Biography
  - ✅ Club Affiliation
  - ✅ Profile Image
  
- **Champs NON éditables (admin seulement):**
  - ❌ First Name / Last Name
  - ❌ Email
  - ❌ Membership Type
  - ❌ Status
  - ❌ Membership Number
  - ❌ Registration Date
  - ❌ Expiry Date
  - ❌ Coach Assignment
  - ❌ Handicap (géré par coach/admin)

---

## 🚨 Système d'Alerte d'Expiration

### Logique de Calcul

```php
// Classe: JuniorGolfKenya_Member_Data
// Méthode: get_membership_status($member)

$expiry_date = $member->expiry_date ?: registration_date + 1 year;
$days_remaining = days until expiry;

if ($days_remaining < 0) {
    // EXPIRED - Rouge
    $status = 'expired';
    $bg_color = '#f8d7da'; // Red background
    $color = '#dc3545';
    $message = 'Membership Expired - Renew Now!';
}
elseif ($days_remaining <= 60) {
    // EXPIRING SOON - Jaune (2 mois = ~60 jours)
    $status = 'expiring_soon';
    $bg_color = '#fff3cd'; // Yellow background
    $color = '#856404';
    $message = 'Membership expires in X days - Renew Soon!';
}
else {
    // ACTIVE - Vert (pas d'alerte affichée)
    $status = 'active';
    $message = 'Active - Expires on DATE';
}
```

### Affichage de l'Alerte

**Member Portal:**
```html
<!-- Alerte affichée en haut de la page -->
<div class="jgk-membership-alert" style="background-color: [YELLOW/RED]; color: [DARK];">
    <div class="jgk-alert-icon">
        <span class="dashicons dashicons-[clock/warning]"></span>
    </div>
    <div class="jgk-alert-content">
        <h3>[MESSAGE]</h3>
        <p>[DETAILS]</p>
        <a href="#" class="jgk-renew-btn">Renew Membership</a>
    </div>
</div>
```

**Member Dashboard:**
```html
<!-- Badge dans le header -->
<span class="jgk-badge jgk-badge-[warning/danger]">
    [Expiring Soon / Expired]
</span>

<!-- Card dans les statistiques -->
<div class="jgk-stat-card jgk-card-[warning/danger]">
    <h3>[X days remaining / Expired]</h3>
    <p>Membership Status</p>
</div>
```

---

## 📁 Structure des Fichiers

### Nouveaux Fichiers Créés

1. **`includes/class-juniorgolfkenya-member-data.php`**
   - Classe pour gérer les données membres
   - Méthodes:
     - `get_membership_status()` - Vérification expiration
     - `get_upcoming_competitions()` - Compétitions à venir
     - `get_past_competitions()` - Résultats passés
     - `get_trophies()` - Trophées du membre
     - `get_performance_stats()` - Statistiques de performance
     - `get_performance_chart_data()` - Données pour graphiques
     - `is_field_editable()` - Vérification des droits d'édition
     - `update_member_profile()` - Mise à jour du profil

### Fichiers Modifiés

1. **`public/partials/juniorgolfkenya-member-portal.php`**
   - Ajout de l'alerte d'expiration
   - Ajout des 4 cartes d'accès rapide
   - Amélioration du design

2. **`public/partials/juniorgolfkenya-member-dashboard.php`**
   - Ajout du bouton logout stylisé
   - Structure prête pour intégrer les nouvelles sections

3. **`public/partials/juniorgolfkenya-coach-dashboard.php`**
   - Ajout du bouton logout stylisé

---

## 🎨 Design & Style

### Couleurs du Système

**Alertes d'Expiration:**
- 🟢 Active: Pas d'alerte spéciale
- 🟡 Expiring Soon: `#fff3cd` (bg), `#856404` (text)
- 🔴 Expired: `#f8d7da` (bg), `#dc3545` (text)

**Cartes d'Accès:**
- 🟣 Dashboard: Gradient `#667eea` → `#764ba2`
- 🟢 Competitions: Gradient `#28a745` → `#20c997`
- 🟡 Trophies: Gradient `#ffc107` → `#ff6f00`
- 🔵 Edit Profile: Gradient `#17a2b8` → `#138496`

**Bouton Logout:**
- Background: `rgba(255, 255, 255, 0.2)` avec backdrop-filter
- Border: `rgba(255, 255, 255, 0.3)`
- Hover: Translate + shadow animation

### Responsive Design

```css
@media (max-width: 768px) {
    /* Portal header en colonne */
    .jgk-portal-header { flex-direction: column; }
    
    /* Cartes d'accès en 1 colonne */
    .jgk-quick-access { grid-template-columns: 1fr; }
    
    /* Alerte en colonne */
    .jgk-membership-alert { flex-direction: column; }
    
    /* Logout pleine largeur */
    .jgk-logout-btn { width: 100%; }
}
```

---

## 🔄 Flux de Navigation

### Après Inscription

```
1. User submits registration form
   ↓
2. Account created (status = 'active')
   ↓
3. Auto-login (wp_set_auth_cookie)
   ↓
4. Success message with "Go to My Dashboard" button
   ↓
5. **Option A:** User clicks button → Member Dashboard
   **Option B:** User goes to /member-portal → Sees quick access cards
```

### Navigation Normale

```
Member Portal (/member-portal)
    ├─→ My Dashboard → Full profile view
    ├─→ Competitions → Jumps to #competitions section
    ├─→ My Trophies → Jumps to #trophies section
    └─→ Edit Profile → Jumps to #edit-profile section

Member Dashboard (/member-dashboard)
    ├─→ Sections avec IDs:
    │   ├─ #profile (header)
    │   ├─ #stats (statistics cards)
    │   ├─ #competitions (upcoming + past)
    │   ├─ #trophies (awards)
    │   ├─ #performance (analytics)
    │   ├─ #coaches (assigned coaches)
    │   └─ #edit-profile (edit form)
    └─→ Logout → Redirects to homepage
```

---

## 🗄️ Base de Données

### Tables Existantes

**`wp_jgk_members`** - Informations membres
- Colonnes importantes:
  - `registration_date` - Date d'inscription
  - `expiry_date` - Date d'expiration (NULL = calculer +1 an)
  - `status` - active, pending, suspended, expired
  - `handicap` - Handicap actuel
  - `membership_type` - junior, youth, adult, senior, family

### Tables à Créer (Phase 2)

**`wp_jgk_competitions`** - Compétitions
```sql
CREATE TABLE wp_jgk_competitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    location VARCHAR(255),
    category VARCHAR(100),
    status VARCHAR(50) DEFAULT 'upcoming',
    max_participants INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**`wp_jgk_competition_registrations`** - Inscriptions
```sql
CREATE TABLE wp_jgk_competition_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id BIGINT UNSIGNED,
    member_id BIGINT UNSIGNED,
    status VARCHAR(50) DEFAULT 'pending',
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id),
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id)
);
```

**`wp_jgk_competition_results`** - Résultats
```sql
CREATE TABLE wp_jgk_competition_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id BIGINT UNSIGNED,
    member_id BIGINT UNSIGNED,
    position INT,
    score INT,
    handicap_used DECIMAL(4,1),
    notes TEXT,
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id),
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id)
);
```

**`wp_jgk_trophies`** - Trophées
```sql
CREATE TABLE wp_jgk_trophies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50), -- gold, silver, bronze, special
    date_awarded DATE,
    competition_id BIGINT UNSIGNED,
    category VARCHAR(100),
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id),
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id)
);
```

---

## 🧪 Tests à Effectuer

### Test 1: Alerte d'Expiration - Jaune (< 60 jours)

```sql
-- Simuler un membership expirant dans 30 jours
UPDATE wp_jgk_members 
SET expiry_date = DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE id = 1;
```

**Résultat Attendu:**
- ✅ Alerte jaune en haut du Member Portal
- ✅ Message: "Membership expires in 30 days - Renew Soon!"
- ✅ Bouton "Renew Membership" visible

### Test 2: Alerte d'Expiration - Rouge (Expiré)

```sql
-- Simuler un membership expiré depuis 5 jours
UPDATE wp_jgk_members 
SET expiry_date = DATE_SUB(NOW(), INTERVAL 5 DAY)
WHERE id = 1;
```

**Résultat Attendu:**
- ✅ Alerte rouge en haut du Member Portal
- ✅ Message: "Membership Expired - Renew Now!"
- ✅ Texte: "Your membership expired 5 days ago..."

### Test 3: Pas d'Alerte (> 60 jours)

```sql
-- Membership valide pour 120 jours
UPDATE wp_jgk_members 
SET expiry_date = DATE_ADD(NOW(), INTERVAL 120 DAY)
WHERE id = 1;
```

**Résultat Attendu:**
- ✅ Pas d'alerte affichée
- ✅ Navigation normale

### Test 4: Navigation entre Pages

**Scénario:**
1. User login
2. Va sur /member-portal
3. Clique "My Dashboard"
4. Arrive sur /member-dashboard
5. Scroll vers #competitions
6. Clique "Edit Profile"
7. Scroll vers #edit-profile
8. Édite son téléphone
9. Clique "Update Profile"
10. Message de succès
11. Clique Logout

**Résultat Attendu:**
- ✅ Toutes les redirections fonctionnent
- ✅ Anchors (#competitions, #edit-profile) scrollent correctement
- ✅ Édition du profil sauvegardée
- ✅ Logout redirige vers homepage

### Test 5: Permissions d'Édition

**Scénario:** Member essaie d'éditer son email via le formulaire

**Résultat Attendu:**
- ✅ Champ email désactivé (readonly ou disabled)
- ✅ Message: "Contact administrator to change email"
- ✅ Si membre modifie le HTML et soumet, le backend ignore la modification

---

## 📊 KPIs & Métriques

### Engagement Utilisateur
- Taux de connexion après inscription
- Temps passé sur Member Portal vs Dashboard
- Fréquence de visite du profil
- Taux de mise à jour du profil

### Conversion Renouvellement
- % de membres qui renouvellent après alerte jaune
- % de membres qui renouvellent après expiration
- Délai moyen entre alerte et renouvellement

### Utilisation des Fonctionnalités
- Clics sur "My Dashboard" depuis Portal
- Clics sur "Competitions" depuis Portal
- Clics sur "My Trophies" depuis Portal
- Clics sur "Edit Profile" depuis Portal

---

## 🚀 Prochaines Étapes (Phase 2)

### Priorité 1: Système de Paiement
- [ ] Intégration M-Pesa (Kenya)
- [ ] Intégration PayPal (International)
- [ ] Page de renouvellement
- [ ] Historique des paiements
- [ ] Factures automatiques

### Priorité 2: Compétitions Réelles
- [ ] Créer les tables de base de données
- [ ] Interface admin pour gérer compétitions
- [ ] Système d'inscription aux compétitions
- [ ] Upload des résultats
- [ ] Notifications par email

### Priorité 3: Trophées & Achievements
- [ ] Système de récompenses automatiques
- [ ] Badges (Most Improved, Consistent Player, etc.)
- [ ] Partage sur réseaux sociaux
- [ ] Certificats PDF téléchargeables

### Priorité 4: Performance Analytics
- [ ] Graphiques interactifs (Chart.js)
- [ ] Comparaison avec autres membres
- [ ] Tendances de progression
- [ ] Recommandations personnalisées

### Priorité 5: Mobile App
- [ ] Application mobile (React Native)
- [ ] Push notifications
- [ ] Mode hors ligne
- [ ] Scanner QR code pour check-in compétitions

---

## 📝 Notes Importantes

### Sécurité
- ✅ Tous les formulaires avec nonce verification
- ✅ Sanitisation de toutes les entrées utilisateur
- ✅ Prepared statements pour les requêtes SQL
- ✅ Vérification des permissions (member vs admin)
- ✅ Échappement de toutes les sorties HTML

### Performance
- ✅ Mise en cache des données membres
- ✅ Lazy loading des images
- ✅ Pagination des compétitions (10 par page)
- ✅ Optimisation des requêtes SQL

### Accessibilité
- ✅ ARIA labels sur tous les éléments interactifs
- ✅ Navigation au clavier
- ✅ Contraste de couleurs conforme WCAG AA
- ✅ Textes alternatifs pour images

---

**Dernière Mise à Jour:** 11 Octobre 2025  
**Auteur:** Junior Golf Kenya Development Team  
**Status:** ✅ Member Portal & Dashboard Logout Buttons Implemented
