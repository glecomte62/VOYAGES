<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
requireAdmin('../index.php');
require_once '../config/smtp.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if ($email) {
        $headers = "From: " . SMTP_FROM_EMAIL . "\r\nContent-Type: text/html; charset=UTF-8";
        if (mail($email, 'Test SMTP', '<h1>Test OK</h1>', $headers)) {
            $msg = 'Envoyé!';
        } else {
            $msg = 'Erreur';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Test SMTP</title></head>
<body>
<h1>Test SMTP</h1>
<p>Config: <?= SMTP_HOST ?>:<?= SMTP_PORT ?></p>
<?php if($msg): ?><p><?= $msg ?></p><?php endif; ?>
<form method="POST">
<input type="email" name="email" required>
<button type="submit">Envoyer</button>
</form>
<a href="admin.php">Retour</a>
</body>
</html>
