<?php
require_once __DIR__ . '/security.php';
startSecureSession();

$env_file = __DIR__ . '/.env';
$env = loadEnv($env_file);

// Çıkış İşlemi
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: admin.php");
    exit;
}

$error = '';
$error_detail = '';
$error_code = '';

// Brute Force Kontrolü
$bruteForceStatus = checkBruteForce(5, 3); // 5 hak, 3 dakika bekleme (timeout)
if ($bruteForceStatus !== true) {
    $error = "Hız Sınırı Engeli";
    $error_detail = $bruteForceStatus; 
    $error_code = "ERR_BRUTE_FORCE";
}

// Giriş İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'], $_POST['username'])) {
    
    // CSRF Koruması
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Güvenlik İhlali";
        $error_detail = "CSRF Token geçersiz veya eksik. Lütfen sayfayı yenileyip tekrar deneyin.";
        $error_code = "ERR_CSRF_TOKEN";
    } else {
        if ($bruteForceStatus === true) {
        $input_user = sanitizeInput($_POST['username']);
        $input_pass = $_POST['password']; // Şifreyi sanitize etmiyoruz çünkü özel karakterler içerebilir ve verify edilecek
        
        // Veritabanından Kullanıcıyı Kontrol Et
        $login_success = false;
        if ($env && isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USER'])) {
            try {
                $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'] . ";charset=" . ($env['DB_CHARSET'] ?? 'utf8mb4');
                $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $pdo->prepare("SELECT password FROM admins WHERE username = ?");
                $stmt->execute([$input_user]);
                $admin_record = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin_record && password_verify($input_pass, $admin_record['password'])) {
                    $login_success = true;
                } else {
                    $error_code = "ERR_INVALID_CREDENTIALS";
                    $error_detail = $admin_record ? "Şifre uyuşmuyor." : "Kullanıcı bulunamadı.";
                }
            } catch (PDOException $e) {
                $error = "Veritabanı Bağlantı Sorunu";
                $error_code = "ERR_DB_" . $e->getCode();
                $error_detail = "Asıl Neden: " . $e->getMessage();
            }
        } else {
            $error = "Sistem Yapılandırma Hatası";
            $error_code = "ERR_CONFIG_MISSING";
            $error_detail = ".env dosyasında veritabanı bilgileri eksik veya dosya bulunamadı.";
        }
        
        if ($login_success) {
            clearBruteForceLog();
            
            // Oturum ID'sini güvenli bir şekilde yenile (Session Fixation koruması)
            session_regenerate_id(true); 
            
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            if (empty($error)) {
                recordFailedAttempt(5, 3);
                $error = 'Giriş Başarısız!';
                // Eğer hata kodu set edilmemişse genel bir tane atayalım
                if (empty($error_code)) $error_code = "ERR_AUTH_FAILED";
                if (empty($error_detail)) $error_detail = "Kullanıcı adı veya şifre yanlış.";
            }
        }
        }
    }
}

$logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// İşlem Mesajları
$action_msg = '';
$action_err = '';

// Veritabanı Bağlantısı (Genel Kullanım İçin)
$pdo = null;
if ($logged_in && $env && isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USER'])) {
    try {
        $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'] . ";charset=" . ($env['DB_CHARSET'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $error = "Veritabanı bağlantı hatası: " . $e->getMessage();
    }
}

if ($logged_in && $pdo) {
    // SİLME İŞLEMİ (Artık POST üzerinden yapılıyor)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            $action_err = "Güvenlik İhlali: CSRF Token geçersiz!";
        } else {
            $del_id = (int)$_POST['delete_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
                $stmt->execute([$del_id]);
                $action_msg = "Takım başarıyla silindi.";
            } catch (PDOException $e) {
                $action_err = "Silme hatası: " . $e->getMessage();
            }
        }
    }
    
    // EKLEME / DÜZENLEME İŞLEMİ
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_team'])) {
        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            $action_err = "Güvenlik İhlali: CSRF Token geçersiz!";
        } else {
            $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
            // Girdileri temizle
            $team_name = sanitizeInput($_POST['team_name'] ?? '');
            $captain_name = sanitizeInput($_POST['captain_name'] ?? '');
            $captain_phone = sanitizeInput($_POST['captain_phone'] ?? '');
            $captain_email = filter_var($_POST['captain_email'] ?? '', FILTER_SANITIZE_EMAIL);
            $submitted_lang = sanitizeInput($_POST['submitted_lang'] ?? 'tr');
            
            // Oyuncuları JSON formatına çevirme
            $players = [];
            for ($i = 1; $i <= 12; $i++) {
                if (!empty($_POST["player$i"])) {
                    $players[] = sanitizeInput(trim($_POST["player$i"]));
                }
            }
            $players_json = json_encode($players, JSON_UNESCAPED_UNICODE);
            
            if (empty($team_name) || empty($captain_name)) {
                $action_err = "Takım ve Kaptan adı zorunludur.";
            } else {
                try {
                    if ($team_id > 0) {
                        // GÜNCELLE
                        $stmt = $pdo->prepare("UPDATE registrations SET team_name=?, captain_name=?, captain_phone=?, captain_email=?, players=?, submitted_lang=? WHERE id=?");
                        $stmt->execute([$team_name, $captain_name, $captain_phone, $captain_email, $players_json, $submitted_lang, $team_id]);
                        $action_msg = "Takım başarıyla güncellendi.";
                    } else {
                        // YENİ EKLE
                        $stmt = $pdo->prepare("INSERT INTO registrations (team_name, captain_name, captain_phone, captain_email, players, submitted_lang) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$team_name, $captain_name, $captain_phone, $captain_email, $players_json, $submitted_lang]);
                        $action_msg = "Yeni takım başarıyla eklendi.";

                        // Yöneticiye E-posta Bildirimi Gönder (Manuel Ekleme)
                        $to = "6002mustafaadsan@gmail.com";
                        $subject = "Admin Panel: Yeni Takım Eklendi - " . $team_name;
                        $email_body = "Yönetici paneli üzerinden manuel olarak yeni bir takım kaydı oluşturuldu.\n\n";
                        $email_body .= "Takım Adı: " . $team_name . "\n";
                        $email_body .= "Kaptan: " . $captain_name . "\n";
                        $email_body .= "E-posta: " . $captain_email . "\n";
                        $email_body .= "Telefon: " . $captain_phone . "\n";
                        $email_body .= "Dil: " . strtoupper($submitted_lang) . "\n";

                        $headers = "From: adminpanel@istinyequantum.com\r\n";
                        $headers .= "Reply-To: " . $captain_email . "\r\n";
                        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
                        $headers .= "X-Mailer: PHP/" . phpversion();

                        @mail($to, $subject, $email_body, $headers);
                    }
                } catch (PDOException $e) {
                    $action_err = "Kayıt hatası: " . $e->getMessage();
                }
            }
        }
    }
}

