<?php
require_once __DIR__ . '/security.php';
startSecureSession();

// Veritabanı bağlantısı ve mod kontrolü
$env = loadEnv(__DIR__ . '/.env');
$site_mode = 'registration';
$matches = [];

if ($env && isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USER'])) {
    try {
        $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'] . ";charset=" . ($env['DB_CHARSET'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Modu çek
        $stmt = $pdo->query("SELECT meta_value FROM settings WHERE meta_key = 'site_mode'");
        $mode_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($mode_row) $site_mode = $mode_row['meta_value'];

        // Maçları çek
        if ($site_mode === 'bracket') {
            $stmt = $pdo->query("SELECT * FROM matches ORDER BY round ASC, match_order ASC");
            $matches_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Maçları raundlara göre grupla
            foreach ($matches_raw as $m) {
                $matches[$m['round']][] = $m;
            }
        }
    } catch (PDOException $e) {
        // Hata durumunda varsayılan registration kalsın
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="volleyball-transparent.png">
    <title class="tr-content">İstinye Üniversitesi Voleybol Turnuvası Başvuru Formu</title>
    <title class="en-content" style="display:none;">Istinye University Volleyball Tournament Registration</title>
    <meta name="description"
        content="İstinye Üniversitesi voleybol turnuvası başvuru sayfası. Takımını kur, formunu doldur ve şampiyonluk mücadelesine katıl!">
    <link rel="stylesheet" href="style.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>

<body class="<?php echo ($site_mode === 'bracket') ? 'esports-neon-bracket' : ''; ?>">


    <?php if ($site_mode === 'bracket'): ?>
        <!-- ── TAM SAYFA YENİ TURNUVA AĞACI (DOUBLE SIDED) ── -->
        <div class="bracket-page-container">
            
            <header class="bracket-header">
                <div class="header-title-row" style="justify-content: center; gap: 2rem;">
                    <div class="club-logo-header">
                        <img src="beyaz-boş.png" alt="Kulüp Logosu" style="height: 120px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
                    </div>
                    <div>
                        <h1 class="tr-content title-dynamic text-shadow">
                            <span class="text-volley" style="color:var(--isu-blue);">VOLEYBOL</span>
                            <span class="text-tourney" style="color:var(--isu-red);">TURNUVASI <span class="volley-icon">🏐</span></span>
                        </h1>
                        <h1 class="en-content title-dynamic text-shadow" style="display:none;">
                            <span class="text-volley" style="color:var(--isu-blue);">VOLLEYBALL</span>
                            <span class="text-tourney" style="color:var(--isu-red);">TOURNAMENT <span class="volley-icon">🏐</span></span>
                        </h1>
                    </div>
                </div>


            </header>

            <div class="bracket-content-wrapper masonry-blur-bg">
                <div class="status-banner centered-banner">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span class="tr-content">Turnuva başladı! Güncel eşleşmeleri aşağıdan takip edebilirsiniz.</span>
                    <span class="en-content" style="display:none;">Tournament is live! Follow the latest matchups below.</span>
                </div>

                <?php 
                $matchesById = [];
                for ($r = 1; $r <= 4; $r++) {
                    if (isset($matches[$r])) {
                        foreach ($matches[$r] as $m) {
                            $matchesById[$m['id']] = $m;
                        }
                    }
                }

                // Ekrana Maç Kutusunu Basan Helper Fonksiyon (sadece template içerisinde kullanım için)
                function renderMatchup($matchId, $matchesById) {
                    if (!isset($matchesById[$matchId])) return;
                    $m = $matchesById[$matchId];
                    $t1w = (!empty($m['winner_name']) && $m['winner_name'] == $m['team1_name']) ? 'winner' : '';
                    $t2w = (!empty($m['winner_name']) && $m['winner_name'] == $m['team2_name']) ? 'winner' : '';

                    // Takım belirlenmemişse 'Belirlenmedi' göster
                    $team1Display = !empty($m['team1_name']) ? htmlspecialchars($m['team1_name']) : 'Belirlenmedi';
                    $team2Display = !empty($m['team2_name']) ? htmlspecialchars($m['team2_name']) : 'Belirlenmedi';
                    $team1Empty   = empty($m['team1_name']);
                    $team2Empty   = empty($m['team2_name']);

                    // Maç durumu tespiti
                    $isCompleted = !empty($m['winner_name']);
                    $isUpcoming  = $team1Empty || $team2Empty;
                    $cardClass   = 'bracket-matchup';
                    if ($isUpcoming)  $cardClass .= ' upcoming-match';
                    if ($isCompleted) $cardClass .= ' completed-match';
                    ?>
                    <div class="<?php echo $cardClass; ?>">

                        <?php if (!empty($m['match_time'])): ?>
                            <div class="match-time-badge">
                                <span class="clock-icon">🕒</span>
                                <?php echo htmlspecialchars($m['match_time']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="bracket-team top-team <?php echo $t1w; ?> <?php echo $team1Empty ? 'empty-slot' : ''; ?>">
                            <span class="team-name" title="<?php echo $team1Display; ?>">
                                <?php echo $team1Display; ?>
                            </span>
                            <?php if (!$team1Empty): ?>
                                <span class="team-score"><?php echo $m['score1']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="bracket-team bottom-team <?php echo $t2w; ?> <?php echo $team2Empty ? 'empty-slot' : ''; ?>">
                            <span class="team-name" title="<?php echo $team2Display; ?>">
                                <?php echo $team2Display; ?>
                            </span>
                            <?php if (!$team2Empty): ?>
                                <span class="team-score"><?php echo $m['score2']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <div class="tournament-bracket-double">
                    <!-- SOL KANAT -->
                    <div class="bracket-side left-side">
                        <!-- Son 16 (Sol) -->
                        <div class="bracket-round">
                            <div class="round-title"><span class="tr-content">Son 16</span><span class="en-content" style="display:none;">Round of 16</span></div>
                            <?php foreach([1,2,3,4] as $mid) renderMatchup($mid, $matchesById); ?>
                        </div>
                        <!-- Çeyrek Final (Sol) -->
                        <div class="bracket-round">
                            <div class="round-title"><span class="tr-content">Çeyrek Final</span><span class="en-content" style="display:none;">Quarter Final</span></div>
                            <?php foreach([9,10] as $mid) renderMatchup($mid, $matchesById); ?>
                        </div>
                        <!-- Yarı Final (Sol) -->
                        <div class="bracket-round">
                            <div class="round-title"><span class="tr-content">Yarı Final</span><span class="en-content" style="display:none;">Semi Final</span></div>
                            <?php foreach([13] as $mid) renderMatchup($mid, $matchesById); ?>
                        </div>
                    </div>

                    <!-- FİNAL ORTA MERKEZ -->
                    <div class="bracket-center">
                        <div class="bracket-round final-round">
                            <div class="round-title final-title">
                                <span class="tr-content">🏆 FİNAL 🏆</span><span class="en-content" style="display:none;">🏆 FINAL 🏆</span>
                            </div>
                            <?php renderMatchup(15, $matchesById); ?>
                            
                            <!-- Şampiyon Vurgusu -->
                            <?php 
                            if(isset($matchesById[15]) && !empty($matchesById[15]['winner_name'])) {
                                echo '<div class="champion-box glass-effect">';
                                echo '<div class="trophy-icon">🏆</div>';
                                echo '<h3>Şampiyon</h3>';
                                echo '<div class="champion-name">'.htmlspecialchars($matchesById[15]['winner_name']).'</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- SAĞ KANAT -->
                    <div class="bracket-side right-side">
                        <!-- Yarı Final (Sağ) - Reverse Flow -->
                        <div class="bracket-round">
                            <div class="round-title"><span class="tr-content">Yarı Final</span><span class="en-content" style="display:none;">Semi Final</span></div>
                            <?php foreach([14] as $mid) renderMatchup($mid, $matchesById); ?>
                        </div>
                        <!-- Çeyrek Final (Sağ) -->
                        <div class="bracket-round">
                            <div class="round-title"><span class="tr-content">Çeyrek Final</span><span class="en-content" style="display:none;">Quarter Final</span></div>
                            <?php foreach([11,12] as $mid) renderMatchup($mid, $matchesById); ?>
                        </div>
                        <!-- Son 16 (Sağ) -->
                        <div class="bracket-round">
                            <div class="round-title"><span class="tr-content">Son 16</span><span class="en-content" style="display:none;">Round of 16</span></div>
                            <?php foreach([5,6,7,8] as $mid) renderMatchup($mid, $matchesById); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; padding: 2rem;">
                <p class="tr-content" style="font-size: 1rem; font-weight: 600; color: rgba(255,255,255,0.85); text-shadow: 0 1px 4px rgba(0,0,0,0.5);">Turnuva boyunca tüm maçlar İstinye Üniversitesi kampüsünde oynanacaktır.</p>
                <p class="en-content" style="display:none; font-size: 1rem; font-weight: 600; color: rgba(255,255,255,0.85); text-shadow: 0 1px 4px rgba(0,0,0,0.5);">All matches will be played at Istinye University campus throughout the tournament.</p>
            </div>
        </div>

        <!-- Orijinal JS'nin çalışması için gizli dummy checkbox -->
        <input type="checkbox" id="lang-checkbox_hidden" style="display:none;">
        <script>
            // Ensure the hidden checkbox propagates correctly if it was created
            document.getElementById('lang-checkbox_hidden').addEventListener('change', function(e) {
                // If there's original script logic attached, it triggers. 
                // Wait, original logic targets 'lang-checkbox', so let's keep that ID for it.
            });
        </script>
        <!-- Need a dummy checkbox with real ID for original event listeners if they strictly search for it by ID -->
        <input type="checkbox" id="lang-checkbox" style="display:none;" onchange="if(typeof setLanguage === 'function'){ setLanguage(this.checked ? 'en' : 'tr'); document.getElementById('lang-checkbox-bracket').checked = this.checked; }">

    <?php else: ?>
        <!-- ── KAYIT FORMU GÖRÜNÜMÜ ── -->
        <div class="container">
            <header>
                <div class="header-title-row">
                    <div class="club-logo-header">
                        <img src="beyaz-boş.png" alt="Kulüp Logosu">
                    </div>
                    <h1 class="tr-content title-dynamic">
                        <span class="text-volley">VOLEYBOL</span>
                        <span class="text-tourney">TURNUVASI <span class="volley-icon">🏐</span></span>
                    </h1>
                    <h1 class="en-content title-dynamic" style="display:none;">
                        <span class="text-volley">VOLLEYBALL</span>
                        <span class="text-tourney">TOURNAMENT <span class="volley-icon">🏐</span></span>
                    </h1>
                </div>

                <p class="tr-content desc-dynamic">
                    Sahaya çıkmaya hazır mısın? <br>
                    <strong class="highlight-action">Takımını kur</strong>, yeteneğini göster ve <strong class="highlight-action">kupayı evine götür!</strong>
                </p>
                <p class="en-content desc-dynamic" style="display:none;">
                    Ready to hit the court? <br>
                    <strong class="highlight-action">Build your team</strong>, show your skills, and <strong class="highlight-action">take the trophy home!</strong>
                </p>

                <div class="lang-toggle-container">
                    <label class="lang-switch" for="lang-checkbox">
                        <input type="checkbox" id="lang-checkbox">
                        <div class="slider"></div>
                        <span class="lang-label tr-label">TR</span>
                        <span class="lang-label en-label">EN</span>
                    </label>
                </div>
            </header>

            <!-- GİZLİ Bracket check hatası almamak için form etiketi aynen aşağıdaki gibi açılır -->
            <form action="submit.php" method="POST" class="registration-form tr-content" id="tournamentFormTR">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
            <input type="hidden" name="submitted_lang" value="tr">

            <div class="form-section" data-step="01">
                <h2>Başvuru Tipi & Takım Bilgileri</h2>
                
                <div class="input-group" style="margin-bottom: 1.5rem;">
                    <label for="app_type_tr">Başvuru Türü</label>
                    <select id="app_type_tr" name="app_type" onchange="toggleAppType('tr')" required style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1.05rem; background: #f8fafc; color: var(--text-main); appearance: none; cursor: pointer; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23004bb6%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right .7em top 50%; background-size: .65em auto;">
                        <option value="full">Tam Takım Başvurusu (7-12 Kişi)</option>
                        <option value="incomplete">Eksik Takım / Bireysel Başvuru</option>
                    </select>
                </div>
                
                <div id="incomplete_warning_tr" style="display: none; background-color: #fef2f2; border-left: 4px solid var(--isu-red); padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; color: #991b1b; font-size: 0.95rem; font-weight: 500;">
                    <strong style="color: var(--isu-red);">Bilgilendirme:</strong> Takımınızı tam oluşturmak ve uygun oyuncularla eşleştirmek için ekibimiz sizinle iletişime geçecektir. <em>(Lütfen iletişim bilgilerinizi eksik veya hatalı girmeyiniz.)</em>
                </div>
                
                <div class="input-group">
                    <label for="team_name_tr">Takım Adı <span id="team_name_hint_tr" style="font-size:0.85rem; color:var(--text-muted); font-weight:normal;">(Varsa)</span></label>
                    <input type="text" id="team_name_tr" name="team_name" placeholder="Örn: İSÜ Smaçörleri">
                </div>
            </div>

            <div class="form-section" data-step="02">
                <h2>Kaptan Bilgileri</h2>
                <div class="form-row">
                    <div class="input-group">
                        <label for="captain_name_tr">Kaptan Adı Soyadı</label>
                        <input type="text" id="captain_name_tr" name="captain_name" required
                            placeholder="Adınız Soyadınız">
                    </div>
                    <div class="input-group">
                        <label for="captain_phone_tr">Telefon Numarası</label>
                        <input type="tel" id="captain_phone_tr" name="captain_phone" required
                            placeholder="0555 555 55 55">
                    </div>
                </div>
                <div class="input-group">
                    <label for="captain_email_tr">E-posta Adresi <span class="label-hint">(istinye.edu.tr)</span></label>
                    <input type="email" id="captain_email_tr" name="captain_email" required
                        placeholder="kaptan@istinye.edu.tr">
                </div>
            </div>

            <div class="form-section" data-step="03">
                <div class="section-header">
                    <h2>Oyuncu Kadrosu</h2>
                </div>
                <p class="section-desc">Sahaya çıkacak 6 asil oyuncu ve yedek kadronuzu belirleyin (Toplam maks. 12
                    kişi).</p>

                <div id="players-container-tr">
                    <div class="player-input-group">
                        <span class="player-number">1</span>
                        <input type="text" id="player_tr_1" name="players[]" placeholder="1. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">2</span>
                        <input type="text" id="player_tr_2" name="players[]" placeholder="2. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">3</span>
                        <input type="text" id="player_tr_3" name="players[]" placeholder="3. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">4</span>
                        <input type="text" id="player_tr_4" name="players[]" placeholder="4. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">5</span>
                        <input type="text" id="player_tr_5" name="players[]" placeholder="5. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">6</span>
                        <input type="text" id="player_tr_6" name="players[]" placeholder="6. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">7</span>
                        <input type="text" id="player_tr_7" name="players[]" placeholder="7. Oyuncu Adı Soyadı (Yedek)"
                            required>
                    </div>
                </div>

                <button type="button" id="add-player-btn-tr" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>Yedek Oyuncu Ekle</span>
                </button>

                <div class="inline-support-tr" style="margin-top: 1.5rem; padding: 1rem; background: #f0fdf4; border-radius: 12px; border: 1px solid #bef264; display: flex; align-items: center; justify-content: center; gap: 0.8rem;">
                    <span style="font-size: 0.85rem; color: #166534; font-weight: 500;">Başvuruyla ilgili bir sorun mu var?</span>
                    <a href="https://wa.me/905322634401" target="_blank" style="background: #25D366; color: white; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 0.4rem; box-shadow: 0 4px 10px rgba(37, 211, 102, 0.2);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-2.202 0-3.991 1.789-3.991 3.991 0 .616.141 1.2.391 1.724l-.45 1.644 1.688-.443c.504.225 1.063.353 1.653.353 2.202 0 3.991-1.789 3.991-3.991 0-2.202-1.789-3.991-3.991-3.991zm2.345 5.613c-.131.371-.659.593-1.03.522-.37-.07-.723-.27-1.15-.558-.426-.288-.857-.665-1.127-1.036-.27-.371-.439-.8-.439-1.21 0-.411.17-.834.502-1.11.331-.276.711-.314.937-.314.226 0 .43.003.518.006.088.003.174.008.261.077.087.069.305.741.332.796.026.055.044.119.001.205-.043.086-.108.181-.191.277-.084.096-.174.201-.074.372.1.171.442.723.948 1.171.506.448 1.05.623 1.221.723.171.1.27.084.37-.015.1-.1.439-.516.554-.693.116-.176.23-.146.388-.087.158.059 1.01.476 1.184.563.175.087.291.13.334.205.043.076.043.438-.088.809zM12 2C6.477 2 2 6.477 2 12c0 1.892.524 3.662 1.435 5.176L2 22l4.958-1.413A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                        WhatsApp Destek
                    </a>
                </div>
            </div>

            <div class="rules-section">
                <div class="rules-title">📋 Turnuva Kuralları</div>
                <div class="rules-content">
                    <h4>Takım Yapısı ve Başvuru Esasları</h4>
                    <ul>
                        <li><strong>Katılım Şartı:</strong> Turnuvaya sadece İstinye Üniversitesi öğrencileri katılabilir.</li>
                        <li><strong>Başvuru:</strong> Takımınız varsa bir Takım İsmi ile, yoksa "Bireysel" veya "Eksik Takım" olarak başvuruda bulunabilirsiniz.</li>
                        <li><strong>Kadro:</strong> Tam takımlar en az 7, en fazla 12 oyuncudan oluşmalıdır. Bireysel başvurular, ekibimiz tarafından uygun takımlara yerleştirilecektir.</li>
                        <li><strong>Cinsiyet:</strong> Her takımın kadrosunda en az 2 kadın oyuncu bulunması zorunludur; sahada her an en az 2 kadın oyuncu aktif oynamak zorundadır. Kadın oyuncu yerine erkek oyuncu girerek sayının 1'e düşmesi kural ihlalidir.</li>
                        <li class="whatsapp-warning"><strong>⚠️ TÜM DUYURULAR WHATSAPP KANALDA YAPILACAĞINDAN DOLAYI KANALA KATILMAK ÇOK ÖNEMLİDİR.</strong></li>
                        <li class="whatsapp-link-item"><a href="https://chat.whatsapp.com/F1qZB7Tya5cKsrYvxG93gc" target="_blank" rel="noopener noreferrer" class="whatsapp-btn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> WhatsApp Kanalına Katıl</a></li>
                    </ul>
                    <h4>Maç Formatı ve Sayı Sistemi</h4>
                    <ul>
                        <li><strong>Set Sistemi:</strong> Maçlar "3 set üzerinden 2 set alan kazanır" formatında oynanır.</li>
                        <li><strong>Sayılar:</strong> İlk iki set 25 sayı, karar seti (3. set) 15 sayı üzerinden oynanır.</li>
                        <li><strong>Uzatma:</strong> Her iki durumda da setin bitmesi için en az 2 fark zorunludur.</li>
                        <li><strong>Saha Değişimi:</strong> Her set sonunda saha değişimi yapılır.</li>
                    </ul>
                    <h4>Oyuncu Değişikliği ve Diskalifiye</h4>
                    <ul>
                        <li><strong>Değişiklik Hakkı:</strong> Bir takım her sette en fazla 4 kez oyuncu değişikliği yapabilir.</li>
                        <li><strong>Eksik Kalma:</strong> Diskalifiye sonrası yedek kalmazsa o set hükmen kaybedilir.</li>
                        <li><strong>Servis:</strong> Hakem düdüğünden sonra 8 saniye içinde kullanılmalıdır; çizgi ihlali hatadır.</li>
                        <li><strong>Temas:</strong> Topa vücudun her yeriyle vurulabilir; "tutma" veya "taşıma" ihlalleri faul sayılır.</li>
                        <li><strong>İhlaller:</strong> File teması ve orta çizgiyi tam ayakla ihlal etmek yasaktır.</li>
                    </ul>
                    <h4>Disiplin ve Genel Kurallar</h4>
                    <ul>
                        <li><strong>Hakem Yetkisi:</strong> Maç esnasında hakemin kararları esastır ve tartışılamaz.</li>
                        <li><strong>Disiplin:</strong> Tartışma veya sportmenlik dışı durumlarda "Turnuva Kurulu" kararıyla takım diskalifire edilebilir. İleri derecedeki ihlaller üniversite disiplin kuruluna sevk edilecektir.</li>
                        <li><strong>Kıyafet (Dresscode):</strong> Turnuva genelinde uygulanacak kıyafet düzeni turnuva başlangıcından birkaç gün önce duyurulacaktır. Tüm takımların yapılacak duyuruya göre hazırlıklı gelmesi zorunludur.</li>
                        <li><strong>Gecikme:</strong> Maç saatinde sahada hazır bulunmayan takım, 15 dakikalık bekleme süresinin ardından hükmen mağlup sayılır.</li>
                    </ul>
                    <h4>Puanlama Sistemi (Grup Aşaması)</h4>
                    <ul>
                        <li><strong>2-0 Galibiyet:</strong> 3 Puan</li>
                        <li><strong>2-1 Galibiyet:</strong> 2 Puan</li>
                        <li><strong>1-2 Mağlubiyet:</strong> 1 Puan</li>
                        <li><strong>0-2 Mağlubiyet:</strong> 0 Puan</li>
                    </ul>
                </div>
                <label class="rules-consent" for="consent-tr">
                    <input type="checkbox" id="consent-tr" name="consent" required>
                    <span>Tüm turnuva kurallarını okudum ve kabul ediyorum.</span>
                </label>
            </div>

            <button type="submit" class="btn-primary" id="submitBtnTR">
                <span>GÖNDER</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>

        </form>

        <form action="submit.php" method="POST" class="registration-form en-content" id="tournamentFormEN"
            style="display: none;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
            <input type="hidden" name="submitted_lang" value="en">

            <div class="form-section" data-step="01">
                <h2>Application Type & Team Details</h2>
                
                <div class="input-group" style="margin-bottom: 1.5rem;">
                    <label for="app_type_en">Application Type</label>
                    <select id="app_type_en" name="app_type" onchange="toggleAppType('en')" required style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1.05rem; background: #f8fafc; color: var(--text-main); appearance: none; cursor: pointer; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23004bb6%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right .7em top 50%; background-size: .65em auto;">
                        <option value="full">Full Team Application (7-12 Players)</option>
                        <option value="incomplete">Incomplete Team / Solo Application</option>
                    </select>
                </div>

                <div id="incomplete_warning_en" style="display: none; background-color: #fef2f2; border-left: 4px solid var(--isu-red); padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; color: #991b1b; font-size: 0.95rem; font-weight: 500;">
                    <strong style="color: var(--isu-red);">Information:</strong> Our team will contact you to pair you with available players and complete your team. <em>(Please ensure your contact information is complete and accurate.)</em>
                </div>

                <div class="input-group">
                    <label for="team_name_en">Team Name <span id="team_name_hint_en" style="font-size:0.85rem; color:var(--text-muted); font-weight:normal;">(If any)</span></label>
                    <input type="text" id="team_name_en" name="team_name" placeholder="E.g. ISU Spikers">
                </div>
            </div>

            <div class="form-section" data-step="02">
                <h2>Captain's Details</h2>
                <div class="form-row">
                    <div class="input-group">
                        <label for="captain_name_en">Captain's Full Name</label>
                        <input type="text" id="captain_name_en" name="captain_name" required
                            placeholder="Your Full Name">
                    </div>
                    <div class="input-group">
                        <label for="captain_phone_en">Phone Number</label>
                        <input type="tel" id="captain_phone_en" name="captain_phone" required
                            placeholder="+90 555 555 55 55">
                    </div>
                </div>
                <div class="input-group">
                    <label for="captain_email_en">Email Address <span class="label-hint">(istinye.edu.tr)</span></label>
                    <input type="email" id="captain_email_en" name="captain_email" required
                        placeholder="captain@istinye.edu.tr">
                </div>
            </div>

            <div class="form-section" data-step="03">
                <div class="section-header">
                    <h2>Team Roster</h2>
                </div>
                <p class="section-desc">List your 6 starting players and substitutes (Max. 12 players total).</p>

                <div id="players-container-en">
                    <div class="player-input-group">
                        <span class="player-number">1</span>
                        <input type="text" id="player_en_1" name="players[]" placeholder="1. Player Name (Starter)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">2</span>
                        <input type="text" id="player_en_2" name="players[]" placeholder="2. Player Name (Starter)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">3</span>
                        <input type="text" id="player_en_3" name="players[]" placeholder="3. Player Name (Starter)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">4</span>
                        <input type="text" id="player_en_4" name="players[]" placeholder="4. Player Name (Starter)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">5</span>
                        <input type="text" id="player_en_5" name="players[]" placeholder="5. Player Name (Starter)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">6</span>
                        <input type="text" id="player_en_6" name="players[]" placeholder="6. Player Name (Starter)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">7</span>
                        <input type="text" id="player_en_7" name="players[]" placeholder="7. Player Name (Substitute)"
                            required>
                    </div>
                </div>

                <button type="button" id="add-player-btn-en" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>Add Substitute</span>
                </button>

                <div class="inline-support-en" style="margin-top: 1.5rem; padding: 1rem; background: #f0fdf4; border-radius: 12px; border: 1px solid #bef264; display: flex; align-items: center; justify-content: center; gap: 0.8rem;">
                    <span style="font-size: 0.85rem; color: #166534; font-weight: 500;">Having issues with the application?</span>
                    <a href="https://wa.me/905322634401" target="_blank" style="background: #25D366; color: white; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 0.4rem; box-shadow: 0 4px 10px rgba(37, 211, 102, 0.2);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-2.202 0-3.991 1.789-3.991 3.991 0 .616.141 1.2.391 1.724l-.45 1.644 1.688-.443c.504.225 1.063.353 1.653.353 2.202 0 3.991-1.789 3.991-3.991 0-2.202-1.789-3.991-3.991-3.991zm2.345 5.613c-.131.371-.659.593-1.03.522-.37-.07-.723-.27-1.15-.558-.426-.288-.857-.665-1.127-1.036-.27-.371-.439-.8-.439-1.21 0-.411.17-.834.502-1.11.331-.276.711-.314.937-.314.226 0 .43.003.518.006.088.003.174.008.261.077.087.069.305.741.332.796.026.055.044.119.001.205-.043.086-.108.181-.191.277-.084.096-.174.201-.074.372.1.171.442.723.948 1.171.506.448 1.05.623 1.221.723.171.1.27.084.37-.015.1-.1.439-.516.554-.693.116-.176.23-.146.388-.087.158.059 1.01.476 1.184.563.175.087.291.13.334.205.043.076.043.438-.088.809zM12 2C6.477 2 2 6.477 2 12c0 1.892.524 3.662 1.435 5.176L2 22l4.958-1.413A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                        WhatsApp Support
                    </a>
                </div>
            </div>

            <div class="rules-section">
                <div class="rules-title">📋 Tournament Rules</div>
                <div class="rules-content">
                    <h4>Team Structure and Registration Rules</h4>
                    <ul>
                        <li><strong>Eligibility:</strong> Only Istinye University students may participate.</li>
                        <li><strong>Registration:</strong> You can apply with a Team Name if you have one, or as "Individual" or "Incomplete Team" if you don't.</li>
                        <li><strong>Roster:</strong> Full teams must have 7 to 12 players. Individual applicants will be placed into suitable teams by our staff.</li>
                        <li><strong>Gender:</strong> Each roster must include at least 2 female players; at all times at least 2 female players must be active on court. Replacing a female player with a male player reducing the count to 1 is a rule violation.</li>
                        <li class="whatsapp-warning"><strong>⚠️ ALL ANNOUNCEMENTS WILL BE MADE ON THE WHATSAPP CHANNEL — JOINING IS ESSENTIAL.</strong></li>
                        <li class="whatsapp-link-item"><a href="https://chat.whatsapp.com/F1qZB7Tya5cKsrYvxG93gc" target="_blank" rel="noopener noreferrer" class="whatsapp-btn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg> Join WhatsApp Channel</a></li>
                    </ul>
                    <h4>Match Format and Scoring</h4>
                    <ul>
                        <li><strong>Set System:</strong> Matches are played in a best-of-3 sets format (first to win 2 sets).</li>
                        <li><strong>Points:</strong> First two sets to 25 points; deciding set (3rd) to 15 points.</li>
                        <li><strong>Deuce:</strong> A minimum 2-point advantage is required to finish any set.</li>
                        <li><strong>Court Switch:</strong> Teams switch sides after each set.</li>
                    </ul>
                    <h4>Substitutions and Disqualification</h4>
                    <ul>
                        <li><strong>Substitutions:</strong> Each team may make up to 4 substitutions per set.</li>
                        <li><strong>Shortage:</strong> If a disqualified player leaves no bench players, that set is forfeited.</li>
                        <li><strong>Serve:</strong> Must be served within 8 seconds of the referee's whistle; foot faults are an error.</li>
                        <li><strong>Contact:</strong> The ball may be played with any body part; holding or carrying results in a fault.</li>
                        <li><strong>Violations:</strong> Touching the net or fully crossing the center line is prohibited.</li>
                    </ul>
                    <h4>Discipline and General Rules</h4>
                    <ul>
                        <li><strong>Referee Authority:</strong> The referee's decisions during the match are final and cannot be disputed.</li>
                        <li><strong>Discipline:</strong> In cases of disputes or unsportsmanlike conduct, the Tournament Committee may disqualify a team. Serious violations will be referred to the university disciplinary board.</li>
                        <li><strong>Dresscode:</strong> The dress code for the tournament will be announced a few days before the event. All teams must be prepared according to the announcement.</li>
                        <li><strong>Delay:</strong> A team not ready on court at match time will be declared the loser by default after a 15-minute waiting period.</li>
                    </ul>
                    <h4>Scoring System (Group Stage)</h4>
                    <ul>
                        <li><strong>2-0 Win:</strong> 3 Points</li>
                        <li><strong>2-1 Win:</strong> 2 Points</li>
                        <li><strong>1-2 Loss:</strong> 1 Point</li>
                        <li><strong>0-2 Loss:</strong> 0 Points</li>
                    </ul>
                </div>

                <label class="rules-consent" for="consent-en">
                    <input type="checkbox" id="consent-en" name="consent" required>
                    <span>I have read and agree to all tournament rules.</span>
                </label>
            </div>


            <button type="submit" class="btn-primary" id="submitBtnEN">
                <span>SUBMIT</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>

        <?php endif; ?>
    </div>
    <a href="https://chat.whatsapp.com/F1qZB7Tya5cKsrYvxG93gc" target="_blank" rel="noopener noreferrer" class="fab-whatsapp" aria-label="WhatsApp Kanalı">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        <span class="fab-whatsapp-tooltip">WhatsApp Kanalına Katıl</span>
    </a>
    <a href="https://www.istinye.edu.tr/tr/sks" target="_blank" rel="noopener noreferrer" class="sks-logo-link">

        <img src="isu_sks_logo.png" alt="İSÜ SKS Logo" class="sks-logo">
    </a>

    <!-- Mobile Sticky WhatsApp Bar -->
    <div class="mobile-sticky-bar">
        <a href="https://chat.whatsapp.com/F1qZB7Tya5cKsrYvxG93gc" target="_blank" rel="noopener noreferrer" class="mobile-wp-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <span>WhatsApp</span>
        </a>
    </div>


    <script src="script.js"></script>
    <script>
        function toggleRules(bodyId, arrowId) {
            const body = document.getElementById(bodyId);
            const arrow = document.getElementById(arrowId);
            if (!body || !arrow) return;
            body.classList.toggle('open');
            arrow.classList.toggle('open');
        }

        document.addEventListener('DOMContentLoaded', () => {

            function setupDynamicPlayers(containerId, btnId, isEn) {
                const playersContainer = document.getElementById(containerId);
                const addPlayerBtn = document.getElementById(btnId);
                const maxPlayers = 12;

                if (!playersContainer || !addPlayerBtn) return;

                addPlayerBtn.addEventListener('click', () => {
                    // Find the first hidden default player group, if any
                    let hiddenDefaultGroup = null;
                    const defaultGroups = playersContainer.children;
                    
                    for (let i = 0; i < defaultGroups.length; i++) {
                        const group = defaultGroups[i];
                        if (group.style.display === 'none' && !group.querySelector('.remove-player')) {
                            hiddenDefaultGroup = group;
                            break;
                        }
                    }

                    if (hiddenDefaultGroup) {
                        // Reshow a default group that was hidden due to "Incomplete" selection
                        hiddenDefaultGroup.style.display = 'flex';
                        const input = hiddenDefaultGroup.querySelector('input');
                        const appTypeObj = document.getElementById(isEn ? 'app_type_en' : 'app_type_tr');
                        if (appTypeObj && appTypeObj.value === 'incomplete') {
                            input.required = false;
                        } else {
                            input.required = true;
                        }
                        updateButtonText(playersContainer, addPlayerBtn, isEn);
                        return;
                    }


                    const currentPlayerCount = playersContainer.querySelectorAll('.player-input-group').length;

                    if (currentPlayerCount < maxPlayers) {
                        const newGroup = document.createElement('div');
                        newGroup.className = 'player-input-group';
                        const playerIndex = currentPlayerCount + 1;

                        const placeholderText = isEn
                            ? `${playerIndex}. Player Name (Substitute)`
                            : `${playerIndex}. Oyuncu Adı Soyadı (Yedek)`;

                        const titleText = isEn ? 'Remove Player' : 'Oyuncuyu Kadrodan Çıkar';
                        const idPrefix = isEn ? 'en_' : 'tr_';

                        newGroup.innerHTML = `
                            <span class="player-number">${playerIndex}</span>
                            <input type="text" id="player_${idPrefix}${playerIndex}" name="players[]" placeholder="${placeholderText}">
                            <button type="button" class="remove-player" title="${titleText}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                            </button>
                        `;
                        playersContainer.appendChild(newGroup);

                        const removeBtn = newGroup.querySelector('.remove-player');
                        removeBtn.addEventListener('click', () => {
                            newGroup.remove();
                            updatePlayerNumbers();
                            addPlayerBtn.style.display = 'flex';
                            updateButtonText(playersContainer, addPlayerBtn, isEn);
                        });

                        if (currentPlayerCount + 1 >= maxPlayers) {
                            addPlayerBtn.style.display = 'none';
                        }
                        
                        // If app_type is 'incomplete', ensure new player fields are not required
                        const appTypeObj = document.getElementById(isEn ? 'app_type_en' : 'app_type_tr');
                        if (appTypeObj && appTypeObj.value === 'incomplete') {
                            newGroup.querySelector('input').required = false;
                        }
                        
                        updateButtonText(playersContainer, addPlayerBtn, isEn);
                    }
                });

                function updatePlayerNumbers() {
                    let visibleIndex = 1;
                    const playerGroups = playersContainer.querySelectorAll('.player-input-group');
                    playerGroups.forEach((group, index) => {
                        if (group.style.display === 'none') {
                            // If it's a hidden default field, just clear its value
                            const input = group.querySelector('input');
                            if (input) input.value = '';
                            return;
                        }
                        
                        const num = visibleIndex;
                        group.querySelector('.player-number').textContent = num;
                        const input = group.querySelector('input');
                        
                        if (num <= 6) {
                            input.placeholder = isEn
                                ? num + '. Player Name (Starter)'
                                : num + '. Oyuncu Adı Soyadı (Asil)';
                        } else {
                            input.placeholder = isEn
                                ? num + '. Player Name (Substitute)'
                                : num + '. Oyuncu Adı Soyadı (Yedek)';
                        }
                        visibleIndex++;
                    });
                }
            }

            setupDynamicPlayers('players-container-tr', 'add-player-btn-tr', false);
            setupDynamicPlayers('players-container-en', 'add-player-btn-en', true);
            
            // Initialization
            window.toggleAppType = function(langPrefix) {
                const selectEl = document.getElementById('app_type_' + langPrefix);
                const isFull = selectEl.value === 'full';
                const teamNameHint = document.getElementById('team_name_hint_' + langPrefix);
                const warningMsg = document.getElementById('incomplete_warning_' + langPrefix);
                const playersContainer = document.getElementById('players-container-' + langPrefix);
                
                if (teamNameHint) {
                    teamNameHint.style.display = isFull ? 'none' : 'inline';
                }
                
                if (warningMsg) {
                    warningMsg.style.display = isFull ? 'none' : 'block';
                }
                
                const teamNameInput = document.getElementById('team_name_' + langPrefix);
                teamNameInput.required = isFull;
                
                // For Incomplete Teams, initially hide players 2-7 and make them not required
                // For Full Teams, show them and make them required
                for (let i = 2; i <= 7; i++) {
                    const playerGroup = playersContainer.children[i-1];
                    const playerInput = document.getElementById('player_' + langPrefix + '_' + i);
                    
                    if (playerGroup && playerInput) {
                        if (isFull) {
                            playerGroup.style.display = 'flex';
                            playerInput.required = true;
                        } else {
                            // If it's incomplete, we want to start with just 1 player field.
                            // Only hide if it's the default fields (no remove button). 
                            // If they added it manually, keep it visible.
                            if (!playerGroup.querySelector('.remove-player')) {
                                playerGroup.style.display = 'none';
                                playerInput.value = ''; // clear value if it was hidden
                            }
                            playerInput.required = false;
                        }
                    }
                }
                
                // First player (applicant) should always be required and visible
                const firstPlayer = document.getElementById('player_' + langPrefix + '_1');
                if (firstPlayer) {
                    firstPlayer.required = true;
                    firstPlayer.closest('.player-input-group').style.display = 'flex';
                }
                
                // Trigger button text update
                const btnId = 'add-player-btn-' + langPrefix;
                const isEn = langPrefix === 'en';
                updateButtonText(playersContainer, document.getElementById(btnId), isEn);
            };
            
            function updateButtonText(container, btn, isEn) {
                if (!container || !btn) return;
                
                // Count visible player groups
                let visibleCount = 0;
                container.querySelectorAll('.player-input-group').forEach(group => {
                    if (group.style.display !== 'none') visibleCount++;
                });

                const btnTextSpan = btn.querySelector('span');
                if (btnTextSpan) {
                    if (visibleCount < 7) {
                        btnTextSpan.textContent = isEn ? 'Add Player' : 'Oyuncu Ekle';
                    } else {
                        btnTextSpan.textContent = isEn ? 'Add Substitute' : 'Yedek Oyuncu Ekle';
                    }
                }
                
                // Also update placeholders just in case
                container.querySelectorAll('.player-input-group').forEach((group, index) => {
                     if (group.style.display === 'none') return;
                     const num = index + 1; // Need a better way to re-index visible fields
                });
            }
            
            toggleAppType('tr');
            toggleAppType('en');
        });
    </script>
</body>

</html>