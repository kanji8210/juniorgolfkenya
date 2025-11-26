-- ========================================
-- SCRIPT SQL DE TEST : Parent Dashboard
-- ========================================
-- Utilisation : Exécuter dans phpMyAdmin ou MySQL CLI
-- But : Créer des données de test pour le Parent Dashboard
-- ========================================
-- 1. CRÉER UN PARENT AVEC 3 ENFANTS
-- ========================================
-- Créer 3 membres juniors (enfants)
INSERT INTO
    wp_jgk_members (
        first_name,
        last_name,
        date_of_birth,
        gender,
        membership_type,
        membership_number,
        status,
        joined_date,
        created_at
    )
VALUES
    (
        'Alice',
        'Johnson',
        '2012-05-15',
        'female',
        'junior',
        'JGK2025001',
        'active',
        '2025-01-10',
        NOW()
    ),
    (
        'Bob',
        'Johnson',
        '2014-08-22',
        'male',
        'junior',
        'JGK2025002',
        'approved',
        '2025-01-15',
        NOW()
    ),
    (
        'Carol',
        'Johnson',
        '2016-03-30',
        'female',
        'junior',
        'JGK2025003',
        'pending',
        '2025-01-20',
        NOW()
    );

-- Récupérer les IDs créés (adapter selon votre base)
SET
    @alice_id = LAST_INSERT_ID();

SET
    @bob_id = @alice_id + 1;

SET
    @carol_id = @alice_id + 2;

-- Lier les 3 enfants au même parent (même email)
INSERT INTO
    wp_jgk_parents_guardians (
        member_id,
        email,
        first_name,
        last_name,
        relationship,
        phone,
        mobile,
        is_primary_contact,
        created_at
    )
VALUES
    (
        @alice_id,
        'parent.johnson@test.com',
        'Jane',
        'Johnson',
        'Mother',
        '+254712345678',
        '+254712345678',
        1,
        NOW()
    ),
    (
        @bob_id,
        'parent.johnson@test.com',
        'Jane',
        'Johnson',
        'Mother',
        '+254712345678',
        '+254712345678',
        1,
        NOW()
    ),
    (
        @carol_id,
        'parent.johnson@test.com',
        'Jane',
        'Johnson',
        'Mother',
        '+254712345678',
        '+254712345678',
        1,
        NOW()
    );

-- Ajouter un paiement pour Alice (active)
INSERT INTO
    wp_jgk_payments (
        member_id,
        amount,
        payment_method,
        transaction_id,
        payment_date,
        status,
        created_at
    )
VALUES
    (
        @alice_id,
        5000,
        'mpesa',
        'MPESA123456789',
        '2025-01-12',
        'completed',
        NOW()
    );

-- ========================================
-- 2. CRÉER UN COMPTE WORDPRESS POUR LE PARENT
-- ========================================
-- IMPORTANT : Exécuter depuis WordPress admin :
-- Utilisateurs → Ajouter
-- Email : parent.johnson@test.com
-- Rôle : Subscriber
-- Ou utiliser ce SQL :
INSERT INTO
    wp_users (
        user_login,
        user_pass,
        user_nicename,
        user_email,
        user_registered,
        display_name
    )
VALUES
    (
        'parent_johnson',
        MD5('test123'),
        'parent_johnson',
        'parent.johnson@test.com',
        NOW(),
        'Jane Johnson'
    );

-- ========================================
-- 3. VÉRIFICATION
-- ========================================
-- Vérifier les membres créés
SELECT
    id,
    CONCAT(first_name, ' ', last_name) as name,
    membership_number,
    status,
    DATE_FORMAT(date_of_birth, '%Y-%m-%d') as dob
FROM
    wp_jgk_members
WHERE
    last_name = 'Johnson';

-- Vérifier les liens parent-enfants
SELECT
    pg.id,
    pg.member_id,
    m.first_name as child_first_name,
    m.last_name as child_last_name,
    m.status as child_status,
    pg.email as parent_email,
    CONCAT(pg.first_name, ' ', pg.last_name) as parent_name
FROM
    wp_jgk_parents_guardians pg
    INNER JOIN wp_jgk_members m ON pg.member_id = m.id
WHERE
    pg.email = 'parent.johnson@test.com';

