<?php
// pages/guides-etapes-edit.php
require_once '../config/config.php';
require_once '../includes/functions.php';
$guide_id = intval($_GET['guide_id'] ?? 0);
$etape_id = intval($_GET['etape_id'] ?? 0);
if (!$guide_id || !$etape_id) { header('Location: guides.php'); exit; }
$sql = "SELECT * FROM guides_etapes WHERE id=? AND guide_id=?";
$etape = requete_une($sql, [$etape_id, $guide_id]);
if (!$etape) { header('Location: guides-etapes.php?guide_id=' . $guide_id); exit; }
// Edition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ordre = intval($_POST['ordre'] ?? 1);
    $type_etape = $_POST['type_etape'] ?? '';
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($type_etape && $titre) {
        $sql = "UPDATE guides_etapes SET ordre=?, type_etape=?, titre=?, description=? WHERE id=? AND guide_id=?";
        requete($sql, [$ordre, $type_etape, $titre, $description, $etape_id, $guide_id]);
        header('Location: guides-etapes.php?guide_id=' . $guide_id);
        exit;
    }
}
include '../includes/header.php';
?>
<div class="container">
    <h1>Éditer l'étape</h1>
    <form method="post" class="card p-3 mb-4">
        <div class="row g-2">
            <div class="col-md-1"><input type="number" name="ordre" class="form-control" min="1" value="<?php echo $etape['ordre']; ?>" required></div>
            <div class="col-md-3">
                <select name="type_etape" class="form-select" required>
                    <option value="">Type</option>
                    <option value="aerodrome" <?php if($etape['type_etape']=='aerodrome') echo 'selected'; ?>>Aérodrome</option>
                    <option value="hebergement" <?php if($etape['type_etape']=='hebergement') echo 'selected'; ?>>Hébergement</option>
                    <option value="restauration" <?php if($etape['type_etape']=='restauration') echo 'selected'; ?>>Restauration</option>
                    <option value="avitaillement" <?php if($etape['type_etape']=='avitaillement') echo 'selected'; ?>>Avitaillement</option>
                    <option value="ravitaillement" <?php if($etape['type_etape']=='ravitaillement') echo 'selected'; ?>>Ravitaillement</option>
                    <option value="visite" <?php if($etape['type_etape']=='visite') echo 'selected'; ?>>Visite</option>
                    <option value="autre" <?php if($etape['type_etape']=='autre') echo 'selected'; ?>>Autre</option>
                </select>
            </div>
            <div class="col-md-3"><input type="text" name="titre" class="form-control" value="<?php echo htmlspecialchars($etape['titre']); ?>" required></div>
            <div class="col-md-4"><input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($etape['description']); ?>"></div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-success">Enregistrer</button>
            <a href="guides-etapes.php?guide_id=<?php echo $guide_id; ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
