<?php
// Inclure les fonctions si pas déjà fait
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}

// Déterminer le chemin de base (si on est dans /pages/ ou à la racine)
$baseUrl = (basename(dirname($_SERVER['PHP_SELF'])) === 'pages') ? '../' : '';
?>
<header class="page-header">
    <nav class="page-navbar">
        <a href="<?php echo $baseUrl; ?>index.php" class="header-brand">
            <svg width="32" height="32" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 5L35 15V25L20 35L5 25V15L20 5Z" fill="url(#skyGradient)"/>
                <path d="M20 12L28 17V23L20 28L12 23V17L20 12Z" fill="white" opacity="0.8"/>
                <defs>
                    <linearGradient id="skyGradient" x1="5" y1="5" x2="35" y2="35">
                        <stop offset="0%" stop-color="#3b82f6"/>
                        <stop offset="100%" stop-color="#8b5cf6"/>
                    </linearGradient>
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
                    <a href="<?php echo $baseUrl; ?>pages/evenements.php">Événements</a>
                </div>
            </div>

            <?php if (isLoggedIn()): ?>
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle nav-link-user">
                        <span class="nav-icon">👤</span>
                        Mon Compte
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?php echo $baseUrl; ?>pages/profil.php">Mon profil</a>
                        <a href="<?php echo $baseUrl; ?>pages/voyages.php">Mes vols</a>
                        <a href="<?php echo $baseUrl; ?>pages/favoris.php">Mes favoris</a>
                        <a href="<?php echo $baseUrl; ?>pages/parametres.php">Paramètres</a>
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
