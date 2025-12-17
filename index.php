<?php
/**
 * VOYAGES - Application de gestion de voyages
 * Page d'accueil
 */

session_start();

require_once 'config/database.php';
require_once 'includes/functions.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOYAGES - Gestion de vos voyages</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <h1>VOYAGES</h1>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="pages/voyages.php">Mes Voyages</a></li>
                <li><a href="pages/itineraires.php">Itinéraires</a></li>
                <li><a href="pages/reservations.php">Réservations</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h2>Bienvenue dans votre gestionnaire de voyages</h2>
            <p>Planifiez, organisez et documentez tous vos voyages en un seul endroit.</p>
        </section>

        <section class="features">
            <div class="feature">
                <h3>📍 Planification d'itinéraires</h3>
                <p>Créez des itinéraires détaillés pour vos voyages</p>
            </div>
            <div class="feature">
                <h3>✈️ Gestion des réservations</h3>
                <p>Centralisez toutes vos réservations (vols, hôtels, activités)</p>
            </div>
            <div class="feature">
                <h3>📝 Documentation</h3>
                <p>Gardez une trace de vos souvenirs et expériences</p>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> VOYAGES - Tous droits réservés</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
