<?php
/**












































































}    exit(1);    echo "\n❌ ERREUR FATALE: " . $e->getMessage() . "\n";} catch (Exception $e) {        }        echo "Certaines tables existent peut-être déjà.\n";        echo "\n⚠️ La migration s'est terminée avec des erreurs.\n";    } else {        echo "\nLa table 'voyages' a été mise à jour avec de nouveaux champs.\n";        echo "- voyage_photos\n";        echo "- voyage_visites\n";        echo "- voyage_ravitaillements_vivres\n";        echo "- voyage_ravitaillements_essence\n";        echo "- voyage_hebergements\n";        echo "- voyage_etapes\n";        echo "\nLes tables suivantes ont été créées :\n";        echo "\n🎉 Migration terminée avec succès !\n";    if ($errors === 0) {        echo "❌ Erreurs: $errors\n";    echo "✅ Succès: $success\n";    echo "\n=== Résumé ===\n";        }        }            echo "❌ ERREUR: " . $e->getMessage() . "\n\n";            $errors++;        } catch (PDOException $e) {                        echo "✅ OK\n\n";            $success++;            $pdo->exec($query);            echo "Exécution de la requête " . ($index + 1) . "...\n";                        }                continue;            if (empty($query)) {                        $query = trim($query);            $query = preg_replace('/--.*$/m', '', $query);            // Nettoyer les commentaires        try {    foreach ($queries as $index => $query) {        $errors = 0;    $success = 0;        echo "Nombre de requêtes à exécuter: " . count($queries) . "\n\n";        );        }            return !empty($query) && strpos($query, '--') !== 0;        function($query) {        array_map('trim', explode(';', $sql)),    $queries = array_filter(    // Séparer les requêtes        }        throw new Exception("Impossible de lire le fichier SQL");    if ($sql === false) {        $sql = file_get_contents('../sql/create_voyage_module.sql');    // Lire le fichier SQL        $pdo = getDBConnection();try {echo "=== Migration du module Voyages ===\n\n";require_once '../config/database.php'; */ * À exécuter une seule fois pour créer les tables nécessaires * Script de migration pour le module Voyages * VOYAGES - Gestion des voyages
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupération de tous les voyages de l'utilisateur
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM voyages WHERE user_id = ? ORDER BY date_debut DESC");
$stmt->execute([$_SESSION['user_id']]);
$voyages = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Voyages - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
            <h1 style="font-size: 2.5rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem;">✈️ Mes Voyages</h1>
        </div>
        
        <div class="page-actions">
            <a href="voyage-new.php" class="btn">+ Nouveau Voyage</a>
        </div>

        <div class="voyages-list">
            <?php if (empty($voyages)): ?>
                <p>Aucun voyage enregistré. Créez votre premier voyage !</p>
            <?php else: ?>
                <?php foreach ($voyages as $voyage): ?>
                    <div class="voyage-card">
                        <h3><?php echo h($voyage['titre']); ?></h3>
                        <?php if ($voyage['description']): ?>
                            <p class="description"><?php echo h(substr($voyage['description'], 0, 150)) . (strlen($voyage['description']) > 150 ? '...' : ''); ?></p>
                        <?php endif; ?>
                        <p class="dates">
                            📅 <?php echo formatDate($voyage['date_debut']); ?> → <?php echo formatDate($voyage['date_fin']); ?>
                        </p>
                        <?php if ($voyage['nombre_etapes']): ?>
                            <p class="etapes">📍 <?php echo $voyage['nombre_etapes']; ?> étape(s)</p>
                        <?php endif; ?>
                        <p class="statut">
                            <?php 
                                $statut_icons = [
                                    'planifie' => '📋',
                                    'en_cours' => '✈️',
                                    'termine' => '✅',
                                    'annule' => '❌'
                                ];
                                echo $statut_icons[$voyage['statut']] ?? '';
                            ?> 
                            <?php echo ucfirst(str_replace('_', ' ', $voyage['statut'])); ?>
                        </p>
                        <?php if ($voyage['budget_total']): ?>
                            <p class="budget">
                                💰 Budget: <?php echo number_format($voyage['budget_total'], 2); ?> €
                                <?php if ($voyage['budget_depense']): ?>
                                    (Dépensé: <?php echo number_format($voyage['budget_depense'], 2); ?> €)
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <div class="actions">
                            <a href="voyage-detail.php?id=<?php echo $voyage['id']; ?>" class="btn btn-view">Voir</a>
                            <a href="voyage-planner.php?id=<?php echo $voyage['id']; ?>" class="btn btn-edit">Planifier</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
