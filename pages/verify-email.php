<?php
/**
 * Page de vérification d'email
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/mail.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token d\'activation manquant';
} else {
    $pdo = getDBConnection();
    
    // Récupérer l'utilisateur avec ce token
    $stmt = $pdo->prepare("
        SELECT id, email, nom, prenom, email_verified, activation_token_expires 
        FROM users 
        WHERE activation_token = ? AND actif = 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = 'Token d\'activation invalide ou compte déjà activé';
    } elseif ($user['email_verified'] == 1) {
        $success = 'Votre compte est déjà activé ! Vous pouvez vous connecter.';
    } elseif (strtotime($user['activation_token_expires']) < time()) {
        $error = 'Ce lien d\'activation a expiré. Veuillez contacter un administrateur pour réactiver votre compte.';
    } else {
        // Activer le compte
        $stmtUpdate = $pdo->prepare("
            UPDATE users 
            SET email_verified = 1, 
                activation_token = NULL, 
                activation_token_expires = NULL 
            WHERE id = ?
        ");
        
        if ($stmtUpdate->execute([$user['id']])) {
            // Envoyer l'email de bienvenue
            sendWelcomeEmail($user['email'], $user['nom'], $user['prenom']);
            
            $success = 'Votre compte a été activé avec succès ! 🎉 Vous pouvez maintenant vous connecter et profiter de toutes les fonctionnalités.';
        } else {
            $error = 'Erreur lors de l\'activation du compte. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation du compte - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .activation-card {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .icon-large {
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }
        
        .success-icon {
            color: #10b981;
        }
        
        .error-icon {
            color: #ef4444;
        }
        
        .activation-card h1 {
            color: #0c4a6e;
            margin-bottom: 1rem;
        }
        
        .activation-card p {
            color: #64748b;
            font-size: 1.125rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3);
        }
        
        .error-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .success-box {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            color: #065f46;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: left;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container" style="padding-top: 5rem;">
        <div class="activation-card">
            <?php if ($error): ?>
                <div class="icon-large error-icon">❌</div>
                <h1>Activation impossible</h1>
                <div class="error-box">
                    <?php echo h($error); ?>
                </div>
                <p>Si le problème persiste, contactez-nous à <a href="mailto:contact@votredomaine.com">contact@votredomaine.com</a></p>
                <a href="login.php" class="btn-primary">Retour à la connexion</a>
                
            <?php elseif ($success): ?>
                <div class="icon-large success-icon">✅</div>
                <h1>Compte activé !</h1>
                <div class="success-box">
                    <?php echo h($success); ?>
                </div>
                <p>Vous allez également recevoir un email de bienvenue avec toutes les informations pour bien démarrer.</p>
                <a href="login.php" class="btn-primary">Se connecter maintenant →</a>
                
            <?php else: ?>
                <div class="icon-large">⏳</div>
                <h1>Vérification en cours...</h1>
                <p>Veuillez patienter pendant que nous activons votre compte.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <footer class="main-footer" style="margin-top: 4rem;">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>✈️ VOYAGES ULM</h4>
                    <p>Plateforme communautaire pour pilotes ULM et petit avion</p>
                </div>
                <div class="footer-section">
                    <h4>Club ULM Évasion</h4>
                    <p>Maubeuge</p>
                    <p>Application développée pour la communauté ULM</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> VOYAGES ULM - Tous droits réservés</p>
            </div>
        </div>
    </footer>
</body>
</html>