-- Vérifier les paiements
SELECT
    p.id,
    m.first_name,
    m.last_name,
    p.amount,
    p.payment_method,
    p.status,
    p.payment_date
FROM
    wp_jgk_payments p
    INNER JOIN wp_jgk_members m ON p.member_id = m.id
WHERE
    m.last_name = 'Johnson';

-- ========================================
-- 4. TEST DU PARENT DASHBOARD
-- ========================================
-- Étapes de test manuel :
-- 1. Se connecter à WordPress avec : parent.johnson@test.com / test123
-- 2. Aller sur /parent-dashboard
-- 3. Vérifications attendues :
--    ✓ En-tête affiche : "Welcome, Jane!"
--    ✓ Badge : "3 Children"
--    ✓ Statistiques :
--      - Total Children: 3
--      - Active Memberships: 1 (Alice)
--      - Pending Payments: 1 (Bob)
--      - Total Paid: KES 5,000
--    ✓ Bannière paiement : "1 membership needs payment"
--    ✓ 3 cartes enfants :
--      - Alice : Badge VERT "Active", "Membership Active"
--      - Bob : Badge ORANGE "Approved", bouton "Pay Now"
--      - Carol : Badge GRIS "Pending", "Awaiting Admin Approval"
-- ========================================
-- 5. SCÉNARIO DE TEST : PAYER POUR BOB
-- ========================================
-- Simuler un paiement pour Bob (sans WooCommerce)
-- Après que le parent clique "Pay Now" et paye via M-Pesa :
UPDATE
    wp_jgk_members
SET
    status = 'active',
    expiry_date = DATE_ADD(NOW(), INTERVAL 1 YEAR)
WHERE
    id = @bob_id;

INSERT INTO
    wp_jgk_payments (
        member_id,
        amount,
        payment_method,
        transaction_id,
        payment_date,
        status,
        created_at
    )
VALUES
    (
        @bob_id,
        5000,
        'mpesa',
        'MPESA987654321',
        NOW(),
        'completed',
        NOW()
    );

-- Vérifier le résultat :
-- Rafraîchir le parent dashboard
-- Attendu :
--   - Statistiques : Active Memberships: 2
--   - Statistiques : Pending Payments: 0
--   - Statistiques : Total Paid: KES 10,000
--   - Bob : Badge VERT "Active", plus de bouton "Pay Now"
--   - Bannière paiement : Disparue (plus de paiements en attente)
-- ========================================
-- 6. NETTOYAGE (Si besoin)
-- ========================================
-- Supprimer les données de test
-- ATTENTION : Adapter les IDs selon votre base !
-- DELETE FROM wp_jgk_payments WHERE member_id IN (@alice_id, @bob_id, @carol_id);
-- DELETE FROM wp_jgk_parents_guardians WHERE email = 'parent.johnson@test.com';
-- DELETE FROM wp_jgk_members WHERE last_name = 'Johnson' AND first_name IN ('Alice', 'Bob', 'Carol');
-- DELETE FROM wp_users WHERE user_email = 'parent.johnson@test.com';
-- ========================================
-- 7. TEST AVANCÉ : PARENT AVEC 5 ENFANTS
-- ========================================
-- Créer 5 enfants avec différents statuts pour tester toutes les situations
INSERT INTO
    wp_jgk_members (
        first_name,
        last_name,
        date_of_birth,
        gender,
        membership_type,
        membership_number,
        status,
        joined_date,
        expiry_date,
        created_at
    )
VALUES
    (
        'David',
        'Smith',
        '2011-01-10',
        'male',
        'junior',
        'JGK2025010',
        'active',
        '2024-11-01',
        '2025-11-01',
        NOW()
    ),
    (
        'Emma',
        'Smith',
        '2013-04-15',
        'female',
        'junior',
        'JGK2025011',
        'active',
        '2024-11-01',
        '2025-11-01',
        NOW()
    ),
    (
        'Frank',
        'Smith',
        '2015-07-20',
        'male',
        'junior',
        'JGK2025012',
        'approved',
        '2025-01-25',
        NULL,
        NOW()
    ),
    (
        'Grace',
        'Smith',
        '2017-09-05',
        'female',
        'junior',
        'JGK2025013',
        'approved',
        '2025-01-25',
        NULL,
        NOW()
    ),
    (
        'Henry',
        'Smith',
        '2018-11-12',
        'male',
        'junior',
        'JGK2025014',
        'pending',
        '2025-01-26',
        NULL,
        NOW()
    );

