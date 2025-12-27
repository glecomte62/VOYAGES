/**
 * VOYAGES - Fonctions utilitaires pour calculs géographiques
 * Distance et temps de vol entre terrains
 */

/**
 * Calcule la distance entre deux points géographiques
 * Utilise la formule de Haversine pour obtenir la distance orthodromique
 * 
 * @param float $lat1 Latitude du point 1 (degrés décimaux)
 * @param float $lon1 Longitude du point 1 (degrés décimaux)
 * @param float $lat2 Latitude du point 2 (degrés décimaux)
 * @param float $lon2 Longitude du point 2 (degrés décimaux)
 * @return float Distance en kilomètres
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    // Rayon de la Terre en km
    $earthRadius = 6371;
    
    // Conversion des degrés en radians
    $lat1Rad = deg2rad($lat1);
    $lon1Rad = deg2rad($lon1);
    $lat2Rad = deg2rad($lat2);
    $lon2Rad = deg2rad($lon2);
    
    // Différences
    $deltaLat = $lat2Rad - $lat1Rad;
    $deltaLon = $lon2Rad - $lon1Rad;
    
    // Formule de Haversine
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    $distance = $earthRadius * $c;
    
    return round($distance, 2);
}

/**
 * Calcule le temps de vol entre deux points
 * 
 * @param float $distance Distance en kilomètres
 * @param int $vitesseCroisiere Vitesse de croisière en km/h
 * @return float Temps de vol en heures (décimal)
 */
function calculateFlightTime($distance, $vitesseCroisiere = 175) {
    if ($vitesseCroisiere <= 0) {
        return 0;
    }
    return round($distance / $vitesseCroisiere, 2);
}

/**
 * Formate un temps de vol en heures décimales vers heures:minutes
 * 
 * @param float $heures Temps en heures (décimal)
 * @return string Format "Xh Ymin"
 */
function formatFlightTime($heures) {
    $h = floor($heures);
    $m = round(($heures - $h) * 60);
    
    if ($h > 0 && $m > 0) {
        return "{$h}h {$m}min";
    } elseif ($h > 0) {
        return "{$h}h";
    } else {
        return "{$m}min";
    }
}

/**
 * Met à jour les distances et temps de vol pour toutes les étapes d'un voyage
 * 
 * @param PDO $pdo Connexion base de données
 * @param int $voyageId ID du voyage
 * @return bool Succès de l'opération
 */
function updateVoyageDistancesAndTimes($pdo, $voyageId) {
    try {
        // Récupérer le voyage avec sa vitesse de croisière
        $stmt = $pdo->prepare("SELECT vitesse_croisiere FROM voyages WHERE id = ?");
        $stmt->execute([$voyageId]);
        $voyage = $stmt->fetch();
        
        if (!$voyage) {
            return false;
        }
        
        $vitesseCroisiere = $voyage['vitesse_croisiere'] ?? 175;
        
        // Récupérer toutes les étapes dans l'ordre
        $stmt = $pdo->prepare("
            SELECT id, latitude, longitude, ordre 
            FROM voyage_etapes 
            WHERE voyage_id = ? 
            ORDER BY ordre ASC
        ");
        $stmt->execute([$voyageId]);
        $etapes = $stmt->fetchAll();
        
        $distanceTotale = 0;
        $tempsTotal = 0;
        $etapePrecedente = null;
        
        foreach ($etapes as $etape) {
            $distance = 0;
            $tempsVol = 0;
            
            // Calculer distance depuis l'étape précédente
            if ($etapePrecedente !== null) {
                $distance = calculateDistance(
                    $etapePrecedente['latitude'],
                    $etapePrecedente['longitude'],
                    $etape['latitude'],
                    $etape['longitude']
                );
                $tempsVol = calculateFlightTime($distance, $vitesseCroisiere);
                $distanceTotale += $distance;
                $tempsTotal += $tempsVol;
            }
            
            // Mettre à jour l'étape
            $updateStmt = $pdo->prepare("
                UPDATE voyage_etapes 
                SET distance_precedente = ?, temps_vol_precedent = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$distance, $tempsVol, $etape['id']]);
            
            $etapePrecedente = $etape;
        }
        
        // Mettre à jour le voyage avec totaux
        $updateVoyageStmt = $pdo->prepare("
            UPDATE voyages 
            SET distance_totale = ?, temps_vol_total = ?
            WHERE id = ?
        ");
        $updateVoyageStmt->execute([$distanceTotale, $tempsTotal, $voyageId]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Erreur update distances/temps: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcule le cap (route magnétique approximative) entre deux points
 * 
 * @param float $lat1 Latitude du point 1
 * @param float $lon1 Longitude du point 1
 * @param float $lat2 Latitude du point 2
 * @param float $lon2 Longitude du point 2
 * @return int Cap en degrés (0-360)
 */
function calculateBearing($lat1, $lon1, $lat2, $lon2) {
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLon = deg2rad($lon2 - $lon1);
    
    $y = sin($deltaLon) * cos($lat2Rad);
    $x = cos($lat1Rad) * sin($lat2Rad) -
         sin($lat1Rad) * cos($lat2Rad) * cos($deltaLon);
    
    $bearing = atan2($y, $x);
    $bearing = rad2deg($bearing);
    $bearing = ($bearing + 360) % 360;
    
    return round($bearing);
}
