<?php
// pages/guides-add.php
require_once '../config/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $auteur_id = $_SESSION['user_id'] ?? 1; // à adapter selon gestion session
    if ($titre) {
        $sql = "INSERT INTO guides_de_voyages (auteur_id, titre, description, region) VALUES (?, ?, ?, ?)";
        requete($sql, [$auteur_id, $titre, $description, $region]);
        header('Location: guides.php');
        exit;
    }
}
include '../includes/header.php';
?>
<div class="container">
    <h1>Créer un guide de voyage</h1>
    <form method="post">
        <div class="mb-3">
            <label for="titre" class="form-label">Titre</label>
            <input type="text" class="form-control" id="titre" name="titre" required>
        </div>
        <div class="mb-3">
            <label for="region" class="form-label">Région</label>
            <input type="text" class="form-control" id="region" name="region">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Créer le guide</button>
        <a href="guides.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
