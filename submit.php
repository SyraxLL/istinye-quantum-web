<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Başvuru Başarılı - İstinye Üniversitesi Voleybol Turnuvası</title>
    <!-- SEO Best Practices -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- User Provided Volleyball Image -->
    <div class="bg-volleyball">
        <img src="volleyball.png" alt="İSÜ Voleybol Topu" class="user-ball">
    </div>

    <!-- Dynamic CSS Particles and Speed Stripes -->
    <div class="action-stripes"></div>
    <div class="particles"></div>

    <div class="court-line court-line-left"></div>
    <div class="court-line court-line-right"></div>

    <div class="container">
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $team_name = htmlspecialchars($_POST['team_name'] ?? '');
            $captain_name = htmlspecialchars($_POST['captain_name'] ?? '');
            $captain_email = htmlspecialchars($_POST['captain_email'] ?? '');
            $captain_phone = htmlspecialchars($_POST['captain_phone'] ?? '');
            $players = $_POST['players'] ?? [];

            $valid_players = array_filter($players, function ($p) {
                return trim($p) !== ''; });
            $player_count = count($valid_players);
            ?>

            <div class="success-content">
                <div class="success-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" style="position:relative; z-index:2;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h1 class="success-title">Smaç Başarılı! (Kayıt Alındı)</h1>
                <p class="success-text">Sayın <strong><?php echo $captain_name; ?></strong>, <strong
                        class="highlight"><?php echo $team_name; ?></strong> takımı adına yaptığınız turnuva başvurusu
                    sistemimize kaydedilmiştir. Sahada görüşmek üzere!</p>

                <div class="summary-card">
                    <div class="summary-item">
                        <span class="summary-label">Takım Kaptanı</span>
                        <span class="summary-value"><?php echo $captain_name; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">İletişim Numarası</span>
                        <span class="summary-value"><?php echo $captain_phone; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Kurumsal E-posta</span>
                        <span class="summary-value"><?php echo $captain_email; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Kadrodaki Oyuncu Sayısı</span>
                        <span class="summary-value highlight"><?php echo $player_count; ?> Kişi</span>
                    </div>
                </div>

                <a href="index.php" class="back-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Sahaya Dön (Ana Sayfa)
                </a>
            </div>

            <?php
        } else {
            // Not a POST request
            echo '<div class="success-content">
                    <div class="success-icon" style="background-color: var(--text-main);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:relative; z-index:2;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <h1 class="success-title" style="color: var(--text-main);">Hatalı Pas!</h1>
                    <p class="success-text">Lütfen başvuru formunu doldurarak tekrar deneyin.</p>
                    <a href="index.php" class="back-link" style="background-color: var(--text-main);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Forma Dön
                    </a>
                  </div>';
        }
        ?>
    </div>
</body>

</html>