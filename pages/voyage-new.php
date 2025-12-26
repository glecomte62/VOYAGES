<?php
/**
 * VOYAGES - Création d'un nouveau voyage
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

$pdo = getDBConnection();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';
    $aeronef = trim($_POST['aeronef'] ?? '');
    $type_aeronef = $_POST['type_aeronef'] ?? 'ulm';
    $nombre_passagers = intval($_POST['nombre_passagers'] ?? 1);
    $budget_total = floatval($_POST['budget_total'] ?? 0);
    $public = isset($_POST['public']) ? 1 : 0;
    
    // Validation
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }
    if (empty($date_debut)) {
        $errors[] = "La date de début est obligatoire";
    }
    if (empty($date_fin)) {
        $errors[] = "La date de fin est obligatoire";
    }
    if ($date_fin < $date_debut) {
        $errors[] = "La date de fin doit être après la date de début";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO voyages (
                    user_id, titre, description, date_debut, date_fin, 
                    aeronef, type_aeronef, nombre_passagers, budget_total,
                    public, statut
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planifie')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $titre,
                $description,
                $date_debut,
                $date_fin,
                $aeronef,
                $type_aeronef,
                $nombre_passagers,
                $budget_total,
                $public
            ]);
            
            $voyage_id = $pdo->lastInsertId();
            $success = true;
            
            // Redirection vers la page de planification d'itinéraire
            header("Location: voyage-planner.php?id=" . $voyage_id);
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la création du voyage : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Voyage - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .voyage-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .voyage-form {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .voyage-form h1 {
            font-size: 2rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .voyage-form .subtitle {
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }
        
        .form-group label .required {
            color: #ef4444;
        }
        
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-help {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f5f9;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 1rem;
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
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
    </style>
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <main class="voyage-form-container">
        <div class="voyage-form">
            <h1>✈️ Nouveau Voyage</h1>
            <p class="subtitle">Créez votre voyage et planifiez ensuite votre itinéraire étape par étape</p>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo h($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Voyage créé avec succès ! Redirection...
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="titre">Titre du voyage <span class="required">*</span></label>
                    <input type="text" id="titre" name="titre" required 
                           value="<?php echo h($_POST['titre'] ?? ''); ?>"
                           placeholder="Ex: Tour de Bretagne, Week-end en Normandie...">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Décrivez votre voyage, les objectifs, les points d'intérêt..."><?php echo h($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_debut">Date de début <span class="required">*</span></label>
                        <input type="date" id="date_debut" name="date_debut" required
                               value="<?php echo h($_POST['date_debut'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_fin">Date de fin <span class="required">*</span></label>
                        <input type="date" id="date_fin" name="date_fin" required
                               value="<?php echo h($_POST['date_fin'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="aeronef">Aéronef</label>
                        <input type="text" id="aeronef" name="aeronef" 
                               value="<?php echo h($_POST['aeronef'] ?? ''); ?>"
                               placeholder="Ex: Tecnam P92, Cessna 172...">
                    </div>
                    
                    <div class="form-group">
                        <label for="type_aeronef">Type d'aéronef</label>
                        <select id="type_aeronef" name="type_aeronef">
                            <option value="ulm" <?php echo ($_POST['type_aeronef'] ?? '') === 'ulm' ? 'selected' : ''; ?>>ULM</option>
                            <option value="avion" <?php echo ($_POST['type_aeronef'] ?? '') === 'avion' ? 'selected' : ''; ?>>Avion</option>
                            <option value="autre" <?php echo ($_POST['type_aeronef'] ?? '') === 'autre' ? 'selected' : ''; ?>>Autre</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_passagers">Nombre de passagers</label>
                        <input type="number" id="nombre_passagers" name="nombre_passagers" min="1" max="10"
                               value="<?php echo h($_POST['nombre_passagers'] ?? '1'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="budget_total">Budget total (€)</label>
                        <input type="number" id="budget_total" name="budget_total" min="0" step="0.01"
                               value="<?php echo h($_POST['budget_total'] ?? ''); ?>"
                               placeholder="Budget estimé du voyage">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="public" name="public" value="1"
                               <?php echo isset($_POST['public']) ? 'checked' : ''; ?>>
                        <label for="public">Partager ce voyage avec la communauté</label>
                    </div>
                    <p class="form-help">Les autres membres pourront voir votre itinéraire et vos recommandations</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Créer et planifier l'itinéraire →</button>
                    <a href="voyages.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
