<?php

$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
    
    $host = $env['DB_HOST'] ?? 'localhost';
    $dbname = $env['DB_NAME'] ?? '';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASS'] ?? '';
    $port = 3306; // Varsayılan port

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Baglanti basarili!";
    }
    catch (PDOException $e) {
        echo "Hata: " . $e->getMessage();
    }
} else {
    echo ".env dosyasi bulunamadi!";
}