<?php

$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    // parse_ini_file yerine manuel okuma (Daha güvenli ve özel karakter dostu)
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Yorum satırlarını atla
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
    }
    
    echo "<pre>Dosya bulundu, manuel okuma yapildi.\n";
    echo "DB_USER: " . ($env['DB_USER'] ?? 'BULUNAMADI') . "\n";
    echo "DB_NAME: " . ($env['DB_NAME'] ?? 'BULUNAMADI') . "\n";
    echo "DB_HOST: " . ($env['DB_HOST'] ?? 'BULUNAMADI') . "\n";
    echo "</pre>";

    $host = $env['DB_HOST'] ?? 'localhost';
    $dbname = $env['DB_NAME'] ?? '';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASS'] ?? '';
    $port = $env['DB_PORT'] ?? 3306;

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<h3 style='color:green'>✅ Veritabanı Bağlantısı Başarılı!</h3>";

        // Tablo Kontrolleri
        $tables = ['registrations', 'admins'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color:green'>✔ <b>$table</b> tablosu mevcut.</p>";
                
                // Kolon Kontrolü (Opsiyonel ama faydalı)
                $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
                echo "<small style='color:#666'>Kolonlar: " . implode(", ", $columns) . "</small><br>";
            } else {
                echo "<p style='color:red'>❌ <b>$table</b> tablosu BULUNAMADI!</p>";
            }
        }

        // Admin Hesabı Kontrolü
        if (in_array('admins', $tables)) {
            $adminCount = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
            if ($adminCount > 0) {
                echo "<p style='color:green'>✔ En az 1 yönetici hesabı tanımlı ($adminCount adet).</p>";
            } else {
                echo "<p style='color:orange'>⚠ Yönetici tablosu boş! Giriş yapamazsınız.</p>";
            }
        }

    }
    catch (PDOException $e) {
        echo "<h3 style='color:red'>❌ Hata: " . $e->getMessage() . "</h3>";
    }
} else {
    echo "<b>Hata: .env dosyası bulunamadı!</b>";
}


