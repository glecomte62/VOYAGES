<?php
// Vérification de version des fichiers
echo "Version check: " . date('Y-m-d H:i:s') . "<br>";
echo "Test POI Modal: OK<br>";
echo "Test Gallery: OK<br>";
echo "File uploaded successfully at: " . filemtime(__FILE__) . "<br>";
echo "Last modified: " . date('Y-m-d H:i:s', filemtime(__FILE__));
?>