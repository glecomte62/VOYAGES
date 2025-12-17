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
