-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Feb 24, 2026 at 04:27 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pterodactyl`
--

--
-- Dumping data for table `allocations`
--

INSERT INTO `allocations` (`id`, `node_id`, `ip`, `ip_alias`, `port`, `server_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '127.0.0.1', NULL, 26625, NULL, NULL, NULL, NULL),
(2, 1, '127.0.0.1', NULL, 26626, NULL, NULL, NULL, NULL),
(3, 1, '127.0.0.1', NULL, 26627, NULL, NULL, NULL, NULL),
(4, 1, '127.0.0.1', NULL, 26628, NULL, NULL, NULL, NULL),
(5, 1, '127.0.0.1', NULL, 26629, NULL, NULL, NULL, NULL),
(6, 1, '127.0.0.1', NULL, 26630, NULL, NULL, NULL, NULL),
(7, 1, '127.0.0.1', NULL, 26631, NULL, NULL, NULL, NULL),
(8, 1, '127.0.0.1', NULL, 26632, NULL, NULL, NULL, NULL),
(9, 1, '127.0.0.1', NULL, 26633, NULL, NULL, NULL, NULL),
(10, 1, '127.0.0.1', NULL, 26634, NULL, NULL, NULL, NULL),
(11, 1, '127.0.0.1', NULL, 26635, NULL, NULL, NULL, NULL),
(12, 1, '127.0.0.1', NULL, 26636, NULL, NULL, NULL, NULL),
(13, 1, '127.0.0.1', NULL, 26637, NULL, NULL, NULL, NULL),
(14, 1, '127.0.0.1', NULL, 26638, NULL, NULL, NULL, NULL),
(15, 1, '127.0.0.1', NULL, 26639, NULL, NULL, NULL, NULL),
(16, 1, '127.0.0.1', NULL, 26640, NULL, NULL, NULL, NULL),
(17, 1, '127.0.0.1', NULL, 26641, NULL, NULL, NULL, NULL),
(18, 1, '127.0.0.1', NULL, 26642, NULL, NULL, NULL, NULL),
(19, 1, '127.0.0.1', NULL, 26643, NULL, NULL, NULL, NULL),
(20, 1, '127.0.0.1', NULL, 26644, NULL, NULL, NULL, NULL),
(21, 1, '127.0.0.1', NULL, 26645, NULL, NULL, NULL, NULL),
(22, 1, '127.0.0.1', NULL, 26646, NULL, NULL, NULL, NULL),
(23, 1, '127.0.0.1', NULL, 26647, NULL, NULL, NULL, NULL),
(24, 1, '127.0.0.1', NULL, 26648, NULL, NULL, NULL, NULL),
(25, 1, '127.0.0.1', NULL, 26649, NULL, NULL, NULL, NULL),
(26, 1, '127.0.0.1', NULL, 26650, NULL, NULL, NULL, NULL),
(27, 1, '127.0.0.1', NULL, 26651, NULL, NULL, NULL, NULL),
(28, 1, '127.0.0.1', NULL, 26652, NULL, NULL, NULL, NULL),
(29, 1, '127.0.0.1', NULL, 26653, NULL, NULL, NULL, NULL),
(30, 1, '127.0.0.1', NULL, 26654, NULL, NULL, NULL, NULL),
(31, 1, '127.0.0.1', NULL, 26655, NULL, NULL, NULL, NULL),
(32, 1, '127.0.0.1', NULL, 26656, NULL, NULL, NULL, NULL),
(33, 1, '127.0.0.1', NULL, 26657, NULL, NULL, NULL, NULL),
(34, 1, '127.0.0.1', NULL, 26658, NULL, NULL, NULL, NULL),
(35, 1, '127.0.0.1', NULL, 26659, NULL, NULL, NULL, NULL),
(36, 1, '127.0.0.1', NULL, 26660, NULL, NULL, NULL, NULL),
(37, 1, '127.0.0.1', NULL, 26661, NULL, NULL, NULL, NULL),
(38, 1, '127.0.0.1', NULL, 26662, NULL, NULL, NULL, NULL),
(39, 1, '127.0.0.1', NULL, 26663, NULL, NULL, NULL, NULL),
(40, 1, '127.0.0.1', NULL, 26664, NULL, NULL, NULL, NULL),
(41, 1, '127.0.0.1', NULL, 26665, NULL, NULL, NULL, NULL),
(42, 1, '127.0.0.1', NULL, 26666, NULL, NULL, NULL, NULL),
(43, 1, '127.0.0.1', NULL, 26667, NULL, NULL, NULL, NULL),
(44, 1, '127.0.0.1', NULL, 26668, NULL, NULL, NULL, NULL),
(45, 1, '127.0.0.1', NULL, 26669, NULL, NULL, NULL, NULL),
(46, 1, '127.0.0.1', NULL, 26670, NULL, NULL, NULL, NULL),
(47, 1, '127.0.0.1', NULL, 26671, NULL, NULL, NULL, NULL),
(48, 1, '127.0.0.1', NULL, 26672, NULL, NULL, NULL, NULL),
(49, 1, '127.0.0.1', NULL, 26673, NULL, NULL, NULL, NULL),
(50, 1, '127.0.0.1', NULL, 26674, NULL, NULL, NULL, NULL),
(51, 1, '127.0.0.1', NULL, 26675, NULL, NULL, NULL, NULL),
(52, 1, '127.0.0.1', NULL, 26676, NULL, NULL, NULL, NULL),
(53, 1, '127.0.0.1', NULL, 26677, NULL, NULL, NULL, NULL),
(54, 1, '127.0.0.1', NULL, 26678, NULL, NULL, NULL, NULL),
(55, 1, '127.0.0.1', NULL, 26679, NULL, NULL, NULL, NULL),
(56, 1, '127.0.0.1', NULL, 26680, NULL, NULL, NULL, NULL),
(57, 1, '127.0.0.1', NULL, 26681, NULL, NULL, NULL, NULL),
(58, 1, '127.0.0.1', NULL, 26682, NULL, NULL, NULL, NULL),
(59, 1, '127.0.0.1', NULL, 26683, NULL, NULL, NULL, NULL),
(60, 1, '127.0.0.1', NULL, 26684, NULL, NULL, NULL, NULL),
(61, 1, '127.0.0.1', NULL, 26685, NULL, NULL, NULL, NULL),
(62, 1, '127.0.0.1', NULL, 26686, NULL, NULL, NULL, NULL),
(63, 1, '127.0.0.1', NULL, 26687, NULL, NULL, NULL, NULL),
(64, 1, '127.0.0.1', NULL, 26688, NULL, NULL, NULL, NULL),
(65, 1, '127.0.0.1', NULL, 26689, NULL, NULL, NULL, NULL),
(66, 1, '127.0.0.1', NULL, 26690, NULL, NULL, NULL, NULL),
(67, 1, '127.0.0.1', NULL, 26691, NULL, NULL, NULL, NULL),
(68, 1, '127.0.0.1', NULL, 26692, NULL, NULL, NULL, NULL),
(69, 1, '127.0.0.1', NULL, 26693, NULL, NULL, NULL, NULL),
(70, 1, '127.0.0.1', NULL, 26694, NULL, NULL, NULL, NULL),
(71, 1, '127.0.0.1', NULL, 26695, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int UNSIGNED NOT NULL,
  `short` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `long` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `short`, `long`, `created_at`, `updated_at`) VALUES
