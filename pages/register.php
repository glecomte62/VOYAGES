<?php
/**
 * Page d'inscription
 */

require_once '../includes/session.php';

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    
    // Validation
    if (empty($email) || empty($password) || empty($nom) || empty($prenom)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif ($password !== $password_confirm) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        $pdo = getDBConnection();
        
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Cet email est déjà utilisé';
        } else {
            // Gérer l'upload de la photo
            $photoFilename = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadPhoto($_FILES['photo']);
                if ($uploadResult['success']) {
                    $photoFilename = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['error'];
                }
            }
            
            // Créer le compte si pas d'erreur
            if (empty($error)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, nom, prenom, telephone, photo) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$email, $password_hash, $nom, $prenom, $telephone, $photoFilename])) {
                    $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                } else {
                    $error = 'Erreur lors de la création du compte';
                    // Supprimer la photo si échec
                    if ($photoFilename) {
                        deletePhoto($photoFilename);
                    }
                }
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
    <title>Inscription - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <h1>✈️ VOYAGES ULM</h1>
                <p class="tagline">Catalogue de destinations pour pilotes</p>
            </div>
            
            <div class="club-badge">
                <p>🏛️ Application offerte par le</p>
                <h3>Club ULM Évasion</h3>
                <p class="location">Maubeuge</p>
            </div>
            
            <h2>Créer un compte</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo h($success); ?>
                    <br><a href="login.php">Se connecter maintenant</a>
                </div>
            <?php else: ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" required 
                                   value="<?php echo h($_POST['prenom'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required 
                                   value="<?php echo h($_POST['nom'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo h($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" 
                               value="<?php echo h($_POST['telephone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="photo">Photo de profil</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="form-help">JPG, PNG, GIF ou WEBP - Max 5 Mo</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input type="password" id="password" name="password" required minlength="8">
                        <small class="form-help">Au moins 8 caractères</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirmer le mot de passe *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">S'inscrire</button>
                </form>
            <?php endif; ?>
            
            <div class="auth-footer">
                <p class="auth-link">
                    Déjà inscrit ? <a href="login.php">Se connecter</a>
                </p>
                <p class="auth-home">
                    <a href="../index.php">← Retour à l'accueil</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
