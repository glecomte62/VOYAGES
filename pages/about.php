<?php
/**
 * Page À propos
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/smtp.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        .about-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .about-header {
            text-align: center;
            padding: 3rem 0;
            background: linear-gradient(135deg, #fbbf24 0%, #84cc16 50%, #10b981 100%);
            border-radius: 12px;
            color: white;
            margin-bottom: 2rem;
        }
        
        .about-header h1 {
            font-size: 2.5rem;
            margin: 0 0 1rem 0;
        }
        
        .about-header p {
            font-size: 1.2rem;
            opacity: 0.95;
        }
        
        .about-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .about-section h2 {
            color: #0ea5e9;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .about-section p {
            line-height: 1.8;
            color: #475569;
            margin-bottom: 1rem;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .value-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #10b981;
        }
        
        .value-card h3 {
            color: #065f46;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .value-card p {
            color: #047857;
            margin: 0;
        }
        
        .contact-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            padding: 2rem;
            border-radius: 12px;
            border: 2px solid #0ea5e9;
            text-align: center;
        }
        
        .contact-box h3 {
            color: #0c4a6e;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .contact-email {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            color: #0ea5e9;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
        }
        
        .contact-email:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            background: #0ea5e9;
            color: white;
        }
        
        .team-info {
            background: #fef3c7;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #fbbf24;
            margin: 2rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e2e8f0;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0ea5e9;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body style="padding-top: 5rem;">
    <?php include '../includes/header.php'; ?>

    <main class="about-container">
        <div class="about-header">
            <h1>🛩️ À propos de VOYAGES ULM</h1>
            <p>Une plateforme collaborative gratuite créée par des passionnés pour des passionnés</p>
        </div>

        <div class="about-section">
            <h2>✨ Notre mission</h2>
            <p>
                <strong>VOYAGES ULM</strong> est une application web collaborative entièrement gratuite dédiée aux pilotes ULM et d'avions légers. 
                Notre objectif est simple : créer ensemble LA référence des destinations accessibles en ULM et petit avion.
            </p>
            <p>
                Cette plateforme est le fruit du travail bénévole de passionnés qui croient fermement que les meilleures ressources 
                naissent de la collaboration et du partage. Jour après jour, grâce à vos contributions, nous construisons un catalogue 
                qui devient de plus en plus riche et utile pour toute la communauté.
            </p>
        </div>

        <div class="about-section">
            <h2>🤝 Nos valeurs</h2>
            <div class="values-grid">
                <div class="value-card">
                    <h3>✈️ Ouverture</h3>
                    <p>ULM, avions légers, tous les pilotes sont les bienvenus !</p>
                </div>
                <div class="value-card">
                    <h3>🤝 Entraide</h3>
                    <p>Chacun apporte sa pierre à l'édifice en partageant ses découvertes</p>
                </div>
                <div class="value-card">
                    <h3>🌍 Liberté</h3>
                    <p>Inspirez-vous, planifiez, explorez de nouveaux horizons</p>
                </div>
                <div class="value-card">
                    <h3>💚 Gratuité</h3>
                    <p>Aucun frais, aucun abonnement. Juste le plaisir de partager</p>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>🏛️ Le Club ULM Évasion</h2>
            <div class="team-info">
                <p style="margin: 0; color: #92400e;">
                    <strong>VOYAGES ULM</strong> est une initiative du <strong>Club ULM Évasion de Maubeuge</strong>, 
                    un aéroclub passionné qui partage les valeurs d'entraide et de découverte caractéristiques de notre communauté.
                </p>
            </div>
            <p>
                Situés dans les Hauts-de-France, nous sommes un groupe de pilotes ULM et avion léger qui avons à cœur de 
                promouvoir notre passion et de faciliter les échanges au sein de la communauté aérienne.
            </p>
        </div>

        <div class="about-section">
            <h2>🚀 Comment ça fonctionne ?</h2>
            <p>VOYAGES ULM est une plateforme collaborative où chaque membre peut :</p>
            <ul style="line-height: 1.8; color: #475569;">
                <li>📍 <strong>Explorer</strong> les destinations partagées par la communauté</li>
                <li>✏️ <strong>Ajouter</strong> ses propres aérodromes et destinations favorites</li>
                <li>📸 <strong>Partager</strong> ses photos et expériences de vol</li>
                <li>💬 <strong>Laisser des avis</strong> pour guider les autres pilotes</li>
                <li>🏛️ <strong>Rejoindre son club</strong> et se connecter avec d'autres membres</li>
            </ul>
            <p>
                Plus nous serons nombreux à participer, plus cette base de données deviendra précieuse pour tous. 
                Chaque contribution compte !
            </p>
        </div>

        <div class="about-section">
            <h2>💡 Votre avis compte !</h2>
            <div class="contact-box">
                <h3>Remarques, suggestions, idées ?</h3>
                <p style="color: #0c4a6e; margin-bottom: 1.5rem;">
                    Nous sommes à l'écoute de la communauté ! N'hésitez pas à nous faire part de vos retours, 
                    suggestions d'amélioration ou nouvelles idées pour enrichir la plateforme.
                </p>
                <a href="mailto:lecomteguillaume@outlook.com" class="contact-email">
                    📧 lecomteguillaume@outlook.com
                </a>
                <p style="color: #64748b; font-size: 0.9rem; margin-top: 1.5rem;">
                    Toute contribution ou suggestion est la bienvenue pour améliorer l'expérience de tous !
                </p>
            </div>
        </div>

        <div class="about-section" style="text-align: center; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10b981;">
            <h2 style="color: #065f46;">🌟 Rejoignez l'aventure !</h2>
            <p style="font-size: 1.1rem; color: #047857;">
                Ensemble, créons la plus belle base de données de destinations aériennes !
            </p>
            <div style="margin-top: 2rem;">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" style="display: inline-block; background: #10b981; color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s;">
                        🚀 Créer mon compte gratuitement
                    </a>
                <?php else: ?>
                    <a href="../index.php" style="display: inline-block; background: #10b981; color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s;">
                        🗺️ Découvrir les destinations
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
