<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$user = null;
session_start();
if (isset($_SESSION['user_id'])) {
    $user = [
        'id' => $_SESSION['user_id'], 
        'role' => $_SESSION['user_role'] ?? null
    ];
}
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT v.*, u.prenom, u.nom, (SELECT COUNT(*) FROM voyages_communautaires_favoris f WHERE f.voyage_id = v.id) as nb_favoris FROM voyages_communautaires v JOIN users u ON v.auteur_id = u.id ORDER BY v.date_creation DESC");
$voyages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voyages de la communauté - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .voyages-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .voyages-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .voyages-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .voyages-header p {
            color: #64748b;
            font-size: 1.125rem;
        }
        .page-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
        }
        .voyages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        .voyage-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .voyage-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }
        .voyage-card h3 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 1rem;
        }
        .voyage-card .description {
            color: #64748b;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        .voyage-card .dates {
            color: #475569;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .voyage-card .auteur {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .voyage-card .badge-fav {
            background: #fffbe6;
            color: #f59e0b;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.3em 0.8em;
            margin-left: 0.5em;
            font-size: 0.95em;
        }
        .voyage-card .actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f5f9;
        }
        .btn-view {
            flex: 1;
            padding: 0.75rem 1rem;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
            transition: all 0.2s;
        }
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
        @media (max-width: 900px) {
            .voyages-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body style="padding-top: 5rem; background:#f0f9ff;">
    <?php include '../includes/header.php'; ?>
    <main class="voyages-container">
        <div class="voyages-header">
            <h1>🌍 Voyages de la communauté</h1>
            <p>Découvrez, partagez et inspirez la communauté ULM avec vos plus beaux itinéraires !</p>
        </div>
        <div class="page-actions">
            <a href="voyage-communaute-ajout.php" class="btn btn-primary">➕ Proposer un nouveau voyage</a>
        </div>
        <?php if (empty($voyages)): ?>
            <div class="empty-state">
                <h2>Aucun voyage communautaire pour l'instant</h2>
                <p>Partagez le premier itinéraire de la communauté !</p>
                <a href="voyage-communaute-ajout.php" class="btn btn-primary">Créer un voyage</a>
            </div>
        <?php else: ?>
            <div class="voyages-grid">
                <?php foreach ($voyages as $voyage): ?>
                    <div class="voyage-card">
                        <h3><?= htmlspecialchars($voyage['titre']) ?></h3>
                        <div class="auteur">par <strong><?= htmlspecialchars($voyage['prenom'].' '.$voyage['nom']) ?></strong> <span style="color:#94a3b8;">le <?= date('d/m/Y', strtotime($voyage['date_creation'])) ?></span></div>
                        <?php if ($voyage['description']): ?>
                            <p class="description"><?= nl2br(htmlspecialchars(mb_strimwidth($voyage['description'],0,150,'…'))) ?></p>
                        <?php endif; ?>
                        <div class="actions">
                            <a href="voyage-communaute-detail2.php?id=<?= $voyage['id'] ?>" class="btn-view">Voir</a>
                            <?php
                            // Afficher les boutons si connecté et auteur ou admin
                            // $user déjà initialisé en haut
                            if ($user && isset($user['id']) && ($user['id'] == $voyage['auteur_id'] || (isset($user['role']) && $user['role'] == 'admin'))): ?>
                                <a href="voyage-communaute-edit.php?id=<?= $voyage['id'] ?>" class="btn-edit">Éditer</a>
                                <a href="voyage-communaute-supprime.php?id=<?= $voyage['id'] ?>" class="btn-danger" onclick="return confirm('Supprimer ce voyage ?');">Supprimer</a>
                            <?php endif; ?>
                            <span class="badge-fav">★ <?= $voyage['nb_favoris'] ?> favoris</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
