<?php
/**
 * VOYAGES - Rejoindre un club
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$club_id = $_GET['id'] ?? null;
$message = '';
$error = '';

if (!$club_id) {
    header('Location: clubs.php');
    exit;
}

// Récupérer le club
$stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ? AND actif = 1");
$stmt->execute([$club_id]);
$club = $stmt->fetch();

if (!$club) {
    header('Location: clubs.php');
    exit;
}

// Vérifier si l'utilisateur est déjà membre
$alreadyMember = false;
try {
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) as count FROM user_clubs WHERE user_id = ? AND club_id = ?");
    $stmtCheck->execute([$_SESSION['user_id'], $club_id]);
    $alreadyMember = $stmtCheck->fetch()['count'] > 0;
} catch (PDOException $e) {
    // Table user_clubs n'existe pas encore, on continue sans vérification
    $alreadyMember = false;
}

// Traitement de l'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyMember) {
    try {
        $stmt = $pdo->prepare("INSERT INTO user_clubs (user_id, club_id, joined_at) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $club_id]);
        
        $message = '✅ Vous avez rejoint le club avec succès !';
        $alreadyMember = true;
        
        // Rediriger après 2 secondes
        header('Refresh: 2; URL=clubs.php');
    } catch (PDOException $e) {
        $error = '❌ Erreur lors de l\'inscription au club.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejoindre <?php echo h($club['nom']); ?> - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .container {
            max-width: 700px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        
        .club-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .club-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .club-name {
            font-size: 2rem;
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .club-location {
            color: #64748b;
            font-size: 1.125rem;
            margin-bottom: 2rem;
        }
        
        .club-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #475569;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
        }
        
        .detail-icon {
            font-size: 1.25rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #3b82f6;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            flex: 1;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.3);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="club-card">
            <div class="club-icon">🏛️</div>
            <h1 class="club-name"><?php echo h($club['nom']); ?></h1>
            <p class="club-location">
                📍 <?php echo h($club['ville']); ?>
                <?php if ($club['aerodrome']): ?>
                    • <?php echo h($club['aerodrome']); ?>
                <?php endif; ?>
            </p>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($alreadyMember): ?>
                <div class="alert alert-info">
                    ✅ Vous êtes déjà membre de ce club !
                </div>
            <?php endif; ?>
            
            <div class="club-details">
                <?php if ($club['code_oaci']): ?>
                    <div class="detail-row">
                        <span class="detail-icon">✈️</span>
                        <span><strong>Code OACI:</strong> <?php echo h($club['code_oaci']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($club['telephone']): ?>
                    <div class="detail-row">
                        <span class="detail-icon">📞</span>
                        <span><?php echo h($club['telephone']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($club['email']): ?>
                    <div class="detail-row">
                        <span class="detail-icon">✉️</span>
                        <span><?php echo h($club['email']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($club['site_web']): ?>
                    <div class="detail-row">
                        <span class="detail-icon">🌐</span>
                        <a href="<?php echo h($club['site_web']); ?>" target="_blank" style="color: #0ea5e9;">
                            Visiter le site web
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST">
                <div class="btn-group">
                    <a href="clubs.php" class="btn btn-secondary">⬅️ Retour</a>
                    <button type="submit" class="btn btn-primary" <?php echo $alreadyMember ? 'disabled' : ''; ?>>
                        <?php echo $alreadyMember ? '✅ Déjà membre' : '✈️ Rejoindre ce club'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
