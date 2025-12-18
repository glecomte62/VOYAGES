<?php
require_once 'config/database.php';

$pdo = getDBConnection();

echo "<h2>Test de la requête clubs pour les membres</h2>";

// Récupérer un membre qui a plusieurs clubs
$sql = "SELECT u.id, u.nom, u.prenom, COUNT(mc.club_id) as nb_clubs
        FROM users u
        INNER JOIN membres_clubs mc ON u.id = mc.user_id
        WHERE u.actif = 1
        GROUP BY u.id
        HAVING COUNT(mc.club_id) > 1
        LIMIT 1";

$stmt = $pdo->query($sql);
$membre = $stmt->fetch(PDO::FETCH_ASSOC);

if ($membre) {
    echo "<p>Membre testé: {$membre['prenom']} {$membre['nom']} (ID: {$membre['id']}) - {$membre['nb_clubs']} clubs</p>";
    
    // Récupérer ses clubs
    $stmtClubs = $pdo->prepare("
        SELECT c.id, c.nom, c.ville 
        FROM clubs c
        INNER JOIN membres_clubs mc ON c.id = mc.club_id
        WHERE mc.user_id = ?
    ");
    $stmtClubs->execute([$membre['id']]);
    $clubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Clubs trouvés (" . count($clubs) . "):</h3>";
    echo "<pre>";
    print_r($clubs);
    echo "</pre>";
} else {
    echo "<p>Aucun membre avec plusieurs clubs trouvé.</p>";
}
