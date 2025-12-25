<?php
// Inclure les fonctions si pas déjà fait
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}

// Déterminer le chemin de base (si on est dans /pages/ ou à la racine)
$baseUrl = (basename(dirname($_SERVER['PHP_SELF'])) === 'pages') ? '../' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="VOYAGES ULM">
    <meta name="description" content="Plateforme collaborative pour partager vos destinations ULM et avion léger">
    <meta name="theme-color" content="#0ea5e9">
    
    <!-- iOS Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Voyages ULM">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $baseUrl; ?>assets/images/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $baseUrl; ?>assets/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="<?php echo $baseUrl; ?>assets/images/icons/icon-192x192.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $baseUrl; ?>assets/images/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $baseUrl; ?>assets/images/icons/icon-512x512.png">
    
    <!-- Manifest -->
    <link rel="manifest" href="<?php echo $baseUrl; ?>manifest.json">
    
    <!-- Apple Splash Screens -->
    <link rel="apple-touch-startup-image" href="<?php echo $baseUrl; ?>assets/images/icons/icon-512x512.png">
</head>
<body>
<header class="page-header">
    <nav class="page-navbar">
        <a href="<?php echo $baseUrl; ?>index.php" class="header-brand">
        <svg width="40" height="40" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Soleil -->
            <circle cx="75" cy="25" r="12" fill="#fbbf24" opacity="0.9"/>
            <circle cx="75" cy="25" r="8" fill="#fcd34d"/>
            
            <!-- Nuage stylisé -->
            <path d="M20 45 Q15 40 20 35 Q25 32 30 35 Q35 30 40 35 Q45 40 40 45 Z" fill="white" opacity="0.8"/>
            
            <!-- Avion principal -->
            <g transform="translate(35, 50)">
                <!-- Fuselage -->
                <ellipse cx="0" cy="0" rx="25" ry="8" fill="url(#planeGradient)"/>
                <!-- Aile gauche -->
                <path d="M-15 0 L-35 -15 L-30 -12 L-12 2 Z" fill="url(#wingGradient)"/>
                <!-- Aile droite -->
                <path d="M-15 0 L-35 15 L-30 12 L-12 -2 Z" fill="url(#wingGradient)"/>
                <!-- Dérive -->
                <path d="M20 -2 L30 -8 L28 0 L30 8 L20 2 Z" fill="url(#tailGradient)"/>
                <!-- Cockpit -->
                <ellipse cx="10" cy="0" rx="8" ry="5" fill="rgba(255,255,255,0.4)"/>
                <circle cx="12" cy="0" r="3" fill="rgba(14,165,233,0.3)"/>
            </g>
            
            <!-- Traînée/trajectoire -->
            <path d="M10 50 Q20 48 30 50" stroke="#06b6d4" stroke-width="2" stroke-linecap="round" opacity="0.5" fill="none"/>
            <circle cx="12" cy="50" r="2" fill="#06b6d4" opacity="0.6"/>
            <circle cx="18" cy="49" r="1.5" fill="#0ea5e9" opacity="0.5"/>
            <circle cx="25" cy="50" r="1.5" fill="#14b8a6" opacity="0.4"/>
            
            <defs>
                <linearGradient id="planeGradient" x1="0" y1="-8" x2="0" y2="8">
                    <stop offset="0%" stop-color="#0ea5e9"/>
                    <stop offset="50%" stop-color="#06b6d4"/>
                    <stop offset="100%" stop-color="#14b8a6"/>
                </linearGradient>
                <linearGradient id="wingGradient" x1="-35" y1="0" x2="-12" y2="0">
                    <stop offset="0%" stop-color="#0ea5e9"/>
                    <stop offset="100%" stop-color="#06b6d4"/>
                </linearGradient>
                <linearGradient id="tailGradient" x1="20" y1="0" x2="30" y2="0">
                    <stop offset="0%" stop-color="#06b6d4"/>
                    <stop offset="100%" stop-color="#14b8a6"/>
                </defs>
            </svg>
            <span class="brand-name">VOYAGES ULM</span>
        </a>

        <button class="mobile-menu-toggle" aria-label="Menu" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="nav-menu">
            <a href="<?php echo $baseUrl; ?>index.php" class="nav-link">
                <span class="nav-icon">🏠</span>
                Accueil
            </a>
            
            <div class="nav-dropdown">
                <button class="nav-link dropdown-toggle">
                    <span class="nav-icon">🗺️</span>
                    Destinations
                    <span class="dropdown-arrow">▼</span>
                </button>
                <div class="dropdown-menu">
                    <a href="<?php echo $baseUrl; ?>pages/destinations.php">Toutes les destinations</a>
                    <a href="<?php echo $baseUrl; ?>pages/destinations.php?type=ulm">Terrains ULM</a>
                    <a href="<?php echo $baseUrl; ?>pages/destinations.php?type=avion">Aérodromes</a>
                    <a href="<?php echo $baseUrl; ?>pages/destinations.php?favoris=1">Mes favoris</a>
                </div>
            </div>

            <div class="nav-dropdown">
                <button class="nav-link dropdown-toggle">
                    <span class="nav-icon">👥</span>
                    Communauté
                    <span class="dropdown-arrow">▼</span>
                </button>
                <div class="dropdown-menu">
                    <a href="<?php echo $baseUrl; ?>pages/clubs.php">Clubs & Aéroclubs</a>
                    <a href="<?php echo $baseUrl; ?>pages/membres.php">Annuaire pilotes</a>
                </div>
            </div>
            
            <a href="<?php echo $baseUrl; ?>pages/about.php" class="nav-link">
                <span class="nav-icon">ℹ️</span>
                À propos
            </a>

            <?php if (isLoggedIn()): ?>
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle nav-link-user">
                        <span class="nav-icon">👤</span>
                        Mon Compte
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?php echo $baseUrl; ?>pages/profil.php">Mon profil</a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $baseUrl; ?>pages/logout.php" class="logout-link">Déconnexion</a>
                    </div>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="nav-dropdown nav-admin">
                    <button class="nav-link dropdown-toggle nav-link-admin">
                        <span class="nav-icon">⚙️</span>
                        Administration
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?php echo $baseUrl; ?>pages/admin.php">
                            <span class="menu-icon">📊</span>
                            Tableau de bord
                        </a>
                        <a href="<?php echo $baseUrl; ?>pages/admin-users.php">
                            <span class="menu-icon">👥</span>
                            Gestion utilisateurs
                        </a>
                        <a href="<?php echo $baseUrl; ?>pages/admin-destinations.php">
                            <span class="menu-icon">🗺️</span>
                            Gestion destinations
                        </a>
                        <a href="<?php echo $baseUrl; ?>pages/admin-clubs.php">
                            <span class="menu-icon">🏛️</span>
                            Gestion clubs
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $baseUrl; ?>pages/admin-logs.php">
                            <span class="menu-icon">📋</span>
                            Logs système
                        </a>
                        <a href="<?php echo $baseUrl; ?>pages/admin-settings.php">
                            <span class="menu-icon">⚙️</span>
                            Paramètres
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?php echo $baseUrl; ?>pages/login.php" class="btn-login">Connexion</a>
                <a href="<?php echo $baseUrl; ?>pages/register.php" class="btn-register">Inscription</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<script>
function toggleMobileMenu() {
    const menu = document.querySelector('.nav-menu');
    const toggle = document.querySelector('.mobile-menu-toggle');
    menu.classList.toggle('active');
    toggle.classList.toggle('active');
}

// Gestion des dropdowns
document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            // Fermer les autres dropdowns
            dropdowns.forEach(d => {
                if (d !== dropdown) d.classList.remove('active');
            });
            dropdown.classList.toggle('active');
        });
    });

    // Fermer les dropdowns en cliquant ailleurs
    document.addEventListener('click', function() {
        dropdowns.forEach(d => d.classList.remove('active'));
    });
});
</script>
