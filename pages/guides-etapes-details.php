<?php
// pages/guides-etapes-details.php
require_once '../config/config.php';
require_once '../includes/functions.php';
$guide_id = intval($_GET['guide_id'] ?? 0);
$etape_id = intval($_GET['etape_id'] ?? 0);
if (!$guide_id || !$etape_id) { header('Location: guides.php'); exit; }
$sql = "SELECT * FROM guides_etapes WHERE id=? AND guide_id=?";
$etape = requete_une($sql, [$etape_id, $guide_id]);
if (!$etape) { header('Location: guides-etapes.php?guide_id=' . $guide_id); exit; }
// Ajout d'un détail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_detail'])) {
    $cle = trim($_POST['cle'] ?? '');
    $valeur = trim($_POST['valeur'] ?? '');
    if ($cle && $valeur) {
        $sql = "INSERT INTO guides_etapes_details (etape_id, cle, valeur) VALUES (?, ?, ?)";
        requete($sql, [$etape_id, $cle, $valeur]);
    }
    header('Location: guides-etapes-details.php?guide_id=' . $guide_id . '&etape_id=' . $etape_id);
    exit;
}
// Suppression d'un détail
if (isset($_GET['delete_detail'])) {
    $detail_id = intval($_GET['delete_detail']);
    requete("DELETE FROM guides_etapes_details WHERE id=? AND etape_id=?", [$detail_id, $etape_id]);
    header('Location: guides-etapes-details.php?guide_id=' . $guide_id . '&etape_id=' . $etape_id);
    exit;
}
// Liste des détails
$sql = "SELECT * FROM guides_etapes_details WHERE etape_id = ? ORDER BY id ASC";
$details = requete_liste($sql, [$etape_id]);
include '../includes/header.php';
?>
<div class="container">
    <h1>Détails de l'étape : <?php echo htmlspecialchars($etape['titre']); ?></h1>
    <a href="guides-etapes.php?guide_id=<?php echo $guide_id; ?>" class="btn btn-secondary mb-3">Retour aux étapes</a>
    <form method="post" class="card p-3 mb-4">
        <h5>Ajouter un détail</h5>
        <div class="row g-2">
            <div class="col-md-4"><input type="text" name="cle" class="form-control" placeholder="Clé (ex: Adresse, Téléphone, Carburant...)" required></div>
            <div class="col-md-6"><input type="text" name="valeur" class="form-control" placeholder="Valeur" required></div>
            <div class="col-md-2"><button type="submit" name="add_detail" class="btn btn-success">Ajouter</button></div>
        </div>
    </form>
    <table class="table table-striped">
        <thead><tr><th>Clé</th><th>Valeur</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($details as $detail): ?>
            <tr>
                <td><?php echo htmlspecialchars($detail['cle']); ?></td>
                <td><?php echo htmlspecialchars($detail['valeur']); ?></td>
                <td>
                    <a href="guides-etapes-details.php?guide_id=<?php echo $guide_id; ?>&etape_id=<?php echo $etape_id; ?>&delete_detail=<?php echo $detail['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce détail ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
