<?php
require __DIR__ . '/../src/Database.php';

$pdo = Database::connect();

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('TRUNCATE TABLE incidents');
$pdo->exec('TRUNCATE TABLE roads');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$pdo->exec(file_get_contents(__DIR__ . '/../db/seed.sql'));

echo "Datos reiniciados\n";