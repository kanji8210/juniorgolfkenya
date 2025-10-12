# 🔧 MIGRATION URGENTE : Ajout de la colonne is_public

## ❌ Problème Actuel

**Erreur SQL :**
```
WordPress database error: [Unknown column 'is_public' in 'field list']
UPDATE `wp_jgk_members` SET ... `is_public` = '0' ...
```

**Cause :** La colonne `is_public` n'existe pas dans la table `wp_jgk_members`.

---

## ✅ Solution : 3 Méthodes

### 🚀 MÉTHODE 1 : Script PHP Automatique (RECOMMANDÉ - 1 minute)

**Étape 1 :** Ouvrez votre navigateur  
**Étape 2 :** Allez à :
```
http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/check_table_structure.php
```

**Étape 3 :** Vous verrez :
- ✅ Liste de toutes les colonnes existantes
- ❌ Message "La colonne 'is_public' n'existe PAS"
- 🔧 Bouton "Ajouter la colonne is_public maintenant"

**Étape 4 :** Cliquez sur le bouton bleu

**Étape 5 :** Rechargez la page pour vérifier → ✅ Colonne ajoutée !

---

### 🗃️ MÉTHODE 2 : Via phpMyAdmin (2 minutes)

**Étape 1 :** Ouvrez phpMyAdmin
```
http://localhost/phpmyadmin
```

**Étape 2 :** Sélectionnez votre base WordPress (ex: `wordpress`)

**Étape 3 :** Cliquez sur l'onglet **SQL**

**Étape 4 :** Copiez-collez cette commande :
```sql
ALTER TABLE wp_jgk_members 
ADD COLUMN is_public tinyint(1) NOT NULL DEFAULT 0 
COMMENT 'Contrôle de visibilité: 0=caché, 1=public'
AFTER parental_consent;
```

**Étape 5 :** Cliquez sur **Exécuter**

**Étape 6 :** Vérifiez :
```sql
SHOW COLUMNS FROM wp_jgk_members LIKE 'is_public';
```

**Résultat attendu :**
| Field | Type | Null | Key | Default | Extra |
|-------|------|------|-----|---------|-------|
| is_public | tinyint(1) | NO | | 0 | |

---

### 📄 MÉTHODE 3 : Fichier SQL (3 minutes)

**Étape 1 :** Un fichier SQL complet est déjà créé :
```
add_is_public_column.sql
```

**Étape 2 :** Ouvrez phpMyAdmin → SQL

**Étape 3 :** Cliquez sur **"Importer un fichier"**

**Étape 4 :** Sélectionnez `add_is_public_column.sql`

**Étape 5 :** Cliquez sur **Exécuter**

---

## ✅ Vérification Après Migration

### Test 1 : Vérifier la colonne existe

**Via SQL :**
```sql
DESCRIBE wp_jgk_members;
```

**Cherchez cette ligne :**
```
is_public | tinyint(1) | NO | | 0 |
```

### Test 2 : Éditer un membre

1. Allez à : **JGK Dashboard > Members**
2. Cliquez sur un membre
3. **Cherchez le champ avec fond bleu** : 🌐 Public Visibility Control
4. Changez la valeur
5. Cliquez **Update Member**
6. **Résultat attendu :** ✅ "Member updated successfully"

### Test 3 : Vérifier en base

```sql
SELECT id, first_name, last_name, is_public 
FROM wp_jgk_members 
WHERE id = 20;
```

**Résultat attendu :**
| id | first_name | last_name | is_public |
|----|------------|-----------|-----------|
| 20 | TEST | MEMBER | 0 ou 1 |

---

## 📊 Commandes SQL Utiles

### Compter les membres par visibilité
```sql
SELECT 
    CASE 
        WHEN is_public = 1 THEN '✅ Public'
        ELSE '🔒 Privé'
    END as visibilite,
    COUNT(*) as nombre
FROM wp_jgk_members
GROUP BY is_public;
```

