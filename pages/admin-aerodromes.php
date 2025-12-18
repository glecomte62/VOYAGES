<?php
/**
 * Administration - Import et gestion des aérodromes français
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$error = '';
$stats = [];

// Traitement de l'import CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pdo = getDBConnection();
    
    if ($_POST['action'] === 'import_csv' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle) {
                $imported = 0;
                $updated = 0;
                $errors = 0;
                
                // Lire l'en-tête
                $header = fgetcsv($handle, 1000, ',');
                
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    try {
                        // Structure attendue du CSV:
                        // code_oaci, nom, ville, region, latitude, longitude, altitude_ft, type_piste, longueur_piste_m
                        
                        $code_oaci = trim($data[0] ?? '');
                        if (empty($code_oaci)) continue;
                        
                        // Vérifier si l'aérodrome existe déjà
                        $stmt = $pdo->prepare("SELECT id FROM destinations WHERE code_oaci = ?");
                        $stmt->execute([$code_oaci]);
                        $existing = $stmt->fetch();
                        
                        if ($existing) {
                            // Mise à jour
                            $stmt = $pdo->prepare("
                                UPDATE destinations SET
                                    nom = ?,
                                    aerodrome = ?,
                                    ville = ?,
                                    region = ?,
                                    latitude = ?,
                                    longitude = ?,
                                    altitude_ft = ?,
                                    type_piste = ?,
                                    longueur_piste_m = ?,
                                    updated_at = NOW()
                                WHERE code_oaci = ?
                            ");
                            $stmt->execute([
                                trim($data[1] ?? ''),
                                trim($data[1] ?? ''),
                                trim($data[2] ?? ''),
                                trim($data[3] ?? ''),
                                !empty($data[4]) ? floatval($data[4]) : null,
                                !empty($data[5]) ? floatval($data[5]) : null,
                                !empty($data[6]) ? intval($data[6]) : null,
                                trim($data[7] ?? 'dur'),
                                !empty($data[8]) ? intval($data[8]) : null,
                                $code_oaci
                            ]);
                            $updated++;
                        } else {
                            // Insertion
                            $stmt = $pdo->prepare("
                                INSERT INTO destinations (
                                    code_oaci, nom, aerodrome, ville, region, pays,
                                    latitude, longitude, altitude_ft, type_piste, longueur_piste_m,
                                    acces_ulm, acces_avion, actif
                                ) VALUES (?, ?, ?, ?, ?, 'France', ?, ?, ?, ?, ?, 1, 1, 1)
                            ");
                            $stmt->execute([
                                $code_oaci,
                                trim($data[1] ?? ''),
                                trim($data[1] ?? ''),
                                trim($data[2] ?? ''),
                                trim($data[3] ?? ''),
                                !empty($data[4]) ? floatval($data[4]) : null,
                                !empty($data[5]) ? floatval($data[5]) : null,
                                !empty($data[6]) ? intval($data[6]) : null,
                                trim($data[7] ?? 'dur'),
                                !empty($data[8]) ? intval($data[8]) : null
                            ]);
                            $imported++;
                        }
                    } catch (Exception $e) {
                        $errors++;
                    }
                }
                
                fclose($handle);
                $message = "Import terminé : $imported ajoutés, $updated mis à jour, $errors erreurs";
            }
        } else {
            $error = "Erreur lors de l'upload du fichier";
        }
    }
    
    if ($_POST['action'] === 'delete_all') {
        $stmt = $pdo->query("DELETE FROM destinations");
        $message = "Toutes les destinations ont été supprimées";
    }
}

// Récupérer les statistiques
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN acces_ulm = 1 THEN 1 ELSE 0 END) as ulm,
    SUM(CASE WHEN acces_avion = 1 THEN 1 ELSE 0 END) as avion,
    SUM(CASE WHEN carburant = 1 THEN 1 ELSE 0 END) as carburant,
    SUM(CASE WHEN restaurant = 1 THEN 1 ELSE 0 END) as restaurant
    FROM destinations WHERE actif = 1");
$stats = $stmt->fetch();

// Liste des derniers aérodromes importés
$stmt = $pdo->query("SELECT * FROM destinations ORDER BY created_at DESC LIMIT 50");
$recent = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Aérodromes - Admin VOYAGES</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .import-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .csv-format {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.875rem;
        }
        
        .csv-example {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 1rem 0;
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
            border-color: #0ea5e9;
        }
        
        .file-upload input[type="file"] {
            display: none;
        }
        
        .aerodrome-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .aerodrome-table th,
        .aerodrome-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .aerodrome-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #334155;
        }
        
        .aerodrome-table tr:hover {
            background: #f8fafc;
        }
        
        .danger-zone {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .danger-zone h3 {
            color: #991b1b;
            margin: 0 0 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="admin-container">
        <div class="admin-header">
            <h1>🛫 Gestion des Aérodromes</h1>
            <p>Import et gestion de la base de données des aérodromes français</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🗺️</div>
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Aérodromes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🪂</div>
                <div class="stat-value"><?php echo $stats['ulm'] ?? 0; ?></div>
                <div class="stat-label">Accès ULM</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✈️</div>
                <div class="stat-value"><?php echo $stats['avion'] ?? 0; ?></div>
                <div class="stat-label">Accès Avion</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⛽</div>
                <div class="stat-value"><?php echo $stats['carburant'] ?? 0; ?></div>
                <div class="stat-label">Avec carburant</div>
            </div>
        </div>

        <!-- Import CSV -->
        <div class="import-section">
            <h2>📤 Importer des aérodromes depuis un fichier CSV</h2>
            
            <div class="csv-format">
                <strong>Format attendu :</strong><br>
                code_oaci, nom, ville, region, latitude, longitude, altitude_ft, type_piste, longueur_piste_m
            </div>
            
            <div class="csv-example">
                LFQJ,Cambrai-Niergnies,Cambrai,Hauts-de-France,50.159722,3.154722,243,dur,1700<br>
                LFAQ,Albert-Bray,Albert,Hauts-de-France,49.971389,2.697778,208,dur,1100<br>
                LFAY,Amiens-Glisy,Amiens,Hauts-de-France,49.873056,2.386944,208,dur,1520
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                
                <div class="file-upload" onclick="document.getElementById('csv_file').click()">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📂</div>
                    <p><strong>Cliquez pour sélectionner un fichier CSV</strong></p>
                    <p style="color: #64748b; font-size: 0.875rem;">ou glissez-déposez votre fichier ici</p>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                    📤 Importer les données
                </button>
            </form>
            
            <p style="margin-top: 1rem; color: #64748b; font-size: 0.875rem;">
                💡 Les aérodromes existants (même code OACI) seront mis à jour, les nouveaux seront ajoutés.
            </p>
        </div>

        <!-- Liste des aérodromes récents -->
        <div class="import-section">
            <h2>📋 Derniers aérodromes (50 plus récents)</h2>
            
            <?php if (empty($recent)): ?>
                <p style="color: #64748b; text-align: center; padding: 2rem;">
                    Aucun aérodrome dans la base de données.
                </p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="aerodrome-table">
                        <thead>
                            <tr>
                                <th>Code OACI</th>
                                <th>Nom</th>
                                <th>Ville</th>
                                <th>Région</th>
                                <th>Altitude</th>
                                <th>Piste</th>
                                <th>Accès</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $aerodrome): ?>
                                <tr>
                                    <td><strong><?php echo h($aerodrome['code_oaci']); ?></strong></td>
                                    <td><?php echo h($aerodrome['nom']); ?></td>
                                    <td><?php echo h($aerodrome['ville']); ?></td>
                                    <td><?php echo h($aerodrome['region']); ?></td>
                                    <td><?php echo $aerodrome['altitude_ft'] ? h($aerodrome['altitude_ft']) . ' ft' : '-'; ?></td>
                                    <td>
                                        <?php echo $aerodrome['longueur_piste_m'] ? h($aerodrome['longueur_piste_m']) . 'm' : '-'; ?>
                                        <?php if ($aerodrome['type_piste']): ?>
                                            (<?php echo h($aerodrome['type_piste']); ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($aerodrome['acces_ulm']): ?><span title="ULM">🪂</span><?php endif; ?>
                                        <?php if ($aerodrome['acces_avion']): ?><span title="Avion">✈️</span><?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.875rem; color: #64748b;">
                                        <?php echo date('d/m/Y', strtotime($aerodrome['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Zone dangereuse -->
        <div class="danger-zone">
            <h3>⚠️ Zone dangereuse</h3>
            <p>Cette action supprimera TOUTES les destinations de la base de données. Cette action est irréversible !</p>
            <form method="POST" onsubmit="return confirm('Êtes-vous ABSOLUMENT SÛR de vouloir supprimer TOUTES les destinations ? Cette action est IRRÉVERSIBLE !')">
                <input type="hidden" name="action" value="delete_all">
                <button type="submit" class="btn" style="background: #dc2626; color: white;">
                    🗑️ Supprimer toutes les destinations
                </button>
            </form>
        </div>
    </div>

    <script>
        // Gestion du drag & drop
        const fileUpload = document.querySelector('.file-upload');
        const fileInput = document.getElementById('csv_file');
        
        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.style.background = '#f0f9ff';
        });
        
        fileUpload.addEventListener('dragleave', () => {
            fileUpload.style.background = '';
        });
        
        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.style.background = '';
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                const fileName = e.dataTransfer.files[0].name;
                fileUpload.querySelector('strong').textContent = fileName;
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                const fileName = e.target.files[0].name;
                fileUpload.querySelector('strong').textContent = fileName;
            }
        });
    </script>
</body>
</html>
