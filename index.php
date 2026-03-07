<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İstinye Üniversitesi Voleybol Turnuvası Başvuru Formu</title>
    <!-- SEO Best Practices -->
    <meta name="description"
        content="İstinye Üniversitesi voleybol turnuvası başvuru sayfası. Takımını kur, formunu doldur ve şampiyonluk mücadelesine katıl!">
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
        <header>
            <div class="logo-container">
                <div class="logo-placeholder">İSÜ</div>
            </div>
            <h1>Voleybol Turnuvası</h1>
            <p>Sahaya çıkmaya hazır mısın? Takımını kur, fılende yeteneğini göster ve kupayı evine götür!</p>
        </header>

        <form action="submit.php" method="POST" class="registration-form" id="tournamentForm">
            <div class="form-section" data-step="01">
                <h2>Takım Bilgileri</h2>
                <div class="input-group">
                    <label for="team_name">Takım Adı</label>
                    <input type="text" id="team_name" name="team_name" required placeholder="Örn: İSÜ Smaçörleri">
                </div>
            </div>

            <div class="form-section" data-step="02">
                <h2>Kaptan Bilgileri</h2>
                <div class="form-row">
                    <div class="input-group">
                        <label for="captain_name">Kaptan Adı Soyadı</label>
                        <input type="text" id="captain_name" name="captain_name" required
                            placeholder="Adınız Soyadınız">
                    </div>
                    <div class="input-group">
                        <label for="captain_phone">Telefon Numarası</label>
                        <input type="tel" id="captain_phone" name="captain_phone" required placeholder="05XX XXX XX XX">
                    </div>
                </div>
                <div class="input-group">
                    <label for="captain_email">E-posta Adresi</label>
                    <input type="email" id="captain_email" name="captain_email" required
                        placeholder="kaptan@istinye.edu.tr">
                </div>
            </div>

            <div class="form-section" data-step="03">
                <div class="section-header">
                    <h2>Oyuncu Kadrosu</h2>
                </div>
                <p class="section-desc">Sahaya çıkacak 6 asil oyuncu ve yedek kadronuzu belirleyin (Toplam maks. 12
                    kişi).</p>

                <div id="players-container">
                    <div class="player-input-group">
                        <span class="player-number">1</span>
                        <input type="text" id="player_1" name="players[]" placeholder="1. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">2</span>
                        <input type="text" id="player_2" name="players[]" placeholder="2. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">3</span>
                        <input type="text" id="player_3" name="players[]" placeholder="3. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">4</span>
                        <input type="text" id="player_4" name="players[]" placeholder="4. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">5</span>
                        <input type="text" id="player_5" name="players[]" placeholder="5. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">6</span>
                        <input type="text" id="player_6" name="players[]" placeholder="6. Oyuncu Adı Soyadı (Asil)"
                            required>
                    </div>
                </div>

                <button type="button" id="add-player-btn" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    Yedek Oyuncu Ekle
                </button>
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">
                Formayı Giy ve Başvur (Gönder)
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const playersContainer = document.getElementById('players-container');
            const addPlayerBtn = document.getElementById('add-player-btn');
            const maxPlayers = 12;

            addPlayerBtn.addEventListener('click', () => {
                const currentPlayerCount = playersContainer.querySelectorAll('.player-input-group').length;

                if (currentPlayerCount < maxPlayers) {
                    const newGroup = document.createElement('div');
                    newGroup.className = 'player-input-group';
                    const playerIndex = currentPlayerCount + 1;

                    newGroup.innerHTML = `
                        <span class="player-number">${playerIndex}</span>
                        <input type="text" id="player_${playerIndex}" name="players[]" placeholder="${playerIndex}. Oyuncu Adı Soyadı (Yedek)">
                        <button type="button" class="remove-player" title="Oyuncuyu Kadrodan Çıkar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        </button>
                    `;
                    playersContainer.appendChild(newGroup);

                    const removeBtn = newGroup.querySelector('.remove-player');
                    removeBtn.addEventListener('click', () => {
                        newGroup.remove();
                        updatePlayerNumbers();
                        addPlayerBtn.style.display = 'flex';
                    });

                    if (currentPlayerCount + 1 >= maxPlayers) {
                        addPlayerBtn.style.display = 'none';
                    }
                }
            });

            function updatePlayerNumbers() {
                const playerGroups = playersContainer.querySelectorAll('.player-input-group');
                playerGroups.forEach((group, index) => {
                    const num = index + 1;
                    group.querySelector('.player-number').textContent = num;
                    const input = group.querySelector('input');
                    input.id = 'player_' + num;
                    if (num <= 6) {
                        input.placeholder = num + '. Oyuncu Adı Soyadı (Asil)';
                    } else {
                        input.placeholder = num + '. Oyuncu Adı Soyadı (Yedek)';
                    }
                });
            }
        });
    </script>
</body>

</html>