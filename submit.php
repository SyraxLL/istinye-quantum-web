<?php
require_once __DIR__ . '/security.php';
startSecureSession();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="volleyball-transparent.png">
    <title>Başvuru Başarılı - İstinye Üniversitesi Voleybol Turnuvası</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="style.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>

<body>


    <div class="container">
        <div class="submit-lang-wrapper" style="position: absolute; top: 1.5rem; right: 1.5rem; z-index: 10;">
            <label class="lang-switch float-switcher" for="submit-lang-checkbox">
                <input type="checkbox" id="submit-lang-checkbox">
                <div class="slider"></div>
                <span class="lang-label tr-label">TR</span>
                <span class="lang-label en-label">EN</span>
            </label>
        </div>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. CSRF Verification
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("Güvenlik İhlali: CSRF Token geçersiz veya eksik!");
    }

    // 2. Rate Limiting (Spam Koruması: Dakikada en fazla 5 başvuru)
    if (!checkRateLimit('registration_rate', 5, 60)) {
        die("Hatalı İstek: Çok fazla başvuru denemesi yaptınız. Lütfen bir süre bekleyip tekrar deneyin.");
    }

    $app_type = sanitizeInput($_POST['app_type'] ?? 'full');
    $team_name = sanitizeInput($_POST['team_name'] ?? '');
    
    // If it's an incomplete/solo application and they didn't provide a team name, set a placeholder.
    if ($app_type === 'incomplete' && empty(trim($team_name))) {
        $team_name = 'Bireysel/Eksik Başvuru';
    }
    
    $captain_name = sanitizeInput($_POST['captain_name'] ?? '');
    $captain_email = filter_var($_POST['captain_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $captain_phone = sanitizeInput($_POST['captain_phone'] ?? '');
    $players = $_POST['players'] ?? [];
    $submitted_lang = sanitizeInput($_POST['submitted_lang'] ?? 'tr');

    $valid_players = [];
    if (is_array($players)) {
        foreach ($players as $p) {
            $p_clean = sanitizeInput(trim($p));
            if ($p_clean !== '') {
                $valid_players[] = $p_clean;
            }
        }
    }
    $player_count = count($valid_players);

    $db_error = false;
    $env_file = __DIR__ . '/.env';
    $env = loadEnv($env_file);
    if ($env && isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USER'])) {
        try {
            $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'] . ";charset=" . ($env['DB_CHARSET'] ?? 'utf8mb4');
            $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // We are storing 'app_type' in the 'team_name' dynamically or we can just append it for clarity in admin panel.
                // Since there is no app_type column, I will append the tag to the team name to make it visible in the admin panel.
                $final_team_name = $team_name;
                if ($app_type === 'incomplete') {
                    $final_team_name = $team_name === 'Bireysel/Eksik Başvuru' ? $team_name : $team_name . ' (Eksik Kadro)';
                }

                $stmt = $pdo->prepare("INSERT INTO registrations (team_name, captain_name, captain_phone, captain_email, players, submitted_lang) VALUES (?, ?, ?, ?, ?, ?)");
                $players_json = json_encode(array_values($valid_players), JSON_UNESCAPED_UNICODE);
                $stmt->execute([$final_team_name, $captain_name, $captain_phone, $captain_email, $players_json, $submitted_lang]);

                // 3. E-posta Bildirimi Gönder (Gelişmiş HTML Tasarımı)
                $to = "6002mustafaadsan@gmail.com";
                $subject = "🏐 Yeni Başvuru: " . $final_team_name;

                // Kadro Ayrımı (Mail için)
                $main_squad = array_slice($valid_players, 0, 6);
                $substitutes = array_slice($valid_players, 6);

                $main_squad_html = !empty($main_squad) ? implode(", ", $main_squad) : "-";
                $substitutes_html = !empty($substitutes) ? implode(", ", $substitutes) : "Yedek yok";

                $email_body = '
                <html>
                <head>
                    <style>
                        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f7f6; }
                        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e1e4e8; }
                        .header { background: linear-gradient(135deg, #004bb6 0%, #003380 100%); color: #ffffff; padding: 25px; text-align: center; }
                        .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; text-transform: uppercase; }
                        .content { padding: 30px; }
                        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                        .info-table th { text-align: left; padding: 12px; background: #f8fafc; border-bottom: 2px solid #edf2f7; color: #4a5568; font-size: 13px; text-transform: uppercase; width: 35%; }
                        .info-table td { padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748; font-weight: 500; }
                        .roster-section { background: #f0f7ff; border-radius: 8px; padding: 20px; border-left: 4px solid #004bb6; margin-bottom: 25px; }
                        .roster-title { color: #004bb6; font-weight: 700; margin-bottom: 10px; display: block; font-size: 15px; }
                        .label-sub { color: #d35400; font-weight: 700; margin-top: 15px; display: block; font-size: 14px; }
                        .footer { background: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #edf2f7; }
                        .btn { display: inline-block; padding: 10px 20px; background: #25D366; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; margin-top: 10px; box-shadow: 0 4px 10px rgba(37, 211, 102, 0.2); }
                        .admin-btn { background: #004bb6; margin-right: 10px; }
                        .badge { display: inline-block; padding: 4px 8px; background: #edf2f7; border-radius: 4px; font-size: 11px; color: #4a5568; margin-top: 5px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>🏐 YENİ TURNUVA KAYDI</h1>
                        </div>
                        <div class="content">
                            <p style="margin-top:0;">Merhaba Mustafa,</p>
                            <p>Sisteme yeni bir başvuru yapıldı. Detaylar aşağıdadır:</p>
                            
                            <table class="info-table">
                                <tr><th>Takım Adı</th><td>' . $final_team_name . '</td></tr>
                                <tr><th>Takım Kaptanı</th><td>' . $captain_name . '</td></tr>
                                <tr><th>E-posta</th><td>' . $captain_email . '</td></tr>
                                <tr><th>Telefon</th><td>' . $captain_phone . '</td></tr>
                                <tr><th>Oyuncu Sayısı</th><td>' . $player_count . ' Arasından ' . count($valid_players) . ' Kişi</td></tr>
                            </table>

                            <div class="roster-section">
                                <span class="roster-title">🛡️ Asil Kadro (İlk 6):</span>
                                <div style="color: #004085; font-weight: 600;">' . $main_squad_html . '</div>
                                
                                ' . (count($valid_players) > 6 ? '
                                <span class="label-sub">🔸 Yedekler:</span>
                                <div style="color: #e67e22; font-weight: 600;">' . $substitutes_html . '</div>' : '') . '
                            </div>

                            <p style="font-size: 13px; color: #718096;">Başvuru Dili: <strong>' . strtoupper($submitted_lang) . '</strong></p>
                        </div>
                        <div class="footer">
                            <a href="https://istinyequantum.com/admin.php" class="btn admin-btn">Yönetici Paneline Git</a>
                            <a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $captain_phone) . '" class="btn">Kaptana WhatsApp\'tan Yaz</a>
                        </div>
                    </div>
                </body>
                </html>';

                $headers = "From: adminpanel@istinyequantum.com\r\n";
                $headers .= "Reply-To: " . $captain_email . "\r\n";
                $headers .= "Content-Type: text/html; charset=utf-8\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                // Hata oluşsa bile kullanıcıya yansıtmamak için @ kullanıyoruz
                @mail($to, $subject, $email_body, $headers);
        }
        catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $db_error = true;
        }
    } else {
        error_log("Invalid or missing database credentials in .env file.");
        $db_error = true;
    }



    if (!$db_error) {
?>

                <div class="success-content">
                    <div class="success-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" style="position:relative; z-index:2;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>

                    <h1 class="success-title tr-content">Smaç Başarılı! (Kayıt Alındı)</h1>
                    <h1 class="success-title en-content">Spike Successful! (Registered)</h1>

                    <p class="success-text tr-content">
                        Sayın <strong><?php echo $captain_name; ?></strong>,
                        <strong class="highlight"><?php echo $team_name; ?></strong>
                        takımı adına yaptığınız turnuva başvurusu sistemimize kaydedilmiştir. Sahada görüşmek üzere!
                    <p class="success-text tr-content">
                        Sayın <strong><?php echo $captain_name; ?></strong>,
                        <strong class="highlight"><?php echo $team_name; ?></strong>
                        takımı adına yaptığınız turnuva başvurusu sistemimize kaydedilmiştir. Sahada görüşmek üzere!
                    </p>
                    <p class="success-text en-content">
                        Dear <strong><?php echo $captain_name; ?></strong>, your tournament application on behalf of the team
                        <strong class="highlight"><?php echo $team_name; ?></strong> has been recorded in our system. See you on
                        the court!
                    </p>

                    <div class="summary-card">
                        <div class="summary-item">
                            <span class="summary-label tr-content">Takım Kaptanı</span>
                            <span class="summary-label en-content">Team Captain</span>
                            <span class="summary-value"><?php echo $captain_name; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label tr-content">İletişim Numarası</span>
                            <span class="summary-label en-content">Contact Number</span>
                            <span class="summary-value"><?php echo $captain_phone; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label tr-content">Öğrenci E-posta</span>
                            <span class="summary-label en-content">Student Email</span>
                            <span class="summary-value"><?php echo $captain_email; ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label tr-content">Kadrodaki Oyuncu Sayısı</span>
                            <span class="summary-label en-content">Roster Size</span>
                            <span class="summary-value highlight">
                                <?php echo $player_count; ?>
                                <span class="tr-content" style="display:inline;">Kişi</span>
                                <span class="en-content" style="display:inline;">Players</span>
                            </span>
                        </div>
                        <div class="summary-item" style="flex-direction: column; align-items: flex-start; gap: 0.8rem; border-bottom: none; background: rgba(255,255,255,0.5); padding: 1rem; border-radius: 8px;">
                            <span class="summary-label tr-content" style="margin-bottom: 0;">Kadro Detayları</span>
                            <span class="summary-label en-content" style="margin-bottom: 0;">Roster Details</span>
                            
                            <div style="width: 100%;">
                                <strong style="font-size: 0.8rem; color: #0056b3;" class="tr-content">Asil Kadro (İlk 6):</strong>
                                <strong style="font-size: 0.8rem; color: #0056b3;" class="en-content">Starting Lineup (First 6):</strong>
                                <div style="font-size: 0.95rem; color: #004085; font-weight: 800; margin-bottom: 0.5rem;">
                                    <?php echo implode(", ", array_slice($valid_players, 0, 6)); ?>
                                </div>
                                
                                <?php if (count($valid_players) > 6): ?>
                                    <strong style="font-size: 0.8rem; color: #d35400;" class="tr-content">Yedekler:</strong>
                                    <strong style="font-size: 0.8rem; color: #d35400;" class="en-content">Substitutes:</strong>
                                    <div style="font-size: 0.9rem; color: #e67e22; font-weight: 600;">
                                        <?php echo implode(", ", array_slice($valid_players, 6)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="support-box" style="background: rgba(37, 211, 102, 0.1); border: 2px dashed #25D366; border-radius: 12px; padding: 1.25rem; margin: 1.5rem 0; text-align: center;">
                        <p style="margin: 0; color: #075E54; font-weight: 700; font-size: 0.95rem;" class="tr-content">
                            Destek veya Hatalı Başvuru Bildirimi
                        </p>
                        <p style="margin: 0; color: #075E54; font-weight: 700; font-size: 0.95rem;" class="en-content">
                            Support or Report Mistake
                        </p>
                        <a href="https://wa.me/905322634401" target="_blank" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #25D366; color: white; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; margin-top: 0.75rem; font-weight: 800; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3); transition: transform 0.2s;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-2.202 0-3.991 1.789-3.991 3.991 0 .616.141 1.2.391 1.724l-.45 1.644 1.688-.443c.504.225 1.063.353 1.653.353 2.202 0 3.991-1.789 3.991-3.991 0-2.202-1.789-3.991-3.991-3.991zm2.345 5.613c-.131.371-.659.593-1.03.522-.37-.07-.723-.27-1.15-.558-.426-.288-.857-.665-1.127-1.036-.27-.371-.439-.8-.439-1.21 0-.411.17-.834.502-1.11.331-.276.711-.314.937-.314.226 0 .43.003.518.006.088.003.174.008.261.077.087.069.305.741.332.796.026.055.044.119.001.205-.043.086-.108.181-.191.277-.084.096-.174.201-.074.372.1.171.442.723.948 1.171.506.448 1.05.623 1.221.723.171.1.27.084.37-.015.1-.1.439-.516.554-.693.116-.176.23-.146.388-.087.158.059 1.01.476 1.184.563.175.087.291.13.334.205.043.076.043.438-.088.809zM12 2C6.477 2 2 6.477 2 12c0 1.892.524 3.662 1.435 5.176L2 22l4.958-1.413A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                            WhatsApp Destek
                        </a>
                    </div>


                    <a href="index.php" class="back-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <span class="tr-content">Sahaya Dön (Ana Sayfa)</span>
                        <span class="en-content">Return to Court (Home Page)</span>
                    </a>
                </div>

                <?php
    }
    else {
        echo '<div class="success-content">
                        <div class="success-icon" style="background-color: var(--text-main);">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:relative; z-index:2;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                        <h1 class="success-title tr-content" style="color: var(--text-main);">Sistem Hatası!</h1>
                        <h1 class="success-title en-content" style="color: var(--text-main);">System Error!</h1>
                        
                        <p class="success-text tr-content">Kaydınızı alırken veritabanında bir sorun oluştu. Lütfen daha sonra tekrar deneyin.</p>
                        <p class="success-text en-content">An error occurred while saving your registration to the database. Please try again later.</p>
                        
                        <a href="index.php" class="back-link" style="background-color: var(--text-main);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                            <span class="tr-content">Geri Dön</span>
                            <span class="en-content">Go Back</span>
                        </a>
                      </div>';
    }
}
else {
    echo '<div class="success-content">
                    <div class="success-icon" style="background-color: var(--text-main);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:relative; z-index:2;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <h1 class="success-title tr-content" style="color: var(--text-main);">Hatalı Pas!</h1>
                    <h1 class="success-title en-content" style="color: var(--text-main);">Out of Bounds!</h1>
                    
                    <p class="success-text tr-content">Lütfen başvuru formunu doldurarak tekrar deneyin.</p>
                    <p class="success-text en-content">Please fill out the application form and try again.</p>
                    
                    <a href="index.php" class="back-link" style="background-color: var(--text-main);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        <span class="tr-content">Forma Dön</span>
                        <span class="en-content">Return to Form</span>
                    </a>
                  </div>';
}
?>
    </div>

    <a href="https://www.istinye.edu.tr/tr/sks" target="_blank" rel="noopener noreferrer" class="sks-logo-link">
        <img src="isu_sks_logo.png" alt="İSÜ SKS Logo" class="sks-logo">
    </a>

    <script src="script.js"></script>

</body>

</html>