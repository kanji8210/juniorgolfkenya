# Plan de Développement - Édition Complète des Membres

## Objectifs

1. ✅ Permettre à l'admin d'éditer complètement un membre
2. ✅ Gérer les images de profil (upload + affichage)
3. ✅ Créer une table parents/tuteurs séparée
4. ✅ Lier les parents/tuteurs aux membres de moins de 18 ans

## 1. Nouvelle Table: jgk_parents_guardians

### Structure
```sql
CREATE TABLE jgk_parents_guardians (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    member_id mediumint(9) NOT NULL,
    relationship varchar(50) NOT NULL, -- father, mother, guardian, legal_guardian
    first_name varchar(100) NOT NULL,
    last_name varchar(100) NOT NULL,
    email varchar(100),
    phone varchar(20),
    mobile varchar(20),
    address text,
    occupation varchar(100),
    employer varchar(150),
    id_number varchar(50), -- National ID or Passport
    is_primary_contact tinyint(1) DEFAULT 0,
    can_pickup tinyint(1) DEFAULT 1,
    emergency_contact tinyint(1) DEFAULT 0,
    notes text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY member_id (member_id),
    KEY is_primary_contact (is_primary_contact)
);
```

### Avantages
- ✅ Permet plusieurs parents/tuteurs par membre
- ✅ Informations détaillées pour chaque parent
- ✅ Gestion des contacts d'urgence
- ✅ Autorisation de récupération (can_pickup)
- ✅ Flexible pour différentes situations familiales

## 2. Modifications à la Table jgk_members

### Colonnes à Conserver
- `profile_image_url` - URL de l'image (stockée dans uploads)
- `profile_image_id` - À AJOUTER: ID de l'attachment WordPress

### Colonnes à Supprimer (remplacées par la table parents)
- `parents_guardians` - Sera remplacé par la table relationnelle

## 3. Gestion des Images de Profil

### Upload
- Utiliser la Media Library WordPress
- Formats acceptés: JPG, PNG, GIF
- Taille max: 2MB
- Dimensions recommandées: 500x500px
- Redimensionnement automatique

### Stockage
```
wp-content/uploads/juniorgolfkenya/members/
  ├── [member_id]/
  │   ├── profile.jpg
  │   └── profile-thumb.jpg
```

### Implémentation
```php
// Classe pour gérer les uploads
class JuniorGolfKenya_Media {
    public static function upload_profile_image($member_id, $file)
    public static function delete_profile_image($member_id)
    public static function get_profile_image_url($member_id, $size = 'full')
}
```

## 4. Formulaire d'Édition de Membre

### Sections du Formulaire

#### Section 1: Informations Personnelles
- Photo de profil (avec aperçu)
- Prénom / Nom
- Date de naissance
- Genre
- Email
- Téléphone
- Adresse

#### Section 2: Informations de Membership
- Numéro de membre (lecture seule)
- Type de membership (dropdown)
- Statut (dropdown)
- Date d'adhésion
- Date d'expiration
- Coach assigné (select)

#### Section 3: Informations Golf
- Handicap
- Club d'affiliation
- Biographie

#### Section 4: Informations Médicales
- Conditions médicales
- Contact d'urgence (nom + téléphone)

#### Section 5: Parents/Tuteurs (Si < 18 ans)
- Liste des parents/tuteurs existants
- Bouton "Ajouter Parent/Tuteur"
- Formulaire inline pour chaque parent:
  - Relation (select)
  - Prénom / Nom
  - Email / Téléphone
  - Adresse
  - ID Number
  - Contact principal (checkbox)
  - Peut récupérer l'enfant (checkbox)
  - Contact d'urgence (checkbox)
  - Actions: Modifier / Supprimer

#### Section 6: Consentements
- Consentement parental (checkbox)
- Consentement photographie (select: yes/no)

## 5. Fichiers à Créer/Modifier

### Nouveaux Fichiers
1. `includes/class-juniorgolfkenya-media.php` - Gestion des médias
2. `includes/class-juniorgolfkenya-parents.php` - Gestion des parents/tuteurs
3. `admin/partials/juniorgolfkenya-admin-member-edit.php` - Formulaire d'édition
4. `admin/js/member-edit.js` - JavaScript pour le formulaire
5. `admin/css/member-edit.css` - Styles pour le formulaire

### Fichiers à Modifier
1. `includes/class-juniorgolfkenya-activator.php` - Ajouter table parents_guardians
2. `includes/class-juniorgolfkenya-database.php` - Méthodes CRUD pour parents
3. `admin/class-juniorgolfkenya-admin.php` - Enregistrer les routes d'édition
4. `admin/partials/juniorgolfkenya-admin-members.php` - Ajouter lien "Edit"

## 6. Fonctionnalités Additionnelles

### Validation
- ✅ Vérifier l'âge pour afficher section parents
- ✅ Valider email unique
- ✅ Valider format téléphone
- ✅ Valider taille/format image
- ✅ Exiger au moins un parent si < 18 ans

### Sécurité
- ✅ Nonces WordPress
- ✅ Capabilities check (edit_users)
- ✅ Sanitization de tous les inputs
- ✅ Validation côté serveur

### UX
- ✅ Auto-save (drafts)
- ✅ Confirmation avant suppression
- ✅ Messages de succès/erreur
- ✅ Aperçu de l'image avant upload
- ✅ Indicateurs de champs requis

## 7. API AJAX

### Endpoints
```
wp_ajax_jgk_upload_profile_image
wp_ajax_jgk_delete_profile_image
wp_ajax_jgk_add_parent
wp_ajax_jgk_update_parent
wp_ajax_jgk_delete_parent
wp_ajax_jgk_update_member
```

## 8. Ordre d'Implémentation

### Phase 1: Base de données ✅ À faire maintenant
1. Créer table jgk_parents_guardians
2. Ajouter colonne profile_image_id à jgk_members
3. Créer classe JuniorGolfKenya_Parents

### Phase 2: Gestion des médias
1. Créer classe JuniorGolfKenya_Media
2. Implémenter upload/delete/display d'images

### Phase 3: Formulaire d'édition
1. Créer le formulaire HTML
2. Ajouter JavaScript (validation, AJAX)
3. Ajouter CSS

### Phase 4: Traitement backend
1. Implémenter les handlers AJAX
2. Validation et sanitization
3. Enregistrement en base de données

### Phase 5: Intégration
1. Ajouter liens d'édition dans la liste des membres
2. Tester toutes les fonctionnalités
3. Gérer les cas d'erreur

---

**Prêt à commencer l'implémentation ?**
