-- =====================================================
-- Script SQL pour Mettre Tous les Membres en PUBLIC
-- =====================================================
-- 
-- Ce script met à jour tous les membres existants pour les rendre publics par défaut.
-- 
-- UTILISATION:
-- 1. Ouvrez phpMyAdmin
-- 2. Sélectionnez votre base de données WordPress
-- 3. Allez dans l'onglet "SQL"
-- 4. Copiez-collez les commandes ci-dessous
-- 5. Cliquez sur "Exécuter"
--
-- =====================================================
-- 1. Vérifier l'état actuel
SELECT
    is_public,
    COUNT(*) as nombre_membres,
    CASE
        WHEN is_public = 1 THEN '👁️ PUBLIC'
        ELSE '🔒 HIDDEN'
    END as visibilite
FROM
    wp_jgk_members
GROUP BY
    is_public;

-- 2. Mettre tous les membres en PUBLIC
UPDATE
    wp_jgk_members
SET
    is_public = 1
WHERE
    is_public = 0;

-- 3. Vérifier le résultat
SELECT
    is_public,
    COUNT(*) as nombre_membres,
    CASE
        WHEN is_public = 1 THEN '👁️ PUBLIC'
        ELSE '🔒 HIDDEN'
    END as visibilite
FROM
    wp_jgk_members
GROUP BY
    is_public;

-- 4. Optionnel: Voir tous les membres avec leur nouvelle visibilité
SELECT
    id,
    first_name,
    last_name,
    membership_number,
    CASE
        WHEN is_public = 1 THEN '👁️ PUBLIC'
        ELSE '🔒 HIDDEN'
    END as visibilite
FROM
    wp_jgk_members
ORDER BY
    id;

-- =====================================================
-- RÉSULTAT ATTENDU:
-- Tous les membres devraient maintenant être en PUBLIC (is_public = 1)
-- =====================================================