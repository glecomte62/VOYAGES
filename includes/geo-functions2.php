<?php
/**
 * Copie temporaire pour forcer le rechargement OPCache
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
    $earthRadius = 6371;
    $lat1Rad = deg2rad($lat1);
    $lon1Rad = deg2rad($lon1);
    $lat2Rad = deg2rad($lat2);
    $lon2Rad = deg2rad($lon2);
    $deltaLat = $lat2Rad - $lat1Rad;
    $deltaLon = $lon2Rad - $lon1Rad;
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;
    return round($distance, 2);
}

function calculateFlightTime($distance, $vitesseCroisiere = 175) {
    if ($vitesseCroisiere <= 0) {
        return 0;
    }
    return round($distance / $vitesseCroisiere, 2);
}

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
