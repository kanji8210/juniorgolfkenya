# 🎯 GUIDE RAPIDE - Passer à la Phase 2

**Status Phase 1:** ✅ TERMINÉE  
**Status Phase 2:** 📝 PRÊTE À DÉMARRER

---

## ✅ Ce Qui Fonctionne Maintenant

```
INSCRIPTION → AUTO-LOGIN → MEMBER PORTAL → DASHBOARD
    ↓             ↓              ↓              ↓
  Form        Connecté       4 Cards       Profil complet
``` 

### tst Rapide (5 minutes)
1. Aller sur `/member-registration`
2. Remplir formulaire + choisir mot de passe
3. Cliquer "Register"
4. ✅ **Redirection automatique vers `/member-portal`**
5. ✅ Voir message "Welcome, [Nom]!"
6. ✅ Voir 4 cartes colorées
7. Cliquer "My Dashboard" (carte violette)
8. ✅ Voir profil complet

---

## 🚀 Démarrer Phase 2 - Étapes Simples

### ÉTAPE 1: Ajouter colonne expiry_date (5 min)

**Dans phpMyAdmin ou terminal MySQL:**

```sql
USE wordpress_db; -- Remplacer par votre nom de DB

ALTER TABLE wp_jgk_members 
ADD COLUMN IF NOT EXISTS expiry_date DATE NULL AFTER registration_date,
ADD INDEX idx_expiry (expiry_date);
```

**Vérifier:**
```sql
DESCRIBE wp_jgk_members;
-- Doit voir colonne expiry_date de type DATE
```

---

### ÉTAPE 2: Tester Alerte d'Expiration (3 min)

**Simuler membership expirant bientôt:**
```sql
UPDATE wp_jgk_members 
SET expiry_date = DATE_ADD(NOW(), INTERVAL 30 DAY)
WHERE id = 1; -- Remplacer par votre member ID
```

**Vérifier:**
- Aller sur `/member-portal`
- ✅ Doit voir alerte **JAUNE** en haut
- ✅ Message: "Membership expires in 30 days"

**Simuler membership expiré:**
```sql
UPDATE wp_jgk_members 
SET expiry_date = DATE_SUB(NOW(), INTERVAL 10 DAY)
WHERE id = 1;
```

**Vérifier:**
- Rafraîchir `/member-portal`
- ✅ Doit voir alerte **ROUGE** en haut
- ✅ Message: "Membership Expired - Renew Now!"

**Remettre à normal (1 an):**
```sql
UPDATE wp_jgk_members 
SET expiry_date = DATE_ADD(NOW(), INTERVAL 365 DAY)
WHERE id = 1;
```

---

### ÉTAPE 3: Créer Tables Compétitions (10 min)

**Copiez-collez dans phpMyAdmin:**

