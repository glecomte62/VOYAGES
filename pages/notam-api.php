<?php
/**
 * Récupération des NOTAM pour un aérodrome
 * Source: API publique NOTAM (simulation pour démo)
 */

header('Content-Type: application/json');

require_once '../includes/session.php';
requireLogin();

$codeOaci = $_GET['oaci'] ?? null;

if (!$codeOaci) {
    echo json_encode(['error' => 'Code OACI manquant']);
    exit;
}

// IMPORTANT: Pour une vraie implémentation, utiliser une API NOTAM officielle
// Par exemple: https://www.notams.faa.gov/dinsQueryWeb/ (USA)
// ou https://www.sia.aviation-civile.gouv.fr/ (France)

// Simulation de NOTAMs pour la démo
$notams = [
    [
        'id' => 'A0123/24',
        'type' => 'INFO',
        'debut' => '2024-12-25 08:00',
        'fin' => '2024-12-31 18:00',
        'titre' => 'Travaux sur taxiway A',
        'description' => 'Taxiway A fermé pour maintenance. Utiliser taxiway B.',
        'priorite' => 'normale'
    ],
    [
        'id' => 'A0124/24',
        'type' => 'RESTRICTION',
        'debut' => '2024-12-26 14:00',
        'fin' => '2024-12-26 16:00',
        'titre' => 'Activité aérienne militaire',
        'description' => 'Vol en formation prévu. Attention accrue requise.',
        'priorite' => 'haute'
    ]
];

// En production, faire un appel API ici:
/*
$url = "https://api-notam.example.com/notams?icao={$codeOaci}";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer YOUR_API_KEY'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['error' => 'Impossible de récupérer les NOTAM']);
    exit;
}

$notams = json_decode($response, true);
*/

echo json_encode([
    'oaci' => $codeOaci,
    'notams' => $notams,
    'count' => count($notams),
    'derniere_maj' => date('Y-m-d H:i:s'),
    'note' => 'Données de démonstration. En production, utiliser une API NOTAM officielle.'
]);
