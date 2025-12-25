-- Ajouter les champs pour le terrain d'attache à la table users
-- Permet de stocker soit un aérodrome soit une base ULM

-- Ajouter le type de terrain (aerodrome ou base_ulm)
ALTER TABLE users ADD COLUMN terrain_attache_type ENUM('aerodrome', 'base_ulm') NULL AFTER telephone;

-- Ajouter l'ID du terrain d'attache
ALTER TABLE users ADD COLUMN terrain_attache_id INT NULL AFTER terrain_attache_type;

-- Créer un index pour optimiser les recherches
CREATE INDEX idx_terrain_attache ON users(terrain_attache_type, terrain_attache_id);
