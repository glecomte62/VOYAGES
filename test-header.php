<?php
/**
 * Test de visibilité du menu et de la session
 */
require_once 'includes/session.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html><html><head><title>Test Header</title></head><body style='font-family: sans-serif; padding: 2rem;'>";
echo "<h1>🔍 Test de diagnostic du menu</h1>";

echo "<h2>État de la session :</h2>";
echo "<ul>";
echo "<li>Session démarrée : " . (session_status() === PHP_STATUS_ACTIVE ? "✅ OUI" : "❌ NON") . "</li>";
echo "<li>User ID : " . ($_SESSION['user_id'] ?? '❌ Non défini') . "</li>";
echo "<li>Email : " . ($_SESSION['email'] ?? '❌ Non défini') . "</li>";
echo "<li>isLoggedIn() : " . (isLoggedIn() ? "✅ TRUE" : "❌ FALSE") . "</li>";
echo "<li>isAdmin() : " . (isAdmin() ? "✅ TRUE" : "❌ FALSE") . "</li>";
echo "</ul>";

echo "<h2>Contenu de \$_SESSION :</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Test du menu :</h2>";
if (isLoggedIn()) {
    echo "<p style='color: green; font-weight: bold;'>✅ Vous êtes connecté, le menu 'Mes Voyages' DEVRAIT être visible</p>";
    echo "<p>Si vous ne le voyez pas, c'est peut-être un problème de cache navigateur.</p>";
    echo "<p><strong>Solutions :</strong></p>";
    echo "<ol>";
    echo "<li>Faire Ctrl+F5 (ou Cmd+Shift+R sur Mac) pour rafraîchir sans cache</li>";
    echo "<li>Vider le cache du navigateur</li>";
    echo "<li>Ouvrir en navigation privée</li>";
    echo "</ol>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Vous n'êtes PAS connecté, le menu 'Mes Voyages' ne sera pas visible</p>";
    echo "<p><a href='pages/login.php' style='padding: 0.5rem 1rem; background: #0ea5e9; color: white; text-decoration: none; border-radius: 8px;'>Se connecter</a></p>";
}

echo "<hr>";
echo "<h2>Inclusion du header actuel :</h2>";
include 'includes/header.php';

echo "</body></html>";
?>
