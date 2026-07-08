-- AdminNeo 5.0.0 MySQL 8.4.0 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  `name` varchar(50) NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `posts` (`id`, `message`, `created_at`, `name`, `image`) VALUES
(1,	'こんばんわ',	'2026-06-30 15:38:27',	'',	''),
(3,	'夜ご飯はじゃがバター',	'2026-06-30 15:41:35',	'',	''),
(4,	'この名さんが投稿しました',	'2026-06-30 16:33:43',	'このな',	''),
(5,	'新しいプロフィール写真',	'2026-07-02 16:05:56',	'ぴか',	NULL),
(6,	'どがん？',	'2026-07-02 16:06:34',	'名無しさん',	NULL),
(9,	'ふｗｌん',	'2026-07-02 16:24:22',	'ぴか',	'20260702072422_6am461226302452.2Z7'),
(10,	'これは？',	'2026-07-02 16:26:30',	'ぴか',	'1782977190_6a4612a6205b1.png');

-- 2026-07-07 08:17:36 UTC
