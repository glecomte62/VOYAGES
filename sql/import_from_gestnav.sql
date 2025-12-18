-- =============================================
-- Import des aérodromes depuis les tables locales
-- À exécuter dans phpMyAdmin
-- =============================================

-- IMPORTANT : Sélectionner la base kica7829_voyages avant d'exécuter ce script

USE kica7829_voyages;

-- =============================================
-- 1. Import des aérodromes depuis aerodromes_fr
-- =============================================

INSERT INTO destinations (
    code_oaci,
    nom,
    aerodrome,
    ville,
    pays,
    latitude,
    longitude,
    acces_ulm,
    acces_avion,
    actif,
    created_at
)
SELECT 
    a.oaci AS code_oaci,
    a.nom AS nom,
    a.nom AS aerodrome,
    a.ville AS ville,
    'France' AS pays,
    a.latitude,
    a.longitude,
    1 AS acces_ulm,
    1 AS acces_avion,
    1 AS actif,
    NOW() AS created_at
FROM aerodromes_fr a
WHERE a.oaci IS NOT NULL 
  AND a.oaci != ''
  AND a.nom IS NOT NULL
ON DUPLICATE KEY UPDATE
    nom = VALUES(nom),
    aerodrome = VALUES(aerodrome),
    ville = VALUES(ville),
    latitude = VALUES(latitude),
    longitude = VALUES(longitude),
    acces_avion = 1,
    updated_at = NOW();

-- =============================================
-- 2. Import des bases ULM depuis ulm_bases_fr
-- =============================================

-- 2a. Mise à jour des aérodromes existants pour marquer l'accès ULM
UPDATE destinations d
INNER JOIN ulm_bases_fr u ON d.code_oaci = u.oaci
SET d.acces_ulm = 1,
    d.updated_at = NOW()
WHERE u.oaci IS NOT NULL 
  AND u.oaci != '';

-- 2b. Insertion des nouvelles bases ULM qui n'ont pas de code OACI correspondant
INSERT INTO destinations (
    code_oaci,
    nom,
    aerodrome,
    ville,
    pays,
    latitude,
    longitude,
    acces_ulm,
    acces_avion,
    actif,
    created_at
)
SELECT 
    u.oaci AS code_oaci,
    u.nom AS nom,
    u.nom AS aerodrome,
    u.ville AS ville,
    'France' AS pays,
    u.latitude,
    u.longitude,
    1 AS acces_ulm,
    0 AS acces_avion,
    1 AS actif,
    NOW() AS created_at
FROM ulm_bases_fr u
WHERE u.nom IS NOT NULL
  AND u.nom != ''
  AND (
    -- Soit pas de code OACI
    u.oaci IS NULL 
    OR u.oaci = ''
    -- Soit code OACI qui n'existe pas encore dans destinations
    OR NOT EXISTS (
        SELECT 1 FROM destinations d WHERE d.code_oaci = u.oaci
    )
  )
  -- Et pas de doublon nom+ville
  AND NOT EXISTS (
    SELECT 1 FROM destinations d 
    WHERE d.nom = u.nom 
      AND (d.ville = u.ville OR (d.ville IS NULL AND u.ville IS NULL))
  )
ON DUPLICATE KEY UPDATE
    acces_ulm = 1,
    updated_at = NOW();

-- =============================================
-- 3. Statistiques après import
-- =============================================

SELECT 
    COUNT(*) as total_destinations,
    SUM(CASE WHEN acces_ulm = 1 THEN 1 ELSE 0 END) as avec_acces_ulm,
    SUM(CASE WHEN acces_avion = 1 THEN 1 ELSE 0 END) as avec_acces_avion,
    SUM(CASE WHEN code_oaci IS NOT NULL AND code_oaci != '' THEN 1 ELSE 0 END) as avec_code_oaci
FROM destinations
WHERE actif = 1;
