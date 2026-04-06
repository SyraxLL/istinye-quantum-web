<?php
require_once __DIR__ . '/security.php';
startSecureSession();

$env_file = __DIR__ . '/.env';
$env = loadEnv($env_file);

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Erişim reddedildi.");
}

$pdo = null;
$fullTeams = [];
$incompleteTeams = [];

if ($env && isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USER'])) {
    try {
        $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'] . ";charset=" . ($env['DB_CHARSET'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT * FROM registrations ORDER BY id ASC");
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($registrations as $reg) {
            if (strpos($reg['team_name'], '(Eksik Kadro)') !== false || $reg['team_name'] === 'Bireysel/Eksik Başvuru') {
                $incompleteTeams[] = $reg;
            } else {
                $fullTeams[] = $reg;
            }
        }
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} else {
    die("Sistem yapılandırma hatası.");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Takım Listesi PDF Çıktısı</title>
    <!-- html2pdf js kütüphanesi (Sayfa yüklendiğinde otomatik PDF indirir) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }
        #documentWrapper {
            background: #ffffff;
            width: 100%;
            max-width: 800px; /* A4 genişliğine yakın */
            margin: 0 auto;
            padding: 40px;
            box-sizing: border-box;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 4px solid #004dbe;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #004dbe;
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 800;
        }
        .header h2 {
            color: #475569;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }
        
        .section-title {
            color: #004dbe;
            border-left: 5px solid #004dbe;
            padding-left: 15px;
            margin: 30px 0 20px 0;
            font-size: 20px;
        }
        .section-title.red {
            color: #dc2626;
            border-left-color: #dc2626;
        }

        .team-box {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            background: #f8fafc;
        }
        .team-box.red {
            border-color: #fecaca;
            background: #fff5f5;
        }

        .team-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .team-box.red .team-header {
            border-color: #fecaca;
        }

        .team-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }
        .team-box.red .team-title {
            color: #991b1b;
        }

        .team-meta {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #475569;
        }
        .team-box.red .team-meta {
            color: #7f1d1d;
        }

        .players-title {
            margin: 0 0 10px 0;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .player-table {
            width: 100%;
            border-collapse: collapse;
        }
        .player-table td {
            width: 50%;
            vertical-align: top;
            font-size: 12px;
            padding-bottom: 6px;
        }
        .role-badge {
            font-size: 9px;
            font-weight: 800;
            padding: 2px 5px;
            border-radius: 4px;
            display: inline-block;
            width: 40px;
            text-align: center;
            text-transform: uppercase;
            margin-right: 8px;
        }
        .role-asil { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .role-yedek { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        
        /* Bireysel/Eksik roller için özel renk */
        .role-red { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Yükleme ekranı stili */
        #loadingOverlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .spinner {
            border: 4px solid #cbd5e1;
            border-top: 4px solid #004dbe;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div id="loadingOverlay">
        <div class="spinner"></div>
        <h2 style="color: #004dbe; margin-top: 20px;">PDF Hazırlanıyor...</h2>
        <p style="color: #64748b;">(Bu yapı doğrudan veritabanından bilgi çeker. İşlem bitince sayfa kapanacaktır.)</p>
    </div>

    <!-- Veritabanından gelen tertemiz liste (Görünmez, sadece PDF'e dönüşecek) -->
    <div id="documentWrapper">
        <div class="header">
            <h1>İSTİNYE ÜNİVERSİTESİ</h1>
            <h2>Voleybol Turnuvası Veritabanı Dökümü</h2>
            <p>Oluşturulma Tarihi: <?php echo date('d.m.Y H:i'); ?></p>
        </div>

        <h3 class="section-title">Tam Takım Başvuruları (<?php echo count($fullTeams); ?>)</h3>
        
        <?php foreach ($fullTeams as $index => $row): ?>
            <?php if ($index > 0 && $index % 3 == 0): ?>
                <div style="page-break-before: always; height: 1px;"></div>
            <?php endif; ?>
            <div class="team-box">
                <div class="team-header">
                    <div style="float: right; font-size: 20px; font-weight: 800; color: #cbd5e1;">#<?php echo ($index + 1); ?></div>
                    <h4 class="team-title"><?php echo htmlspecialchars($row['team_name'] ?? ''); ?></h4>
                    <p class="team-meta">
                        <strong>Kaptan:</strong> <?php echo htmlspecialchars($row['captain_name'] ?? ''); ?> | 
                        📞 <?php echo htmlspecialchars($row['captain_phone'] ?? ''); ?> | 
                        ✉️ <?php echo htmlspecialchars($row['captain_email'] ?? ''); ?>
                    </p>
                </div>
                
                <h5 class="players-title" style="color: #334155;">Oyuncu Kadrosu</h5>
                <table class="player-table">
                    <?php 
                    $playersArr = isset($row['players']) ? json_decode($row['players'], true) : [];
                    if (!is_array($playersArr)) $playersArr = [];
                    
                    // 2 sütun tablolama
                    $total = max(count($playersArr), 6); // en az 6 asil sığsın
                    $col1 = [];
                    $col2 = [];
                    
                    foreach ($playersArr as $i => $p) {
                        if ($i % 2 == 0) $col1[] = ['idx' => $i, 'name' => $p];
                        else $col2[] = ['idx' => $i, 'name' => $p];
                    }
                    
                    $rows = max(count($col1), count($col2));
                    for ($r = 0; $r < $rows; $r++) {
                        echo "<tr>";
                        
                        // Sol Kolon
                        echo "<td>";
                        if (isset($col1[$r])) {
                            $idx = $col1[$r]['idx'];
                            $name = htmlspecialchars($col1[$r]['name']);
                            $roleText = ($idx < 6) ? 'ASİL' : 'YEDEK';
                            $roleClass = ($idx < 6) ? 'role-asil' : 'role-yedek';
                            echo "<span class='role-badge {$roleClass}'>{$roleText}</span> " . $name;
                        }
                        echo "</td>";
                        
                        // Sağ Kolon
                        echo "<td>";
                        if (isset($col2[$r])) {
                            $idx = $col2[$r]['idx'];
                            $name = htmlspecialchars($col2[$r]['name']);
                            $roleText = ($idx < 6) ? 'ASİL' : 'YEDEK';
                            $roleClass = ($idx < 6) ? 'role-asil' : 'role-yedek';
                            echo "<span class='role-badge {$roleClass}'>{$roleText}</span> " . $name;
                        }
                        echo "</td>";
                        
                        echo "</tr>";
                    }
                    if (empty($playersArr)) {
                        echo "<tr><td colspan='2' style='color:#94a3b8; font-style:italic;'>Veri girilmemiş</td></tr>";
                    }
                    ?>
                </table>
            </div>
        <?php endforeach; ?>
        
        <?php if(count($incompleteTeams) > 0): ?>
            <!-- Yeni Bölüm Sayfa Başı -->
            <div style="page-break-before: always; height: 1px;"></div>
            
            <h3 class="section-title red">Bireysel / Eksik Kayıtlar (<?php echo count($incompleteTeams); ?>)</h3>
            
            <?php foreach ($incompleteTeams as $index => $row): ?>
                <?php if ($index > 0 && $index % 5 == 0): ?>
                    <div style="page-break-before: always; height: 1px;"></div>
                <?php endif; ?>
                <div class="team-box red">
                    <div class="team-header">
                        <div style="float: right; font-size: 20px; font-weight: 800; color: #fca5a5;">#<?php echo ($index + 1); ?></div>
                        <h4 class="team-title"><?php echo htmlspecialchars($row['team_name'] ?? ''); ?></h4>
                        <p class="team-meta">
                            <strong>Kişi/Kaptan:</strong> <?php echo htmlspecialchars($row['captain_name'] ?? ''); ?> | 
                            📞 <?php echo htmlspecialchars($row['captain_phone'] ?? ''); ?> | 
                            ✉️ <?php echo htmlspecialchars($row['captain_email'] ?? ''); ?>
                        </p>
                    </div>
                    
                    <h5 class="players-title" style="color: #991b1b;">Ekli Oyuncular</h5>
                    <table class="player-table">
                        <?php 
                        $playersArr = isset($row['players']) ? json_decode($row['players'], true) : [];
                        if (!is_array($playersArr)) $playersArr = [];
                        
                        $col1 = [];
                        $col2 = [];
                        foreach ($playersArr as $i => $p) {
                            if ($i % 2 == 0) $col1[] = ['idx' => $i, 'name' => $p];
                            else $col2[] = ['idx' => $i, 'name' => $p];
                        }
                        
                        $rows = max(count($col1), count($col2));
                        for ($r = 0; $r < $rows; $r++) {
                            echo "<tr>";
                            // Sol
                            echo "<td>";
                            if (isset($col1[$r])) {
                                $name = htmlspecialchars($col1[$r]['name']);
                                $idx = $col1[$r]['idx'];
                                $roleText = ($idx < 6) ? 'ASİL' : 'YEDEK';
                                echo "<span class='role-badge role-red'>{$roleText}</span> " . $name;
                            }
                            echo "</td>";
                            // Sağ
                            echo "<td>";
                            if (isset($col2[$r])) {
                                $name = htmlspecialchars($col2[$r]['name']);
                                $idx = $col2[$r]['idx'];
                                $roleText = ($idx < 6) ? 'ASİL' : 'YEDEK';
                                echo "<span class='role-badge role-red'>{$roleText}</span> " . $name;
                            }
                            echo "</td>";
                            
                            echo "</tr>";
                        }
                        if (empty($playersArr)) {
                            echo "<tr><td colspan='2' style='color:#fca5a5; font-style:italic;'>Veri yok</td></tr>";
                        }
                        ?>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        window.addEventListener('load', function() {
            var element = document.getElementById('documentWrapper');
            
            var opt = {
                margin:       10,
                filename:     'Turnuva_Kayıtlı_Takımlar.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak:    { mode: ['css', 'legacy'] }
            };
            
            // PDF'i oluştur ve indir
            html2pdf().set(opt).from(element).save().then(function() {
                // İndirme başlayınca overlay mesajını değiştir ve sekmeyi kapat
                document.getElementById('loadingOverlay').innerHTML = '<div style="background: #10b981; color: white; padding: 20px; border-radius: 12px; text-align:center;"><h2>Başarıyla İndirildi!</h2><p>Bu pencereyi kapatabilirsiniz.</p></div>';
                setTimeout(function() {
                    window.close(); // Kullanıcı eğer yeni sekmede açtıysa sekme kapanır
                }, 3000);
            }).catch(function(err) {
                console.error(err);
                document.getElementById('loadingOverlay').innerHTML = '<div style="color: #dc2626;"><h2>Hata Oluştu!</h2><p>Lütfen sayfayı yenileyip tekrar deneyin.</p></div>';
            });
        });
    </script>
</body>
</html>
