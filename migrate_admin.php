<?php
/**
 * Script de migration pour ajouter le système de rôles
 * À exécuter une seule fois
 */

// Configuration de la base de données directement dans le script
define('DB_HOST', 'localhost');
define('DB_NAME', 'kica7829_voyages');
define('DB_USER', 'kica7829_voyages');
define('DB_PASS', 'Corvus2024@LFQJ');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

echo "=== Migration du système administrateur ===\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Vérifier si la colonne role existe déjà
    echo "1. Vérification de la colonne 'role'...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('role', $columns)) {
        echo "   ✓ La colonne 'role' existe déjà\n\n";
    } else {
        echo "   → Ajout de la colonne 'role'...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER photo");
        $pdo->exec("ALTER TABLE users ADD INDEX idx_role (role)");
        echo "   ✓ Colonne 'role' ajoutée avec succès\n\n";
    }
    
    // 2. Vérifier/créer le compte admin
    echo "2. Vérification du compte administrateur...\n";
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = 'admin@voyages-ulm.fr'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "   → Compte admin existe (ID: {$admin['id']})\n";
        
        // Mettre à jour le mot de passe et le rôle
        $pdo->prepare("UPDATE users SET role = 'admin', password = ? WHERE id = ?")
            ->execute(['$2y$12$BzsnlTDzmYiM/hE2grCBm.SUJEXkIt7vho4VK2z8Cyjw.rKsPyYfi', $admin['id']]);
        echo "   ✓ Rôle et mot de passe mis à jour\n\n";
    } else {
        echo "   → Création du compte administrateur...\n";
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, nom, prenom, role, actif) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'admin@voyages-ulm.fr',
            '$2y$12$BzsnlTDzmYiM/hE2grCBm.SUJEXkIt7vho4VK2z8Cyjw.rKsPyYfi', // Admin@2025
            'Administrateur',
            'Système',
            'admin',
            1
        ]);
        echo "   ✓ Compte admin créé avec succès\n";
        echo "   Email: admin@voyages-ulm.fr\n";
        echo "   Mot de passe: Admin@2025\n\n";
    }
    
    // 3. Statistiques
    echo "3. Statistiques:\n";
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stats = $stmt->fetchAll();
    foreach ($stats as $stat) {
        $role = $stat['role'] ?? 'non défini';
        echo "   - {$role}: {$stat['count']} utilisateur(s)\n";
    }
    
    echo "\n=== Migration terminée avec succès ! ===\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
