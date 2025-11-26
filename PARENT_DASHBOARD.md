# Parent Dashboard - Documentation

## Vue d'ensemble

Le **Parent Dashboard** est une nouvelle fonctionnalité qui permet aux parents/tuteurs de gérer plusieurs membres juniors depuis un seul tableau de bord unifié.

## Problème résolu

Les juniors n'ont généralement pas leur propre adresse email. Les parents utilisent leur email pour enregistrer et gérer plusieurs enfants membres. Cette fonctionnalité permet :
- Un parent avec un email peut gérer plusieurs enfants juniors
- Vue consolidée de tous les enfants et leurs statuts
- Gestion centralisée des paiements pour tous les enfants
- Suivi individuel de chaque enfant

## Architecture

### Fichiers créés/modifiés

#### Nouveaux fichiers
1. **`includes/class-juniorgolfkenya-parent-dashboard.php`**
   - Classe backend pour la logique du tableau de bord parent
   - Méthodes principales :
     - `get_parent_children($parent_email)` - Récupère tous les enfants d'un parent
     - `get_child_stats($member_id)` - Statistiques pour un enfant
     - `get_payment_summary($parent_email)` - Résumé des paiements
     - `is_parent($email)` - Vérifie si un email est parent

2. **`public/partials/juniorgolfkenya-parent-dashboard.php`**
   - Vue frontend du tableau de bord parent
   - Affiche tous les enfants dans une grille responsive
   - Sections : en-tête, statistiques, cartes enfants, paiements en attente

#### Fichiers modifiés
1. **`public/class-juniorgolfkenya-public.php`**
   - Ajout du shortcode `[jgk_parent_dashboard]`
   - Logique de routage automatique : si un utilisateur est parent, il est redirigé vers le parent dashboard
   - Méthode `parent_dashboard_shortcode()` ajoutée

## Base de données

### Table utilisée : `jgk_parents_guardians`

Structure :
```sql
- id (INT) - Clé primaire
- member_id (INT) - ID du membre junior (FK vers jgk_members)
- email (VARCHAR) - Email du parent
- first_name (VARCHAR) - Prénom du parent
- last_name (VARCHAR) - Nom du parent
- relationship (VARCHAR) - Relation (Parent, Guardian, etc.)
- phone (VARCHAR) - Téléphone
- mobile (VARCHAR) - Mobile
- is_primary_contact (BOOLEAN) - Contact principal
- created_at (DATETIME)
- updated_at (DATETIME)
```

### Requête clé

Pour récupérer tous les enfants d'un parent :
```php
$children = $wpdb->get_results($wpdb->prepare("
    SELECT DISTINCT m.* 
    FROM {$parents_table} p
    INNER JOIN {$members_table} m ON p.member_id = m.id
    WHERE p.email = %s
    ORDER BY m.first_name, m.last_name
", $parent_email));
```

## Utilisation

### 1. Créer une page WordPress

Créer une nouvelle page dans WordPress (ex: "Parent Dashboard")

### 2. Ajouter le shortcode

```
[jgk_parent_dashboard]
```

### 3. Configuration du lien

Dans le menu WordPress, créer un lien vers cette page pour les parents.

### 4. Routage automatique

Si un parent se connecte et accède à `[jgk_member_dashboard]`, il sera automatiquement redirigé vers le parent dashboard.

## Fonctionnalités du tableau de bord

### 1. En-tête parent
- Avatar du parent (ou initiale)
- Nom complet et email
- Badge "Parent / Guardian"
- Nombre d'enfants
- Bouton de déconnexion

### 2. Bannière de paiement
- Affichée si des enfants ont des paiements en attente
- Montant total à payer
- Bouton "View Pending Payments"

### 3. Statistiques résumées
- **Total Children** : Nombre total d'enfants
- **Active Memberships** : Enfants avec statut "active"
- **Pending Payments** : Enfants avec statut "approved" (en attente de paiement)
- **Total Paid** : Montant total payé pour tous les enfants

### 4. Grille des enfants
Chaque carte enfant affiche :
- Photo de profil ou initiale
- Nom complet
- Numéro de membre
- Badge de statut (actif/approuvé/en attente)
- Détails : âge, genre, date d'inscription, coach
- Actions :
  - **Si "approved"** : Bouton "Pay Now (KES 5,000)"
  - **Si "active"** : Badge "Membership Active" + date d'expiration
  - **Si "pending"** : Badge "Awaiting Admin Approval"
- Historique des paiements

### 5. Section Paiements en attente
- Liste des enfants nécessitant un paiement
- Montant individuel : KES 5,000/an
- Total à payer
- Boutons de paiement :
  - **M-Pesa**
  - **Card / eLipa**
- Intégration WooCommerce

### 6. Barre latérale
- **Informations parent** : nom, email, téléphone, mobile
- **Actions rapides** : 
  - Contact support
  - Download receipts
- **Aide & Support** : coordonnées JGK

## Flux de paiement

### Pour un enfant individuel
1. Parent clique sur "Pay Now" sur la carte de l'enfant
2. Redirigé vers le panier WooCommerce
3. Après paiement : statut passe de "approved" → "active"

### Pour plusieurs enfants
1. Parent clique sur "Pay with M-Pesa/Card" dans la section "Pending Payments"
2. Tous les enfants approuvés sont ajoutés au panier
3. Paiement groupé possible
4. Chaque paiement met à jour le statut individuellement

