echo 'OKdetail2';
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Détail d'un voyage communautaire
require_once '../config/database.php';
require_once '../includes/functions.php';
$pdo = getDBConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT v.*, u.prenom, u.nom FROM voyages_communautaires v JOIN users u ON v.auteur_id = u.id WHERE v.id = ?");
$stmt->execute([$id]);
$voyage = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$voyage) {
	echo '<div class="container"><h2>Voyage non trouvé</h2></div>';
	exit;
}
// Récupérer les étapes
$stmt = $pdo->prepare("SELECT e.*, d.nom AS destination_nom, d.code_oaci FROM voyages_communautaires_etapes e JOIN destinations d ON e.destination_id = d.id WHERE e.voyage_id = ? ORDER BY e.ordre ASC");
$stmt->execute([$id]);
$etapes = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Récupérer les commentaires
$stmt = $pdo->prepare("SELECT c.*, u.prenom, u.nom FROM voyages_communautaires_commentaires c JOIN users u ON c.auteur_id = u.id WHERE c.voyage_id = ? ORDER BY c.date_commentaire DESC");
$stmt->execute([$id]);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Récupérer les photos
$stmt = $pdo->prepare("SELECT * FROM voyages_communautaires_photos WHERE voyage_id = ? ORDER BY date_ajout DESC");
$stmt->execute([$id]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Récupérer les favoris
$stmt = $pdo->prepare("SELECT COUNT(*) FROM voyages_communautaires_favoris WHERE voyage_id = ?");
$stmt->execute([$id]);
$nb_favoris = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Détail du voyage communautaire</title>
	<link rel="stylesheet" href="../assets/css/style.css">
	<link rel="stylesheet" href="../assets/css/header.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container">
	<h2><?php echo htmlspecialchars($voyage['titre']); ?></h2>
	<p class="auteur">Par <?php echo htmlspecialchars($voyage['prenom'] . ' ' . $voyage['nom']); ?> | Créé le <?php echo date('d/m/Y', strtotime($voyage['date_creation'])); ?></p>
	<?php
	// Gestion utilisateur connecté (fallback si getCurrentUser n'existe pas)
	if (function_exists('getCurrentUser')) {
		$user = getCurrentUser();
	} else {
		$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
	}
	if ($user && isset($user['id']) && ($user['id'] == $voyage['auteur_id'] || (isset($user['role']) && $user['role'] == 'admin'))): ?>
		<div style="margin-bottom:15px;">
			<a href="voyage-communaute-edit.php?id=<?= $voyage['id'] ?>" class="btn-edit">Éditer</a>
			<a href="voyage-communaute-supprime.php?id=<?= $voyage['id'] ?>" class="btn-danger" onclick="return confirm('Supprimer ce voyage ?');">Supprimer</a>
		</div>
	<?php endif; ?>
	<div class="description">
		<?php echo nl2br(htmlspecialchars($voyage['description'])); ?>
	</div>
	<div class="favoris">
		<span><?php echo $nb_favoris; ?> favoris</span>
	</div>
	<h3>Étapes du voyage</h3>
	<ol class="etapes">
		<?php foreach ($etapes as $etape): ?>
			<li>
				<strong><?php echo htmlspecialchars($etape['destination_nom']); ?></strong> (<?php echo htmlspecialchars($etape['code_oaci']); ?>)
				<?php if (!empty($etape['note'])): ?>
					<div class="note">Note : <?php echo nl2br(htmlspecialchars($etape['note'])); ?></div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<h3>Photos</h3>
	<div class="photos">
		<?php foreach ($photos as $photo): ?>
			<img src="../uploads/destinations/<?php echo htmlspecialchars($photo['chemin']); ?>" alt="Photo du voyage" style="max-width:200px; margin:5px;">
		<?php endforeach; ?>
	</div>
	<h3>Commentaires</h3>
	<div class="commentaires">
		<?php foreach ($commentaires as $commentaire): ?>
			<div class="commentaire">
				<span class="auteur">Par <?php echo htmlspecialchars($commentaire['prenom'] . ' ' . $commentaire['nom']); ?> le <?php echo date('d/m/Y H:i', strtotime($commentaire['date_commentaire'])); ?></span>
				<div class="texte"><?php echo nl2br(htmlspecialchars($commentaire['commentaire'])); ?></div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
</body>
</html>
