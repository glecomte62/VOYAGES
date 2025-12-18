<?php
/**
 * VOYAGES - Annuaire des clubs
 */

require_once '../includes/session.php';
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
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        body {
            background: url('../assets/images/hero-bg.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .hero-banner {
            text-align: center;
            margin-bottom: 3rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #14b8a6 100%);
            padding: 3rem 2rem;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.2);
            position: relative;
            overflow: hidden;
            margin-top: -2rem;
        }
        
        .hero-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .hero-banner::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        
        .hero-title {
            font-size: 3rem;
            color: white;
            margin-bottom: 0.75rem;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .hero-subtitle {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.25rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        .search-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
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
            white-space: nowrap;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.3);
        }
        
        .stats-bar {
            text-align: center;
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1.125rem;
        }
        
        .stats-bar strong {
            color: #0ea5e9;
        }
        
        .clubs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .club-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .club-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(6, 182, 212, 0.15);
            border-color: #06b6d4;
        }
        
        .club-header {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .club-name {
            font-size: 1.375rem;
            color: #1e293b;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }
        
        .club-code {
            display: inline-block;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .club-info {
            margin: 0.75rem 0;
            color: #475569;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .club-info-icon {
            flex-shrink: 0;
            font-size: 1.125rem;
        }
        
        .club-actions {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid #f1f5f9;
        }
        
        .btn-join {
            display: inline-block;
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(6, 182, 212, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .empty-state-text {
            color: #64748b;
            font-size: 1.125rem;
        }
        
        @media (max-width: 768px) {
            .clubs-grid {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .hero-banner {
                padding: 2rem 1rem;
                margin-top: -1rem;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="hero-banner">
        <h1 class="hero-title">🏛️ Annuaire des clubs</h1>
        <p class="hero-subtitle">Trouvez votre club ULM ou avion</p>
    </div>

    <div class="container">
        <div class="search-card">
            <form method="GET" action="" class="search-form">
                <input type="text" 
                       name="search" 
                       class="search-input"
                       placeholder="🔍 Rechercher par nom, ville, aérodrome ou code OACI..." 
                       value="<?php echo h($search); ?>">
                <button type="submit" class="btn-search">Rechercher</button>
            </form>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="club-add.php" class="btn-search" style="display: inline-block; text-decoration: none;">
                    ➕ Ajouter un club
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($search)): ?>
            <div class="stats-bar">
                <?php echo count($clubs); ?> club<?php echo count($clubs) > 1 ? 's' : ''; ?> trouvé<?php echo count($clubs) > 1 ? 's' : ''; ?> pour "<strong><?php echo h($search); ?></strong>"
            </div>
        <?php else: ?>
            <div class="stats-bar">
                <strong><?php echo count($clubs); ?></strong> club<?php echo count($clubs) > 1 ? 's' : ''; ?> référencé<?php echo count($clubs) > 1 ? 's' : ''; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($clubs)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h3 class="empty-state-title">Aucun club trouvé</h3>
                <p class="empty-state-text">Essayez de modifier votre recherche</p>
            </div>
        <?php else: ?>
            <div class="clubs-grid">
                <?php foreach ($clubs as $club): ?>
                    <div class="club-card">
                        <div class="club-header">
                            <h3 class="club-name"><?php echo h($club['nom']); ?></h3>
                            <?php if ($club['code']): ?>
                                <span class="club-code"><?php echo h($club['code']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="club-info">
                            <span class="club-info-icon">📍</span>
                            <span>
                                <?php echo h($club['ville']); ?>
                                <?php if ($club['aerodrome']): ?>
                                    <br><small style="color: #94a3b8;"><?php echo h($club['aerodrome']); ?></small>
                                <?php endif; ?>
                                <?php if ($club['code_oaci']): ?>
                                    <br><small style="color: #94a3b8;">Code OACI: <?php echo h($club['code_oaci']); ?></small>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($club['telephone']): ?>
                            <div class="club-info">
                                <span class="club-info-icon">📞</span>
                                <a href="tel:<?php echo h($club['telephone']); ?>" style="color: #0ea5e9; text-decoration: none;">
                                    <?php echo h($club['telephone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($club['email']): ?>
                            <div class="club-info">
                                <span class="club-info-icon">✉️</span>
                                <a href="mailto:<?php echo h($club['email']); ?>" style="color: #0ea5e9; text-decoration: none;">
                                    <?php echo h($club['email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($club['site_web']): ?>
                            <div class="club-info">
                                <span class="club-info-icon">🌐</span>
                                <a href="<?php echo h($club['site_web']); ?>" target="_blank" style="color: #0ea5e9; text-decoration: none;">
                                    Site web
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isLoggedIn()): ?>
                            <div class="club-actions">
                                <a href="club-join.php?id=<?php echo $club['id']; ?>" class="btn-join">
                                    ✈️ Rejoindre ce club
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <footer style="background: white; margin-top: 4rem; padding: 2rem; text-align: center; border-top: 2px solid #f1f5f9; box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.05);">
        <p style="color: #64748b; margin: 0; font-size: 0.875rem;">
            © <?php echo date('Y'); ?> <strong style="color: #0ea5e9;">VOYAGES</strong> - Application collaborative pour pilotes ULM et avion
        </p>
        <p style="color: #94a3b8; margin: 0.5rem 0 0 0; font-size: 0.75rem;">
            ✈️ Partagez vos destinations • 🗺️ Découvrez de nouveaux horizons • 🏛️ Rejoignez votre club
        </p>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
