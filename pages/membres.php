<?php
/**
 * VOYAGES - Annuaire des membres
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $pdo = getDBConnection();
    $search = $_GET['search'] ?? '';

    // Récupérer les membres
    $sql = "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.photo, u.created_at
            FROM users u
            WHERE u.actif = 1";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam];
    }

    $sql .= " ORDER BY u.nom ASC, u.prenom ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $membres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque membre, récupérer ses clubs
    foreach ($membres as $key => $membre) {
        $membres[$key]['clubs'] = [];
        try {
            $stmtClubs = $pdo->prepare("
                SELECT c.id, c.nom, c.ville 
                FROM clubs c
                INNER JOIN membres_clubs mc ON c.id = mc.club_id
                WHERE mc.user_id = ?
                ORDER BY c.nom ASC
            ");
            $stmtClubs->execute([$membre['id']]);
            $clubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);
            $membres[$key]['clubs'] = $clubs;
            
            // Debug temporaire - à supprimer après vérification
            // error_log("Membre {$membre['nom']}: " . count($clubs) . " clubs trouvés");
        } catch (PDOException $e) {
            // Table membres_clubs n'existe pas ou erreur - continuer avec tableau vide
            error_log("Erreur récupération clubs pour membre {$membre['id']}: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuaire des membres - VOYAGES ULM</title>
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
        
        .membres-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .membre-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .membre-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(6, 182, 212, 0.15);
            border-color: #06b6d4;
        }
        
        .membre-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .membre-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            font-weight: 700;
        }
        
        .membre-info {
            flex: 1;
        }
        
        .membre-nom {
            font-size: 1.25rem;
            color: #1e293b;
            font-weight: 700;
            margin: 0;
        }
        
        .membre-date {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        
        .membre-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            color: #475569;
            font-size: 0.875rem;
        }
        
        .membre-clubs {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #f1f5f9;
        }
        
        .membre-clubs-title {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .club-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1e40af;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            margin: 0.25rem 0.25rem 0.25rem 0;
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
        
        @media (max-width: 768px) {
            .membres-grid {
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
        <h1 class="hero-title">👥 Annuaire des membres</h1>
        <p class="hero-subtitle">Découvrez les pilotes de la communauté</p>
    </div>

    <div class="container">
        <div class="search-card">
            <form method="GET" action="" class="search-form">
                <input type="text" 
                       name="search" 
                       class="search-input"
                       placeholder="🔍 Rechercher par nom ou prénom..." 
                       value="<?php echo h($search); ?>">
                <button type="submit" class="btn-search">Rechercher</button>
            </form>
        </div>

        <?php if (!empty($search)): ?>
            <div class="stats-bar">
                <?php echo count($membres); ?> membre<?php echo count($membres) > 1 ? 's' : ''; ?> trouvé<?php echo count($membres) > 1 ? 's' : ''; ?> pour "<strong><?php echo h($search); ?></strong>"
            </div>
        <?php else: ?>
            <div class="stats-bar">
                <strong><?php echo count($membres); ?></strong> membre<?php echo count($membres) > 1 ? 's' : ''; ?> inscrit<?php echo count($membres) > 1 ? 's' : ''; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($membres)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h3>Aucun membre trouvé</h3>
                <p style="color: #64748b;">Essayez de modifier votre recherche</p>
            </div>
        <?php else: ?>
            <div class="membres-grid">
                <?php foreach ($membres as $membre): ?>
                    <div class="membre-card">
                        <div class="membre-header">
                            <div class="membre-avatar">
                                <?php if (!empty($membre['photo'])): ?>
                                    <img src="../uploads/photos/<?php echo h($membre['photo']); ?>" 
                                         alt="<?php echo h($membre['prenom'] . ' ' . $membre['nom']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($membre['prenom'], 0, 1) . substr($membre['nom'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="membre-info">
                                <h3 class="membre-nom"><?php echo h($membre['prenom'] . ' ' . $membre['nom']); ?></h3>
                                <div class="membre-date">
                                    Membre depuis <?php echo date('m/Y', strtotime($membre['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isLoggedIn() && $membre['email']): ?>
                            <div class="membre-detail">
                                <span>✉️</span>
                                <a href="mailto:<?php echo h($membre['email']); ?>" style="color: #0ea5e9; text-decoration: none;">
                                    Envoyer un message
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isLoggedIn() && !empty($membre['telephone'])): ?>
                            <div class="membre-detail">
                                <span>📞</span>
                                <a href="tel:<?php echo h($membre['telephone']); ?>" style="color: #0ea5e9; text-decoration: none;">
                                    <?php echo h($membre['telephone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($membre['clubs'])): ?>
                            <div class="membre-clubs">
                                <div class="membre-clubs-title">Clubs</div>
                                <?php foreach ($membre['clubs'] as $club): ?>
                                    <span class="club-badge">
                                        🏛️ <?php echo h($club['nom']); ?>
                                    </span>
                                <?php endforeach; ?>
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
