<header class="page-header">
    <nav class="page-navbar">
        <a href="../index.php" class="header-brand">
            <div class="brand-logo">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 5L35 15V25L20 35L5 25V15L20 5Z" fill="url(#skyGradient)"/>
                    <path d="M20 12L28 17V23L20 28L12 23V17L20 12Z" fill="white" opacity="0.8"/>
                    <defs>
                        <linearGradient id="skyGradient" x1="5" y1="5" x2="35" y2="35">
                            <stop offset="0%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#8b5cf6"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-name">VOYAGES ULM</span>
                <span class="brand-tagline">Partagez vos aventures</span>
            </div>
        </a>

        <button class="mobile-menu-toggle" aria-label="Menu" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="nav-menu">
            <a href="../index.php" class="nav-link">
                <span class="nav-icon">🏠</span>
                <span>Accueil</span>
            </a>
            <a href="destinations.php" class="nav-link">
                <span class="nav-icon">🗺️</span>
                <span>Destinations</span>
            </a>
            <a href="clubs.php" class="nav-link">
                <span class="nav-icon">👥</span>
                <span>Clubs</span>
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="voyages.php" class="nav-link">
                    <span class="nav-icon">✈️</span>
                    <span>Mes Vols</span>
                </a>
                <div class="nav-divider"></div>
                <a href="profil.php" class="nav-link nav-link-user">
                    <span class="nav-icon">👤</span>
                    <span>Mon Profil</span>
                </a>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            <?php else: ?>
                <a href="login.php" class="btn-login">Connexion</a>
                <a href="register.php" class="btn-register">Inscription</a>
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
</script>
