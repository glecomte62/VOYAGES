<?php
/**
 * Script de debug pour vérifier les clubs liés
 */

require_once 'config/database.php';

$pdo = getDBConnection();
$destination_id = 7;

echo "<h2>Debug clubs liés à la destination #$destination_id</h2>";

// Vérifier si la table destination_clubs existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'destination_clubs'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p style='color: green;'>✅ Table destination_clubs existe</p>";
        
        // Compter les liaisons
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM destination_clubs");
        $count = $stmt->fetch();
        echo "<p>Total liaisons: " . $count['total'] . "</p>";
        
        // Vérifier les liaisons pour cette destination
        $stmt = $pdo->prepare("SELECT * FROM destination_clubs WHERE destination_id = ?");
        $stmt->execute([$destination_id]);
        $liaisons = $stmt->fetchAll();
        
        echo "<p>Liaisons pour destination #$destination_id: " . count($liaisons) . "</p>";
        
        if (!empty($liaisons)) {
            echo "<pre>";
            print_r($liaisons);
            echo "</pre>";
        }
        
        // Récupérer les clubs liés
        $stmtClubs = $pdo->prepare("
            SELECT c.id, c.nom, c.ville, c.code_oaci, c.telephone, c.email
            FROM clubs c
            INNER JOIN destination_clubs dc ON c.id = dc.club_id
            WHERE dc.destination_id = ?
            ORDER BY c.nom ASC
        ");
        $stmtClubs->execute([$destination_id]);
        $clubs_lies = $stmtClubs->fetchAll();
        
        echo "<h3>Clubs liés:</h3>";
        if (!empty($clubs_lies)) {
            echo "<ul>";
            foreach ($clubs_lies as $club) {
                echo "<li>" . htmlspecialchars($club['nom']) . " - " . htmlspecialchars($club['ville'] ?? 'Ville non renseignée') . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Aucun club lié à cette destination</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Table destination_clubs n'existe pas</p>";
        echo "<p>Vous devez exécuter le script SQL: sql/add_destination_clubs.sql</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
