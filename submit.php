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

                // 3. E-posta Bildirimi Gönder
                $to = "6002mustafaadsan@gmail.com";
                $subject = "Yeni Turnuva Başvurusu: " . $final_team_name;
                $email_body = "Sisteme yeni bir turnuva başvurusu düştü.\n\n";
                $email_body .= "Takım Adı: " . $final_team_name . "\n";
                $email_body .= "Kaptan: " . $captain_name . "\n";
                $email_body .= "E-posta: " . $captain_email . "\n";
                $email_body .= "Telefon: " . $captain_phone . "\n";
                $email_body .= "Oyuncu Sayısı: " . $player_count . "\n";
                $email_body .= "Başvuru Dili: " . strtoupper($submitted_lang) . "\n";
                $email_body .= "\nDetaylar için yönetici paneline giriş yapabilirsiniz.";

                $headers = "From: adminpanel@istinyequantum.com\r\n";
                $headers .= "Reply-To: " . $captain_email . "\r\n";
                $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
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