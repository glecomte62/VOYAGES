<?php
/**
 * Fonctions d'envoi d'emails via SMTP
 */

require_once __DIR__ . '/../config/smtp.php';

/**
 * Envoie un email via SMTP (version simple)
 */
function sendEmail($to, $subject, $body, $isHtml = true) {
    $headers = [];
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . SMTP_FROM_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    if ($isHtml) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Envoie un email d'activation de compte
 */
function sendActivationEmail($email, $nom, $prenom, $token) {
    $activationLink = SITE_URL . '/pages/verify-email.php?token=' . urlencode($token);
    
    $subject = 'Activez votre compte ' . SITE_NAME;
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
            .footer { text-align: center; color: #64748b; font-size: 0.875rem; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✈️ Bienvenue sur ' . SITE_NAME . ' !</h1>
            </div>
            <div class="content">
                <p>Bonjour ' . htmlspecialchars($prenom) . ' ' . htmlspecialchars($nom) . ',</p>
                
                <p>Merci de vous être inscrit sur <strong>' . SITE_NAME . '</strong>, la plateforme communautaire des pilotes ULM et petit avion !</p>
                
                <p>Pour activer votre compte et commencer à partager vos aventures aériennes, veuillez cliquer sur le bouton ci-dessous :</p>
                
                <div style="text-align: center;">
                    <a href="' . htmlspecialchars($activationLink) . '" class="button">
                        ✅ Activer mon compte
                    </a>
                </div>
                
                <p style="color: #64748b; font-size: 0.875rem; margin-top: 20px;">
                    Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                    <a href="' . htmlspecialchars($activationLink) . '">' . htmlspecialchars($activationLink) . '</a>
                </p>
                
                <p style="margin-top: 30px;">
                    <strong>Ce lien est valable pendant 24 heures.</strong>
                </p>
                
                <p>À très bientôt dans les airs ! 🛩️</p>
                
                <p style="margin-top: 30px;">
                    L\'équipe ' . SITE_NAME . '<br>
                    Club ULM Évasion - Maubeuge
                </p>
            </div>
            <div class="footer">
                <p>Vous recevez cet email car vous vous êtes inscrit sur ' . SITE_NAME . '</p>
                <p>Si vous n\'êtes pas à l\'origine de cette inscription, vous pouvez ignorer cet email.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return sendEmail($email, $subject, $body, true);
}

/**
 * Envoie un email de bienvenue après activation
 */
function sendWelcomeEmail($email, $nom, $prenom) {
    $subject = 'Bienvenue dans la communauté VOYAGES ULM ! ✈️';
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8fafc; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
            .highlight-box { background: #e0f2fe; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .collab-box { background: #fff7ed; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 8px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 Bienvenue dans notre communauté !</h1>
            </div>
            <div class="content">
                <p>Bonjour ' . htmlspecialchars($prenom) . ',</p>
                
                <p>Le <strong>Club ULM Évasion</strong> est heureux de vous accueillir sur <strong>' . SITE_NAME . '</strong> ! 🛩️</p>
                
                <div class="highlight-box">
                    <p style="margin-top: 0;"><strong>📍 Découvrez un catalogue de voyages unique</strong></p>
                    <p>Cette application a été créée pour vous inspirer dans vos sorties ULM. Explorez des dizaines de destinations accessibles en ULM et petit avion, partagées par des pilotes passionnés comme vous !</p>
                </div>
                
                <p><strong>Une aventure collaborative 🤝</strong></p>
                <p>VOYAGES ULM est une plateforme collaborative : plus nous serons nombreux à partager nos destinations favorites, plus cette base de données deviendra riche et utile pour tous. Jour après jour, ensemble, nous construisons LA référence des voyages ULM !</p>
                
                <p><strong>Comment participer ?</strong></p>
                <ul>
                    <li>📍 <strong>Explorez</strong> les destinations partagées par la communauté</li>
                    <li>✏️ <strong>Ajoutez</strong> vos propres destinations et aérodromes favoris</li>
                    <li>📸 <strong>Partagez</strong> vos photos et expériences de vol</li>
                    <li>💬 <strong>Laissez des avis</strong> pour guider les autres pilotes</li>
                    <li>🏛️ <strong>Rejoignez</strong> votre club et connectez-vous avec d\'autres membres</li>
                </ul>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . SITE_URL . '/pages/login.php" class="button">
                        🚀 Commencer l\'aventure
                    </a>
                </div>
                
                <div class="collab-box">
                    <p style="margin: 0;"><strong>💡 Votre première contribution :</strong></p>
                    <p style="margin: 10px 0 0 0;">Pensez à ajouter votre aérodrome préféré ou la dernière destination que vous avez visitée. Même une simple description et quelques photos peuvent aider d\'autres pilotes à découvrir de nouveaux horizons !</p>
                </div>
                
                <p>Ensemble, créons la plus belle base de données de voyages ULM ! ✈️</p>
                
                <p style="margin-top: 30px;">
                    À très bientôt dans les airs,<br>
                    <strong>L\'équipe ' . SITE_NAME . '</strong><br>
                    Club ULM Évasion - Maubeuge
                </p>
            </div>
            <div style="text-align: center; color: #64748b; font-size: 0.875rem; margin-top: 20px; padding: 20px;">
                <p>Des questions ? Une suggestion ? N\'hésitez pas à nous contacter !</p>
                <p>' . SITE_URL . '</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return sendEmail($email, $subject, $body, true);
}