-- Lier au parent
SET
    @parent_email = 'parent.smith@test.com';

INSERT INTO
    wp_jgk_parents_guardians (
        member_id,
        email,
        first_name,
        last_name,
        relationship,
        phone,
        is_primary_contact,
        created_at
    )
SELECT
    id,
    @parent_email,
    'John',
    'Smith',
    'Father',
    '+254798765432',
    1,
    NOW()
FROM
    wp_jgk_members
WHERE
    last_name = 'Smith'
    AND first_name IN ('David', 'Emma', 'Frank', 'Grace', 'Henry');

-- Ajouter paiements pour les actifs
INSERT INTO
    wp_jgk_payments (
        member_id,
        amount,
        payment_method,
        transaction_id,
        payment_date,
        status,
        created_at
    )
SELECT
    id,
    5000,
    'mpesa',
    CONCAT('MPESA', id),
    '2024-11-05',
    'completed',
    NOW()
FROM
    wp_jgk_members
WHERE
    last_name = 'Smith'
    AND status = 'active';

-- Résultat attendu sur parent dashboard :
-- Total Children: 5
-- Active Memberships: 2 (David, Emma)
-- Pending Payments: 2 (Frank, Grace - approved)
-- Total Paid: KES 10,000
-- Bannière : "2 memberships need payment" → Total Due: KES 10,000
-- ========================================
-- 8. REQUÊTES UTILES POUR DEBUGGING
-- ========================================
-- Trouver tous les parents avec le nombre d'enfants
SELECT
    pg.email,
    CONCAT(pg.first_name, ' ', pg.last_name) as parent_name,
    COUNT(DISTINCT pg.member_id) as total_children,
    SUM(
        CASE
            WHEN m.status = 'active' THEN 1
            ELSE 0
        END
    ) as active_children,
    SUM(
        CASE
            WHEN m.status = 'approved' THEN 1
            ELSE 0
        END
    ) as approved_children,
    SUM(
        CASE
            WHEN m.status = 'pending' THEN 1
            ELSE 0
        END
    ) as pending_children
FROM
    wp_jgk_parents_guardians pg
    INNER JOIN wp_jgk_members m ON pg.member_id = m.id
GROUP BY
    pg.email,
    pg.first_name,
    pg.last_name;

-- Trouver tous les paiements par parent
SELECT
    pg.email as parent_email,
    CONCAT(pg.first_name, ' ', pg.last_name) as parent_name,
    COUNT(p.id) as total_payments,
    SUM(p.amount) as total_paid
FROM
    wp_jgk_parents_guardians pg
    INNER JOIN wp_jgk_members m ON pg.member_id = m.id
    LEFT JOIN wp_jgk_payments p ON m.id = p.member_id
    AND p.status = 'completed'
GROUP BY
    pg.email,
    pg.first_name,
    pg.last_name;

-- Trouver les parents avec des enfants nécessitant un paiement
SELECT
    pg.email,
    CONCAT(pg.first_name, ' ', pg.last_name) as parent_name,
    COUNT(DISTINCT m.id) as children_needing_payment,
    COUNT(DISTINCT m.id) * 5000 as total_amount_due
FROM
    wp_jgk_parents_guardians pg
    INNER JOIN wp_jgk_members m ON pg.member_id = m.id
WHERE
    m.status = 'approved'
GROUP BY
    pg.email,
    pg.first_name,
    pg.last_name
HAVING
    children_needing_payment > 0;

-- ========================================
-- FIN DU SCRIPT
-- ========================================
-- Notes :
-- 1. Adapter les préfixes de tables (wp_) selon votre installation
-- 2. Les IDs générés peuvent varier, vérifier avec SELECT LAST_INSERT_ID()
-- 3. Pour WooCommerce, les paiements réels déclencheront automatiquement les webhooks
-- 4. Password par défaut : test123 (MD5) - À changer en production !
-- Test rapide :
-- SELECT * FROM wp_jgk_parents_guardians WHERE email LIKE '%test.com%';