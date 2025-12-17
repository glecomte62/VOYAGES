<?php
/**
 * Page de profil utilisateur
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
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
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
        $photoUpdated = false;
        $newPhotoFilename = $user['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadPhoto($_FILES['photo']);
            if ($uploadResult['success']) {
                // Supprimer l'ancienne photo
                if ($user['photo']) {
                    deletePhoto($user['photo']);
                }
                $newPhotoFilename = $uploadResult['filename'];
                $photoUpdated = true;
            } else {
                $error = $uploadResult['error'];
            }
        }
        
        // Mettre à jour si pas d'erreur
        if (empty($error)) {
            if ($passwordUpdated) {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nom = ?, prenom = ?, telephone = ?, email = ?, password = ?, photo = ?
                    WHERE id = ?
                ");
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt->execute([$nom, $prenom, $telephone, $email, $password_hash, $newPhotoFilename, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nom = ?, prenom = ?, telephone = ?, email = ?, photo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nom, $prenom, $telephone, $email, $newPhotoFilename, $_SESSION['user_id']]);
            }
            
            // Mettre à jour la session
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email'] = $email;
            
            $success = 'Profil mis à jour avec succès';
            
            // Recharger les données
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
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="page-header-title">
                <h1>Mon Profil</h1>
                <p>Gérez vos informations personnelles</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo h($success); ?></div>
            <?php endif; ?>
            
            <div class="profile-container">
                <div class="profile-photo-section">
                    <div class="profile-photo">
                        <?php if ($user['photo']): ?>
                            <img src="../uploads/photos/<?php echo h($user['photo']); ?>" alt="Photo de profil">
                        <?php else: ?>
                            <div class="photo-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo h($user['prenom'] . ' ' . $user['nom']); ?></h2>
                        <p class="role-badge badge-<?php echo h($user['role']); ?>">
                            <?php echo $user['role'] === 'admin' ? 'Administrateur' : 'Membre'; ?>
                        </p>
                        <p class="user-since">Membre depuis <?php echo formatDate($user['created_at'], 'd F Y'); ?></p>
                    </div>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data" class="profile-form">
                    <div class="form-section">
                        <h3>Informations personnelles</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prenom">Prénom *</label>
                                <input type="text" id="prenom" name="prenom" required 
                                       value="<?php echo h($user['prenom']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="nom">Nom *</label>
                                <input type="text" id="nom" name="nom" required 
                                       value="<?php echo h($user['nom']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo h($user['email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" 
                                   value="<?php echo h($user['telephone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="photo">Changer la photo de profil</label>
                            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="form-help">JPG, PNG, GIF ou WEBP - Max 5 Mo</small>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Changer le mot de passe</h3>
                        <p class="form-help">Laissez vide si vous ne souhaitez pas changer votre mot de passe</p>
                        
                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password" minlength="8">
                                <small class="form-help">Au moins 8 caractères</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password_confirm">Confirmer le nouveau mot de passe</label>
                                <input type="password" id="new_password_confirm" name="new_password_confirm" minlength="8">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="../index.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
