<?php
/**
 * Gestion des photos de destination (AJAX)
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Empêcher tout output avant le JSON
ob_start();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit;
}

$action = $_POST['action'] ?? '';
$destination_id = $_POST['destination_id'] ?? null;

if ($action === 'upload' && $destination_id) {
    // Upload de nouvelles photos
    if (isset($_FILES['photos'])) {
        $uploaded = [];
        $errors = [];
        
        try {
            $filesCount = count($_FILES['photos']['name']);
            
            for ($i = 0; $i < $filesCount; $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['photos']['name'][$i],
                        'type' => $_FILES['photos']['type'][$i],
                        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                        'error' => $_FILES['photos']['error'][$i],
                        'size' => $_FILES['photos']['size'][$i]
                    ];
                    
                    $result = uploadPhoto($file, '../uploads/destinations/');
                    
                    if ($result['success']) {
                        $legende = $_POST['legende'][$i] ?? '';
                        
                        // Insérer en base
                        $stmt = $pdo->prepare("
                            INSERT INTO destination_photos (destination_id, user_id, chemin_fichier, legende, ordre)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $ordre = $i;
                        $stmt->execute([$destination_id, $_SESSION['user_id'], $result['filename'], $legende, $ordre]);
                        
                        $uploaded[] = [
                            'id' => $pdo->lastInsertId(),
                            'filename' => $result['filename'],
                            'legende' => $legende
                        ];
                    } else {
                        $errors[] = $result['error'];
                    }
                }
            }
            
            ob_end_clean();
            echo json_encode([
                'success' => count($uploaded) > 0,
                'uploaded' => $uploaded,
                'errors' => $errors
            ]);
        } catch (PDOException $e) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Erreur base de données : ' . $e->getMessage()
            ]);
        }
    }
    
} elseif ($action === 'delete' && isset($_POST['photo_id'])) {
    // Supprimer une photo
    $photo_id = $_POST['photo_id'];
    
    if ($photo_id) {
        try {
            // Récupérer le nom du fichier
            $stmt = $pdo->prepare("SELECT chemin_fichier FROM destination_photos WHERE id = ?");
            $stmt->execute([$photo_id]);
            $photo = $stmt->fetch();
            
            if ($photo) {
                // Supprimer le fichier
                $filepath = '../uploads/destinations/' . $photo['chemin_fichier'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                // Supprimer de la base
                $stmt = $pdo->prepare("DELETE FROM destination_photos WHERE id = ?");
                $stmt->execute([$photo_id]);
                
                ob_end_clean();
                echo json_encode(['success' => true]);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'error' => 'Photo non trouvée']);
            }
        } catch (PDOException $e) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Erreur base de données : ' . $e->getMessage()]);
        }
    } else {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'ID photo manquant']);
    }
    
} else {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Action invalide']);
}
