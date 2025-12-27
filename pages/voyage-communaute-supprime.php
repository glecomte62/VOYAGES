<?php
// Suppression d'un voyage communautaire
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
$user = getCurrentUser();
if (!$user || ($user['id'] != $voyage['auteur_id'] && $user['role'] != 'admin')) { echo '<div class="container"><h2>Accès refusé</h2></div>'; exit; }
// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM voyages_communautaires WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: voyages-communaute.php?suppr=ok');
    exit;
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer le voyage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container">
    <h2>Supprimer le voyage</h2>
    <p>Voulez-vous vraiment supprimer ce voyage ?<br><strong><?= htmlspecialchars($voyage['titre']) ?></strong></p>
    <form method="post">
        <button type="submit" class="btn-danger">Oui, supprimer</button>
        <a href="voyage-communaute-detail2.php?id=<?= $id ?>" class="btn-cancel">Annuler</a>
    </form>
</div>
</body>
</html>
