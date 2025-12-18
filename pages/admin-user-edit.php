<?php
/**
 * Administration - Édition d'un utilisateur
 */

require_once '../includes/session.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireAdmin();

$pdo = getDBConnection();
$success = '';
$error = '';

// Récupérer l'ID de l'utilisateur
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    header('Location: admin-users.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: admin-users.php');
    exit;
}

// Récupérer les clubs de l'utilisateur
$stmt = $pdo->prepare("
    SELECT c.id, c.nom, c.ville 
    FROM clubs c
    INNER JOIN membres_clubs mc ON c.id = mc.club_id
    WHERE mc.user_id = ?
");
$stmt->execute([$userId]);
$userClubs = $stmt->fetchAll();

// Récupérer tous les clubs pour la sélection
$stmt = $pdo->query("SELECT id, nom, ville FROM clubs ORDER BY nom");
$allClubs = $stmt->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        // Validation
        if (empty($nom) || empty($prenom) || empty($email)) {
            $error = 'Le nom, prénom et email sont obligatoires';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide';
        } else {
            // Vérifier si l'email existe déjà pour un autre utilisateur
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $error = 'Cet email est déjà utilisé par un autre utilisateur';
            } else {
                // Mise à jour
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nom = ?, prenom = ?, email = ?, telephone = ?, role = ?, actif = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$nom, $prenom, $email, $telephone, $role, $actif, $userId])) {
                    // Log de l'opération
                    logOperation($_SESSION['user_id'], 'UPDATE', 'users', $userId, 
                        "Modification utilisateur: $prenom $nom ($email)");
                    
                    $success = 'Utilisateur modifié avec succès';
                    
                    // Recharger les données
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                } else {
                    $error = 'Erreur lors de la mise à jour';
                }
            }
        }
    } elseif ($action === 'change_password') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Veuillez remplir tous les champs';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Les mots de passe ne correspondent pas';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashedPassword, $userId])) {
                logOperation($_SESSION['user_id'], 'UPDATE', 'users', $userId, 
                    "Réinitialisation mot de passe");
                $success = 'Mot de passe modifié avec succès';
            } else {
                $error = 'Erreur lors de la modification du mot de passe';
            }
        }
    } elseif ($action === 'update_clubs') {
        $selectedClubs = $_POST['clubs'] ?? [];
        
        try {
            // Supprimer toutes les associations actuelles
            $stmt = $pdo->prepare("DELETE FROM membres_clubs WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Ajouter les nouvelles associations
            $stmt = $pdo->prepare("INSERT INTO membres_clubs (user_id, club_id) VALUES (?, ?)");
            foreach ($selectedClubs as $clubId) {
                $stmt->execute([$userId, $clubId]);
            }
            
            logOperation($_SESSION['user_id'], 'UPDATE', 'membres_clubs', $userId, 
                "Modification clubs utilisateur (" . count($selectedClubs) . " clubs)");
            
            $success = 'Clubs mis à jour avec succès';
            
            // Recharger les clubs
            $stmt = $pdo->prepare("
                SELECT c.id, c.nom, c.ville 
                FROM clubs c
                INNER JOIN membres_clubs mc ON c.id = mc.club_id
                WHERE mc.user_id = ?
            ");
            $stmt->execute([$userId]);
            $userClubs = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $error = 'Erreur lors de la mise à jour des clubs';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer <?php echo h($user['prenom'] . ' ' . $user['nom']); ?> - Admin VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/page-header.css">
    <style>
        .edit-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .edit-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .edit-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        
        .edit-card.full-width {
            grid-column: 1 / -1;
        }
        
        .edit-card h3 {
            margin-top: 0;
            color: #374151;
            border-bottom: 2px solid #fbbf24;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .clubs-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .club-item {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .club-item:hover {
            background: #f9fafb;
        }
        
        .club-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #fbbf24, #84cc16);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .user-info-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .user-photo-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .user-photo-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-placeholder-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fbbf24, #84cc16);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .user-info-text h2 {
            margin: 0;
            color: #374151;
        }
        
        .user-info-text p {
            margin: 0.25rem 0 0 0;
            color: #6b7280;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .badge-admin {
            background: #fbbf24;
            color: #78350f;
        }
        
        .badge-user {
            background: #bfdbfe;
            color: #1e40af;
        }
        
        @media (max-width: 768px) {
            .edit-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container" style="padding-top: 6rem;">
        <div class="page-header">
            <h1 class="page-title">✏️ Éditer un utilisateur</h1>
        </div>

        <div class="edit-container">
            <div style="margin-bottom: 1rem;">
                <a href="admin-users.php" class="btn btn-secondary">← Retour à la liste</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ <?php echo h($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ✗ <?php echo h($error); ?>
                </div>
            <?php endif; ?>

            <!-- En-tête utilisateur -->
            <div class="user-info-header">
                <?php if ($user['photo']): ?>
                    <div class="user-photo-large">
                        <img src="../uploads/photos/<?php echo h($user['photo']); ?>" alt="">
                    </div>
                <?php else: ?>
                    <div class="photo-placeholder-large">
                        <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="user-info-text">
                    <h2>
                        <?php echo h($user['prenom'] . ' ' . $user['nom']); ?>
                        <span class="badge badge-<?php echo h($user['role']); ?>">
                            <?php echo $user['role'] === 'admin' ? 'Administrateur' : 'Utilisateur'; ?>
                        </span>
                    </h2>
                    <p><?php echo h($user['email']); ?></p>
                    <p style="font-size: 0.875rem;">
                        Inscrit le <?php echo formatDate($user['created_at'], 'd/m/Y'); ?>
                        <?php if ($user['derniere_connexion']): ?>
                            • Dernière connexion : <?php echo formatDate($user['derniere_connexion'], 'd/m/Y H:i'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Formulaires d'édition -->
            <div class="edit-grid">
                <!-- Informations personnelles -->
                <div class="edit-card">
                    <h3>👤 Informations personnelles</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="update">
                        
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" class="form-control" 
                                   value="<?php echo h($user['nom']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" class="form-control" 
                                   value="<?php echo h($user['prenom']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo h($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" class="form-control" 
                                   value="<?php echo h($user['telephone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Rôle</label>
                            <select id="role" name="role" class="form-control">
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="actif" name="actif" 
                                       <?php echo $user['actif'] ? 'checked' : ''; ?>>
                                <label for="actif" style="margin: 0;">Compte actif</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">💾 Enregistrer les modifications</button>
                    </form>
                </div>

                <!-- Mot de passe -->
                <div class="edit-card">
                    <h3>🔒 Modifier le mot de passe</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" minlength="6" required>
                            <small style="color: #6b7280;">Minimum 6 caractères</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" minlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">🔑 Changer le mot de passe</button>
                    </form>
                </div>

                <!-- Clubs -->
                <div class="edit-card full-width">
                    <h3>🏛️ Clubs affiliés</h3>
                    
                    <?php if (!empty($userClubs)): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <strong>Clubs actuels :</strong>
                            <div style="margin-top: 0.5rem;">
                                <?php foreach ($userClubs as $club): ?>
                                    <span style="display: inline-block; background: #e0f2fe; padding: 0.5rem 1rem; border-radius: 8px; margin-right: 0.5rem; margin-bottom: 0.5rem;">
                                        <?php echo h($club['nom']); ?>
                                        <?php if ($club['ville']): ?>
                                            <small style="color: #6b7280;">(<?php echo h($club['ville']); ?>)</small>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="color: #6b7280; margin-bottom: 1.5rem;">Aucun club associé</p>
                    <?php endif; ?>
                    
                    <form method="post">
                        <input type="hidden" name="action" value="update_clubs">
                        
                        <div class="form-group">
                            <label>Sélectionner les clubs</label>
                            <div class="clubs-list">
                                <?php 
                                $userClubIds = array_column($userClubs, 'id');
                                foreach ($allClubs as $club): 
                                    $isChecked = in_array($club['id'], $userClubIds);
                                ?>
                                    <div class="club-item">
                                        <input type="checkbox" name="clubs[]" value="<?php echo $club['id']; ?>" 
                                               id="club_<?php echo $club['id']; ?>" 
                                               <?php echo $isChecked ? 'checked' : ''; ?>>
                                        <label for="club_<?php echo $club['id']; ?>" style="margin: 0; cursor: pointer;">
                                            <?php echo h($club['nom']); ?>
                                            <?php if ($club['ville']): ?>
                                                <small style="color: #6b7280;">(<?php echo h($club['ville']); ?>)</small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">🏛️ Mettre à jour les clubs</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
