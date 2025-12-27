<?php
// Page d’édition avancée des étapes d’un voyage communautaire
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pdo = getDBConnection();
// Vérifier droits (auteur ou admin)
$stmt = $pdo->prepare("SELECT * FROM voyages_communautaires WHERE id = ?");
$stmt->execute([$id]);
$voyage = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$voyage) { echo '<div class=\"container\"><h2>Voyage non trouvé</h2></div>'; exit; }
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['user_role'] ?? null
    ];
}
if (!$user || ($user['id'] != $voyage['auteur_id'] && $user['role'] != 'admin')) { echo '<div class=\"container\"><h2>Accès refusé</h2></div>'; exit; }
// Récupérer les étapes existantes
$stmt = $pdo->prepare("SELECT e.*, d.nom AS destination_nom, d.code_oaci, d.latitude, d.longitude FROM voyages_communautaires_etapes e JOIN destinations d ON e.destination_id = d.id WHERE e.voyage_id = ? ORDER BY e.ordre ASC");
$stmt->execute([$id]);
$etapes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer les étapes du voyage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { background: #f0f9ff; }
        .etapes-container { max-width: 1200px; margin: 2rem auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2.5rem; }
        .etapes-header { text-align: center; margin-bottom: 2rem; }
        .etapes-list { margin-bottom: 2rem; }
        .etape-card { background: #f8fafc; border-radius: 10px; padding: 1rem 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .etape-card strong { color: #0ea5e9; }
        .etape-pois { margin-top: 0.5rem; font-size: 0.98em; color: #475569; }
        .leaflet-container { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .add-etape-form { margin-bottom: 2rem; background: #f1f5f9; border-radius: 10px; padding: 1.5rem; }
        .add-etape-form label { font-weight: 600; color: #0ea5e9; display: block; margin-bottom: 0.3rem; }
        .add-etape-form input, .add-etape-form textarea, .add-etape-form select { width: 100%; padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 1rem; background: #fff; }
        .add-etape-form .form-row { margin-bottom: 1rem; }
        .add-etape-form .form-actions { display: flex; gap: 1rem; justify-content: flex-end; }
        .btn-primary { background: linear-gradient(135deg, #0ea5e9, #14b8a6); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3); }
    </style>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="etapes-container">
    <div class="etapes-header">
        <h2>Éditer les étapes du voyage</h2>
        <p><?= htmlspecialchars($voyage['titre']) ?></p>
    </div>
    <form class="add-etape-form">
        <div class="form-row">
            <label for="destination">Destination (OACI ou nom)</label>
            <input type="text" id="destination" name="destination" placeholder="Code OACI ou nom de l’aérodrome">
        </div>
        <div class="form-row">
            <label for="note">Note sur l’étape</label>
            <textarea id="note" name="note" placeholder="Remarques, conseils, etc."></textarea>
        </div>
        <div class="form-row">
            <label>Où manger</label>
            <input type="text" name="manger" placeholder="Nom, adresse, infos…">
        </div>
        <div class="form-row">
            <label>Où dormir</label>
            <input type="text" name="dormir" placeholder="Nom, adresse, infos…">
        </div>
        <div class="form-row">
            <label>Où loger la machine</label>
            <input type="text" name="loger" placeholder="Hangar, parking, contact…">
        </div>
        <div class="form-row">
            <label>Carburant</label>
            <input type="text" name="carburant" placeholder="Type, station, contact…">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Ajouter l’étape</button>
        </div>
    </form>
    <div class="etapes-list">
        <?php foreach ($etapes as $etape): ?>
            <div class="etape-card">
                <strong><?= htmlspecialchars($etape['destination_nom']) ?> (<?= htmlspecialchars($etape['code_oaci']) ?>)</strong>
                <div>Note : <?= nl2br(htmlspecialchars($etape['note'])) ?></div>
                <div class="etape-pois">
                    🍽️ <?= htmlspecialchars($etape['manger'] ?? '') ?> | 🛏️ <?= htmlspecialchars($etape['dormir'] ?? '') ?> | 🛩️ <?= htmlspecialchars($etape['loger'] ?? '') ?> | ⛽ <?= htmlspecialchars($etape['carburant'] ?? '') ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="map" style="height: 400px; margin-top:2rem;"></div>
</div>
<script>
    // Affichage de la carte et des étapes existantes
    var map = L.map('map').setView([47, 2], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap'
    }).addTo(map);
    <?php foreach ($etapes as $etape): if ($etape['latitude'] && $etape['longitude']): ?>
        L.marker([<?= $etape['latitude'] ?>, <?= $etape['longitude'] ?>]).addTo(map)
            .bindPopup("<strong><?= htmlspecialchars($etape['destination_nom']) ?></strong><br><?= htmlspecialchars($etape['code_oaci']) ?>");
    <?php endif; endforeach; ?>
</script>
</body>
</html>
<?php
// Ajout d'étapes à un voyage communautaire
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$pdo = getDBConnection();
$voyage_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Vérifier que l'utilisateur est bien l'auteur
$stmt = $pdo->prepare("SELECT * FROM voyages_communautaires WHERE id = ? AND auteur_id = ?");
$stmt->execute([$voyage_id, $_SESSION['user_id']]);
$voyage = $stmt->fetch();
if (!$voyage) { echo "<p>Accès refusé.</p>"; exit; }
// Récupérer les destinations
$destinations = $pdo->query("SELECT id, nom FROM destinations ORDER BY nom ASC")->fetchAll();
// Ajout d'une étape
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destination_id'])) {
    $ordre = intval($_POST['ordre'] ?? 1);
    $destination_id = intval($_POST['destination_id']);
    $note = trim($_POST['note'] ?? '');
    $stmt = $pdo->prepare("INSERT INTO voyages_communautaires_etapes (voyage_id, ordre, destination_id, note) VALUES (?, ?, ?, ?)");
    $stmt->execute([$voyage_id, $ordre, $destination_id, $note]);
    header('Location: voyage-communaute-etapes.php?id=' . $voyage_id);
    exit;
}
// Liste des étapes existantes
$stmt = $pdo->prepare("SELECT e.*, d.nom as destination_nom FROM voyages_communautaires_etapes e JOIN destinations d ON e.destination_id = d.id WHERE e.voyage_id = ? ORDER BY e.ordre ASC");
$stmt->execute([$voyage_id]);
$etapes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étapes du voyage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h1>Ajouter des étapes à "<?= htmlspecialchars($voyage['titre']) ?>"</h1>
    <form method="post">
        <label>Ordre<br><input type="number" name="ordre" min="1" value="<?= count($etapes)+1 ?>" required></label><br>
        <label>Destination<br>
            <select name="destination_id" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($destinations as $dest): ?>
                    <option value="<?= $dest['id'] ?>"><?= htmlspecialchars($dest['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Note (optionnel)<br><input type="text" name="note"></label><br>
        <button type="submit">Ajouter l'étape</button>
    </form>
    <h2>Étapes existantes</h2>
    <ol>
        <?php foreach ($etapes as $etape): ?>
            <li><?= htmlspecialchars($etape['destination_nom']) ?><?php if ($etape['note']): ?> <em>(<?= htmlspecialchars($etape['note']) ?>)</em><?php endif; ?></li>
        <?php endforeach; ?>
    </ol>
    <a href="voyage-communaute-detail2.php?id=<?= $voyage_id ?>">← Retour au voyage</a>
</body>
</html>
