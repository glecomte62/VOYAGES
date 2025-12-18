<?php
require_once 'config/database.php';

$pdo = getDBConnection();

// Insérer une destination de test avec la photo
$sql = "INSERT INTO destinations (
    code_oaci, nom, aerodrome, ville, pays, latitude, longitude,
    type_piste, longueur_piste_m, frequence_radio,
    carburant, restaurant, hebergement,
    acces_ulm, acces_avion,
    description, points_interet, photo_principale,
    created_by, actif, created_at
) VALUES (
    'LFQJ', 'Test Destination', 'Aérodrome de Test', 'Testville', 'France', 48.8566, 2.3522,
    'dur', 800, '123.50',
    1, 1, 1,
    1, 1,
    'Ceci est une destination de test pour vérifier l\'affichage des photos.',
    'Hôtel Test - 5 min à pied\nRestaurant Le Test - 10 min à pied',
    'test.jpg',
    1, 1, NOW()
) ON DUPLICATE KEY UPDATE photo_principale = 'test.jpg'";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo "Destination de test créée avec succès ! ID: " . $pdo->lastInsertId() . "\n";
    
    // Afficher toutes les destinations
    $stmt = $pdo->query("SELECT id, nom, photo_principale FROM destinations");
    echo "\nDestinations dans la base:\n";
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']} - {$row['nom']} - Photo: {$row['photo_principale']}\n";
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
