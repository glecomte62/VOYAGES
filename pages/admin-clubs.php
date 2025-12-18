<?php
/**
 * Administration - Gestion des clubs
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
    
    // Supprimer un club
    if ($action === 'delete' && isset($_POST['club_id'])) {
        $clubId = (int)$_POST['club_id'];
        
        try {
            // Vérifier s'il y a des membres
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM membres_clubs WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $nbMembres = $stmt->fetchColumn();
            
            if ($nbMembres > 0) {
                $error = "Impossible de supprimer ce club : il a encore $nbMembres membre(s) affilié(s)";
            } else {
                $stmt = $pdo->prepare("DELETE FROM clubs WHERE id = ?");
                if ($stmt->execute([$clubId])) {
                    $success = 'Club supprimé avec succès';
                } else {
                    $error = 'Erreur lors de la suppression';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur : ' . $e->getMessage();
        }
    }
    
    // Activer/Désactiver un club
    if ($action === 'toggle_active' && isset($_POST['club_id'])) {
        $clubId = (int)$_POST['club_id'];
        $stmt = $pdo->prepare("UPDATE clubs SET actif = NOT actif WHERE id = ?");
        if ($stmt->execute([$clubId])) {
            $success = 'Statut modifié avec succès';
        } else {
            $error = 'Erreur lors de la modification du statut';
        }
    }
}

// Récupérer les filtres
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Construire la requête
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM membres_clubs mc WHERE mc.club_id = c.id) as nb_membres,
        (SELECT COUNT(*) FROM destination_clubs dc WHERE dc.club_id = c.id) as nb_destinations
        FROM clubs c WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.nom LIKE ? OR c.ville LIKE ? OR c.code_oaci LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($statusFilter !== '') {
    $sql .= " AND c.actif = ?";
    $params[] = (int)$statusFilter;
}

$sql .= " ORDER BY c.nom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clubs = $stmt->fetchAll();

// Statistiques
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM clubs")->fetchColumn(),
    'actifs' => $pdo->query("SELECT COUNT(*) FROM clubs WHERE actif = 1")->fetchColumn(),
    'membres' => $pdo->query("SELECT COUNT(*) FROM membres_clubs")->fetchColumn(),
    'nouveaux' => $pdo->query("SELECT COUNT(*) FROM clubs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des clubs - VOYAGES ULM Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content admin-content">
        <div class="admin-container">
            <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
                <h1 style="font-size: 2.5rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem;">🏛️ Gestion des clubs</h1>
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
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total clubs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['actifs']; ?></div>
                    <div class="stat-label">Clubs actifs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['membres']; ?></div>
                    <div class="stat-label">Affiliations membres</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['nouveaux']; ?></div>
                    <div class="stat-label">Nouveaux (30j)</div>
                </div>
            </div>
            
            <!-- Filtres et recherche -->
            <div class="filters-bar">
                <form method="GET" action="" class="filters-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Rechercher par nom, ville, OACI..." 
                               value="<?php echo h($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select name="status">
                            <option value="">Tous les statuts</option>
                            <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Actifs</option>
                            <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactifs</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="admin-clubs.php" class="btn btn-secondary">Réinitialiser</a>
                </form>
                
                <a href="club-add.php" class="btn btn-success">+ Ajouter un club</a>
            </div>
            
            <!-- Liste des clubs -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Ville</th>
                            <th>Code OACI</th>
                            <th>Membres</th>
                            <th>Destinations</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clubs)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem;">
                                    Aucun club trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clubs as $club): ?>
                                <tr>
                                    <td><?php echo $club['id']; ?></td>
                                    <td>
                                        <strong><?php echo h($club['nom']); ?></strong>
                                    </td>
                                    <td><?php echo h($club['ville']); ?></td>
                                    <td><?php echo h($club['code_oaci'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo $club['nb_membres']; ?> membre<?php echo $club['nb_membres'] > 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?php echo $club['nb_destinations']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($club['actif']): ?>
                                            <span class="badge badge-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Changer le statut de ce club ?');">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>">
                                            <button type="submit" class="btn-action btn-warning" 
                                                    title="<?php echo $club['actif'] ? 'Désactiver' : 'Activer'; ?>">
                                                <?php echo $club['actif'] ? '🔒' : '🔓'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Supprimer ce club ? Cette action est irréversible.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>">
                                            <button type="submit" class="btn-action btn-danger" title="Supprimer">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 2rem; padding: 1.5rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 8px;">
                <h3 style="margin: 0 0 0.5rem; color: #856404;">ℹ️ Informations</h3>
                <ul style="margin: 0; padding-left: 1.5rem; color: #856404;">
                    <li>Les clubs avec des membres affiliés ne peuvent pas être supprimés</li>
                    <li>Désactiver un club le rend invisible pour les utilisateurs</li>
                    <li>Les données du club sont conservées même s'il est inactif</li>
                </ul>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
