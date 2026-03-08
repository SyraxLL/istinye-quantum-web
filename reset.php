<?php
/**
 * 🛠️ SUNUCU ŞİFRE SIFIRLAMA VE GÜVENLİK TEMİZLEME ARACI
 * 
 * Bu dosyayı sunucunuza yükleyip çalıştırdığınızda:
 * 1. Tarayıcınızdaki tüm oturum engellerini kaldırır.
 * 2. Sunucu veritabanındaki admin şifresini 'admin123' olarak günceller.
 */

session_start();
require_once __DIR__ . '/security.php';

// 1. Tarayıcı Oturumunu ve Çerezleri Temizle
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 2. Veritabanında Şifreyi Güncelle (admin123)
$env_file = __DIR__ . '/.env';
$new_pass = "admin123";
$new_hash = password_hash($new_pass, PASSWORD_DEFAULT);

echo "<div style='font-family:sans-serif; padding:40px; line-height:1.6; max-width:600px; margin:auto; border:1px solid #ddd; border-radius:10px; margin-top:50px;'>";
echo "<h2 style='color:#16a34a; border-bottom:2px solid #16a34a; padding-bottom:10px;'>🚀 Sunucu Kurtarma Başlatıldı</h2>";

if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
    if (isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USER'])) {
        try {
            $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'] . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
            
            // Veritabanındaki admin şifresini 'admin123' yapıyoruz
            // Eğer tablo yoksa hata vermesi için REPLACE yerine UPDATE/INSERT kontrolü
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = 'admin'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $update = $pdo->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
                $update->execute([$new_hash]);
            } else {
                $insert = $pdo->prepare("INSERT INTO admins (username, password) VALUES ('admin', ?)");
                $insert->execute([$new_hash]);
            }
            
            echo "<p style='padding:10px; background:#f0fdf4; color:#166534; border-radius:5px;'>✅ <b>Oturum Engelleri Kaldırıldı ve Şifre Güncellendi!</b></p>";
            echo "<div style='background:#f8fafc; padding:15px; border-radius:5px; margin:20px 0;'>";
            echo "<p><b>Yeni Kullanıcı Adı:</b> admin</p>";
            echo "<p><b>Yeni Şifre:</b> $new_pass</p>";
            echo "</div>";
            echo "<p>Artık sunucunuzdaki <b>admin.php</b> sayfasına gidip bu bilgilerle giriş yapabilirsiniz.</p>";
            
        } catch (PDOException $e) {
            echo "<p style='padding:10px; background:#fef2f2; color:#991b1b; border-radius:5px;'>❌ <b>Veritabanı Hatası:</b> " . $e->getMessage() . "</p>";
            echo "<p>Lütfen sunucudaki <b>.env</b> dosyasındaki veritabanı bilgilerinin doğruluğunu kontrol edin.</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ .env dosyasında veritabanı bilgileri (DB_NAME vb.) eksik!</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Sunucuda .env dosyası bulunamadı!</p>";
}

echo "<hr style='border:0; border-top:1px solid #eee; margin:20px 0;'>";
echo "<div style='text-align:center;'>";
echo "<a href='admin.php' style='display:inline-block; padding:12px 24px; background:#004bb6; color:white; text-decoration:none; border-radius:8px; font-weight:600;'>Panele Dön</a>";
echo "<p style='font-size:0.85rem; color:#666; margin-top:15px;'>⚠️ İşlem bittikten sonra bu dosyayı (reset.php) sunucudan mutlaka silin.</p>";
echo "</div></div>";
?>
