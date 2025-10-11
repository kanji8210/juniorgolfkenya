# 🎯 RÉVISION MAJEURE : JUNIORS UNIQUEMENT (2-17 ans)

## 📌 Contexte
Le client souhaite accepter **UNIQUEMENT des juniors** (enfants de 2 à 17 ans).

## 🎯 Objectifs

### ✅ Conservation
- **Base de données** : Aucune modification de schéma
- Le champ `membership_type` reste en base (historique)
- Valeur fixée à `'junior'` pour tous les nouveaux membres

### 🚫 Suppressions
- **Options de membership** : Retirer youth/adult/senior/family
- Garder uniquement "Junior (2-17 ans)"

### ✔️ Validations à ajouter

#### 1. Validation d'âge
```php
// Âge minimum : 2 ans
// Âge maximum : 17 ans (strictement inférieur à 18)

$birthdate = new DateTime($date_of_birth);
$today = new DateTime();
$age = $today->diff($birthdate)->y;

if ($age < 2) {
    $errors[] = 'L\'âge minimum pour l\'inscription est de 2 ans.';
}

if ($age >= 18) {
    $errors[] = 'Ce programme est réservé aux juniors de moins de 18 ans.';
}
```

#### 2. Informations parent/tuteur OBLIGATOIRES
- Prénom parent
- Nom parent  
- Email parent
- Téléphone parent
- Relation (mère/père/tuteur)

## 📁 Fichiers à modifier

### 1. ✏️ Formulaire d'inscription public
**Fichier** : `public/partials/juniorgolfkenya-registration-form.php`

**Modifications :**

**A) Ligne ~35-36** : Forcer membership_type = 'junior'
```php
// AVANT :
$membership_type = sanitize_text_field($_POST['membership_type'] ?? '');

// APRÈS :
$membership_type = 'junior'; // Forcé : juniors uniquement
```

**B) Ligne ~71** : Supprimer la validation du type (toujours junior)
```php
// SUPPRIMER :
if (empty($membership_type)) {
    $registration_errors[] = 'Membership type is required.';
}
```

**C) AJOUTER après ligne ~71** : Validation d'âge complète
```php
// Validation de l'âge (2-17 ans)
if (!empty($date_of_birth)) {
    $birthdate = new DateTime($date_of_birth);
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;
    
    if ($age < 2) {
        $registration_errors[] = 'L\'âge minimum pour s\'inscrire est de 2 ans.';
    }
    
    if ($age >= 18) {
        $registration_errors[] = 'Ce programme est réservé aux juniors de moins de 18 ans. Si vous avez 18 ans ou plus, veuillez nous contacter directement.';
    }
} else {
    $registration_errors[] = 'La date de naissance est obligatoire.';
}
```

**D) Ligne ~81** : Renforcer validation parent (toujours obligatoire)
```php
// AVANT :
if ($membership_type === 'junior' && empty($parent_first_name)) {
    $registration_errors[] = 'Parent/Guardian information is required for junior members.';
}

// APRÈS :
if (empty($parent_first_name) || empty($parent_last_name)) {
    $registration_errors[] = 'Les informations du parent/tuteur sont obligatoires (prénom et nom).';
}

if (empty($parent_email) && empty($parent_phone)) {
    $registration_errors[] = 'Au moins un moyen de contact du parent est requis (email ou téléphone).';
}

if (empty($parent_relationship)) {
    $registration_errors[] = 'Veuillez indiquer votre relation avec l\'enfant.';
}
```

**E) Ligne ~333** : Rendre date_of_birth obligatoire
```php
// AVANT :
<label for="date_of_birth">Date of Birth</label>
<input type="date" id="date_of_birth" name="date_of_birth" ...>

// APRÈS :
<label for="date_of_birth">Date of Birth *</label>
<input type="date" id="date_of_birth" name="date_of_birth" required max="<?php echo date('Y-m-d', strtotime('-2 years')); ?>" ...>
<small style="color: #666;">L'enfant doit avoir entre 2 et 17 ans</small>
```

**F) Ligne ~363-370** : Supprimer sélecteur de type, afficher info
```php
// SUPPRIMER TOUT LE SELECT :
<label for="membership_type">Membership Type *</label>
<select id="membership_type" name="membership_type" required>
    <option value="">Select Membership Type</option>
    <option value="junior">Junior (Under 18) - KSh 5,000/year</option>
    <option value="youth">Youth (18-25) - KSh 8,000/year</option>
    <option value="adult">Adult (26+) - KSh 15,000/year</option>
    <option value="senior">Senior (65+) - KSh 10,000/year</option>
    <option value="family">Family Package - KSh 30,000/year</option>
</select>

// REMPLACER PAR :
<div class="jgk-membership-info" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
    <h4 style="margin: 0 0 10px 0; font-size: 20px;">🎯 Programme Junior Golf Kenya</h4>
    <p style="margin: 0; font-size: 16px; opacity: 0.95;">
        <strong>Âge requis :</strong> 2 à 17 ans<br>
        <strong>Cotisation annuelle :</strong> KSh 5,000
    </p>
    <input type="hidden" name="membership_type" value="junior">
</div>
```

