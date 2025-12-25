<?php
/**
 * API pour récupérer la liste des terrains (aérodromes ou bases ULM)
 */

header('Content-Type: application/json');

require_once '../config/database.php';

$type = $_GET['type'] ?? '';

if (!in_array($type, ['aerodrome', 'base_ulm'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type invalide']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $table = $type === 'aerodrome' ? 'aerodromes_fr' : 'ulm_bases_fr';
    
    $stmt = $pdo->query("SELECT id, nom FROM $table ORDER BY nom ASC");
    $terrains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($terrains);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
