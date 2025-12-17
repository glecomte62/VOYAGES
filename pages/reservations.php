<?php
/**
 * VOYAGES - Gestion des réservations
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations - VOYAGES</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <h1>VOYAGES</h1>
            <ul>
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="voyages.php">Mes Voyages</a></li>
                <li><a href="itineraires.php">Itinéraires</a></li>
                <li><a href="reservations.php">Réservations</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2>Réservations</h2>
        <p>Gérez toutes vos réservations (vols, hôtels, activités).</p>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> VOYAGES - Tous droits réservés</p>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
