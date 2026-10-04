-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 22, 2026 at 12:00 PM
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
-- Table structure for table `libraries`
--

DROP TABLE IF EXISTS `libraries`;
CREATE TABLE IF NOT EXISTS `libraries` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upazila_id` bigint UNSIGNED DEFAULT NULL,
  `district_id` int UNSIGNED DEFAULT NULL,
  `division_id` bigint UNSIGNED DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraries_code_unique` (`code`),
  KEY `libraries_upazila_id_index` (`upazila_id`),
  KEY `libraries_district_id_index` (`district_id`),
  KEY `libraries_division_id_index` (`division_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `libraries`
--

INSERT INTO `libraries` (`id`, `name`, `library_name`, `code`, `email`, `phone`, `upazila_id`, `district_id`, `division_id`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Test Library', 'Test Library', 'LIB-001', 'lib@test.com', '01700000000', NULL, NULL, NULL, 'Test Address', 1, '2026-09-21 05:27:21', '2026-09-21 05:27:49', '2026-09-21 05:27:49'),
(2, 'WLFSC Library', 'WLFSC Library', 'WLFCS-001', 'willes_little@yahoo.com', '01715323827', 156, 18, 6, '85 Kakrail, Dhaka 1000, Bangladesh', 1, '2026-09-21 06:13:06', '2026-09-21 06:23:46', NULL),
(3, 'Ayesha Abed Library', 'Ayesha Abed Library', 'AAL-001', 'librarian@bracu.ac.bd', '+880 9638-464646', 158, 19, 6, 'Kha 224, Bir Uttam Rafiqul Islam Avenue, Merul Badda, Dhaka, Bangladesh', 1, '2026-09-21 06:21:41', '2026-09-21 06:21:41', NULL),
(4, 'BAIUST Library', 'BAIUST Library', 'BAIUSTL-005', 'librarian@baiust.ac.bd', '01324763890', 121, 14, 1, 'Syedpur, Adarsha Sadar, Cumilla', 1, '2026-09-21 06:29:50', '2026-09-21 06:29:50', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
