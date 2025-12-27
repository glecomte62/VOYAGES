<?php
// Page d'édition d'un voyage communautaire
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();
$pdo = getDBConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Vérifier droits (auteur ou admin)
$stmt = $pdo->prepare("SELECT * FROM voyages_communautaires WHERE id = ?");
$stmt->execute([$id]);
$voyage = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$voyage) { echo '<div class="container"><h2>Voyage non trouvé</h2></div>'; exit; }
 $user = null;
 if (isset($_SESSION['user_id'])) {
     $user = [
         'id' => $_SESSION['user_id'],
         'role' => $_SESSION['user_role'] ?? null
     ];
 }
if (!$user || ($user['id'] != $voyage['auteur_id'] && $user['role'] != 'admin')) { echo '<div class="container"><h2>Accès refusé</h2></div>'; exit; }
// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE voyages_communautaires SET titre=?, description=?, is_public=? WHERE id=?");
    $stmt->execute([$titre, $description, $is_public, $id]);
    header('Location: voyage-communaute-detail2.php?id=' . $id);
    exit;
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer le voyage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        body { background: #f0f9ff; }
        .edit-container {
            max-width: 500px;
            margin: 3rem auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2.5rem 2rem 2rem 2rem;
        }
        .edit-container h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: #0f172a;
        }
        .edit-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #0ea5e9;
        }
        .edit-form input[type="text"],
        .edit-form textarea {
            width: 100%;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            background: #f8fafc;
            color: #0f172a;
        }
        .edit-form textarea {
            min-height: 120px;
            resize: vertical;
        }
        .edit-form .form-row {
            margin-bottom: 1.5rem;
        }
        .edit-form .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
        }
        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-cancel:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="edit-container">
    <h2>Éditer le voyage</h2>
    <form method="post" class="edit-form">
        <div class="form-row">
            <label for="titre">Titre</label>
            <input type="text" id="titre" name="titre" value="<?= htmlspecialchars($voyage['titre']) ?>" required>
        </div>
        <div class="form-row">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($voyage['description']) ?></textarea>
        </div>
        <div class="form-row">
            <label><input type="checkbox" name="is_public" value="1" <?= $voyage['is_public'] ? 'checked' : '' ?>> Rendre ce voyage public</label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Enregistrer</button>
            <a href="voyage-communaute-detail2.php?id=<?= $id ?>" class="btn-cancel">Annuler</a>
        </div>
    </form>
</div>
</body>
</html>
