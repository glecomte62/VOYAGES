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

/**
 * Upload une photo de profil
 * @param array $file Fichier uploadé ($_FILES['photo'])
 * @param string $uploadDir Répertoire d'upload (par défaut uploads/photos/)
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadPhoto($file, $uploadDir = '../uploads/photos/') {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    // Vérifier si un fichier a été uploadé
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $result; // Pas d'erreur si pas de fichier
    }
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Erreur lors de l\'upload du fichier';
        return $result;
    }
    
    // Vérifier la taille (max 5Mo)
    if ($file['size'] > 5 * 1024 * 1024) {
        $result['error'] = 'La photo ne doit pas dépasser 5 Mo';
        return $result;
    }
    
    // Vérifier le type MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $result['error'] = 'Format de photo non autorisé (JPG, PNG, GIF, WEBP uniquement)';
        return $result;
    }
    
    // Créer le répertoire si nécessaire
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('photo_') . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Redimensionner l'image
        if (resizeImage($filepath, 400, 400)) {
            $result['success'] = true;
            $result['filename'] = $filename;
        } else {
            $result['error'] = 'Erreur lors du redimensionnement de l\'image';
            unlink($filepath); // Supprimer le fichier
        }
    } else {
        $result['error'] = 'Erreur lors de l\'enregistrement du fichier';
    }
    
    return $result;
}

/**
 * Redimensionne une image
 * @param string $filepath Chemin du fichier
 * @param int $maxWidth Largeur maximale
 * @param int $maxHeight Hauteur maximale
 * @return bool
 */
function resizeImage($filepath, $maxWidth = 400, $maxHeight = 400) {
    $imageInfo = getimagesize($filepath);
    if (!$imageInfo) return false;
    
    list($width, $height, $type) = $imageInfo;
    
    // Calculer les nouvelles dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    if ($ratio >= 1) return true; // Pas besoin de redimensionner
    
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Créer l'image source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            return false;
    }
    
    // Créer l'image de destination
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Préserver la transparence pour PNG et GIF
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Redimensionner
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Sauvegarder
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($destination, $filepath, 85);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($destination, $filepath, 8);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($destination, $filepath);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($destination, $filepath, 85);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($destination);
    
    return $success;
}

/**
 * Upload une photo de destination SANS redimensionnement (qualité maximale)
 * @param array $file Fichier uploadé ($_FILES)
 * @param string $uploadDir Répertoire de destination
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadDestinationPhoto($file, $uploadDir = '../uploads/destinations/') {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    // Vérifier si un fichier a été uploadé
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $result;
    }
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Erreur lors de l\'upload du fichier';
        return $result;
    }
    
    // Vérifier la taille (max 10Mo pour les photos de destinations)
    if ($file['size'] > 10 * 1024 * 1024) {
        $result['error'] = 'La photo ne doit pas dépasser 10 Mo';
        return $result;
    }
    
    // Vérifier le type MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $result['error'] = 'Format de photo non autorisé (JPG, PNG, GIF, WEBP uniquement)';
        return $result;
    }
    
    // Créer le répertoire si nécessaire
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('dest_') . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Déplacer le fichier SANS redimensionnement
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['error'] = 'Erreur lors de l\'enregistrement du fichier';
    }
    
    return $result;
}

/**
 * Supprime une photo de profil
 * @param string $filename Nom du fichier
 * @param string $uploadDir Répertoire d'upload
 * @return bool
 */
function deletePhoto($filename, $uploadDir = '../uploads/photos/') {
    if (empty($filename)) return false;
    
    $filepath = $uploadDir . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

