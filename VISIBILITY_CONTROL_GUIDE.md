# 🌐 Guide du Contrôle de Visibilité des Membres

## 📋 Vue d'ensemble

Le système de contrôle de visibilité permet de gérer finement qui peut voir les profils des membres dans l'application Junior Golf Kenya.

---

## 🔑 Champ de Base de Données

### Table : `wp_jgk_members`
### Colonne : `is_public`

```sql
is_public tinyint(1) DEFAULT 0
```

**Valeurs possibles :**
- `0` = Caché du public (par défaut)
- `1` = Visible publiquement

---

## 🎯 Fonctionnement

### 1. **Visibilité Publique (`is_public = 1`)**

Quand un membre est marqué comme **visible publiquement** :

✅ **Le membre apparaît dans :**
- Annuaires publics des membres
- Galeries de photos publiques
- Listes d'équipes publiques
- Pages de présentation des juniors
- Résultats de recherche publics
- Widgets de membres sur le site

✅ **Accessible par :**
- Visiteurs anonymes
- Utilisateurs connectés
- Administrateurs
- Entraîneurs
- Autres membres

📝 **Cas d'usage :**
- Membre/parent a donné son consentement pour la visibilité publique
- Membre souhaite apparaître dans les annuaires
- Promotion du club et visibilité des jeunes golfeurs

---

### 2. **Caché du Public (`is_public = 0`)**

Quand un membre est marqué comme **caché** :

🔒 **Le membre N'apparaît PAS dans :**
- Annuaires publics
- Galeries publiques
- Pages accessibles aux visiteurs
- Résultats de recherche publics
- Widgets publics

✅ **Mais reste accessible à :**
- Administrateurs (manage_options)
- Entraîneurs assignés (manage_coaches)
- Comité (jgk_committee)
- Le membre lui-même via son tableau de bord

📝 **Cas d'usage :**
- Parents ne souhaitent pas d'exposition publique
- Membre mineur sans consentement parental pour photos
- Confidentialité demandée
- Période d'essai avant visibilité complète

---

## 🛠️ Configuration dans l'Admin

### Interface de Modification de Membre

Dans **JGK Dashboard > Members > Edit Member** :

```
┌─────────────────────────────────────────────────────────────┐
│ 🌐 Public Visibility Control                                │
│                                                               │
│ [Dropdown]                                                    │
│ ┌───────────────────────────────────────────────────────┐  │
│ │ ✅ Visible Publicly - Show in directories, galleries  │  │
│ │ 🔒 Hidden from Public - Only visible to admins       │  │
│ └───────────────────────────────────────────────────────┘  │
│                                                               │
│ Important: Controls whether this member appears on public    │
│ pages, member directories, galleries, and team listings.     │
│ When hidden, profile is only accessible to logged-in         │
│ administrators and coaches.                                   │
└─────────────────────────────────────────────────────────────┘
```

**Style visuel :**
- Fond bleu clair (`#f0f8ff`)
- Bordure bleue à gauche (`4px solid #0073aa`)
- Icône 🌐 pour identifier rapidement
- Options avec emojis pour clarté visuelle
- Texte explicatif détaillé

---

## 💾 Code d'Update

### Fichier : `admin/partials/juniorgolfkenya-admin-members.php`

```php
// Ligne ~185
$member_data = array(
    'first_name' => sanitize_text_field($_POST['first_name']),
    'last_name' => sanitize_text_field($_POST['last_name']),
    // ... autres champs ...
    'is_public' => isset($_POST['is_public']) ? intval($_POST['is_public']) : 0
);

// Log pour debug
error_log('JGK Member Update - is_public value: ' . 
    (isset($_POST['is_public']) ? $_POST['is_public'] : 'not set'));
error_log('JGK Member Update - Data: ' . print_r($member_data, true));

$result = JuniorGolfKenya_Database::update_member($member_id, $member_data);
```

**Points clés :**
- Utilise `intval()` pour forcer un entier
- Défaut à `0` si non défini (caché)
- Logs pour debugging
- Sanitization automatique par `intval()`

---

## 🔍 Requêtes SQL d'Exemple

### Afficher seulement les membres publics

```sql
SELECT * FROM wp_jgk_members 
WHERE is_public = 1 
AND status = 'active'
ORDER BY last_name, first_name;
```

### Compter les membres publics vs privés

```sql
SELECT 
    is_public,
    COUNT(*) as total,
    CASE 
        WHEN is_public = 1 THEN 'Public'
        ELSE 'Private'
    END as visibility
FROM wp_jgk_members
GROUP BY is_public;
```

### Trouver les membres sans paramètre de visibilité