```sql
-- Table: Compétitions
CREATE TABLE IF NOT EXISTS wp_jgk_competitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    location VARCHAR(255),
    category VARCHAR(100),
    status VARCHAR(50) DEFAULT 'upcoming',
    max_participants INT,
    entry_fee DECIMAL(10,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Inscriptions
CREATE TABLE IF NOT EXISTS wp_jgk_competition_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id BIGINT UNSIGNED NOT NULL,
    member_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_status VARCHAR(50) DEFAULT 'pending',
    UNIQUE KEY unique_registration (competition_id, member_id),
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Résultats
CREATE TABLE IF NOT EXISTS wp_jgk_competition_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id BIGINT UNSIGNED NOT NULL,
    member_id BIGINT UNSIGNED NOT NULL,
    position INT,
    score INT,
    handicap_used DECIMAL(4,1),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_result (competition_id, member_id),
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Trophées
CREATE TABLE IF NOT EXISTS wp_jgk_trophies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    date_awarded DATE NOT NULL,
    competition_id BIGINT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id) ON DELETE CASCADE,
    FOREIGN KEY (competition_id) REFERENCES wp_jgk_competitions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: Paiements
CREATE TABLE IF NOT EXISTS wp_jgk_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    payment_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100) UNIQUE,
    status VARCHAR(50) DEFAULT 'pending',
    transaction_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES wp_jgk_members(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_reference (payment_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Vérifier:**
```sql
SHOW TABLES LIKE 'wp_jgk_%';
-- Doit voir 9 tables au total maintenant
```

---

### ÉTAPE 4: Ajouter Données d'Exemple (5 min)

**Compétitions d'exemple:**
```sql
INSERT INTO wp_jgk_competitions (name, description, date, location, category, status, max_participants, entry_fee)
VALUES
('Junior Golf Kenya Championship 2025', 'Annual championship for all junior members', '2025-11-15', 'Karen Country Club', 'Championship', 'upcoming', 50, 1500.00),
('Youth Open Tournament', 'Open tournament for youth category', '2025-11-22', 'Muthaiga Golf Club', 'Open', 'upcoming', 60, 1000.00),
('Spring Classic 2025', 'Classic spring tournament', '2025-12-01', 'Windsor Golf Club', 'Classic', 'upcoming', 40, 1200.00);
```

**Trophées d'exemple (pour member_id = 1):**
```sql
INSERT INTO wp_jgk_trophies (member_id, name, type, date_awarded, competition_id)
VALUES
(1, 'Spring Open Champion 2024', 'gold', '2024-09-15', NULL),
(1, 'Most Improved Player 2024', 'special', '2024-08-20', NULL);
```

**Vérifier:**
```sql
SELECT * FROM wp_jgk_competitions;
SELECT * FROM wp_jgk_trophies WHERE member_id = 1;
```

---

### ÉTAPE 5: Mettre à Jour Member Data Class (DÉJÀ FAIT ✅)

Le fichier `includes/class-juniorgolfkenya-member-data.php` est déjà créé avec:
- ✅ `get_membership_status()` - Vérifie expiration
- ✅ `get_upcoming_competitions()` - Liste compétitions
- ✅ `get_past_competitions()` - Résultats passés
- ✅ `get_trophies()` - Trophées du membre
- ✅ `get_performance_stats()` - Statistiques

**Modifier pour utiliser vraies données:**

Ouvrir: `includes/class-juniorgolfkenya-member-data.php`

**Fonction `get_upcoming_competitions()` ligne ~105:**
```php
// REMPLACER le sample data par:
public static function get_upcoming_competitions($member_id, $limit = 5) {
    global $wpdb;
    $competitions_table = $wpdb->prefix . 'jgk_competitions';
    
    $competitions = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM {$competitions_table}
        WHERE status = 'upcoming' AND date >= CURDATE()
        ORDER BY date ASC
        LIMIT %d
    ", $limit));
    
    return $competitions;
}
```

---

## 📊 Checklist Phase 2 Complète

### Semaine 1-2: Compétitions
- [ ] ✅ Tables SQL créées
- [ ] Interface admin pour créer compétitions
- [ ] Page frontend liste compétitions
- [ ] Page détails compétition
- [ ] Formulaire inscription membre
- [ ] Interface admin saisie résultats

### Semaine 3-4: Paiements
- [ ] Configuration M-Pesa API
- [ ] Page renouvellement membership
- [ ] STK Push implementation
- [ ] Callback handler
- [ ] Auto-update expiry_date sur paiement
- [ ] Page historique paiements

### Semaine 5: Trophées
- [ ] Auto-attribution après compétition
- [ ] Page galerie trophées
- [ ] Download certificats PDF
- [ ] Badges achievements

### Semaine 6: Analytics
- [ ] Graphiques Chart.js
- [ ] Page performance détaillée
- [ ] Leaderboards
- [ ] Comparaisons

---

## 💡 Ordre Recommandé de Développement

### JOUR 1: Setup Base de Données
1. ✅ Ajouter colonne expiry_date
2. ✅ Créer 5 tables (competitions, registrations, results, trophies, payments)
3. ✅ Ajouter données d'exemple
4. ✅ Tester requêtes SQL

### JOUR 2-3: Compétitions Backend
1. Créer `class-juniorgolfkenya-competitions.php`
2. Méthodes CRUD (create, read, update, delete)
3. Page admin liste compétitions
4. Page admin créer/éditer compétition

### JOUR 4-5: Compétitions Frontend
1. Page liste compétitions publique
2. Page détails compétition
3. Formulaire inscription
4. Shortcode `[jgk_competitions_list]`

### JOUR 6-7: Paiements M-Pesa
1. Configuration Daraja API
2. Class `class-juniorgolfkenya-mpesa.php`
3. STK Push implementation
4. Page renouvellement
5. Auto-update membership

### JOUR 8-9: Trophées & Achievements
1. Auto-attribution trophées
2. Page galerie trophées
3. Système badges
4. Certificats PDF

### JOUR 10: Analytics & Charts
1. Graphiques performance
2. Leaderboards
3. Comparaisons
4. Export données

---

## 🎯 Prochaine Action Immédiate

**MAINTENANT:**
1. ✅ Tester inscription → Confirmer redirection fonctionne
2. ✅ Ajouter colonne `expiry_date` dans MySQL
3. ✅ Tester alerte jaune/rouge
4. ✅ Créer 5 tables SQL
5. ✅ Ajouter données d'exemple

**ENSUITE (Semaine prochaine):**
1. Créer interface admin compétitions
2. Créer page frontend compétitions
3. Implémenter inscriptions
4. Commencer M-Pesa

---

## 📞 Aide Rapide

**Problème redirection:**
```php
// Tester dans registration form après ligne 215
error_log('Redirect URL: ' . $redirect_url);
```

**Problème tables:**
```sql
-- Vérifier foreign keys
SHOW CREATE TABLE wp_jgk_competition_registrations;
```

**Problème données:**
```php
// Tester dans member portal
$comps = JuniorGolfKenya_Member_Data::get_upcoming_competitions(1);
var_dump($comps);
```

---

**🎉 VOUS ÊTES PRÊT POUR PHASE 2 !**

**Temps estimé:** 2-3 semaines pour Phase 2 complète  
**Prochaine session:** Configuration M-Pesa + Interface Compétitions

**BONNE CHANCE ! 🚀**
