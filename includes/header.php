<header>
    <nav>
        <h1>✈️ VOYAGES ULM</h1>
        <ul>
            <li><a href="../index.php">Accueil</a></li>
            <li><a href="destinations.php">Destinations</a></li>
            <li><a href="clubs.php">Clubs</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="voyages.php">Mes Vols</a></li>
                <li><a href="profil.php">Mon Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            <?php else: ?>
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.php">Inscription</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
