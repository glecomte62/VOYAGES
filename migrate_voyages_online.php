<?php
/**
 * Migration du module Voyages - Version en ligne
 * À exécuter UNE SEULE FOIS depuis le navigateur
 */

require_once 'config/database.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Migration Voyages</title>";
echo "<style>body{font-family:sans-serif;padding:2rem;max-width:800px;margin:0 auto;}";
echo "h1{color:#0ea5e9;}pre{background:#f1f5f9;padding:1rem;border-radius:8px;overflow-x:auto;}";
echo ".success{color:#059669;}.error{color:#ef4444;}.warning{color:#f59e0b;}</style></head><body>";

echo "<h1>🚀 Migration du module Voyages</h1>";

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Création des tables...</h2>";
    
    // Table voyage_etapes
    echo "<p>Création de <strong>voyage_etapes</strong>...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS voyage_etapes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            voyage_id INT NOT NULL,
            ordre INT NOT NULL,
            terrain_type ENUM('aerodrome', 'ulm_base', 'destination') NOT NULL,
            terrain_id INT NOT NULL,
            terrain_nom VARCHAR(200),
            terrain_code_oaci VARCHAR(10),
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            date_arrivee DATETIME,
            date_depart DATETIME,
            duree_vol_precedent INT,
            distance_precedent DECIMAL(8, 2),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
            INDEX idx_voyage_id (voyage_id),
            INDEX idx_ordre (voyage_id, ordre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='success'>✅ OK</p>";
    
    // Table voyage_hebergements
    echo "<p>Création de <strong>voyage_hebergements</strong>...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS voyage_hebergements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            etape_id INT NOT NULL,
            voyage_id INT NOT NULL,
            type ENUM('hotel', 'camping', 'gite', 'chambre_hote', 'bivouac', 'autre') NOT NULL,
            nom VARCHAR(200),
            adresse TEXT,
            telephone VARCHAR(20),
            email VARCHAR(100),
            site_web VARCHAR(255),
            date_checkin DATE NOT NULL,
            date_checkout DATE NOT NULL,
            nombre_nuits INT NOT NULL,
            prix_total DECIMAL(10, 2),
            devise VARCHAR(3) DEFAULT 'EUR',
            reserve BOOLEAN DEFAULT FALSE,
            numero_reservation VARCHAR(100),
            note INT,
            commentaire TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
            FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
            INDEX idx_etape_id (etape_id),
            INDEX idx_voyage_id (voyage_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='success'>✅ OK</p>";
    
    // Table voyage_ravitaillements_essence
    echo "<p>Création de <strong>voyage_ravitaillements_essence</strong>...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS voyage_ravitaillements_essence (
            id INT AUTO_INCREMENT PRIMARY KEY,
            etape_id INT NOT NULL,
            voyage_id INT NOT NULL,
            type_carburant ENUM('avgas_100ll', 'mogas_95', 'mogas_98', 'ul91', 'jet_a1', 'autre') NOT NULL,
            quantite DECIMAL(8, 2),
            prix_litre DECIMAL(6, 3),
            prix_total DECIMAL(10, 2),
            devise VARCHAR(3) DEFAULT 'EUR',
            lieu VARCHAR(200),
            disponible_terrain BOOLEAN DEFAULT TRUE,
            fournisseur VARCHAR(100),
            date_ravitaillement DATETIME,
            horaires_ouverture VARCHAR(100),
            self_service BOOLEAN DEFAULT FALSE,
            carte_acceptee BOOLEAN,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
            FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
            INDEX idx_etape_id (etape_id),
            INDEX idx_voyage_id (voyage_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='success'>✅ OK</p>";
    
    // Table voyage_ravitaillements_vivres
    echo "<p>Création de <strong>voyage_ravitaillements_vivres</strong>...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS voyage_ravitaillements_vivres (
            id INT AUTO_INCREMENT PRIMARY KEY,
            etape_id INT NOT NULL,
            voyage_id INT NOT NULL,
            type ENUM('restaurant', 'supermarche', 'marche', 'boulangerie', 'bar', 'autre') NOT NULL,
            nom VARCHAR(200),
            adresse TEXT,
            telephone VARCHAR(20),
            date_visite DATETIME,
            horaires VARCHAR(100),
            prix_total DECIMAL(10, 2),
            devise VARCHAR(3) DEFAULT 'EUR',
            note INT,
            specialite VARCHAR(200),
            commentaire TEXT,
            distance_terrain DECIMAL(5, 2),
            sur_terrain BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
            FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
            INDEX idx_etape_id (etape_id),
            INDEX idx_voyage_id (voyage_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='success'>✅ OK</p>";
    
    // Table voyage_visites
    echo "<p>Création de <strong>voyage_visites</strong>...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS voyage_visites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            etape_id INT NOT NULL,
            voyage_id INT NOT NULL,
            type ENUM('monument', 'musee', 'site_naturel', 'ville', 'evenement', 'activite', 'autre') NOT NULL,
            nom VARCHAR(200) NOT NULL,
            description TEXT,
            adresse TEXT,
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            distance_terrain DECIMAL(5, 2),
            date_visite DATE,
            heure_debut TIME,
            heure_fin TIME,
            duree_visite INT,
            horaires_ouverture TEXT,
            prix_adulte DECIMAL(8, 2),
            prix_enfant DECIMAL(8, 2),
            prix_total DECIMAL(10, 2),
            devise VARCHAR(3) DEFAULT 'EUR',
            gratuit BOOLEAN DEFAULT FALSE,
            reservation_requise BOOLEAN DEFAULT FALSE,
            numero_reservation VARCHAR(100),
            site_web VARCHAR(255),
            telephone VARCHAR(20),
            email VARCHAR(100),
            note INT,
            commentaire TEXT,
            recommande BOOLEAN DEFAULT TRUE,
            photo_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
            FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
            INDEX idx_etape_id (etape_id),
            INDEX idx_voyage_id (voyage_id),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='success'>✅ OK</p>";
    
    // Table voyage_photos
    echo "<p>Création de <strong>voyage_photos</strong>...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS voyage_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            voyage_id INT NOT NULL,
            etape_id INT,
            visite_id INT,
            fichier VARCHAR(255) NOT NULL,
            legende TEXT,
            ordre INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (voyage_id) REFERENCES voyages(id) ON DELETE CASCADE,
            FOREIGN KEY (etape_id) REFERENCES voyage_etapes(id) ON DELETE CASCADE,
            FOREIGN KEY (visite_id) REFERENCES voyage_visites(id) ON DELETE CASCADE,
            INDEX idx_voyage_id (voyage_id),
            INDEX idx_etape_id (etape_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='success'>✅ OK</p>";
    
    // Modification de la table voyages
    echo "<h2>Mise à jour de la table voyages...</h2>";
    
    $columns_to_add = [
        'date_debut' => 'DATE',
        'date_fin' => 'DATE',
        'budget_total' => 'DECIMAL(10, 2)',
        'budget_depense' => 'DECIMAL(10, 2) DEFAULT 0',
        'distance_totale' => 'DECIMAL(8, 2)',
        'temps_vol_total' => 'INT',
        'nombre_etapes' => 'INT DEFAULT 0',
        'public' => 'BOOLEAN DEFAULT FALSE'
    ];
    
    foreach ($columns_to_add as $column => $type) {
        try {
            $pdo->exec("ALTER TABLE voyages ADD COLUMN $column $type");
            echo "<p class='success'>✅ Colonne '$column' ajoutée</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p class='warning'>⚠️ Colonne '$column' existe déjà</p>";
            } else {
                throw $e;
            }
        }
    }
    
    echo "<h2 class='success'>✅ Migration terminée avec succès !</h2>";
    echo "<p>Le module Voyages est maintenant opérationnel.</p>";
    echo "<p><a href='pages/voyages.php' style='display:inline-block;padding:0.75rem 1.5rem;background:#0ea5e9;color:white;text-decoration:none;border-radius:8px;font-weight:600;'>Accéder à Mes Voyages</a></p>";
    echo "<p style='color:#f59e0b;margin-top:2rem;'><strong>⚠️ Important :</strong> Supprimez ce fichier (migrate_voyages_online.php) après la migration pour des raisons de sécurité.</p>";
    
} catch (PDOException $e) {
    echo "<h2 class='error'>❌ Erreur lors de la migration</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Vérifiez votre connexion à la base de données et réessayez.</p>";
}

echo "</body></html>";
