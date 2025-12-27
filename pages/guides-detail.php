<?php
// pages/guides-detail.php
require_once '../config/config.php';
require_once '../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
$sql = "SELECT g.*, u.nom, u.prenom FROM guides_de_voyages g JOIN users u ON g.auteur_id = u.id WHERE g.id = ?";
$guide = requete_une($sql, [$id]);
if (!$guide) {
    header('Location: guides.php');
    exit;
}
// Étapes et détails
$sql_etapes = "SELECT * FROM guides_etapes WHERE guide_id = ? ORDER BY ordre ASC";
$etapes = requete_liste($sql_etapes, [$id]);
$etapes_details = [];
foreach ($etapes as $e) {
    $etapes_details[$e['id']] = requete_liste("SELECT * FROM guides_etapes_details WHERE etape_id = ?", [$e['id']]);
}
// Photos guide et par étape
$sql_photos = "SELECT * FROM guides_photos WHERE guide_id = ?";
$photos = requete_liste($sql_photos, [$id]);
$photos_par_etape = [];
foreach ($photos as $p) {
    if ($p['etape_id']) $photos_par_etape[$p['etape_id']][] = $p;
}
// Commentaires
$sql_commentaires = "SELECT c.*, u.nom, u.prenom FROM guides_commentaires c JOIN users u ON c.auteur_id = u.id WHERE guide_id = ? ORDER BY date_commentaire DESC";
$commentaires = requete_liste($sql_commentaires, [$id]);
include '../includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>#guideMap { height: 350px; margin-bottom: 2rem; border-radius: 8px; border:1px solid #ccc; }</style>
<div class="container">
    <h1><?php echo htmlspecialchars($guide['titre']); ?></h1>
    <p class="text-muted">Par <?php echo htmlspecialchars($guide['prenom'].' '.$guide['nom']); ?> | Région : <?php echo htmlspecialchars($guide['region']); ?></p>
    <p><?php echo nl2br(htmlspecialchars($guide['description'])); ?></p>
    <h2>Carte du circuit</h2>
    <div id="guideMap"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/leaflet-init.js"></script>
    <script>
    // Préparation des coordonnées des étapes (si présentes dans les détails)
    var etapes = [
        <?php foreach ($etapes as $etape):
            $lat = $lng = null;
            if (!empty($etapes_details[$etape['id']])) {
                foreach ($etapes_details[$etape['id']] as $detail) {
                    if (strtolower($detail['cle']) == 'lat' || strtolower($detail['cle']) == 'latitude') $lat = floatval($detail['valeur']);
                    if (strtolower($detail['cle']) == 'lng' || strtolower($detail['cle']) == 'lon' || strtolower($detail['cle']) == 'longitude') $lng = floatval($detail['valeur']);
                }
            }
        ?>
        {
            id: <?php echo $etape['id']; ?>,
            titre: <?php echo json_encode($etape['titre']); ?>,
            type_etape: <?php echo json_encode($etape['type_etape']); ?>,
            lat: <?php echo $lat ? $lat : 'null'; ?>,
            lng: <?php echo $lng ? $lng : 'null'; ?>
        },
        <?php endforeach; ?>
    ];
    initGuideMap(etapes);
    </script>
    <h2>Étapes du circuit</h2>
    <ol class="list-group mb-4">
        <?php foreach ($etapes as $etape): ?>
            <li class="list-group-item mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2"><?php echo ucfirst($etape['type_etape']); ?></span>
                        <strong><?php echo htmlspecialchars($etape['titre']); ?></strong>
                    </div>
                </div>
                <div class="mt-2 mb-2"><?php echo nl2br(htmlspecialchars($etape['description'])); ?></div>
                <?php if (!empty($etapes_details[$etape['id']])): ?>
                    <ul class="list-unstyled ms-3">
                        <?php foreach ($etapes_details[$etape['id']] as $detail): ?>
                            <li><span class="fw-bold"><?php echo htmlspecialchars($detail['cle']); ?> :</span> <?php echo htmlspecialchars($detail['valeur']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (!empty($photos_par_etape[$etape['id']])): ?>
                    <div class="row mt-2">
                        <?php foreach ($photos_par_etape[$etape['id']] as $photo): ?>
                            <div class="col-md-3 mb-2">
                                <img src="../uploads/<?php echo htmlspecialchars($photo['chemin']); ?>" class="img-fluid rounded" alt="Photo étape">
                                <div class="small text-muted"><?php echo htmlspecialchars($photo['description']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
    <h2>Photos du guide</h2>
    <div class="row">
        <?php foreach ($photos as $photo): if ($photo['etape_id']) continue; ?>
            <div class="col-md-3 mb-2">
                <img src="../uploads/<?php echo htmlspecialchars($photo['chemin']); ?>" class="img-fluid rounded" alt="Photo guide">
                <div class="small text-muted"><?php echo htmlspecialchars($photo['description']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <h2>Commentaires</h2>
    <?php foreach ($commentaires as $com): ?>
        <div class="border rounded p-2 mb-2">
            <strong><?php echo htmlspecialchars($com['prenom'].' '.$com['nom']); ?></strong> :
            <?php echo nl2br(htmlspecialchars($com['commentaire'])); ?>
            <div class="text-muted small"><?php echo $com['date_commentaire']; ?></div>
        </div>
    <?php endforeach; ?>
    <a href="guides.php" class="btn btn-secondary mt-3">Retour à la liste</a>
</div>
<?php include '../includes/footer.php'; ?>
