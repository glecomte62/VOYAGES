<?php
/**
 * VOYAGES ULM - Catalogue de destinations pour pilotes
 * Page d'accueil
 */

require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si pas connecté, rediriger vers login
if (!isLoggedIn()) {
    header('Location: pages/login.php');
    exit;
}

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

// Récupérer toutes les destinations avec coordonnées pour la carte
$stmt = $pdo->query("SELECT id, nom, aerodrome, code_oaci, ville, latitude, longitude, acces_ulm, acces_avion 
                     FROM destinations 
                     WHERE actif = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL");
$destinations = $stmt->fetchAll();
$destinations_json = json_encode($destinations);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>VOYAGES ULM - Partagez vos aventures aériennes</title>
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="VOYAGES ULM">
    <meta name="description" content="Plateforme collaborative pour partager vos destinations ULM et avion léger">
    <meta name="theme-color" content="#0ea5e9">
    
    <!-- iOS Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Voyages ULM">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="152x152" href="assets/images/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="assets/images/icons/icon-192x192.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/images/icons/icon-512x512.png">
    
    <!-- Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: 600px;
            border-radius: 12px;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .map-section {
            background: white;
            padding: 3rem 0;
            margin: 4rem 0;
        }
        .map-section h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Welcome Section -->
        <section class="welcome-section" style="background: linear-gradient(135deg, rgba(74, 144, 226, 0.95), rgba(52, 152, 219, 0.95)); padding: 4rem 0; text-align: center; color: white; margin-top: 80px;">
            <div class="container" style="max-width: 900px; margin: 0 auto; padding: 0 2rem;">
                <h1 style="font-size: 2.5rem; margin-bottom: 1.5rem; font-weight: 700;">Bienvenue dans la communauté VOYAGES ULM</h1>
                <div style="font-size: 1.2rem; line-height: 1.8; margin-bottom: 2rem;">
                    <p style="margin-bottom: 1rem;">
                        <strong>Vous êtes pilote ULM ou petit avion ?</strong> Cette application a été créée spécialement pour vous !
                    </p>
                    <p style="margin-bottom: 1rem;">
                        Notre mission est simple : <strong>partager nos découvertes</strong> et <strong>faciliter vos aventures aériennes</strong>. 
                        Chaque pilote a ses destinations favorites, ses bonnes adresses, ses petits coins secrets découverts au fil des vols.
                    </p>
                    <p style="margin-bottom: 1rem;">
                        En participant, vous enrichissez une base commune de <strong>destinations testées et approuvées</strong> par la communauté. 
                        Vous aidez d'autres passionnés à <strong>préparer leurs vols</strong>, à découvrir de nouveaux horizons et à <strong>voler en toute sérénité</strong>.
                    </p>
                    <p style="margin-bottom: 2rem;">
                        <strong>Ensemble, créons le guide ultime des destinations aériennes !</strong> 
                        Que vous soyez pilote expérimenté ou débutant, votre contribution compte. Chaque avis, chaque photo, chaque conseil fait la différence.
                    </p>
                    <?php if (!isLoggedIn()): ?>
                        <div style="margin-top: 2rem;">
                            <a href="pages/register.php" class="btn" style="display: inline-block; background: white; color: #3498db; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 0.5rem; font-size: 1.1rem;">
                                ✈️ Rejoindre la communauté
                            </a>
                            <a href="pages/destinations.php" class="btn" style="display: inline-block; background: transparent; color: white; border: 2px solid white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 0.5rem; font-size: 1.1rem;">
                                🗺️ Voir les destinations
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 2rem;">
                            <a href="pages/destination-add.php" class="btn" style="display: inline-block; background: white; color: #3498db; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 0.5rem; font-size: 1.1rem;">
                                ➕ Partager une destination
                            </a>
                            <a href="pages/destinations.php" class="btn" style="display: inline-block; background: transparent; color: white; border: 2px solid white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 0.5rem; font-size: 1.1rem;">
                                🗺️ Explorer les destinations
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Map Section -->
        <section class="map-section">
            <div class="container">
                <h2>🗺️ Carte des destinations</h2>
                <div id="map"></div>
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
                    <div class="footer-logo">
                        <img src="assets/images/LOGO-LEGER.jpeg" alt="Club ULM Évasion">
                        <div>
                            <h4>VOYAGES ULM</h4>
                            <p class="footer-tagline">Plateforme communautaire pour pilotes ULM et petit avion</p>
                        </div>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="pages/destinations.php">Destinations</a></li>
                        <li><a href="pages/clubs.php">Clubs</a></li>
                        <li><a href="pages/about.php">Inscription</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Club ULM Évasion</h4>
                    <p>Maubeuge</p>
                    <p>Application développée pour la communauté ULM</p>
                    <p><a href="https://www.clubulmevasion.fr" target="_blank">🌐 www.clubulmevasion.fr</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> VOYAGES ULM - Tous droits réservés</p>
                <p class="footer-author">Développé par <a href="https://www.linkedin.com/in/guillaume-lecomte-frbe/" target="_blank">Guillaume Lecomte</a></p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialiser la carte centrée sur la France
        const map = L.map('map').setView([46.603354, 1.888334], 6);

        // Ajouter les tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Définir des icônes personnalisées par type d'accès
        const iconULM = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background-color: #28a745; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        const iconAvion = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background-color: #007bff; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        const iconMixte = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background-color: #ff8c00; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        // Charger les destinations depuis PHP
        const destinations = <?php echo $destinations_json; ?>;
        console.log('Nombre de destinations:', destinations.length);

        // Ajouter les marqueurs
        destinations.forEach(dest => {
            // Déterminer le type d'accès
            let type_acces = '';
            let icon = iconMixte;
            
            if (dest.acces_ulm == 1 && dest.acces_avion == 1) {
                type_acces = 'ULM et Avion';
                icon = iconMixte;
            } else if (dest.acces_ulm == 1) {
                type_acces = 'ULM uniquement';
                icon = iconULM;
            } else if (dest.acces_avion == 1) {
                type_acces = 'Avion uniquement';
                icon = iconAvion;
            }

            const marker = L.marker([dest.latitude, dest.longitude], {icon: icon})
                .addTo(map)
                .bindPopup(`
                    <div style="min-width: 200px;">
                        <h3 style="margin: 0 0 10px 0; color: #2c3e50;">${dest.nom}</h3>
                        <p style="margin: 5px 0;"><strong>Aérodrome:</strong> ${dest.aerodrome}</p>
                        <p style="margin: 5px 0;"><strong>Code OACI:</strong> ${dest.code_oaci}</p>
                        <p style="margin: 5px 0;"><strong>Ville:</strong> ${dest.ville}</p>
                        <p style="margin: 5px 0;"><strong>Accès:</strong> ${type_acces}</p>
                        <a href="pages/destination-detail.php?id=${dest.id}" style="display: inline-block; margin-top: 10px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Voir détails</a>
                    </div>
                `);
        });
    </script>
    
    <!-- PWA Install Script -->
    <script src="assets/js/pwa-install.js"></script>
</body>
</html>
