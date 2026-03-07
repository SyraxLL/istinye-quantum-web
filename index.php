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

<body>


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
                <strong class="highlight-action">Takımını kur</strong>, yeteneğini göster ve <strong
                    class="highlight-action">kupayı evine götür!</strong>
            </p>
            <p class="en-content desc-dynamic" style="display:none;">
                Ready to hit the court? <br>
                <strong class="highlight-action">Build your team</strong>, show your skills, and <strong
                    class="highlight-action">take the trophy home!</strong>
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
                <p class="section-desc">Sahaya çıkacak 6 asil oyuncu ve yedek kadronuzu belirleyin (Toplam maks. 10
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
            </div>

            <div class="rules-section">
                <div class="rules-title">📋 Turnuva Kuralları</div>
                <div class="rules-content">
                    <h4>Takım Yapısı ve Başvuru Esasları</h4>
                    <ul>
                        <li><strong>Katılım Şartı:</strong> Turnuvaya sadece İstinye Üniversitesi öğrencileri katılabilir.</li>
                        <li><strong>Başvuru:</strong> Başvurular kişisel değil, belirlenen bir Takım İsmi ile yapılmalıdır.</li>
                        <li><strong>Kadro:</strong> Takımlar en az 6, en fazla 10 oyuncudan oluşmalıdır. Sahada maç 6 kişilik takımlar halinde oynanır.</li>
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
                <p class="section-desc">List your 6 starting players and substitutes (Max. 10 players total).</p>

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
            </div>

            <div class="rules-section">
                <div class="rules-title">📋 Tournament Rules</div>
                <div class="rules-content">
                    <h4>Team Structure and Registration Rules</h4>
                    <ul>
                        <li><strong>Eligibility:</strong> Only Istinye University students may participate.</li>
                        <li><strong>Registration:</strong> Applications must be submitted under a Team Name, not individually.</li>
                        <li><strong>Roster:</strong> Teams must have a minimum of 6 and a maximum of 10 players. Matches are played 6-a-side.</li>
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
                const maxPlayers = 10;

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

            setupDynamicPlayers('players-container-tr', 'add-player-btn-tr', false);
            setupDynamicPlayers('players-container-en', 'add-player-btn-en', true);
        });
    </script>
</body>

</html>