<?php
/**
 * Administration - Gestion des utilisateurs
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
    
    // Supprimer un utilisateur
    if ($action === 'delete' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        if ($userId === $_SESSION['user_id']) {
            $error = 'Vous ne pouvez pas supprimer votre propre compte';
        } else {
            // Récupérer la photo avant suppression
            $stmt = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            // Supprimer l'utilisateur
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$userId])) {
                // Supprimer la photo
                if ($user && $user['photo']) {
                    deletePhoto($user['photo']);
                }
                $success = 'Utilisateur supprimé avec succès';
            } else {
                $error = 'Erreur lors de la suppression';
            }
        }
    }
    
    // Changer le rôle
    if ($action === 'change_role' && isset($_POST['user_id']) && isset($_POST['role'])) {
        $userId = (int)$_POST['user_id'];
        $role = $_POST['role'];
        
        if (!in_array($role, ['user', 'admin'])) {
            $error = 'Rôle invalide';
        } elseif ($userId === $_SESSION['user_id']) {
            $error = 'Vous ne pouvez pas modifier votre propre rôle';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$role, $userId])) {
                $success = 'Rôle modifié avec succès';
            } else {
                $error = 'Erreur lors de la modification du rôle';
            }
        }
    }
    
    // Activer/Désactiver un utilisateur
    if ($action === 'toggle_active' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        if ($userId === $_SESSION['user_id']) {
            $error = 'Vous ne pouvez pas désactiver votre propre compte';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET actif = NOT actif WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $success = 'Statut modifié avec succès';
            } else {
                $error = 'Erreur lors de la modification du statut';
            }
        }
    }
}

// Récupérer les filtres
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Construire la requête
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($roleFilter !== '') {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter !== '') {
    $sql .= " AND actif = ?";
    $params[] = (int)$statusFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Statistiques
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
    'actifs' => $pdo->query("SELECT COUNT(*) FROM users WHERE actif = 1")->fetchColumn(),
    'nouveaux' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - VOYAGES ULM Admin</title>
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content admin-content" style="padding-top: 6rem;">
        <div class="admin-container">
            <div class="page-header">
                <h1 class="page-title">👥 Gestion des utilisateurs</h1>
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
                    <div class="stat-label">Total utilisateurs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['admins']; ?></div>
                    <div class="stat-label">Administrateurs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['actifs']; ?></div>
                    <div class="stat-label">Comptes actifs</div>
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
                        <input type="text" name="search" placeholder="Rechercher..." 
                               value="<?php echo h($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select name="role">
                            <option value="">Tous les rôles</option>
                            <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>Utilisateurs</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Administrateurs</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="status">
                            <option value="">Tous les statuts</option>
                            <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Actifs</option>
                            <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactifs</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="admin-users.php" class="btn btn-secondary">Réinitialiser</a>
                </form>
            </div>
            
            <!-- Liste des utilisateurs -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Aucun utilisateur trouvé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-photo-small">
                                            <?php if ($user['photo']): ?>
                                                <img src="../uploads/photos/<?php echo h($user['photo']); ?>" alt="">
                                            <?php else: ?>
                                                <div class="photo-placeholder-small">
                                                    <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo h($user['prenom'] . ' ' . $user['nom']); ?></strong>
                                    </td>
                                    <td><?php echo h($user['email']); ?></td>
                                    <td><?php echo h($user['telephone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo h($user['role']); ?>">
                                            <?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['actif'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['actif'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($user['created_at'], 'd/m/Y'); ?></td>
                                    <td class="actions-cell">
                                        <!-- Éditer -->
                                        <a href="admin-user-edit.php?id=<?php echo $user['id']; ?>" 
                                           class="btn-action btn-edit" 
                                           title="Éditer">
                                            ✏️
                                        </a>
                                        
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <!-- Changer le rôle -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="role" value="<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>">
                                                <button type="submit" class="btn-action btn-role" 
                                                        title="<?php echo $user['role'] === 'admin' ? 'Rétrograder en user' : 'Promouvoir admin'; ?>">
                                                    <?php echo $user['role'] === 'admin' ? '👤' : '⭐'; ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Activer/Désactiver -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn-action btn-toggle" 
                                                        title="<?php echo $user['actif'] ? 'Désactiver' : 'Activer'; ?>">
                                                    <?php echo $user['actif'] ? '🔒' : '🔓'; ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Supprimer -->
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn-action btn-delete" title="Supprimer">
                                                    🗑️
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
