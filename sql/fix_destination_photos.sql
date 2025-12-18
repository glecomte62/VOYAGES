-- Script pour corriger la table destination_photos
-- À exécuter dans phpMyAdmin

-- Vérifier si la table existe et sa structure
SHOW TABLES LIKE 'destination_photos';

-- Voir la structure actuelle
DESCRIBE destination_photos;

-- Si la table existe avec de mauvaises colonnes, la supprimer d'abord (décommenter si nécessaire)
-- DROP TABLE IF EXISTS destination_photos;

-- Créer ou recréer la table avec la bonne structure
CREATE TABLE IF NOT EXISTS destination_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    legende VARCHAR(255) DEFAULT NULL,
    ordre INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    INDEX idx_destination_ordre (destination_id, ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vérifier la structure finale
DESCRIBE destination_photos;
