# Configuration SMTP - VOYAGES ULM

## 1. Créer un mot de passe d'application Google

1. Aller sur https://myaccount.google.com/
2. Sécurité > Validation en deux étapes (activer si pas déjà fait)
3. Mots de passe des applications
4. Créer un nouveau mot de passe d'application pour "VOYAGES ULM"
5. Copier le mot de passe généré (16 caractères)

## 2. Configurer config/smtp.php

Modifiez le fichier `config/smtp.php` avec vos informations :

```php
define('SMTP_USERNAME', 'votre-email@votredomaine.com'); // Votre email Google Workspace
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx'); // Le mot de passe d'application
define('SMTP_FROM_EMAIL', 'no-reply@votredomaine.com'); // Email d'envoi
```

## 3. Exécuter le script SQL

Dans phpMyAdmin, exécutez le fichier `sql/add_email_verification.sql` pour ajouter les colonnes nécessaires.

## 4. Upload des fichiers

Les fichiers suivants ont été créés/modifiés :
- `config/smtp.php` - Configuration SMTP
- `includes/mail.php` - Fonctions d'envoi d'email
- `pages/register.php` - Modifié pour envoyer l'email d'activation
- `pages/login.php` - Modifié pour vérifier l'activation
- `pages/verify-email.php` - Page d'activation du compte
- `sql/add_email_verification.sql` - Script SQL à exécuter

## 5. Tester

1. Créer un nouveau compte
2. Vérifier la réception de l'email d'activation
3. Cliquer sur le lien d'activation
4. Vérifier la réception de l'email de bienvenue
5. Se connecter avec le compte activé

## Notes importantes

- Les comptes existants sont automatiquement marqués comme vérifiés
- Le lien d'activation expire après 24 heures
- Les emails utilisent des templates HTML responsive
- Si l'email n'est pas envoyé, le compte est créé mais nécessite validation admin

## Alternative : PHPMailer

Si la fonction `mail()` ne fonctionne pas avec Google SMTP, utilisez PHPMailer :

```bash
composer require phpmailer/phpmailer
```

Puis modifiez `includes/mail.php` pour utiliser PHPMailer au lieu de mail().
