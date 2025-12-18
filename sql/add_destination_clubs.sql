-- =============================================
-- Table de liaison destination-clubs
-- À exécuter dans phpMyAdmin
-- =============================================

USE kica7829_voyages;

-- Créer la table de liaison destination_clubs
CREATE TABLE IF NOT EXISTS destination_clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    club_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_destination_club (destination_id, club_id),
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    INDEX idx_destination (destination_id),
    INDEX idx_club (club_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vérifier la création
SELECT COUNT(*) as nb_liaisons FROM destination_clubs;
