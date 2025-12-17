<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Test de la session et du rôle admin
 */

require_once 'includes/session.php';

echo "<h1>Test Session Admin</h1>";
echo "<h2>Contenu de la session:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p>user_role dans session: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'NON DÉFINI') . "</p>";

if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, email, nom, prenom, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    echo "<h2>Données utilisateur en base:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
}
