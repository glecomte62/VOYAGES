<?php
/**
 * Administration - Gestion des destinations
 */

require_once '../includes/session.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();
$success = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Supprimer une destination
    if ($action === 'delete' && isset($_POST['destination_id'])) {
        $destId = (int)$_POST['destination_id'];
        
        // Récupérer la photo avant suppression
        $stmt = $pdo->prepare("SELECT photo_principale FROM destinations WHERE id = ?");
        $stmt->execute([$destId]);
        $dest = $stmt->fetch();
        
        // Supprimer la destination
        $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
        if ($stmt->execute([$destId])) {
            // Supprimer la photo
            if ($dest && $dest['photo_principale'] && file_exists('../uploads/destinations/' . $dest['photo_principale'])) {
                unlink('../uploads/destinations/' . $dest['photo_principale']);
            }
            
            // Supprimer les photos de la galerie
            try {
                $stmtPhotos = $pdo->prepare("SELECT chemin_fichier FROM destination_photos WHERE destination_id = ?");
                $stmtPhotos->execute([$destId]);
                $photos = $stmtPhotos->fetchAll();
                foreach ($photos as $photo) {
                    if (file_exists('../uploads/destinations/' . $photo['chemin_fichier'])) {
                        unlink('../uploads/destinations/' . $photo['chemin_fichier']);
                    }
                }
            } catch (PDOException $e) {
                // Table n'existe pas encore
            }
            
            $success = 'Destination supprimée avec succès';
        } else {
            $error = 'Erreur lors de la suppression';
        }
    }
    
    // Activer/Désactiver une destination
    if ($action === 'toggle_active' && isset($_POST['destination_id'])) {
        $destId = (int)$_POST['destination_id'];
        $stmt = $pdo->prepare("UPDATE destinations SET actif = NOT actif WHERE id = ?");
        if ($stmt->execute([$destId])) {
            $success = 'Statut modifié avec succès';
        } else {
            $error = 'Erreur lors de la modification du statut';
        }
    }
}

// Récupérer les filtres
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';

