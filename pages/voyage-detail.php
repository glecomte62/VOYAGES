<?php
/**
 * VOYAGES - Détails d'un voyage avec gestion complète
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

$pdo = getDBConnection();
$voyage_id = intval($_GET['id'] ?? 0);

// Vérifier que le voyage existe et appartient à l'utilisateur (ou est public)
$stmt = $pdo->prepare("SELECT v.*, u.prenom, u.nom FROM voyages v JOIN users u ON v.user_id = u.id WHERE v.id = ? AND (v.user_id = ? OR v.public = 1)");
$stmt->execute([$voyage_id, $_SESSION['user_id']]);
$voyage = $stmt->fetch();

if (!$voyage) {
    header("Location: voyages.php");
    exit;
}

$is_owner = ($voyage['user_id'] == $_SESSION['user_id']);

// Récupérer toutes les étapes avec leurs détails
$stmt = $pdo->prepare("SELECT * FROM voyage_etapes WHERE voyage_id = ? ORDER BY ordre ASC");
$stmt->execute([$voyage_id]);
$etapes = $stmt->fetchAll();

// Pour chaque étape, récupérer les hébergements, ravitaillements et visites
foreach ($etapes as &$etape) {
    // Hébergements
    $stmt = $pdo->prepare("SELECT * FROM voyage_hebergements WHERE etape_id = ? ORDER BY date_checkin ASC");
    $stmt->execute([$etape['id']]);
    $etape['hebergements'] = $stmt->fetchAll();
    
    // Ravitaillements essence
    $stmt = $pdo->prepare("SELECT * FROM voyage_ravitaillements_essence WHERE etape_id = ? ORDER BY date_ravitaillement ASC");
    $stmt->execute([$etape['id']]);
    $etape['ravitaillements_essence'] = $stmt->fetchAll();
    
    // Ravitaillements vivres
    $stmt = $pdo->prepare("SELECT * FROM voyage_ravitaillements_vivres WHERE etape_id = ? ORDER BY date_visite ASC");
    $stmt->execute([$etape['id']]);
    $etape['ravitaillements_vivres'] = $stmt->fetchAll();
    
    // Visites
    $stmt = $pdo->prepare("SELECT * FROM voyage_visites WHERE etape_id = ? ORDER BY date_visite ASC");
    $stmt->execute([$etape['id']]);
    $etape['visites'] = $stmt->fetchAll();
}

// Traitement AJAX pour ajout/modification/suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $is_owner) {
    header('Content-Type: application/json');
    
    try {
        // HÉBERGEMENTS
        if ($_POST['action'] === 'add_hebergement') {
            $stmt = $pdo->prepare("
                INSERT INTO voyage_hebergements (
                    etape_id, voyage_id, type, nom, adresse, telephone, email, site_web,
                    date_checkin, date_checkout, nombre_nuits, prix_total, devise,
                    reserve, numero_reservation, note, commentaire
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $nombre_nuits = (strtotime($_POST['date_checkout']) - strtotime($_POST['date_checkin'])) / 86400;
            
            $stmt->execute([
                $_POST['etape_id'],
                $voyage_id,
                $_POST['type'],
                $_POST['nom'],
                $_POST['adresse'] ?? null,
                $_POST['telephone'] ?? null,
                $_POST['email'] ?? null,
                $_POST['site_web'] ?? null,
                $_POST['date_checkin'],
                $_POST['date_checkout'],
                $nombre_nuits,
                $_POST['prix_total'] ?? null,
                $_POST['devise'] ?? 'EUR',
                isset($_POST['reserve']) ? 1 : 0,
                $_POST['numero_reservation'] ?? null,
                $_POST['note'] ?? null,
                $_POST['commentaire'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            exit;
        }
        
        // RAVITAILLEMENTS ESSENCE
        if ($_POST['action'] === 'add_ravitaillement_essence') {
            $stmt = $pdo->prepare("
                INSERT INTO voyage_ravitaillements_essence (
                    etape_id, voyage_id, type_carburant, quantite, prix_litre, prix_total, devise,
                    lieu, disponible_terrain, fournisseur, date_ravitaillement,
                    horaires_ouverture, self_service, carte_acceptee, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['etape_id'],
                $voyage_id,
                $_POST['type_carburant'],
                $_POST['quantite'] ?? null,
                $_POST['prix_litre'] ?? null,
                $_POST['prix_total'] ?? null,
                $_POST['devise'] ?? 'EUR',
                $_POST['lieu'] ?? null,
                isset($_POST['disponible_terrain']) ? 1 : 0,
                $_POST['fournisseur'] ?? null,
                $_POST['date_ravitaillement'] ?? null,
                $_POST['horaires_ouverture'] ?? null,
                isset($_POST['self_service']) ? 1 : 0,
                isset($_POST['carte_acceptee']) ? 1 : 0,
                $_POST['notes'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            exit;
        }
        
        // RAVITAILLEMENTS VIVRES
        if ($_POST['action'] === 'add_ravitaillement_vivres') {
            $stmt = $pdo->prepare("
                INSERT INTO voyage_ravitaillements_vivres (
                    etape_id, voyage_id, type, nom, adresse, telephone,
                    date_visite, horaires, prix_total, devise,
                    note, specialite, commentaire, distance_terrain, sur_terrain
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['etape_id'],
                $voyage_id,
                $_POST['type'],
                $_POST['nom'],
                $_POST['adresse'] ?? null,
                $_POST['telephone'] ?? null,
                $_POST['date_visite'] ?? null,
                $_POST['horaires'] ?? null,
                $_POST['prix_total'] ?? null,
                $_POST['devise'] ?? 'EUR',
                $_POST['note'] ?? null,
                $_POST['specialite'] ?? null,
                $_POST['commentaire'] ?? null,
                $_POST['distance_terrain'] ?? null,
                isset($_POST['sur_terrain']) ? 1 : 0
            ]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            exit;
        }
        
        // VISITES CULTURELLES
        if ($_POST['action'] === 'add_visite') {
            $stmt = $pdo->prepare("
                INSERT INTO voyage_visites (
                    etape_id, voyage_id, type, nom, description, adresse,
                    latitude, longitude, distance_terrain,
                    date_visite, heure_debut, heure_fin, duree_visite, horaires_ouverture,
                    prix_adulte, prix_enfant, prix_total, devise, gratuit,
                    reservation_requise, numero_reservation, site_web, telephone, email,
                    note, commentaire, recommande
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['etape_id'],
                $voyage_id,
                $_POST['type'],
                $_POST['nom'],
                $_POST['description'] ?? null,
                $_POST['adresse'] ?? null,
                $_POST['latitude'] ?? null,
                $_POST['longitude'] ?? null,
                $_POST['distance_terrain'] ?? null,
                $_POST['date_visite'] ?? null,
                $_POST['heure_debut'] ?? null,
                $_POST['heure_fin'] ?? null,
                $_POST['duree_visite'] ?? null,
                $_POST['horaires_ouverture'] ?? null,
                $_POST['prix_adulte'] ?? null,
                $_POST['prix_enfant'] ?? null,
                $_POST['prix_total'] ?? null,
                $_POST['devise'] ?? 'EUR',
                isset($_POST['gratuit']) ? 1 : 0,
                isset($_POST['reservation_requise']) ? 1 : 0,
                $_POST['numero_reservation'] ?? null,
                $_POST['site_web'] ?? null,
                $_POST['telephone'] ?? null,
                $_POST['email'] ?? null,
                $_POST['note'] ?? null,
                $_POST['commentaire'] ?? null,
                isset($_POST['recommande']) ? 1 : 0
            ]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Fonction helper pour les icônes de type
function getTypeIcon($type) {
    $icons = [
        // Hébergements
        'hotel' => '🏨',
        'camping' => '⛺',
        'gite' => '🏡',
        'chambre_hote' => '🏠',
        'bivouac' => '🏕️',
        // Carburants
        'avgas_100ll' => '⛽',
        'mogas_95' => '⛽',
        'mogas_98' => '⛽',
        'ul91' => '⛽',
        'jet_a1' => '⛽',
        // Vivres
        'restaurant' => '🍽️',
        'supermarche' => '🛒',
        'marche' => '🥗',
        'boulangerie' => '🥖',
        'bar' => '🍺',
        // Visites
        'monument' => '🏛️',
        'musee' => '🖼️',
        'site_naturel' => '🏞️',
        'ville' => '🏙️',
        'evenement' => '🎭',
        'activite' => '🎯',
    ];
    return $icons[$type] ?? '📍';
}

function getTypeLabel($type) {
    $labels = [
        // Hébergements
        'hotel' => 'Hôtel',
        'camping' => 'Camping',
        'gite' => 'Gîte',
        'chambre_hote' => 'Chambre d\'hôtes',
        'bivouac' => 'Bivouac',
        'autre' => 'Autre',
        // Carburants
        'avgas_100ll' => 'AVGAS 100LL',
        'mogas_95' => 'MOGAS 95',
        'mogas_98' => 'MOGAS 98',
        'ul91' => 'UL91',
        'jet_a1' => 'JET A1',
        // Vivres
        'restaurant' => 'Restaurant',
        'supermarche' => 'Supermarché',
        'marche' => 'Marché',
        'boulangerie' => 'Boulangerie',
        'bar' => 'Bar/Café',
        // Visites
        'monument' => 'Monument',
        'musee' => 'Musée',
        'site_naturel' => 'Site naturel',
        'ville' => 'Ville',
        'evenement' => 'Événement',
        'activite' => 'Activité',
    ];
    return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($voyage['titre']); ?> - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .voyage-detail-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .voyage-header {
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
            padding: 3rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.3);
        }
        
        .voyage-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .voyage-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            font-size: 1.1rem;
        }
        
        .voyage-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .voyage-description {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .etape-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .etape-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .etape-number {
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .etape-title {
            flex: 1;
        }
        
        .etape-title h2 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }
        
        .etape-title .etape-code {
            color: #0ea5e9;
            font-weight: 600;
        }
        
        .etape-dates {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .etape-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .content-section {
            margin-bottom: 1.5rem;
        }
        
        .content-section h3 {
            font-size: 1.125rem;
            color: #0f172a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .add-button {
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            color: #475569;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .add-button:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
        }
        
        .item-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .item-title {
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .item-type {
            font-size: 0.75rem;
            color: #64748b;
            background: white;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
        }
        
        .item-details {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.6;
        }
        
        .item-price {
            font-weight: 600;
            color: #059669;
            margin-top: 0.5rem;
        }
        
        .item-rating {
            color: #f59e0b;
        }
        
        .empty-state {
            text-align: center;
            color: #94a3b8;
            padding: 2rem;
            font-style: italic;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            font-size: 1.5rem;
            color: #0f172a;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input {
            width: auto;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <main class="voyage-detail-container">
        <div class="voyage-header">
            <h1>✈️ <?php echo h($voyage['titre']); ?></h1>
            <div class="voyage-meta">
                <div class="voyage-meta-item">📅 <?php echo formatDate($voyage['date_debut']); ?> → <?php echo formatDate($voyage['date_fin']); ?></div>
                <?php if ($voyage['aeronef']): ?>
                    <div class="voyage-meta-item">✈️ <?php echo h($voyage['aeronef']); ?></div>
                <?php endif; ?>
                <div class="voyage-meta-item">👤 Par <?php echo h($voyage['prenom'] . ' ' . $voyage['nom']); ?></div>
                <div class="voyage-meta-item">📍 <?php echo count($etapes); ?> étape(s)</div>
            </div>
        </div>
        
        <?php if ($voyage['description']): ?>
            <div class="voyage-description">
                <p><?php echo nl2br(h($voyage['description'])); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (empty($etapes)): ?>
            <div class="empty-state">
                <p>Aucune étape définie pour ce voyage.</p>
                <?php if ($is_owner): ?>
                    <a href="voyage-planner.php?id=<?php echo $voyage_id; ?>" class="add-button" style="margin-top: 1rem;">
                        + Planifier l'itinéraire
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($etapes as $etape): ?>
                <div class="etape-card" id="etape-<?php echo $etape['id']; ?>">
                    <div class="etape-card-header">
                        <div class="etape-number"><?php echo $etape['ordre']; ?></div>
                        <div class="etape-title">
                            <h2>
                                <?php echo h($etape['terrain_nom']); ?>
                                <?php if ($etape['terrain_code_oaci']): ?>
                                    <span class="etape-code">(<?php echo h($etape['terrain_code_oaci']); ?>)</span>
                                <?php endif; ?>
                            </h2>
                            <?php if ($etape['date_arrivee'] || $etape['date_depart']): ?>
                                <div class="etape-dates">
                                    <?php if ($etape['date_arrivee']): ?>
                                        Arrivée: <?php echo date('d/m/Y à H:i', strtotime($etape['date_arrivee'])); ?>
                                    <?php endif; ?>
                                    <?php if ($etape['date_depart']): ?>
                                        | Départ: <?php echo date('d/m/Y à H:i', strtotime($etape['date_depart'])); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($etape['notes']): ?>
                        <div style="background: #fff7ed; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #9a3412;">
                            📝 <?php echo nl2br(h($etape['notes'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="etape-content">
                        <!-- HÉBERGEMENTS -->
                        <div class="content-section">
                            <h3>🏨 Hébergements</h3>
                            <?php if (empty($etape['hebergements'])): ?>
                                <p class="empty-state">Aucun hébergement ajouté</p>
                            <?php else: ?>
                                <?php foreach ($etape['hebergements'] as $hebergement): ?>
                                    <div class="item-card">
                                        <div class="item-header">
                                            <div class="item-title">
                                                <?php echo getTypeIcon($hebergement['type']); ?>
                                                <?php echo h($hebergement['nom']); ?>
                                            </div>
                                            <div class="item-type"><?php echo getTypeLabel($hebergement['type']); ?></div>
                                        </div>
                                        <div class="item-details">
                                            <?php if ($hebergement['adresse']): ?>
                                                📍 <?php echo h($hebergement['adresse']); ?><br>
                                            <?php endif; ?>
                                            🗓️ <?php echo date('d/m/Y', strtotime($hebergement['date_checkin'])); ?> → 
                                            <?php echo date('d/m/Y', strtotime($hebergement['date_checkout'])); ?>
                                            (<?php echo $hebergement['nombre_nuits']; ?> nuit<?php echo $hebergement['nombre_nuits'] > 1 ? 's' : ''; ?>)<br>
                                            <?php if ($hebergement['telephone']): ?>
                                                📞 <?php echo h($hebergement['telephone']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($hebergement['reserve']): ?>
                                                ✅ Réservé<?php echo $hebergement['numero_reservation'] ? ' (Nº ' . h($hebergement['numero_reservation']) . ')' : ''; ?><br>
                                            <?php endif; ?>
                                            <?php if ($hebergement['prix_total']): ?>
                                                <div class="item-price">💰 <?php echo number_format($hebergement['prix_total'], 2); ?> €</div>
                                            <?php endif; ?>
                                            <?php if ($hebergement['note']): ?>
                                                <div class="item-rating">⭐ <?php echo str_repeat('★', $hebergement['note']) . str_repeat('☆', 5 - $hebergement['note']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($hebergement['commentaire']): ?>
                                                <div style="margin-top: 0.5rem; font-style: italic;"><?php echo nl2br(h($hebergement['commentaire'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($is_owner): ?>
                                <button class="add-button" onclick="openModal('hebergement', <?php echo $etape['id']; ?>)">
                                    + Ajouter un hébergement
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- RAVITAILLEMENTS ESSENCE -->
                        <div class="content-section">
                            <h3>⛽ Ravitaillements essence</h3>
                            <?php if (empty($etape['ravitaillements_essence'])): ?>
                                <p class="empty-state">Aucun ravitaillement essence</p>
                            <?php else: ?>
                                <?php foreach ($etape['ravitaillements_essence'] as $ravit): ?>
                                    <div class="item-card">
                                        <div class="item-header">
                                            <div class="item-title">
                                                ⛽ <?php echo getTypeLabel($ravit['type_carburant']); ?>
                                            </div>
                                        </div>
                                        <div class="item-details">
                                            <?php if ($ravit['lieu']): ?>
                                                📍 <?php echo h($ravit['lieu']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($ravit['quantite']): ?>
                                                📊 <?php echo number_format($ravit['quantite'], 2); ?> L<br>
                                            <?php endif; ?>
                                            <?php if ($ravit['prix_litre']): ?>
                                                💶 <?php echo number_format($ravit['prix_litre'], 3); ?> €/L<br>
                                            <?php endif; ?>
                                            <?php if ($ravit['disponible_terrain']): ?>
                                                ✅ Disponible sur le terrain<br>
                                            <?php endif; ?>
                                            <?php if ($ravit['self_service']): ?>
                                                🔑 Self-service<br>
                                            <?php endif; ?>
                                            <?php if ($ravit['prix_total']): ?>
                                                <div class="item-price">💰 Total: <?php echo number_format($ravit['prix_total'], 2); ?> €</div>
                                            <?php endif; ?>
                                            <?php if ($ravit['notes']): ?>
                                                <div style="margin-top: 0.5rem; font-style: italic;"><?php echo nl2br(h($ravit['notes'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($is_owner): ?>
                                <button class="add-button" onclick="openModal('essence', <?php echo $etape['id']; ?>)">
                                    + Ajouter ravitaillement essence
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- RAVITAILLEMENTS VIVRES -->
                        <div class="content-section">
                            <h3>🍽️ Ravitaillements vivres</h3>
                            <?php if (empty($etape['ravitaillements_vivres'])): ?>
                                <p class="empty-state">Aucun ravitaillement vivres</p>
                            <?php else: ?>
                                <?php foreach ($etape['ravitaillements_vivres'] as $vivres): ?>
                                    <div class="item-card">
                                        <div class="item-header">
                                            <div class="item-title">
                                                <?php echo getTypeIcon($vivres['type']); ?>
                                                <?php echo h($vivres['nom']); ?>
                                            </div>
                                            <div class="item-type"><?php echo getTypeLabel($vivres['type']); ?></div>
                                        </div>
                                        <div class="item-details">
                                            <?php if ($vivres['adresse']): ?>
                                                📍 <?php echo h($vivres['adresse']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($vivres['distance_terrain']): ?>
                                                📏 À <?php echo number_format($vivres['distance_terrain'], 1); ?> km du terrain<br>
                                            <?php endif; ?>
                                            <?php if ($vivres['sur_terrain']): ?>
                                                ✅ Sur le terrain<br>
                                            <?php endif; ?>
                                            <?php if ($vivres['specialite']): ?>
                                                🌟 Spécialité: <?php echo h($vivres['specialite']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($vivres['prix_total']): ?>
                                                <div class="item-price">💰 <?php echo number_format($vivres['prix_total'], 2); ?> €</div>
                                            <?php endif; ?>
                                            <?php if ($vivres['note']): ?>
                                                <div class="item-rating">⭐ <?php echo str_repeat('★', $vivres['note']) . str_repeat('☆', 5 - $vivres['note']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($vivres['commentaire']): ?>
                                                <div style="margin-top: 0.5rem; font-style: italic;"><?php echo nl2br(h($vivres['commentaire'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($is_owner): ?>
                                <button class="add-button" onclick="openModal('vivres', <?php echo $etape['id']; ?>)">
                                    + Ajouter ravitaillement vivres
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- VISITES CULTURELLES -->
                        <div class="content-section">
                            <h3>🏛️ Visites culturelles</h3>
                            <?php if (empty($etape['visites'])): ?>
                                <p class="empty-state">Aucune visite prévue</p>
                            <?php else: ?>
                                <?php foreach ($etape['visites'] as $visite): ?>
                                    <div class="item-card">
                                        <div class="item-header">
                                            <div class="item-title">
                                                <?php echo getTypeIcon($visite['type']); ?>
                                                <?php echo h($visite['nom']); ?>
                                            </div>
                                            <div class="item-type"><?php echo getTypeLabel($visite['type']); ?></div>
                                        </div>
                                        <div class="item-details">
                                            <?php if ($visite['description']): ?>
                                                <div style="margin-bottom: 0.5rem;"><?php echo nl2br(h($visite['description'])); ?></div>
                                            <?php endif; ?>
                                            <?php if ($visite['adresse']): ?>
                                                📍 <?php echo h($visite['adresse']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($visite['distance_terrain']): ?>
                                                📏 À <?php echo number_format($visite['distance_terrain'], 1); ?> km du terrain<br>
                                            <?php endif; ?>
                                            <?php if ($visite['date_visite']): ?>
                                                🗓️ <?php echo date('d/m/Y', strtotime($visite['date_visite'])); ?>
                                                <?php if ($visite['heure_debut']): ?>
                                                    à <?php echo date('H:i', strtotime($visite['heure_debut'])); ?>
                                                <?php endif; ?>
                                                <br>
                                            <?php endif; ?>
                                            <?php if ($visite['duree_visite']): ?>
                                                ⏱️ Durée: <?php echo $visite['duree_visite']; ?> min<br>
                                            <?php endif; ?>
                                            <?php if ($visite['gratuit']): ?>
                                                🎁 Gratuit<br>
                                            <?php elseif ($visite['prix_total']): ?>
                                                <div class="item-price">💰 <?php echo number_format($visite['prix_total'], 2); ?> €</div>
                                            <?php endif; ?>
                                            <?php if ($visite['reservation_requise']): ?>
                                                ⚠️ Réservation requise<br>
                                            <?php endif; ?>
                                            <?php if ($visite['note']): ?>
                                                <div class="item-rating">⭐ <?php echo str_repeat('★', $visite['note']) . str_repeat('☆', 5 - $visite['note']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($visite['commentaire']): ?>
                                                <div style="margin-top: 0.5rem; font-style: italic;"><?php echo nl2br(h($visite['commentaire'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($is_owner): ?>
                                <button class="add-button" onclick="openModal('visite', <?php echo $etape['id']; ?>)">
                                    + Ajouter une visite
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <?php if ($is_owner): ?>
                <a href="voyage-planner.php?id=<?php echo $voyage_id; ?>" class="btn btn-primary">Modifier l'itinéraire</a>
            <?php endif; ?>
            <a href="voyages.php" class="btn btn-secondary">Retour aux voyages</a>
        </div>
    </main>
    
    <!-- Modals pour ajouter des éléments -->
    <?php if ($is_owner): ?>
        <?php include 'voyage-modals.php'; ?>
    <?php endif; ?>
    
    <script src="../assets/js/voyage-detail.js"></script>
</body>
</html>
