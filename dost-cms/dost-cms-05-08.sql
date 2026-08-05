-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2026 at 10:30 AM
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
(17, 'R05-CMS-EL-2026-0001', 'EL', 'Outgoing', 'sample letter', 'sample office', 4, 'Processing', '2026-08-05 07:19:35'),
(18, 'R05-CMS-EL-2026-0002', 'EL', 'Outgoing', 'sample letter2', 'sample office', 4, 'Processing', '2026-08-05 07:35:19'),
(20, 'R05-CMS-IM-2026-0001', 'IM', 'Outgoing', 'sample Imemo', 'sample office', 4, 'Processing', '2026-08-05 08:26:21');

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
(40, 17, 'Processing', 'Document registered', '2026-08-05 07:19:35', 4),
(41, 18, 'Processing', 'Document registered', '2026-08-05 07:35:19', 4),
(45, 20, 'Processing', 'Document registered', '2026-08-05 08:26:21', 4),
(46, 20, 'Received', 'Status updated', '2026-08-05 08:29:13', 4),
(47, 20, 'Processing', 'Status updated', '2026-08-05 08:29:14', 4);

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `title`, `message`, `notification_type`, `related_document_id`, `reference_number`, `priority`, `is_read`, `created_at`) VALUES
(16, 'New Outgoing Document', 'Reference No. R05-CMS-EL-2026-0001 has been registered.\nEL • Aug 05, 2026', 'outgoing', 17, 'R05-CMS-EL-2026-0001', 'normal', 1, '2026-08-05 07:19:35'),
(17, 'New Outgoing Document', 'Reference No. R05-CMS-EL-2026-0002 has been registered.\nEL • Aug 05, 2026', 'outgoing', 18, 'R05-CMS-EL-2026-0002', 'normal', 1, '2026-08-05 07:35:19'),
(20, 'New Outgoing Document', 'Reference No. R05-CMS-IM-2026-0001 has been registered.\nIM • Aug 05, 2026', 'outgoing', 20, 'R05-CMS-IM-2026-0001', 'normal', 1, '2026-08-05 08:26:21'),
(21, 'Document Processing', 'Reference No. R05-CMS-IM-2026-0001 is now being processed.', 'processing', 20, 'R05-CMS-IM-2026-0001', 'normal', 1, '2026-08-05 08:29:14');

-- --------------------------------------------------------

--
-- Table structure for table `reference_counters`
--

CREATE TABLE `reference_counters` (
  `reference_type` varchar(10) NOT NULL,
  `seq_year` varchar(4) NOT NULL DEFAULT '',
  `last_sequence` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reference_counters`
--

INSERT INTO `reference_counters` (`reference_type`, `seq_year`, `last_sequence`) VALUES
('IM', '2026', 1);

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
(1, 'R05-CMS', '-', 4, 1);

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
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `version` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_name`, `username`, `password`, `status`, `role`, `version`, `created_at`) VALUES
(4, 'admin', 'admin', '$2y$10$t6JMFcGVQZvTMs8GZDwj4eDhf6tIb/uMKao97DJb60lW.0w.gMJ56', 'active', 'super_admin', 0, '2026-07-29 10:53:35'),
(19, 'PSTO Daet Admin', 'daetadmin', '$2y$10$kRPCuYnsdiKhY2m0qZLXSO2ZdtfXR7nKnn3X.mKtic7Tqt6dXH9c2', 'active', 'office_admin', 0, '2026-08-05 07:58:27'),
(20, 'daet user 1', 'daetuser', '$2y$10$6P92wJ.ykZzu/JVHnYqbiummM1QqBqJfnDzVPsb8D3SnqCIjFI3zu', 'active', 'user', 0, '2026-08-05 08:00:22');

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
-- Indexes for table `reference_counters`
--
ALTER TABLE `reference_counters`
  ADD PRIMARY KEY (`reference_type`,`seq_year`);

--
-- Indexes for table `reference_settings`
--
ALTER TABLE `reference_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `document_updates`
--
ALTER TABLE `document_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reference_settings`
--
ALTER TABLE `reference_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
