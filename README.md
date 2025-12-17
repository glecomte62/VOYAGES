# VOYAGES - Application de Gestion de Voyages

Application web PHP/MySQL pour planifier, organiser et documenter vos voyages.

## 🚀 Fonctionnalités

- **Planification d'itinéraires** : Créez et organisez des itinéraires détaillés
- **Gestion des réservations** : Centralisez vos réservations (vols, hôtels, activités)
- **Documentation de voyages** : Gardez une trace de vos expériences et souvenirs

## 🛠️ Stack Technique

- **Backend** : PHP 7.4+
- **Base de données** : MySQL 5.7+
- **Frontend** : HTML5, CSS3, JavaScript vanilla
- **Déploiement** : FTP
- **Versionnement** : Git

## 📋 Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- Accès FTP pour le déploiement

## 🔧 Installation

### 1. Cloner le repository

```bash
git clone [URL_DU_REPOSITORY]
cd VOYAGES
```

### 2. Configuration de la base de données

1. Créez une base de données MySQL
2. Éditez le fichier `config/database.php` avec vos paramètres :

```php
define('DB_HOST', 'votre_host');
define('DB_NAME', 'votre_db');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_password');
```

3. Importez le schéma de la base de données (à venir)

### 3. Configuration de l'application

Éditez `config/config.php` pour ajuster :
- L'URL de base de votre application
- L'environnement (development/production)

### 4. Permissions

Assurez-vous que les dossiers suivants sont accessibles en écriture :
```bash
chmod 755 uploads/
chmod 755 logs/
chmod 755 cache/
```

## 📁 Structure du Projet

```
VOYAGES/
├── assets/           # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── config/           # Fichiers de configuration
│   ├── config.php
│   └── database.php
├── includes/         # Fichiers PHP réutilisables
│   └── functions.php
├── pages/           # Pages de l'application
├── uploads/         # Fichiers uploadés
├── logs/            # Logs d'erreur
├── cache/           # Fichiers de cache
├── index.php        # Page d'accueil
└── README.md
```

## 🚀 Déploiement

Le déploiement se fait via FTP sur votre espace d'hébergement.

```bash
# Exemple avec lftp
lftp -u utilisateur,motdepasse ftp.votre-serveur.com
mirror -R /chemin/local /chemin/distant
```

## 🔒 Sécurité

- Les mots de passe sont hashés avec `password_hash()`
- Protection CSRF sur tous les formulaires
- Requêtes préparées (PDO) pour prévenir les injections SQL
- Validation et échappement des données utilisateur

## 📝 TODO

- [ ] Créer le schéma de base de données
- [ ] Implémenter l'authentification utilisateur
- [ ] Développer les pages de gestion des voyages
- [ ] Ajouter la fonctionnalité d'itinéraires
- [ ] Implémenter la gestion des réservations
- [ ] Créer l'interface de documentation

## 📄 Licence

Tous droits réservés

## 👤 Auteur

Guillaume Lecomte
