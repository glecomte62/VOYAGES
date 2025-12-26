<?php
/**
 * VOYAGES - Planification d'itinéraire pour un voyage
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

$pdo = getDBConnection();
$voyage_id = intval($_GET['id'] ?? 0);

// Vérifier que le voyage existe et appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM voyages WHERE id = ? AND user_id = ?");
$stmt->execute([$voyage_id, $_SESSION['user_id']]);
$voyage = $stmt->fetch();

if (!$voyage) {
    header("Location: voyages.php");
    exit;
}

// Récupérer les étapes existantes
$stmt = $pdo->prepare("
    SELECT * FROM voyage_etapes 
    WHERE voyage_id = ? 
    ORDER BY ordre ASC
");
$stmt->execute([$voyage_id]);
$etapes = $stmt->fetchAll();

// Récupérer tous les terrains disponibles pour l'autocomplete
$aerodromes = $pdo->query("SELECT id, nom, code_oaci, ville, latitude, longitude FROM aerodromes_fr ORDER BY nom ASC")->fetchAll();
$ulm_bases = $pdo->query("SELECT id, nom, ville, latitude, longitude FROM ulm_bases_fr ORDER BY nom ASC")->fetchAll();
$destinations = $pdo->query("SELECT id, nom, aerodrome as code_oaci, ville, latitude, longitude FROM destinations WHERE actif = 1 ORDER BY nom ASC")->fetchAll();

// Traitement AJAX pour ajouter/modifier/supprimer des étapes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'add_etape') {
            $ordre = intval($_POST['ordre']);
            $terrain_type = $_POST['terrain_type'];
            $terrain_id = intval($_POST['terrain_id']);
            $date_arrivee = $_POST['date_arrivee'] ?? null;
            $date_depart = $_POST['date_depart'] ?? null;
            $notes = $_POST['notes'] ?? '';
            
            // Récupérer les infos du terrain
            $terrain_info = null;
            if ($terrain_type === 'aerodrome') {
                $stmt = $pdo->prepare("SELECT nom, code_oaci, latitude, longitude FROM aerodromes_fr WHERE id = ?");
                $stmt->execute([$terrain_id]);
                $terrain_info = $stmt->fetch();
            } elseif ($terrain_type === 'ulm_base') {
                $stmt = $pdo->prepare("SELECT nom, latitude, longitude FROM ulm_bases_fr WHERE id = ?");
                $stmt->execute([$terrain_id]);
                $terrain_info = $stmt->fetch();
            } elseif ($terrain_type === 'destination') {
                $stmt = $pdo->prepare("SELECT nom, aerodrome as code_oaci, latitude, longitude FROM destinations WHERE id = ?");
                $stmt->execute([$terrain_id]);
                $terrain_info = $stmt->fetch();
            }
            
            if ($terrain_info) {
                $stmt = $pdo->prepare("
                    INSERT INTO voyage_etapes (
                        voyage_id, ordre, terrain_type, terrain_id, terrain_nom, 
                        terrain_code_oaci, latitude, longitude, date_arrivee, date_depart, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $voyage_id,
                    $ordre,
                    $terrain_type,
                    $terrain_id,
                    $terrain_info['nom'],
                    $terrain_info['code_oaci'] ?? null,
                    $terrain_info['latitude'],
                    $terrain_info['longitude'],
                    $date_arrivee ?: null,
                    $date_depart ?: null,
                    $notes
                ]);
                
                // Mettre à jour le nombre d'étapes
                $pdo->prepare("UPDATE voyages SET nombre_etapes = (SELECT COUNT(*) FROM voyage_etapes WHERE voyage_id = ?) WHERE id = ?")
                    ->execute([$voyage_id, $voyage_id]);
                
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Terrain non trouvé']);
            }
            
        } elseif ($_POST['action'] === 'delete_etape') {
            $etape_id = intval($_POST['etape_id']);
            
            $stmt = $pdo->prepare("DELETE FROM voyage_etapes WHERE id = ? AND voyage_id = ?");
            $stmt->execute([$etape_id, $voyage_id]);
            
            // Réorganiser les ordres
            $pdo->prepare("
                UPDATE voyage_etapes ve
                SET ordre = (
                    SELECT COUNT(*) 
                    FROM voyage_etapes ve2 
                    WHERE ve2.voyage_id = ve.voyage_id 
                    AND ve2.id <= ve.id
                )
                WHERE voyage_id = ?
            ")->execute([$voyage_id]);
            
            // Mettre à jour le nombre d'étapes
            $pdo->prepare("UPDATE voyages SET nombre_etapes = (SELECT COUNT(*) FROM voyage_etapes WHERE voyage_id = ?) WHERE id = ?")
                ->execute([$voyage_id, $voyage_id]);
            
            echo json_encode(['success' => true]);
            
        } elseif ($_POST['action'] === 'reorder_etapes') {
            $etapes_order = json_decode($_POST['etapes'], true);
            
            foreach ($etapes_order as $index => $etape_id) {
                $pdo->prepare("UPDATE voyage_etapes SET ordre = ? WHERE id = ? AND voyage_id = ?")
                    ->execute([$index + 1, $etape_id, $voyage_id]);
            }
            
            echo json_encode(['success' => true]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planifier l'itinéraire - <?php echo h($voyage['titre']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .planner-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .planner-header {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .planner-header h1 {
            font-size: 2rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        
        .voyage-info {
            display: flex;
            gap: 2rem;
            color: #64748b;
            margin-top: 1rem;
        }
        
        .voyage-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .planner-content {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 2rem;
        }
        
        .etapes-panel {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            height: fit-content;
        }
        
        .etapes-panel h2 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 1.5rem;
        }
        
        .add-etape-form {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0ea5e9;
        }
        
        .terrain-search {
            position: relative;
        }
        
        .terrain-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-top: 0.25rem;
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }
        
        .terrain-results.active {
            display: block;
        }
        
        .terrain-result-item {
            padding: 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .terrain-result-item:hover {
            background: #f8fafc;
        }
        
        .terrain-result-name {
            font-weight: 600;
            color: #0f172a;
        }
        
        .terrain-result-code {
            color: #0ea5e9;
            font-size: 0.875rem;
        }
        
        .terrain-result-ville {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .etapes-list {
            list-style: none;
            padding: 0;
        }
        
        .etape-item {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: move;
            transition: all 0.2s;
        }
        
        .etape-item:hover {
            border-color: #0ea5e9;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.1);
        }
        
        .etape-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .etape-order {
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        
        .etape-name {
            font-weight: 700;
            color: #0f172a;
            flex: 1;
            margin-left: 1rem;
        }
        
        .etape-code {
            color: #0ea5e9;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .etape-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            color: #64748b;
            transition: color 0.2s;
        }
        
        .btn-icon:hover {
            color: #ef4444;
        }
        
        .etape-details {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .map-panel {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .map-container {
            width: 100%;
            height: 600px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
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
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .planner-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
    </style>
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <main class="planner-container">
        <div class="planner-header">
            <h1>🗺️ <?php echo h($voyage['titre']); ?></h1>
            <div class="voyage-info">
                <div class="voyage-info-item">
                    📅 <?php echo formatDate($voyage['date_debut']); ?> - <?php echo formatDate($voyage['date_fin']); ?>
                </div>
                <?php if ($voyage['aeronef']): ?>
                    <div class="voyage-info-item">
                        ✈️ <?php echo h($voyage['aeronef']); ?>
                    </div>
                <?php endif; ?>
                <div class="voyage-info-item">
                    👥 <?php echo $voyage['nombre_passagers']; ?> passager(s)
                </div>
            </div>
        </div>
        
        <div class="planner-content">
            <div class="etapes-panel">
                <h2>Étapes du voyage</h2>
                
                <div class="add-etape-form">
                    <h3 style="margin-bottom: 1rem; font-size: 1rem;">➕ Ajouter une étape</h3>
                    
                    <div class="form-group terrain-search">
                        <label>Rechercher un terrain</label>
                        <input type="text" id="terrain-search-input" placeholder="Nom du terrain ou code OACI...">
                        <div class="terrain-results" id="terrain-results"></div>
                    </div>
                    
                    <input type="hidden" id="selected-terrain-type">
                    <input type="hidden" id="selected-terrain-id">
                    <input type="hidden" id="selected-terrain-name">
                    <input type="hidden" id="selected-terrain-code">
                    
                    <div class="form-group">
                        <label>Date d'arrivée</label>
                        <input type="datetime-local" id="date-arrivee">
                    </div>
                    
                    <div class="form-group">
                        <label>Date de départ</label>
                        <input type="datetime-local" id="date-depart">
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea id="notes-etape" rows="2" placeholder="Notes pour cette étape..."></textarea>
                    </div>
                    
                    <button class="btn btn-primary" onclick="addEtape()" style="width: 100%;">
                        Ajouter l'étape
                    </button>
                </div>
                
                <ul class="etapes-list" id="etapes-list">
                    <?php foreach ($etapes as $etape): ?>
                        <li class="etape-item" data-etape-id="<?php echo $etape['id']; ?>">
                            <div class="etape-header">
                                <div class="etape-order"><?php echo $etape['ordre']; ?></div>
                                <div class="etape-name"><?php echo h($etape['terrain_nom']); ?></div>
                                <?php if ($etape['terrain_code_oaci']): ?>
                                    <div class="etape-code"><?php echo h($etape['terrain_code_oaci']); ?></div>
                                <?php endif; ?>
                                <div class="etape-actions">
                                    <button class="btn-icon" onclick="deleteEtape(<?php echo $etape['id']; ?>)" title="Supprimer">🗑️</button>
                                </div>
                            </div>
                            <?php if ($etape['date_arrivee'] || $etape['date_depart']): ?>
                                <div class="etape-details">
                                    <?php if ($etape['date_arrivee']): ?>
                                        Arrivée: <?php echo date('d/m/Y H:i', strtotime($etape['date_arrivee'])); ?>
                                    <?php endif; ?>
                                    <?php if ($etape['date_depart']): ?>
                                        | Départ: <?php echo date('d/m/Y H:i', strtotime($etape['date_depart'])); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($etape['notes']): ?>
                                <div class="etape-details"><?php echo h($etape['notes']); ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="map-panel">
                <h2 style="margin-bottom: 1rem;">Carte de l'itinéraire</h2>
                <div class="map-container">
                    🗺️ Carte interactive (à implémenter avec Leaflet ou Google Maps)
                </div>
                
                <div class="planner-actions">
                    <a href="voyage-detail.php?id=<?php echo $voyage_id; ?>" class="btn btn-primary">
                        Ajouter hébergements, ravitaillements et visites →
                    </a>
                    <a href="voyages.php" class="btn btn-secondary">Retour aux voyages</a>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        const terrains = {
            aerodromes: <?php echo json_encode($aerodromes); ?>,
            ulm_bases: <?php echo json_encode($ulm_bases); ?>,
            destinations: <?php echo json_encode($destinations); ?>
        };
        
        const searchInput = document.getElementById('terrain-search-input');
        const resultsDiv = document.getElementById('terrain-results');
        
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (query.length < 2) {
                resultsDiv.classList.remove('active');
                return;
            }
            
            const results = [];
            
            // Recherche dans les aérodromes
            terrains.aerodromes.forEach(terrain => {
                if (terrain.nom.toLowerCase().includes(query) || 
                    (terrain.code_oaci && terrain.code_oaci.toLowerCase().includes(query)) ||
                    (terrain.ville && terrain.ville.toLowerCase().includes(query))) {
                    results.push({...terrain, type: 'aerodrome', typeLabel: 'Aérodrome'});
                }
            });
            
            // Recherche dans les bases ULM
            terrains.ulm_bases.forEach(terrain => {
                if (terrain.nom.toLowerCase().includes(query) || 
                    (terrain.ville && terrain.ville.toLowerCase().includes(query))) {
                    results.push({...terrain, type: 'ulm_base', typeLabel: 'Base ULM'});
                }
            });
            
            // Recherche dans les destinations
            terrains.destinations.forEach(terrain => {
                if (terrain.nom.toLowerCase().includes(query) || 
                    (terrain.code_oaci && terrain.code_oaci.toLowerCase().includes(query)) ||
                    (terrain.ville && terrain.ville.toLowerCase().includes(query))) {
                    results.push({...terrain, type: 'destination', typeLabel: 'Destination'});
                }
            });
            
            displayResults(results.slice(0, 10));
        });
        
        function displayResults(results) {
            if (results.length === 0) {
                resultsDiv.innerHTML = '<div style="padding: 1rem; color: #64748b;">Aucun terrain trouvé</div>';
                resultsDiv.classList.add('active');
                return;
            }
            
            resultsDiv.innerHTML = results.map(terrain => `
                <div class="terrain-result-item" onclick="selectTerrain('${terrain.type}', ${terrain.id}, '${terrain.nom.replace(/'/g, "\\'")}', '${terrain.code_oaci || ''}')">
                    <div class="terrain-result-name">${terrain.nom}</div>
                    ${terrain.code_oaci ? `<div class="terrain-result-code">${terrain.code_oaci} - ${terrain.typeLabel}</div>` : `<div class="terrain-result-code">${terrain.typeLabel}</div>`}
                    ${terrain.ville ? `<div class="terrain-result-ville">${terrain.ville}</div>` : ''}
                </div>
            `).join('');
            
            resultsDiv.classList.add('active');
        }
        
        function selectTerrain(type, id, name, code) {
            document.getElementById('selected-terrain-type').value = type;
            document.getElementById('selected-terrain-id').value = id;
            document.getElementById('selected-terrain-name').value = name;
            document.getElementById('selected-terrain-code').value = code;
            
            searchInput.value = name + (code ? ` (${code})` : '');
            resultsDiv.classList.remove('active');
        }
        
        // Fermer les résultats si clic ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.terrain-search')) {
                resultsDiv.classList.remove('active');
            }
        });
        
        function addEtape() {
            const terrainType = document.getElementById('selected-terrain-type').value;
            const terrainId = document.getElementById('selected-terrain-id').value;
            const dateArrivee = document.getElementById('date-arrivee').value;
            const dateDepart = document.getElementById('date-depart').value;
            const notes = document.getElementById('notes-etape').value;
            
            if (!terrainType || !terrainId) {
                alert('Veuillez sélectionner un terrain');
                return;
            }
            
            const ordre = document.querySelectorAll('.etape-item').length + 1;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'add_etape',
                    ordre: ordre,
                    terrain_type: terrainType,
                    terrain_id: terrainId,
                    date_arrivee: dateArrivee,
                    date_depart: dateDepart,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Impossible d\'ajouter l\'étape'));
                }
            });
        }
        
        function deleteEtape(etapeId) {
            if (!confirm('Voulez-vous vraiment supprimer cette étape ?')) {
                return;
            }
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'delete_etape',
                    etape_id: etapeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Impossible de supprimer l\'étape'));
                }
            });
        }
    </script>
</body>
</html>
