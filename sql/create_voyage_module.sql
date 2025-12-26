-- ================================================================
-- MODULE VOYAGES - Schéma complet pour la gestion d'itinéraires
-- ================================================================

-- Table des étapes d'un voyage (chaque terrain visité)
CREATE TABLE IF NOT EXISTS voyage_etapes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voyage_id INT NOT NULL,
    ordre INT NOT NULL, -- Ordre de l'étape dans le voyage
    terrain_type ENUM('aerodrome', 'ulm_base', 'destination') NOT NULL,
    terrain_id INT NOT NULL, -- ID dans la table correspondante
    terrain_nom VARCHAR(200),
    terrain_code_oaci VARCHAR(10),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Dates et horaires
    date_arrivee DATETIME,
    date_depart DATETIME,
    duree_vol_precedent INT, -- Durée en minutes depuis l'étape précédente
    distance_precedent DECIMAL(8, 2), -- Distance en km depuis l'étape précédente
    
    -- Notes pour cette étape
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_voyage_id (voyage_id),
    INDEX idx_ordre (voyage_id, ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des hébergements/nuitées
CREATE TABLE IF NOT EXISTS voyage_hebergements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etape_id INT NOT NULL,
    voyage_id INT NOT NULL,
    
    -- Type d'hébergement
    type ENUM('hotel', 'camping', 'gite', 'chambre_hote', 'bivouac', 'autre') NOT NULL,
    nom VARCHAR(200),
    adresse TEXT,
    telephone VARCHAR(20),
    email VARCHAR(100),
    site_web VARCHAR(255),
    
    -- Dates
    date_checkin DATE NOT NULL,
    date_checkout DATE NOT NULL,
    nombre_nuits INT NOT NULL,
    
    -- Coût
    prix_total DECIMAL(10, 2),
    devise VARCHAR(3) DEFAULT 'EUR',
    reserve BOOLEAN DEFAULT FALSE,
    numero_reservation VARCHAR(100),
    
    -- Évaluation
    note INT, -- Note sur 5
    commentaire TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_etape_id (etape_id),
    INDEX idx_voyage_id (voyage_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des ravitaillements essence
CREATE TABLE IF NOT EXISTS voyage_ravitaillements_essence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etape_id INT NOT NULL,
    voyage_id INT NOT NULL,
    
    -- Type de carburant
    type_carburant ENUM('avgas_100ll', 'mogas_95', 'mogas_98', 'ul91', 'jet_a1', 'autre') NOT NULL,
    quantite DECIMAL(8, 2), -- En litres
    prix_litre DECIMAL(6, 3),
    prix_total DECIMAL(10, 2),
    devise VARCHAR(3) DEFAULT 'EUR',
    
    -- Lieu de ravitaillement
    lieu VARCHAR(200),
    disponible_terrain BOOLEAN DEFAULT TRUE, -- Dispo sur le terrain ?
    fournisseur VARCHAR(100),
    
    -- Date et horaires
    date_ravitaillement DATETIME,
    horaires_ouverture VARCHAR(100),
    
    -- Notes
    self_service BOOLEAN DEFAULT FALSE,
    carte_acceptee BOOLEAN,
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_etape_id (etape_id),
    INDEX idx_voyage_id (voyage_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des ravitaillements vivres
CREATE TABLE IF NOT EXISTS voyage_ravitaillements_vivres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etape_id INT NOT NULL,
    voyage_id INT NOT NULL,
    
    -- Type d'établissement
    type ENUM('restaurant', 'supermarche', 'marche', 'boulangerie', 'bar', 'autre') NOT NULL,
    nom VARCHAR(200),
    adresse TEXT,
    telephone VARCHAR(20),
    
    -- Date et horaires
    date_visite DATETIME,
    horaires VARCHAR(100),
    
    -- Coût
    prix_total DECIMAL(10, 2),
    devise VARCHAR(3) DEFAULT 'EUR',
    
    -- Évaluation
    note INT, -- Note sur 5
    specialite VARCHAR(200), -- Plat spécial ou produit notable
    commentaire TEXT,
    
    -- Détails pratiques
    distance_terrain DECIMAL(5, 2), -- Distance en km du terrain
    sur_terrain BOOLEAN DEFAULT FALSE, -- Restaurant sur le terrain ?
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_etape_id (etape_id),
    INDEX idx_voyage_id (voyage_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des visites culturelles
CREATE TABLE IF NOT EXISTS voyage_visites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etape_id INT NOT NULL,
    voyage_id INT NOT NULL,
    
    -- Type de visite
    type ENUM('monument', 'musee', 'site_naturel', 'ville', 'evenement', 'activite', 'autre') NOT NULL,
    nom VARCHAR(200) NOT NULL,
    description TEXT,
    
    -- Localisation
    adresse TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    distance_terrain DECIMAL(5, 2), -- Distance en km du terrain
    
    -- Horaires et accès
    date_visite DATE,
    heure_debut TIME,
    heure_fin TIME,
    duree_visite INT, -- Durée estimée en minutes
    horaires_ouverture TEXT,
    
    -- Coût
    prix_adulte DECIMAL(8, 2),
    prix_enfant DECIMAL(8, 2),
    prix_total DECIMAL(10, 2),
    devise VARCHAR(3) DEFAULT 'EUR',
    gratuit BOOLEAN DEFAULT FALSE,
    
    -- Réservation
    reservation_requise BOOLEAN DEFAULT FALSE,
    numero_reservation VARCHAR(100),
    site_web VARCHAR(255),
    telephone VARCHAR(20),
    email VARCHAR(100),
    
    -- Évaluation
    note INT, -- Note sur 5
    commentaire TEXT,
    recommande BOOLEAN DEFAULT TRUE,
    
    -- Photos
    photo_url VARCHAR(255),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    INDEX idx_etape_id (etape_id),
    INDEX idx_voyage_id (voyage_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des photos de voyage
CREATE TABLE IF NOT EXISTS voyage_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voyage_id INT NOT NULL,
    etape_id INT, -- NULL si photo générale du voyage
    visite_id INT, -- NULL si non liée à une visite
    
    fichier VARCHAR(255) NOT NULL,
    legende TEXT,
    ordre INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
    FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
    FOREIGN KEY (visite_id) REFERENCES voyage_visites(id) ON DELETE CASCADE,
    INDEX idx_voyage_id (voyage_id),
    INDEX idx_etape_id (etape_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mise à jour de la table voyages existante pour ajouter des champs
ALTER TABLE voyages
    ADD COLUMN IF NOT EXISTS date_debut DATE,
    ADD COLUMN IF NOT EXISTS date_fin DATE,
    ADD COLUMN IF NOT EXISTS budget_total DECIMAL(10, 2),
    ADD COLUMN IF NOT EXISTS budget_depense DECIMAL(10, 2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS distance_totale DECIMAL(8, 2),
    ADD COLUMN IF NOT EXISTS temps_vol_total INT, -- En minutes
    ADD COLUMN IF NOT EXISTS nombre_etapes INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS public BOOLEAN DEFAULT FALSE;
