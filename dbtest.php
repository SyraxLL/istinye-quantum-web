<?php

$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    // ini formatında okuma yaparken bazen tırnak işaretleri veya boşluklar sorun olabilir
    $env = parse_ini_file($env_file);
    
    echo "<pre>Dosya bulundu, içerik okunuyor...\n";
    if ($env === false) {
        echo "Hata: parse_ini_file dosyayi okuyamadi (format hatasi olabilir).\n";
    } else {
        echo "Okunan Anahtarlar (Keys): " . implode(", ", array_keys($env)) . "\n";
        echo "DB_USER: " . ($env['DB_USER'] ?? 'BULUNAMADI') . "\n";
        echo "DB_NAME: " . ($env['DB_NAME'] ?? 'BULUNAMADI') . "\n";
        echo "DB_HOST: " . ($env['DB_HOST'] ?? 'BULUNAMADI') . "\n";
    }
    echo "</pre>";


    $host = $env['DB_HOST'] ?? 'localhost';
    $dbname = $env['DB_NAME'] ?? '';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASS'] ?? '';
    $port = 3306;

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<h3>Baglanti basarili!</h3>";
    }
    catch (PDOException $e) {
        echo "<h3 style='color:red'>Hata: " . $e->getMessage() . "</h3>";
    }
} else {
    echo "<b>Hata: .env dosyasi dizinde bulunamadi!</b> Path: " . $env_file;
}
