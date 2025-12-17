-- VOYAGES - Schéma de base de données MySQL

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des voyages
CREATE TABLE IF NOT EXISTS voyages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    destination VARCHAR(200) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    budget DECIMAL(10, 2),
    statut ENUM('planifie', 'en_cours', 'termine', 'annule') DEFAULT 'planifie',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_date_debut (date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des itinéraires
CREATE TABLE IF NOT EXISTS itineraires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voyage_id INT NOT NULL,
    jour INT NOT NULL,
    date DATE NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    lieu VARCHAR(200),
    heure_debut TIME,
    heure_fin TIME,
    ordre INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_voyage_id (voyage_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voyage_id INT NOT NULL,
    type_reservation ENUM('vol', 'hotel', 'activite', 'transport', 'restaurant', 'autre') NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    numero_confirmation VARCHAR(100),
    date_reservation DATE,
    heure_debut TIME,
    heure_fin TIME,
    prix DECIMAL(10, 2),
    statut ENUM('confirmee', 'en_attente', 'annulee') DEFAULT 'confirmee',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_voyage_id (voyage_id),
    INDEX idx_type (type_reservation),
    INDEX idx_date (date_reservation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des documents (photos, tickets, etc.)
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voyage_id INT NOT NULL,
    type_document ENUM('photo', 'document', 'billet', 'recu', 'autre') NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(500) NOT NULL,
    taille_fichier INT,
    type_mime VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_voyage_id (voyage_id),
    INDEX idx_type (type_document)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notes de voyage
CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voyage_id INT NOT NULL,
    titre VARCHAR(200),
    contenu TEXT NOT NULL,
    date_note DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_voyage_id (voyage_id),
    INDEX idx_date (date_note)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
