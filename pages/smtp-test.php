<?php
// Configuration directe dans la page
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587;
$SMTP_FROM_EMAIL = 'notifications@clubulmevasion.fr';
$SMTP_FROM_NAME = 'VOYAGES ULM';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $msg = 'Email invalide';
        $msgType = 'error';
    } else {
        $subject = 'Test SMTP';
        $body = '<h1>Test réussi!</h1><p>Serveur: ' . htmlspecialchars($SMTP_HOST) . ':' . $SMTP_PORT . '</p>';
        $headers = "From: " . $SMTP_FROM_EMAIL . "\r\nContent-Type: text/html; charset=UTF-8";
        
        if (mail($email, $subject, $body, $headers)) {
            $msg = 'Email envoyé à ' . htmlspecialchars($email);
            $msgType = 'success';
        } else {
            $msg = 'Erreur d\'envoi';
            $msgType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test SMTP</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        h1 { color: #0ea5e9; }
        .msg { padding: 15px; border-radius: 6px; margin: 20px 0; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
        input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; margin: 10px 0; }
        button { background: #0ea5e9; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>📧 Test SMTP</h1>
    
    <?php if ($msg): ?>
        <div class="msg <?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>
    
    <p>Serveur: <?= htmlspecialchars($SMTP_HOST) ?>:<?= $SMTP_PORT ?></p>
    
    <form method="POST">
        <input type="email" name="email" placeholder="votre@email.com" required>
        <button type="submit" name="send">Envoyer</button>
    </form>
</body>
</html>
