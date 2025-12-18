<?php
/**
 * Page d'édition de destination (collaborative)
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$id = $_GET['id'] ?? null;
$message = '';

if (!$id) {
    header('Location: destinations.php');
    exit;
}

// Récupérer la destination
$stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
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
    $photosResult = $stmtPhotos;
} catch (PDOException $e) {
    // Table destination_photos n'existe pas encore
    $photosResult = null;
}

// Récupérer tous les clubs disponibles
$allClubs = [];
try {
    $stmtAllClubs = $pdo->query("SELECT id, nom, ville, code_oaci FROM clubs WHERE actif = 1 ORDER BY nom ASC");
    $allClubs = $stmtAllClubs->fetchAll();
} catch (PDOException $e) {
    $allClubs = [];
}

// Récupérer les clubs déjà liés à cette destination
$linkedClubIds = [];
try {
    $stmtLinked = $pdo->prepare("SELECT club_id FROM destination_clubs WHERE destination_id = ?");
    $stmtLinked->execute([$id]);
    $linkedClubIds = $stmtLinked->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $linkedClubIds = [];
}

// Gestion de la mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Si on ne modifie que les clubs (pas le formulaire principal)
        $onlyClubsUpdate = !isset($_POST['nom']) && !isset($_POST['aerodrome']) && isset($_POST['clubs']);
        
        // Validation des champs obligatoires (sauf si on met à jour seulement les clubs)
        if (!$onlyClubsUpdate && (empty($_POST['nom']) || empty($_POST['aerodrome']))) {
            throw new Exception('Le nom et l\'aérodrome sont obligatoires');
        }
        
        // Upload de la photo si présente
        $photo_filename = $destination['photo_principale'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadPhoto($_FILES['photo'], '../uploads/destinations/');
            if ($uploadResult['success']) {
                // Supprimer l'ancienne photo si elle existe
                if ($photo_filename && file_exists('../uploads/destinations/' . $photo_filename)) {
                    unlink('../uploads/destinations/' . $photo_filename);
                }
                $photo_filename = $uploadResult['filename'];
            }
        }
        
        // Préparer les données
        $code_oaci = !empty($_POST['code_oaci']) ? $_POST['code_oaci'] : null;
        $nom = trim($_POST['nom']);
        $aerodrome = trim($_POST['aerodrome']);
        $ville = trim($_POST['ville'] ?? '');
        $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
        $type_piste = !empty($_POST['type_piste']) ? $_POST['type_piste'] : null;
        $longueur_piste_m = !empty($_POST['longueur_piste_m']) ? (int)$_POST['longueur_piste_m'] : null;
        $frequence_radio = !empty($_POST['frequence_radio']) ? $_POST['frequence_radio'] : null;
        $carburant = isset($_POST['carburant']) ? 1 : 0;
        $restaurant = isset($_POST['restaurant']) ? 1 : 0;
        $hebergement = isset($_POST['hebergement']) ? 1 : 0;
        $acces_ulm = isset($_POST['acces_ulm']) ? 1 : 0;
        $acces_avion = isset($_POST['acces_avion']) ? 1 : 0;
        $description = trim($_POST['description'] ?? '');
        $points_interet = trim($_POST['points_interet'] ?? '');
        
        // Mise à jour dans la base (sauf si on ne modifie que les clubs)
        if (!$onlyClubsUpdate) {
            $sql = "UPDATE destinations SET
                code_oaci = ?,
                nom = ?,
                aerodrome = ?,
                ville = ?,
                latitude = ?,
                longitude = ?,
                type_piste = ?,
                longueur_piste_m = ?,
                frequence_radio = ?,
                carburant = ?,
                restaurant = ?,
                hebergement = ?,
                acces_ulm = ?,
                acces_avion = ?,
                description = ?,
                points_interet = ?,
                photo_principale = ?,
                updated_at = NOW()
            WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $code_oaci, $nom, $aerodrome, $ville, $latitude, $longitude,
                $type_piste, $longueur_piste_m, $frequence_radio,
                $carburant, $restaurant, $hebergement,
                $acces_ulm, $acces_avion,
                $description, $points_interet, $photo_filename,
                $id
            ]);
        }
        
        // Gérer les liaisons avec les clubs
        try {
            // Supprimer les anciennes liaisons
            $pdo->prepare("DELETE FROM destination_clubs WHERE destination_id = ?")->execute([$id]);
            
            // Ajouter les nouvelles liaisons (si des clubs sont sélectionnés)
            if (isset($_POST['clubs']) && is_array($_POST['clubs'])) {
                $stmtInsert = $pdo->prepare("INSERT INTO destination_clubs (destination_id, club_id) VALUES (?, ?)");
                foreach ($_POST['clubs'] as $clubId) {
                    $stmtInsert->execute([$id, (int)$clubId]);
                }
            }
        } catch (PDOException $e) {
            // Table destination_clubs n'existe pas encore - ignorer silencieusement
        }
        
        $message = $onlyClubsUpdate 
            ? '<div class="alert alert-success">✅ Clubs associés mis à jour avec succès !</div>'
            : '<div class="alert alert-success">✅ Destination mise à jour avec succès !</div>';
        
        // Recharger les données
        $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
        $stmt->execute([$id]);
        $destination = $stmt->fetch();
        
        // Recharger les clubs liés
        try {
            $stmtLinked = $pdo->prepare("SELECT club_id FROM destination_clubs WHERE destination_id = ?");
            $stmtLinked->execute([$id]);
            $linkedClubIds = $stmtLinked->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $linkedClubIds = [];
        }
        
    } catch (PDOException $e) {
        $message = '<div class="alert alert-error">❌ Erreur SQL : ' . h($e->getMessage()) . '</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-error">❌ Erreur : ' . h($e->getMessage()) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer <?php echo h($destination['nom']); ?> - Voyages ULM</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-title {
            text-align: center;
            color: #0c4a6e;
            margin-bottom: 2rem;
        }
        
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .form-card h2 {
            color: #0c4a6e;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #06b6d4;
            padding-bottom: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            color: #475569;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group-full textarea#description {
            max-width: 100%;
            width: 100%;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .file-upload {
            border: 2px dashed #06b6d4;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            background: #f0f9ff;
        }
        
        .current-photo {
            max-width: 300px;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        
        #map {
            height: 400px;
            width: 100%;
            border-radius: 12px;
            margin-top: 0.5rem;
            border: 2px solid #e5e7eb;
        }
        
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #14b8a6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.3);
        }
        
        .btn-secondary {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .poi-section {
            background: #ffffff;
            border: 2px solid #e0f2fe;
            padding: 0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .poi-section h3 {
            color: #0c4a6e;
            margin: 0;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border-bottom: 2px solid #bae6fd;
        }
        
        .poi-help {
            color: #475569;
            font-size: 0.875rem;
            line-height: 1.6;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .description-tabs {
            display: flex;
            gap: 0;
            background: #f1f5f9;
            padding: 0.5rem;
            border-bottom: 2px solid #cbd5e1;
        }
        
        .description-tab {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #64748b;
        }
        
        .description-tab.active {
            background: white;
            color: #0ea5e9;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .description-tab:hover:not(.active) {
            background: #e2e8f0;
        }
        
        .description-content {
            display: none;
            padding: 1.5rem;
        }
        
        .description-content.active {
            display: block;
        }
        
        .editor-toolbar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
            flex-wrap: wrap;
        }
        
        .editor-btn {
            padding: 0.5rem 0.75rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .editor-btn:hover {
            background: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
        }
        
        .char-counter {
            text-align: right;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.5rem;
        }
        
        .description-help {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
            font-size: 0.875rem;
            color: #1e40af;
        }
        
        .description-help strong {
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .description-help ul {
            margin: 0.5rem 0 0 1.5rem;
            line-height: 1.8;
        }
        
        .gallery-manager {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .gallery-photo {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .gallery-photo img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .gallery-photo-delete {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: white;
            margin-top: 1rem;
        }
        
        .poi-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .poi-modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .poi-modal-close {
            float: right;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            color: #94a3b8;
        }
        
        .poi-modal-close:hover {
            color: #475569;
        }
        
        .poi-form-group {
            margin-bottom: 1rem;
        }
        
        .poi-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #334155;
        }
        
        .poi-form-group input,
        .poi-form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .poi-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1 class="page-title">✏️ Éditer la destination</h1>
        
        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <div class="form-card">
            <h2>Informations de la destination</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <!-- Informations de base -->
                    <div class="form-group">
                        <label for="code_oaci">Code OACI</label>
                        <input type="text" id="code_oaci" name="code_oaci" 
                               value="<?php echo h($destination['code_oaci'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" 
                               value="<?php echo h($destination['nom']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="aerodrome">Aérodrome *</label>
                        <input type="text" id="aerodrome" name="aerodrome" 
                               value="<?php echo h($destination['aerodrome']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" 
                               value="<?php echo h($destination['ville'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="latitude">Latitude *</label>
                        <input type="number" step="0.000001" id="latitude" name="latitude" 
                               value="<?php echo h($destination['latitude'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="longitude">Longitude *</label>
                        <input type="number" step="0.000001" id="longitude" name="longitude" 
                               value="<?php echo h($destination['longitude'] ?? ''); ?>" required>
                    </div>
                    
                    <!-- Carte -->
                    <div class="form-group-full">
                        <label>📍 Position sur la carte</label>
                        <p style="font-size: 0.875rem; color: #64748b; margin: 0.5rem 0;">
                            Déplacez le marqueur pour ajuster la position précise
                        </p>
                        <div id="map"></div>
                    </div>
                    
                    <!-- Informations piste -->
                    <div class="form-group">
                        <label for="type_piste">Type de piste</label>
                        <select id="type_piste" name="type_piste">
                            <option value="">-- Sélectionner --</option>
                            <option value="dur" <?php echo $destination['type_piste'] === 'dur' ? 'selected' : ''; ?>>Dur</option>
                            <option value="herbe" <?php echo $destination['type_piste'] === 'herbe' ? 'selected' : ''; ?>>Herbe</option>
                            <option value="mixte" <?php echo $destination['type_piste'] === 'mixte' ? 'selected' : ''; ?>>Mixte</option>
                            <option value="terre" <?php echo $destination['type_piste'] === 'terre' ? 'selected' : ''; ?>>Terre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="longueur_piste_m">Longueur piste (m)</label>
                        <input type="number" id="longueur_piste_m" name="longueur_piste_m" 
                               value="<?php echo h($destination['longueur_piste_m'] ?? ''); ?>" placeholder="Ex: 800">
                    </div>
                    
                    <div class="form-group">
                        <label for="frequence_radio">Fréquence radio</label>
                        <input type="text" id="frequence_radio" name="frequence_radio" 
                               value="<?php echo h($destination['frequence_radio'] ?? ''); ?>" placeholder="Ex: 123.50">
                    </div>
                    
                    <!-- Services disponibles -->
                    <div class="form-group-full">
                        <label>🛠️ Services disponibles</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="carburant" name="carburant" value="1" 
                                       <?php echo $destination['carburant'] ? 'checked' : ''; ?>>
                                <label for="carburant">⛽ Carburant</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="restaurant" name="restaurant" value="1" 
                                       <?php echo $destination['restaurant'] ? 'checked' : ''; ?>>
                                <label for="restaurant">🍽️ Restaurant</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="hebergement" name="hebergement" value="1" 
                                       <?php echo $destination['hebergement'] ? 'checked' : ''; ?>>
                                <label for="hebergement">🏨 Hébergement</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Type d'accès -->
                    <div class="form-group-full">
                        <label>✈️ Type d'accès autorisé</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="acces_ulm" name="acces_ulm" value="1" 
                                       <?php echo $destination['acces_ulm'] ? 'checked' : ''; ?>>
                                <label for="acces_ulm">🪂 ULM</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="acces_avion" name="acces_avion" value="1" 
                                       <?php echo $destination['acces_avion'] ? 'checked' : ''; ?>>
                                <label for="acces_avion">✈️ Avion</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description et Points d'intérêt (onglets modernes) -->
                    <div class="form-group-full">
                        <div class="poi-section">
                            <div class="description-tabs">
                                <button type="button" class="description-tab active" onclick="switchDescriptionTab('description')">
                                    📝 Description
                                </button>
                                <button type="button" class="description-tab" onclick="switchDescriptionTab('poi')">
                                    📍 Points d'intérêt
                                </button>
                            </div>
                            
                            <!-- Onglet Description -->
                            <div id="tab-description" class="description-content active">
                                <div class="description-help">
                                    <strong>💡 Conseils pour une bonne description :</strong>
                                    <ul>
                                        <li>Décrivez l'ambiance et les particularités du terrain</li>
                                        <li>Mentionnez les conditions de vol spécifiques</li>
                                        <li>Indiquez les éventuelles restrictions ou précautions</li>
                                        <li>Parlez de l'accueil et des services disponibles</li>
                                    </ul>
                                </div>
                                
                                <div class="editor-toolbar">
                                    <button type="button" class="editor-btn" onclick="insertText('description', '**', '**')" title="Gras">
                                        <strong>B</strong>
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('description', '_', '_')" title="Italique">
                                        <em>I</em>
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('description', '\n- ', '')" title="Liste à puces">
                                        • Liste
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('description', '\n## ', '')" title="Titre">
                                        # Titre
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('description', '\n---\n', '')" title="Séparateur">
                                        ─ Ligne
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('description', '[', '](url)')" title="Lien">
                                        🔗 Lien
                                    </button>
                                </div>
                                
                                <textarea id="description" name="description" rows="15" 
                                          placeholder="Décrivez cette destination, son ambiance, les conditions de vol, l'accueil...

Exemples de mise en forme :
• **Texte en gras** pour mettre en valeur
• _Texte en italique_ pour les nuances
• ## Titre pour organiser
• - Liste à puces pour énumérer
• --- pour séparer les sections"
                                          oninput="updateCharCount('description')"
                                          style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 0.95rem; line-height: 1.6; resize: vertical; min-height: 300px;"><?php echo h($destination['description'] ?? ''); ?></textarea>
                                <div id="description-counter" class="char-counter">0 caractères</div>
                            </div>
                            
                            <!-- Onglet Points d'intérêt -->
                            <div id="tab-poi" class="description-content">
                                <div class="poi-help">
                                    <strong>ℹ️ Ajoutez des lieux d'intérêt autour de la destination</strong><br>
                                    Utilisez le bouton ci-dessous pour insérer facilement des lieux avec la mise en forme recommandée.
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <button type="button" class="btn-primary" onclick="openPoiModal()" style="padding: 0.75rem; width: auto; display: inline-flex; align-items: center; gap: 0.5rem;">
                                        ➕ Ajouter un lieu d'intérêt
                                    </button>
                                </div>
                                
                                <div class="editor-toolbar">
                                    <button type="button" class="editor-btn" onclick="insertText('points_interet', '🏨 Hôtel : ', '')" title="Hôtel">
                                        🏨 Hôtel
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('points_interet', '🍽️ Restaurant : ', '')" title="Restaurant">
                                        🍽️ Restaurant
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('points_interet', '🏛️ Monument : ', '')" title="Monument">
                                        🏛️ Monument
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('points_interet', '🌳 Nature : ', '')" title="Nature">
                                        🌳 Nature
                                    </button>
                                    <button type="button" class="editor-btn" onclick="insertText('points_interet', '🎯 Activité : ', '')" title="Activité">
                                        🎯 Activité
                                    </button>
                                </div>
                                
                                <textarea name="points_interet" id="points_interet" rows="12"
                                          placeholder="Ajoutez des points d'intérêt : restaurants, hôtels, monuments, activités..."
                                          oninput="updateCharCount('points_interet')"><?php echo h($destination['points_interet']); ?></textarea>
                                <div id="poi-counter" class="char-counter">0 caractères</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Photo -->
                    <div class="form-group-full">
                        <label>📷 Photo principale</label>
                        <?php if ($destination['photo_principale']): ?>
                            <div style="margin-bottom: 1rem;">
                                <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Photo actuelle :</p>
                                <img src="/uploads/destinations/<?php echo h($destination['photo_principale']); ?>?v=<?php echo time(); ?>" 
                                     alt="Photo actuelle" class="current-photo"
                                     onerror="console.error('Erreur photo:', this.src); this.style.display='none';">
                            </div>
                        <?php endif; ?>
                        <div class="file-upload">
                            <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                            <label for="photo" style="cursor: pointer;">
                                <div style="font-size: 3rem; margin-bottom: 0.5rem;">📸</div>
                                <strong>Cliquez pour changer la photo</strong>
                                <p style="font-size: 0.875rem; color: #64748b; margin-top: 0.5rem;">
                                    JPG, PNG - Max 5 Mo
                                </p>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Boutons -->
                    <div class="form-group-full" style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <a href="destination-detail.php?id=<?php echo $id; ?>" class="btn-secondary">
                            ⬅️ Annuler
                        </a>
                        <button type="submit" class="btn-primary" style="flex: 1;">
                            ✅ Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Clubs associés au terrain -->
        <div class="form-card">
            <h2>🏛️ Clubs associés à ce terrain</h2>
            <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1.5rem;">
                Sélectionnez les clubs qui utilisent régulièrement ce terrain. Les membres de ces clubs seront visibles sur la fiche de la destination.
            </p>
            
            <form method="POST" action="">
                <?php if (!empty($allClubs)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <?php foreach ($allClubs as $club): ?>
                            <label style="display: flex; align-items: start; gap: 0.75rem; padding: 1rem; background: #f8fafc; border-radius: 8px; cursor: pointer; border: 2px solid transparent; transition: all 0.3s;"
                                   onmouseover="this.style.background='#e0f2fe'; this.style.borderColor='#06b6d4';"
                                   onmouseout="this.style.background='#f8fafc'; this.style.borderColor='transparent';">
                                <input type="checkbox" 
                                       name="clubs[]" 
                                       value="<?php echo $club['id']; ?>"
                                       <?php echo in_array($club['id'], $linkedClubIds) ? 'checked' : ''; ?>
                                       style="margin-top: 0.25rem;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #0c4a6e;">
                                        🏛️ <?php echo h($club['nom']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                        <?php if ($club['ville']): ?>
                                            📍 <?php echo h($club['ville']); ?>
                                        <?php endif; ?>
                                        <?php if ($club['code_oaci']): ?>
                                            • <?php echo h($club['code_oaci']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn-primary" style="flex: 1;">
                            ✅ Enregistrer les clubs associés
                        </button>
                    </div>
                <?php else: ?>
                    <div style="background: #fef3c7; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        ⚠️ Aucun club disponible. <a href="clubs.php" style="color: #92400e; font-weight: 600;">Ajouter des clubs</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Galerie de photos supplémentaires -->
        <div class="gallery-manager">
            <h2>🖼️ Galerie photos supplémentaires</h2>
            
            <!-- Photos existantes -->
            <?php if ($photosResult && $photosResult->num_rows > 0): ?>
                <div class="gallery-grid">
                    <?php while ($photo = $photosResult->fetch_assoc()): ?>
                        <div class="gallery-photo" data-photo-id="<?php echo $photo['id']; ?>">
                            <img src="/uploads/destinations/<?php echo h($photo['filename']); ?>" 
                                 alt="<?php echo h($photo['legende'] ?? ''); ?>">
                            <button class="gallery-photo-delete" 
                                    onclick="deletePhoto(<?php echo $photo['id']; ?>)"
                                    title="Supprimer">
                                ×
                            </button>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: #64748b; font-style: italic;">Aucune photo supplémentaire pour le moment.</p>
            <?php endif; ?>
            
            <!-- Zone d'upload -->
            <div class="upload-zone">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">📤</div>
                <p style="margin-bottom: 1rem;"><strong>Ajouter des photos supplémentaires</strong></p>
                <input type="file" id="gallery-photos" multiple accept="image/*" style="display: none;">
                <label for="gallery-photos" class="btn-primary" style="cursor: pointer; display: inline-block;">
                    Choisir des photos
                </label>
                <p style="font-size: 0.875rem; color: #64748b; margin-top: 0.5rem;">
                    JPG, PNG - Max 5 Mo par photo
                </p>
            </div>
        </div>
    </div>
    
    <!-- Modal POI -->
    <div id="poiModal" class="poi-modal">
        <div class="poi-modal-content">
            <span class="poi-modal-close" onclick="closePoiModal()">&times;</span>
            <h2>➕ Ajouter un lieu d'intérêt</h2>
            
            <div class="poi-form-group">
                <label>Type de lieu</label>
                <select id="poiType">
                    <option value="🏨">🏨 Hôtel</option>
                    <option value="🍽️">🍽️ Restaurant</option>
                    <option value="🚕">🚕 Taxi</option>
                    <option value="🏪">🏪 Commerce</option>
                    <option value="⛽">⛽ Station-service</option>
                    <option value="🏛️">🏛️ Monument</option>
                    <option value="🏖️">🏖️ Site touristique</option>
                    <option value="ℹ️">ℹ️ Information</option>
                </select>
            </div>
            
            <div class="poi-form-group">
                <label>Nom et description</label>
                <input type="text" id="poiName" placeholder="Ex: Hôtel de la Gare - Hôtel 3 étoiles">
            </div>
            
            <div class="poi-form-group">
                <label>Adresse complète</label>
                <input type="text" id="poiAddress" placeholder="Ex: 12 Avenue de la République, 59000 Lille">
            </div>
            
            <div class="poi-grid-2">
                <div class="poi-form-group">
                    <label>Latitude GPS</label>
                    <input type="text" id="poiLat" placeholder="Ex: 50.6292">
                </div>
                <div class="poi-form-group">
                    <label>Longitude GPS</label>
                    <input type="text" id="poiLng" placeholder="Ex: 3.0573">
                </div>
            </div>
            
            <div class="poi-form-group">
                <label>Téléphone</label>
                <input type="text" id="poiPhone" placeholder="Ex: +33 3 20 06 06 06">
            </div>
            
            <div class="poi-form-group">
                <label>Tarifs / Informations pratiques</label>
                <input type="text" id="poiPrice" placeholder="Ex: 80-120€/nuit - Parking gratuit">
            </div>
            
            <div class="poi-form-group">
                <label>Distance à pied</label>
                <input type="text" id="poiDistance" placeholder="Ex: 5 min à pied de l'aérodrome">
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn-secondary" onclick="closePoiModal()">
                    Annuler
                </button>
                <button type="button" class="btn-primary" onclick="insertPoi()" style="flex: 1;">
                    ✅ Insérer le lieu
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Gestion des onglets Description/POI
        function switchDescriptionTab(tab) {
            // Désactiver tous les onglets
            document.querySelectorAll('.description-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.description-content').forEach(c => c.classList.remove('active'));
            
            // Activer l'onglet cliqué
            event.target.classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }
        
        // Insertion de texte dans textarea
        function insertText(textareaId, before, after) {
            const textarea = document.getElementById(textareaId);
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            const beforeText = textarea.value.substring(0, start);
            const afterText = textarea.value.substring(end);
            
            textarea.value = beforeText + before + selectedText + after + afterText;
            
            // Repositionner le curseur
            const newPos = start + before.length + selectedText.length;
            textarea.setSelectionRange(newPos, newPos);
            textarea.focus();
            
            updateCharCount(textareaId);
        }
        
        // Compteur de caractères
        function updateCharCount(textareaId) {
            const textarea = document.getElementById(textareaId);
            const counter = document.getElementById(textareaId + '-counter');
            if (counter) {
                const count = textarea.value.length;
                counter.textContent = count + ' caractère' + (count > 1 ? 's' : '');
                
                // Couleur selon la longueur
                if (count === 0) {
                    counter.style.color = '#94a3b8';
                } else if (count < 100) {
                    counter.style.color = '#f59e0b';
                } else {
                    counter.style.color = '#10b981';
                }
            }
        }
        
        // Initialiser les compteurs au chargement
        document.addEventListener('DOMContentLoaded', function() {
            updateCharCount('description');
            updateCharCount('points_interet');
        });
        
        // Modal POI
        function openPoiModal() {
            document.getElementById('poiModal').style.display = 'block';
        }
        
        function closePoiModal() {
            document.getElementById('poiModal').style.display = 'none';
            // Réinitialiser le formulaire
            document.getElementById('poiName').value = '';
            document.getElementById('poiAddress').value = '';
            document.getElementById('poiLat').value = '';
            document.getElementById('poiLng').value = '';
            document.getElementById('poiPhone').value = '';
            document.getElementById('poiPrice').value = '';
            document.getElementById('poiDistance').value = '';
        }
        
        function insertPoi() {
            const type = document.getElementById('poiType').value;
            const name = document.getElementById('poiName').value;
            const address = document.getElementById('poiAddress').value;
            const lat = document.getElementById('poiLat').value;
            const lng = document.getElementById('poiLng').value;
            const phone = document.getElementById('poiPhone').value;
            const price = document.getElementById('poiPrice').value;
            const distance = document.getElementById('poiDistance').value;
            
            if (!name) {
                alert('Veuillez remplir au moins le nom du lieu');
                return;
            }
            
            let poiText = `${type} ${name}\n`;
            if (address) poiText += `📍 ${address}\n`;
            if (lat && lng) poiText += `🌍 GPS: ${lat}, ${lng}\n`;
            if (phone) poiText += `📞 ${phone}\n`;
            if (price) poiText += `💰 ${price}\n`;
            if (distance) poiText += `🚶 ${distance}\n`;
            
            const textarea = document.getElementById('points_interet');
            const currentValue = textarea.value;
            const newValue = currentValue ? currentValue + '\n\n' + poiText : poiText;
            textarea.value = newValue;
            
            closePoiModal();
        }
        
        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('poiModal');
            if (event.target === modal) {
                closePoiModal();
            }
        }
        
        // Upload de photos supplémentaires
        document.getElementById('gallery-photos').addEventListener('change', async function(e) {
            const files = e.target.files;
            if (files.length === 0) return;
            
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('destination_id', <?php echo $id; ?>);
            
            for (let i = 0; i < files.length; i++) {
                formData.append('photos[]', files[i]);
            }
            
            try {
                const response = await fetch('destination-photos-ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Recharger la page pour afficher les nouvelles photos
                    location.reload();
                } else {
                    alert('Erreur lors de l\'upload : ' + (result.error || 'Erreur inconnue'));
                }
            } catch (error) {
                alert('Erreur réseau : ' + error.message);
            }
        });
        
        // Suppression de photo
        async function deletePhoto(photoId) {
            if (!confirm('Supprimer cette photo ?')) return;
            
            try {
                const response = await fetch('destination-photos-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete&photo_id=' + photoId
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Retirer l'élément du DOM
                    const photoElement = document.querySelector(`[data-photo-id="${photoId}"]`);
                    if (photoElement) {
                        photoElement.remove();
                    }
                } else {
                    alert('Erreur lors de la suppression : ' + (result.error || 'Erreur inconnue'));
                }
            } catch (error) {
                alert('Erreur réseau : ' + error.message);
            }
        }
        
        // Affichage du nom de fichier sélectionné
        document.getElementById('photo')?.addEventListener('change', function(e) {
            if (e.target.files.length) {
                const fileName = e.target.files[0].name;
                const label = this.closest('.file-upload').querySelector('strong');
                label.textContent = '📎 ' + fileName;
            }
        });
        
        // Carte Leaflet
        document.addEventListener('DOMContentLoaded', function() {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            
            // Coordonnées initiales
            let lat = parseFloat(latInput.value) || 48.8566;
            let lng = parseFloat(lngInput.value) || 2.3522;
            
            // Initialiser la carte
            const map = L.map('map').setView([lat, lng], 13);
            
            // Tuiles OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19
            }).addTo(map);
            
            // Icône personnalisée
            const customIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
            
            // Marqueur déplaçable
            const marker = L.marker([lat, lng], {
                icon: customIcon,
                draggable: true
            }).addTo(map);
            
            marker.bindPopup('Déplacez-moi pour ajuster la position !').openPopup();
            
            // Mise à jour des coordonnées quand on déplace le marqueur
            marker.on('dragend', function(e) {
                const position = marker.getLatLng();
                latInput.value = position.lat.toFixed(6);
                lngInput.value = position.lng.toFixed(6);
            });
            
            // Mise à jour du marqueur quand on modifie les inputs
            latInput.addEventListener('change', updateMarker);
            lngInput.addEventListener('change', updateMarker);
            
            function updateMarker() {
                const newLat = parseFloat(latInput.value);
                const newLng = parseFloat(lngInput.value);
                if (!isNaN(newLat) && !isNaN(newLng)) {
                    marker.setLatLng([newLat, newLng]);
                    map.setView([newLat, newLng], 13);
                }
            }
        });
    </script>
</body>
</html>
