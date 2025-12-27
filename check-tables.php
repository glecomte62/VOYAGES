<?php
require_once 'config/database.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "=== STRUCTURE DES TABLES ===\n\n";

$pdo = getDBConnection();

// Structure aerodromes_fr
echo "TABLE: aerodromes_fr\n";
echo "--------------------\n";
$result = $pdo->query("DESCRIBE aerodromes_fr");
while ($row = $result->fetch()) {
    echo sprintf("%-20s %-15s\n", $row['Field'], $row['Type']);
}

echo "\n\n";

// Structure ulm_bases_fr
echo "TABLE: ulm_bases_fr\n";
echo "-------------------\n";
$result = $pdo->query("DESCRIBE ulm_bases_fr");
while ($row = $result->fetch()) {
    echo sprintf("%-20s %-15s\n", $row['Field'], $row['Type']);
}

echo "\n\n";

// Structure destinations
echo "TABLE: destinations\n";
echo "-------------------\n";
$result = $pdo->query("DESCRIBE destinations");
while ($row = $result->fetch()) {
    echo sprintf("%-20s %-15s\n", $row['Field'], $row['Type']);
}

echo "\n\n";

// Exemples de données
echo "EXEMPLE aerodromes_fr (1 ligne):\n";
echo "---------------------------------\n";
$result = $pdo->query("SELECT * FROM aerodromes_fr LIMIT 1");
$row = $result->fetch(PDO::FETCH_ASSOC);
print_r($row);

echo "\n\n";

echo "EXEMPLE ulm_bases_fr (1 ligne):\n";
echo "--------------------------------\n";
$result = $pdo->query("SELECT * FROM ulm_bases_fr LIMIT 1");
$row = $result->fetch(PDO::FETCH_ASSOC);
print_r($row);

echo "</pre>";
