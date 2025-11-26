# Guide Rapide : Configuration du Parent Dashboard

## 📋 Étapes de configuration

### 1️⃣ Créer la page WordPress (1 minute)

1. Dans WordPress admin, aller à **Pages → Ajouter**
2. Titre : `Parent Dashboard`
3. Dans l'éditeur, ajouter le shortcode :
   ```
   [jgk_parent_dashboard]
   ```
4. Slug URL recommandé : `parent-dashboard`
5. **Publier** la page

### 2️⃣ Ajouter au menu (optionnel)

1. Aller à **Apparence → Menus**
2. Ajouter la page "Parent Dashboard" au menu
3. Ou créer un menu spécial "Member Area" avec :
   - Member Dashboard
   - Parent Dashboard
   - Coach Dashboard

### 3️⃣ Test rapide

#### Créer un parent de test :
```sql
-- Dans phpMyAdmin ou terminal MySQL
INSERT INTO wp_jgk_parents_guardians 
(member_id, email, first_name, last_name, relationship, phone, is_primary_contact, created_at)
VALUES 
(1, 'parent@test.com', 'John', 'Doe', 'Parent', '+254712345678', 1, NOW());
```

#### Créer un compte WordPress pour le parent :
1. **Utilisateurs → Ajouter**
2. Email : `parent@test.com` (doit correspondre à celui dans jgk_parents_guardians)
3. Rôle : Subscriber ou custom role
4. Envoyer l'email de notification

#### Se connecter et tester :
1. Se connecter avec `parent@test.com`
2. Aller sur `/parent-dashboard`
3. Le tableau de bord devrait afficher l'enfant lié (member_id = 1)

---

## 🔄 Flux de travail normal

### Scénario : Parent inscrit 2 enfants

#### Étape 1 : Inscription des enfants
Le parent remplit le formulaire d'inscription 2 fois (une fois par enfant) en utilisant **son propre email** à chaque fois.

Résultat dans la base :
```
jgk_members:
- id: 5, first_name: 'Alice', last_name: 'Smith', status: 'pending'
- id: 6, first_name: 'Bob', last_name: 'Smith', status: 'pending'

jgk_parents_guardians:
- member_id: 5, email: 'parent@smith.com', first_name: 'Jane', last_name: 'Smith'
- member_id: 6, email: 'parent@smith.com', first_name: 'Jane', last_name: 'Smith'
```

#### Étape 2 : Admin approuve
1. Admin va dans **Members** (plugin admin)
2. Change le statut de Alice de `pending` → `approved`
3. Change le statut de Bob de `pending` → `approved`

#### Étape 3 : Parent se connecte
1. Parent va sur `/parent-dashboard`
2. Voit les 2 enfants avec statut "Approved"
3. Voit la bannière "2 memberships need payment"
4. Total à payer : KES 10,000

#### Étape 4 : Parent paie
Option A : **Paiement individuel**
- Cliquer "Pay Now" sur la carte d'Alice → paie pour Alice uniquement

Option B : **Paiement groupé**
- Cliquer "Pay with M-Pesa" dans la section "Pending Payments"
- Tous les enfants approuvés sont ajoutés au panier
- Payer pour les 2 en une seule transaction

#### Étape 5 : Après paiement
WooCommerce déclenche le webhook qui :
1. Détecte le paiement complété
2. Change le statut de `approved` → `active`
3. Parent voit maintenant :
   - Alice : badge "Active" vert
   - Bob : badge "Active" vert
   - Statistiques : "2 Active Memberships"

---

## 🎨 Personnalisation

### Changer les montants
**Fichier** : `public/partials/juniorgolfkenya-parent-dashboard.php`

```php
// Ligne ~95 (bouton individuel)
Pay Now (KES 5,000)
// Changer en :
Pay Now (KES 7,500)

// Ligne ~330 (section paiements)
<span class="jgk-amount">KES 5,000</span>
// Changer en :
<span class="jgk-amount">KES 7,500</span>
```

### Changer les couleurs
**Fichier** : `public/partials/css/juniorgolfkenya-member-dashboard.css`

```css
:root {
    --jgk-primary: #0ea57a;  /* Vert principal */
    --jgk-success: #10b981;  /* Vert succès */
    --jgk-warning: #f59e0b;  /* Orange */
    --jgk-danger: #dc2626;   /* Rouge */
}
```

### Textes et labels
Tous les textes sont dans `juniorgolfkenya-parent-dashboard.php` et peuvent être modifiés directement :
- Ligne 44 : `Welcome, <?php echo ...`
- Ligne 54 : `Parent / Guardian`
- Ligne 70 : `Payment Required`
- etc.

---

## 🐛 Dépannage rapide

### ❌ "No children registered under this account"
**Problème** : Email ne correspond pas

**Solution** :
```sql
-- Vérifier l'email du parent connecté
SELECT user_email FROM wp_users WHERE ID = [USER_ID];

-- Vérifier les enfants liés à cet email
SELECT * FROM wp_jgk_parents_guardians WHERE email = 'parent@example.com';

-- Si vide, ajouter le lien :
INSERT INTO wp_jgk_parents_guardians (member_id, email, first_name, last_name, relationship)
VALUES ([MEMBER_ID], 'parent@example.com', 'FirstName', 'LastName', 'Parent');
```

### ❌ Boutons de paiement manquants
**Problème** : WooCommerce non configuré

**Solution** :
1. Vérifier que WooCommerce est activé
2. Créer un produit "Junior Membership" (KES 5,000)
3. Dans **Settings → JGK Settings**, définir l'ID du produit

### ❌ Statistiques incorrectes
**Problème** : Données incohérentes

**Solution** :
```sql
-- Vérifier les statuts
SELECT status, COUNT(*) as count 
FROM wp_jgk_members 
GROUP BY status;

-- Vérifier les paiements
SELECT m.id, m.first_name, m.status, SUM(p.amount) as total_paid
FROM wp_jgk_members m
LEFT JOIN wp_jgk_payments p ON m.id = p.member_id
GROUP BY m.id;
```

---

## 📞 Support

### Si problème persiste :
1. Vérifier les logs PHP : `wp-content/debug.log`
2. Activer le mode debug dans `wp-config.php` :
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
3. Contacter le développeur avec :
   - Message d'erreur exact
   - Étapes pour reproduire
   - Screenshots

---

## ✅ Checklist de mise en production

- [ ] Page "Parent Dashboard" créée avec shortcode `[jgk_parent_dashboard]`
- [ ] Page testée avec un parent ayant plusieurs enfants
- [ ] WooCommerce configuré avec produit "Junior Membership"
- [ ] Passerelle de paiement M-Pesa configurée
- [ ] Email de confirmation de paiement testé
- [ ] Transitions de statut testées : pending → approved → active
- [ ] Design responsive testé sur mobile/tablet
- [ ] Menu mis à jour avec lien vers parent dashboard
- [ ] Documentation fournie aux utilisateurs

---

**Note** : Le routage automatique fonctionne ! Si un parent accède à `/member-dashboard`, il sera automatiquement redirigé vers le parent dashboard. Vous n'avez donc besoin que d'un seul lien "Dashboard" dans le menu.
