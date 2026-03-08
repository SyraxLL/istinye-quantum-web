-- 1. Veritabanı Seçimi (Eğer manuel oluşturduysanız bu satırı silebilirsiniz)
-- USE `istinyequantum_database1`;

-- 2. Tabloları Temizle (Eskiden kalan hatalı yapı varsa siler)
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `admins`;

-- 3. Başvurular Tablosu (registrations)
CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `captain_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `captain_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `captain_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `players` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL, -- JSON formatında oyuncu listesi
  `submitted_lang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'tr',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Yönetici Tablosu (admins)
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, -- Hashed password
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Varsayılan Yönetici Hesabı (Kullanıcı: admin | Şifre: admin123)
-- Not: Bu veri ilk girişinizi yapabilmeniz içindir.
INSERT INTO `admins` (`username`, `password`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
