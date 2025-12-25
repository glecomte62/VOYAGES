-- Ajout des champs pour la validation email
ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER actif,
ADD COLUMN activation_token VARCHAR(64) DEFAULT NULL AFTER email_verified,
ADD COLUMN activation_token_expires DATETIME DEFAULT NULL AFTER activation_token,
ADD INDEX idx_activation_token (activation_token);

-- Mettre à jour les utilisateurs existants comme déjà vérifiés
UPDATE users SET email_verified = 1 WHERE actif = 1;
