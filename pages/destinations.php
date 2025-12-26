<?php
/**
 * VOYAGES - Catalogue des destinations
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupération des destinations
$pdo = getDBConnection();
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$favorisOnly = isset($_GET['favoris']) && $_GET['favoris'] == '1';
$maxDistance = isset($_GET['distance']) && is_numeric($_GET['distance']) ? (int)$_GET['distance'] : null;
$maxTemps = isset($_GET['temps']) && is_numeric($_GET['temps']) ? (int)$_GET['temps'] : null;

// Récupérer le terrain d'attache de l'utilisateur (toujours si connecté)
$terrainAttacheLat = null;
$terrainAttacheLon = null;
$terrainAttacheNom = null;

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $stmtUser = $pdo->prepare("SELECT terrain_attache_type, terrain_attache_id FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $userData = $stmtUser->fetch();
    
    if ($userData && $userData['terrain_attache_type'] && $userData['terrain_attache_id']) {
        $terrainType = $userData['terrain_attache_type'];
        $terrainId = $userData['terrain_attache_id'];
        
        // Récupérer les coordonnées du terrain d'attache
        if ($terrainType === 'aerodrome') {
            $stmtTerrain = $pdo->prepare("SELECT nom, lat as latitude, lon as longitude FROM aerodromes_fr WHERE id = ?");
        } else {
            $stmtTerrain = $pdo->prepare("SELECT nom, lat as latitude, lon as longitude FROM ulm_bases_fr WHERE id = ?");
        }
        $stmtTerrain->execute([$terrainId]);
        $terrain = $stmtTerrain->fetch();
        
        if ($terrain) {
            $terrainAttacheLat = $terrain['latitude'];
            $terrainAttacheLon = $terrain['longitude'];
            $terrainAttacheNom = $terrain['nom'];
        }
    }
}

// Si on demande les favoris, vérifier que l'utilisateur est connecté
if ($favorisOnly && !isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($favorisOnly) {
    // Requête pour les favoris uniquement
    $sql = "SELECT d.*, AVG(ad.note) as avg_rating, COUNT(ad.id) as total_reviews 
            FROM destinations d
            INNER JOIN favoris f ON d.id = f.destination_id
            LEFT JOIN avis_destinations ad ON d.id = ad.destination_id
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
        $sql .= " AND d.acces_ulm = 1 AND d.acces_avion = 0";
    } elseif ($type === 'avion') {
        $sql .= " AND d.acces_avion = 1";
    }
    
    $sql .= " GROUP BY d.id ORDER BY d.nom ASC";
} else {
    // Requête normale pour toutes les destinations
    $sql = "SELECT d.*, AVG(ad.note) as avg_rating, COUNT(ad.id) as total_reviews 
            FROM destinations d
            LEFT JOIN avis_destinations ad ON d.id = ad.destination_id
            WHERE d.actif = 1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (d.nom LIKE ? OR d.aerodrome LIKE ? OR d.ville LIKE ? OR d.code_oaci LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }

    if ($type === 'ulm') {
        $sql .= " AND d.acces_ulm = 1 AND d.acces_avion = 0";
    } elseif ($type === 'avion') {
        $sql .= " AND d.acces_avion = 1";
    }

    $sql .= " GROUP BY d.id ORDER BY d.nom ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

// Filtrer par distance/temps si demandé et si on a le terrain d'attache
if (($maxDistance || $maxTemps) && $terrainAttacheLat && $terrainAttacheLon) {
    $destinationsFiltered = [];
    
    foreach ($destinations as $dest) {
        if ($dest['latitude'] && $dest['longitude']) {
            // Calcul de la distance avec formule de Haversine
            $lat1 = deg2rad($terrainAttacheLat);
            $lon1 = deg2rad($terrainAttacheLon);
            $lat2 = deg2rad($dest['latitude']);
            $lon2 = deg2rad($dest['longitude']);
            
            $dlat = $lat2 - $lat1;
            $dlon = $lon2 - $lon1;
            
            $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance = 6371 * $c; // Distance en km
            
            $dest['distance_from_base'] = round($distance, 1);
            $dest['temps_vol'] = round($distance / 160 * 60); // Temps en minutes à 160 km/h
            
            // Appliquer le filtre
            $include = true;
            if ($maxDistance && $distance > $maxDistance) {
                $include = false;
            }
            if ($maxTemps && ($distance / 160 * 60) > $maxTemps) {
                $include = false;
            }
            
            if ($include) {
                $destinationsFiltered[] = $dest;
            }
        }
    }
    
    // Trier par distance croissante
    usort($destinationsFiltered, function($a, $b) {
        return ($a['distance_from_base'] ?? 9999) <=> ($b['distance_from_base'] ?? 9999);
    });
    
    $destinations = $destinationsFiltered;
}

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
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <main>
        <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 1rem;">
            <?php if ($favorisOnly): ?>
                <div style="text-align: center; margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1);">
                    <h1 style="font-size: clamp(1.8rem, 5vw, 2.8rem); font-weight: 700; background: linear-gradient(135deg, #fbbf24, #84cc16); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 0.5rem; word-wrap: break-word;">⭐ Mes destinations favorites</h1>
                    <p style="font-size: clamp(0.9rem, 3vw, 1.1rem); color: #64748b;">Les aérodromes que vous avez mis en favoris</p>
                </div>
            <?php else: ?>
                <div style="text-align: center; margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1);">
                    <h1 style="font-size: clamp(1.8rem, 5vw, 2.8rem); font-weight: 700; background: linear-gradient(135deg, #0ea5e9, #14b8a6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 0.5rem; word-wrap: break-word;">
                        <?php if ($type === 'ulm'): ?>
                            🛩️ Destinations ULM
                        <?php elseif ($type === 'avion'): ?>
                            ✈️ Destinations Avion
                        <?php else: ?>
                            🗺️ Découvrez nos destinations
                        <?php endif; ?>
                    </h1>
                    <p style="font-size: clamp(0.9rem, 3vw, 1.1rem); color: #64748b; padding: 0 0.5rem;">
                        <?php if ($type === 'ulm'): ?>
                            Explorez les aérodromes accessibles en ULM partout en France
                        <?php elseif ($type === 'avion'): ?>
                            Trouvez les meilleures pistes pour votre avion léger
                        <?php else: ?>
                            Explorez notre catalogue d'aérodromes pour ULM et avions légers
                        <?php endif; ?>
                    </p>
                </div>
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
                
                <?php if ($terrainAttacheLat && $terrainAttacheLon): ?>
                    <select name="distance" class="search-select">
                        <option value="">📏 Toutes distances</option>
                        <option value="50" <?php echo $maxDistance == 50 ? 'selected' : ''; ?>>➡️ Moins de 50 km</option>
                        <option value="100" <?php echo $maxDistance == 100 ? 'selected' : ''; ?>>➡️ Moins de 100 km</option>
                        <option value="200" <?php echo $maxDistance == 200 ? 'selected' : ''; ?>>➡️ Moins de 200 km</option>
                        <option value="500" <?php echo $maxDistance == 500 ? 'selected' : ''; ?>>➡️ Moins de 500 km</option>
                    </select>
                    
                    <select name="temps" class="search-select">
                        <option value="">⏱️ Tous temps de vol</option>
                        <option value="30" <?php echo $maxTemps == 30 ? 'selected' : ''; ?>>⏱️ Moins de 30 min</option>
                        <option value="60" <?php echo $maxTemps == 60 ? 'selected' : ''; ?>>⏱️ Moins de 1h</option>
                        <option value="120" <?php echo $maxTemps == 120 ? 'selected' : ''; ?>>⏱️ Moins de 2h</option>
                        <option value="180" <?php echo $maxTemps == 180 ? 'selected' : ''; ?>>⏱️ Moins de 3h</option>
                    </select>
                <?php elseif (isLoggedIn()): ?>
                    <div style="background: #fef3c7; color: #92400e; padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.875rem;">
                        💡 <a href="profil.php" style="color: #92400e; text-decoration: underline;">Définissez votre terrain d'attache</a> pour activer les filtres par distance/temps
                    </div>
                <?php endif; ?>
                
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
                <?php if (($maxDistance || $maxTemps) && $terrainAttacheNom): ?>
                    <div class="stat-badge" style="background: #dbeafe; color: #1e40af;">
                        📍 Depuis <strong><?php echo h($terrainAttacheNom); ?></strong>
                    </div>
                <?php endif; ?>
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
                            
                            <?php if ($dest['total_reviews'] > 0): ?>
                                <div class="rating-display" style="margin: 0.5rem 0; color: #f59e0b; font-size: 0.9rem;">
                                    <?php 
                                    $rating = round($dest['avg_rating'], 1);
                                    $fullStars = floor($rating);
                                    $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                    
                                    for ($i = 0; $i < $fullStars; $i++) {
                                        echo '⭐';
                                    }
                                    if ($hasHalfStar) {
                                        echo '⭐';
                                    }
                                    for ($i = 0; $i < (5 - $fullStars - ($hasHalfStar ? 1 : 0)); $i++) {
                                        echo '☆';
                                    }
                                    ?>
                                    <span style="color: #666; font-weight: 500;">
                                        <?php echo $rating; ?>/5 (<?php echo $dest['total_reviews']; ?> avis)
                                    </span>
                                </div>
                            <?php endif; ?>
                            
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
                            
                            <?php if (isset($dest['distance_from_base'])): ?>
                                <div class="destination-info" style="background: #dbeafe; padding: 0.5rem; border-radius: 8px; margin: 0.75rem 0;">
                                    <span>📏</span>
                                    <span><strong><?php echo $dest['distance_from_base']; ?> km</strong> depuis votre terrain</span>
                                </div>
                                <div class="destination-info" style="background: #e0e7ff; padding: 0.5rem; border-radius: 8px; margin: 0.75rem 0;">
                                    <span>⏱️</span>
                                    <span><strong><?php 
                                        $heures = floor($dest['temps_vol'] / 60);
                                        $minutes = $dest['temps_vol'] % 60;
                                        if ($heures > 0) {
                                            echo $heures . 'h';
                                            if ($minutes > 0) echo ' ' . $minutes . 'min';
                                        } else {
                                            echo $minutes . 'min';
                                        }
                                    ?></strong> de vol (160 km/h)</span>
                                </div>
                            <?php endif; ?>
                            
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
