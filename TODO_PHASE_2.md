# 📋 TODO - Phase 2: Compétitions, Paiements & Performance

**Date Début:** À définir  
**Priorité:** Haute  
**Statut:** 📝 Planifié

---

## 🗄️ BASE DE DONNÉES

### ✅ Tables Existantes
- [x] `wp_jgk_members` - Membres
- [x] `wp_jgk_parents_guardians` - Parents/tuteurs
- [x] `wp_jgk_coach_members` - Relations coach-membre
- [x] `wp_jgk_role_requests` - Demandes de rôle coach

### 📝 Tables à Créer

#### 1. Compétitions
```sql
CREATE TABLE wp_jgk_competitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    end_date DATE,
    location VARCHAR(255),
    venue_details TEXT,
    category VARCHAR(100), -- junior, youth, adult, open
    format VARCHAR(50), -- stroke play, match play, scramble
    status VARCHAR(50) DEFAULT 'upcoming', -- upcoming, ongoing, completed, cancelled
    max_participants INT,
    registration_deadline DATE,
    entry_fee DECIMAL(10,2) DEFAULT 0,
    created_by BIGINT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2. Inscriptions aux Compétitions
```sql
CREATE TABLE wp_jgk_competition_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id BIGINT UNSIGNED NOT NULL,
    member_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) DEFAULT 'pending', -- pending, confirmed, cancelled, waitlist
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    handicap_at_registration DECIMAL(4,1),
    payment_status VARCHAR(50) DEFAULT 'pending', -- pending, paid, refunded
    payment_reference VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (competition_id, member_id),
    INDEX idx_member (member_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 3. Résultats des Compétitions
```sql
CREATE TABLE wp_jgk_competition_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id BIGINT UNSIGNED NOT NULL,
    member_id BIGINT UNSIGNED NOT NULL,
    position INT,
    gross_score INT,
    net_score INT,
    handicap_used DECIMAL(4,1),
    holes_played INT DEFAULT 18,
    birdies INT DEFAULT 0,
    eagles INT DEFAULT 0,
    pars INT DEFAULT 0,
    bogeys INT DEFAULT 0,
    double_bogeys INT DEFAULT 0,
    scorecard_image VARCHAR(255),
    notes TEXT,
    verified_by BIGINT UNSIGNED,
    verified_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_result (competition_id, member_id),
    INDEX idx_position (position),
    INDEX idx_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4. Trophées & Récompenses
```sql
CREATE TABLE wp_jgk_trophies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL, -- gold, silver, bronze, special, achievement
    description TEXT,
    date_awarded DATE NOT NULL,
    competition_id BIGINT UNSIGNED,
    category VARCHAR(100),
    trophy_image VARCHAR(255),
    certificate_pdf VARCHAR(255),
    awarded_by BIGINT UNSIGNED,
    display_order INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id) ON DELETE CASCADE,
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id) ON DELETE SET NULL,
    INDEX idx_member (member_id),
    INDEX idx_type (type),
    INDEX idx_date (date_awarded)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 5. Paiements
```sql
CREATE TABLE wp_jgk_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    payment_type VARCHAR(50) NOT NULL, -- membership, competition, other
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    payment_method VARCHAR(50), -- mpesa, paypal, bank, cash
    payment_reference VARCHAR(100) UNIQUE,
    mpesa_receipt VARCHAR(100),
    status VARCHAR(50) DEFAULT 'pending', -- pending, completed, failed, refunded
    related_id BIGINT UNSIGNED, -- competition_id or membership renewal
    description TEXT,
    transaction_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id) ON DELETE CASCADE,
    INDEX idx_member (member_id),
    INDEX idx_status (status),
    INDEX idx_reference (payment_reference),
    INDEX idx_type (payment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 6. Activités / Logs
```sql
CREATE TABLE wp_jgk_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    activity_type VARCHAR(50) NOT NULL, -- login, profile_update, competition_join, payment, etc.
    description TEXT,
    metadata JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (activity_type),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🔧 MODIFICATIONS DES TABLES EXISTANTES

