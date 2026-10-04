-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 22, 2026 at 11:58 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `field-marketing`
--

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'Bangla', '2026-09-22 09:34:03', '2026-09-22 09:43:24', NULL),
(3, 'English', '2026-09-22 09:34:19', '2026-09-22 09:34:19', NULL),
(4, 'Math', '2026-09-22 09:34:26', '2026-09-22 09:34:26', NULL),
(5, 'Sociology', '2026-09-22 09:34:59', '2026-09-22 09:50:16', '2026-09-22 09:50:16'),
(6, 'Physics', '2026-09-22 09:43:43', '2026-09-22 09:43:43', NULL),
(7, 'Chemistry', '2026-09-22 09:43:57', '2026-09-22 09:43:57', NULL),
(8, 'Religion', '2026-09-22 09:49:58', '2026-09-22 09:49:58', NULL),
(9, 'Sociology', '2026-09-22 09:50:59', '2026-09-22 09:50:59', NULL),
(10, 'Mathematics', '2026-09-22 10:41:02', '2026-09-22 10:41:02', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
