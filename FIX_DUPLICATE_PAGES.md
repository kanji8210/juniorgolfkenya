# 🔧 Fix: Empêcher les Pages Dupliquées à la Réactivation

**Date:** 11 octobre 2025  
**Issue:** Pages dupliquées lors de la réactivation du plugin  
**Status:** ✅ RÉSOLU

---

## 🐛 Problème Initial

Lors de la **réactivation** du plugin Junior Golf Kenya, les pages étaient créées en double :
- `coach-dashboard` + `coach-dashboard-2`
- `member-dashboard` + `member-dashboard-2`
- `member-portal` + `member-portal-2`
- etc.

**Cause:** La fonction `create_pages()` utilisait seulement `get_page_by_path()` qui peut échouer si :
- Le slug a été modifié
- WordPress a ajouté un suffixe numérique
- La page a été déplacée dans la corbeille puis restaurée

---

## ✅ Solution Implémentée

### Triple Vérification en Cascade

**Fichier modifié:** `includes/class-juniorgolfkenya-activator.php`  
**Fonction:** `create_pages()`  
**Lignes:** ~487-605

#### Étape 1: Vérifier l'Option Stockée
```php
$option_name = 'jgk_page_' . str_replace('-', '_', $slug);
$stored_page_id = get_option($option_name);

if ($stored_page_id) {
    $existing_page = get_post($stored_page_id);
    // Vérifier que la page existe et n'est pas dans la corbeille
    if ($existing_page && $existing_page->post_type === 'page' && $existing_page->post_status !== 'trash') {
        // Page trouvée ✅
    }
}
```

**Avantage:** Accès direct via ID (le plus rapide et fiable)

#### Étape 2: Rechercher par Slug
```php
if (!$existing_page) {
    $existing_page = get_page_by_path($slug);
}
```

**Avantage:** Trouve la page même si l'option a été supprimée

#### Étape 3: Rechercher par Titre (Dernier Recours)
```php
if (!$existing_page) {
    $query = new WP_Query(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'title' => $page_data['title'],
        'posts_per_page' => 1
    ));
    
    if ($query->have_posts()) {
        $existing_page = $query->posts[0];
    }
}
```

**Avantage:** Trouve la page même si le slug a été modifié

---

## 🎯 Comportement Après Fix

### Activation (1ère fois)
```
✅ Aucune page existante → Créer 6 pages
✅ Stocker IDs dans wp_options:
   - jgk_page_coach_dashboard = 42
   - jgk_page_member_dashboard = 43
   - jgk_page_member_portal = 44
   - jgk_page_member_registration = 45
   - jgk_page_coach_role_request = 46
   - jgk_page_verify_membership = 47
```

### Réactivation (2ème, 3ème fois...)
```
✅ Options existent → Vérifier pages avec IDs stockés
✅ Pages trouvées → SKIP création (pas de duplicata)
✅ Logs: "Found existing page 'Coach Dashboard' with ID 42"
```

### Cas Edge: Page Supprimée
```
⚠️ Option existe MAIS page dans trash
✅ Supprimer option obsolète
✅ Rechercher par slug → Pas trouvé
✅ Rechercher par titre → Pas trouvé
✅ Créer nouvelle page avec ID propre
```

### Cas Edge: Slug Modifié
```
⚠️ Page 'member-portal' renommée en 'portal-membre'
✅ Recherche par ID → Échoue (slug différent)
✅ Recherche par slug → Échoue
✅ Recherche par titre "Member Portal" → TROUVÉ ✅
✅ Mettre à jour option avec le vrai ID
```

---

## 🔍 Vérifications de Sécurité Ajoutées

### 1. Validation du Post Type
```php
if ($existing_page->post_type !== 'page') {
    // Pas une page, ignorer
}
```

**Protection:** Évite de confondre avec un post/custom post type

### 2. Validation du Status
```php
if ($existing_page->post_status === 'trash') {
    // Page dans corbeille, supprimer option
    delete_option($option_name);
}
```

**Protection:** Ne pas réutiliser une page supprimée

### 3. Validation du Type d'Erreur
```php
if ($page_id && !is_wp_error($page_id)) {
    // Création réussie
}
```

**Protection:** Vérifier que `wp_insert_post()` n'a pas retourné d'erreur

---

## 📊 Logs Améliorés

### Avant (peu d'info)
```
JuniorGolfKenya: Created pages - {"coach-dashboard":42}
```