### wp_jgk_members
```sql
-- Ajouter colonne expiry_date si manquante
ALTER TABLE wp_jgk_members 
ADD COLUMN IF NOT EXISTS expiry_date DATE NULL AFTER registration_date;

-- Ajouter index pour performance
ALTER TABLE wp_jgk_members 
ADD INDEX IF NOT EXISTS idx_expiry (expiry_date);

-- Ajouter colonnes pour statistiques (calculées périodiquement)
ALTER TABLE wp_jgk_members 
ADD COLUMN IF NOT EXISTS total_competitions INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_wins INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS best_score INT,
ADD COLUMN IF NOT EXISTS last_competition_date DATE,
ADD COLUMN IF NOT EXISTS stats_updated_at DATETIME;
```

---

## 💻 DÉVELOPPEMENT BACKEND

### Phase 2.1: Compétitions (Semaine 1-2)

#### Admin Interface
- [ ] Page liste des compétitions
  - [ ] Tableau avec filtres (status, date, category)
  - [ ] Actions: Create, Edit, Delete, View registrations
  - [ ] Pagination & search
  
- [ ] Page création/édition compétition
  - [ ] Formulaire complet avec validation
  - [ ] Upload image de l'événement
  - [ ] Paramètres d'inscription
  - [ ] Gestion des catégories

- [ ] Page gestion des inscriptions
  - [ ] Liste des inscrits par compétition
  - [ ] Accepter/Refuser inscriptions
  - [ ] Voir statut de paiement
  - [ ] Export CSV des participants

- [ ] Page saisie des résultats
  - [ ] Formulaire pour entrer scores
  - [ ] Calcul automatique net score
  - [ ] Attribution automatique des positions
  - [ ] Upload scorecard images
  - [ ] Validation des résultats

#### Frontend Member
- [ ] Page liste des compétitions
  - [ ] Filtres: upcoming, past, my competitions
  - [ ] Cards avec infos essentielles
  - [ ] Bouton "Register"
  
- [ ] Page détails compétition
  - [ ] Informations complètes
  - [ ] Liste des inscrits (si public)
  - [ ] Formulaire d'inscription
  - [ ] Conditions de participation

- [ ] Mes inscriptions
  - [ ] Liste de mes compétitions
  - [ ] Statut d'inscription
  - [ ] Annulation possible (selon deadline)
  - [ ] Rappels avant événement

#### API/Functions
```php
// includes/class-juniorgolfkenya-competitions.php
class JuniorGolfKenya_Competitions {
    public static function get_upcoming_competitions($filters = []);
    public static function get_past_competitions($filters = []);
    public static function get_competition($id);
    public static function create_competition($data);
    public static function update_competition($id, $data);
    public static function delete_competition($id);
    public static function register_member($competition_id, $member_id);
    public static function cancel_registration($registration_id);
    public static function get_registrations($competition_id);
    public static function add_results($competition_id, $results_data);
    public static function get_results($competition_id);
    public static function get_member_results($member_id);
}
```

---

### Phase 2.2: Paiements (Semaine 3-4)

#### M-Pesa Integration
- [ ] Configuration API Daraja
  - [ ] Consumer Key & Secret
  - [ ] Passkey & Shortcode
  - [ ] Callback URL setup
  
- [ ] STK Push Implementation
  ```php
  // includes/class-juniorgolfkenya-mpesa.php
  class JuniorGolfKenya_Mpesa {
      public static function initiate_stk_push($phone, $amount, $reference);
      public static function handle_callback($data);
      public static function query_transaction($checkout_request_id);
  }
  ```

- [ ] Payment workflow
  - [ ] Membership renewal page
  - [ ] Competition entry fee payment
  - [ ] Payment confirmation page
  - [ ] Automatic receipt generation

#### PayPal Integration (International)
- [ ] PayPal SDK setup
- [ ] Payment buttons
- [ ] IPN handler
- [ ] Currency conversion

