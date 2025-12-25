<?php
require_once '../includes/session.php';
require_once '../config/smtp.php';

// Vérification simple - décommenter après le test
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     die('Accès réservé aux administrateurs');
// }

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $msg = 'Email invalide';
        $msgType = 'error';
    } else {
        $subject = 'Test SMTP - ' . SITE_NAME;
        $body = '<h1>✅ Test réussi!</h1><p>Serveur: ' . SMTP_HOST . ':' . SMTP_PORT . '</p>';
        $headers = "From: " . SMTP_FROM_EMAIL . "\r\nContent-Type: text/html; charset=UTF-8";
        
        if (mail($email, $subject, $body, $headers)) {
            $msg = 'Email envoyé avec succès à ' . htmlspecialchars($email);
            $msgType = 'success';
        } else {
            $msg = 'Erreur lors de l\'envoi';
            $msgType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SMTP</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #0ea5e9; }
        .config { background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .config p { margin: 8px 0; }
        .form { background: white; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; }
        input[type="email"] { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px; }
        button { background: #0ea5e9; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background: #0284c7; }
        .alert { padding: 15px; border-radius: 6px; margin: 20px 0; }
        .success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
    <h1>📧 Test SMTP</h1>
    
    <?php if ($msg): ?>
        <div class="alert <?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>
    
    <div class="config">
        <h3>Configuration actuelle</h3>
        <p><strong>Serveur:</strong> <?= htmlspecialchars(SMTP_HOST) ?></p>
        <p><strong>Port:</strong> <?= htmlspecialchars(SMTP_PORT) ?></p>
        <p><strong>Sécurité:</strong> <?= htmlspecialchars(SMTP_SECURE) ?></p>
        <p><strong>Expéditeur:</strong> <?= htmlspecialchars(SMTP_FROM_EMAIL) ?></p>
    </div>
    
    <div class="form">
        <h3>Envoyer un email de test</h3>
        <form method="POST">
            <label for="email">Adresse email de destination:</label><br>
            <input type="email" id="email" name="email" required placeholder="votre@email.com" value="<?= isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '' ?>">
            <button type="submit" name="send">📤 Envoyer le test</button>
        </form>
    </div>
    
    <p style="margin-top: 30px;"><a href="admin.php">← Retour à l'administration</a></p>
</body>
</html>
