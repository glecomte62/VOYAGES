<?php
/**
 * Configuration générale de l'application
 */

// Environnement (development, production)
define('ENVIRONMENT', 'development');

// URL de base de l'application
define('BASE_URL', 'http://localhost/VOYAGES/');

// Chemin racine du projet
define('ROOT_PATH', dirname(__DIR__));

// Configuration des erreurs
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration de session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mettre à 1 en HTTPS

// Durée de session (24 heures)
ini_set('session.gc_maxlifetime', 86400);
