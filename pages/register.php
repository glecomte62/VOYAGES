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
require_once '../includes/mail.php';

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
                
                // Générer un token d'activation
                $activation_token = bin2hex(random_bytes(32));
                $activation_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, nom, prenom, telephone, photo, email_verified, activation_token, activation_token_expires) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)
                ");
                
                if ($stmt->execute([$email, $password_hash, $nom, $prenom, $telephone, $photoFilename, $activation_token, $activation_expires])) {
                    // Envoyer l'email d'activation
                    if (sendActivationEmail($email, $nom, $prenom, $activation_token)) {
                        $success = 'Compte créé avec succès ! Un email d\'activation a été envoyé à ' . htmlspecialchars($email) . '. Veuillez vérifier votre boîte de réception (et vos spams) pour activer votre compte.';
                    } else {
                        $success = 'Compte créé avec succès ! Cependant, l\'email d\'activation n\'a pas pu être envoyé. Contactez un administrateur.';
                    }
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
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .hero-banner {
            text-align: center;
            margin-bottom: 3rem;
            background: linear-gradient(135deg, #fbbf24 0%, #84cc16 50%, #10b981 100%);
            padding: 3rem 2rem;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 30px rgba(251, 191, 36, 0.2);
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
        
        .auth-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .club-badge {
            text-align: left;
            padding: 1.5rem;
            background: linear-gradient(135deg, #fef3c7 0%, #d9f99d 100%);
            border-radius: 12px;
            margin-bottom: 2rem;
            border-left: 4px solid #84cc16;
        }
        
        .club-badge h3 {
            color: #065f46;
            font-size: 1.2rem;
            margin: 0 0 1rem 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .club-badge p {
            margin: 0.5rem 0;
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .club-badge strong {
            color: #065f46;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .form-help {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .btn-full {
            width: 100%;
            padding: 1rem;
            font-size: 1.125rem;
            border-radius: 8px;
            font-weight: 700;
            border: none;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-full:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3);
        }
        
        .auth-footer {
            text-align: center;
            padding: 1.5rem;
        }
        
        .auth-footer a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .success-message {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            color: #065f46;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container" style="padding-top: 5rem;">
        <div class="auth-card">
            <div class="club-badge">
                <h3>🤝 Rejoignez notre communauté ULM</h3>
                <p>Vos informations (nom, prénom, photo et clubs) seront <strong>partagées avec les autres membres</strong> de cette plateforme pour favoriser les échanges et créer une véritable cohésion au sein de notre communauté.</p>
                <p>Notre but est de <strong>rassembler tous les passionnés d'aviation légère</strong> autour de notre passion commune !</p>
                <p>💡 <strong>N'hésitez pas à ajouter une photo de vous</strong> : elle permet aux autres pilotes de vous identifier facilement. Si vous avez une question sur un terrain et que vous voyez qu'un membre y vole régulièrement via son club, ce sera plus simple de le contacter et d'échanger !</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo h($success); ?>
                    <br><br><a href="login.php" class="btn-full" style="display: inline-block; text-decoration: none; padding: 0.875rem 2rem; width: auto;">Se connecter maintenant →</a>
                </div>
            <?php else: ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" required 
                                   value="<?php echo h($_POST['prenom'] ?? ''); ?>" placeholder="Votre prénom">
                        </div>
                        
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required 
                                   value="<?php echo h($_POST['nom'] ?? ''); ?>" placeholder="Votre nom">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo h($_POST['email'] ?? ''); ?>" placeholder="votre@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" 
                               value="<?php echo h($_POST['telephone'] ?? ''); ?>" placeholder="06 12 34 56 78">
                    </div>
                    
                    <div class="form-group">
                        <label for="photo">Photo de profil</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="form-help">JPG, PNG, GIF ou WEBP - Max 5 Mo</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input type="password" id="password" name="password" required minlength="8" placeholder="Min. 8 caractères">
                        <small class="form-help">Au moins 8 caractères</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirmer le mot de passe *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8" placeholder="Confirmer votre mot de passe">
                    </div>
                    
                    <button type="submit" class="btn-full">Créer mon compte</button>
                </form>
            <?php endif; ?>
            
            <div class="auth-footer">
                <p>
                    Déjà inscrit ? <a href="login.php">Se connecter</a>
                </p>
                <p style="margin-top: 0.5rem;">
                    <a href="../index.php">← Retour à l'accueil</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
