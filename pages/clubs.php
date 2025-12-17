<?php
/**
 * VOYAGES - Annuaire des clubs
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupération des clubs
$pdo = getDBConnection();
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM clubs WHERE actif = 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nom LIKE ? OR ville LIKE ? OR aerodrome LIKE ? OR code_oaci LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

$sql .= " ORDER BY nom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clubs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clubs - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <h2>🏛️ Annuaire des clubs</h2>
        
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Rechercher un club..." 
                       value="<?php echo h($search); ?>">
                <button type="submit" class="btn">Rechercher</button>
            </form>
        </div>

        <div class="clubs-list">
            <?php if (empty($clubs)): ?>
                <p>Aucun club trouvé.</p>
            <?php else: ?>
                <?php foreach ($clubs as $club): ?>
                    <div class="club-card">
                        <h3><?php echo h($club['nom']); ?></h3>
                        <?php if ($club['code']): ?>
                            <p class="club-code">Code: <?php echo h($club['code']); ?></p>
                        <?php endif; ?>
                        
                        <p class="location">📍 <?php echo h($club['ville']); ?>
                            <?php if ($club['aerodrome']): ?>
                                - <?php echo h($club['aerodrome']); ?>
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($club['telephone']): ?>
                            <p>📞 <?php echo h($club['telephone']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($club['email']): ?>
                            <p>✉️ <?php echo h($club['email']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (isLoggedIn()): ?>
                            <a href="club-join.php?id=<?php echo $club['id']; ?>" class="btn btn-small">Rejoindre ce club</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> VOYAGES ULM - Tous droits réservés</p>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
