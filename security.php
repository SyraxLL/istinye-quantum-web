<?php
/**
 * -------------------------------------------------------------------
 * OYUN İÇİ MERKEZİ GÜVENLİK SİSTEMİ (SECURITY CORE)
 * -------------------------------------------------------------------
 * Bu dosya admin paneli ve herkese açık form sayfalarını korumak için tasarlanmıştır.
 * Katı oturum yönetimi, CSRF koruması, Rate Limiting (Hız Sınırlandırıcı), 
 * Brute-Force engelleme ve Girdi Temizleme (Sanitization) özelliklerini içerir.
 */

// 1. ZORUNLU HTTP GÜVENLİK BAŞLIKLARI (SECURITY HEADERS)
header("X-Frame-Options: DENY"); // Clickjacking Koruması: Başka sitelerin iframe'inde açılmasını engeller
header("X-XSS-Protection: 1; mode=block"); // Tarayıcının dahili XSS filtresini zorunlu kılar
header("X-Content-Type-Options: nosniff"); // MIME-Sniffing saldırılarını durdurur
header("Referrer-Policy: strict-origin-when-cross-origin"); // Gereksiz referrer bilgisi sızıntısını önler
// Not: CSP (Content-Security-Policy) site özellikleri tam bilinmeden kırılmalara yol açabileceği için eklenmedi.

// 2. GÜVENLİ OTURUM YÖNETİMİ (SECURE SESSION HANDLING)
function startSecureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        // Oturum başlarken çerez güvenliğini artır
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            // domain parametresini otomatik bırakıyoruz (bazı sunucularda HTTP_HOST port içerebilir ve çerez reddedilebilir)
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true, // XSS koruması
            'samesite' => 'Lax' // 'Strict' bazen yönlendirmelerde çerezin kaybolmasına neden olabilir
        ]);

        session_start();

        // Session Hijacking Koruması: Kullanıcı Tarayıcısı ve IP'sini bağla
        $currentUserAgent = md5($_SERVER['HTTP_USER_AGENT'] ?? 'unknown_agent');
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip';

        if (!isset($_SESSION['sec_fingerprint'])) {
            $_SESSION['sec_fingerprint'] = hash('sha256', $currentUserAgent . $currentIp);
            $_SESSION['sec_last_regeneration'] = time();
        }
        else {
            // Eğer parmak izi değiştiyse oturum kopyalanmış (çalınmış) olabilir!
            if ($_SESSION['sec_fingerprint'] !== hash('sha256', $currentUserAgent . $currentIp)) {
                session_unset();
                session_destroy();
                die("Güvenlik İhlali: Oturum parmak izi uyumsuzluğu. Lütfen tekrar giriş yapın.");
            }

            // Session Fixation Önlemi: Her 15 dakikada bir Session ID yenile
            if (time() - $_SESSION['sec_last_regeneration'] > 900) {
                session_regenerate_id(true); // Eski oturum dosyasını sil ve yenisini oluştur
                $_SESSION['sec_last_regeneration'] = time();
            }
        }
    }
}

// 3. CSRF (Cross-Site Request Forgery) KORUMASI
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        // Kriptografik olarak güvenli 32 byte rastgele veri oluştur
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token); // Zamanlama saldırısına (Timing Attack) karşı güvenli karşılaştırma
}

// 4. GİRDİ TEMİZLEME (INPUT SANITIZATION) - XSS KORUMASI
function sanitizeInput($data)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }

    // PHP'nin yerleşik fonksiyonları ile zararlı scriptleri HTML entiti'lerine çevirir
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    return $data;
}

// 5. BRUTE-FORCE KORUMASI (Kaba Kuvvet Saldırıları)
// Belirli bir IP adresinden ardışık hatalı giriş miktarını sınırlamak için
function checkBruteForce($maxAttempts = 5, $lockoutMinutes = 3)
{
    if (session_status() === PHP_SESSION_NONE) {
        startSecureSession();
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    $currentTime = time();
    $lockoutSeconds = $lockoutMinutes * 60;

    if (isset($_SESSION['bruteforce_attempts'])) {
        $attemptsInfo = $_SESSION['bruteforce_attempts'];

        // Eğer bekleme süresindeyse (timeout) süresinin dolup dolmadığını kontrol et
        if (isset($attemptsInfo['locked_until']) && $currentTime < $attemptsInfo['locked_until']) {
            $remainingSeconds = $attemptsInfo['locked_until'] - $currentTime;
            return "Güvenlik nedeniyle kısa süreli bir bekleme moduna alındınız. Lütfen " . $remainingSeconds . " saniye sonra tekrar deneyin.";
        }

        // Engel süresi dolmuşsa veya limit dolmadıysa ve 2 saatten eski kayıt varsa sıfırla
        if ($currentTime - $attemptsInfo['last_attempt'] > 7200 ||
        (isset($attemptsInfo['locked_until']) && $currentTime >= $attemptsInfo['locked_until'])) {
            unset($_SESSION['bruteforce_attempts']);
            return true;
        }
    }
    return true; // Girişe izin ver
}

// Başarısız giriş denemesini kaydet
function recordFailedAttempt($maxAttempts = 5, $lockoutMinutes = 3)
{
    $currentTime = time();
    $lockoutSeconds = $lockoutMinutes * 60;

    if (!isset($_SESSION['bruteforce_attempts'])) {
        $_SESSION['bruteforce_attempts'] = [
            'count' => 1,
            'last_attempt' => $currentTime
        ];
    }
    else {
        $_SESSION['bruteforce_attempts']['count']++;
        $_SESSION['bruteforce_attempts']['last_attempt'] = $currentTime;

        // Limit aşıldıysa hesabı kitle
        if ($_SESSION['bruteforce_attempts']['count'] >= $maxAttempts) {
            $_SESSION['bruteforce_attempts']['locked_until'] = $currentTime + $lockoutSeconds;
        }
    }
}

// Başarılı girişte logları temizle
function clearBruteForceLog()
{
    if (isset($_SESSION['bruteforce_attempts'])) {
        unset($_SESSION['bruteforce_attempts']);
    }
}

// 6. RATE LIMITING (Hız Sınırlandırıcı) - Form Spamlara Karşı
function checkRateLimit($limitTokenName, $maxRequests = 3, $windowSeconds = 60)
{
    if (!isset($_SESSION[$limitTokenName])) {
        $_SESSION[$limitTokenName] = [];
    }

    $currentTime = time();

    // Geçmiş zaman penceresinin dışındaki kayıtları temizle
    $validRequests = [];
    foreach ($_SESSION[$limitTokenName] as $reqTime) {
        if ($currentTime - $reqTime <= $windowSeconds) {
            $validRequests[] = $reqTime;
        }
    }

    // Kontrol et ve yeni isteği kaydet
    if (count($validRequests) >= $maxRequests) {
        $_SESSION[$limitTokenName] = $validRequests;
        return false; // Limit aşıldı
    }

    $validRequests[] = $currentTime;
    $_SESSION[$limitTokenName] = $validRequests;
    return true; // İşleme devam
}
?>
