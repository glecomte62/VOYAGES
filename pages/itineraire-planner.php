<?php
/**
 * Planificateur d'itinéraires multi-étapes
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

$pdo = getDBConnection();

// Récupérer le terrain d'attache
$terrainAttacheLat = null;
$terrainAttacheLon = null;
$terrainAttacheNom = null;

$userId = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT terrain_attache_type, terrain_attache_id FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$userData = $stmtUser->fetch();

if ($userData && $userData['terrain_attache_type'] && $userData['terrain_attache_id']) {
    $terrainType = $userData['terrain_attache_type'];
    $terrainId = $userData['terrain_attache_id'];
    
    if ($terrainType === 'aerodrome') {
        $stmtTerrain = $pdo->prepare("SELECT nom, lat as latitude, lon as longitude FROM aerodromes_fr WHERE id = ?");
    } else {
        $stmtTerrain = $pdo->prepare("SELECT nom, lat as latitude, lon as longitude FROM ulm_bases_fr WHERE id = ?");
    }
    $stmtTerrain->execute([$terrainId]);
    $terrain = $stmtTerrain->fetch();
    
    if ($terrain) {
        $terrainAttacheLat = $terrain['latitude'];
        $terrainAttacheLon = $terrain['longitude'];
        $terrainAttacheNom = $terrain['nom'];
    }
}

// Récupérer toutes les destinations pour l'autocomplete
$stmt = $pdo->query("SELECT id, nom, aerodrome, code_oaci, ville, latitude, longitude FROM destinations WHERE actif = 1 ORDER BY nom ASC");
$destinations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificateur d'itinéraires - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .planner-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .planner-header {
            text-align: center;
            margin-bottom: 3rem;
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .planner-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #0ea5e9, #14b8a6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .planner-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .etapes-panel {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .etapes-panel h2 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .etape-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: move;
        }
        
        .etape-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .etape-info {
            flex: 1;
        }
        
        .etape-info h3 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
            color: #0f172a;
        }
        
        .etape-info p {
            margin: 0;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .etape-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.125rem;
            transition: all 0.3s;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-delete:hover {
            background: #fecaca;
        }
        
        .add-etape {
            width: 100%;
            padding: 1rem;
            background: white;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .add-etape:hover {
            border-color: #0ea5e9;
            color: #0ea5e9;
            background: #f0f9ff;
        }
        
        .destination-search {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .destination-search:focus {
            outline: none;
            border-color: #0ea5e9;
        }
        
        .carte-panel {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 2rem;
        }
        
        .carte-panel h2 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 1.5rem;
        }
        
        #carte-itineraire {
            width: 100%;
            height: 500px;
            border-radius: 12px;
            background: #f1f5f9;
        }
        
        .stats-itineraire {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #bae6fd;
        }
        
        .stat-row:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #0c4a6e;
            font-weight: 600;
        }
        
        .stat-value {
            color: #0369a1;
            font-weight: 700;
            font-size: 1.125rem;
        }
        
        @media (max-width: 968px) {
            .planner-grid {
                grid-template-columns: 1fr;
            }
            
            .carte-panel {
                position: static;
            }
        }
    </style>
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>
    
    <main class="planner-container">
        <div class="planner-header">
            <h1>🗺️ Planificateur d'itinéraires</h1>
            <p style="color: #64748b; margin: 0;">Créez votre voyage multi-étapes et visualisez votre parcours</p>
        </div>
        
        <div class="planner-grid">
            <div class="etapes-panel">
                <h2>📍 Étapes de votre voyage</h2>
                
                <?php if ($terrainAttacheNom): ?>
                    <div class="etape-item" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 2px solid #fbbf24;">
                        <div class="etape-number" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);">🏠</div>
                        <div class="etape-info">
                            <h3>Départ : <?php echo h($terrainAttacheNom); ?></h3>
                            <p>Votre terrain d'attache</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div id="etapes-list">
                    <!-- Les étapes seront ajoutées ici dynamiquement -->
                </div>
                
                <button class="add-etape" onclick="ajouterEtape()">
                    ➕ Ajouter une étape
                </button>
                
                <div id="search-modal" style="display: none; margin-top: 1rem;">
                    <input type="text" 
                           class="destination-search" 
                           id="search-input"
                           placeholder="🔍 Rechercher une destination..."
                           onkeyup="filtrerDestinations()">
                    <div id="destinations-list" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
            
            <div class="carte-panel">
                <h2>🗺️ Visualisation</h2>
                <div id="carte-itineraire">
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; color: #94a3b8;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">✈️</div>
                        <p>Ajoutez des étapes pour visualiser votre itinéraire</p>
                    </div>
                </div>
                
                <div class="stats-itineraire">
                    <div class="stat-row">
                        <span class="stat-label">📏 Distance totale</span>
                        <span class="stat-value" id="distance-totale">0 km</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">⏱️ Temps de vol</span>
                        <span class="stat-value" id="temps-total">0h 0min</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">🛫 Nombre d'étapes</span>
                        <span class="stat-value" id="nb-etapes">0</span>
                    </div>
                </div>
                
                <button style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; border: none; border-radius: 12px; font-weight: 700; margin-top: 1rem; cursor: pointer; font-size: 1rem;"
                        onclick="exporterItineraire()">
                    📄 Exporter en PDF
                </button>
            </div>
        </div>
    </main>
    
    <script>
        const destinations = <?php echo json_encode($destinations); ?>;
        const terrainDepart = <?php echo $terrainAttacheNom ? json_encode([
            'nom' => $terrainAttacheNom,
            'latitude' => $terrainAttacheLat,
            'longitude' => $terrainAttacheLon
        ]) : 'null'; ?>;
        
        let etapes = [];
        
        function ajouterEtape() {
            document.getElementById('search-modal').style.display = 'block';
            afficherToutesDestinations();
        }
        
        function afficherToutesDestinations() {
            const listDiv = document.getElementById('destinations-list');
            listDiv.innerHTML = destinations.map(dest => `
                <div style="padding: 1rem; background: white; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 0.5rem; cursor: pointer;"
                     onclick="selectionnerDestination(${dest.id})">
                    <div style="font-weight: 600; color: #0f172a;">${dest.nom}</div>
                    <div style="font-size: 0.875rem; color: #64748b;">${dest.aerodrome} ${dest.code_oaci ? '(' + dest.code_oaci + ')' : ''} • ${dest.ville}</div>
                </div>
            `).join('');
        }
        
        function filtrerDestinations() {
            const search = document.getElementById('search-input').value.toLowerCase();
            const filtered = destinations.filter(d => 
                d.nom.toLowerCase().includes(search) ||
                d.aerodrome.toLowerCase().includes(search) ||
                (d.code_oaci && d.code_oaci.toLowerCase().includes(search)) ||
                d.ville.toLowerCase().includes(search)
            );
            
            const listDiv = document.getElementById('destinations-list');
            listDiv.innerHTML = filtered.map(dest => `
                <div style="padding: 1rem; background: white; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 0.5rem; cursor: pointer;"
                     onclick="selectionnerDestination(${dest.id})">
                    <div style="font-weight: 600; color: #0f172a;">${dest.nom}</div>
                    <div style="font-size: 0.875rem; color: #64748b;">${dest.aerodrome} ${dest.code_oaci ? '(' + dest.code_oaci + ')' : ''} • ${dest.ville}</div>
                </div>
            `).join('');
        }
        
        function selectionnerDestination(id) {
            const dest = destinations.find(d => d.id === id);
            if (dest && !etapes.find(e => e.id === id)) {
                etapes.push(dest);
                afficherEtapes();
                calculerItineraire();
                document.getElementById('search-modal').style.display = 'none';
                document.getElementById('search-input').value = '';
            }
        }
        
        function afficherEtapes() {
            const listDiv = document.getElementById('etapes-list');
            listDiv.innerHTML = etapes.map((etape, index) => `
                <div class="etape-item">
                    <div class="etape-number">${index + 1}</div>
                    <div class="etape-info">
                        <h3>${etape.nom}</h3>
                        <p>${etape.aerodrome} ${etape.code_oaci ? '(' + etape.code_oaci + ')' : ''}</p>
                    </div>
                    <div class="etape-actions">
                        <button class="btn-icon btn-delete" onclick="supprimerEtape(${index})">🗑️</button>
                    </div>
                </div>
            `).join('');
        }
        
        function supprimerEtape(index) {
            etapes.splice(index, 1);
            afficherEtapes();
            calculerItineraire();
        }
        
        function calculerDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                     Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        function calculerItineraire() {
            if (etapes.length === 0) {
                document.getElementById('distance-totale').textContent = '0 km';
                document.getElementById('temps-total').textContent = '0h 0min';
                document.getElementById('nb-etapes').textContent = '0';
                return;
            }
            
            let distanceTotale = 0;
            let pointPrecedent = terrainDepart;
            
            for (const etape of etapes) {
                if (pointPrecedent) {
                    distanceTotale += calculerDistance(
                        pointPrecedent.latitude,
                        pointPrecedent.longitude,
                        etape.latitude,
                        etape.longitude
                    );
                }
                pointPrecedent = etape;
            }
            
            const tempsMinutes = Math.round((distanceTotale / 160) * 60);
            const heures = Math.floor(tempsMinutes / 60);
            const minutes = tempsMinutes % 60;
            
            document.getElementById('distance-totale').textContent = Math.round(distanceTotale) + ' km';
            document.getElementById('temps-total').textContent = heures + 'h ' + minutes + 'min';
            document.getElementById('nb-etapes').textContent = etapes.length;
        }
        
        function exporterItineraire() {
            if (etapes.length === 0) {
                alert('Ajoutez au moins une étape à votre itinéraire');
                return;
            }
            window.location.href = 'export-itineraire-pdf.php?etapes=' + etapes.map(e => e.id).join(',');
        }
    </script>
</body>
</html>
