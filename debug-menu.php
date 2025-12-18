<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

echo "<!-- DEBUG: isLoggedIn = " . (isLoggedIn() ? 'true' : 'false') . " -->";
echo "<!-- DEBUG: isAdmin = " . (isAdmin() ? 'true' : 'false') . " -->";
echo "<!-- DEBUG: user_role = " . ($_SESSION['user_role'] ?? 'undefined') . " -->";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Menu</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<h1>Si vous êtes admin, le menu Administration doit apparaître ci-dessus</h1>
</body>
</html>