```sql
SELECT id, first_name, last_name, membership_number
FROM wp_jgk_members
WHERE is_public IS NULL;

-- Si trouvés, corriger :
UPDATE wp_jgk_members 
SET is_public = 0 
WHERE is_public IS NULL;
```

---

## 📝 Utilisation dans le Code PHP

### Vérifier la visibilité d'un membre

```php
function is_member_publicly_visible($member_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'jgk_members';
    
    $is_public = $wpdb->get_var($wpdb->prepare(
        "SELECT is_public FROM {$table} WHERE id = %d",
        $member_id
    ));
    
    return (bool) $is_public;
}
```

### Filtrer une liste de membres pour affichage public

```php
function get_public_members() {
    global $wpdb;
    $table = $wpdb->prefix . 'jgk_members';
    
    // Seulement les membres publics et actifs
    $members = $wpdb->get_results("
        SELECT m.*, u.user_email, u.display_name
        FROM {$table} m
        LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
        WHERE m.is_public = 1 
        AND m.status = 'active'
        ORDER BY m.last_name, m.first_name
    ");
    
    return $members;
}
```

### Vérifier les permissions avant affichage

```php
function can_view_member($member_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'jgk_members';
    
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT is_public FROM {$table} WHERE id = %d",
        $member_id
    ));
    
    if (!$member) {
        return false; // Membre n'existe pas
    }
    
    // Si membre public, tout le monde peut voir
    if ($member->is_public == 1) {
        return true;
    }
    
    // Si privé, seuls admin/coaches peuvent voir
    if (current_user_can('manage_options') || 
        current_user_can('manage_coaches') ||
        current_user_can('edit_members')) {
        return true;
    }
    
    return false;
}
```

---

## 🎨 Affichage Conditionnel dans les Templates

### Shortcode de Liste de Membres

```php
// public/partials/shortcode-member-directory.php

function jgk_member_directory_shortcode($atts) {
    global $wpdb;
    $table = $wpdb->prefix . 'jgk_members';
    
    // Paramètres du shortcode
    $atts = shortcode_atts(array(
        'show_private' => 'no', // Par défaut, seulement publics
        'limit' => 20
    ), $atts);
    
    $where = "m.status = 'active'";
    
    // Si non admin, forcer seulement les publics
    if (!current_user_can('manage_options') || $atts['show_private'] === 'no') {
        $where .= " AND m.is_public = 1";
    }
    
    $members = $wpdb->get_results("
        SELECT m.*, u.display_name
        FROM {$table} m
        LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
        WHERE {$where}
        ORDER BY m.last_name, m.first_name
        LIMIT " . intval($atts['limit'])
    );
    
    ob_start();
    ?>
    <div class="jgk-member-directory">
        <?php foreach ($members as $member): ?>
        <div class="member-card">
            <img src="<?php echo esc_url($member->profile_image_url ?: 'default.jpg'); ?>" alt="">
            <h3><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></h3>
            <p><?php echo esc_html($member->club_affiliation); ?></p>
            
            <?php if ($member->is_public == 0 && current_user_can('manage_options')): ?>
                <span class="badge private">🔒 Private</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('jgk_members', 'jgk_member_directory_shortcode');
```

**Usage :**
```
[jgk_members] <!-- Seulement publics -->
[jgk_members show_private="yes"] <!-- Admin seulement : affiche tous -->
[jgk_members limit="10"] <!-- Limite à 10 membres -->
```

---

## 🔐 Considérations de Sécurité

### 1. **Protection des Données Sensibles**

Même si `is_public = 0`, certaines données ne doivent JAMAIS être publiques :
- Numéros de téléphone personnels
- Adresses email
- Adresses domicile
- Conditions médicales
- Contacts d'urgence

**Bonne pratique :**
```php
function get_member_public_data($member) {
    $public_data = array(
        'id' => $member->id,
        'first_name' => $member->first_name,
        'last_name' => $member->last_name,
        'club_affiliation' => $member->club_affiliation,
        'profile_image' => $member->profile_image_url
    );
    
    // Données sensibles seulement si admin connecté
    if (current_user_can('manage_options')) {
        $public_data['phone'] = $member->phone;
        $public_data['email'] = $member->email;
        $public_data['address'] = $member->address;
    }
    
    return $public_data;
}
```

### 2. **Consentement Parental**

Lier `is_public` avec `consent_photography` :

```php
// Ne montrer publiquement QUE si :
// 1. is_public = 1
// 2. consent_photography = 'yes' (si photos affichées)
// 3. parental_consent = 1

$can_show_publicly = (
    $member->is_public == 1 &&
    $member->consent_photography === 'yes' &&
    $member->parental_consent == 1
);
```

### 3. **RGPD / Protection des Mineurs**

