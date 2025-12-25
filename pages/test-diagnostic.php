<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de diagnostic</h1>";

echo "<h2>1. PHP fonctionne</h2>";
echo "Version PHP : " . phpversion() . "<br>";

echo "<h2>2. Test chargement session.php</h2>";
try {
    require_once '../includes/session.php';
    echo "✅ session.php chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur session.php: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Test chargement config/smtp.php</h2>";
try {
    require_once '../config/smtp.php';
    echo "✅ smtp.php chargé<br>";
    echo "SMTP_HOST: " . SMTP_HOST . "<br>";
} catch (Exception $e) {
    echo "❌ Erreur smtp.php: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Test vendor/autoload.php</h2>";
$autoloadPath = '../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "✅ vendor/autoload.php existe<br>";
    try {
        require_once $autoloadPath;
        echo "✅ autoload chargé<br>";
        
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "✅ PHPMailer disponible<br>";
        } else {
            echo "❌ PHPMailer classe non trouvée<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur autoload: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ vendor/autoload.php n'existe pas<br>";
}

echo "<h2>5. Test chargement includes/mail.php</h2>";
try {
    require_once '../includes/mail.php';
    echo "✅ mail.php chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur mail.php: " . $e->getMessage() . "<br>";
}

echo "<h2>Diagnostic terminé</h2>";
