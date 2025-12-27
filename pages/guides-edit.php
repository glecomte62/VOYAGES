<?php
// pages/guides-edit.php
require_once '../config/config.php';
require_once '../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
$sql = "SELECT * FROM guides_de_voyages WHERE id = ?";
$guide = requete_une($sql, [$id]);
if (!$guide) {
    header('Location: guides.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    if ($titre) {
        $sql = "UPDATE guides_de_voyages SET titre=?, description=?, region=?, is_published=? WHERE id=?";
        requete($sql, [$titre, $description, $region, $is_published, $id]);
        header('Location: guides-detail.php?id=' . $id);
        exit;
    }
}
include '../includes/header.php';
?>
<div class="container">
    <h1>Éditer le guide</h1>
    <form method="post">
        <div class="mb-3">
            <label for="titre" class="form-label">Titre</label>
            <input type="text" class="form-control" id="titre" name="titre" value="<?php echo htmlspecialchars($guide['titre']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="region" class="form-label">Région</label>
            <input type="text" class="form-control" id="region" name="region" value="<?php echo htmlspecialchars($guide['region']); ?>">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($guide['description']); ?></textarea>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" <?php if ($guide['is_published']) echo 'checked'; ?>>
            <label class="form-check-label" for="is_published">Publié</label>
        </div>
        <button type="submit" class="btn btn-success">Enregistrer</button>
        <a href="guides-detail.php?id=<?php echo $id; ?>" class="btn btn-secondary">Annuler</a>
    </form>

    <hr>
    <h2>Étapes du guide</h2>
    <a href="guides-etapes.php?guide_id=<?php echo $id; ?>" class="btn btn-outline-primary mb-3">Gérer les étapes</a>
    <?php
    // Affichage rapide des étapes (liste)
    $sql_etapes = "SELECT * FROM guides_etapes WHERE guide_id = ? ORDER BY ordre ASC";
    $etapes = requete_liste($sql_etapes, [$id]);
    ?>
    <ul class="list-group mb-4">
        <?php foreach ($etapes as $etape): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <strong><?php echo htmlspecialchars($etape['titre']); ?></strong> (<?php echo htmlspecialchars($etape['type_etape']); ?>)
                </span>
                <span>
                    <a href="guides-etapes-edit.php?guide_id=<?php echo $id; ?>&etape_id=<?php echo $etape['id']; ?>" class="btn btn-sm btn-primary">Éditer</a>
                    <a href="guides-etapes-details.php?guide_id=<?php echo $id; ?>&etape_id=<?php echo $etape['id']; ?>" class="btn btn-sm btn-info">Détails</a>
                    <a href="guides-etapes.php?guide_id=<?php echo $id; ?>&delete_etape=<?php echo $etape['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette étape ?');">Supprimer</a>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include '../includes/footer.php'; ?>
