CREATE DATABASE IF NOT EXISTS storage CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE IF NOT EXISTS `storage`.`toto_attachment_files_storecache` (
  `id` int(10) unsigned NOT NULL,
  `cacheinfo` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;
