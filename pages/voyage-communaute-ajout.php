<?php
// Formulaire d'ajout d'un voyage communautaire
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$pdo = getDBConnection();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($titre) {
        $stmt = $pdo->prepare("INSERT INTO voyages_communautaires (auteur_id, titre, description) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $titre, $description]);
        $voyage_id = $pdo->lastInsertId();
        header('Location: voyage-communaute-detail2.php?id=' . $voyage_id);
        exit;
    } else {
        $message = 'Le titre est obligatoire.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposer un voyage - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .container-ajout {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        .ajout-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.08);
            margin-bottom: 2rem;
        }
        .ajout-card h1 {
            font-size: 2rem;
            color: #0ea5e9;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-label {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            background: #f8fafc;
            color: #0f172a;
            transition: border 0.2s;
        }
        .form-input:focus, .form-textarea:focus {
            border-color: #0ea5e9;
            outline: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.9em 2em;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.2s;
            display: block;
            margin: 0 auto;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #14b8a6, #0ea5e9);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
        }
        .error-message {
            color: #ef4444;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: 600;
        }
    </style>
</head>
<body style="padding-top: 5rem; background:#f0f9ff;">
    <?php include '../includes/header.php'; ?>
    <div class="container-ajout">
        <div class="ajout-card">
            <h1>➕ Proposer un nouveau voyage</h1>
            <?php if ($message): ?><div class="error-message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <form method="post">
                <label class="form-label">Titre du voyage</label>
                <input type="text" name="titre" class="form-input" required maxlength="255" placeholder="Ex : Tour de la Bretagne en ULM">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" rows="5" placeholder="Décrivez l'esprit du voyage, les points forts, etc."></textarea>
                <button type="submit" class="btn-primary">Créer le voyage</button>
            </form>
        </div>
        <a href="voyages-communaute.php" class="back-link">← Retour à la liste des voyages</a>
    </div>
</body>
</html>