**G) Ligne ~390-395** : Rendre champs parent obligatoires
```php
// AVANT :
<label for="parent_first_name">Parent/Guardian First Name</label>
<input type="text" id="parent_first_name" name="parent_first_name" ...>

// APRÈS :
<label for="parent_first_name">Parent/Guardian First Name *</label>
<input type="text" id="parent_first_name" name="parent_first_name" required ...>

// Appliquer le même changement pour :
// - parent_last_name (required)
// - parent_email OU parent_phone (au moins un requis)
// - parent_relationship (required)
```

**H) Ligne ~393** : Améliorer description section parent
```php
// AVANT :
<p class="jgk-section-description">Required for junior members (under 18)</p>

// APRÈS :
<p class="jgk-section-description" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; color: #856404;">
    <strong>⚠️ Obligatoire</strong> - Les informations du parent ou tuteur légal sont requises pour tous les membres juniors.
</p>
```

**I) Ligne ~800+** : Ajouter JavaScript de validation d'âge en temps réel
```javascript
<script>
document.getElementById('date_of_birth')?.addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    
    const messageDiv = document.getElementById('age-validation-message') || (() => {
        const div = document.createElement('div');
        div.id = 'age-validation-message';
        div.style.marginTop = '10px';
        div.style.padding = '10px';
        div.style.borderRadius = '5px';
        this.parentElement.appendChild(div);
        return div;
    })();
    
    if (age < 2) {
        messageDiv.style.background = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.innerHTML = '❌ L\'enfant doit avoir au moins 2 ans pour s\'inscrire.';
        this.setCustomValidity('Âge minimum : 2 ans');
    } else if (age >= 18) {
        messageDiv.style.background = '#f8d7da';
        messageDiv.style.color = '#721c24';
        messageDiv.innerHTML = '❌ Ce programme est réservé aux juniors de moins de 18 ans.';
        this.setCustomValidity('Âge maximum : 17 ans');
    } else {
        messageDiv.style.background = '#d4edda';
        messageDiv.style.color = '#155724';
        messageDiv.innerHTML = `✅ Âge valide : ${age} ans`;
        this.setCustomValidity('');
    }
});

// Validation lors du changement de membership_type (déjà supprimé mais par sécurité)
document.getElementById('membership_type')?.addEventListener('change', function() {
    const parentSection = document.getElementById('parent-section');
    // Toujours afficher la section parent (obligatoire)
    if (parentSection) {
        parentSection.style.display = 'block';
        // Rendre les champs parent obligatoires
        document.getElementById('parent_first_name').required = true;
        document.getElementById('parent_last_name').required = true;
        document.getElementById('parent_relationship').required = true;
    }
});
</script>
```

### 2. ✏️ Formulaire admin - Ajout membre
**Fichier** : `admin/partials/juniorgolfkenya-admin-members.php`

**Modifications :**

**A) Ligne ~75** : Forcer membership_type = 'junior'
```php
// AVANT :
'membership_type' => sanitize_text_field($_POST['membership_type']),

// APRÈS :
'membership_type' => 'junior', // Forcé : programme juniors uniquement
```

**B) Ligne ~387-393** : Simplifier sélecteur
```php
// SUPPRIMER :
<label for="membership_type">Membership Type *</label>
<select id="membership_type" name="membership_type" required>
    <option value="">Select Type</option>
    <option value="junior">Junior (Under 18)</option>
    <option value="youth">Youth (18-25)</option>
    <option value="adult">Adult (26+)</option>
    <option value="senior">Senior (65+)</option>
    <option value="family">Family Package</option>
</select>

// REMPLACER PAR :
<div class="jgk-form-field-info" style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; border-radius: 5px;">
    <label style="font-weight: 600; color: #0073aa; display: block; margin-bottom: 5px;">
        Membership Type
    </label>
    <p style="margin: 0; color: #555;">
        <strong>Junior Golf Kenya</strong> - Programme réservé aux 2-17 ans
    </p>
    <input type="hidden" name="membership_type" value="junior">
</div>
```

**C) AJOUTER validation d'âge dans le traitement du formulaire (avant ligne ~75)**
```php
// Validation de l'âge pour les juniors
$date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? '');
if (!empty($date_of_birth)) {
    $birthdate = new DateTime($date_of_birth);
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;
    
    if ($age < 2) {
        $message = 'Erreur : L\'âge minimum est de 2 ans.';
        $message_type = 'error';
        $create_error = true;
    }
    
    if ($age >= 18) {
        $message = 'Erreur : Ce programme est réservé aux juniors de moins de 18 ans.';
        $message_type = 'error';
        $create_error = true;
    }
}

if (!isset($create_error)) {
    // Suite du code...
}
```

