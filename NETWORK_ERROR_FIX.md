# 🔧 Correction de l'erreur "Network Error" - Détails des Membres

## 🐛 Problème Identifié

Lorsque vous cliquez sur "View Details" d'un membre, vous obtenez une erreur "Network Error".

## 🔍 Causes Possibles

### 1. **Problème de Permissions** (Le Plus Probable)
La fonction AJAX `jgk_ajax_get_member_details()` vérifie la permission `manage_coaches` :

```php
// Ligne 139 de juniorgolfkenya.php
if (!current_user_can('manage_coaches')) {
    wp_send_json_error('Insufficient permissions');
}
```

**Si l'utilisateur n'a pas cette permission → Erreur réseau**

### 2. **Problème de Colonnes SQL**
La requête SQL peut échouer si certaines colonnes n'existent pas dans la table `wp_jgk_members`.

### 3. **Problème de Rôles**
Si les rôles utilisent encore `jgf_*` au lieu de `jgk_*`, les permissions ne fonctionnent pas.

## ✅ Solutions

### Solution 1 : Corriger les Permissions (Immédiat)

**Option A : Changer la permission requise**

Modifier `juniorgolfkenya.php` ligne 139 :

```php
// ANCIEN CODE (ligne 139)
if (!current_user_can('manage_coaches')) {
    wp_send_json_error('Insufficient permissions');
}

// NOUVEAU CODE
if (!current_user_can('edit_members') && !current_user_can('manage_coaches') && !current_user_can('manage_options')) {
    wp_send_json_error('Insufficient permissions');
}
```

**Option B : Désactiver temporairement la vérification (UNIQUEMENT POUR DEBUG)**

```php
// COMMENTER temporairement les lignes 139-141
/*
if (!current_user_can('manage_coaches')) {
    wp_send_json_error('Insufficient permissions');
}
*/
```

### Solution 2 : Corriger les Rôles SQL

Si vous n'avez pas encore exécuté le script SQL de correction des rôles :

```sql
-- Exécuter dans phpMyAdmin

-- 1. Vérifier les rôles actuels
SELECT user_id, meta_value 
FROM wp_usermeta 
WHERE meta_key = 'wp_capabilities' 
AND (meta_value LIKE '%jgf_%' OR meta_value LIKE '%jgk_%');

-- 2. Corriger les rôles (si nécessaire)
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:10:"jgf_member"', 's:10:"jgk_member"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_member%';

UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:9:"jgf_coach"', 's:9:"jgk_coach"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_coach%';

UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:13:"jgf_committee"', 's:13:"jgk_committee"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_committee%';

-- 3. Vérifier la correction
SELECT user_id, meta_value 
FROM wp_usermeta 
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgk_%';
```

### Solution 3 : Vérifier les Colonnes de la Base de Données

Exécuter ce script pour vérifier que toutes les colonnes existent :

```sql
-- Vérifier la structure de la table
DESCRIBE wp_jgk_members;

-- Colonnes requises par la fonction AJAX :
-- id, user_id, first_name, last_name, phone, date_of_birth, gender
-- status, membership_type, membership_number, club_name, handicap_index
-- date_joined, address, biography, emergency_contact_name, emergency_contact_phone

-- Ajouter les colonnes manquantes si nécessaire
ALTER TABLE wp_jgk_members 
ADD COLUMN IF NOT EXISTS biography TEXT DEFAULT NULL;

ALTER TABLE wp_jgk_members 
ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(255) DEFAULT NULL;

ALTER TABLE wp_jgk_members 
ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(20) DEFAULT NULL;
```

### Solution 4 : Activer le Mode Debug WordPress

Pour voir l'erreur exacte, activer le debug dans `wp-config.php` :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Ensuite, consulter le fichier : `wp-content/debug.log`

## 🚀 Correction Complète et Robuste

Voici la correction complète à appliquer dans `juniorgolfkenya.php` :

**Remplacer les lignes 131-268 par :**

