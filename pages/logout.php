<?php
/**
 * Déconnexion
 */

require_once '../includes/session.php';

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si il existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header('Location: ../index.php');
exit;
