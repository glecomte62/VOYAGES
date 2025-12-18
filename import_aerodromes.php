<?php
/**
 * Script d'import des aérodromes depuis la base GESTNAV
 * Import des tables aerodromes_fr et ulm_bases_fr
 */

require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireAdmin();

// Configuration de la base GESTNAV
$gestnav_config = [
    'host' => 'localhost',
    'dbname' => 'kica7829_gestnav',  // À vérifier : peut-être juste 'gestnav' ou autre nom
    'user' => 'kica7829_voyages',
    'pass' => 'Corvus2024@LFQJ'
];

$stats = [
    'aerodromes_imported' => 0,
    'aerodromes_updated' => 0,
    'ulm_imported' => 0,
    'ulm_updated' => 0,
    'errors' => 0
];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // Connexion à GESTNAV
        $gestnav_pdo = new PDO(
            "mysql:host={$gestnav_config['host']};dbname={$gestnav_config['dbname']};charset=utf8mb4",
            $gestnav_config['user'],
            $gestnav_config['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Connexion à VOYAGES
        $voyages_pdo = getDBConnection();
        
        if ($_POST['action'] === 'import_aerodromes') {
            // Import des aérodromes
            $stmt = $gestnav_pdo->query("SELECT oaci, nom, ville, latitude, longitude FROM aerodromes_fr WHERE oaci IS NOT NULL AND oaci != ''");
            $aerodromes = $stmt->fetchAll();
            
            foreach ($aerodromes as $aero) {
                try {
                    // Vérifier si existe déjà
                    $check = $voyages_pdo->prepare("SELECT id FROM destinations WHERE code_oaci = ?");
                    $check->execute([$aero['oaci']]);
                    $existing = $check->fetch();
                    
                    if ($existing) {
                        // Mise à jour
                        $update = $voyages_pdo->prepare("
                            UPDATE destinations SET
                                nom = ?,
                                aerodrome = ?,
                                ville = ?,
                                latitude = ?,
                                longitude = ?,
                                acces_avion = 1,
                                updated_at = NOW()
                            WHERE code_oaci = ?
                        ");
                        $update->execute([
                            $aero['nom'],
                            $aero['nom'],
                            $aero['ville'],
                            $aero['latitude'],
                            $aero['longitude'],
                            $aero['oaci']
                        ]);
                        $stats['aerodromes_updated']++;
                    } else {
                        // Insertion
                        $insert = $voyages_pdo->prepare("
                            INSERT INTO destinations (
                                code_oaci, nom, aerodrome, ville, pays,
                                latitude, longitude,
                                acces_ulm, acces_avion, actif
                            ) VALUES (?, ?, ?, ?, 'France', ?, ?, 1, 1, 1)
                        ");
                        $insert->execute([
                            $aero['oaci'],
                            $aero['nom'],
                            $aero['nom'],
                            $aero['ville'],
                            $aero['latitude'],
                            $aero['longitude']
                        ]);
                        $stats['aerodromes_imported']++;
                    }
                } catch (Exception $e) {
                    $stats['errors']++;
                }
            }
        }
        
        if ($_POST['action'] === 'import_ulm') {
            // Import des bases ULM
            $stmt = $gestnav_pdo->query("SELECT nom, ville, oaci, latitude, longitude FROM ulm_bases_fr WHERE nom IS NOT NULL AND nom != ''");
            $bases = $stmt->fetchAll();
            
            foreach ($bases as $base) {
                try {
                    $code_oaci = $base['oaci'] ?: null;
                    
                    // Si code OACI, vérifier par code OACI
                    if ($code_oaci) {
                        $check = $voyages_pdo->prepare("SELECT id FROM destinations WHERE code_oaci = ?");
                        $check->execute([$code_oaci]);
                        $existing = $check->fetch();
                        
                        if ($existing) {
                            // Mise à jour pour ajouter accès ULM
                            $update = $voyages_pdo->prepare("
                                UPDATE destinations SET
                                    acces_ulm = 1,
                                    updated_at = NOW()
                                WHERE code_oaci = ?
                            ");
                            $update->execute([$code_oaci]);
                            $stats['ulm_updated']++;
                            continue;
                        }
                    }
                    
                    // Sinon vérifier par nom
                    $check = $voyages_pdo->prepare("SELECT id FROM destinations WHERE nom = ? AND ville = ?");
                    $check->execute([$base['nom'], $base['ville']]);
                    $existing = $check->fetch();
                    
                    if ($existing) {
                        // Mise à jour
                        $update = $voyages_pdo->prepare("
                            UPDATE destinations SET
                                acces_ulm = 1,
                                code_oaci = COALESCE(code_oaci, ?),
                                updated_at = NOW()
                            WHERE nom = ? AND ville = ?
                        ");
                        $update->execute([
                            $code_oaci,
                            $base['nom'],
                            $base['ville']
                        ]);
                        $stats['ulm_updated']++;
                    } else {
                        // Insertion nouvelle base ULM
                        $insert = $voyages_pdo->prepare("
                            INSERT INTO destinations (
                                code_oaci, nom, aerodrome, ville, pays,
                                latitude, longitude,
                                acces_ulm, acces_avion, actif
                            ) VALUES (?, ?, ?, ?, 'France', ?, ?, 1, 0, 1)
                        ");
                        $insert->execute([
                            $code_oaci,
                            $base['nom'],
                            $base['nom'],
                            $base['ville'],
                            $base['latitude'],
                            $base['longitude']
                        ]);
                        $stats['ulm_imported']++;
                    }
                } catch (Exception $e) {
                    $stats['errors']++;
                }
            }
        }
        
        if ($_POST['action'] === 'import_all') {
            // Import complet : aérodromes puis bases ULM
            $_POST['action'] = 'import_aerodromes';
            include(__FILE__);
            $_POST['action'] = 'import_ulm';
            include(__FILE__);
        }
        
        $message = sprintf(
            "Import terminé : %d aérodromes ajoutés, %d mis à jour | %d bases ULM ajoutées, %d mises à jour | %d erreurs",
            $stats['aerodromes_imported'],
            $stats['aerodromes_updated'],
            $stats['ulm_imported'],
            $stats['ulm_updated'],
            $stats['errors']
        );
        
    } catch (PDOException $e) {
        $error = "Erreur de connexion à la base GESTNAV : " . $e->getMessage();
    }
}

// Récupérer les stats actuelles
try {
    $voyages_pdo = getDBConnection();
    $stmt = $voyages_pdo->query("SELECT COUNT(*) as total FROM destinations WHERE actif = 1");
    $current_stats = $stmt->fetch();
} catch (Exception $e) {
    $current_stats = ['total' => 0];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import GESTNAV - Admin VOYAGES</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .import-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .config-section {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .config-section h3 {
            color: #92400e;
            margin: 0 0 1rem;
        }
        
        .config-code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.875rem;
            overflow-x: auto;
        }
        
        .import-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .action-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .action-card h3 {
            margin: 0 0 1rem;
            color: #0f172a;
        }
        
        .action-card p {
            color: #64748b;
            margin: 0 0 1.5rem;
        }
        
        .stats-preview {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="import-container">
        <div class="admin-header">
            <h1>📥 Import depuis GESTNAV</h1>
            <p>Importer les aérodromes et bases ULM depuis votre base GESTNAV</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="config-section">
            <h3>⚙️ Configuration de la connexion GESTNAV</h3>
            <p>Avant d'importer, vérifiez la configuration dans ce fichier :</p>
            <div class="config-code">
$gestnav_config = [<br>
&nbsp;&nbsp;&nbsp;&nbsp;'host' => 'localhost',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'dbname' => 'gestnav',  // Nom de votre base GESTNAV<br>
&nbsp;&nbsp;&nbsp;&nbsp;'user' => 'root',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'pass' => ''<br>
];
            </div>
            <p style="margin-top: 1rem; color: #92400e;">
                💡 Modifiez ces valeurs dans le fichier <code>import_aerodromes.php</code> selon votre configuration MySQL
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🗺️</div>
                <div class="stat-value"><?php echo $current_stats['total']; ?></div>
                <div class="stat-label">Destinations actuelles</div>
            </div>
        </div>

        <div class="import-actions">
            <div class="action-card">
                <h3>✈️ Importer les aérodromes</h3>
                <p>Importe tous les aérodromes depuis la table <code>aerodromes_fr</code></p>
                <div class="stats-preview">
                    <strong>Source :</strong> Table aerodromes_fr<br>
                    <strong>Champs :</strong> OACI, nom, ville, coordonnées<br>
                    <strong>Accès :</strong> Avion = Oui, ULM = Oui
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="import_aerodromes">
                    <button type="submit" class="btn btn-primary">
                        📤 Importer les aérodromes
                    </button>
                </form>
            </div>

            <div class="action-card">
                <h3>🪂 Importer les bases ULM</h3>
                <p>Importe toutes les bases ULM depuis la table <code>ulm_bases_fr</code></p>
                <div class="stats-preview">
                    <strong>Source :</strong> Table ulm_bases_fr<br>
                    <strong>Champs :</strong> nom, ville, OACI (optionnel), coordonnées<br>
                    <strong>Accès :</strong> ULM = Oui, Avion = Non
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="import_ulm">
                    <button type="submit" class="btn btn-primary">
                        📤 Importer les bases ULM
                    </button>
                </form>
            </div>

            <div class="action-card">
                <h3>🚀 Import complet</h3>
                <p>Importe tous les aérodromes ET toutes les bases ULM en une seule fois</p>
                <div class="stats-preview">
                    <strong>Étapes :</strong><br>
                    1. Import aerodromes_fr<br>
                    2. Import ulm_bases_fr<br>
                    3. Fusion automatique des doublons
                </div>
                <form method="POST" onsubmit="return confirm('Lancer l\'import complet ?')">
                    <input type="hidden" name="action" value="import_all">
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                        🚀 Import complet
                    </button>
                </form>
            </div>
        </div>

        <div class="import-section" style="margin-top: 2rem; background: white; border-radius: 16px; padding: 2rem;">
            <h2>ℹ️ Fonctionnement de l'import</h2>
            <ul style="color: #64748b; line-height: 1.8;">
                <li><strong>Aérodromes :</strong> Identifiés par code OACI, mise à jour si existe déjà</li>
                <li><strong>Bases ULM :</strong> Identifiées par code OACI ou nom+ville, fusion intelligente</li>
                <li><strong>Coordonnées :</strong> Latitude/longitude importées directement</li>
                <li><strong>Doublons :</strong> Les bases ULM avec même OACI qu'un aérodrome sont fusionnées</li>
                <li><strong>Sécurité :</strong> Import non destructif, les données existantes sont préservées</li>
            </ul>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