```php
function jgk_ajax_get_member_details() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jgk_get_member_details')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    
    // Check permissions - Allow edit_members, manage_coaches, or admin
    if (!current_user_can('edit_members') && 
        !current_user_can('manage_coaches') && 
        !current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions. Required: edit_members, manage_coaches, or admin.'));
        return;
    }
    
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    
    if (!$member_id) {
        wp_send_json_error(array('message' => 'Invalid member ID'));
        return;
    }
    
    global $wpdb;
    $members_table = $wpdb->prefix . 'jgk_members';
    $users_table = $wpdb->users;
    $coach_members_table = $wpdb->prefix . 'jgk_coach_members';
    $coaches_table = $wpdb->users;
    $parents_table = $wpdb->prefix . 'jgk_parents_guardians';
    
    try {
        // Get member basic info with error handling
        $member = $wpdb->get_row($wpdb->prepare("
            SELECT 
                m.*,
                u.user_email,
                u.display_name
            FROM {$members_table} m
            LEFT JOIN {$users_table} u ON m.user_id = u.ID
            WHERE m.id = %d
        ", $member_id));
        
        if ($wpdb->last_error) {
            wp_send_json_error(array(
                'message' => 'Database error',
                'sql_error' => $wpdb->last_error
            ));
            return;
        }
        
        if (!$member) {
            wp_send_json_error(array('message' => 'Member not found with ID: ' . $member_id));
            return;
        }
        
        // Get all assigned coaches (with error handling)
        $coaches = $wpdb->get_results($wpdb->prepare("
            SELECT 
                c.ID as coach_id,
                c.display_name as name,
                cm.is_primary
            FROM {$coach_members_table} cm
            INNER JOIN {$coaches_table} c ON cm.coach_id = c.ID
            WHERE cm.member_id = %d AND cm.status = 'active'
            ORDER BY cm.is_primary DESC, c.display_name ASC
        ", $member_id));
        
        if ($wpdb->last_error) {
            error_log('JGK AJAX Error - Coaches query: ' . $wpdb->last_error);
            $coaches = array(); // Continue without coaches
        }
        
        // Get parents/guardians (check if table exists first)
        $parents = array();
        if ($wpdb->get_var("SHOW TABLES LIKE '{$parents_table}'") == $parents_table) {
            $parents = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    parent_name as name,
                    relationship,
                    phone,
                    email
                FROM {$parents_table}
                WHERE member_id = %d
                ORDER BY 
                    CASE relationship
                        WHEN 'father' THEN 1
                        WHEN 'mother' THEN 2
                        WHEN 'guardian' THEN 3
                        ELSE 4
                    END
            ", $member_id));
            
            if ($wpdb->last_error) {
                error_log('JGK AJAX Error - Parents query: ' . $wpdb->last_error);
                $parents = array(); // Continue without parents
            }
        }
        
        // Get profile image URL
        $profile_image = '';
        if (!empty($member->user_id)) {
            $avatar_id = get_user_meta($member->user_id, 'jgk_profile_image', true);
            if ($avatar_id) {
                $profile_image = wp_get_attachment_url($avatar_id);
            }
        }
        
        // Calculate age from date of birth
        $age = '';
        if (!empty($member->date_of_birth)) {
            try {
                $dob = new DateTime($member->date_of_birth);
                $now = new DateTime();
                $age = $dob->diff($now)->y;
            } catch (Exception $e) {
                error_log('JGK AJAX Error - Age calculation: ' . $e->getMessage());
                $age = 'N/A';
            }
        }
        
        // Format coaches array
        $coaches_array = array();
        if (is_array($coaches)) {
            foreach ($coaches as $coach) {
                $coaches_array[] = array(
                    'id' => $coach->coach_id,
                    'name' => $coach->name,
                    'is_primary' => (bool)$coach->is_primary
                );
            }
        }
        
        // Format parents array
        $parents_array = array();
        if (is_array($parents)) {
            foreach ($parents as $parent) {
                $parents_array[] = array(
                    'name' => $parent->name ?? '',
                    'relationship' => $parent->relationship ?? '',
                    'phone' => $parent->phone ?? '',
                    'email' => $parent->email ?? ''
                );
            }
        }
        
        // Prepare response data with safe property access
        $response = array(
            'id' => $member->id ?? 0,
            'display_name' => $member->display_name ?: (($member->first_name ?? '') . ' ' . ($member->last_name ?? '')),
            'first_name' => $member->first_name ?? '',
            'last_name' => $member->last_name ?? '',
            'email' => $member->user_email ?? '',
            'phone' => $member->phone ?? '',
            'date_of_birth' => !empty($member->date_of_birth) ? date('F j, Y', strtotime($member->date_of_birth)) : '',
            'age' => $age,
            'gender' => $member->gender ?? '',
            'status' => $member->status ?? '',
            'membership_type' => $member->membership_type ?? '',
            'membership_number' => $member->membership_number ?? '',
            'club_name' => $member->club_name ?? '',
            'handicap' => $member->handicap_index ?? '',
            'date_joined' => !empty($member->date_joined) ? date('F j, Y', strtotime($member->date_joined)) : '',
            'address' => $member->address ?? '',
            'biography' => $member->biography ?? '',
            'emergency_contact_name' => $member->emergency_contact_name ?? '',
            'emergency_contact_phone' => $member->emergency_contact_phone ?? '',
            'profile_image' => $profile_image,
            'coaches' => $coaches_array,
            'parents' => $parents_array
        );
        
        wp_send_json_success($response);
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'An error occurred',
            'error' => $e->getMessage()
        ));
    }
}
```

## 📝 Étapes de Correction (À Faire Maintenant)

### Étape 1 : Diagnostic Rapide

Ouvrir la console JavaScript du navigateur (F12) et cliquer sur "View Details". Regarder l'erreur exacte affichée.

**Erreurs possibles :**
- `403 Forbidden` → Problème de permissions
- `500 Internal Server Error` → Erreur PHP/SQL
- `Network Error` → Problème AJAX/URL

### Étape 2 : Vérifier les Logs

Consulter : `wp-content/debug.log` pour l'erreur PHP exacte.

### Étape 3 : Appliquer la Correction

Je vais maintenant modifier le fichier `juniorgolfkenya.php` pour corriger le problème.

## 🎯 Test Après Correction

1. Vider le cache du navigateur
2. Se reconnecter à WordPress
3. Aller dans Members
4. Cliquer sur "View Details" d'un membre
5. Vérifier que la modal s'affiche avec les informations

## 📞 Si le Problème Persiste

1. Partager le contenu de `wp-content/debug.log`
2. Partager l'erreur dans la console JavaScript (F12)
3. Vérifier que vous êtes connecté en tant qu'administrateur
4. Exécuter le script SQL de correction des rôles

---

**Je vais maintenant appliquer la correction dans le code...**
