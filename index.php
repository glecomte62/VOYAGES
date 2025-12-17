<?php
/**
 * VOYAGES ULM - Catalogue de destinations pour pilotes
 * Page d'accueil
 */

require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOYAGES ULM - Catalogue de destinations pour pilotes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <h1>✈️ VOYAGES ULM</h1>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="pages/destinations.php">Destinations</a></li>
                <li><a href="pages/clubs.php">Clubs</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="pages/voyages.php">Mes Vols</a></li>
                    <li><a href="pages/profil.php">Mon Profil</a></li>
                    <li><a href="pages/logout.php">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="pages/login.php">Connexion</a></li>
                    <li><a href="pages/register.php">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h2>Découvrez les meilleures destinations en ULM et petit avion</h2>
            <p>Partagez vos découvertes avec la communauté des pilotes</p>
            <?php if (!isLoggedIn()): ?>
                <a href="pages/register.php" class="btn btn-primary">Rejoindre la communauté</a>
            <?php endif; ?>
        </section>

        <section class="features">
            <div class="feature">
                <h3>📍 Catalogue de destinations</h3>
                <p>Trouvez des aérodromes accessibles en ULM et petit avion</p>
            </div>
            <div class="feature">
                <h3>🏛️ Clubs référencés</h3>
                <p>Rejoignez votre club et connectez-vous avec d'autres membres</p>
            </div>
            <div class="feature">
                <h3>🤝 Communauté</h3>
                <p>Partagez vos expériences, photos et recommandations</p>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> VOYAGES - Tous droits réservés</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
