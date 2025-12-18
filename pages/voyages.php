<?php
/**
 * VOYAGES - Gestion des voyages
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupération de tous les voyages
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM voyages ORDER BY date_debut DESC");
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
                        <p class="destination"><?php echo h($voyage['destination']); ?></p>
                        <p class="dates">
                            Du <?php echo formatDate($voyage['date_debut']); ?> 
                            au <?php echo formatDate($voyage['date_fin']); ?>
                        </p>
                        <p class="statut">Statut: <?php echo h($voyage['statut']); ?></p>
                        <?php if ($voyage['budget']): ?>
                            <p class="budget">Budget: <?php echo number_format($voyage['budget'], 2); ?> €</p>
                        <?php endif; ?>
                        <div class="actions">
                            <a href="voyage-detail.php?id=<?php echo $voyage['id']; ?>">Voir</a>
                            <a href="voyage-edit.php?id=<?php echo $voyage['id']; ?>">Modifier</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