#### Payment Management
- [ ] Admin: View all payments
  - [ ] Filter by status, type, date
  - [ ] Manual payment entry
  - [ ] Refund processing
  - [ ] Export reports

- [ ] Member: Payment history
  - [ ] List of all payments
  - [ ] Download receipts
  - [ ] Payment status tracking

#### Automated Processes
- [ ] Auto-renew membership on payment
  - [ ] Update expiry_date
  - [ ] Update status to 'active'
  - [ ] Send confirmation email
  
- [ ] Auto-confirm competition registration
  - [ ] Update registration status
  - [ ] Send confirmation email
  - [ ] Add to calendar reminder

---

### Phase 2.3: Trophées & Achievements (Semaine 5)

#### Admin Interface
- [ ] Page gestion des trophées
  - [ ] Award trophy manually
  - [ ] Upload trophy/certificate images
  - [ ] Edit trophy details
  - [ ] Publish/Unpublish

#### Automated Trophies
- [ ] Trigger après compétition
  ```php
  // Automatic trophy assignment
  if ($position == 1) {
      award_trophy($member_id, 'gold', $competition_id);
  }
  if ($position == 2) {
      award_trophy($member_id, 'silver', $competition_id);
  }
  if ($position == 3) {
      award_trophy($member_id, 'bronze', $competition_id);
  }
  ```

- [ ] Achievement badges
  - [ ] Most Improved (handicap reduction)
  - [ ] Consistent Player (4+ competitions)
  - [ ] Perfect Attendance (all competitions)
  - [ ] Eagle Master (3+ eagles in season)
  - [ ] Streak Master (3+ consecutive top-10s)

#### Frontend Display
- [ ] Trophy cabinet page
  - [ ] Grid display avec images
  - [ ] Filter par type, année
  - [ ] Sort par date
  
- [ ] Trophy details modal
  - [ ] Full info
  - [ ] Download certificate
  - [ ] Share on social media

---

### Phase 2.4: Performance Analytics (Semaine 6)

#### Data Collection
- [ ] Aggregate stats after each competition
  ```php
  function update_member_stats($member_id) {
      // Recalculate all stats
      $total_competitions = count_competitions($member_id);
      $wins = count_wins($member_id);
      $best_score = get_best_score($member_id);
      $avg_score = calculate_average_score($member_id);
      // ... update wp_jgk_members
  }
  ```

- [ ] Handicap tracking
  - [ ] Store handicap history
  - [ ] Calculate trend
  - [ ] Graph over time

#### Charts & Graphs (Chart.js)
- [ ] Score progression line chart
- [ ] Handicap evolution
- [ ] Competition participation bar chart
- [ ] Win/Loss pie chart
- [ ] Position distribution histogram

#### Comparisons
- [ ] Compare with club average
- [ ] Compare with age group
- [ ] Leaderboards (overall, by category)

---

## 🎨 FRONTEND DESIGN

### Pages à Créer
- [ ] `/competitions` - Liste publique
- [ ] `/competition/{id}` - Détails & inscription
- [ ] `/my-competitions` - Mes inscriptions
- [ ] `/leaderboard` - Classements
- [ ] `/trophy-cabinet` - Galerie de trophées
- [ ] `/renew-membership` - Paiement renouvellement
- [ ] `/payment-history` - Historique paiements
- [ ] `/performance` - Analytics détaillées

### Shortcodes à Créer
```php
[jgk_competitions_list] // Liste des compétitions
[jgk_competition_details id="123"] // Détails compétition
[jgk_registration_form competition="123"] // Formulaire inscription
[jgk_my_competitions] // Mes compétitions
[jgk_leaderboard] // Classement général
[jgk_trophy_cabinet] // Mes trophées
[jgk_payment_form type="membership"] // Formulaire paiement
[jgk_payment_history] // Historique paiements
[jgk_performance_chart] // Graphiques performance
```

---

## 📧 NOTIFICATIONS EMAIL

