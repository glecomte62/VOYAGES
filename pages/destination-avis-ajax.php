<?php
/**
 * Gestion des avis de destination (AJAX)
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

$action = $_POST['action'] ?? 'add';
$user_id = $_SESSION['user_id'];

// Gestion de la suppression d'avis
if ($action === 'delete') {
    $avis_id = $_POST['avis_id'] ?? null;
    
    if (!$avis_id) {
        echo json_encode(['success' => false, 'error' => 'ID avis manquant']);
        exit;
    }
    
    try {
        // Vérifier que l'avis existe et que l'utilisateur a le droit de le supprimer
        $stmtCheck = $pdo->prepare("SELECT user_id FROM avis_destinations WHERE id = ?");
        $stmtCheck->execute([$avis_id]);
        $avis = $stmtCheck->fetch();
        
        if (!$avis) {
            echo json_encode(['success' => false, 'error' => 'Avis introuvable']);
            exit;
        }
        
        // Vérifier les permissions : propriétaire ou admin
        if ($avis['user_id'] != $user_id && !isAdmin()) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé à supprimer cet avis']);
            exit;
        }
        
        // Supprimer l'avis
        $stmtDelete = $pdo->prepare("DELETE FROM avis_destinations WHERE id = ?");
        $stmtDelete->execute([$avis_id]);
        
        echo json_encode(['success' => true, 'message' => 'Avis supprimé avec succès']);
        exit;
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur base de données : ' . $e->getMessage()]);
        exit;
    }
}

// Gestion de l'ajout/modification d'avis
$destination_id = $_POST['destination_id'] ?? null;
$note = $_POST['note'] ?? null;
$titre = trim($_POST['titre'] ?? '');
$commentaire = trim($_POST['commentaire'] ?? '');
$date_visite = $_POST['date_visite'] ?? null;
$user_id = $_SESSION['user_id'];

// Validation
if (!$destination_id || !$note || empty($commentaire)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

if ($note < 1 || $note > 5) {
    echo json_encode(['success' => false, 'error' => 'Note invalide (1-5)']);
    exit;
}

try {
    // Vérifier que la destination existe
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) as count FROM destinations WHERE id = ? AND actif = 1");
    $stmtCheck->execute([$destination_id]);
    $destExists = $stmtCheck->fetch()['count'] > 0;
    
    if (!$destExists) {
        echo json_encode(['success' => false, 'error' => 'Destination introuvable']);
        exit;
    }
    
    // Vérifier si l'utilisateur a déjà laissé un avis
    $stmtExisting = $pdo->prepare("SELECT COUNT(*) as count FROM avis_destinations WHERE user_id = ? AND destination_id = ?");
    $stmtExisting->execute([$user_id, $destination_id]);
    $hasAvis = $stmtExisting->fetch()['count'] > 0;
    
    if ($hasAvis) {
        // Mettre à jour l'avis existant
        $stmt = $pdo->prepare("
            UPDATE avis_destinations 
            SET note = ?, titre = ?, commentaire = ?, date_visite = ?, updated_at = NOW()
            WHERE user_id = ? AND destination_id = ?
        ");
        $stmt->execute([
            $note,
            $titre ?: null,
            $commentaire,
            $date_visite ?: null,
            $user_id,
            $destination_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Avis mis à jour']);
    } else {
        // Créer un nouvel avis
        $stmt = $pdo->prepare("
            INSERT INTO avis_destinations (destination_id, user_id, note, titre, commentaire, date_visite, recommande, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $destination_id,
            $user_id,
            $note,
            $titre ?: null,
            $commentaire,
            $date_visite ?: null,
            $note >= 3 ? 1 : 0  // Recommandé si note >= 3
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Avis publié avec succès']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données : ' . $e->getMessage()]);
}
