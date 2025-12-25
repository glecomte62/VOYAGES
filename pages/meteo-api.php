<?php
/**
 * API Météo pour les destinations
 * Utilise l'API Open-Meteo (gratuite, sans clé API)
 */

header('Content-Type: application/json');

require_once '../includes/session.php';
requireLogin();

$latitude = $_GET['lat'] ?? null;
$longitude = $_GET['lon'] ?? null;

if (!$latitude || !$longitude) {
    echo json_encode(['error' => 'Coordonnées manquantes']);
    exit;
}

// API Open-Meteo (gratuite, pas de clé requise)
$url = "https://api.open-meteo.com/v1/forecast?latitude={$latitude}&longitude={$longitude}&current=temperature_2m,relative_humidity_2m,precipitation,weather_code,cloud_cover,wind_speed_10m,wind_direction_10m,wind_gusts_10m&hourly=visibility&timezone=Europe/Paris";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['error' => 'Impossible de récupérer les données météo']);
    exit;
}

$data = json_decode($response, true);

if (!$data || !isset($data['current'])) {
    echo json_encode(['error' => 'Données météo invalides']);
    exit;
}

$current = $data['current'];

// Codes météo WMO
$weatherCodes = [
    0 => ['description' => 'Ciel dégagé', 'icon' => '☀️'],
    1 => ['description' => 'Principalement dégagé', 'icon' => '🌤️'],
    2 => ['description' => 'Partiellement nuageux', 'icon' => '⛅'],
    3 => ['description' => 'Couvert', 'icon' => '☁️'],
    45 => ['description' => 'Brouillard', 'icon' => '🌫️'],
    48 => ['description' => 'Brouillard givrant', 'icon' => '🌫️'],
    51 => ['description' => 'Bruine légère', 'icon' => '🌦️'],
    53 => ['description' => 'Bruine modérée', 'icon' => '🌦️'],
    55 => ['description' => 'Bruine dense', 'icon' => '🌧️'],
    61 => ['description' => 'Pluie légère', 'icon' => '🌧️'],
    63 => ['description' => 'Pluie modérée', 'icon' => '🌧️'],
    65 => ['description' => 'Pluie forte', 'icon' => '⛈️'],
    71 => ['description' => 'Neige légère', 'icon' => '🌨️'],
    73 => ['description' => 'Neige modérée', 'icon' => '🌨️'],
    75 => ['description' => 'Neige forte', 'icon' => '❄️'],
    95 => ['description' => 'Orage', 'icon' => '⛈️'],
];

$weatherCode = $current['weather_code'];
$weather = $weatherCodes[$weatherCode] ?? ['description' => 'Inconnu', 'icon' => '❓'];

// Visibilité (prendre la première heure)
$visibility = isset($data['hourly']['visibility'][0]) ? $data['hourly']['visibility'][0] / 1000 : null;

// Déterminer les conditions de vol
$conditionsVol = 'favorable';
if ($current['wind_speed_10m'] > 30 || $current['wind_gusts_10m'] > 40) {
    $conditionsVol = 'défavorable';
} elseif ($weatherCode >= 61 || $current['wind_speed_10m'] > 20) {
    $conditionsVol = 'précaire';
} elseif ($current['cloud_cover'] > 80 || $weatherCode >= 45) {
    $conditionsVol = 'vigilance';
}

$result = [
    'temperature' => round($current['temperature_2m'], 1),
    'humidite' => $current['relative_humidity_2m'],
    'precipitation' => $current['precipitation'],
    'meteo' => $weather['description'],
    'icon' => $weather['icon'],
    'couverture_nuageuse' => $current['cloud_cover'],
    'vent_vitesse' => round($current['wind_speed_10m'], 1),
    'vent_direction' => $current['wind_direction_10m'],
    'vent_rafales' => round($current['wind_gusts_10m'], 1),
    'visibilite' => $visibility ? round($visibility, 1) : null,
    'conditions_vol' => $conditionsVol,
    'timestamp' => $current['time']
];

echo json_encode($result);
