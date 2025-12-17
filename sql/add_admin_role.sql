-- Migration : Ajout du rôle administrateur
-- À exécuter sur la base de données existante

-- Ajouter la colonne role si elle n'existe pas
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user' AFTER photo,
ADD INDEX IF NOT EXISTS idx_role (role);

-- Créer un compte administrateur par défaut
-- Mot de passe : Admin@2025
INSERT INTO users (email, password, nom, prenom, role, actif) 
VALUES (
    'admin@voyages-ulm.fr',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Administrateur',
    'Système',
    'admin',
    TRUE
) ON DUPLICATE KEY UPDATE role = 'admin';

-- Mettre à jour un utilisateur existant si besoin (remplacer l'email)
-- UPDATE users SET role = 'admin' WHERE email = 'votre-email@exemple.com';
