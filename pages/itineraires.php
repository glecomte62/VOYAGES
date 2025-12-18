<?php
/**
 * VOYAGES - Gestion des itinéraires
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
    <title>Itinéraires - VOYAGES ULM</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/page-header.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container" style="padding-top: 6rem;">
        <div class="page-header">
            <h1 class="page-title">🗺️ Itinéraires</h1>
        </div>
        <p>Gérez vos itinéraires de voyage.</p>
    </main>
</body>
</html>
