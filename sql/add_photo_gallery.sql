-- =============================================
-- Ajout de la table pour les galeries de photos
-- À exécuter dans phpMyAdmin
-- =============================================

USE kica7829_voyages;

-- Créer la table pour les photos de destinations
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

-- Vérifier la création
SHOW TABLES LIKE 'destination_photos';
DESCRIBE destination_photos;
