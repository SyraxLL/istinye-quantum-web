-- --------------------------------------------------------
-- 1. Ayarlar Tablosu (Mod kontrolü için)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Varsayılan mod: registration
INSERT IGNORE INTO `settings` (`meta_key`, `meta_value`) VALUES ('site_mode', 'registration');

-- --------------------------------------------------------
-- 2. Maçlar Tablosu (Turnuva ağacı için)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `round` int(11) NOT NULL,                -- 1: Son 16, 2: Çeyrek, 3: Yarı, 4: Final
  `match_order` int(11) NOT NULL,          -- Tur içindeki sıralama
  `team1_id` int(11) DEFAULT NULL,         -- registrations tablosuna referans (isteğe bağlı)
  `team2_id` int(11) DEFAULT NULL,
  `team1_name` varchar(255) DEFAULT NULL,  -- Manuel isim girişi fallback
  `team2_name` varchar(255) DEFAULT NULL,
  `match_time` varchar(50) DEFAULT NULL,   -- Maç Saati (örn: 10 Nisan 14:00)
  `score1` int(11) DEFAULT 0,
  `score2` int(11) DEFAULT 0,
  `winner_id` int(11) DEFAULT NULL,
  `winner_name` varchar(255) DEFAULT NULL,
  `next_match_id` int(11) DEFAULT NULL,    -- Bir sonraki turun maçı
  `next_match_slot` int(11) DEFAULT 1,     -- Bir sonraki maçta 1 mı 2 mi olacak?
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Round 1 (Son 16 - Maç 1-8)
INSERT IGNORE INTO `matches` (`id`, `round`, `match_order`, `next_match_id`, `next_match_slot`) VALUES 
(1, 1, 1, 9, 1), (2, 1, 2, 9, 2), (3, 1, 3, 10, 1), (4, 1, 4, 10, 2), 
(5, 1, 5, 11, 1), (6, 1, 6, 11, 2), (7, 1, 7, 12, 1), (8, 1, 8, 12, 2);

-- Round 2 (Çeyrek Final - Maç 9-12)
INSERT IGNORE INTO `matches` (`id`, `round`, `match_order`, `next_match_id`, `next_match_slot`) VALUES 
(9, 2, 1, 13, 1), (10, 2, 2, 13, 2), (11, 2, 3, 14, 1), (12, 2, 4, 14, 2);

-- Round 3 (Yarı Final - Maç 13-14)
INSERT IGNORE INTO `matches` (`id`, `round`, `match_order`, `next_match_id`, `next_match_slot`) VALUES 
(13, 3, 1, 15, 1), (14, 3, 2, 15, 2);

-- Round 4 (Final - Maç 15)
INSERT IGNORE INTO `matches` (`id`, `round`, `match_order`, `next_match_id`, `next_match_slot`) VALUES 
(15, 4, 1, NULL, 0);
