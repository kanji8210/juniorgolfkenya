-- =====================================================
-- AJOUT DE LA COLONNE is_public À LA TABLE wp_jgk_members
-- =====================================================
-- Date: 12 octobre 2025
-- Description: Ajoute la colonne de contrôle de visibilité
-- =====================================================
-- Vérifier la structure actuelle
SHOW COLUMNS
FROM
    wp_jgk_members;

-- Ajouter la colonne is_public
ALTER TABLE
    wp_jgk_members
ADD
    COLUMN is_public tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Contrôle de visibilité: 0=caché du public, 1=visible publiquement'
AFTER
    parental_consent;

-- Vérifier que la colonne a été ajoutée
SHOW COLUMNS
FROM
    wp_jgk_members LIKE 'is_public';

-- Compter les membres par visibilité (devrait tous être 0 au départ)
SELECT
    is_public,
    COUNT(*) as total,
    CONCAT(
        ROUND(
            COUNT(*) * 100.0 / (
                SELECT
                    COUNT(*)
                FROM
                    wp_jgk_members
            ),
            1
        ),
        '%'
    ) as percentage
FROM
    wp_jgk_members
GROUP BY
    is_public;

-- =====================================================
-- OPTIONNEL: Définir certains membres comme publics
-- =====================================================
-- Option 1: Rendre publics les membres actifs avec consentements
UPDATE
    wp_jgk_members
SET
    is_public = 1
WHERE
    status = 'active'
    AND consent_photography = 'yes'
    AND parental_consent = 1;

-- Option 2: Rendre publics TOUS les membres actifs
-- UPDATE wp_jgk_members 
-- SET is_public = 1 
-- WHERE status = 'active';
-- Option 3: Tout laisser privé (par défaut)
-- Ne rien faire, is_public reste à 0
-- =====================================================
-- VÉRIFICATIONS POST-MIGRATION
-- =====================================================
-- 1. Vérifier la structure
DESCRIBE wp_jgk_members;

-- 2. Compter les membres par statut de visibilité
SELECT
    CASE
        WHEN is_public = 1 THEN '✅ Public'
        WHEN is_public = 0 THEN '🔒 Privé'
        ELSE '⚠️ NULL'
    END as visibilite,
    COUNT(*) as nombre
FROM
    wp_jgk_members
GROUP BY
    is_public;

-- 3. Lister quelques membres avec leur visibilité
SELECT
    id,
    first_name,
    last_name,
    membership_number,
    status,
    CASE
        WHEN is_public = 1 THEN '✅ Public'
        ELSE '🔒 Privé'
    END as visibilite
FROM
    wp_jgk_members
ORDER BY
    id DESC
LIMIT
    10;

-- 4. Vérifier qu'il n'y a pas de NULL (ne devrait rien retourner)
SELECT
    COUNT(*) as erreurs_null
FROM
    wp_jgk_members
WHERE
    is_public IS NULL;

-- =====================================================
-- ROLLBACK (si nécessaire)
-- =====================================================
-- Pour supprimer la colonne (ATTENTION: perte de données)
-- ALTER TABLE wp_jgk_members DROP COLUMN is_public;
-- =====================================================
-- FIN DU SCRIPT
-- =====================================================