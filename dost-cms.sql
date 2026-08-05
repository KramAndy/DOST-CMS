-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2026 at 04:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dost_cms`
--
CREATE DATABASE IF NOT EXISTS `dost_cms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dost_cms`;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `reference_no` varchar(50) NOT NULL,
  `document_type` varchar(10) NOT NULL,
  `direction` varchar(20) NOT NULL DEFAULT 'Incoming',
  `title` varchar(255) NOT NULL,
  `origin_office` varchar(255) NOT NULL,
  `registered_by` int(11) NOT NULL,
  `status` enum('Received','Processing','Dispatched') DEFAULT 'Processing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `reference_no`, `document_type`, `direction`, `title`, `origin_office`, `registered_by`, `status`, `created_at`) VALUES
(3, 'R05-CMS-EL-2026-0001', 'EL', 'Incoming', 'Test mail', 'PSTO-Daet', 0, 'Received', '2026-07-30 03:21:46'),
(4, 'R05-CMS-MOA-2026-0001', 'MOA', 'Incoming', 'BLa Bla Bla test', 'PSTO-Daet', 0, 'Processing', '2026-07-30 03:36:32'),
(5, 'R05-CMS-SO-2026-0001', 'SO', 'Incoming', 'waa', 'waaa', 0, 'Processing', '2026-07-30 03:43:03'),
(6, 'R05-CMS-SO-2026-0002', 'SO', 'Incoming', 'special order', 'PSTO - Daet', 4, 'Processing', '2026-07-30 05:31:39'),
(7, 'R05-CMS-MOA-2026-0002', 'MOA', 'Incoming', 'MOA for OJT', 'PSTO Daet', 4, 'Dispatched', '2026-08-03 02:53:00');

-- --------------------------------------------------------

--
-- Table structure for table `document_updates`
--

CREATE TABLE `document_updates` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_updates`
--

INSERT INTO `document_updates` (`id`, `document_id`, `status`, `notes`, `created_at`, `updated_by`) VALUES
(4, 3, 'Received', 'Document registered', '2026-07-30 03:21:46', NULL),
(5, 3, 'Processing', 'Status updated', '2026-07-30 03:23:07', NULL),
(6, 3, 'Received', 'Status updated', '2026-07-30 03:24:05', NULL),
(7, 3, 'Processing', 'Status updated', '2026-07-30 03:28:00', NULL),
(8, 4, 'Received', 'Document registered', '2026-07-30 03:36:32', NULL),
(9, 5, 'Processing', 'Document registered', '2026-07-30 03:43:03', NULL),
(10, 6, 'Processing', 'Document registered', '2026-07-30 05:31:39', NULL),
(11, 3, 'Dispatched', 'Status updated', '2026-07-30 06:06:58', 4),
(12, 3, 'Received', 'Status updated', '2026-07-30 06:07:16', 4),
(13, 3, 'Processing', 'Status updated', '2026-07-30 06:07:21', 4),
(14, 3, 'Received', 'Status updated', '2026-07-30 06:11:46', 4),
(15, 7, 'Processing', 'Document registered', '2026-08-03 02:53:00', NULL),
(16, 7, 'Processing', 'for signing', '2026-08-03 02:53:48', 4),
(17, 7, 'Dispatched', 'Status updated', '2026-08-03 02:53:55', 4);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `notification_type` varchar(50) NOT NULL,
  `related_document_id` int(11) DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reference_settings`
--

CREATE TABLE `reference_settings` (
  `id` int(11) NOT NULL,
  `prefix` varchar(50) NOT NULL DEFAULT 'R05-CMS',
  `separator` varchar(5) NOT NULL DEFAULT '-',
  `padding` int(11) NOT NULL DEFAULT 4,
  `include_year` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reference_settings`
--

INSERT INTO `reference_settings` (`id`, `prefix`, `separator`, `padding`, `include_year`) VALUES
(1, 'PSTODAET-CMS', '-', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `employee_name` varchar(50) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_name`, `username`, `password`, `status`, `created_at`) VALUES
(4, 'admin', 'admin', '$2y$10$t6JMFcGVQZvTMs8GZDwj4eDhf6tIb/uMKao97DJb60lW.0w.gMJ56', 'active', '2026-07-29 10:53:35'),
(5, 'Mark Andrian Escalante', 'andy', '$2y$10$jURhGtkBTWB1rX7eOu.MAuwRZIYPzyMo4xscheTvYazfWzx82nnT.', 'active', '2026-08-05 00:40:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`);

--
-- Indexes for table `document_updates`
--
ALTER TABLE `document_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD UNIQUE KEY `notification_type_document` (`notification_type`,`related_document_id`);

--
-- Indexes for table `reference_settings`
--
ALTER TABLE `reference_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `document_updates`
--
ALTER TABLE `document_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `reference_settings`
--
ALTER TABLE `reference_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `document_updates`
--
ALTER TABLE `document_updates`
  ADD CONSTRAINT `document_updates_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