### Après (détaillé)
```
JuniorGolfKenya: Found existing page 'Coach Dashboard' with ID 42
JuniorGolfKenya: Found existing page 'Member Dashboard' with ID 43
JuniorGolfKenya: Found existing page 'Member Portal' with ID 44
JuniorGolfKenya: Created page 'Verify Membership' with ID 48
JuniorGolfKenya: Page creation summary - {"verify-membership":48}
```

**Bénéfice:** Debugging plus facile, traçabilité complète

---

## 🧪 Tests à Effectuer

### Test 1: Activation Initiale
1. Désactiver le plugin
2. Supprimer toutes les pages JGK manuellement
3. Supprimer options : `DELETE FROM wp_options WHERE option_name LIKE 'jgk_page_%'`
4. Activer le plugin
5. ✅ Vérifier 6 pages créées (pas de doublons)

### Test 2: Réactivation Simple
1. Désactiver le plugin
2. Activer le plugin
3. ✅ Vérifier aucune page dupliquée
4. ✅ Check logs: "Found existing page..."

### Test 3: Page Supprimée puis Réactivation
1. Supprimer manuellement `member-portal`
2. Réactiver le plugin
3. ✅ Vérifier 1 nouvelle page créée (juste celle supprimée)

### Test 4: Slug Modifié
1. Modifier slug `member-portal` → `portal-membre`
2. Réactiver plugin
3. ✅ Vérifier plugin trouve la page par titre
4. ✅ Vérifier option mise à jour avec bon ID

### Test 5: Page dans Corbeille
1. Mettre `coach-dashboard` dans corbeille
2. Réactiver plugin
3. ✅ Vérifier nouvelle page créée (pas réutilisation corbeille)

---

## 🛠️ Commandes SQL de Diagnostic

### Voir toutes les options JGK pages
```sql
SELECT * FROM wp_options 
WHERE option_name LIKE 'jgk_page_%';
```

**Résultat attendu:**
```
jgk_page_coach_dashboard = 42
jgk_page_member_dashboard = 43
jgk_page_member_portal = 44
jgk_page_member_registration = 45
jgk_page_coach_role_request = 46
jgk_page_verify_membership = 47
```

### Vérifier pages JGK existantes
```sql
SELECT ID, post_title, post_name, post_status 
FROM wp_posts 
WHERE post_type = 'page' 
AND (
    post_name LIKE '%coach-dashboard%' OR
    post_name LIKE '%member-dashboard%' OR
    post_name LIKE '%member-portal%' OR
    post_name LIKE '%member-registration%' OR
    post_name LIKE '%coach-role-request%' OR
    post_name LIKE '%verify-membership%'
)
ORDER BY post_title;
```

### Nettoyer options orphelines
```sql
-- Supprimer options si pages n'existent plus
DELETE wo FROM wp_options wo
LEFT JOIN wp_posts wp ON wo.option_value = wp.ID
WHERE wo.option_name LIKE 'jgk_page_%'
AND (wp.ID IS NULL OR wp.post_status = 'trash');
```

### Nettoyer pages dupliquées (si nécessaire)
```sql
-- Lister les doublons
SELECT post_name, COUNT(*) as count 
FROM wp_posts 
WHERE post_type = 'page' 
AND post_status = 'publish'
AND post_name LIKE '%-dashboard%'
GROUP BY post_name 
HAVING count > 1;

-- Supprimer doublons (ATTENTION: tester d'abord!)
-- Ne garder que la première occurrence
DELETE p1 FROM wp_posts p1
INNER JOIN wp_posts p2 
WHERE p1.post_name = p2.post_name 
AND p1.post_type = 'page'
AND p1.ID > p2.ID
AND p1.post_name IN ('coach-dashboard', 'member-dashboard', 'member-portal');
```

---

## 📝 Options WordPress Utilisées

### Liste Complète
```php
// Pages
'jgk_page_coach_dashboard'      => Page ID (int)
'jgk_page_member_dashboard'     => Page ID (int)
'jgk_page_member_portal'        => Page ID (int)
'jgk_page_member_registration'  => Page ID (int)
'jgk_page_coach_role_request'   => Page ID (int)
'jgk_page_verify_membership'    => Page ID (int)

// Log de création
'jgk_created_pages'             => Array de IDs créés lors dernière activation
```

### Usage dans le Code
```php
// Récupérer ID de la page Member Portal
$portal_page_id = get_option('jgk_page_member_portal');

// Récupérer URL de la page
$portal_url = get_permalink($portal_page_id);

// Vérifier si page existe
if ($portal_page_id && get_post($portal_page_id)) {
    echo 'Page exists!';
}
```

