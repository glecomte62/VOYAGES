<?php
/**
 * Script de test de connexion
 */

// Configuration directe
define('DB_HOST', 'localhost');
define('DB_NAME', 'kica7829_voyages');
define('DB_USER', 'kica7829_voyages');
define('DB_PASS', 'Corvus2024@LFQJ');
define('DB_CHARSET', 'utf8mb4');

echo "<h1>Test de connexion à la base de données</h1>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p>✓ Connexion à la base de données réussie</p>";
    
    // Vérifier la structure de la table users
    echo "<h2>Structure de la table users</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    echo "<table border='1'><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Lister les utilisateurs
    echo "<h2>Utilisateurs dans la base</h2>";
    $stmt = $pdo->query("SELECT id, email, nom, prenom, role, actif FROM users");
    echo "<table border='1'><tr><th>ID</th><th>Email</th><th>Nom</th><th>Prénom</th><th>Rôle</th><th>Actif</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['nom']}</td>";
        echo "<td>{$row['prenom']}</td>";
        echo "<td>" . ($row['role'] ?? 'NULL') . "</td>";
        echo "<td>{$row['actif']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test de login avec le compte admin
    echo "<h2>Test de login avec admin@voyages-ulm.fr</h2>";
    $stmt = $pdo->prepare("SELECT id, email, password, nom, prenom, role FROM users WHERE email = ? AND actif = 1");
    $stmt->execute(['admin@voyages-ulm.fr']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>✓ Utilisateur trouvé</p>";
        echo "<pre>";
        echo "ID: {$user['id']}\n";
        echo "Email: {$user['email']}\n";
        echo "Nom: {$user['nom']}\n";
        echo "Prénom: {$user['prenom']}\n";
        echo "Rôle: " . ($user['role'] ?? 'NULL') . "\n";
        echo "</pre>";
        
        $password = 'Admin@2025';
        if (password_verify($password, $user['password'])) {
            echo "<p style='color: green;'>✓ Le mot de passe 'Admin@2025' est correct</p>";
        } else {
            echo "<p style='color: red;'>✗ Le mot de passe 'Admin@2025' est incorrect</p>";
            echo "<p>Hash dans la DB: {$user['password']}</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Utilisateur admin@voyages-ulm.fr non trouvé ou inactif</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

