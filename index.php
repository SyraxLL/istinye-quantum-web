<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title class="tr-content">İstinye Üniversitesi Voleybol Turnuvası Başvuru Formu</title>
    <title class="en-content" style="display:none;">Istinye University Volleyball Tournament Registration</title>
    <!-- SEO Best Practices -->
    <meta name="description"
        content="İstinye Üniversitesi voleybol turnuvası başvuru sayfası. Takımını kur, formunu doldur ve şampiyonluk mücadelesine katıl!">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>

<body>


    <div class="container">
        <header>
            <h1 class="tr-content">VOLEYBOL TURNUVASI</h1>
            <h1 class="en-content" style="display:none;">VOLLEYBALL TOURNAMENT</h1>
            
            <p class="tr-content">Sahaya çıkmaya hazır mısın? Takımını kur, yeteneğini göster ve kupayı evine götür!</p>
            <p class="en-content" style="display:none;">Ready to hit the court? Build your team, show your skills, and take the trophy home!</p>
            
            <!-- Sliding Toggle for Language -->
            <div class="lang-toggle-container">
                <label class="lang-switch" for="lang-checkbox">
                    <input type="checkbox" id="lang-checkbox">
                    <div class="slider"></div>
                    <span class="lang-label tr-label">TR</span>
                    <span class="lang-label en-label">EN</span>
                </label>
            </div>
        </header>

        <!-- ============================================== -->
        <!-- TURKISH FORM                                    -->
        <!-- ============================================== -->
        <form action="submit.php" method="POST" class="registration-form tr-content" id="tournamentFormTR">
            <input type="hidden" name="submitted_lang" value="tr">
            
            <div class="form-section" data-step="01">
                <h2>Takım Bilgileri</h2>
                <div class="input-group">
                    <label for="team_name_tr">Takım Adı</label>
                    <input type="text" id="team_name_tr" name="team_name" required placeholder="Örn: İSÜ Smaçörleri">
                </div>
            </div>

            <div class="form-section" data-step="02">
                <h2>Kaptan Bilgileri</h2>
                <div class="form-row">
                    <div class="input-group">
                        <label for="captain_name_tr">Kaptan Adı Soyadı</label>
                        <input type="text" id="captain_name_tr" name="captain_name" required placeholder="Adınız Soyadınız">
                    </div>
                    <div class="input-group">
                        <label for="captain_phone_tr">Telefon Numarası</label>
                        <input type="tel" id="captain_phone_tr" name="captain_phone" required placeholder="0555 555 55 55">
                    </div>
                </div>
                <div class="input-group">
                    <label for="captain_email_tr">E-posta Adresi</label>
                    <input type="email" id="captain_email_tr" name="captain_email" required placeholder="kaptan@istinye.edu.tr">
                </div>
            </div>

            <div class="form-section" data-step="03">
                <div class="section-header">
                    <h2>Oyuncu Kadrosu</h2>
                </div>
                <p class="section-desc">Sahaya çıkacak 6 asil oyuncu ve yedek kadronuzu belirleyin (Toplam maks. 12 kişi).</p>

                <div id="players-container-tr">
                    <div class="player-input-group">
                        <span class="player-number">1</span>
                        <input type="text" id="player_tr_1" name="players[]" placeholder="1. Oyuncu Adı Soyadı (Asil)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">2</span>
                        <input type="text" id="player_tr_2" name="players[]" placeholder="2. Oyuncu Adı Soyadı (Asil)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">3</span>
                        <input type="text" id="player_tr_3" name="players[]" placeholder="3. Oyuncu Adı Soyadı (Asil)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">4</span>
                        <input type="text" id="player_tr_4" name="players[]" placeholder="4. Oyuncu Adı Soyadı (Asil)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">5</span>
                        <input type="text" id="player_tr_5" name="players[]" placeholder="5. Oyuncu Adı Soyadı (Asil)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">6</span>
                        <input type="text" id="player_tr_6" name="players[]" placeholder="6. Oyuncu Adı Soyadı (Asil)" required>
                    </div>
                </div>

                <button type="button" id="add-player-btn-tr" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>Yedek Oyuncu Ekle</span>
                </button>
            </div>

            <button type="submit" class="btn-primary" id="submitBtnTR">
                <span>GÖNDER</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>

        <!-- ============================================== -->
        <!-- ENGLISH FORM                                    -->
        <!-- ============================================== -->
        <form action="submit.php" method="POST" class="registration-form en-content" id="tournamentFormEN" style="display: none;">
            <input type="hidden" name="submitted_lang" value="en">
            
            <div class="form-section" data-step="01">
                <h2>Team Details</h2>
                <div class="input-group">
                    <label for="team_name_en">Team Name</label>
                    <input type="text" id="team_name_en" name="team_name" required placeholder="E.g. ISU Spikers">
                </div>
            </div>

            <div class="form-section" data-step="02">
                <h2>Captain's Details</h2>
                <div class="form-row">
                    <div class="input-group">
                        <label for="captain_name_en">Captain's Full Name</label>
                        <input type="text" id="captain_name_en" name="captain_name" required placeholder="Your Full Name">
                    </div>
                    <div class="input-group">
                        <label for="captain_phone_en">Phone Number</label>
                        <input type="tel" id="captain_phone_en" name="captain_phone" required placeholder="+90 555 555 55 55">
                    </div>
                </div>
                <div class="input-group">
                    <label for="captain_email_en">Email Address</label>
                    <input type="email" id="captain_email_en" name="captain_email" required placeholder="captain@istinye.edu.tr">
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
                        <input type="text" id="player_en_1" name="players[]" placeholder="1. Player Name (Starter)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">2</span>
                        <input type="text" id="player_en_2" name="players[]" placeholder="2. Player Name (Starter)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">3</span>
                        <input type="text" id="player_en_3" name="players[]" placeholder="3. Player Name (Starter)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">4</span>
                        <input type="text" id="player_en_4" name="players[]" placeholder="4. Player Name (Starter)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">5</span>
                        <input type="text" id="player_en_5" name="players[]" placeholder="5. Player Name (Starter)" required>
                    </div>
                    <div class="player-input-group">
                        <span class="player-number">6</span>
                        <input type="text" id="player_en_6" name="players[]" placeholder="6. Player Name (Starter)" required>
                    </div>
                </div>

                <button type="button" id="add-player-btn-en" class="btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>Add Substitute</span>
                </button>
            </div>

            <button type="submit" class="btn-primary" id="submitBtnEN">
                <span>SUBMIT</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>
    </div>

    <!-- SKS Logo at Bottom Right -->
    <a href="https://www.istinye.edu.tr/tr/sks" target="_blank" rel="noopener noreferrer" class="sks-logo-link">
        <img src="isu_sks_logo.png" alt="İSÜ SKS Logo" class="sks-logo">
    </a>

    <!-- Application Script (UI & i18n) -->
    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // Reusable function to manage dynamic players for either form
            function setupDynamicPlayers(containerId, btnId, isEn) {
                const playersContainer = document.getElementById(containerId);
                const addPlayerBtn = document.getElementById(btnId);
                const maxPlayers = 12;

                if (!playersContainer || !addPlayerBtn) return;

                addPlayerBtn.addEventListener('click', () => {
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
                            <input type="text" id="player_${idPrefix}${playerIndex}" name="players[]" placeholder="${placeholderText}" required>
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
                        const idPrefix = isEn ? 'en_' : 'tr_';
                        input.id = 'player_' + idPrefix + num;
                        
                        if (num <= 6) {
                            input.placeholder = isEn 
                                ? num + '. Player Name (Starter)' 
                                : num + '. Oyuncu Adı Soyadı (Asil)';
                        } else {
                            input.placeholder = isEn 
                                ? num + '. Player Name (Substitute)' 
                                : num + '. Oyuncu Adı Soyadı (Yedek)';
                        }
                    });
                }
            }

            // Initialize dynamic logic for both forms individually
            setupDynamicPlayers('players-container-tr', 'add-player-btn-tr', false);
            setupDynamicPlayers('players-container-en', 'add-player-btn-en', true);
        });
    </script>
</body>

</html>