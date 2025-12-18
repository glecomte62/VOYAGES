<?php
/**
 * Gestion des favoris de destination (AJAX)
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit;
}

$action = $_POST['action'] ?? '';
$destination_id = $_POST['destination_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$destination_id || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

try {
    if ($action === 'add') {
        // Vérifier que la destination existe
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) as count FROM destinations WHERE id = ? AND actif = 1");
        $stmtCheck->execute([$destination_id]);
        $destExists = $stmtCheck->fetch()['count'] > 0;
        
        if (!$destExists) {
            echo json_encode(['success' => false, 'error' => 'Destination introuvable']);
            exit;
        }
        
        // Ajouter aux favoris (ignore si déjà présent grâce à UNIQUE KEY)
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO favoris (user_id, destination_id, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$user_id, $destination_id]);
        
        echo json_encode(['success' => true, 'message' => 'Destination ajoutée aux favoris']);
        
    } elseif ($action === 'remove') {
        // Retirer des favoris
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE user_id = ? AND destination_id = ?");
        $stmt->execute([$user_id, $destination_id]);
        
        echo json_encode(['success' => true, 'message' => 'Destination retirée des favoris']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données : ' . $e->getMessage()]);
}
