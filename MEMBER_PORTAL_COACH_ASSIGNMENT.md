# Member Portal - Coach Assignment Feature

## 🎯 Objectif

Permettre aux membres de sélectionner et changer leur coach depuis l'interface frontend (non-admin).

## ✅ Solution implémentée

### Fichier créé

**`public/partials/juniorgolfkenya-member-portal.php`** - Interface frontend complète pour les membres

### Fonctionnalités

1. **Affichage des informations du membre**
   - Membership number
   - Membership type
   - Status (Active, Pending, Expired)
   - Handicap

2. **Gestion du coach**
   - Affichage du coach actuel (si assigné)
   - Dropdown pour sélectionner un nouveau coach
   - Bouton pour assigner/changer de coach
   - Email du coach affiché pour contact

3. **Mise à jour du profil**
   - Phone number
   - Emergency contact (nom + téléphone)
   - Medical conditions
   - Address

### Interface utilisateur

- Design moderne et responsive
- Cards avec ombres et bordures arrondies
- Messages de succès/erreur
- Formulaires clairs et intuitifs
- Compatible mobile

---

## 📋 Installation

### Étape 1 : Vérifier le shortcode

Le shortcode `[jgk_member_portal]` est déjà enregistré dans `class-juniorgolfkenya-public.php` (ligne ~77).

### Étape 2 : Créer une page WordPress

1. **Aller dans WordPress Admin** → Pages → Add New
2. **Titre de la page** : "Member Portal" ou "Espace Membre"
3. **Contenu** : Insérer le shortcode :
   ```
   [jgk_member_portal]
   ```
4. **Publier** la page

### Étape 3 : Configurer les permissions

Par défaut, seuls les utilisateurs connectés peuvent accéder au portail.

**Pour donner accès aux membres** :
- Les utilisateurs avec le rôle `jgf_member` peuvent accéder automatiquement
- Les utilisateurs non connectés voient un message de login

---

## 🧪 Tests à effectuer

### Test 1 : Accès au portail

1. **Se connecter** en tant que membre (rôle `jgf_member`)
2. **Aller** sur la page Member Portal
3. **Observer** l'affichage

**Résultat attendu** :
- ✅ Page charge sans erreur
- ✅ Informations du membre affichées (nom, numéro, type, status)
- ✅ Section "Your Coach" visible
- ✅ Dropdown avec liste des coaches
- ✅ Formulaire de mise à jour du profil

### Test 2 : Assigner un coach (membre sans coach)

1. **Vérifier** "You don't have a coach assigned yet" s'affiche
2. **Sélectionner** un coach dans le dropdown
3. **Cliquer** sur "Assign Coach"

**Résultat attendu** :
- ✅ Message : "Coach assignment updated successfully!"
- ✅ Le nom et email du coach s'affichent
- ✅ Le dropdown affiche le coach sélectionné
- ✅ En DB : `wp_jgk_members.coach_id` mis à jour

### Test 3 : Changer de coach (membre avec coach)

1. **Vérifier** le coach actuel s'affiche avec nom et email
2. **Sélectionner** un autre coach dans le dropdown
3. **Cliquer** sur "Update Coach"

**Résultat attendu** :
- ✅ Message : "Coach assignment updated successfully!"
- ✅ Le nouveau coach s'affiche
- ✅ Email du nouveau coach visible
- ✅ En DB : `coach_id` mis à jour

### Test 4 : Retirer l'assignation de coach

1. **Avoir** un coach assigné
2. **Sélectionner** "No coach" dans le dropdown
3. **Cliquer** sur "Update Coach"

**Résultat attendu** :
- ✅ Message : "Coach assignment updated successfully!"
- ✅ Message "You don't have a coach assigned yet" s'affiche
- ✅ En DB : `coach_id` = NULL

### Test 5 : Mise à jour du profil

1. **Modifier** :
   - Phone number : +254123456789
   - Emergency contact name : John Doe
   - Emergency contact phone : +254987654321
   - Medical conditions : Asthma
   - Address : 123 Main St, Nairobi
2. **Cliquer** sur "Update Profile"

**Résultat attendu** :
- ✅ Message : "Profile updated successfully!"
- ✅ Les données sont sauvegardées en DB
- ✅ Lors du rechargement, les données sont toujours là

### Test 6 : Sécurité

**Test 6.1 : Utilisateur non connecté**
1. **Se déconnecter**
2. **Accéder** à la page Member Portal

**Résultat attendu** :
- ✅ Message : "Please login to access the member portal"
- ✅ Lien de login affiché

**Test 6.2 : Utilisateur non-membre**
1. **Se connecter** en tant qu'admin ou autre rôle (non `jgf_member`)
2. **Accéder** à la page Member Portal

**Résultat attendu** :
- ✅ Message : "Member profile not found"
- ✅ Pas d'accès aux fonctionnalités

---

## 🔍 Vérification en base de données

### Vérifier l'assignation de coach

```sql
SELECT 
    m.id,
    m.first_name,
    m.last_name,
    m.membership_number,
    m.coach_id,
    c.display_name as coach_name,
    c.user_email as coach_email
FROM wp_jgk_members m
LEFT JOIN wp_users c ON m.coach_id = c.ID
WHERE m.user_id = [USER_ID];
```

### Vérifier les mises à jour de profil

```sql
SELECT 
    phone,
    emergency_contact_name,
    emergency_contact_phone,
    medical_conditions,
    address
FROM wp_jgk_members
WHERE user_id = [USER_ID];
```

---

## 📱 Interface responsive

Le portail est entièrement responsive :

