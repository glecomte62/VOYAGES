<?php
echo "1<br>";
require_once '../includes/session.php';
echo "2<br>";
require_once '../config/database.php';
echo "3<br>";
require_once '../includes/functions.php';
echo "4<br>";
requireAdmin('../index.php');
echo "5 - requireAdmin OK<br>";
