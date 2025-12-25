<?php
/**
 * VOYAGES - Ajouter un club
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

$pdo = getDBConnection();
$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $code_oaci = trim($_POST['code_oaci'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $site_web = trim($_POST['site_web'] ?? '');
    
    // Validation
    if (empty($nom) || empty($ville)) {
        $error = '❌ Le nom et la ville du club sont obligatoires.';
    } else {
        // Vérifier les doublons
        $checkSql = "SELECT COUNT(*) as count FROM clubs WHERE 
                     (LOWER(nom) = LOWER(?) AND LOWER(ville) = LOWER(?))";
        $checkParams = [$nom, $ville];
        
        if (!empty($code_oaci)) {
            $checkSql .= " OR code_oaci = ?";
            $checkParams[] = $code_oaci;
        }
        
        $stmtCheck = $pdo->prepare($checkSql);
        $stmtCheck->execute($checkParams);
        $result = $stmtCheck->fetch();
        
        if ($result['count'] > 0) {
            $error = '⚠️ Ce club existe déjà dans notre base de données (même nom/ville ou même code OACI).';
        } else {
            // Insérer le club
            try {
                $sql = "INSERT INTO clubs (nom, ville, code_oaci, telephone, email, site_web, actif, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nom,
                    $ville,
                    $code_oaci ?: null,
                    $telephone ?: null,
                    $email ?: null,
                    $site_web ?: null
                ]);
                
                $message = '✅ Le club a été ajouté avec succès !';
                
                // Rediriger après 2 secondes
                header('Refresh: 2; URL=clubs.php');
            } catch (PDOException $e) {
                $error = '❌ Erreur lors de l\'ajout du club : ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un club - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-title {
            text-align: center;
            font-size: 2.5rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #334155;
        }
        
        .required {
            color: #ef4444;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .help-text {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            border: none;
        }
        
        .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.3);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .info-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .info-box h3 {
            margin-top: 0;
            color: #92400e;
        }
        
        .info-box ul {
            margin: 0.5rem 0 0 1.5rem;
            color: #78350f;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1 class="page-title">➕ Ajouter un club</h1>
        <p class="page-subtitle">Ajoutez votre club à notre annuaire communautaire</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>ℹ️ Avant d'ajouter un club</h3>
            <ul>
                <li>Vérifiez que le club n'existe pas déjà dans notre annuaire</li>
                <li>Assurez-vous d'avoir les informations correctes</li>
                <li>Le code OACI doit correspondre à l'aérodrome principal du club</li>
                <li>Les informations seront vérifiées avant publication</li>
            </ul>
        </div>
        
        <div class="form-card">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="nom">Nom du club <span class="required">*</span></label>
                        <input type="text" id="nom" name="nom" required 
                               placeholder="Ex: Club ULM Évasion"
                               value="<?php echo h($_POST['nom'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="ville">Ville <span class="required">*</span></label>
                        <input type="text" id="ville" name="ville" required 
                               placeholder="Ex: Maubeuge"
                               value="<?php echo h($_POST['ville'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="code_oaci">Code OACI</label>
                        <input type="text" id="code_oaci" name="code_oaci" 
                               placeholder="Ex: LFQJ" maxlength="4"
                               value="<?php echo h($_POST['code_oaci'] ?? ''); ?>">
                        <p class="help-text">Code de l'aérodrome principal (4 lettres)</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" 
                               placeholder="Ex: +33 3 27 64 00 00"
                               value="<?php echo h($_POST['telephone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               placeholder="Ex: contact@club.fr"
                               value="<?php echo h($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_web">Site web</label>
                        <input type="url" id="site_web" name="site_web" 
                               placeholder="Ex: https://club.fr"
                               value="<?php echo h($_POST['site_web'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="btn-group">
                    <a href="clubs.php" class="btn btn-secondary">⬅️ Annuler</a>
                    <button type="submit" class="btn btn-primary">✅ Ajouter le club</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