- **Desktop** : Layout en grille 2 colonnes
- **Tablet** : Layout en grille 1-2 colonnes adaptative
- **Mobile** : Layout en 1 colonne

Breakpoint : 768px

---

## 🎨 Personnalisation CSS

### Couleurs principales

```css
/* Primaire (bleu) */
--primary: #3498db;
--primary-hover: #2980b9;

/* Succès (vert) */
--success: #28a745;

/* Erreur (rouge) */
--error: #dc3545;

/* Avertissement (jaune) */
--warning: #ffc107;
```

### Classes CSS principales

| Classe | Usage |
|--------|-------|
| `.jgk-member-portal` | Container principal |
| `.jgk-card` | Cards avec ombres |
| `.jgk-message-success` | Message de succès |
| `.jgk-message-error` | Message d'erreur |
| `.jgk-button-primary` | Bouton principal |
| `.jgk-status-active` | Status actif (vert) |
| `.jgk-status-pending` | Status en attente (jaune) |
| `.jgk-status-expired` | Status expiré (rouge) |

---

## 🔧 Intégration dans le thème

### Option 1 : Shortcode (recommandé)

Créer une page avec le shortcode :
```
[jgk_member_portal]
```

### Option 2 : Template PHP

Dans un template de thème :
```php
<?php
if (shortcode_exists('jgk_member_portal')) {
    echo do_shortcode('[jgk_member_portal]');
}
?>
```

### Option 3 : Widget

Ajouter dans un widget Text :
```
[jgk_member_portal]
```

---

## 🔐 Sécurité

### Mesures de sécurité implémentées

1. **Vérification de connexion** : `is_user_logged_in()`
2. **Nonce verification** : `wp_verify_nonce()`
3. **Sanitization** : 
   - `sanitize_text_field()` pour texte simple
   - `sanitize_textarea_field()` pour textarea
   - `intval()` pour IDs
4. **Escaping** :
   - `esc_html()` pour affichage texte
   - `esc_attr()` pour attributs HTML
   - `esc_textarea()` pour textarea
5. **Prepared statements** : `$wpdb->prepare()` pour SQL

---

## 📊 Workflow complet

```
Membre se connecte
    ↓
Accède à la page Member Portal (shortcode)
    ↓
Voit ses informations
    ↓
Sélectionne un coach dans le dropdown
    ↓
Clique "Assign Coach" ou "Update Coach"
    ↓
POST vers la même page avec action=update_coach
    ↓
Vérification nonce + sécurité
    ↓
UPDATE wp_jgk_members SET coach_id = X WHERE id = Y
    ↓
Message de succès + rechargement données
    ↓
Le nouveau coach s'affiche avec email
```

---

## 🚀 Améliorations futures possibles

### 1. Voir le profil du coach

Avant de sélectionner, afficher :
- Photo
- Bio
- Specialties
- Experience
- Members count

### 2. Système de notification

- Email au coach quand un membre le sélectionne
- Email au membre pour confirmer l'assignation

### 3. Historique des coaches

Table `wp_jgk_coach_assignments` :
```sql
CREATE TABLE wp_jgk_coach_assignments (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    member_id mediumint(9) NOT NULL,
    coach_id bigint(20) UNSIGNED NOT NULL,
    assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
    removed_at datetime,
    PRIMARY KEY (id)
);
```

### 4. Restriction d'assignation

- Limiter le nombre de membres par coach
- Nécessiter approbation du coach
- Bloquer changement trop fréquent (ex: max 1 fois par mois)

### 5. Dashboard membre étendu

- Statistiques personnelles
- Historique de paiements
- Inscriptions aux tournois
- Progression/performances

---

## 📝 Notes importantes

### Coach_id vs User_ID

- `wp_jgk_members.coach_id` = `wp_users.ID` du coach
- Pas `wp_jgf_coach_profiles.id` !

### Liste des coaches

Les coaches sont récupérés avec :
```php
get_users(array('role' => 'jgf_coach'))
```

Cela utilise la table `wp_usermeta` où :
- `meta_key` = 'wp_capabilities'
- `meta_value` LIKE '%jgf_coach%'

### Permissions

Par défaut, **tous les membres connectés** avec un profil dans `wp_jgk_members` peuvent :
- Voir le portail
- Assigner/changer leur coach
- Mettre à jour leur profil

**Ils ne peuvent PAS** :
- Voir/modifier les profils d'autres membres
- Supprimer leur compte
- Modifier leur membership_type ou status

---

## ✅ Checklist de déploiement

- [ ] Fichier `juniorgolfkenya-member-portal.php` créé
- [ ] Shortcode `[jgk_member_portal]` fonctionne
- [ ] Page "Member Portal" créée dans WordPress
- [ ] Shortcode inséré dans la page
- [ ] Page publiée
- [ ] Testée avec compte membre
- [ ] Assignation de coach testée
- [ ] Changement de coach testé
- [ ] Retrait de coach testé
- [ ] Mise à jour profil testée
- [ ] Vérification en base de données OK
- [ ] Sécurité testée (non-connecté, non-membre)
- [ ] Interface responsive testée (mobile, tablet, desktop)

---

## 🎉 Conclusion

✅ **Feature complète** : Les membres peuvent maintenant assigner/changer leur coach depuis le frontend

✅ **Interface intuitive** : Design moderne et facile à utiliser

✅ **Sécurité robuste** : Nonce, sanitization, escaping, prepared statements

✅ **Responsive design** : Fonctionne sur tous les appareils

✅ **Facile à déployer** : Un simple shortcode dans une page WordPress

🚀 **Prêt pour production** : Testez et déployez !