Pour les membres juniors (< 18 ans) :
- ✅ Toujours demander consentement parental
- ✅ Permettre de changer `is_public` à tout moment
- ✅ Logs des changements de visibilité
- ✅ Droit à l'oubli (suppression complète)

---

## 🧪 Tests et Vérification

### Test 1 : Update fonctionne

```sql
-- Avant modification
SELECT id, first_name, last_name, is_public 
FROM wp_jgk_members WHERE id = 20;

-- Résultat attendu : is_public = 0 ou 1
```

### Test 2 : Logs de debugging

Chercher dans les logs WordPress (`wp-content/debug.log`) :

```
JGK Member Update - is_public value: 1
JGK Member Update - Data: Array (
    [first_name] => TEST
    [last_name] => MEMBER
    ...
    [is_public] => 1
)
```

### Test 3 : Affichage conditionnel

1. Créer un membre avec `is_public = 0`
2. Se déconnecter
3. Visiter la page publique de liste des membres
4. **Résultat attendu :** Ce membre ne doit PAS apparaître

5. Se connecter comme admin
6. Visiter la même page
7. **Résultat attendu :** Ce membre apparaît avec badge "🔒 Private"

---

## 📊 Migration de Données (si nécessaire)

Si des membres existants n'ont pas de valeur `is_public` définie :

```sql
-- Étape 1 : Vérifier les NULL
SELECT COUNT(*) FROM wp_jgk_members WHERE is_public IS NULL;

-- Étape 2 : Définir par défaut à 0 (caché)
UPDATE wp_jgk_members 
SET is_public = 0 
WHERE is_public IS NULL;

-- Étape 3 : Optionnel - Rendre publics les membres avec consentements
UPDATE wp_jgk_members 
SET is_public = 1 
WHERE consent_photography = 'yes' 
  AND parental_consent = 1
  AND status = 'active';

-- Étape 4 : Vérifier les résultats
SELECT 
    is_public,
    COUNT(*) as total,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM wp_jgk_members), 1), '%') as percentage
FROM wp_jgk_members
GROUP BY is_public;
```

---

## 🎯 Checklist de Déploiement

Avant de passer en production :

- [ ] ✅ Colonne `is_public` existe dans `wp_jgk_members`
- [ ] ✅ Valeur par défaut = 0 (caché)
- [ ] ✅ Formulaire d'édition affiche le champ clairement
- [ ] ✅ Update SQL inclut `is_public`
- [ ] ✅ Logs de debugging activés
- [ ] ✅ Tests en tant qu'utilisateur anonyme
- [ ] ✅ Tests en tant qu'admin
- [ ] ✅ Shortcodes filtrent correctement
- [ ] ✅ API REST respecte la visibilité
- [ ] ✅ Migration des données existantes effectuée
- [ ] ✅ Documentation fournie aux admins

---

## 📞 Support et Debugging

### Erreur : "visibility is set to hidden"

**Cause :** L'update SQL essaie de mettre `is_public = 0` mais la requête échoue.

**Solutions :**

1. **Vérifier la colonne existe :**
   ```sql
   SHOW COLUMNS FROM wp_jgk_members LIKE 'is_public';
   ```

2. **Vérifier les logs :**
   ```bash
   tail -f wp-content/debug.log | grep "JGK Member Update"
   ```

3. **Tester manuellement :**
   ```sql
   UPDATE wp_jgk_members 
   SET is_public = 1 
   WHERE id = 20;
   
   -- Vérifier
   SELECT id, is_public FROM wp_jgk_members WHERE id = 20;
   ```

4. **Vérifier les permissions de la table :**
   ```sql
   SHOW GRANTS FOR CURRENT_USER();
   ```

---

## 📚 Références Rapides

| Action | Fichier | Ligne |
|--------|---------|-------|
| **Définition table** | `includes/class-juniorgolfkenya-activator.php` | ~90 |
| **Formulaire edit** | `admin/partials/juniorgolfkenya-admin-member-edit.php` | ~200 |
| **Traitement update** | `admin/partials/juniorgolfkenya-admin-members.php` | ~185 |
| **Champ HTML** | `admin-member-edit.php` | 201-211 |

---

## ✅ Résumé

Le champ `is_public` est maintenant :

1. ✅ **Clairement visible** dans le formulaire d'édition (fond bleu, icône 🌐)
2. ✅ **Bien documenté** avec texte explicatif
3. ✅ **Correctement traité** dans l'update SQL
4. ✅ **Loggé** pour debugging
5. ✅ **Sécurisé** avec sanitization
6. ✅ **Testé** avec requêtes SQL

**Vous pouvez maintenant contrôler la visibilité de chaque membre individuellement !** 🎉

---

**Dernière mise à jour :** 12 octobre 2025  
**Version :** 1.0.0  
**Plugin :** Junior Golf Kenya
