-- Modification du module Voyages pour transformer en guide de voyages
-- Dates optionnelles et ajout vitesse de croisière

-- 1. Modifier les colonnes de dates pour les rendre optionnelles
ALTER TABLE voyages 
    MODIFY COLUMN date_debut DATE NULL,
    MODIFY COLUMN date_fin DATE NULL;

-- 2. Ajouter colonne vitesse de croisière (km/h)
ALTER TABLE voyages 
    ADD COLUMN vitesse_croisiere INT DEFAULT 175 
    COMMENT 'Vitesse de croisière en km/h (150-200 pour ULM/petit avion)';

-- 3. Ajouter colonne distance totale (calculée automatiquement)
ALTER TABLE voyages 
    ADD COLUMN distance_totale DECIMAL(10,2) DEFAULT 0 
    COMMENT 'Distance totale de l\'itinéraire en km';

-- 4. Ajouter colonne temps de vol total (calculé automatiquement)
ALTER TABLE voyages 
    ADD COLUMN temps_vol_total DECIMAL(10,2) DEFAULT 0 
    COMMENT 'Temps de vol total en heures';

-- 5. Modifier les étapes pour ajouter distance et temps
ALTER TABLE voyage_etapes
    ADD COLUMN distance_precedente DECIMAL(10,2) DEFAULT 0 
    COMMENT 'Distance depuis l\'étape précédente en km',
    ADD COLUMN temps_vol_precedent DECIMAL(10,2) DEFAULT 0 
    COMMENT 'Temps de vol depuis l\'étape précédente en heures';

-- 6. Modifier les dates d'étapes pour les rendre optionnelles
ALTER TABLE voyage_etapes
    MODIFY COLUMN date_arrivee DATETIME NULL,
    MODIFY COLUMN date_depart DATETIME NULL;
