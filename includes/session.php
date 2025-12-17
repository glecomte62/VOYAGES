<?php
/**
 * Initialisation de la session avec configuration sécurisée
 * À inclure au début de chaque page AVANT session_start()
 */

// Configuration de session (doit être fait AVANT session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mettre à 1 en HTTPS
ini_set('session.gc_maxlifetime', 86400); // 24 heures

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