## Statuts des enfants

- **pending** : En attente d'approbation admin → Badge gris
- **approved** : Approuvé, en attente de paiement → Badge orange + bouton paiement
- **active** : Paiement effectué, membre actif → Badge vert
- **expired** : Adhésion expirée → Badge rouge
- **suspended** : Membre suspendu → Badge rouge

## Sécurité

### Vérifications
1. **Authentification** : `is_user_logged_in()` vérifie la connexion
2. **Autorisation** : `is_parent($email)` vérifie que l'utilisateur est bien un parent
3. **Nonce** : Protection CSRF pour les formulaires de paiement
4. **Échappement** : Tous les outputs sont échappés (`esc_html`, `esc_url`, `esc_attr`)

### Requêtes préparées
Toutes les requêtes SQL utilisent `$wpdb->prepare()` pour prévenir les injections SQL.

## Responsive Design

Le design est entièrement responsive :
- **Desktop** : Grille 2-3 colonnes
- **Tablet** : Grille 2 colonnes
- **Mobile** : 1 colonne

### Breakpoints
```css
@media (max-width: 768px) {
    .jgk-children-grid {
        grid-template-columns: 1fr;
    }
}
```

## Personnalisation

### Couleurs (CSS Variables)
```css
--jgk-primary: #0ea57a;
--jgk-success: #10b981;
--jgk-warning: #f59e0b;
--jgk-danger: #dc2626;
--jgk-gray: #6b7280;
```

### Montants de paiement
Montant actuellement codé en dur : KES 5,000/an

Pour changer, modifier dans :
- `juniorgolfkenya-parent-dashboard.php` lignes ~95, ~330

### Méthodes de paiement
Configurées via WooCommerce :
- M-Pesa (via plugin WooCommerce M-Pesa)
- eLipa
- Stripe
- Paiement manuel (admin)

## Intégration WooCommerce

### Prérequis
1. WooCommerce installé et activé
2. Produit "Junior Membership" créé
3. ID du produit configuré : `jgk_membership_product_id`

### Code d'intégration
```php
if (class_exists('WooCommerce')):
    $membership_product_id = get_option('jgk_membership_product_id');
    $add_to_cart_url = wc_get_cart_url() . '?add-to-cart=' . $membership_product_id;
endif;
```

## Tests

### Scénarios de test

#### Test 1 : Parent avec 1 enfant
1. Créer un parent dans `jgk_parents_guardians`
2. Lier 1 enfant (member_id) avec l'email du parent
3. Se connecter avec le compte parent
4. Vérifier affichage de 1 enfant

#### Test 2 : Parent avec plusieurs enfants
1. Créer un parent
2. Lier 3 enfants avec différents statuts :
   - Enfant 1 : `pending`
   - Enfant 2 : `approved`
   - Enfant 3 : `active`
3. Vérifier :
   - Statistiques correctes
   - Bannière de paiement (1 enfant approved)
   - Boutons appropriés selon statut

#### Test 3 : Paiement
1. Parent avec enfant `approved`
2. Cliquer "Pay Now"
3. Compléter paiement WooCommerce
4. Vérifier statut passe à `active`

#### Test 4 : Routage automatique
1. Parent se connecte
2. Accède à `/member-dashboard` (shortcode member)
3. Doit être automatiquement redirigé vers parent dashboard

## Dépannage

### Problème : "No children registered under this account"
**Cause** : Email du parent ne correspond pas à `jgk_parents_guardians.email`
**Solution** : Vérifier que l'email dans la table correspond exactement

### Problème : Enfant n'apparaît pas
**Cause** : Lien manquant dans `jgk_parents_guardians`
**Solution** : 
```sql
INSERT INTO wp_jgk_parents_guardians (member_id, email, first_name, last_name, relationship)
VALUES (123, 'parent@example.com', 'John', 'Doe', 'Parent');
```

### Problème : Boutons de paiement non affichés
**Cause** : WooCommerce non activé ou produit non configuré
**Solution** : 
1. Activer WooCommerce
2. Créer produit "Junior Membership"
3. Configurer l'option `jgk_membership_product_id`

### Problème : Statistiques incorrectes
**Cause** : Cache ou données corrompues
**Solution** : Vérifier les requêtes SQL dans la méthode `get_payment_summary()`

## Extensions futures

### Idées d'améliorations
1. **Notifications email** : Alertes parents pour paiements dus
2. **Historique complet** : Logs d'activité pour chaque enfant
3. **Rapports PDF** : Export des reçus de paiement
4. **Calendrier** : Événements et compétitions pour chaque enfant
5. **Messagerie** : Communication parent-coach
6. **Photos** : Galerie photos par enfant
7. **Certifications** : Téléchargement des certificats
8. **Paiements récurrents** : Auto-renouvellement annuel

## Support

### Contacts
- **Email** : info@juniorgolfkenya.com
- **Documentation** : [GitHub Wiki](https://github.com/kanji8210/juniorgolfkenya/wiki)
- **Issues** : [GitHub Issues](https://github.com/kanji8210/juniorgolfkenya/issues)

---

**Version** : 1.0.0  
**Date** : 26 novembre 2025  
**Auteur** : Dennis Kosgei pour PSM Consult