### 3. ✏️ Formulaire admin - Édition membre
**Fichier** : `admin/partials/juniorgolfkenya-admin-member-edit.php`

**Modifications :**

**A) Ligne ~124-130** : Simplifier sélecteur de type
```php
// SUPPRIMER :
<label for="membership_type">Membership Type *</label>
<select id="membership_type" name="membership_type" required>
    <option value="junior" <?php selected($edit_member->membership_type, 'junior'); ?>>Junior (Under 18)</option>
    <option value="youth" <?php selected($edit_member->membership_type, 'youth'); ?>>Youth (18-25)</option>
    <option value="adult" <?php selected($edit_member->membership_type, 'adult'); ?>>Adult (26+)</option>
    <option value="senior" <?php selected($edit_member->membership_type, 'senior'); ?>>Senior (65+)</option>
    <option value="family" <?php selected($edit_member->membership_type, 'family'); ?>>Family Package</option>
</select>

// REMPLACER PAR :
<div class="jgk-form-field-info" style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; border-radius: 5px;">
    <label style="font-weight: 600; color: #0073aa; display: block; margin-bottom: 5px;">
        Membership Type
    </label>
    <p style="margin: 0; color: #555;">
        <strong>Junior</strong> <?php if (!empty($edit_member->date_of_birth)) {
            $birthdate = new DateTime($edit_member->date_of_birth);
            $today = new DateTime();
            $age = $today->diff($birthdate)->y;
            echo "({$age} ans)";
        } ?>
    </p>
    <input type="hidden" name="membership_type" value="junior">
    <?php if ($edit_member->membership_type !== 'junior'): ?>
    <p style="color: #d63638; font-size: 12px; margin: 5px 0 0 0;">
        ⚠️ Ancien type : <?php echo esc_html(ucfirst($edit_member->membership_type)); ?> (sera converti en Junior)
    </p>
    <?php endif; ?>
</div>
```

**B) Ligne ~93** : Rendre date_of_birth obligatoire
```php
// AVANT :
<label for="date_of_birth">Date of Birth</label>
<input type="date" id="date_of_birth" name="date_of_birth" ...>

// APRÈS :
<label for="date_of_birth">Date of Birth *</label>
<input type="date" id="date_of_birth" name="date_of_birth" required max="<?php echo date('Y-m-d', strtotime('-2 years')); ?>" ...>
<small style="color: #666;">Âge requis : 2-17 ans</small>
```

**C) AJOUTER validation dans le traitement (fichier class-juniorgolfkenya-admin.php ou dans le même fichier si traitement inline)**

## 📝 Messages utilisateur à ajouter

### Messages d'erreur français
```php
$error_messages = array(
    'age_too_young' => 'L\'enfant doit avoir au moins 2 ans pour s\'inscrire au programme Junior Golf Kenya.',
    'age_too_old' => 'Ce programme est réservé aux juniors de moins de 18 ans. Pour les membres de 18 ans et plus, veuillez nous contacter directement.',
    'parent_required' => 'Les informations du parent ou tuteur légal sont obligatoires.',
    'dob_required' => 'La date de naissance est obligatoire pour vérifier l\'éligibilité.',
);
```

### Message de bienvenue
```html
<div class="jgk-program-info" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; text-align: center;">
    <h2 style="margin: 0 0 15px 0; font-size: 28px;">⛳ Junior Golf Kenya</h2>
    <p style="font-size: 18px; margin: 0 0 20px 0; opacity: 0.95;">
        Programme de développement pour jeunes golfeurs<br>
        <strong>Âges : 2 à 17 ans</strong>
    </p>
    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; display: inline-block;">
        <p style="margin: 0; font-size: 16px;">
            Cotisation annuelle : <strong>KSh 5,000</strong>
        </p>
    </div>
</div>
```

## ✅ Checklist de validation

Après les modifications, tester :

- [ ] Formulaire public refuse âge < 2 ans
- [ ] Formulaire public refuse âge >= 18 ans
- [ ] Date de naissance est obligatoire
- [ ] Informations parent obligatoires
- [ ] Membership type forcé à 'junior'
- [ ] Formulaire admin - ajout avec validation d'âge
- [ ] Formulaire admin - édition affiche type junior
- [ ] Messages d'erreur clairs en français
- [ ] JavaScript de validation en temps réel fonctionne
- [ ] Les anciens membres (youth/adult) restent visibles (DB intacte)

## 🎯 Résultat attendu

**AVANT :**
- 5 types de membership (junior/youth/adult/senior/family)
- Pas de validation d'âge stricte
- Parent optionnel pour certains types

**APRÈS :**
- 1 seul type : Junior (2-17 ans)
- Validation stricte : refus < 2 ans et >= 18 ans
- Parent TOUJOURS obligatoire
- DB inchangée (historique préservé)
- Messages clairs pour les refus

---

**Date de révision** : 11 octobre 2025  
**Version plugin** : 1.0.0  
**Statut** : 📝 Documentation complète - Prêt pour implémentation
