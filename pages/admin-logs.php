<?php
/**
 * Page d'administration - Logs système
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

// Paramètres de pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Type de log à afficher (connexions ou opérations)
$type = isset($_GET['type']) && $_GET['type'] === 'operations' ? 'operations' : 'connexions';

// Filtres
$filterUser = isset($_GET['user']) ? intval($_GET['user']) : null;
$filterStatut = isset($_GET['statut']) ? $_GET['statut'] : null;
$filterAction = isset($_GET['action']) ? $_GET['action'] : null;
$filterDateDebut = isset($_GET['date_debut']) ? $_GET['date_debut'] : null;
$filterDateFin = isset($_GET['date_fin']) ? $_GET['date_fin'] : null;

// Récupération des logs selon le type
if ($type === 'connexions') {
    // Logs de connexion
    $sql = "SELECT lc.*, u.nom, u.prenom, u.email as user_email 
            FROM logs_connexions lc
            LEFT JOIN users u ON lc.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    if ($filterUser) {
        $sql .= " AND lc.user_id = ?";
        $params[] = $filterUser;
    }
    if ($filterStatut) {
        $sql .= " AND lc.statut = ?";
        $params[] = $filterStatut;
    }
    if ($filterDateDebut) {
        $sql .= " AND DATE(lc.created_at) >= ?";
        $params[] = $filterDateDebut;
    }
    if ($filterDateFin) {
        $sql .= " AND DATE(lc.created_at) <= ?";
        $params[] = $filterDateFin;
    }
    
    $sql .= " ORDER BY lc.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Compter le total pour la pagination
    $sqlCount = "SELECT COUNT(*) FROM logs_connexions lc WHERE 1=1";
    $paramsCount = [];
    if ($filterUser) {
        $sqlCount .= " AND lc.user_id = ?";
        $paramsCount[] = $filterUser;
    }
    if ($filterStatut) {
        $sqlCount .= " AND lc.statut = ?";
        $paramsCount[] = $filterStatut;
    }
    if ($filterDateDebut) {
        $sqlCount .= " AND DATE(lc.created_at) >= ?";
        $paramsCount[] = $filterDateDebut;
    }
    if ($filterDateFin) {
        $sqlCount .= " AND DATE(lc.created_at) <= ?";
        $paramsCount[] = $filterDateFin;
    }
    
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($paramsCount);
    $totalLogs = $stmtCount->fetchColumn();
    
} else {
    // Logs d'opérations
    $sql = "SELECT lo.*, u.nom, u.prenom, u.email as user_email 
            FROM logs_operations lo
            LEFT JOIN users u ON lo.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    if ($filterUser) {
        $sql .= " AND lo.user_id = ?";
        $params[] = $filterUser;
    }
    if ($filterAction) {
        $sql .= " AND lo.action = ?";
        $params[] = $filterAction;
    }
    if ($filterDateDebut) {
        $sql .= " AND DATE(lo.created_at) >= ?";
        $params[] = $filterDateDebut;
    }
    if ($filterDateFin) {
        $sql .= " AND DATE(lo.created_at) <= ?";
        $params[] = $filterDateFin;
    }
    
    $sql .= " ORDER BY lo.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Compter le total pour la pagination
    $sqlCount = "SELECT COUNT(*) FROM logs_operations lo WHERE 1=1";
    $paramsCount = [];
    if ($filterUser) {
        $sqlCount .= " AND lo.user_id = ?";
        $paramsCount[] = $filterUser;
    }
    if ($filterAction) {
        $sqlCount .= " AND lo.action = ?";
        $paramsCount[] = $filterAction;
    }
    if ($filterDateDebut) {
        $sqlCount .= " AND DATE(lo.created_at) >= ?";
        $paramsCount[] = $filterDateDebut;
    }
    if ($filterDateFin) {
        $sqlCount .= " AND DATE(lo.created_at) <= ?";
        $paramsCount[] = $filterDateFin;
    }
    
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($paramsCount);
    $totalLogs = $stmtCount->fetchColumn();
}

$totalPages = ceil($totalLogs / $perPage);

// Récupérer la liste des utilisateurs pour le filtre
$stmtUsers = $pdo->query("SELECT id, nom, prenom, email FROM users ORDER BY nom, prenom");
$users = $stmtUsers->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs système - Admin - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/page-header.css">
    <style>
        body {
            background-image: url('../assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .logs-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .tab-button {
            padding: 1rem 2rem;
            border: none;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tab-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .tab-button.active {
            background: linear-gradient(135deg, #fbbf24, #84cc16);
            color: white;
        }
        
        .filters-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .logs-table {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .logs-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .logs-table th {
            background: linear-gradient(135deg, #fbbf24, #84cc16);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .logs-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .logs-table tr:last-child td {
            border-bottom: none;
        }
        
        .logs-table tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #374151;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .pagination a:hover {
            background: #fbbf24;
            color: white;
        }
        
        .pagination .current {
            background: linear-gradient(135deg, #fbbf24, #84cc16);
            color: white;
        }
        
        .ip-address {
            font-family: monospace;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .details-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container" style="padding-top: 6rem;">
        <div class="page-header">
            <h1 class="page-title">📊 Logs Système</h1>
        </div>

        <div class="admin-content">
            <div class="logs-tabs">
                <a href="?type=connexions" class="tab-button <?php echo $type === 'connexions' ? 'active' : ''; ?>">
                    🔐 Logs de connexion
                </a>
                <a href="?type=operations" class="tab-button <?php echo $type === 'operations' ? 'active' : ''; ?>">
                    ⚙️ Logs d'opérations
                </a>
            </div>

            <div class="filters-card">
                <h3 style="margin-top: 0;">Filtres</h3>
                <form method="get" action="">
                    <input type="hidden" name="type" value="<?php echo h($type); ?>">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label>Utilisateur</label>
                            <select name="user" class="form-control">
                                <option value="">Tous</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $filterUser == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($user['nom'] . ' ' . $user['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if ($type === 'connexions'): ?>
                            <div class="form-group">
                                <label>Statut</label>
                                <select name="statut" class="form-control">
                                    <option value="">Tous</option>
                                    <option value="succes" <?php echo $filterStatut === 'succes' ? 'selected' : ''; ?>>Succès</option>
                                    <option value="echec" <?php echo $filterStatut === 'echec' ? 'selected' : ''; ?>>Échec</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label>Action</label>
                                <select name="action" class="form-control">
                                    <option value="">Toutes</option>
                                    <option value="CREATE" <?php echo $filterAction === 'CREATE' ? 'selected' : ''; ?>>CREATE</option>
                                    <option value="UPDATE" <?php echo $filterAction === 'UPDATE' ? 'selected' : ''; ?>>UPDATE</option>
                                    <option value="DELETE" <?php echo $filterAction === 'DELETE' ? 'selected' : ''; ?>>DELETE</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Date début</label>
                            <input type="date" name="date_debut" value="<?php echo h($filterDateDebut ?? ''); ?>" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Date fin</label>
                            <input type="date" name="date_fin" value="<?php echo h($filterDateFin ?? ''); ?>" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="?type=<?php echo h($type); ?>" class="btn btn-secondary">Réinitialiser</a>
                </form>
            </div>

            <div class="logs-table">
                <?php if ($type === 'connexions'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Statut</th>
                                <th>Adresse IP</th>
                                <th>Navigateur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        Aucun log trouvé
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td>
                                            <?php if ($log['nom'] && $log['prenom']): ?>
                                                <?php echo h($log['nom'] . ' ' . $log['prenom']); ?>
                                            <?php else: ?>
                                                <em style="color: #9ca3af;">Inconnu</em>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo h($log['email']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $log['statut'] === 'succes' ? 'badge-success' : 'badge-error'; ?>">
                                                <?php echo $log['statut'] === 'succes' ? '✓ Succès' : '✗ Échec'; ?>
                                            </span>
                                        </td>
                                        <td class="ip-address"><?php echo h($log['ip_address']); ?></td>
                                        <td class="details-cell" title="<?php echo h($log['user_agent'] ?? ''); ?>">
                                            <?php echo h(substr($log['user_agent'] ?? '', 0, 50)); ?>
                                            <?php if (strlen($log['user_agent'] ?? '') > 50): ?>...<?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Heure</th>
                                <th>Utilisateur</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>ID Élément</th>
                                <th>Détails</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        Aucun log trouvé
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td>
                                            <?php if ($log['nom'] && $log['prenom']): ?>
                                                <?php echo h($log['nom'] . ' ' . $log['prenom']); ?>
                                            <?php else: ?>
                                                <em style="color: #9ca3af;">Système</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo h($log['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo h($log['table_affectee']); ?></td>
                                        <td><?php echo h($log['id_element'] ?? '-'); ?></td>
                                        <td class="details-cell" title="<?php echo h($log['details'] ?? ''); ?>">
                                            <?php echo h(substr($log['details'] ?? '', 0, 50)); ?>
                                            <?php if (strlen($log['details'] ?? '') > 50): ?>...<?php endif; ?>
                                        </td>
                                        <td class="ip-address"><?php echo h($log['ip_address']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?type=<?php echo h($type); ?>&page=<?php echo $page - 1; ?><?php echo $filterUser ? '&user=' . $filterUser : ''; ?><?php echo $filterStatut ? '&statut=' . $filterStatut : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterDateDebut ? '&date_debut=' . $filterDateDebut : ''; ?><?php echo $filterDateFin ? '&date_fin=' . $filterDateFin : ''; ?>">« Précédent</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?type=<?php echo h($type); ?>&page=<?php echo $i; ?><?php echo $filterUser ? '&user=' . $filterUser : ''; ?><?php echo $filterStatut ? '&statut=' . $filterStatut : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterDateDebut ? '&date_debut=' . $filterDateDebut : ''; ?><?php echo $filterDateFin ? '&date_fin=' . $filterDateFin : ''; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?type=<?php echo h($type); ?>&page=<?php echo $page + 1; ?><?php echo $filterUser ? '&user=' . $filterUser : ''; ?><?php echo $filterStatut ? '&statut=' . $filterStatut : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterDateDebut ? '&date_debut=' . $filterDateDebut : ''; ?><?php echo $filterDateFin ? '&date_fin=' . $filterDateFin : ''; ?>">Suivant »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
