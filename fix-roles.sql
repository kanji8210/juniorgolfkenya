-- Script SQL de correction des rôles JGK
-- À exécuter dans phpMyAdmin

-- 1. Vérifier les utilisateurs avec anciens rôles
SELECT user_id, meta_value 
FROM wp_usermeta 
WHERE meta_key = 'wp_capabilities' 
AND (meta_value LIKE '%jgf_member%' OR meta_value LIKE '%jgf_coach%' OR meta_value LIKE '%jgf_staff%');

-- 2. Corriger les rôles membres
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:10:\"jgf_member\"', 's:10:\"jgk_member\"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_member%';

-- 3. Corriger les rôles coach
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:9:\"jgf_coach\"', 's:9:\"jgk_coach\"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_coach%';

-- 4. Corriger les rôles staff
UPDATE wp_usermeta 
SET meta_value = REPLACE(meta_value, 's:9:\"jgf_staff\"', 's:9:\"jgk_staff\"')
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%jgf_staff%';

-- 5. Vérifier la correction
SELECT user_id, meta_value 
FROM wp_usermeta 
WHERE meta_key = 'wp_capabilities' 
AND (meta_value LIKE '%jgk_member%' OR meta_value LIKE '%jgk_coach%' OR meta_value LIKE '%jgk_staff%');
