<?php
/**
 * Initialisation de la session avec configuration sécurisée
 * À inclure au début de chaque page AVANT session_start()
 */

// Configuration de session (doit être fait AVANT session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
ini_set('session.gc_maxlifetime', 86400); // 24 heures

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fonction pour forcer la connexion
 * Redirige vers la page de login si l'utilisateur n'est pas connecté
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        // Déterminer le chemin vers login.php selon le répertoire actuel
        $loginPath = 'pages/login.php';
        
        // Si on est déjà dans le dossier pages/, utiliser un chemin relatif
        if (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
            $loginPath = 'login.php';
        }
        
        // Stocker l'URL demandée pour redirection après login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $loginPath);
        exit;
    }
}