### Lister les membres avec visibilité
```sql
SELECT 
    id,
    CONCAT(first_name, ' ', last_name) as nom,
    membership_number,
    CASE 
        WHEN is_public = 1 THEN '✅ Public'
        ELSE '🔒 Privé'
    END as visibilite
FROM wp_jgk_members
ORDER BY id DESC
LIMIT 10;
```

### Rendre publics les membres actifs avec consentements
```sql
UPDATE wp_jgk_members 
SET is_public = 1 
WHERE status = 'active' 
  AND consent_photography = 'yes' 
  AND parental_consent = 1;
```

### Rendre TOUS les membres publics
```sql
UPDATE wp_jgk_members 
SET is_public = 1 
WHERE status = 'active';
```

### Tout laisser privé (défaut recommandé)
```sql
-- Ne rien faire, is_public = 0 par défaut
-- Les admins pourront activer manuellement
```

---

## 🔄 Mettre à Jour l'Activator (Futur)

Pour que la colonne soit créée automatiquement lors de nouvelles installations :

**Fichier :** `includes/class-juniorgolfkenya-activator.php`

**Cherchez la création de la table `wp_jgk_members`** (ligne ~70)

**Ajoutez après `parental_consent` :**
```php
parental_consent tinyint(1) DEFAULT 0,
is_public tinyint(1) NOT NULL DEFAULT 0,  // ← AJOUTER CETTE LIGNE
is_public tinyint(1) DEFAULT 0,
created_at datetime DEFAULT CURRENT_TIMESTAMP,
```

---

## 🚨 Que Faire Si Ça Ne Marche Toujours Pas

### Erreur : "Column already exists"

**Signification :** La colonne existe déjà !  
**Solution :** Vérifiez avec :
```sql
SHOW COLUMNS FROM wp_jgk_members LIKE 'is_public';
```

Si elle existe, le problème est ailleurs.

### Erreur : "Access denied"

**Signification :** Votre utilisateur MySQL n'a pas les droits ALTER  
**Solution :** Utilisez un compte avec privilèges complets (ex: root)

### Erreur : "Table doesn't exist"

**Signification :** La table `wp_jgk_members` n'existe pas  
**Solution :** Réinstallez le plugin ou créez la table manuellement

---

## 📋 Checklist Complète

- [ ] ✅ Ouvrir `check_table_structure.php` dans le navigateur
- [ ] ✅ Vérifier que `is_public` n'existe PAS
- [ ] ✅ Cliquer sur "Ajouter la colonne is_public maintenant"
- [ ] ✅ Voir le message "Colonne ajoutée avec succès"
- [ ] ✅ Recharger la page
- [ ] ✅ Voir `is_public` dans la liste (fond vert)
- [ ] ✅ Éditer un membre dans WordPress
- [ ] ✅ Voir le champ 🌐 Public Visibility Control (fond bleu)
- [ ] ✅ Changer la valeur et sauvegarder
- [ ] ✅ Voir "Member updated successfully"
- [ ] ✅ Vérifier en base que `is_public` = 0 ou 1
- [ ] ✅ Tester sur plusieurs membres

---

## 🎯 Résumé Ultra-Rapide

**1 minute pour corriger :**

```
1. Ouvrir: http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/check_table_structure.php
2. Cliquer: "Ajouter la colonne is_public maintenant"
3. Recharger la page
4. Tester: Éditer un membre → Changer visibilité → Sauvegarder
5. ✅ Terminé !
```

---

## 📞 Fichiers Créés

1. ✅ **check_table_structure.php** - Script de vérification et ajout automatique
2. ✅ **add_is_public_column.sql** - Script SQL complet
3. ✅ **MIGRATION_IS_PUBLIC.md** - Ce guide (ce fichier)

---

## 🔐 Pourquoi Cette Colonne ?

La colonne `is_public` permet de :

- 🔒 **Protéger la vie privée** des membres mineurs
- ✅ **Respecter les choix** des parents
- 📊 **Contrôler finement** qui apparaît publiquement
- 🎯 **Filtrer** les annuaires et galeries publiques

**Valeurs :**
- `0` = 🔒 Caché du public (défaut sécurisé)
- `1` = ✅ Visible publiquement

---

**Allez-y maintenant et suivez la Méthode 1 !** 🚀
