<?php
/**
 * Page d'administration - Paramètres du site
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$pdo = getDBConnection();
$success = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'maintenance') {
        // Mode maintenance
        $maintenance = isset($_POST['maintenance_mode']) ? 1 : 0;
        // À implémenter : sauvegarder dans une table de configuration
        $success = 'Mode maintenance ' . ($maintenance ? 'activé' : 'désactivé');
        
    } elseif ($action === 'clear_cache') {
        // Nettoyer le cache
        $cacheDir = '../cache/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*');
            foreach($files as $file) {
                if(is_file($file)) {
                    unlink($file);
                }
            }
            $success = 'Cache vidé avec succès';
        }
        
    } elseif ($action === 'optimize_db') {
        // Optimiser les tables
        try {
            $tables = ['users', 'clubs', 'membres_clubs', 'destinations', 'destination_photos', 
                      'favoris', 'avis_destinations', 'logs_connexions', 'logs_operations'];
            
            foreach ($tables as $table) {
                $pdo->exec("OPTIMIZE TABLE $table");
            }
            $success = 'Base de données optimisée avec succès';
            
            // Logger l'opération
            logOperation($_SESSION['user_id'], 'OPTIMIZE', 'database', null, 'Optimisation de toutes les tables');
            
        } catch (Exception $e) {
            $error = 'Erreur lors de l\'optimisation : ' . $e->getMessage();
        }
    }
}

// Statistiques du site
$stats = [];

try {
    // Nombre total d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE actif = 1");
    $stats['users'] = $stmt->fetchColumn();
    
    // Nombre de destinations
    $stmt = $pdo->query("SELECT COUNT(*) FROM destinations");
    $stats['destinations'] = $stmt->fetchColumn();
    
    // Nombre de clubs
    $stmt = $pdo->query("SELECT COUNT(*) FROM clubs");
    $stats['clubs'] = $stmt->fetchColumn();
    
    // Nombre de photos
    $stmt = $pdo->query("SELECT COUNT(*) FROM destination_photos");
    $stats['photos'] = $stmt->fetchColumn();
    
    // Nombre d'avis
    $stmt = $pdo->query("SELECT COUNT(*) FROM avis_destinations");
    $stats['avis'] = $stmt->fetchColumn();
    
    // Nombre de connexions dans les dernières 24h
    $stmt = $pdo->query("SELECT COUNT(*) FROM logs_connexions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND statut = 'succes'");
    $stats['connexions_24h'] = $stmt->fetchColumn();
    
    // Taille du cache
    $cacheSize = 0;
    $cacheDir = '../cache/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*');
        foreach($files as $file) {
            if(is_file($file)) {
                $cacheSize += filesize($file);
            }
        }
    }
    $stats['cache_size'] = round($cacheSize / 1024 / 1024, 2); // En Mo
    
} catch (Exception $e) {
    $error = 'Erreur lors de la récupération des statistiques';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Admin - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/page-header.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .settings-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        
        .settings-card h3 {
            margin-top: 0;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #10b981;
        }
        
        .action-button {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #10b981;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container" style="padding-top: 6rem;">
        <div class="page-header">
            <h1 class="page-title">⚙️ Paramètres du Site</h1>
        </div>

        <div class="admin-content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ <?php echo h($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ✗ <?php echo h($error); ?>
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                <!-- Statistiques -->
                <div class="settings-card">
                    <h3>📊 Statistiques du site</h3>
                    
                    <div class="stat-item">
                        <span class="stat-label">Utilisateurs actifs</span>
                        <span class="stat-value"><?php echo number_format($stats['users']); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Destinations</span>
                        <span class="stat-value"><?php echo number_format($stats['destinations']); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Clubs</span>
                        <span class="stat-value"><?php echo number_format($stats['clubs']); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Photos</span>
                        <span class="stat-value"><?php echo number_format($stats['photos']); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Avis</span>
                        <span class="stat-value"><?php echo number_format($stats['avis']); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Connexions (24h)</span>
                        <span class="stat-value"><?php echo number_format($stats['connexions_24h']); ?></span>
                    </div>
                </div>

                <!-- Cache -->
                <div class="settings-card">
                    <h3>🗂️ Gestion du cache</h3>
                    
                    <div class="stat-item">
                        <span class="stat-label">Taille du cache</span>
                        <span class="stat-value"><?php echo $stats['cache_size']; ?> Mo</span>
                    </div>
                    
                    <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir vider le cache ?');">
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="action-button btn-warning">
                            🗑️ Vider le cache
                        </button>
                    </form>
                    
                    <div class="info-box">
                        Vider le cache peut améliorer les performances si des fichiers obsolètes s'y trouvent.
                    </div>
                </div>

                <!-- Base de données -->
                <div class="settings-card">
                    <h3>💾 Base de données</h3>
                    
                    <form method="post" onsubmit="return confirm('Optimiser la base de données ?');">
                        <input type="hidden" name="action" value="optimize_db">
                        <button type="submit" class="action-button btn-success">
                            ⚡ Optimiser la base de données
                        </button>
                    </form>
                    
                    <div class="info-box">
                        L'optimisation des tables peut améliorer les performances des requêtes.
                    </div>
                    
                    <a href="admin-logs.php" class="action-button btn-success" style="display: block; text-align: center; text-decoration: none; margin-top: 1rem;">
                        📋 Consulter les logs
                    </a>
                </div>

                <!-- Sécurité -->
                <div class="settings-card">
                    <h3>🔒 Sécurité</h3>
                    
                    <div style="margin-bottom: 1rem;">
                        <p style="color: #6b7280; margin-bottom: 0.5rem;">Les logs de connexion et d'opérations sont activés</p>
                        <span style="color: #10b981; font-weight: 600;">✓ Système de logs actif</span>
                    </div>
                    
                    <a href="admin-logs.php?type=connexions" class="action-button btn-success" style="display: block; text-align: center; text-decoration: none;">
                        🔐 Logs de connexion
                    </a>
                    
                    <a href="admin-logs.php?type=operations" class="action-button btn-success" style="display: block; text-align: center; text-decoration: none;">
                        ⚙️ Logs d'opérations
                    </a>
                    
                    <div class="info-box">
                        Surveillez les connexions et les opérations pour détecter toute activité suspecte.
                    </div>
                </div>

                <!-- Informations système -->
                <div class="settings-card">
                    <h3>ℹ️ Informations système</h3>
                    
                    <div class="stat-item">
                        <span class="stat-label">Version PHP</span>
                        <span class="stat-value" style="font-size: 1rem;"><?php echo phpversion(); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Serveur</span>
                        <span class="stat-value" style="font-size: 0.875rem;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Upload max</span>
                        <span class="stat-value" style="font-size: 1rem;"><?php echo ini_get('upload_max_filesize'); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Memory limit</span>
                        <span class="stat-value" style="font-size: 1rem;"><?php echo ini_get('memory_limit'); ?></span>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="settings-card">
                    <h3>⚡ Actions rapides</h3>
                    
                    <a href="admin-users.php" class="action-button btn-success" style="display: block; text-align: center; text-decoration: none;">
                        👥 Gérer les utilisateurs
                    </a>
                    
                    <a href="admin-destinations.php" class="action-button btn-success" style="display: block; text-align: center; text-decoration: none;">
                        🗺️ Gérer les destinations
                    </a>
                    
                    <a href="../pages/destination-add.php" class="action-button btn-success" style="display: block; text-align: center; text-decoration: none;">
                        ➕ Ajouter une destination
                    </a>
                    
                    <a href="admin.php" class="action-button btn-success" style="display: block; text-align: center; text-decoration: none;">
                        📊 Tableau de bord
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
