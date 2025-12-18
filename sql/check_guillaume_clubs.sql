-- Vérifier les clubs de Guillaume LECOMTE

-- 1. Trouver l'utilisateur
SELECT id, nom, prenom, email FROM users WHERE nom = 'LECOMTE' AND prenom = 'Guillaume';

-- 2. Voir ses clubs dans membres_clubs (remplacer XX par l'ID trouvé)
-- SELECT * FROM membres_clubs WHERE user_id = XX;

-- 3. Voir les détails complets avec jointure (remplacer XX par l'ID trouvé)
-- SELECT u.id, u.nom, u.prenom, c.id as club_id, c.nom as club_nom, c.ville
-- FROM users u
-- INNER JOIN membres_clubs mc ON u.id = mc.user_id
-- INNER JOIN clubs c ON mc.club_id = c.id
-- WHERE u.id = XX;

-- 4. Compter les clubs par utilisateur
SELECT u.id, u.nom, u.prenom, COUNT(mc.club_id) as nb_clubs
FROM users u
LEFT JOIN membres_clubs mc ON u.id = mc.user_id
WHERE u.nom = 'LECOMTE' AND u.prenom = 'Guillaume'
GROUP BY u.id;
