<?php
/**
 * Diagnostic pour voyage-planner.php
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Debug voyage-planner</title>";
echo "<style>body{font-family:sans-serif;padding:2rem;max-width:1000px;margin:0 auto;}";
echo "h1{color:#0ea5e9;}pre{background:#f1f5f9;padding:1rem;border-radius:8px;overflow-x:auto;}";
echo ".success{color:#059669;}.error{color:#ef4444;}.warning{color:#f59e0b;}</style></head><body>";

echo "<h1>🔍 Diagnostic voyage-planner.php</h1>";

try {
    require_once '../includes/session.php';
    echo "<p class='success'>✅ Session incluse</p>";
    
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NON CONNECTÉ') . "</p>";
    echo "<p>Email: " . ($_SESSION['email'] ?? 'N/A') . "</p>";
    
    require_once '../config/database.php';
    echo "<p class='success'>✅ Database config incluse</p>";
    
    require_once '../includes/functions.php';
    echo "<p class='success'>✅ Functions incluses</p>";
    
    $pdo = getDBConnection();
    echo "<p class='success'>✅ Connexion BDD OK</p>";
    
    $voyage_id = intval($_GET['id'] ?? 0);
    echo "<p>Voyage ID: $voyage_id</p>";
    
    // Vérifier la table voyages
    echo "<h2>Test table voyages...</h2>";
    $stmt = $pdo->prepare("SELECT * FROM voyages WHERE id = ?");
    $stmt->execute([$voyage_id]);
    $voyage = $stmt->fetch();
    
    if ($voyage) {
        echo "<p class='success'>✅ Voyage trouvé: " . htmlspecialchars($voyage['titre']) . "</p>";
        echo "<pre>";
        print_r($voyage);
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ Voyage ID $voyage_id non trouvé</p>";
    }
    
    // Vérifier la table voyage_etapes
    echo "<h2>Test table voyage_etapes...</h2>";
    try {
        $stmt = $pdo->prepare("SELECT * FROM voyage_etapes WHERE voyage_id = ? ORDER BY ordre ASC");
        $stmt->execute([$voyage_id]);
        $etapes = $stmt->fetchAll();
        echo "<p class='success'>✅ Table voyage_etapes existe</p>";
        echo "<p>Nombre d'étapes: " . count($etapes) . "</p>";
        if (!empty($etapes)) {
            echo "<pre>";
            print_r($etapes);
            echo "</pre>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur table voyage_etapes: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='warning'>⚠️ La table n'existe probablement pas. Exécutez la migration !</p>";
    }
    
    // Vérifier les autres tables
    echo "<h2>Test des tables nécessaires...</h2>";
    $tables = ['aerodromes_fr', 'ulm_bases_fr', 'destinations'];
    foreach ($tables as $table) {
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM $table")->fetch();
            echo "<p class='success'>✅ Table $table existe ({$result['count']} entrées)</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Table $table: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h2>Conclusion</h2>";
    if ($voyage && isset($etapes)) {
        echo "<p class='success'>✅ Toutes les vérifications sont OK, la page devrait fonctionner</p>";
        echo "<p><a href='voyage-planner.php?id=$voyage_id'>Tester voyage-planner.php</a></p>";
    } else {
        echo "<p class='error'>❌ Des problèmes ont été détectés</p>";
        echo "<p><a href='../migrate_voyages_online.php'>Exécuter la migration</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Erreur fatale</h2>";
    echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