(1, 'eu.ger', NULL, '2026-02-24 16:16:32', '2026-02-24 16:16:32');

-- --------------------------------------------------------

--
-- Table structure for table `nodes`
--

CREATE TABLE `nodes` (
  `id` int UNSIGNED NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public` smallint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location_id` int UNSIGNED NOT NULL,
  `fqdn` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheme` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'https',
  `behind_proxy` tinyint(1) NOT NULL DEFAULT '0',
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT '0',
  `memory` int UNSIGNED NOT NULL,
  `memory_overallocate` int NOT NULL DEFAULT '0',
  `disk` int UNSIGNED NOT NULL,
  `disk_overallocate` int NOT NULL DEFAULT '0',
  `upload_size` int UNSIGNED NOT NULL DEFAULT '100',
  `daemon_token_id` char(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daemon_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `daemonListen` smallint UNSIGNED NOT NULL DEFAULT '8080',
  `daemonSFTP` smallint UNSIGNED NOT NULL DEFAULT '2022',
  `daemonBase` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/home/daemon-files',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nodes`
--

INSERT INTO `nodes` (`id`, `uuid`, `public`, `name`, `description`, `location_id`, `fqdn`, `scheme`, `behind_proxy`, `maintenance_mode`, `memory`, `memory_overallocate`, `disk`, `disk_overallocate`, `upload_size`, `daemon_token_id`, `daemon_token`, `daemonListen`, `daemonSFTP`, `daemonBase`, `created_at`, `updated_at`) VALUES
(1, 'f2b6f48d-1449-4f7a-96d5-69ddbe6eac8c', 1, 'eu.ger.fsn-1', NULL, 1, 'pterodactyl-wings', 'http', 0, 0, 4096, 10, 1000, 100, 100, 'Yt7fFgg8lbbYQpTI', 'eyJpdiI6IjMzZ1JucWhRRCs1d0REOXBvNWN2VGc9PSIsInZhbHVlIjoiS3l3N0J2WHV1aUEzMGFHakl1dXZ5OGtDY3U5MkJkUUxOWlZFMngwWERrcjVlTVdDcGEvbEpIYnRsTWtmTzJUTXJqcDhOWGRzYnl0MmdpdEJPOTVxMi8zcmt1QmhpZlZRc3diNEwzYndFUUE9IiwibWFjIjoiOGIyZTEyODIxOTRjOGQyMDRhMTRhYjY1OWQzMzI0ZWYwMDNlMGE1ZTc3NmQ5MjkxZmZiMjE5Yzk0NzVlMzI1MiIsInRhZyI6IiJ9', 8080, 2022, '/var/lib/pterodactyl/volumes', '2026-02-24 16:18:58', '2026-02-24 16:24:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_first` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_last` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` char(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `root_admin` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `use_totp` tinyint UNSIGNED NOT NULL,
  `totp_secret` text COLLATE utf8mb4_unicode_ci,
  `totp_authenticated_at` timestamp NULL DEFAULT NULL,
  `gravatar` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `external_id`, `uuid`, `username`, `email`, `name_first`, `name_last`, `password`, `remember_token`, `language`, `root_admin`, `use_totp`, `totp_secret`, `totp_authenticated_at`, `gravatar`, `created_at`, `updated_at`) VALUES
(1, NULL, '8eaa98a5-d501-4291-aef8-ff33c8a836f5', 'tom', 'tom@intera.digital', 'Tom', 'Kent', '$2y$10$SnnyXJt1wUCCx7yJDeBhPebxhCAi5L7eFy.xG5gR.ftF1rGHGJV6C', '1QPSpab0zeRZeyYWDcgtOfFj9AfvEZmg51X2w2v7CWlclHZLBg9fqhIBp3uQ', 'en', 1, 0, NULL, NULL, 1, '2026-02-23 21:10:18', '2026-02-23 21:10:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `locations_short_unique` (`short`);

--
-- Indexes for table `nodes`
--
ALTER TABLE `nodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nodes_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `nodes_daemon_token_id_unique` (`daemon_token_id`),
  ADD KEY `nodes_location_id_foreign` (`location_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `users_external_id_index` (`external_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nodes`
--
ALTER TABLE `nodes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `nodes`
--
ALTER TABLE `nodes`
  ADD CONSTRAINT `nodes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
