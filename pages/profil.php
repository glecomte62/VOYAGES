<?php
/**
 * Page de profil utilisateur - Version modernisée
 */

require_once '../includes/session.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$error = '';
$success = '';

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Récupérer les clubs de l'utilisateur
$userClubs = [];
try {
    $stmtClubs = $pdo->prepare("
        SELECT c.id, c.nom, c.ville, c.code_oaci
        FROM clubs c
        INNER JOIN user_clubs uc ON c.id = uc.club_id
        WHERE uc.user_id = ?
        ORDER BY c.nom ASC
    ");
    $stmtClubs->execute([$_SESSION['user_id']]);
    $userClubs = $stmtClubs->fetchAll();
} catch (PDOException $e) {
    // Table user_clubs n'existe pas encore
}

// Compter les contributions
$nbDestinations = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM destinations WHERE created_by = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $nbDestinations = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Colonne created_by n'existe pas encore
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email)) {
        $error = 'Le nom, prénom et email sont obligatoires';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide';
    } else {
        // Vérifier si l'email est déjà utilisé
        if ($email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error = 'Cet email est déjà utilisé';
            }
        }
        
        // Gérer le changement de mot de passe
        $passwordUpdated = false;
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $error = 'Veuillez saisir votre mot de passe actuel';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Mot de passe actuel incorrect';
            } elseif (strlen($new_password) < 8) {
                $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères';
            } elseif ($new_password !== $new_password_confirm) {
                $error = 'Les nouveaux mots de passe ne correspondent pas';
            } else {
                $passwordUpdated = true;
            }
        }
        
        // Gérer l'upload de photo
        $newPhotoFilename = $user['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadPhoto($_FILES['photo']);
            if ($uploadResult['success']) {
                if ($user['photo']) {
                    deletePhoto($user['photo']);
                }
                $newPhotoFilename = $uploadResult['filename'];
            } else {
                $error = $uploadResult['error'];
            }
        }
        
        // Mettre à jour
        if (empty($error)) {
            if ($passwordUpdated) {
                $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, telephone = ?, email = ?, password = ?, photo = ? WHERE id = ?");
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt->execute([$nom, $prenom, $telephone, $email, $password_hash, $newPhotoFilename, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, telephone = ?, email = ?, photo = ? WHERE id = ?");
                $stmt->execute([$nom, $prenom, $telephone, $email, $newPhotoFilename, $_SESSION['user_id']]);
            }
            
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email'] = $email;
            
            $success = 'Profil mis à jour avec succès';
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .hero-banner {
            text-align: center;
            margin-bottom: 3rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #14b8a6 100%);
            padding: 3rem 2rem;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.2);
            position: relative;
            overflow: hidden;
            margin-top: -2rem;
        }
        
        .hero-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .hero-title {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 0.5rem;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }
        
        .hero-subtitle {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.125rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-avatar-placeholder {
            font-size: 4rem;
            color: white;
            font-weight: 700;
        }
        
        .profile-name {
            text-align: center;
            font-size: 1.75rem;
            color: #0c4a6e;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .profile-role {
            text-align: center;
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 1.5rem;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 20px;
            display: inline-block;
            width: 100%;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0ea5e9;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        .clubs-list {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f5f9;
        }
        
        .clubs-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        
        .club-item {
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .club-item-icon {
            font-size: 1.5rem;
        }
        
        .club-item-name {
            font-weight: 600;
            color: #0c4a6e;
        }
        
        .club-item-city {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .form-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            color: #0c4a6e;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e0f2fe;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #0c4a6e;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-help {
            display: block;
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1.5rem;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.3);
        }
        
        .btn-secondary {
            background: #f8fafc;
            color: #64748b;
            border: 2px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
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
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .hero-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 700; color: #0f172a; margin-bottom: 2rem;">👤 Mon Profil</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>
        
        <div class="profile-grid">
            <div>
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php if ($user['photo']): ?>
                            <img src="../uploads/photos/<?php echo h($user['photo']); ?>" alt="Photo">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder">
                                <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h2 class="profile-name"><?php echo h($user['prenom'] . ' ' . $user['nom']); ?></h2>
                    
                    <div class="profile-role">
                        <?php if ($user['role'] === 'admin'): ?>
                            ⭐ Administrateur
                        <?php else: ?>
                            👤 Membre
                        <?php endif; ?>
                        • Depuis <?php echo date('m/Y', strtotime($user['created_at'])); ?>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $nbDestinations; ?></div>
                            <div class="stat-label">Destinations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo count($userClubs); ?></div>
                            <div class="stat-label">Clubs</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($userClubs)): ?>
                        <div class="clubs-list">
                            <div class="clubs-title">🏛️ Mes Clubs</div>
                            <?php foreach ($userClubs as $club): ?>
                                <div class="club-item">
                                    <div class="club-item-icon">🏛️</div>
                                    <div style="flex: 1;">
                                        <div class="club-item-name"><?php echo h($club['nom']); ?></div>
                                        <?php if ($club['ville']): ?>
                                            <div class="club-item-city">📍 <?php echo h($club['ville']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3>📝 Informations personnelles</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prenom">Prénom *</label>
                                <input type="text" id="prenom" name="prenom" required value="<?php echo h($user['prenom']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="nom">Nom *</label>
                                <input type="text" id="nom" name="nom" required value="<?php echo h($user['nom']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required value="<?php echo h($user['email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?php echo h($user['telephone']); ?>" placeholder="+33 6 12 34 56 78">
                        </div>
                        
                        <div class="form-group">
                            <label for="photo">📸 Photo de profil</label>
                            <input type="file" id="photo" name="photo" accept="image/*">
                            <small class="form-help">JPG, PNG, GIF ou WEBP - Max 5 Mo</small>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>🔐 Changer le mot de passe</h3>
                        <p class="form-help" style="margin-bottom: 1.5rem;">Laissez vide pour conserver votre mot de passe actuel</p>
                        
                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password" minlength="8">
                                <small class="form-help">Min. 8 caractères</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password_confirm">Confirmer</label>
                                <input type="password" id="new_password_confirm" name="new_password_confirm" minlength="8">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="../index.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
