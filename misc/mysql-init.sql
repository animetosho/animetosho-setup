-- needs to be run before InnoDB is disabled!
ALTER TABLE mysql.gtid_slave_pos engine=aria;
INSTALL SONAME 'ha_blackhole';

-- create databases
CREATE DATABASE IF NOT EXISTS toto_repl CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS anidb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS anito CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS arcscrape CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
