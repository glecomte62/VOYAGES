-- Table principale des guides de voyages (circuits publics)
CREATE TABLE IF NOT EXISTS guides_de_voyages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auteur_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    region VARCHAR(255),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_published TINYINT(1) DEFAULT 1,
    FOREIGN KEY (auteur_id) REFERENCES users(id)
);

-- Table des étapes d’un guide (ordre, type, etc.)
CREATE TABLE IF NOT EXISTS guides_etapes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guide_id INT NOT NULL,
    ordre INT NOT NULL,
    type_etape ENUM('aerodrome','hebergement','restauration','avitaillement','ravitaillement','visite','autre') NOT NULL,
    titre VARCHAR(255),
    description TEXT,
    FOREIGN KEY (guide_id) REFERENCES guides_de_voyages(id) ON DELETE CASCADE
);

-- Table des détails d’étape (clé-valeur, extensible)
CREATE TABLE IF NOT EXISTS guides_etapes_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etape_id INT NOT NULL,
    cle VARCHAR(100) NOT NULL,
    valeur TEXT,
    FOREIGN KEY (etape_id) REFERENCES guides_etapes(id) ON DELETE CASCADE
);

-- Table des commentaires sur un guide
CREATE TABLE IF NOT EXISTS guides_commentaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guide_id INT NOT NULL,
    auteur_id INT NOT NULL,
    commentaire TEXT NOT NULL,
    date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guide_id) REFERENCES guides_de_voyages(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES users(id)
);

-- Table des photos associées à un guide ou une étape
CREATE TABLE IF NOT EXISTS guides_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guide_id INT NOT NULL,
    etape_id INT,
    chemin VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guide_id) REFERENCES guides_de_voyages(id) ON DELETE CASCADE,
    FOREIGN KEY (etape_id) REFERENCES guides_etapes(id) ON DELETE SET NULL
);
