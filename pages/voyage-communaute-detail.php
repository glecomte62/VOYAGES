<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo 'OK0';
// Détail d'un voyage communautaire
require_once '../config/database.php';
echo 'OK1';
require_once '../includes/functions.php';
echo 'OK2';
$pdo = getDBConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT v.*, u.prenom, u.nom FROM voyages_communautaires v JOIN users u ON v.auteur_id = u.id WHERE v.id = ?");
$stmt->execute([$id]);
$voyage = $stmt->fetch();
if (!$voyage) { echo "<p>Voyage introuvable.</p>"; exit; }
$stmtEtapes = $pdo->prepare("SELECT e.*, d.nom as destination_nom FROM voyages_communautaires_etapes e JOIN destinations d ON e.destination_id = d.id WHERE e.voyage_id = ? ORDER BY e.ordre ASC");
$stmtEtapes->execute([$id]);
$etapes = $stmtEtapes->fetchAll();
<?php
    $nbFavoris = $pdo->prepare("SELECT COUNT(*) FROM voyages_communautaires_favoris WHERE voyage_id = ?");
    $nbFavoris->execute([$voyage['id']]);
    $nbFavoris = $nbFavoris->fetchColumn();
    $isFavori = false;
    if (isset($_SESSION['user_id'])) {
        $stmtFav = $pdo->prepare("SELECT id FROM voyages_communautaires_favoris WHERE voyage_id = ? AND user_id = ?");
        $stmtFav->execute([$voyage['id'], $_SESSION['user_id']]);
        $isFavori = $stmtFav->fetch() ? true : false;
        // Ajout ou suppression du favori
        if (isset($_GET['action']) && $_GET['action'] === 'favori') {
            if (!$isFavori) {
                $pdo->prepare("INSERT INTO voyages_communautaires_favoris (voyage_id, user_id) VALUES (?, ?)")->execute([$voyage['id'], $_SESSION['user_id']]);
            } else {
                $pdo->prepare("DELETE FROM voyages_communautaires_favoris WHERE voyage_id = ? AND user_id = ?")->execute([$voyage['id'], $_SESSION['user_id']]);
            }
            header('Location: voyage-communaute-detail2.php?id=' . $voyage['id']);
            exit;
        }
                .badge-fav {
                    background: #fffbe6;
                    color: #f59e0b;
                    font-weight: 600;
                    border-radius: 8px;
                    padding: 0.3em 0.8em;
                    margin-left: 0.5em;
                }
                .etapes-list {
                    background: #f8fafc;
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin-bottom: 2rem;
                }
                .etape-item {
                    background: white;
                    border-radius: 8px;
                    margin-bottom: 1rem;
                    padding: 1rem 1.5rem;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                }
                .etape-num {
                    background: linear-gradient(135deg, #0ea5e9, #06b6d4);
                    color: white;
                    border-radius: 50%;
                    width: 38px;
                    height: 38px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    font-size: 1.1rem;
                }
                .btn-fav {
                    background: #fffbe6;
                    color: #f59e0b;
                    border: none;
                    border-radius: 8px;
                    padding: 0.4em 1em;
                    font-weight: 600;
                    margin-right: 0.5em;
                    transition: background 0.2s;
                }
                .btn-fav:hover {
                    background: #fef3c7;
                }
                .commentaire-item {
                    background: #f1f5f9;
                    border-radius: 8px;
                    margin-bottom: 1rem;
                    padding: 1rem 1.5rem;
                }
                .commentaire-auteur {
                    color: #0ea5e9;
                    font-weight: 600;
                }
                .commentaire-date {
                    color: #64748b;
                    font-size: 0.9em;
                }
                .btn-primary {
                    background: linear-gradient(135deg, #0ea5e9, #14b8a6);
                    border: none;
                }
                .btn-primary:hover {
                    background: linear-gradient(135deg, #14b8a6, #0ea5e9);
                }
                @media (max-width: 768px) {
                    .voyage-header, .etapes-list, .commentaire-item {
                        padding: 1rem;
                    }
                }
            </style>
        </head>
        <body style="background:#f0f9ff;">
            <?php include '../includes/header.php'; ?>
            <div class="container my-5">
                <div class="voyage-header">
                    <h1 class="mb-2" style="font-size:2.2rem;">🌍 <?= htmlspecialchars($voyage['titre']) ?></h1>
                    <div class="mb-2">
                        <span>par <strong><?= htmlspecialchars($voyage['prenom'].' '.$voyage['nom']) ?></strong></span>
                        <span class="ms-2" style="color:#e0e7ef;">le <?= date('d/m/Y', strtotime($voyage['date_creation'])) ?></span>
                    </div>
                    <div class="mb-3">
                        <?php
                        session_start();
                        $nbFavoris = $pdo->prepare("SELECT COUNT(*) FROM voyages_communautaires_favoris WHERE voyage_id = ?");
                        $nbFavoris->execute([$voyage['id']]);
                        $nbFavoris = $nbFavoris->fetchColumn();
                        $isFavori = false;
                        if (isset($_SESSION['user_id'])) {
                            $stmtFav = $pdo->prepare("SELECT id FROM voyages_communautaires_favoris WHERE voyage_id = ? AND user_id = ?");
                            $stmtFav->execute([$voyage['id'], $_SESSION['user_id']]);
                            $isFavori = $stmtFav->fetch() ? true : false;
                            // Ajout ou suppression du favori
                            if (isset($_GET['action']) && $_GET['action'] === 'favori') {
                                if (!$isFavori) {
                                    $pdo->prepare("INSERT INTO voyages_communautaires_favoris (voyage_id, user_id) VALUES (?, ?)")->execute([$voyage['id'], $_SESSION['user_id']]);
                                } else {
                                    $pdo->prepare("DELETE FROM voyages_communautaires_favoris WHERE voyage_id = ? AND user_id = ?")->execute([$voyage['id'], $_SESSION['user_id']]);
                                }
                                header('Location: voyage-communaute-detail2.php?id=' . $voyage['id']);
                                exit;
                            }
                        }
                        ?>
                        <form method="get" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $voyage['id'] ?>">
                            <input type="hidden" name="action" value="favori">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button type="submit" class="btn-fav">
                                    <?= $isFavori ? '★ Retirer des favoris' : '☆ Ajouter aux favoris' ?>
                                </button>
                            <?php endif; ?>
                            <span class="badge-fav"><?= $nbFavoris ?> favoris</span>
                        </form>
                    </div>
                    <div class="mb-3" style="font-size:1.1rem; color:#e0e7ef;">
                        <?= nl2br(htmlspecialchars($voyage['description'])) ?>
                    </div>
                </div>
                <div class="etapes-list mb-4">
                    <h2 class="mb-3">🛫 Étapes du voyage</h2>
                    <?php foreach ($etapes as $i => $etape): ?>
                        <div class="etape-item">
                            <div class="etape-num"><?= $i+1 ?></div>
                            <div><strong><?= htmlspecialchars($etape['destination_nom']) ?></strong><?php if ($etape['note']): ?> <span style="color:#64748b;">(<?= htmlspecialchars($etape['note']) ?>)</span><?php endif; ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($etapes)): ?><em>Aucune étape pour ce voyage.</em><?php endif; ?>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $voyage['auteur_id']): ?>
                        <a href="voyage-communaute-etapes.php?id=<?= $voyage['id'] ?>" class="btn btn-primary mt-2">✏️ Gérer les étapes</a>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <a href="voyages-communaute.php" class="btn btn-outline-secondary">← Retour à la liste</a>
                </div>
                <div class="mb-5">
                    <h2 class="mb-3">💬 Commentaires</h2>
                    <?php
                    // Affichage des commentaires
                    $stmtCom = $pdo->prepare("SELECT c.*, u.prenom, u.nom FROM voyages_communautaires_commentaires c JOIN users u ON c.auteur_id = u.id WHERE c.voyage_id = ? ORDER BY c.date_commentaire DESC");
                    $stmtCom->execute([$voyage['id']]);
                    $commentaires = $stmtCom->fetchAll();
                    ?>
                    <div>
                        <?php foreach ($commentaires as $com): ?>
                            <div class="commentaire-item">
                                <span class="commentaire-auteur"><?= htmlspecialchars($com['prenom'].' '.$com['nom']) ?></span>
                                <span class="commentaire-date ms-2">le <?= date('d/m/Y H:i', strtotime($com['date_commentaire'])) ?></span><br>
                                <div><?= nl2br(htmlspecialchars($com['commentaire'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($commentaires)): ?>
                            <em>Aucun commentaire pour l'instant.</em>
                        <?php endif; ?>
                    </div>
                    <?php
                    // Ajout d'un commentaire
                    if (isset($_SESSION['user_id'])) {
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commentaire'])) {
                            $texte = trim($_POST['commentaire']);
                            if ($texte) {
                                $stmtAdd = $pdo->prepare("INSERT INTO voyages_communautaires_commentaires (voyage_id, auteur_id, commentaire) VALUES (?, ?, ?)");
                                $stmtAdd->execute([$voyage['id'], $_SESSION['user_id'], $texte]);
                                header('Location: voyage-communaute-detail2.php?id=' . $voyage['id'] . '#commentaires');
                                exit;
                            }
                        }
                    ?>
                    <form method="post" id="commentaires" class="mt-3">
                        <textarea name="commentaire" rows="3" class="form-control mb-2" placeholder="Votre commentaire..."></textarea>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </form>
                    <?php } else { ?>
                        <p><a href="login.php">Connectez-vous</a> pour commenter.</p>
                    <?php } ?>
                </div>
            </div>
        </body>
        </html>
