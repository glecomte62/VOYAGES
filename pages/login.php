<?php
/**
 * Page de connexion
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, email, password, nom, prenom FROM users WHERE email = ? AND actif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            
            // Mise à jour de la dernière connexion
            $stmt = $pdo->prepare("UPDATE users SET derniere_connexion = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - VOYAGES ULM</title>
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
            
            <h2>Connexion</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">📧 Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="votre.email@exemple.com"
                           value="<?php echo h($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">🔒 Mot de passe</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Votre mot de passe">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    Se connecter
                </button>
            </form>
            
            <div class="auth-footer">
                <p class="auth-link">
                    Pas encore inscrit ? <a href="register.php">Créer un compte</a>
                </p>
                <p class="auth-home">
                    <a href="../index.php">← Retour à l'accueil</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
