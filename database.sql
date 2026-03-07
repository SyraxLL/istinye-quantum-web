CREATE DATABASE IF NOT EXISTS `volleyball_tournament` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `volleyball_tournament`;

CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_name` varchar(100) NOT NULL,
  `captain_name` varchar(100) NOT NULL,
  `captain_phone` varchar(20) NOT NULL,
  `captain_email` varchar(100) NOT NULL,
  `players` json NOT NULL,
  `submitted_lang` varchar(5) NOT NULL DEFAULT 'tr',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
