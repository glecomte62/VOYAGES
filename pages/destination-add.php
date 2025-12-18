<?php
/**
 * Page d'ajout de destination
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
$step = 1;
$results = [];
$selected_aerodrome = null;
$message = '';

// Gestion de la recherche
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    $search = trim($_POST['search'] ?? '');
    $type = $_POST['type'] ?? 'aerodrome';
    
    if (!empty($search)) {
        if ($type === 'aerodrome') {
            $stmt = $pdo->prepare("SELECT * FROM aerodromes_fr WHERE oaci LIKE ? OR nom LIKE ? OR ville LIKE ? LIMIT 20");
            $searchParam = "%$search%";
            $stmt->execute([$searchParam, $searchParam, $searchParam]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM ulm_bases_fr WHERE nom LIKE ? OR ville LIKE ? LIMIT 20");
            $searchParam = "%$search%";
            $stmt->execute([$searchParam, $searchParam]);
        }
        $results = $stmt->fetchAll();
    }
}

// Gestion de la sélection
if (isset($_POST['action']) && $_POST['action'] === 'select') {
    $type = $_POST['type'];
    $id = $_POST['aerodrome_id'];
    
    if ($type === 'aerodrome') {
        $stmt = $pdo->prepare("SELECT * FROM aerodromes_fr WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM ulm_bases_fr WHERE id = ?");
    }
    $stmt->execute([$id]);
    $selected_aerodrome = $stmt->fetch();
    
    // Sauvegarder en session
    $_SESSION['temp_aerodrome'] = $selected_aerodrome;
    $_SESSION['temp_type'] = $type;
    $step = 2;
}

// Récupérer depuis la session si on revient à l'étape 2
if (!isset($_POST['action']) && isset($_SESSION['temp_aerodrome'])) {
    $selected_aerodrome = $_SESSION['temp_aerodrome'];
    $step = 2;
}

// Gestion de la création
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        // Upload de la photo si présente
        $photo_filename = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadPhoto($_FILES['photo'], '../uploads/destinations/');
            if ($uploadResult['success']) {
                $photo_filename = $uploadResult['filename'];
            }
        }
        
        // Préparer les données
        $code_oaci = $_POST['code_oaci'] ?? null;
        $nom = $_POST['nom'];
        $aerodrome = $_POST['aerodrome'];
        $ville = $_POST['ville'] ?? '';
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        $type_piste = $_POST['type_piste'] ?? null;
        $longueur_piste_m = $_POST['longueur_piste_m'] ?? null;
        $frequence_radio = $_POST['frequence_radio'] ?? null;
        $carburant = isset($_POST['carburant']) ? 1 : 0;
        $restaurant = isset($_POST['restaurant']) ? 1 : 0;
        $hebergement = isset($_POST['hebergement']) ? 1 : 0;
        $acces_ulm = isset($_POST['acces_ulm']) ? 1 : 0;
        $acces_avion = isset($_POST['acces_avion']) ? 1 : 0;
        $description = $_POST['description'] ?? '';
        $points_interet = $_POST['points_interet'] ?? '';
        
        // Insertion dans la base
        $sql = "INSERT INTO destinations (
            code_oaci, nom, aerodrome, ville, pays, latitude, longitude,
            type_piste, longueur_piste_m, frequence_radio,
            carburant, restaurant, hebergement,
            acces_ulm, acces_avion,
            description, points_interet, photo_principale,
            created_by, actif, created_at
        ) VALUES (
            ?, ?, ?, ?, 'France', ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, 1, NOW()
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $code_oaci, $nom, $aerodrome, $ville, $latitude, $longitude,
            $type_piste, $longueur_piste_m, $frequence_radio,
            $carburant, $restaurant, $hebergement,
            $acces_ulm, $acces_avion,
            $description, $points_interet, $photo_filename,
            $_SESSION['user_id']
        ]);
        
        // Nettoyer la session
        unset($_SESSION['temp_aerodrome']);
        unset($_SESSION['temp_type']);
        
        $message = '<div class="alert alert-success">✅ Destination créée avec succès !</div>';
        $step = 1;
        
    } catch (PDOException $e) {
        $message = '<div class="alert alert-error">❌ Erreur : ' . h($e->getMessage()) . '</div>';
    }
}

// Gérer le reset
if (isset($_GET['reset'])) {
    unset($_SESSION['temp_aerodrome']);
    unset($_SESSION['temp_type']);
    header('Location: destination-add.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une destination - Voyages ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body {
            background: url('../assets/images/hero-bg.jpg') center/cover no-repeat fixed;
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
        
        .type-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .type-option {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .type-option:hover {
            border-color: #06b6d4;
            background: #f0f9ff;
        }
        
        .type-option input[type="radio"] {
            display: none;
        }
        
        .type-option.active {
            border-color: #06b6d4;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        
        .search-box {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .search-box input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #06b6d4;
        }
        
        .results-grid {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .result-item {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .result-item:hover {
            border-color: #06b6d4;
            background: #f0f9ff;
            transform: translateX(8px);
        }
        
        .result-item strong {
            color: #0c4a6e;
            font-size: 1.1rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .result-item small {
            color: #64748b;
            font-size: 0.875rem;
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
            min-height: 100px;
            resize: vertical;
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
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .poi-section h3 {
            color: #92400e;
            margin-top: 0;
        }
        
        .poi-help {
            color: #78350f;
            font-size: 0.875rem;
            line-height: 1.6;
        }
        
        .poi-help p {
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1 class="page-title">➕ Ajouter une destination</h1>
        
        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <?php if ($step === 1): ?>
            <!-- Étape 1 : Recherche d'aérodrome -->
            <div class="form-card">
                <h2>Étape 1 : Rechercher un aérodrome ou une base ULM</h2>
                
                <form method="POST">
                    <input type="hidden" name="action" value="search">
                    
                    <div class="type-selector">
                        <div class="type-option">
                            <input type="radio" name="type" value="aerodrome" id="type_aerodrome" checked>
                            <label for="type_aerodrome">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">✈️</div>
                                <strong>Aérodrome</strong>
                                <p style="font-size: 0.875rem; color: #64748b; margin-top: 0.5rem;">
                                    Avec code OACI
                                </p>
                            </label>
                        </div>
                        
                        <div class="type-option">
                            <input type="radio" name="type" value="ulm" id="type_ulm">
                            <label for="type_ulm">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🪂</div>
                                <strong>Base ULM</strong>
                                <p style="font-size: 0.875rem; color: #64748b; margin-top: 0.5rem;">
                                    Terrains ULM
                                </p>
                            </label>
                        </div>
                    </div>
                    
                    <div class="search-box">
                        <input type="text" 
                               name="search" 
                               placeholder="Rechercher par code OACI, nom ou ville..."
                               value="<?php echo h($_POST['search'] ?? ''); ?>"
                               required>
                        <button type="submit" class="btn-primary" style="width: auto; padding: 0.75rem 2rem;">
                            🔍 Rechercher
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($results)): ?>
                    <div class="results-grid">
                        <?php foreach ($results as $result): ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="select">
                                <input type="hidden" name="type" value="<?php echo h($_POST['type']); ?>">
                                <input type="hidden" name="aerodrome_id" value="<?php echo h($result['id']); ?>">
                                
                                <div class="result-item" onclick="this.closest('form').submit()">
                                    <strong>
                                        <?php if (!empty($result['oaci'])): ?>
                                            <?php echo h($result['oaci']); ?> - 
                                        <?php endif; ?>
                                        <?php echo h($result['nom']); ?>
                                    </strong>
                                    <small>
                                        📍 <?php echo h($result['ville']); ?>
                                        <?php if (!empty($result['latitude']) && !empty($result['longitude'])): ?>
                                            • 🌍 <?php echo h($result['latitude']); ?>, <?php echo h($result['longitude']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (isset($results)): ?>
                    <p style="text-align: center; color: #64748b; margin-top: 2rem;">
                        Aucun résultat trouvé. Essayez une autre recherche.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 2 && $selected_aerodrome): ?>
            <!-- Étape 2 : Compléter les informations -->
            <div class="form-card">
                <h2>✏️ Compléter les informations</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-grid">
                        <!-- Informations de base -->
                        <div class="form-group">
                            <label for="code_oaci">Code OACI</label>
                            <input type="text" id="code_oaci" name="code_oaci" 
                                   value="<?php echo h($selected_aerodrome['oaci'] ?? ''); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" 
                                   value="<?php echo h($selected_aerodrome['nom']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="aerodrome">Aérodrome *</label>
                            <input type="text" id="aerodrome" name="aerodrome" 
                                   value="<?php echo h($selected_aerodrome['nom']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="ville">Ville</label>
                            <input type="text" id="ville" name="ville" 
                                   value="<?php echo h($selected_aerodrome['ville'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="latitude">Latitude *</label>
                            <input type="number" step="0.000001" id="latitude" name="latitude" 
                                   value="<?php echo h($selected_aerodrome['latitude'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="longitude">Longitude *</label>
                            <input type="number" step="0.000001" id="longitude" name="longitude" 
                                   value="<?php echo h($selected_aerodrome['longitude'] ?? ''); ?>" required>
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
                                <option value="dur">Dur</option>
                                <option value="herbe">Herbe</option>
                                <option value="mixte">Mixte</option>
                                <option value="terre">Terre</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="longueur_piste_m">Longueur piste (m)</label>
                            <input type="number" id="longueur_piste_m" name="longueur_piste_m" placeholder="Ex: 800">
                        </div>
                        
                        <div class="form-group">
                            <label for="frequence_radio">Fréquence radio</label>
                            <input type="text" id="frequence_radio" name="frequence_radio" placeholder="Ex: 123.50">
                        </div>
                        
                        <!-- Services disponibles -->
                        <div class="form-group-full">
                            <label>🛠️ Services disponibles</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="carburant" name="carburant" value="1">
                                    <label for="carburant">⛽ Carburant</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="restaurant" name="restaurant" value="1">
                                    <label for="restaurant">🍽️ Restaurant</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="hebergement" name="hebergement" value="1">
                                    <label for="hebergement">🏨 Hébergement</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Type d'accès -->
                        <div class="form-group-full">
                            <label>✈️ Type d'accès autorisé</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" id="acces_ulm" name="acces_ulm" value="1" checked>
                                    <label for="acces_ulm">🪂 ULM</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="acces_avion" name="acces_avion" value="1" checked>
                                    <label for="acces_avion">✈️ Avion</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="form-group-full">
                            <label for="description">📝 Description</label>
                            <textarea id="description" name="description" placeholder="Décrivez cette destination..."></textarea>
                        </div>
                        
                    <!-- Points d'intérêt -->
                    <div class="form-group-full">
                        <label for="points_interet">🗺️ Points d'intérêt touristiques</label>
                        <div class="poi-section">
                            <h3>💡 Guide pour les points d'intérêt</h3>
                            <div class="poi-help">
                                <p><strong>Format recommandé pour chaque lieu :</strong></p>
                                <p>🏨 <strong>Nom du lieu</strong> - Type (Hôtel/Restaurant/Taxi)<br>
                                📍 Adresse complète<br>
                                🌍 GPS: latitude, longitude<br>
                                📞 Téléphone: +33 X XX XX XX XX<br>
                                💰 Prix moyen / Info pratique<br>
                                🚶 Distance: X min à pied depuis l'aérodrome</p>
                                
                                <p style="margin-top: 1rem;"><strong>Exemple :</strong></p>
                                <p style="background: white; padding: 0.75rem; border-radius: 6px; font-size: 0.875rem;">
                                🏨 <strong>Hôtel de la Gare</strong> - Hôtel 3 étoiles<br>
                                📍 12 Avenue de la République, 59000 Lille<br>
                                🌍 GPS: 50.6292, 3.0573<br>
                                📞 +33 3 20 06 06 06<br>
                                💰 80-120€/nuit - Parking gratuit<br>
                                🚶 5 min à pied de l'aérodrome
                                </p>
                            </div>
                        </div>
                        <textarea id="points_interet" name="points_interet" 
                                  placeholder="Ajoutez les restaurants, hôtels, taxis accessibles à pied avec leurs coordonnées GPS et contacts..."
                                  style="min-height: 250px;"></textarea>
                    </div>
                        
                        <!-- Photo -->
                        <div class="form-group-full">
                            <label>📷 Photo principale</label>
                            <div class="file-upload">
                                <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                                <label for="photo" style="cursor: pointer;">
                                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">📸</div>
                                    <strong>Cliquez pour choisir une photo</strong>
                                    <p style="font-size: 0.875rem; color: #64748b; margin-top: 0.5rem;">
                                        JPG, PNG - Max 5 Mo
                                    </p>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Boutons -->
                        <div class="form-group-full" style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <a href="?reset=1" class="btn-secondary" onclick="return confirm('Annuler la saisie ?')">
                                ⬅️ Retour à la recherche
                            </a>
                            <button type="submit" class="btn-primary" style="flex: 1;">
                                ✅ Créer la destination
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Gestion du sélecteur de type
        document.querySelectorAll('.type-option').forEach(option => {
            option.addEventListener('click', function() {
                // Désélectionner tous
                document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('active'));
                
                // Sélectionner celui-ci
                this.classList.add('active');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });
        
        // Sélectionner le bon au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const checkedRadio = document.querySelector('input[type="radio"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('.type-option').classList.add('active');
            }
        });
        
        // Affichage du nom de fichier sélectionné
        document.getElementById('photo')?.addEventListener('change', function(e) {
            if (e.target.files.length) {
                const fileName = e.target.files[0].name;
                const label = this.closest('.file-upload').querySelector('strong');
                label.textContent = '📎 ' + fileName;
            }
        });
        
        // Carte Leaflet
        <?php if ($step === 2 && $selected_aerodrome): ?>
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
            
            marker.bindPopup('<strong><?php echo addslashes($selected_aerodrome['nom']); ?></strong><br>Déplacez-moi !').openPopup();
            
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
        <?php endif; ?>
    </script>
</body>
</html>
