-- Lier tous les membres existants au club "LES GODASSES VOLANTES"
-- À exécuter une seule fois

-- Vérifier d'abord quels utilisateurs existent
SELECT id, nom, prenom, email FROM users WHERE actif = 1;

-- Vérifier l'ID du club
SELECT id, nom FROM clubs WHERE nom LIKE '%GODASSES%';

-- Insérer les liens pour tous les utilisateurs actifs vers le club ID 1 (LES GODASSES VOLANTES)
-- Ajustez l'ID du club si nécessaire
INSERT INTO membres_clubs (user_id, club_id, date_adhesion, statut, role)
SELECT 
    u.id,
    1 as club_id, -- ID du club LES GODASSES VOLANTES
    CURDATE() as date_adhesion,
    'actif' as statut,
    'membre' as role
FROM users u
WHERE u.actif = 1
AND u.id NOT IN (
    SELECT user_id 
    FROM membres_clubs 
    WHERE club_id = 1
)
ON DUPLICATE KEY UPDATE statut = 'actif';

-- Vérifier le résultat
SELECT 
    u.nom, 
    u.prenom, 
    c.nom as club_nom,
    mc.date_adhesion,
    mc.statut
FROM membres_clubs mc
INNER JOIN users u ON mc.user_id = u.id
INNER JOIN clubs c ON mc.club_id = c.id
WHERE c.id = 1
ORDER BY u.nom, u.prenom;