### Templates à Créer
- [ ] Competition registration confirmation
- [ ] Competition reminder (1 week before)
- [ ] Competition results notification
- [ ] Trophy awarded notification
- [ ] Payment confirmation
- [ ] Membership renewed confirmation
- [ ] Membership expiring soon (60 days, 30 days, 7 days)
- [ ] Membership expired notification

---

## 🧪 TESTS

### Test Compétitions
- [ ] Create competition (admin)
- [ ] Edit competition (admin)
- [ ] Delete competition (admin)
- [ ] Register for competition (member)
- [ ] Cancel registration (member)
- [ ] Enter results (admin)
- [ ] View results (public)
- [ ] Filter/search competitions

### Test Paiements
- [ ] M-Pesa STK push
- [ ] M-Pesa callback handling
- [ ] PayPal payment
- [ ] Payment confirmation
- [ ] Auto-renewal on payment
- [ ] Payment history display
- [ ] Receipt generation

### Test Trophées
- [ ] Manual trophy award
- [ ] Automatic trophy on win
- [ ] Trophy display in cabinet
- [ ] Certificate download
- [ ] Social sharing

### Test Performance
- [ ] Stats calculation correctness
- [ ] Charts display properly
- [ ] Trend calculation
- [ ] Leaderboard accuracy

---

## 📱 MOBILE OPTIMIZATION

- [ ] Responsive design toutes nouvelles pages
- [ ] Touch-friendly buttons & forms
- [ ] Mobile payment flow
- [ ] Mobile-optimized charts
- [ ] Swipe gestures pour galleries

---

## 🔐 SÉCURITÉ

- [ ] Nonce verification tous formulaires
- [ ] Input sanitization & validation
- [ ] SQL injection protection (prepared statements)
- [ ] XSS prevention (esc_html, esc_attr, etc.)
- [ ] CSRF protection
- [ ] Rate limiting sur API calls
- [ ] Payment data encryption
- [ ] PCI DSS compliance (si stockage carte)

---

## 🚀 DÉPLOIEMENT

### Pré-déploiement
- [ ] Backup complet base de données
- [ ] Test sur staging environment
- [ ] Migration script pour nouvelles tables
- [ ] Data migration script (si nécessaire)
- [ ] Update documentation

### Post-déploiement
- [ ] Monitor error logs
- [ ] Test all payment flows
- [ ] Verify email delivery
- [ ] Check performance metrics
- [ ] User acceptance testing

---

## 📊 METRIQUES DE SUCCÈS

### Engagement
- [ ] % members registered for competitions
- [ ] Average competitions per member
- [ ] Trophy cabinet views
- [ ] Performance page visits

### Conversion
- [ ] Membership renewal rate
- [ ] Payment completion rate
- [ ] Competition registration conversion
- [ ] Time to renew after alert

### Satisfaction
- [ ] User feedback surveys
- [ ] Support ticket volume
- [ ] Feature usage analytics

---

## 🎯 PRIORITÉS

### MUST HAVE (Critique)
1. ✅ Tables de base de données créées
2. ✅ Système de compétitions fonctionnel
3. ✅ Paiement M-Pesa intégré
4. ✅ Auto-renewal membership sur paiement

### SHOULD HAVE (Important)
1. Trophées automatiques
2. Performance analytics basiques
3. Email notifications
4. Payment history

### NICE TO HAVE (Bonus)
1. PayPal integration
2. Advanced charts
3. Social sharing
4. Mobile app

---

## 📅 TIMELINE ESTIMÉ

```
Semaine 1-2:  Compétitions (admin + frontend)
Semaine 3-4:  Paiements M-Pesa + Auto-renewal
Semaine 5:    Trophées & Achievements
Semaine 6:    Performance Analytics
Semaine 7:    Tests & Bug Fixes
Semaine 8:    Déploiement Production
```

**Total: ~2 mois**

---

**Status:** 📝 Ready to Start  
**Next Action:** Créer les tables SQL et commencer Phase 2.1