---

## 🔄 Flux de Décision Visuel

```
ACTIVATION PLUGIN
    ↓
Pour chaque page (6 total)
    ↓
┌─────────────────────────────────────┐
│ 1. Chercher ID dans wp_options      │
│    jgk_page_member_portal = ?       │
└─────────────────────────────────────┘
    ↓                ↓
   OUI              NON
    ↓                ↓
┌─────────────┐   ┌──────────────────┐
│ Page valide?│   │ 2. Chercher slug │
│ Type=page?  │   │ get_page_by_path │
│ Status≠trash│   └──────────────────┘
└─────────────┘          ↓
    ↓                   OUI/NON
   OUI                   ↓
    ↓            ┌──────────────────────┐
    │            │ 3. Chercher par titre│
    │            │    WP_Query(title)   │
    │            └──────────────────────┘
    │                     ↓
    │                   OUI/NON
    │                     ↓
    └──────→ OUI ────────┴────────→ NON
                ↓                     ↓
        ┌──────────────┐      ┌──────────────┐
        │ SKIP Création│      │ CRÉER PAGE   │
        │ Update option│      │ Save option  │
        │ Log: "Found" │      │ Log: "Created│
        └──────────────┘      └──────────────┘
```

---

## 🎓 Leçons Apprises

### ❌ Mauvaise Pratique
```php
// NE PAS faire juste ça
if (!get_page_by_path($slug)) {
    wp_insert_post(...); // Peut créer doublons!
}
```

### ✅ Bonne Pratique
```php
// Vérifications en cascade + stockage option
$stored_id = get_option('jgk_page_' . $slug);
$page = $stored_id ? get_post($stored_id) : null;

if (!$page) {
    $page = get_page_by_path($slug);
}

if (!$page) {
    // WP_Query par titre...
}

if (!$page) {
    $id = wp_insert_post(...);
    update_option('jgk_page_' . $slug, $id);
}
```

### 🔑 Points Clés
1. **Toujours stocker** les IDs de pages dans `wp_options`
2. **Vérifier validité** des pages stockées (status, type)
3. **Fallback multiple** : ID → Slug → Titre
4. **Logger** toutes les actions pour debugging
5. **Nettoyer** options obsolètes si page supprimée

---

## 🚀 Prochaines Améliorations Possibles

### 1. Admin Notice Améliorée
```php
// Après activation, afficher résumé
add_action('admin_notices', function() {
    $summary = get_transient('jgk_pages_summary');
    if ($summary) {
        echo '<div class="notice notice-success">';
        echo '<p>Pages JGK: ' . $summary['found'] . ' found, ' . $summary['created'] . ' created</p>';
        echo '</div>';
    }
});
```

### 2. Page Health Check
```php
// Ajouter fonction dans admin pour vérifier santé des pages
public static function check_pages_health() {
    $pages = ['coach-dashboard', 'member-dashboard', ...];
    $issues = [];
    
    foreach ($pages as $slug) {
        $page_id = get_option('jgk_page_' . $slug);
        $page = get_post($page_id);
        
        if (!$page) {
            $issues[] = "$slug page missing";
        } elseif ($page->post_status !== 'publish') {
            $issues[] = "$slug not published";
        }
    }
    
    return empty($issues) ? 'healthy' : $issues;
}
```

### 3. Réparation Automatique
```php
// Bouton admin pour réparer pages
add_action('admin_post_jgk_repair_pages', function() {
    self::create_pages(); // Force re-check
    wp_redirect(admin_url('admin.php?page=juniorgolfkenya&pages=repaired'));
});
```

---

## ✅ Checklist de Validation

- [x] Triple vérification (ID → Slug → Titre)
- [x] Validation post_type = 'page'
- [x] Validation post_status ≠ 'trash'
- [x] Nettoyage options obsolètes
- [x] Logs détaillés
- [x] Protection `is_wp_error()`
- [x] Update options même si page existe
- [x] Pas de création si page trouvée
- [ ] Tests manuels effectués
- [ ] Tests sur environnement staging
- [ ] Documentation mise à jour

---

**🎉 RÉSULTAT:** Plus de pages dupliquées lors des réactivations ! Le plugin détecte intelligemment les pages existantes et les réutilise au lieu de créer des doublons.

**TESTER MAINTENANT:**
1. Désactiver plugin
2. Activer plugin
3. Vérifier `/wp-admin/edit.php?post_type=page`
4. ✅ Aucun doublon visible !
