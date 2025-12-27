<?php
// pages/guides.php
// Liste des guides de voyages
require_once '../config/config.php';
require_once '../includes/functions.php';

$sql = "SELECT g.*, u.nom, u.prenom FROM guides_de_voyages g JOIN users u ON g.auteur_id = u.id WHERE is_published = 1 ORDER BY date_creation DESC";
$guides = requete_liste($sql);

include '../includes/header.php';
?>
<div class="container">
    <h1>Guides de voyages</h1>
    <a href="guides-add.php" class="btn btn-primary">Créer un nouveau guide</a>
    <div class="row mt-4">
        <?php foreach ($guides as $guide): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($guide['titre']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">Par <?php echo htmlspecialchars($guide['prenom'].' '.$guide['nom']); ?></h6>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($guide['description'])); ?></p>
                        <a href="guides-detail.php?id=<?php echo $guide['id']; ?>" class="btn btn-outline-primary">Voir le guide</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
