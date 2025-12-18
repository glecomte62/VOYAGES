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
        $stmt = $pdo->prepare("SELECT id, email, password, nom, prenom, role FROM users WHERE email = ? AND actif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            
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
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #14b8a6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 2rem 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 480px;
        }
        
        .login-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            padding: 3rem 2rem;
            text-align: center;
            color: white;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .login-logo svg {
            width: 60px;
            height: 60px;
        }
        
        .login-header h1 {
            margin: 0 0 0.5rem;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .login-header p {
            margin: 0;
            opacity: 0.95;
            font-size: 1rem;
        }
        
        .club-banner {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            padding: 1rem;
            text-align: center;
            color: white;
        }
        
        .club-banner p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
        }
        
        .club-banner h3 {
            margin: 0.25rem 0;
            font-size: 1.125rem;
            font-weight: 700;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
        }
        
        .login-title {
            margin: 0 0 2rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            text-align: center;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #334155;
            font-size: 0.875rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(6, 182, 212, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            padding: 1.5rem 2rem 2rem;
            background: #f8fafc;
            text-align: center;
        }
        
        .login-footer p {
            margin: 0.5rem 0;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .login-footer a {
            color: #06b6d4;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-footer a:hover {
            color: #0ea5e9;
            text-decoration: underline;
        }
        
        .divider {
            margin: 1.5rem 0;
            text-align: center;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            position: relative;
            background: white;
            padding: 0 1rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        @media (max-width: 640px) {
            .login-card {
                border-radius: 16px;
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="75" cy="25" r="12" fill="#fbbf24" opacity="0.9"/>
                        <circle cx="75" cy="25" r="8" fill="#fcd34d"/>
                        <path d="M20 45 Q15 40 20 35 Q25 32 30 35 Q35 30 40 35 Q45 40 40 45 Z" fill="#e0f2fe" opacity="0.8"/>
                        <g transform="translate(35, 50)">
                            <ellipse cx="0" cy="0" rx="25" ry="8" fill="url(#planeGradient)"/>
                            <path d="M-15 0 L-35 -15 L-30 -12 L-12 2 Z" fill="url(#wingGradient)"/>
                            <path d="M-15 0 L-35 15 L-30 12 L-12 -2 Z" fill="url(#wingGradient)"/>
                            <path d="M20 -2 L30 -8 L28 0 L30 8 L20 2 Z" fill="url(#tailGradient)"/>
                            <ellipse cx="10" cy="0" rx="8" ry="5" fill="rgba(255,255,255,0.4)"/>
                            <circle cx="12" cy="0" r="3" fill="rgba(14,165,233,0.3)"/>
                        </g>
                        <path d="M10 50 Q20 48 30 50" stroke="#06b6d4" stroke-width="2" stroke-linecap="round" opacity="0.5" fill="none"/>
                        <circle cx="12" cy="50" r="2" fill="#06b6d4" opacity="0.6"/>
                        <defs>
                            <linearGradient id="planeGradient" x1="0" y1="-8" x2="0" y2="8">
                                <stop offset="0%" stop-color="#0ea5e9"/>
                                <stop offset="100%" stop-color="#06b6d4"/>
                            </linearGradient>
                            <linearGradient id="wingGradient" x1="-35" y1="0" x2="-12" y2="0">
                                <stop offset="0%" stop-color="#0ea5e9"/>
                                <stop offset="100%" stop-color="#06b6d4"/>
                            </linearGradient>
                            <linearGradient id="tailGradient" x1="20" y1="0" x2="30" y2="0">
                                <stop offset="0%" stop-color="#06b6d4"/>
                                <stop offset="100%" stop-color="#14b8a6"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <h1>VOYAGES ULM</h1>
                <p>Partagez vos aventures aériennes</p>
            </div>
            
            <div class="club-banner">
                <p>🏛️ Application du</p>
                <h3>Club ULM Évasion</h3>
                <p>Maubeuge</p>
            </div>
            
            <div class="login-body">
                <h2 class="login-title">Connexion</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span style="font-size: 1.25rem;">⚠️</span>
                        <span><?php echo h($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📧</span>
                            <input type="email" id="email" name="email" required 
                                   placeholder="votre.email@exemple.com"
                                   value="<?php echo h($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="password" name="password" required
                                   placeholder="Votre mot de passe">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        Se connecter ✈️
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <p>
                    Pas encore inscrit ? 
                    <a href="register.php">Créer un compte</a>
                </p>
                <div class="divider">
                    <span>ou</span>
                </div>
                <p>
                    <a href="../index.php">← Retour à l'accueil</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
