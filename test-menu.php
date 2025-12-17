<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/session.php';
require_once 'includes/functions.php';

echo "<h1>Test Menu Admin</h1>";

echo "<h2>Session:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Fonctions disponibles:</h2>";
echo "<p>function_exists('isLoggedIn'): " . (function_exists('isLoggedIn') ? 'OUI' : 'NON') . "</p>";
echo "<p>function_exists('isAdmin'): " . (function_exists('isAdmin') ? 'OUI' : 'NON') . "</p>";

echo "<h2>Tests:</h2>";
echo "<p>isLoggedIn(): " . (isLoggedIn() ? 'OUI' : 'NON') . "</p>";
echo "<p>isAdmin(): " . (isAdmin() ? 'OUI' : 'NON') . "</p>";

echo "<h2>Header inclus:</h2>";
include 'includes/header.php';
