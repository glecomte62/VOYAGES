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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOYAGES ULM - Partagez vos aventures aériennes</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        .map-section {
            background: white;
            padding: 4rem 0;
        }
        .map-section h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0f172a;
        }
        .map-section p {
            text-align: center;
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 3rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

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
                <p>Une initiative de</p>
                <img src="assets/images/LOGO-LEGER.jpeg" alt="Logo Voyages ULM" class="badge-logo">
                <p class="badge-location">Maubeuge</p>
            </div>
        </secCarte interactive des destinations -->
        <section class="map-section">
            <div class="container">
                <h2>🗺️ Carte des destinations</h2>
                <p>Explorez toutes nos destinations sur la carte de France</p>
                <div id="map"></div>
            </div>
        </section>

        <!-- tion>

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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Données des destinations
        const destinations = <?php echo $destinations_json; ?>;
        
        // Initialiser la carte centrée sur la France
        const map = L.map('map').setView([46.603354, 1.888334], 6);
        
        // Ajouter le fond de carte OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);
        
        // Icônes personnalisées
        const iconULM = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        const iconAvion = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        const iconMixte = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        // Ajouter les marqueurs
        destinations.forEach(dest => {
            if (dest.latitude && dest.longitude) {
                // Choisir l'icône selon le type
                let icon = iconMixte;
                if (dest.acces_ulm == 1 && dest.acces_avion == 0) {
                    icon = iconULM;
                } else if (dest.acces_avion == 1 && dest.acces_ulm == 0) {
                    icon = iconAvion;
                }
                
                // Type d'accès
                let typeAcces = '';
                if (dest.acces_ulm == 1 && dest.acces_avion == 1) {
                    typeAcces = '🪂 ULM • ✈️ Avion';
                } else if (dest.acces_ulm == 1) {
                    typeAcces = '🪂 ULM uniquement';
                } else if (dest.acces_avion == 1) {
                    typeAcces = '✈️ Avion uniquement';
                }
                
                const marker = L.marker([parseFloat(dest.latitude), parseFloat(dest.longitude)], {icon: icon})
                    .addTo(map)
                    .bindPopup(`
                        <div style="min-width: 200px;">
                            <h3 style="margin: 0 0 0.5rem; font-size: 1.1rem; color: #0f172a;">${dest.nom}</h3>
                            <p style="margin: 0.25rem 0; color: #64748b; font-size: 0.9rem;">
                                <strong>📍</strong> ${dest.aerodrome || ''} ${dest.code_oaci ? '(' + dest.code_oaci + ')' : ''}
                            </p>
                            <p style="margin: 0.25rem 0; color: #64748b; font-size: 0.9rem;">
                                <strong>🏙️</strong> ${dest.ville || ''}
                            </p>
                            <p style="margin: 0.5rem 0; color: #3b82f6; font-size: 0.85rem;">
                                ${typeAcces}
                            </p>
                            <a href="pages/destination-detail.php?id=${dest.id}" 
                               style="display: inline-block; margin-top: 0.5rem; padding: 0.5rem 1rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">
                                Voir les détails →
                            </a>
                        </div>
                    `);
            }
        });
    </script>
    <script src="   <p>Application développée pour la communauté ULM</p>
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
