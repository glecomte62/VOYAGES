<?php
/**
 * Page d'administration - Tableau de bord
 * Accessible uniquement aux administrateurs
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est admin
requireAdmin('../index.php');

$pdo = getDBConnection();

// Récupérer les statistiques
$stats = [];

// Nombre total d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE actif = 1");
$stats['users'] = $stmt->fetch()['total'];

// Nombre d'admins
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND actif = 1");
$stats['admins'] = $stmt->fetch()['total'];

// Nombre de destinations
$stmt = $pdo->query("SELECT COUNT(*) as total FROM destinations WHERE actif = 1");
$stats['destinations'] = $stmt->fetch()['total'];

// Nombre de clubs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clubs WHERE actif = 1");
$stats['clubs'] = $stmt->fetch()['total'];

// Derniers utilisateurs inscrits
$stmt = $pdo->query("SELECT id, nom, prenom, email, role, date_inscription FROM users ORDER BY date_inscription DESC LIMIT 10");
$recent_users = $stmt->fetchAll();

$pageTitle = "Administration";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <main class="admin-container">
        <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
            <h1 style="font-size: 2.5rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem;">🛡️ Administration</h1>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?= $stats['users'] ?></h3>
                    <p>Utilisateurs</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-info">
                    <h3><?= $stats['admins'] ?></h3>
                    <p>Administrateurs</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📍</div>
                <div class="stat-info">
                    <h3><?= $stats['destinations'] ?></h3>
                    <p>Destinations</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏛️</div>
                <div class="stat-info">
                    <h3><?= $stats['clubs'] ?></h3>
                    <p>Clubs</p>
                </div>
            </div>
        </div>

        <!-- Menu d'administration -->
        <div class="admin-sections">
            <h2>Actions rapides</h2>
            <div class="admin-menu">
                <a href="admin-users.php" class="admin-card">
                    <span class="admin-icon">👥</span>
                    <h3>Gestion des utilisateurs</h3>
                    <p>Gérer les comptes, rôles et permissions</p>
                </a>
                <a href="admin-destinations.php" class="admin-card">
                    <span class="admin-icon">🗺️</span>
                    <h3>Gestion des destinations</h3>
                    <p>Ajouter, modifier, supprimer des destinations</p>
                </a>
                <a href="admin-clubs.php" class="admin-card">
                    <span class="admin-icon">🏛️</span>
                    <h3>Gestion des clubs</h3>
                    <p>Gérer les clubs et aéroclubs</p>
                </a>
                <a href="admin-settings.php" class="admin-card">
                    <span class="admin-icon">⚙️</span>
                    <h3>Paramètres</h3>
                    <p>Configuration générale du site</p>
                </a>
            </div>
        </div>

        <!-- Derniers utilisateurs -->
        <div class="recent-activity">
            <h2>Derniers utilisateurs inscrits</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date d'inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                    <tr>
                        <td><?= h($user['id']) ?></td>
                        <td><?= h($user['prenom'] . ' ' . $user['nom']) ?></td>
                        <td><?= h($user['email']) ?></td>
                        <td>
                            <span class="badge badge-<?= $user['role'] ?>">
                                <?= $user['role'] === 'admin' ? '🛡️ Admin' : '👤 Utilisateur' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($user['date_inscription'])) ?></td>
                        <td>
                            <a href="admin-users.php?edit=<?= $user['id'] ?>" class="btn-action">Modifier</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
