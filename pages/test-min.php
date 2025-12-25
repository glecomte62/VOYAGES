<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1 OK<br>";

require_once '../includes/session.php';
echo "Test 2 - session OK<br>";

require_once '../config/database.php';
echo "Test 3 - database OK<br>";

require_once '../includes/functions.php';
echo "Test 4 - functions OK<br>";

requireAdmin('../index.php');
echo "Test 5 - requireAdmin OK<br>";

require_once '../config/smtp.php';
echo "Test 6 - smtp OK<br>";
echo "SMTP_HOST = " . SMTP_HOST . "<br>";

echo "<h2>Tout fonctionne!</h2>";
