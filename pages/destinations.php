<?php
/**
 * VOYAGES - Catalogue des destinations
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupération des destinations
$pdo = getDBConnection();
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';

$sql = "SELECT * FROM destinations WHERE actif = 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nom LIKE ? OR aerodrome LIKE ? OR ville LIKE ? OR code_oaci LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

if ($type === 'ulm') {
    $sql .= " AND acces_ulm = 1";
} elseif ($type === 'avion') {
    $sql .= " AND acces_avion = 1";
}

$sql .= " ORDER BY nom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <h2>📍 Catalogue des destinations</h2>
        
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Rechercher une destination..." 
                       value="<?php echo h($search); ?>">
                <select name="type">
                    <option value="">Tous</option>
                    <option value="ulm" <?php echo $type === 'ulm' ? 'selected' : ''; ?>>ULM uniquement</option>
                    <option value="avion" <?php echo $type === 'avion' ? 'selected' : ''; ?>>Avion uniquement</option>
                </select>
                <button type="submit" class="btn">Rechercher</button>
            </form>
            
            <?php if (isLoggedIn()): ?>
                <a href="destination-add.php" class="btn btn-primary">+ Ajouter une destination</a>
            <?php endif; ?>
        </div>

        <div class="destinations-grid">
            <?php if (empty($destinations)): ?>
                <p>Aucune destination trouvée. Soyez le premier à en ajouter une !</p>
            <?php else: ?>
                <?php foreach ($destinations as $dest): ?>
                    <div class="destination-card">
                        <?php if ($dest['photo_principale']): ?>
                            <img src="../uploads/destinations/<?php echo h($dest['photo_principale']); ?>" 
                                 alt="<?php echo h($dest['nom']); ?>">
                        <?php endif; ?>
                        
                        <div class="destination-content">
                            <h3><?php echo h($dest['nom']); ?></h3>
                            <p class="aerodrome">🛫 <?php echo h($dest['aerodrome']); ?>
                                <?php if ($dest['code_oaci']): ?>
                                    (<?php echo h($dest['code_oaci']); ?>)
                                <?php endif; ?>
                            </p>
                            <p class="location">📍 <?php echo h($dest['ville']); ?>, <?php echo h($dest['pays']); ?></p>
                            
                            <div class="destination-tags">
                                <?php if ($dest['acces_ulm']): ?>
                                    <span class="tag">ULM</span>
                                <?php endif; ?>
                                <?php if ($dest['acces_avion']): ?>
                                    <span class="tag">Avion</span>
                                <?php endif; ?>
                                <?php if ($dest['carburant']): ?>
                                    <span class="tag">⛽</span>
                                <?php endif; ?>
                                <?php if ($dest['restaurant']): ?>
                                    <span class="tag">🍽️</span>
                                <?php endif; ?>
                                <?php if ($dest['hebergement']): ?>
                                    <span class="tag">🏨</span>
                                <?php endif; ?>
                            </div>
                            
                            <a href="destination-detail.php?id=<?php echo $dest['id']; ?>" class="btn btn-small">Voir détails</a>
                        </div>
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
