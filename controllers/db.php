<?php
declare(strict_types=1);

// Config DB (XAMPP par défaut). Modifie si besoin.
$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'edukids';
$dbUser = 'root';
$dbPass = '';

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur connexion base de données',
        'detail' => $e->getMessage(),
    ]);
    exit;
}
