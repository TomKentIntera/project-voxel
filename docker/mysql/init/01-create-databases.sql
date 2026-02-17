-- The 'store' database is created automatically via MYSQL_DATABASE env var.
-- Create the legacy database here.
CREATE DATABASE IF NOT EXISTS `store_legacy` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant the default user full access to both databases.
GRANT ALL PRIVILEGES ON `store_legacy`.* TO 'voxel'@'%';
FLUSH PRIVILEGES;

