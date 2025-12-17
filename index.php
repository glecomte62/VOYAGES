<?php
/**
 * VOYAGES ULM - Catalogue de destinations pour pilotes
 * Page d'accueil
 */

require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Récupérer quelques statistiques
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT COUNT(*) as total FROM destinations WHERE actif = 1");
$stats = $stmt->fetch();
$nb_destinations = $stats['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE actif = 1");
$stats = $stmt->fetch();
$nb_membres = $stats['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM clubs WHERE actif = 1");
$stats = $stmt->fetch();
$nb_clubs = $stats['total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOYAGES ULM - Partagez vos aventures aériennes</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-brand">
                <span class="logo">✈️</span>
                <h1>VOYAGES ULM</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php" class="active">Accueil</a></li>
                <li><a href="pages/destinations.php">Destinations</a></li>
                <li><a href="pages/clubs.php">Clubs</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="pages/voyages.php">Mes Vols</a></li>
                    <li><a href="pages/profil.php">Mon Profil</a></li>
                    <li><a href="pages/logout.php" class="btn-logout">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="pages/login.php" class="btn-login">Connexion</a></li>
                    <li><a href="pages/register.php" class="btn-register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 class="hero-title">Explorez le ciel en toute liberté</h1>
                <p class="hero-subtitle">Partagez vos destinations, rencontrez d'autres pilotes et vivez votre passion</p>
                <?php if (!isLoggedIn()): ?>
                    <div class="hero-actions">
                        <a href="pages/register.php" class="btn btn-hero-primary">Rejoindre l'aventure</a>
                        <a href="pages/destinations.php" class="btn btn-hero-secondary">Découvrir les destinations</a>
                    </div>
                <?php else: ?>
                    <div class="hero-actions">
                        <a href="pages/destinations.php" class="btn btn-hero-primary">Explorer les destinations</a>
                        <a href="pages/voyages.php" class="btn btn-hero-secondary">Planifier un vol</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hero-badge">
                <div class="badge-icon">🏛️</div>
                <p>Une initiative du</p>
                <h3>Club ULM Évasion</h3>
                <p class="badge-location">Maubeuge</p>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📍</div>
                        <div class="stat-number"><?php echo $nb_destinations; ?></div>
                        <div class="stat-label">Destinations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number"><?php echo $nb_membres; ?></div>
                        <div class="stat-label">Membres</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🏛️</div>
                        <div class="stat-number"><?php echo $nb_clubs; ?></div>
                        <div class="stat-label">Clubs</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="container">
                <h2 class="section-title">Tout ce dont vous avez besoin pour vos vols</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🗺️</div>
                        <h3>Catalogue de destinations</h3>
                        <p>Découvrez des aérodromes testés et approuvés par la communauté. Informations détaillées sur les pistes, services et points d'intérêt.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🤝</div>
                        <h3>Communauté de passionnés</h3>
                        <p>Échangez avec d'autres pilotes, partagez vos expériences et découvrez de nouveaux compagnons de vol.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📸</div>
                        <h3>Partagez vos aventures</h3>
                        <p>Publiez vos photos, donnez votre avis et aidez la communauté à découvrir de magnifiques destinations.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🏛️</div>
                        <h3>Réseau de clubs</h3>
                        <p>Trouvez votre club, connectez-vous avec ses membres et participez à des vols groupés.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">✈️</div>
                        <h3>Planification de vols</h3>
                        <p>Organisez vos sorties, gardez un historique de vos vols et documentez vos voyages aériens.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🌟</div>
                        <h3>Favoris et recommandations</h3>
                        <p>Sauvegardez vos destinations préférées et bénéficiez des conseils de pilotes expérimentés.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Prêt à décoller ?</h2>
                    <p>Rejoignez une communauté de pilotes passionnés et partagez vos plus belles découvertes aériennes</p>
                    <?php if (!isLoggedIn()): ?>
                        <a href="pages/register.php" class="btn btn-cta">Créer mon compte gratuitement</a>
                    <?php else: ?>
                        <a href="pages/destinations.php" class="btn btn-cta">Explorer les destinations</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>✈️ VOYAGES ULM</h4>
                    <p>Plateforme communautaire pour pilotes ULM et petit avion</p>
                </div>
                <div class="footer-section">
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="pages/destinations.php">Destinations</a></li>
                        <li><a href="pages/clubs.php">Clubs</a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li><a href="pages/register.php">Inscription</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Club ULM Évasion</h4>
                    <p>Maubeuge</p>
                    <p>Application développée pour la communauté ULM</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> VOYAGES ULM - Tous droits réservés</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
