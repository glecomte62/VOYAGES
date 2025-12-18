<?php
/**
 * Page de détail d'une destination
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$pdo = getDBConnection();

// Récupérer l'ID de la destination
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: destinations.php');
    exit;
}

// Récupérer la destination
$stmt = $pdo->prepare("
    SELECT 
        d.id, d.code_oaci, d.nom, d.aerodrome, d.ville, d.pays, 
        d.latitude, d.longitude, d.type_piste, d.longueur_piste_m, 
        d.frequence_radio, d.carburant, d.restaurant, d.hebergement,
        d.acces_ulm, d.acces_avion, d.description, d.points_interet, 
        d.photo_principale, d.created_at, d.created_by,
        u.nom as creator_nom, u.prenom as creator_prenom
    FROM destinations d
    LEFT JOIN users u ON d.created_by = u.id
    WHERE d.id = ? AND d.actif = 1
");
$stmt->execute([$id]);
$destination = $stmt->fetch();

if (!$destination) {
    header('Location: destinations.php');
    exit;
}

// Récupérer les photos de la galerie (si la table existe)
$photos = [];
try {
    $stmtPhotos = $pdo->prepare("
        SELECT id, filename, legende 
        FROM destination_photos 
        WHERE destination_id = ? 
        ORDER BY ordre ASC, created_at ASC
    ");
    $stmtPhotos->execute([$id]);
    $photos = $stmtPhotos->fetchAll();
} catch (PDOException $e) {
    // Table destination_photos n'existe pas encore ou autre erreur
    $photos = [];
}

// Récupérer les clubs liés à cette destination
$clubs_lies = [];
try {
    $stmtClubs = $pdo->prepare("
        SELECT c.id, c.nom, c.ville, c.code_oaci, c.telephone, c.email
        FROM clubs c
        INNER JOIN destination_clubs dc ON c.id = dc.club_id
        WHERE dc.destination_id = ?
        ORDER BY c.nom ASC
    ");
    $stmtClubs->execute([$id]);
    $clubs_lies = $stmtClubs->fetchAll();
} catch (PDOException $e) {
    // Table destination_clubs n'existe pas encore
    $clubs_lies = [];
}

// Pour chaque club, récupérer les membres
$clubs_membres = [];
if (!empty($clubs_lies)) {
    foreach ($clubs_lies as $club) {
        try {
            $stmtMembres = $pdo->prepare("
                SELECT u.id, u.nom, u.prenom, u.ville, u.photo, u.telephone, u.email
                FROM users u
                INNER JOIN membres_clubs mc ON u.id = mc.user_id
                WHERE mc.club_id = ? AND u.actif = 1
                ORDER BY u.nom ASC, u.prenom ASC
            ");
            $stmtMembres->execute([$club['id']]);
            $clubs_membres[$club['id']] = $stmtMembres->fetchAll();
        } catch (PDOException $e) {
            // Table membres_clubs n'existe pas encore - initialiser avec tableau vide
            $clubs_membres[$club['id']] = [];
        }
    }
}

// Fonction pour afficher les services
function displayServices($destination) {
    $services = [];
    if ($destination['carburant']) $services[] = '<span class="service-badge">⛽ Carburant</span>';
    if ($destination['restaurant']) $services[] = '<span class="service-badge">🍽️ Restaurant</span>';
    if ($destination['hebergement']) $services[] = '<span class="service-badge">🏨 Hébergement</span>';
    if (empty($services)) {
        $msg = '<span style="color: #94a3b8;">⚠️ Aucun service renseigné</span>';
        if (isLoggedIn()) {
            $msg .= '<br><a href="destination-edit.php?id=' . $_GET['id'] . '" style="color: #0ea5e9; font-size: 0.875rem; font-weight: 600;">Ajouter des services</a>';
        }
        return $msg;
    }
    return implode(' ', $services);
}

// Fonction pour afficher l'accès
function displayAccess($destination) {
    $access = [];
    if ($destination['acces_ulm']) $access[] = '<span class="access-badge ulm">🪂 ULM</span>';
    if ($destination['acces_avion']) $access[] = '<span class="access-badge avion">✈️ Avion</span>';
    if (empty($access)) {
        $msg = '<span style="color: #94a3b8;">⚠️ Accès non renseigné</span>';
        if (isLoggedIn()) {
            $msg .= '<br><a href="destination-edit.php?id=' . $_GET['id'] . '" style="color: #0ea5e9; font-size: 0.875rem; font-weight: 600;">Préciser l\'accès autorisé</a>';
        }
        return $msg;
    }
    return implode(' ', $access);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($destination['nom']); ?> - Voyages ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #0c4a6e;
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: #06b6d4;
            transform: translateX(-4px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #14b8a6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.3);
        }
        
        .destination-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .destination-title {
            display: flex;
            align-items: start;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .destination-title h1 {
            color: #0c4a6e;
            margin: 0;
            font-size: 2rem;
        }
        
        .code-oaci {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 2px;
        }
        
        .destination-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .info-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            border-left: 4px solid #06b6d4;
        }
        
        .info-card h3 {
            color: #475569;
            font-size: 0.875rem;
            margin: 0 0 0.5rem 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-card p {
            color: #0c4a6e;
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 968px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .content-card h2 {
            color: #0c4a6e;
            margin: 0 0 1.5rem 0;
            border-bottom: 2px solid #06b6d4;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        #map {
            height: 500px;
            width: 100%;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        
        .photo-container {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .photo-container img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        
        .no-photo {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }
        
        .service-badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0.25rem;
        }
        
        .access-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0.25rem;
        }
        
        .access-badge.ulm {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .access-badge.avion {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .description-text {
            color: #475569;
            line-height: 1.8;
            font-size: 1rem;
        }
        
        .empty-state {
            color: #94a3b8;
            font-style: italic;
            text-align: center;
            padding: 2rem;
        }
        
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .spec-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid #06b6d4;
        }
        
        .spec-label {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .spec-value {
            color: #0c4a6e;
            font-weight: 600;
            font-size: 1.125rem;
        }
        
        .creator-info {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .creator-info strong {
            color: #92400e;
        }
        
        .full-width-card {
            grid-column: 1 / -1;
        }
        
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .gallery-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        
        .gallery-item .legende {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .lightbox.active {
            display: flex;
        }
        
        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .lightbox-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            color: white;
            font-size: 3rem;
            cursor: pointer;
            background: none;
            border: none;
            line-height: 1;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <a href="destinations.php" class="back-link" style="margin: 0;">
                ⬅️ Retour aux destinations
            </a>
            
            <?php if (isLoggedIn()): ?>
                <a href="destination-edit.php?id=<?php echo $id; ?>" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; text-decoration: none; width: auto;">
                    ✏️ Éditer cette destination
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Header avec titre et infos principales -->
        <div class="destination-header">
            <div class="destination-title">
                <div>
                    <h1 style="font-size: 2.5rem; color: #0c4a6e; margin: 0;">
                        📍 <?php echo h($destination['nom'] ?: $destination['aerodrome'] ?: 'Destination sans nom'); ?>
                    </h1>
                    <p style="color: #64748b; margin: 0.5rem 0 0 0; font-size: 1.125rem;">
                        <?php if ($destination['aerodrome'] && $destination['aerodrome'] !== $destination['nom']): ?>
                            <?php echo h($destination['aerodrome']); ?>
                        <?php endif; ?>
                        <?php if ($destination['ville']): ?>
                            <?php echo $destination['aerodrome'] && $destination['aerodrome'] !== $destination['nom'] ? '•' : ''; ?> 
                            <?php echo h($destination['ville']); ?>
                        <?php endif; ?>
                        <?php if ($destination['pays']): ?>
                            • <?php echo h($destination['pays']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($destination['code_oaci']): ?>
                    <div class="code-oaci">
                        <?php echo h($destination['code_oaci']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="destination-info">
                <div class="info-card">
                    <h3>📍 Coordonnées GPS</h3>
                    <p>
                        <?php if ($destination['latitude'] && $destination['longitude']): ?>
                            <?php echo h($destination['latitude']); ?>, <?php echo h($destination['longitude']); ?>
                        <?php else: ?>
                            <span style="color: #94a3b8;">Non renseignées</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="info-card">
                    <h3>✈️ Accès autorisé</h3>
                    <p><?php echo displayAccess($destination); ?></p>
                </div>
                
                <div class="info-card">
                    <h3>🛠️ Services</h3>
                    <p><?php echo displayServices($destination); ?></p>
                </div>
                
                <?php if ($destination['frequence_radio']): ?>
                <div class="info-card">
                    <h3>📻 Fréquence radio</h3>
                    <p><?php echo h($destination['frequence_radio']); ?> MHz</p>
                </div>
                <?php endif; ?>
                
                <?php if ($destination['type_piste']): ?>
                <div class="info-card">
                    <h3>🛬 Type de piste</h3>
                    <p><?php echo ucfirst(h($destination['type_piste'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($destination['longueur_piste_m']): ?>
                <div class="info-card">
                    <h3>📏 Longueur piste</h3>
                    <p><?php echo h($destination['longueur_piste_m']); ?> mètres</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Grille principale -->
        <div class="main-grid">
            <!-- Colonne gauche : Photo -->
            <div>
                <div class="content-card">
                    <h2>📷 Photo principale</h2>
                    <div class="photo-container">
                        <?php if ($destination['photo_principale'] && !is_array($destination['photo_principale'])): ?>
                            <img src="/uploads/destinations/<?php echo h($destination['photo_principale']); ?>?v=<?php echo time(); ?>" 
                                 alt="<?php echo h($destination['nom']); ?>"
                                 onerror="console.error('Erreur chargement photo:', this.src); this.parentElement.innerHTML='<div class=\"no-photo\">📸</div>';">
                        <?php else: ?>
                            <div class="no-photo">📸</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Colonne droite : Carte -->
            <div>
                <div class="content-card">
                    <h2>🗺️ Localisation</h2>
                    <?php if ($destination['latitude'] && $destination['longitude']): ?>
                        <div id="map"></div>
                    <?php else: ?>
                        <div style="padding: 3rem; text-align: center; background: #f8fafc; border-radius: 12px;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🗺️</div>
                            <p style="color: #64748b;">Coordonnées GPS non renseignées</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Description -->
        <div class="content-card full-width-card" style="margin-bottom: 2rem;">
            <h2>📝 Description</h2>
            <div class="description-text">
                <?php if ($destination['description']): ?>
                    <?php echo nl2br(h($destination['description'])); ?>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; background: #f8fafc; border-radius: 12px; color: #94a3b8;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">✍️</div>
                        Aucune description disponible. 
                        <?php if (isLoggedIn()): ?>
                            <a href="destination-edit.php?id=<?php echo $id; ?>" style="color: #0ea5e9; font-weight: 600;">
                                Ajouter une description
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Points d'intérêt -->
        <div class="content-card full-width-card" style="margin-bottom: 2rem;">
            <h2>🗺️ Points d'intérêt touristiques</h2>
            <div class="description-text">
                <?php if ($destination['points_interet']): ?>
                    <?php echo nl2br(h($destination['points_interet'])); ?>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; background: #f8fafc; border-radius: 12px; color: #94a3b8;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📍</div>
                        Aucun point d'intérêt renseigné. 
                        <?php if (isLoggedIn()): ?>
                            <a href="destination-edit.php?id=<?php echo $id; ?>" style="color: #0ea5e9; font-weight: 600;">
                                Ajouter des points d'intérêt
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Galerie photos supplémentaires -->
        <?php if (!empty($photos)): ?>
        <div class="content-card full-width-card">
            <h2>📷 Galerie photos</h2>
            <div class="photo-gallery">
                <?php foreach ($photos as $photo): ?>
                    <div class="gallery-item" onclick="openLightbox('/uploads/destinations/<?php echo h($photo['filename']); ?>')">
                        <img src="/uploads/destinations/<?php echo h($photo['filename']); ?>" 
                             alt="<?php echo h($photo['legende'] ?? ''); ?>">
                        <?php if ($photo['legende']): ?>
                            <div style="text-align: center; padding: 0.5rem; font-size: 0.875rem; color: #64748b;">
                                <?php echo h($photo['legende']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Clubs liés à cette destination -->
        <div class="content-card full-width-card" style="margin-bottom: 2rem;">
            <h2>🏛️ Clubs associés à ce terrain</h2>
            
            <?php if (!empty($clubs_lies)): ?>
                <div style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">
                    Les clubs suivants utilisent régulièrement ce terrain
                </div>
            
            <?php foreach ($clubs_lies as $club): ?>
                <div style="background: white; border: 2px solid #e0f2fe; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="color: #0c4a6e; margin: 0 0 0.5rem 0; font-size: 1.25rem;">
                                🏛️ <?php echo h($club['nom']); ?>
                            </h3>
                            <div style="color: #64748b; font-size: 0.875rem;">
                                <?php if ($club['ville']): ?>
                                    📍 <?php echo h($club['ville']); ?>
                                <?php endif; ?>
                                <?php if ($club['code_oaci']): ?>
                                    • ✈️ <?php echo h($club['code_oaci']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($clubs_membres[$club['id']])): ?>
                            <div style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;">
                                👥 <?php echo count($clubs_membres[$club['id']]); ?> membre<?php echo count($clubs_membres[$club['id']]) > 1 ? 's' : ''; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($club['telephone'] || $club['email']): ?>
                        <div style="padding: 0.75rem; background: #f8fafc; border-radius: 8px; margin-bottom: 1rem;">
                            <?php if ($club['telephone']): ?>
                                <div style="margin-bottom: 0.25rem; font-size: 0.875rem;">
                                    📞 <?php echo h($club['telephone']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($club['email']): ?>
                                <div style="font-size: 0.875rem;">
                                    ✉️ <a href="mailto:<?php echo h($club['email']); ?>" style="color: #0ea5e9; text-decoration: none;">
                                        <?php echo h($club['email']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($clubs_membres[$club['id']])): ?>
                        <div style="border-top: 2px solid #f1f5f9; padding-top: 1rem;">
                            <div style="color: #64748b; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; text-transform: uppercase;">
                                👥 Membres pilotes sur ce terrain (<?php echo count($clubs_membres[$club['id']]); ?>)
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
                                <?php foreach ($clubs_membres[$club['id']] as $membre): ?>
                                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border: 2px solid #bae6fd; transition: all 0.3s;">
                                        <?php if ($membre['photo']): ?>
                                            <img src="../uploads/users/<?php echo h($membre['photo']); ?>" 
                                                 alt="<?php echo h($membre['prenom'] . ' ' . $membre['nom']); ?>"
                                                 style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #0ea5e9;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #fbbf24 0%, #84cc16 100%); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: white; font-weight: 700; border: 2px solid #84cc16;">
                                                <?php echo strtoupper(substr($membre['prenom'], 0, 1) . substr($membre['nom'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="color: #0c4a6e; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.25rem;">
                                                <?php echo h($membre['prenom'] . ' ' . $membre['nom']); ?>
                                            </div>
                                            <?php if ($membre['ville']): ?>
                                                <div style="color: #64748b; font-size: 0.8rem; margin-bottom: 0.25rem;">
                                                    📍 <?php echo h($membre['ville']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($membre['telephone']): ?>
                                                <div style="color: #0ea5e9; font-size: 0.75rem;">
                                                    📞 <a href="tel:<?php echo h($membre['telephone']); ?>" style="color: inherit; text-decoration: none;"><?php echo h($membre['telephone']); ?></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php else: ?>
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1.5rem; border-radius: 8px;">
                    <div style="color: #92400e; font-weight: 600; margin-bottom: 0.5rem;">
                        ⚠️ Aucun club associé à ce terrain
                    </div>
                    <div style="color: #78350f; font-size: 0.875rem; margin-bottom: 1rem;">
                        Les clubs permettent d'identifier les pilotes qui utilisent régulièrement ce terrain et de créer du lien entre membres.
                    </div>
                    <?php if (isLoggedIn()): ?>
                        <a href="destination-edit.php?id=<?php echo $id; ?>#clubs" 
                           style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(6, 182, 212, 0.3)';"
                           onmouseout="this.style.transform=''; this.style.boxShadow='';">
                            ➕ Associer des clubs à ce terrain
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Info créateur -->
        <?php if ($destination['creator_nom']): ?>
        <div class="creator-info">
            ✏️ Destination ajoutée par <strong><?php echo h($destination['creator_prenom'] . ' ' . $destination['creator_nom']); ?></strong>
            le <?php echo date('d/m/Y', strtotime($destination['created_at'])); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Lightbox pour agrandir les photos -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <img id="lightbox-img" src="" alt="Photo agrandie">
    </div>
    
    <script>
        function openLightbox(src) {
            event.stopPropagation();
            document.getElementById('lightbox').classList.add('active');
            document.getElementById('lightbox-img').src = src;
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Fermer avec Echap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });
        
        // Carte Leaflet
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($destination['latitude'] && $destination['longitude']): ?>
                const lat = <?php echo $destination['latitude']; ?>;
                const lng = <?php echo $destination['longitude']; ?>;
                
                // Initialiser la carte
                const map = L.map('map').setView([lat, lng], 13);
                
                // Tuiles OpenStreetMap
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19
                }).addTo(map);
                
                // Icône personnalisée
                const customIcon = L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });
                
                // Marqueur
                const marker = L.marker([lat, lng], {
                    icon: customIcon
                }).addTo(map);
                
                marker.bindPopup(`
                    <strong><?php echo addslashes($destination['nom']); ?></strong><br>
                    <?php echo addslashes($destination['ville'] ?? ''); ?><br>
                    <small><?php echo $destination['latitude']; ?>, <?php echo $destination['longitude']; ?></small>
                `).openPopup();
            <?php else: ?>
                document.getElementById('map').innerHTML = '<div class="empty-state">📍 Coordonnées GPS non renseignées</div>';
                document.getElementById('map').style.display = 'flex';
                document.getElementById('map').style.alignItems = 'center';
                document.getElementById('map').style.justifyContent = 'center';
            <?php endif; ?>
        });
    </script>
</body>
</html>
