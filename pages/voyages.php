<?php
/**
 * VOYAGES - Gestion des voyages
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupération de tous les voyages de l'utilisateur
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM voyages WHERE user_id = ? ORDER BY date_debut DESC");
$stmt->execute([$_SESSION['user_id']]);
$voyages = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Voyages - VOYAGES ULM</title>
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
        
        .voyage-card .etapes {
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        .voyage-card .statut {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            background: #f1f5f9;
            color: #475569;
        }
        
        .voyage-card .budget {
            color: #059669;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .voyage-card .actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f5f9;
        }
        
        .btn-view, .btn-edit {
            flex: 1;
            padding: 0.75rem 1rem;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
        
        .btn-edit {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-edit:hover {
            background: #e2e8f0;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .empty-state h2 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1.125rem;
        }
        
        .empty-state .btn {
            font-size: 1.125rem;
            padding: 1rem 2rem;
        }
    </style>
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <main class="voyages-container">
        <div class="voyages-header">
            <h1>✈️ Mes Voyages</h1>
            <p>Planifiez et documentez vos aventures aériennes</p>
        </div>
        
        <div class="page-actions">
            <a href="voyage-new.php" class="btn btn-primary">+ Nouveau Voyage</a>
        </div>

        <?php if (empty($voyages)): ?>
            <div class="empty-state">
                <h2>Aucun voyage enregistré</h2>
                <p>Créez votre premier voyage et commencez à planifier votre itinéraire !</p>
                <a href="voyage-new.php" class="btn btn-primary">Créer mon premier voyage</a>
            </div>
        <?php else: ?>
            <div class="voyages-grid">
                <?php foreach ($voyages as $voyage): ?>
                    <div class="voyage-card">
                        <h3><?php echo h($voyage['titre']); ?></h3>
                        <?php if ($voyage['description']): ?>
                            <p class="description"><?php echo h(substr($voyage['description'], 0, 150)) . (strlen($voyage['description']) > 150 ? '...' : ''); ?></p>
                        <?php endif; ?>
                        <p class="dates">
                            📅 <?php echo formatDate($voyage['date_debut']); ?> → <?php echo formatDate($voyage['date_fin']); ?>
                        </p>
                        <?php if ($voyage['nombre_etapes']): ?>
                            <p class="etapes">📍 <?php echo $voyage['nombre_etapes']; ?> étape(s)</p>
                        <?php endif; ?>
                        <p class="statut">
                            <?php 
                                $statut_icons = [
                                    'planifie' => '📋',
                                    'en_cours' => '✈️',
                                    'termine' => '✅',
                                    'annule' => '❌'
                                ];
                                echo $statut_icons[$voyage['statut']] ?? '';
                            ?> 
                            <?php echo ucfirst(str_replace('_', ' ', $voyage['statut'])); ?>
                        </p>
                        <?php if ($voyage['budget_total']): ?>
                            <p class="budget">
                                💰 Budget: <?php echo number_format($voyage['budget_total'], 2); ?> €
                                <?php if ($voyage['budget_depense']): ?>
                                    (Dépensé: <?php echo number_format($voyage['budget_depense'], 2); ?> €)
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <div class="actions">
                            <a href="voyage-detail.php?id=<?php echo $voyage['id']; ?>" class="btn-view">Voir</a>
                            <a href="voyage-planner.php?id=<?php echo $voyage['id']; ?>" class="btn-edit">Planifier</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