// Construire la requête
$sql = "SELECT d.*, 
        u.nom as creator_nom, u.prenom as creator_prenom
        FROM destinations d
        LEFT JOIN users u ON d.created_by = u.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (d.nom LIKE ? OR d.aerodrome LIKE ? OR d.code_oaci LIKE ? OR d.ville LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($statusFilter !== '') {
    $sql .= " AND d.actif = ?";
    $params[] = (int)$statusFilter;
}

if ($typeFilter === 'ulm') {
    $sql .= " AND d.acces_ulm = 1";
} elseif ($typeFilter === 'avion') {
    $sql .= " AND d.acces_avion = 1";
}

$sql .= " ORDER BY d.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

// Pour chaque destination, récupérer le nombre de clubs
foreach ($destinations as &$dest) {
    try {
        $stmtClubs = $pdo->prepare("SELECT COUNT(*) FROM destination_clubs WHERE destination_id = ?");
        $stmtClubs->execute([$dest['id']]);
        $dest['nb_clubs'] = $stmtClubs->fetchColumn();
    } catch (PDOException $e) {
        $dest['nb_clubs'] = 0;
    }
}
unset($dest); // Détruire la référence

// Statistiques
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn(),
    'actives' => $pdo->query("SELECT COUNT(*) FROM destinations WHERE actif = 1")->fetchColumn(),
    'ulm' => $pdo->query("SELECT COUNT(*) FROM destinations WHERE acces_ulm = 1")->fetchColumn(),
    'avion' => $pdo->query("SELECT COUNT(*) FROM destinations WHERE acces_avion = 1")->fetchColumn(),
    'nouvelles' => $pdo->query("SELECT COUNT(*) FROM destinations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des destinations - VOYAGES ULM Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .destination-row {
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr 120px 150px 200px;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        
        .destination-row:hover {
            background: #f8fafc;
            border-color: #06b6d4;
        }
        
        .destination-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        }
        
        .destination-photo-placeholder {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .destination-name {
            font-weight: 600;
            color: #0c4a6e;
        }
        
        .destination-code {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .badge-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.25rem;
        }
        
        .badge-ulm {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-avion {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .badge-clubs {
            background: #e0f2fe;
            color: #0c4a6e;
        }
        
        .status-active {
            color: #10b981;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #ef4444;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-view:hover {
            background: #bfdbfe;
        }
        
        .btn-edit {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-edit:hover {
            background: #fde68a;
        }
        
        .btn-toggle {
            background: #ddd6fe;
            color: #5b21b6;
        }
        
        .btn-toggle:hover {
            background: #c4b5fd;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-delete:hover {
            background: #fecaca;
        }
        
        .admin-container {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content admin-content">
        <div class="admin-container">
            <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
                <h1 style="font-size: 2.5rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem;">🗺️ Gestion des destinations</h1>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem; justify-content: center;">
                <a href="destination-add.php" class="btn btn-primary">+ Ajouter une destination</a>
                <a href="admin.php" class="btn btn-secondary">← Tableau de bord</a>
            </div>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo h($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">🗺️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total destinations</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">✅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['actives']; ?></div>
                        <div class="stat-label">Actives</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">🪂</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['ulm']; ?></div>
                        <div class="stat-label">Accès ULM</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">✈️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['avion']; ?></div>
                        <div class="stat-label">Accès Avion</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">🆕</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['nouvelles']; ?></div>
                        <div class="stat-label">Derniers 30j</div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="filters-section">
                <form method="GET" action="" class="filters-form">
                    <input type="text" 
                           name="search" 
                           placeholder="🔍 Rechercher (nom, code OACI, ville)..." 
                           value="<?php echo h($search); ?>"
                           class="filter-input">
                    
                    <select name="type" class="filter-select">
                        <option value="">Tous les types</option>
                        <option value="ulm" <?php echo $typeFilter === 'ulm' ? 'selected' : ''; ?>>🪂 ULM</option>
                        <option value="avion" <?php echo $typeFilter === 'avion' ? 'selected' : ''; ?>>✈️ Avion</option>
                    </select>
                    
                    <select name="status" class="filter-select">
                        <option value="">Tous les statuts</option>
                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>✅ Actives</option>
                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>❌ Inactives</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="admin-destinations.php" class="btn btn-secondary">Réinitialiser</a>
                </form>
            </div>
            
            <!-- Liste des destinations -->
            <div class="data-section">
                <div class="section-header">
                    <h2>Liste des destinations (<?php echo count($destinations); ?>)</h2>
                </div>
                
                <?php if (empty($destinations)): ?>
                    <div class="empty-state">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">🗺️</div>
                        <h3>Aucune destination trouvée</h3>
                        <p>Aucune destination ne correspond à vos critères de recherche.</p>
                        <a href="destination-add.php" class="btn btn-primary">+ Ajouter une destination</a>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; display: grid; grid-template-columns: 80px 2fr 1fr 1fr 120px 150px 200px; gap: 1rem; font-weight: 600; color: #64748b; font-size: 0.875rem;">
                        <div>Photo</div>
                        <div>Nom / Aérodrome</div>
                        <div>Localisation</div>
                        <div>Type</div>
                        <div>Clubs</div>
                        <div>Statut</div>
                        <div>Actions</div>
                    </div>
                    
                    <?php foreach ($destinations as $dest): ?>
                        <div class="destination-row">
                            <div>
                                <?php if ($dest['photo_principale']): ?>
                                    <img src="../uploads/destinations/<?php echo h($dest['photo_principale']); ?>" 
                                         alt="<?php echo h($dest['nom']); ?>"
                                         class="destination-photo">
                                <?php else: ?>
                                    <div class="destination-photo-placeholder">📸</div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <div class="destination-name">
                                    <?php echo h($dest['nom'] ?: $dest['aerodrome']); ?>
                                </div>
                                <div class="destination-code">
                                    <?php if ($dest['code_oaci']): ?>
                                        <?php echo h($dest['code_oaci']); ?> • 
                                    <?php endif; ?>
                                    <?php echo h($dest['aerodrome']); ?>
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 0.875rem;">
                                    <?php if ($dest['ville']): ?>
                                        📍 <?php echo h($dest['ville']); ?>
                                    <?php endif; ?>
                                    <?php if ($dest['pays']): ?>
                                        <br><?php echo h($dest['pays']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <?php if ($dest['acces_ulm']): ?>
                                    <span class="badge-type badge-ulm">🪂 ULM</span>
                                <?php endif; ?>
                                <?php if ($dest['acces_avion']): ?>
                                    <span class="badge-type badge-avion">✈️ Avion</span>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <?php if ($dest['nb_clubs'] > 0): ?>
                                    <span class="badge-type badge-clubs">🏛️ <?php echo $dest['nb_clubs']; ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.875rem;">Aucun</span>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <?php if ($dest['actif']): ?>
                                    <span class="status-active">✅ Active</span>
                                <?php else: ?>
                                    <span class="status-inactive">❌ Inactive</span>
                                <?php endif; ?>
                                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                                    Par <?php echo h($dest['creator_prenom'] . ' ' . $dest['creator_nom']); ?>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="destination-detail.php?id=<?php echo $dest['id']; ?>" 
                                   class="btn-icon btn-view"
                                   title="Voir">
                                    👁️
                                </a>
                                
                                <a href="destination-edit.php?id=<?php echo $dest['id']; ?>" 
                                   class="btn-icon btn-edit"
                                   title="Éditer">
                                    ✏️
                                </a>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmer le changement de statut ?')">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="destination_id" value="<?php echo $dest['id']; ?>">
                                    <button type="submit" class="btn-icon btn-toggle" title="Activer/Désactiver">
                                        <?php echo $dest['actif'] ? '🔒' : '🔓'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette destination ? Cette action est irréversible.')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="destination_id" value="<?php echo $dest['id']; ?>">
                                    <button type="submit" class="btn-icon btn-delete" title="Supprimer">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
