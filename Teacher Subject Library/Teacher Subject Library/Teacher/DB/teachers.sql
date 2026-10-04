-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 22, 2026 at 11:59 AM
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
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `institution_id` bigint UNSIGNED DEFAULT NULL,
  `designation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teachers_institution_id_foreign` (`institution_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_name`, `institution_id`, `designation`, `department`, `subject`, `class_name`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Dr Abul Bashar', 2, 'Professor', 'Statistics', NULL, NULL, 1, '2026-09-17 10:42:24', '2026-09-22 10:56:27', '2026-09-22 10:56:27'),
(2, 'Jewel', 3, 'Professor', 'Statistics', NULL, NULL, 1, '2026-09-17 11:21:26', '2026-09-22 10:56:24', '2026-09-22 10:56:24'),
(3, 'Mamun Hossain', 6, 'Head Teacher', 'Science', NULL, NULL, 1, '2026-09-19 03:32:48', '2026-09-22 10:56:21', '2026-09-22 10:56:21'),
(4, 'Professor Dr Salauddin', 3, 'Professor', 'Science', NULL, NULL, 1, '2026-09-19 05:25:26', '2026-09-22 10:56:18', '2026-09-22 10:56:18'),
(5, 'Test Teacher Verify', NULL, NULL, NULL, NULL, NULL, 1, '2026-09-20 11:42:22', '2026-09-20 11:42:22', '2026-09-20 11:42:22'),
(7, 'Md. Imran Hossain Emu', 7, 'Lecturer', 'Science', 'English', 'Class 10', 1, '2026-09-22 11:02:29', '2026-09-22 11:02:29', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
