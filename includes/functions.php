<?php
/**
 * Fonctions utilitaires globales
 */

/**
 * Sécurise une chaîne pour affichage HTML
 * @param string $string
 * @return string
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige vers une URL
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est administrateur
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Récupère le rôle de l'utilisateur connecté
 * @return string|null
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Redirige si l'utilisateur n'est pas admin
 * @param string $redirectUrl URL de redirection si non admin
 */
function requireAdmin($redirectUrl = '../index.php') {
    if (!isAdmin()) {
        $_SESSION['error'] = "Accès refusé. Vous devez être administrateur.";
        redirect($redirectUrl);
    }
}

/**
 * Formate une date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * Génère un token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
