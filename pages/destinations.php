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
$favorisOnly = isset($_GET['favoris']) && $_GET['favoris'] == '1';

// Si on demande les favoris, vérifier que l'utilisateur est connecté
if ($favorisOnly && !isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($favorisOnly) {
    // Requête pour les favoris uniquement
    $sql = "SELECT d.* FROM destinations d
            INNER JOIN favoris f ON d.id = f.destination_id
            WHERE d.actif = 1 AND f.user_id = ?";
    $params = [$_SESSION['user_id']];
    
    if (!empty($search)) {
        $sql .= " AND (d.nom LIKE ? OR d.aerodrome LIKE ? OR d.ville LIKE ? OR d.code_oaci LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if ($type === 'ulm') {
        $sql .= " AND d.acces_ulm = 1";
    } elseif ($type === 'avion') {
        $sql .= " AND d.acces_avion = 1";
    }
    
    $sql .= " ORDER BY d.nom ASC";
} else {
    // Requête normale pour toutes les destinations
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
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

// Pour chaque destination, récupérer les clubs liés
foreach ($destinations as &$dest) {
    try {
        $stmtClubs = $pdo->prepare("
            SELECT c.nom, c.ville 
            FROM clubs c
            INNER JOIN destination_clubs dc ON c.id = dc.club_id
            WHERE dc.destination_id = ?
            ORDER BY c.nom ASC
            LIMIT 3
        ");
        $stmtClubs->execute([$dest['id']]);
        $dest['clubs'] = $stmtClubs->fetchAll();
    } catch (PDOException $e) {
        $dest['clubs'] = [];
    }
}
unset($dest); // Détruire la référence
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/page-header.css">
    <style>
        .page-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header-section {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }
        
        .page-header-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .page-header-section p {
            font-size: 1.125rem;
            color: #64748b;
            margin: 0;
        }
        
        .search-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 1rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .search-select {
            padding: 1rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-select:focus {
            outline: none;
            border-color: #06b6d4;
        }
        
        .btn-search {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(6, 182, 212, 0.3);
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
        }
        
        .btn-add {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            display: inline-block;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .stats-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-badge {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .stat-badge strong {
            color: #0f172a;
            font-size: 1.25rem;
            margin-right: 0.5rem;
        }
        
        .destinations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .destination-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .destination-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .destination-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        }
        
        .destination-image-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }
        
        .destination-content {
            padding: 1.5rem;
        }
        
        .destination-content h3 {
            margin: 0 0 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
        }
        
        .destination-info {
            margin-bottom: 0.75rem;
            color: #64748b;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .destination-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }
        
        .tag {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .tag-ulm {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .tag-avion {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .tag-service {
            background: #fef3c7;
            color: #92400e;
        }
        
        .tag-club {
            background: #e0f2fe;
            color: #0c4a6e;
            font-size: 0.7rem;
        }
        
        .clubs-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .clubs-title {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .btn-detail {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        
        .btn-detail:hover {
            background: linear-gradient(135deg, #0284c7 0%, #0891b2 100%);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: #0f172a;
            margin: 0 0 0.5rem;
        }
        
        .empty-state p {
            color: #64748b;
            margin: 0 0 2rem;
        }
        
        @media (max-width: 768px) {
            .page-content {
                padding: 1rem;
            }
            
            .page-header-section h1 {
                font-size: 2rem;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input,
            .search-select,
            .btn-search,
            .btn-add {
                width: 100%;
            }
            
            .destinations-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main style="padding-top: 6rem;">
        <div class="page-header">
            <?php if ($favorisOnly): ?>
                <h1 class="page-title">⭐ Mes destinations favorites</h1>
                <p style="text-align: center; color: white; margin-top: 0.5rem; font-size: 1.1rem;">Retrouvez toutes vos destinations enregistrées en favoris</p>
            <?php else: ?>
                <h1 class="page-title">🗺️ Catalogue des destinations</h1>
                <p style="text-align: center; color: white; margin-top: 0.5rem; font-size: 1.1rem;">Découvrez toutes les destinations accessibles en ULM et petit avion</p>
            <?php endif; ?>
        </div>
        
        <div class="page-content">
        
        <?php if ($favorisOnly): ?>
            <a href="destinations.php" style="display: inline-block; margin-bottom: 1.5rem; color: #fbbf24; text-decoration: none; font-weight: 600; background: white; padding: 0.75rem 1.5rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                ⬅️ Voir toutes les destinations
            </a>
        <?php endif; ?>
        
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <?php if ($favorisOnly): ?>
                    <input type="hidden" name="favoris" value="1">
                <?php endif; ?>
                <input type="text" 
                       name="search" 
                       class="search-input"
                       placeholder="🔍 Rechercher une destination, un code OACI, une ville..." 
                       value="<?php echo h($search); ?>">
                
                <select name="type" class="search-select">
                    <option value="">✈️ Tous les types</option>
                    <option value="ulm" <?php echo $type === 'ulm' ? 'selected' : ''; ?>>🪂 ULM uniquement</option>
                    <option value="avion" <?php echo $type === 'avion' ? 'selected' : ''; ?>>✈️ Avion uniquement</option>
                </select>
                
                <button type="submit" class="btn-search">Rechercher</button>
                
                <?php if (isLoggedIn()): ?>
                    <a href="destination-add.php" class="btn-add">+ Ajouter une destination</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($destinations)): ?>
            <div class="stats-bar">
                <div class="stat-badge">
                    <strong><?php echo count($destinations); ?></strong> destination<?php echo count($destinations) > 1 ? 's' : ''; ?> trouvée<?php echo count($destinations) > 1 ? 's' : ''; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="destinations-grid">
            <?php if (empty($destinations)): ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <?php if ($favorisOnly): ?>
                        <div class="empty-state-icon">⭐</div>
                        <h3>Aucun favori enregistré</h3>
                        <p>Vous n'avez pas encore ajouté de destinations en favoris.</p>
                        <a href="destinations.php" class="btn-add" style="margin-top: 1rem;">🗺️ Parcourir les destinations</a>
                    <?php else: ?>
                        <div class="empty-state-icon">🗺️</div>
                        <h3>Aucune destination trouvée</h3>
                        <p>Aucune destination ne correspond à vos critères de recherche.</p>
                        <?php if (isLoggedIn()): ?>
                            <a href="destination-add.php" class="btn-add">+ Ajouter la première destination</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($destinations as $dest): ?>
                    <div class="destination-card" onclick="window.location='destination-detail.php?id=<?php echo $dest['id']; ?>'">
                        <?php if ($dest['photo_principale']): ?>
                            <img src="../uploads/destinations/<?php echo h($dest['photo_principale']); ?>" 
                                 alt="<?php echo h($dest['nom']); ?>"
                                 class="destination-image">
                        <?php else: ?>
                            <div class="destination-image-placeholder">
                                ✈️
                            </div>
                        <?php endif; ?>
                        
                        <div class="destination-content">
                            <h3><?php echo h($dest['nom']); ?></h3>
                            
                            <div class="destination-info">
                                <span>🛫</span>
                                <span><?php echo h($dest['aerodrome']); ?>
                                    <?php if ($dest['code_oaci']): ?>
                                        <strong>(<?php echo h($dest['code_oaci']); ?>)</strong>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="destination-info">
                                <span>📍</span>
                                <span><?php echo h($dest['ville']); ?>, <?php echo h($dest['pays']); ?></span>
                            </div>
                            
                            <div class="destination-tags">
                                <?php if ($dest['acces_ulm']): ?>
                                    <span class="tag tag-ulm">🪂 ULM</span>
                                <?php endif; ?>
                                <?php if ($dest['acces_avion']): ?>
                                    <span class="tag tag-avion">✈️ Avion</span>
                                <?php endif; ?>
                                <?php if ($dest['carburant']): ?>
                                    <span class="tag tag-service">⛽ Carburant</span>
                                <?php endif; ?>
                                <?php if ($dest['restaurant']): ?>
                                    <span class="tag tag-service">🍽️ Restaurant</span>
                                <?php endif; ?>
                                <?php if ($dest['hebergement']): ?>
                                    <span class="tag tag-service">🏨 Hébergement</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($dest['clubs'])): ?>
                                <div class="clubs-section">
                                    <div class="clubs-title">🏛️ Clubs associés</div>
                                    <div class="destination-tags">
                                        <?php foreach ($dest['clubs'] as $club): ?>
                                            <span class="tag tag-club">
                                                <?php echo h($club['nom']); ?>
                                                <?php if ($club['ville']): ?>
                                                    • <?php echo h($club['ville']); ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <a href="destination-detail.php?id=<?php echo $dest['id']; ?>" 
                               class="btn-detail"
                               onclick="event.stopPropagation();">
                               Voir les détails →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        </div><!-- .page-content -->
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
