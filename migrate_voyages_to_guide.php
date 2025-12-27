<?php
/**
 * Migration pour transformer le module Voyages en guide de voyages
 * Exécuter depuis le navigateur: https://voyages.clubulmevasion.fr/migrate_voyages_to_guide.php
 */

require_once 'config/database.php';

echo "<h1>Migration du module Voyages</h1>";
echo "<p>Transformation en guide de voyages avec carte et calculs de distances...</p>";

try {
    $pdo = getDBConnection();
    
    echo "<h2>1. Modification des colonnes de dates (optionnelles)</h2>";
    $pdo->exec("ALTER TABLE voyages MODIFY COLUMN date_debut DATE NULL");
    $pdo->exec("ALTER TABLE voyages MODIFY COLUMN date_fin DATE NULL");
    echo "✅ Dates rendues optionnelles<br>";
    
    echo "<h2>2. Ajout de la vitesse de croisière</h2>";
    $pdo->exec("ALTER TABLE voyages ADD COLUMN IF NOT EXISTS vitesse_croisiere INT DEFAULT 175 COMMENT 'Vitesse de croisière en km/h (150-200 pour ULM/petit avion)'");
    echo "✅ Colonne vitesse_croisiere ajoutée<br>";
    
    echo "<h2>3. Ajout de la distance totale</h2>";
    $pdo->exec("ALTER TABLE voyages ADD COLUMN IF NOT EXISTS distance_totale DECIMAL(10,2) DEFAULT 0 COMMENT 'Distance totale de l''itinéraire en km'");
    echo "✅ Colonne distance_totale ajoutée<br>";
    
    echo "<h2>4. Ajout du temps de vol total</h2>";
    $pdo->exec("ALTER TABLE voyages ADD COLUMN IF NOT EXISTS temps_vol_total DECIMAL(10,2) DEFAULT 0 COMMENT 'Temps de vol total en heures'");
    echo "✅ Colonne temps_vol_total ajoutée<br>";
    
    echo "<h2>5. Modification des étapes - distance et temps</h2>";
    $pdo->exec("ALTER TABLE voyage_etapes ADD COLUMN IF NOT EXISTS distance_precedente DECIMAL(10,2) DEFAULT 0 COMMENT 'Distance depuis l''étape précédente en km'");
    $pdo->exec("ALTER TABLE voyage_etapes ADD COLUMN IF NOT EXISTS temps_vol_precedent DECIMAL(10,2) DEFAULT 0 COMMENT 'Temps de vol depuis l''étape précédente en heures'");
    echo "✅ Colonnes distance_precedente et temps_vol_precedent ajoutées<br>";
    
    echo "<h2>6. Modification des dates d'étapes (optionnelles)</h2>";
    $pdo->exec("ALTER TABLE voyage_etapes MODIFY COLUMN date_arrivee DATETIME NULL");
    $pdo->exec("ALTER TABLE voyage_etapes MODIFY COLUMN date_depart DATETIME NULL");
    echo "✅ Dates d'étapes rendues optionnelles<br>";
    
    echo "<h2 style='color: green;'>✅ Migration terminée avec succès !</h2>";
    echo "<p><a href='pages/voyages.php'>Retour aux voyages</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Erreur lors de la migration</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
