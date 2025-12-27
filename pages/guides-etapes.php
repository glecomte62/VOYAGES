<?php
// pages/guides-etapes.php
require_once '../config/config.php';
require_once '../includes/functions.php';
$guide_id = intval($_GET['guide_id'] ?? 0);
if (!$guide_id) { header('Location: guides.php'); exit; }

// Ajout d'une étape
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_etape'])) {
    $ordre = intval($_POST['ordre'] ?? 1);
    $type_etape = $_POST['type_etape'] ?? '';
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($type_etape && $titre) {
        $sql = "INSERT INTO guides_etapes (guide_id, ordre, type_etape, titre, description) VALUES (?, ?, ?, ?, ?)";
        requete($sql, [$guide_id, $ordre, $type_etape, $titre, $description]);
    }
    header('Location: guides-etapes.php?guide_id=' . $guide_id);
    exit;
}
// Suppression d'une étape
if (isset($_GET['delete_etape'])) {
    $etape_id = intval($_GET['delete_etape']);
    requete("DELETE FROM guides_etapes WHERE id=? AND guide_id=?", [$etape_id, $guide_id]);
    header('Location: guides-etapes.php?guide_id=' . $guide_id);
    exit;
}
// Liste des étapes
$sql = "SELECT * FROM guides_etapes WHERE guide_id = ? ORDER BY ordre ASC";
$etapes = requete_liste($sql, [$guide_id]);
include '../includes/header.php';
?>
<div class="container">
    <h1>Gestion des étapes du guide</h1>
    <a href="guides-edit.php?id=<?php echo $guide_id; ?>" class="btn btn-secondary mb-3">Retour au guide</a>
    <form method="post" class="card p-3 mb-4">
        <h5>Ajouter une étape</h5>
        <div class="row g-2">
            <div class="col-md-1"><input type="number" name="ordre" class="form-control" placeholder="#" min="1" value="<?php echo count($etapes)+1; ?>" required></div>
            <div class="col-md-3">
                <select name="type_etape" class="form-select" required>
                    <option value="">Type</option>
                    <option value="aerodrome">Aérodrome</option>
                    <option value="hebergement">Hébergement</option>
                    <option value="restauration">Restauration</option>
                    <option value="avitaillement">Avitaillement</option>
                    <option value="ravitaillement">Ravitaillement</option>
                    <option value="visite">Visite</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            <div class="col-md-3"><input type="text" name="titre" class="form-control" placeholder="Titre de l'étape" required></div>
            <div class="col-md-4"><input type="text" name="description" class="form-control" placeholder="Description"></div>
            <div class="col-md-1"><button type="submit" name="add_etape" class="btn btn-success">Ajouter</button></div>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>#</th><th>Type</th><th>Titre</th><th>Description</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($etapes as $etape): ?>
            <tr>
                <td><?php echo $etape['ordre']; ?></td>
                <td><?php echo ucfirst($etape['type_etape']); ?></td>
                <td><?php echo htmlspecialchars($etape['titre']); ?></td>
                <td><?php echo htmlspecialchars($etape['description']); ?></td>
                <td>
                    <a href="guides-etapes-edit.php?guide_id=<?php echo $guide_id; ?>&etape_id=<?php echo $etape['id']; ?>" class="btn btn-sm btn-primary">Éditer</a>
                    <a href="guides-etapes.php?guide_id=<?php echo $guide_id; ?>&delete_etape=<?php echo $etape['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette étape ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
