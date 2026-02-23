-- The 'store' database is created automatically via MYSQL_DATABASE env var.
-- Create additional schemas used by local services.
CREATE DATABASE IF NOT EXISTS `store_legacy` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `pterodactyl` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Provision a dedicated panel user on the shared MySQL instance.
CREATE USER IF NOT EXISTS 'pterodactyl'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON `pterodactyl`.* TO 'pterodactyl'@'%';

-- Grant the default app user access to app-owned schemas.
GRANT ALL PRIVILEGES ON `store_legacy`.* TO 'voxel'@'%';
FLUSH PRIVILEGES;

