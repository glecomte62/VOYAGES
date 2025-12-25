# Changelog - VOYAGES ULM

## [Version du 25 décembre 2025]

### 🎄 Nouvelles fonctionnalités majeures

#### 🔐 Système d'authentification obligatoire
- Login obligatoire sur toute l'application
- Nouvelle page de connexion moderne avec description du projet
- Mention "100% GRATUIT ET LE RESTERA" mise en avant
- Redirection automatique après connexion
- Gestion sécurisée des sessions

#### 📏 Module de recherche par distance/temps de vol
- **Filtrage par distance** depuis le terrain d'attache :
  - Moins de 50 km
  - Moins de 100 km
  - Moins de 200 km
  - Moins de 500 km
- **Filtrage par temps de vol** (calculé à 160 km/h) :
  - Moins de 30 min
  - Moins de 1h
  - Moins de 2h
  - Moins de 3h
- Affichage de la distance et du temps de vol sur chaque carte de destination
- Tri automatique par distance croissante
- Calcul avec formule de Haversine (précision GPS)

#### 🏠 Gestion du terrain d'attache
- Sélection du terrain d'attache dans le profil utilisateur
- Support des aérodromes ET des bases ULM
- Système dual avec `terrain_attache_type` et `terrain_attache_id`
- Dropdown combiné avec emoji pour distinction (✈️ vs 🪂)
- Index composite pour optimisation des performances

#### 📍 Badges de distance/temps sur les détails de destination
- Affichage de la distance depuis le terrain d'attache
- Calcul du temps de vol théorique (160 km/h)
- Design moderne avec bordures colorées
- Responsive mobile et desktop

### 🔧 Améliorations techniques

#### Base de données
- Extension de `code_oaci` de VARCHAR(4) à VARCHAR(10)
- Support des codes longs pour bases ULM (ex: LF5954)
- Ajout des champs `terrain_attache_type` et `terrain_attache_id` dans `users`
- Index composite pour optimisation des requêtes

#### Architecture
- Fonction `requireLogin()` centralisée dans `session.php`
- Support multi-tables (aerodromes_fr + ulm_bases_fr)
- Fallback automatique pour noms de colonnes (latitude/longitude vs lat/lon)
- Gestion des erreurs améliorée avec try/catch

#### Interface utilisateur
- Page de login redesignée avec gradient et animations
- Logo du Club ULM Évasion intégré
- Design responsive optimisé pour mobile
- Filtres contextuels (affichés uniquement si terrain d'attache défini)
- Message d'invitation à définir le terrain d'attache

### 🐛 Corrections de bugs
- Correction des redirections de session après login
- Fix de l'affichage du code OACI sur mobile (word-break)
- Résolution des problèmes de pages blanches après authentification
- Correction du chemin relatif vers login.php selon le contexte
- Nettoyage des vérifications manuelles de login (remplacées par requireLogin())

### 📱 PWA et Mobile
- Footer avec logo Club ULM Évasion maintenu
- Filtres distance/temps entièrement responsive
- Badges visibles sur mobile et desktop
- CSS responsive avec clamp() et media queries

### 🔒 Sécurité
- Toutes les pages protégées par requireLogin()
- Endpoints AJAX sécurisés
- Sessions configurées avec httponly et secure cookies
- Durée de session : 24h

### 📊 Pages modifiées
- `index.php` - Redirection automatique vers login si non connecté
- `pages/login.php` - Redesign complet avec présentation de l'application
- `pages/destinations.php` - Module de recherche par distance/temps
- `pages/destination-detail.php` - Badges de distance et temps de vol
- `pages/profil.php` - Sélection du terrain d'attache
- `includes/session.php` - Fonction requireLogin() ajoutée
- Tous les fichiers dans `pages/` - requireLogin() appliqué

### 🗃️ Scripts SQL
- `sql/fix_code_oaci_length.sql` - Extension VARCHAR(10)
- `sql/add_aerodrome_attache.sql` - Champs terrain d'attache

### 🎯 Prochaines évolutions possibles
- Calcul d'itinéraires multi-étapes
- Sauvegarde de recherches favorites
- Partage de destinations par rayon
- Export des résultats en PDF
- Météo en temps réel sur les destinations
- Intégration NOTAM et informations aéronautiques

---

**Développé pour le Club ULM Évasion - Maubeuge (LFQJ)**  
Application 100% gratuite et open source 🎁
