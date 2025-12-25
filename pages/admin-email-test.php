<?php
/**
 * Page de test SMTP
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../config/smtp.php';

requireAdmin('../index.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $testEmail = filter_input(INPUT_POST, 'test_email', FILTER_VALIDATE_EMAIL);
    
    if (!$testEmail) {
        $message = 'Email invalide';
        $messageType = 'error';
    } else {
        $subject = 'Test SMTP - ' . SITE_NAME;
        $body = '<h1>Test réussi!</h1><p>Configuration SMTP : ' . SMTP_HOST . ':' . SMTP_PORT . '</p>';
        
        $headers = [];
        $headers[] = 'From: ' . SMTP_FROM_EMAIL;
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        
        if (mail($testEmail, $subject, $body, implode("\r\n", $headers))) {
            $message = 'Email envoyé à ' . htmlspecialchars($testEmail);
            $messageType = 'success';
        } else {
            $message = 'Erreur d\'envoi';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test SMTP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>
    <main style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
        <a href="admin.php">← Retour</a>
        <h1>📧 Test SMTP</h1>
        
        <?php if ($message): ?>
            <div style="padding: 1rem; margin: 1rem 0; border-radius: 6px; <?= $messageType === 'success' ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
            <h3>Configuration</h3>
            <p>Serveur: <?= h(SMTP_HOST) ?>:<?= h(SMTP_PORT) ?></p>
            <p>De: <?= h(SMTP_FROM_EMAIL) ?></p>
        </div>
        
        <form method="POST" style="background: white; padding: 2rem; border-radius: 8px; border: 1px solid #e2e8f0;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email de test:</label>
            <input type="email" name="test_email" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 1rem;">
            <button type="submit" name="send_test" style="background: #0ea5e9; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer;">Envoyer</button>
        </form>
    </main>
</body>
</html>