// Veritabanından Kayıtları Çekme (Yenileme)
$registrations = [];
$fullTeams = [];
$incompleteTeams = [];

if ($logged_in && $pdo) {
    // ── Gelişmiş Turnuva Yönetimi İşlemleri ──
    
    // 1. Site Modu Güncelleme
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_site_mode'])) {
        if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $new_mode = $_POST['site_mode'] === 'bracket' ? 'bracket' : 'registration';
            $stmt = $pdo->prepare("INSERT INTO settings (meta_key, meta_value) VALUES ('site_mode', ?) ON DUPLICATE KEY UPDATE meta_value = ?");
            $stmt->execute([$new_mode, $new_mode]);
            $action_msg = "Site modu başarıyla güncellendi: " . ($new_mode === 'bracket' ? 'Turnuva Ağacı' : 'Başvuru Formu');
        }
    }

    // 2. Maç Verilerini Güncelleme (Tekli)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_match'])) {
        if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $m_id = (int)$_POST['match_id'];
            $t1_name = sanitizeInput($_POST['team1_name'] ?? '');
            $t2_name = sanitizeInput($_POST['team2_name'] ?? '');
            $s1 = (int)($_POST['score1'] ?? 0);
            $s2 = (int)($_POST['score2'] ?? 0);
            $w_name = sanitizeInput($_POST['winner_name'] ?? '');
            $m_time = sanitizeInput($_POST['match_time'] ?? '');

            try {
                try { $pdo->exec("ALTER TABLE matches ADD COLUMN match_time VARCHAR(50) DEFAULT NULL"); } catch(PDOException $e) {}
                $stmt = $pdo->prepare("UPDATE matches SET team1_name = ?, team2_name = ?, score1 = ?, score2 = ?, winner_name = ?, match_time = ? WHERE id = ?");
                $stmt->execute([$t1_name, $t2_name, $s1, $s2, $w_name, $m_time, $m_id]);
                $stmt = $pdo->prepare("SELECT next_match_id, next_match_slot FROM matches WHERE id = ?");
                $stmt->execute([$m_id]);
                $next = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($next && $next['next_match_id'] && !empty($w_name)) {
                    $slot_col = ($next['next_match_slot'] == 1) ? 'team1_name' : 'team2_name';
                    $stmt = $pdo->prepare("UPDATE matches SET $slot_col = ? WHERE id = ?");
                    $stmt->execute([$w_name, $next['next_match_id']]);
                }
                $action_msg = "Maç bilgileri güncellendi.";
            } catch (PDOException $e) {
                $action_err = "Maç güncelleme hatası: " . $e->getMessage();
            }
        }
    }

    // 2b. TOPLU Maç Güncelleme
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_update_matches'])) {
        if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $matches_data = $_POST['matches'] ?? [];
            $updated = 0;
            try {
                try { $pdo->exec("ALTER TABLE matches ADD COLUMN match_time VARCHAR(50) DEFAULT NULL"); } catch(PDOException $e) {}

                $stmt_update = $pdo->prepare(
                    "UPDATE matches SET team1_name = ?, team2_name = ?, score1 = ?, score2 = ?, winner_name = ?, match_time = ? WHERE id = ?"
                );

                // ─── 1. GEÇİŞ: Tüm maç verilerini kaydet ───
                foreach ($matches_data as $mid => $data) {
                    $mid    = (int)$mid;
                    $t1     = sanitizeInput($data['team1_name'] ?? '');
                    $t2     = sanitizeInput($data['team2_name'] ?? '');
                    $s1     = (int)($data['score1'] ?? 0);
                    $s2     = (int)($data['score2'] ?? 0);
                    $winner = sanitizeInput($data['winner_name'] ?? '');
                    $mtime  = sanitizeInput($data['match_time'] ?? '');

                    $stmt_update->execute([$t1, $t2, $s1, $s2, $winner, $mtime, $mid]);
                    $updated++;
                }

                // ─── 2. GEÇİŞ: Kazananları bir sonraki tura ilerlet ───
                $stmt_nxt = $pdo->prepare(
                    "SELECT next_match_id, next_match_slot FROM matches WHERE id = ?"
                );
                $stmt_adv = $pdo->prepare(
                    "UPDATE matches SET team1_name = ? WHERE id = ?"
                );
                $stmt_adv2 = $pdo->prepare(
                    "UPDATE matches SET team2_name = ? WHERE id = ?"
                );

                foreach ($matches_data as $mid => $data) {
                    $mid    = (int)$mid;
                    $winner = sanitizeInput($data['winner_name'] ?? '');

                    if (empty($winner)) continue; // kazanan yoksa atla

                    $stmt_nxt->execute([$mid]);
                    $next = $stmt_nxt->fetch(PDO::FETCH_ASSOC);

                    if ($next && !empty($next['next_match_id'])) {
                        $next_id   = (int)$next['next_match_id'];
                        $next_slot = (int)$next['next_match_slot'];

                        if ($next_slot === 1) {
                            $stmt_adv->execute([$winner, $next_id]);
                        } else {
                            $stmt_adv2->execute([$winner, $next_id]);
                        }
                    }
                }

                $action_msg = "✅ $updated maç toplu olarak kaydedildi. Kazananlar bir sonraki tura aktarıldı.";
            } catch (PDOException $e) {
                $action_err = "Toplu güncelleme hatası: " . $e->getMessage();
            }
        }
    }

    try {
        $stmt = $pdo->query("SELECT * FROM registrations ORDER BY created_at ASC");
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($registrations as $reg) {
            if (strpos($reg['team_name'], '(Eksik Kadro)') !== false || $reg['team_name'] === 'Bireysel/Eksik Başvuru') {
                $incompleteTeams[] = $reg;
            } else {
                $fullTeams[] = $reg;
            }
        }

        // Ayarları Çek
        $stmt = $pdo->query("SELECT * FROM settings WHERE meta_key = 'site_mode'");
        $site_mode_row = $stmt->fetch(PDO::FETCH_ASSOC);
        $site_mode = $site_mode_row ? $site_mode_row['meta_value'] : 'registration';

        // Maçları Çek
        $stmt = $pdo->query("SELECT * FROM matches ORDER BY round ASC, match_order ASC");
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Base table or view not found") === false) {
            $error = "Veri çekme hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="volleyball-transparent.png">
    <title>Yönetim Paneli - Voleybol Turnuvası</title>
    <link rel="stylesheet" href="style.css?v=4">
    <style>
        body {
            display: block;        /* flex yerine block — admin grid'ini bozmasın */
            justify-content: unset;
            align-items: unset;
            padding: 2rem 1rem;
            min-height: 100vh;
        }

        /* Kayıt formuna özgü fixed elementleri admin panelinde gizle */
        .sks-logo, .sks-logo-link,
        .fab-whatsapp,
        .mobile-sticky-bar,
        .court-line {
            display: none !important;
        }

        /* ── Modern Premium Login Box ── */
        .login-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 8rem);
            width: 100%;
        }

        .login-box {
            width: 100%;
            max-width: 440px;
            padding: 3rem 2.5rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 45, 114, 0.1), 0 5px 15px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            text-align: center;
            position: relative;
            z-index: 2;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .login-header svg {
            color: var(--isu-blue);
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 6px rgba(0, 45, 114, 0.2));
            animation: bounceIn 1s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .login-box h2 {
            color: var(--isu-blue);
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .login-box p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .input-wrapper svg {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: color 0.3s ease;
            width: 20px;
            height: 20px;
        }

        .input-wrapper input {
            width: 100%;
            padding: 1.1rem 1.1rem 1.1rem 3.5rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1.05rem;
            color: var(--text-main);
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .input-wrapper input:focus {
            border-color: var(--isu-blue);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0, 45, 114, 0.1), inset 0 2px 4px rgba(0,0,0,0.01);
            outline: none;
        }

        .input-wrapper input:focus + svg,
        .input-wrapper input:not(:placeholder-shown) + svg {
            color: var(--isu-blue);
        }

        .login-btn {
            width: 100%;
            padding: 1.1rem;
            background: linear-gradient(135deg, var(--isu-blue) 0%, #004dbe 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 45, 114, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 45, 114, 0.4);
            background: linear-gradient(135deg, #0040a8 0%, var(--isu-blue-light) 100%);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-msg {
            background: #fef2f2;
            color: var(--isu-red);
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid #fecaca;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% {transform: translateX(0);}
            20%, 60% {transform: translateX(-5px);}
            40%, 80% {transform: translateX(5px);}
        }

        /* ── Admin Dashboard Stilleri ── */
        .admin-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: var(--surface);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 45, 114, 0.15);
            position: relative;
            z-index: 2;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }
        .admin-header h1 {
            color: var(--isu-blue);
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
        }
        .logout-btn {
            background: var(--isu-red);
            color: white;
            padding: 0.6rem 1.2rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(227, 24, 55, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .logout-btn:hover {
            background: #c5142e;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(227, 24, 55, 0.4);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            margin-top: 1rem;
        }
        th, td {
            text-align: left;
            padding: 1.1rem;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background: rgba(0, 45, 114, 0.05);
            color: var(--isu-blue);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        tr { transition: background 0.2s ease; }
        tr:hover { background: var(--input-bg); }
        .player-list {
            margin: 0;
            padding-left: 1.2rem;
            color: var(--text-main);
            font-size: 0.9rem;
        }
        .player-list li { margin-bottom: 0.2rem; }
        .badge {
            display: inline-block;
            background: var(--isu-blue-light);
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .date-text {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.4rem;
            font-weight: 500;
        }
        .player-role-badge {
            display: inline-block;
            font-size: 0.7rem;
            padding: 1px 4px;
            border-radius: 4px;
            font-weight: 700;
            margin-right: 4px;
            text-transform: uppercase;
        }
        .role-asil {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .role-yedek {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .table-responsive {
            overflow-x: auto;
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        /* ── Turnuva Yönetimi Özel Stilleri ── */
        .mode-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e2e8f0;
        }
        .mode-info h3 { margin: 0; color: var(--isu-blue); font-size: 1.2rem; }
        .mode-info p { margin: 0.3rem 0 0 0; font-size: 0.9rem; color: var(--text-muted); }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 240px;
            height: 44px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
            display: flex;
            align-items: center;
            padding: 0 10px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 36px;
            width: 110px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        input:checked + .toggle-slider { background-color: var(--isu-blue); }
        input:checked + .toggle-slider:before { transform: translateX(122px); }
        
        .toggle-label {
            position: relative;
            z-index: 1;
            flex: 1;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 700;
            color: #475569;
            transition: color 0.3s;
            pointer-events: none;
        }
        input:checked + .toggle-slider .label-reg { color: white; opacity: 0.5; }
        input:checked + .toggle-slider .label-bracket { color: var(--isu-blue); }
        input:not(:checked) + .toggle-slider .label-reg { color: var(--isu-blue); }
        input:not(:checked) + .toggle-slider .label-bracket { color: white; opacity: 0.5; }

        /* ── Maç Kartları (Turnuva Yönetimi) ── */
        .bracket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
            margin-top: 1.5rem;
        }

        .match-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .match-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        /* Tur renk kodu: header şeridi */
        .match-card-header {
            padding: 0.65rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .round-1 .match-card-header { background: linear-gradient(90deg, #f1f5f9, #e2e8f0); border-bottom: 2px solid #94a3b8; }
        .round-2 .match-card-header { background: linear-gradient(90deg, #eff6ff, #dbeafe); border-bottom: 2px solid #60a5fa; }
        .round-3 .match-card-header { background: linear-gradient(90deg, #f0fdf4, #dcfce7); border-bottom: 2px solid #4ade80; }
        .round-4 .match-card-header { background: linear-gradient(90deg, #fffbeb, #fef3c7); border-bottom: 2px solid #fbbf24; }

        .match-round-tag {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .round-1 .match-round-tag { background: #e2e8f0; color: #475569; }
        .round-2 .match-round-tag { background: #dbeafe; color: #1d4ed8; }
        .round-3 .match-round-tag { background: #dcfce7; color: #15803d; }
        .round-4 .match-round-tag { background: #fef3c7; color: #b45309; }

        .match-id-tag {
            font-size: 0.72rem;
            font-weight: 700;
            color: #94a3b8;
        }

        .match-card-body {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Takım satırı */
        .team-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .team-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #94a3b8;
            min-width: 20px;
            text-align: center;
            text-transform: uppercase;
        }
        .team-row input[type="text"] {
            flex: 1;
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-main);
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f8fafc;
        }
        .team-row input[type="text"]:focus {
            outline: none;
            border-color: var(--isu-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 45, 114, 0.08);
        }
        .team-row input[type="number"] {
            width: 52px;
            height: 38px;
            padding: 0.3rem;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            text-align: center;
            font-weight: 800;
            font-size: 1rem;
            color: var(--text-main);
            background: #f8fafc;
            transition: border-color 0.2s;
        }
        .team-row input[type="number"]:focus {
            outline: none;
            border-color: var(--isu-blue);
            background: white;
        }

        .vs-divider {
            text-align: center;
            font-size: 0.7rem;
            font-weight: 800;
            color: #cbd5e1;
            letter-spacing: 1px;
            padding: 0 0.25rem;
        }

        /* Kazanı seçimi */
        .winner-select {
            padding-top: 0.75rem;
            border-top: 1px solid #f1f5f9;
        }
        .winner-select label {
            font-size: 0.72rem;
            font-weight: 800;
            color: #059669;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 0.4rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .winner-select select {
            width: 100%;
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            border: 1.5px solid #10b981;
            color: #059669;
            font-weight: 700;
            font-size: 0.9rem;
            background: #f0fdf4;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .winner-select select:focus { outline: none; border-color: #059669; }

        /* Güncelle butonu */
        .update-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--isu-blue) 0%, #0047b3 100%);
            color: white;
            border: none;
            padding: 0.65rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }
        .update-btn:hover {
            background: linear-gradient(135deg, #003380 0%, #005ce6 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 45, 114, 0.3);
        }
        .update-btn:active { transform: translateY(0); }

        /* Toplu Kaydet Butonu */
        .bulk-save-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .bulk-save-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
        }
        .bulk-save-btn:active { transform: translateY(0); }
    </style>
</head>
<body>
<?php if (!$logged_in): ?>
    <div class="login-wrap">
        <div class="login-box">
            <div class="login-header">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <h2>Yönetici Girişi</h2>
                <p>Devam etmek için lütfen giriş yapın.</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg" style="display: block; text-align: left; padding: 1rem;">
                    <div style="display: flex; align-items: center; margin-bottom: 0.5rem; color: #dc2626; font-weight: 700;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php if ($error_code): ?>
                        <div style="font-size: 0.75rem; background: #fee2e2; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-bottom: 0.5rem; color: #991b1b; font-weight: 600;">Kod: <?php echo htmlspecialchars($error_code); ?></div>
                    <?php endif; ?>
                    <?php if ($error_detail): ?>
                        <div style="font-size: 0.85rem; color: #4b5563; border-top: 1px solid #fca5a5; padding-top: 0.5rem; word-break: break-all;">
                            <strong>Nedeni:</strong> <?php echo htmlspecialchars($error_detail); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                <div class="input-wrapper">
                    <input type="text" name="username" placeholder="Kullanıcı Adı" required autocomplete="username">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                
                <div class="input-wrapper">
                    <input type="password" name="password" placeholder="Şifre" required autocomplete="current-password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>

                <button type="submit" class="login-btn">
                    Giriş Yap
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="admin-container">
        <div class="admin-header">
            <h1 style="display: flex; align-items: center; flex-wrap: wrap; gap: 1rem;">
                Turnuva Kayıtları 
                <span class="header-counters" style="display: inline-flex; align-items: center; gap: 0.5rem; font-family: system-ui, -apple-system, sans-serif;">
                    <span style="font-size: 0.85rem; padding: 0.3rem 0.8rem; background: #f1f5f9; color: #475569; border-radius: 6px; font-weight: 700; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Toplam: <?php echo count($registrations); ?></span>
                    <span style="font-size: 0.85rem; padding: 0.3rem 0.8rem; background: #e0f2fe; color: #0369a1; border-radius: 6px; font-weight: 700; border: 1px solid #bae6fd; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Tam Takım: <?php echo count($fullTeams); ?></span>
                    <span style="font-size: 0.85rem; padding: 0.3rem 0.8rem; background: #fef2f2; color: #b91c1c; border-radius: 6px; font-weight: 700; border: 1px solid #fecaca; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Bireysel/Eksik: <?php echo count($incompleteTeams); ?></span>
                </span>
            </h1>
            <div style="display: flex; gap: 0.8rem; align-items: center;">
                <a href="export_pdf.php" target="_blank" class="login-btn" style="display: inline-flex; margin: 0; width: auto; padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); line-height: 1.2; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); font-size: 0.95rem; font-family: system-ui, -apple-system, sans-serif; border: none; border-radius: 8px; color: white; cursor: pointer; text-decoration: none; font-weight: bold;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    PDF İndir
                </a>
                <a href="?logout=1" class="logout-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Çıkış Yap
                </a>
            </div>
        </div>
        
            <?php
            // Şifre Değiştirme İşlemi
            $settings_msg = '';
            $settings_err = '';
            if (isset($_POST['change_password'])) {
                if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                    $settings_err = "Güvenlik İhlali: CSRF Token geçersiz!";
                } else {
                    $current_pass = $_POST['current_pass'] ?? '';
                    $new_pass = $_POST['new_pass'] ?? '';
                    $new_pass_confirm = $_POST['new_pass_confirm'] ?? '';
                    
                    if (empty($current_pass) || empty($new_pass) || empty($new_pass_confirm)) {
                        $settings_err = "Lütfen tüm alanları doldurun.";
                    } elseif ($new_pass !== $new_pass_confirm) {
                        $settings_err = "Yeni şifreler birbiriyle eşleşmiyor.";
                    } elseif (strlen($new_pass) < 5) {
                        $settings_err = "Yeni şifre en az 5 karakter olmalıdır.";
                    } else {
                        // Mevcut şifreyi doğrula
                        try {
                            $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'] . ";charset=" . ($env['DB_CHARSET'] ?? 'utf8mb4');
                            $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            $stmt = $pdo->prepare("SELECT id, password FROM admins LIMIT 1");
                            $stmt->execute();
                            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($admin && password_verify($current_pass, $admin['password'])) {
                                // Şifreyi güncelle
                                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                                $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                                $upd->execute([$new_hash, $admin['id']]);
                                $settings_msg = "Şifreniz başarıyla değiştirildi! Yeni şifrenizle giriş yapabilirsiniz.";
                            } else {
                                $settings_err = "Mevcut şifreniz hatalı.";
                            }
                        } catch (PDOException $e) {
                            $settings_err = "Hata: " . $e->getMessage();
                        }
                    }
                }
            }
            ?>
            
            <style>
                .admin-tabs {
                    display: flex;
                    gap: 1rem;
                    margin-bottom: 2rem;
                    border-bottom: 2px solid var(--border-color);
                    padding-bottom: 1rem;
                }
                .tab-btn {
                    padding: 0.8rem 1.5rem;
                    background: transparent;
                    border: none;
                    font-size: 1.05rem;
                    font-weight: 700;
                    color: var(--text-muted);
                    cursor: pointer;
                    border-radius: 8px;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }
                .tab-btn:hover {
                    background: rgba(0, 45, 114, 0.05);
                    color: var(--isu-blue);
                }
                .tab-btn.active {
                    background: var(--isu-blue);
                    color: white;
                    box-shadow: 0 4px 10px rgba(0, 45, 114, 0.2);
                }
                .tab-content {
                    display: none;
                    animation: fadeIn 0.4s ease-out;
                }
                .tab-content.active {
                    display: block;
                }
                .settings-card {
                    background: #f8fafc;
                    border: 1px solid var(--border-color);
                    border-radius: 16px;
                    padding: 2.5rem;
                    max-width: 500px;
                }
                .settings-card h3 {
                    margin-top: 0;
                    color: var(--isu-blue);
                    margin-bottom: 1.5rem;
                    font-size: 1.4rem;
                }
                .settings-card input {
                    background: white;
                }
                .form-group {
                    margin-bottom: 1.25rem;
                }
                .form-group label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 0.5rem;
                    color: var(--text-main);
                    font-size: 0.95rem;
                }
                .success-msg {
                    background: #ecfdf5;
                    color: #059669;
                    padding: 1rem;
                    border-radius: 10px;
                    border-left: 4px solid #10b981;
                    margin-bottom: 1.5rem;
                    font-weight: 600;
                    font-size: 0.95rem;
                }
            </style>
            
            <div class="admin-tabs">
                <button class="tab-btn active" id="tab-records" onclick="switchTab('records')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Kayıtlar
                </button>
                <button class="tab-btn" id="tab-tournament" onclick="switchTab('tournament')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m11 17 2 2 4-4"></path><path d="m3 3 18 18"></path><path d="M7 11V7a5 5 0 0 1 7-4.6"></path><path d="M21 11v11"></path><path d="M3 11v11"></path><path d="m15 11 6 6"></path></svg>
                    Turnuva Yönetimi
                </button>
                <button class="tab-btn" id="tab-settings" onclick="switchTab('settings')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Ayarlar
                </button>
            </div>
            
            <!-- TURNUVA YÖNETİMİ TAB -->
            <div id="tournament" class="tab-content">
                <div class="mode-card">
                    <div class="mode-info">
                        <h3>Site Görünüm Modu</h3>
                        <p>Web sitesinde başvuru formu mu yoksa turnuva ağacı mı görünsün?</p>
                    </div>
                    <form method="POST" id="mode-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <input type="hidden" name="update_site_mode" value="1">
                        <label class="toggle-switch">
                            <input type="checkbox" name="site_mode" value="bracket" <?php echo ($site_mode === 'bracket' ? 'checked' : ''); ?> onchange="document.getElementById('mode-form').submit();">
                            <span class="toggle-slider">
                                <span class="toggle-label label-reg">Kayıt Formu</span>
                                <span class="toggle-label label-bracket">Turnuva Ağacı</span>
                            </span>
                        </label>
                    </form>
                </div>

                <div class="bracket-editor">
                    <h3 style="color: var(--isu-blue); margin-top: 2rem;">16 Takımlık Turnuva Ağacı Maçları</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Tüm değişiklikleri yaptıktan sonra en alttaki <strong>Tümünü Kaydet</strong> butonuna basın.</p>

                    <!-- TEK FORM — Toplu Güncelleme -->
                    <form method="POST" id="bulk-match-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <input type="hidden" name="bulk_update_matches" value="1">

                        <!-- Üst Kaydet Butonu -->
                        <div style="display: flex; justify-content: flex-end; margin: 1rem 0 0.5rem;">
                            <button type="submit" class="bulk-save-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                Tümünü Kaydet
                            </button>
                        </div>

                        <div class="bracket-grid">
                        <?php
                        $rounds = [
                            1 => "Son 16 (Round of 16)",
                            2 => "Çeyrek Final",
                            3 => "Yarı Final",
                            4 => "Final"
                        ];

                        foreach ($matches as $match):
                            $roundClass = 'round-' . $match['round'];
                            $mid = $match['id'];
                        ?>
                            <div class="match-card <?php echo $roundClass; ?>">
                                <!-- Kart Başlığı -->
                                <div class="match-card-header">
                                    <span class="match-round-tag"><?php echo $rounds[$match['round']]; ?></span>
                                    <span class="match-id-tag">Maç #<?php echo $mid; ?></span>
                                </div>

                                <div class="match-card-body">

                                    <!-- Maç Saati -->
                                    <div style="padding: 0.5rem 0.75rem; background: linear-gradient(135deg, #eff6ff, #f0fdf4); border-radius: 8px; border: 1px solid #bae6fd;">
                                        <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.7rem; font-weight: 800; color: #0369a1; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">
                                            🕒 Maç Saati / Tarih
                                        </label>
                                        <input type="text"
                                            name="matches[<?php echo $mid; ?>][match_time]"
                                            value="<?php echo htmlspecialchars($match['match_time'] ?? ''); ?>"
                                            placeholder="örn: 12 Nisan 14:00"
                                            style="width: 100%; border: 1.5px solid #7dd3fc; border-radius: 6px; padding: 0.4rem 0.6rem; font-size: 0.85rem; background: white; text-align: center; font-weight: 600; color: #0c4a6e; outline: none;"
                                            onfocus="this.style.borderColor='#0284c7'"
                                            onblur="this.style.borderColor='#7dd3fc'"
                                        >
                                    </div>

                                    <!-- Takım 1 -->
                                    <div class="team-row">
                                        <span class="team-label">T1</span>
                                        <input type="text" name="matches[<?php echo $mid; ?>][team1_name]"
                                            value="<?php echo htmlspecialchars($match['team1_name'] ?? ''); ?>"
                                            placeholder="1. Takım Adı">
                                        <input type="number" name="matches[<?php echo $mid; ?>][score1]"
                                            value="<?php echo $match['score1']; ?>"
                                            min="0" title="Skor">
                                    </div>

                                    <div class="vs-divider">— VS —</div>

                                    <!-- Takım 2 -->
                                    <div class="team-row">
                                        <span class="team-label">T2</span>
                                        <input type="text" name="matches[<?php echo $mid; ?>][team2_name]"
                                            value="<?php echo htmlspecialchars($match['team2_name'] ?? ''); ?>"
                                            placeholder="2. Takım Adı">
                                        <input type="number" name="matches[<?php echo $mid; ?>][score2]"
                                            value="<?php echo $match['score2']; ?>"
                                            min="0" title="Skor">
                                    </div>

                                    <!-- Kazanan Seç -->
                                    <div class="winner-select">
                                        <label>🏆 Kazanan</label>
                                        <select name="matches[<?php echo $mid; ?>][winner_name]">
                                            <option value="">— Seçilmedi —</option>
                                            <?php if(!empty($match['team1_name'])): ?>
                                                <option value="<?php echo htmlspecialchars($match['team1_name']); ?>"
                                                    <?php echo ($match['winner_name'] == $match['team1_name'] ? 'selected' : ''); ?>>
                                                    <?php echo htmlspecialchars($match['team1_name']); ?>
                                                </option>
                                            <?php endif; ?>
                                            <?php if(!empty($match['team2_name'])): ?>
                                                <option value="<?php echo htmlspecialchars($match['team2_name']); ?>"
                                                    <?php echo ($match['winner_name'] == $match['team2_name'] ? 'selected' : ''); ?>>
                                                    <?php echo htmlspecialchars($match['team2_name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                </div><!-- /match-card-body -->
                            </div>
                        <?php endforeach; ?>
                        </div><!-- /bracket-grid -->

                        <!-- Alt Kaydet Butonu -->
                        <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                            <button type="submit" class="bulk-save-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                Tümünü Kaydet
                            </button>
                        </div>
                    </form><!-- /bulk-match-form -->
                </div>
            </div>

            <!-- KAYITLAR TAB -->
            <div id="records" class="tab-content active">
                <div class="table-responsive" style="margin-bottom: 3rem;">
                    <h3 style="color: var(--isu-blue); margin-top: 0; margin-bottom: 1rem; padding-left: 0.5rem; border-left: 4px solid var(--isu-blue);">Tam Takım Başvuruları</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Takım Adı</th>
                                <th>Kaptan Bilgileri</th>
                                <th>Oyuncu Kadrosu</th>
                                <th>Dil / Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fullTeams as $index => $row): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--text-muted);">
                                    #<?php echo ($index + 1); ?>
                                </td>
                                <td>
                                    <strong style="color: var(--isu-blue); font-size: 1.05rem;"><?php echo htmlspecialchars($row['team_name'] ?? ''); ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($row['captain_name'] ?? ''); ?></div>
                                    <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.2rem;">📞 <?php echo htmlspecialchars($row['captain_phone'] ?? ''); ?></div>
                                    <div style="font-size: 0.9rem; color: var(--text-muted);">✉️ <?php echo htmlspecialchars($row['captain_email'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $playersArr = isset($row['players']) ? json_decode($row['players'], true) : null;
                                    if ($playersArr && is_array($playersArr)): ?>
                                        <ul class="player-list">
                                            <?php foreach ($playersArr as $i => $p): ?>
                                                <li>
                                                    <?php if ($i < 6): ?>
                                                        <span class="player-role-badge role-asil">Asil</span>
                                                    <?php else: ?>
                                                        <span class="player-role-badge role-yedek">Yedek</span>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($p); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-style: italic;">Veri yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['submitted_lang'])): ?>
                                        <span class="badge"><?php echo strtoupper(htmlspecialchars($row['submitted_lang'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (isset($row['created_at'])): ?>
                                        <div class="date-text">
                                            <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($row['created_at']))); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <button type="button" class="action-btn edit-btn" onclick='openEditModal(<?php echo json_encode($row); ?>)'>Düzenle</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bu takımı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="action-btn delete-btn">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($fullTeams) === 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">Henüz tam takım başvurusu bulunmamaktadır.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive" style="border: 2px solid #fee2e2; border-radius: 12px; padding: 1rem; background-color: #fef2f2;">
                    <h3 style="color: #dc2626; margin-top: 0; margin-bottom: 1rem; padding-left: 0.5rem; border-left: 4px solid #dc2626;">Bireysel / Eksik Takım Başvuruları ⚠️</h3>
                    <table style="background: white; border-radius: 8px; overflow: hidden;">
                        <thead style="background: #fee2e2; color: #991b1b;">
                            <tr>
                                <th>No</th>
                                <th>Takım Adı</th>
                                <th>Kaptan Bilgileri</th>
                                <th>Oyuncu Kadrosu</th>
                                <th>Dil / Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incompleteTeams as $index => $row): ?>
                            <tr style="border-bottom: 1px solid #fecaca;">
                                <td style="font-weight: 700; color: #991b1b;">
                                    #<?php echo ($index + 1); ?>
                                </td>
                                <td>
                                    <strong style="color: #dc2626; font-size: 1.05rem;"><?php echo htmlspecialchars($row['team_name'] ?? ''); ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600; margin-bottom: 0.2rem; color: #7f1d1d;"><?php echo htmlspecialchars($row['captain_name'] ?? ''); ?></div>
                                    <div style="font-size: 0.9rem; color: #b91c1c; margin-bottom: 0.2rem;">📞 <?php echo htmlspecialchars($row['captain_phone'] ?? ''); ?></div>
                                    <div style="font-size: 0.9rem; color: #b91c1c;">✉️ <?php echo htmlspecialchars($row['captain_email'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $playersArr = isset($row['players']) ? json_decode($row['players'], true) : null;
                                    if ($playersArr && is_array($playersArr)): ?>
                                        <ul class="player-list">
                                            <?php foreach ($playersArr as $i => $p): ?>
                                                <li style="color: #991b1b;">
                                                    <?php if ($i < 6): ?>
                                                        <span class="player-role-badge role-asil" style="background-color: #fee2e2; color: #991b1b; border-color: #fecaca;">Asil</span>
                                                    <?php else: ?>
                                                        <span class="player-role-badge role-yedek" style="background-color: #fffbeb; color: #92400e; border-color: #fef3c7;">Yedek</span>
                                                    <?php endif; ?>
                                                    <strong><?php echo htmlspecialchars($p); ?></strong>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span style="color:#b91c1c; font-style: italic;">Veri yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['submitted_lang'])): ?>
                                        <span class="badge" style="background: #fca5a5; color: #7f1d1d;"><?php echo strtoupper(htmlspecialchars($row['submitted_lang'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (isset($row['created_at'])): ?>
                                        <div class="date-text" style="color: #991b1b;">
                                            <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($row['created_at']))); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <button type="button" class="action-btn edit-btn" onclick='openEditModal(<?php echo json_encode($row); ?>)'>Düzenle</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bu takımı silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="action-btn delete-btn">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($incompleteTeams) === 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem; color: #991b1b;">Henüz bireysel veya eksik takım başvurusu bulunmamaktadır.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" class="login-btn" style="display: inline-flex; width: auto; padding: 0.8rem 1.5rem;" onclick="openAddModal()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Yeni Takım Ekle
                    </button>
                </div>
            </div>
            
            <!-- AYARLAR TAB -->
            <div id="settings" class="tab-content <?php echo ($settings_msg || $settings_err) ? 'active' : ''; ?>">
                <div class="settings-card">
                    <h3>Şifre Değiştir</h3>
                    
                    <?php if ($settings_msg): ?>
                        <div class="success-msg"><?php echo htmlspecialchars($settings_msg); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($settings_err): ?>
                        <div class="error-msg"><?php echo htmlspecialchars($settings_err); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label>Mevcut Şifreniz</label>
                            <div class="input-wrapper" style="margin-bottom: 0;">
                                <input type="password" name="current_pass" required>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Yeni Şifre</label>
                            <div class="input-wrapper" style="margin-bottom: 0;">
                                <input type="password" name="new_pass" required minlength="5">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Yeni Şifre (Tekrar)</label>
                            <div class="input-wrapper" style="margin-bottom: 0;">
                                <input type="password" name="new_pass_confirm" required minlength="5">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </div>
                        </div>
                        
                        <button type="submit" class="login-btn" style="margin-top: 2rem;">Şifreyi Güncelle</button>
                    </form>
                </div>
            </div>

            <!-- MODAL (EKLE / DÜZENLE) -->
            <div id="teamModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
                <div class="modal-content" style="background: white; border-radius: 16px; padding: 2rem; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
                        <h3 id="modalTitle" style="margin: 0; color: var(--isu-blue); font-size: 1.5rem;">Takım Düzenle</h3>
                        <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <input type="hidden" name="save_team" value="1">
                        <input type="hidden" name="team_id" id="modal_team_id" value="0">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Takım Adı</label>
                                <input type="text" name="team_name" id="modal_team_name" required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 8px;">
                            </div>
                            <div class="form-group">
                                <label>Kaptan Adı</label>
                                <input type="text" name="captain_name" id="modal_captain_name" required style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 8px;">
                            </div>
                            <div class="form-group">
                                <label>Kaptan Telefon</label>
                                <input type="text" name="captain_phone" id="modal_captain_phone" style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 8px;">
                            </div>
                            <div class="form-group">
                                <label>Kaptan E-posta</label>
                                <input type="email" name="captain_email" id="modal_captain_email" style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 8px;">
                            </div>
                            <div class="form-group">
                                <label>Kayıt Dili</label>
                                <select name="submitted_lang" id="modal_lang" style="width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 8px;">
                                    <option value="tr">TR</option>
                                    <option value="en">EN</option>
                                </select>
                            </div>
                        </div>
                        
                        <h4 style="margin-top: 1.5rem; margin-bottom: 0.2rem; color: #0369a1; font-size: 0.9rem;">Asil Kadro (İlk 6 Oyuncu)</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem; padding: 0.8rem; background: #f0f9ff; border-radius: 8px; border: 1px solid #e0f2fe;">
                            <?php for($i=1; $i<=6; $i++): ?>
                            <div>
                                <input type="text" name="player<?php echo $i; ?>" id="modal_player_<?php echo $i; ?>" placeholder="<?php echo $i; ?>. Oyuncu (Asil)" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <h4 style="margin-top: 0.5rem; margin-bottom: 0.2rem; color: #92400e; font-size: 0.9rem;">Yedek Oyuncular (Opsiyonel)</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; padding: 0.8rem; background: #fffbeb; border-radius: 8px; border: 1px solid #fef3c7;">
                            <?php for($i=7; $i<=12; $i++): ?>
                            <div>
                                <input type="text" name="player<?php echo $i; ?>" id="modal_player_<?php echo $i; ?>" placeholder="<?php echo $i; ?>. Oyuncu (Yedek)" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div style="margin-top: 2rem; text-align: right;">
                            <button type="button" onclick="closeModal()" style="padding: 0.8rem 1.5rem; background: #f1f5f9; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-right: 0.5rem; color: var(--text-main);">İptal</button>
                            <button type="submit" class="login-btn" style="display: inline-block; width: auto; padding: 0.8rem 1.5rem; margin: 0;">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

            <style>
                .action-btn {
                    padding: 0.4rem 0.8rem;
                    border: none;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    font-weight: bold;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                }
                .edit-btn { background: var(--isu-blue-light); color: white; }
                .edit-btn:hover { background: var(--isu-blue); }
                .delete-btn { background: #fee2e2; color: #dc2626; }
                .delete-btn:hover { background: #fca5a5; }
            </style>

            <script>
                function switchTab(tabId) {
                    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                    
                    event.currentTarget.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                }
                
                function openAddModal() {
                    document.getElementById('modalTitle').innerText = 'Yeni Takım Ekle';
                    document.getElementById('modal_team_id').value = '0';
                    document.getElementById('modal_team_name').value = '';
                    document.getElementById('modal_captain_name').value = '';
                    document.getElementById('modal_captain_phone').value = '';
                    document.getElementById('modal_captain_email').value = '';
                    document.getElementById('modal_lang').value = 'tr';
                    
                    for(let i=1; i<=12; i++) {
                        document.getElementById('modal_player_' + i).value = '';
                    }
                    
                    document.getElementById('teamModal').style.display = 'flex';
                }
                
                function openEditModal(teamData) {
                    document.getElementById('modalTitle').innerText = 'Takımı Düzenle';
                    document.getElementById('modal_team_id').value = teamData.id;
                    document.getElementById('modal_team_name').value = teamData.team_name || '';
                    document.getElementById('modal_captain_name').value = teamData.captain_name || '';
                    document.getElementById('modal_captain_phone').value = teamData.captain_phone || '';
                    document.getElementById('modal_captain_email').value = teamData.captain_email || '';
                    document.getElementById('modal_lang').value = teamData.submitted_lang || 'tr';
                    
                    // Clear previous players
                    for(let i=1; i<=12; i++) {
                        document.getElementById('modal_player_' + i).value = '';
                    }
                    
                    // Populate existing players
                    if (teamData.players) {
                        try {
                            const players = JSON.parse(teamData.players);
                            if (Array.isArray(players)) {
                                players.forEach((player, index) => {
                                    if (index < 12) {
                                        document.getElementById('modal_player_' + (index + 1)).value = player;
                                    }
                                });
                            }
                        } catch (e) {
                            console.error("Player data parse error", e);
                        }
                    }
                    
                    document.getElementById('teamModal').style.display = 'flex';
                }
                
                function closeModal() {
                    document.getElementById('teamModal').style.display = 'none';
                }
                
                <?php if ($settings_msg || $settings_err): ?>
                    // Eğer şifre değistirme işlemi yapıldıysa direkt Ayarlar sekmesini aç
                    document.querySelectorAll('.tab-btn')[1].classList.add('active');
                    document.querySelectorAll('.tab-btn')[0].classList.remove('active');
                    document.getElementById('records').classList.remove('active');
                <?php endif; ?>
            </script>
        </div>
    <?php endif; ?>

</body>
</html>
