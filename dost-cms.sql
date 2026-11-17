-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2026 at 08:23 AM
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
-- Database: `dost-cms`
--
CREATE DATABASE IF NOT EXISTS `dost-cms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dost-cms`;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `reference_no` varchar(50) NOT NULL,
  `document_type` varchar(10) NOT NULL,
  `direction` enum('Incoming','Outgoing') NOT NULL DEFAULT 'Incoming',
  `title` varchar(255) NOT NULL,
  `origin_office` varchar(255) NOT NULL,
  `registered_by` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `status` enum('Received','Processing','Dispatched') DEFAULT 'Processing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(30) NOT NULL,
  `related_document_id` int(11) DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `office_id` int(11) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `office_id` int(11) NOT NULL,
  `office_code` varchar(20) NOT NULL,
  `office_name` varchar(100) NOT NULL,
  `preferred_prefix` varchar(50) NOT NULL DEFAULT '',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`office_id`, `office_code`, `office_name`, `preferred_prefix`, `status`, `created_at`) VALUES
(1, 'DOST-R5', 'DOST Regional Office V', 'R05-CMS', 'active', '2026-08-12 08:00:59');

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
('IM', '2026', 4);

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
  `office_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_name`, `username`, `password`, `status`, `role`, `version`, `office_id`, `created_at`) VALUES
(4, 'admin', 'admin', '$2y$10$t6JMFcGVQZvTMs8GZDwj4eDhf6tIb/uMKao97DJb60lW.0w.gMJ56', 'active', 'super_admin', 0, 1, '2026-07-29 10:53:35'),
(203, 'Jmeter Test User 12', 'jmeter_test_12', '$2y$10$WTXBKONcAwBZWgrSNekjN.6wWFhDjx20HYRIfnuKL23WsOe2lcV86', 'active', 'user', 0, 1, '2026-10-27 07:12:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`),
  ADD KEY `fk_documents_office` (`office_id`);

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
  ADD UNIQUE KEY `unique_event` (`notification_type`,`related_document_id`),
  ADD KEY `related_document_id` (`related_document_id`),
  ADD KEY `reference_number` (`reference_number`),
  ADD KEY `fk_notifications_office` (`office_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`office_id`),
  ADD UNIQUE KEY `uk_office_code` (`office_code`),
  ADD UNIQUE KEY `uk_office_name` (`office_name`);

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
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_office` (`office_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `document_updates`
--
ALTER TABLE `document_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `office_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reference_settings`
--
ALTER TABLE `reference_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=303;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_documents_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`office_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `document_updates`
--
ALTER TABLE `document_updates`
  ADD CONSTRAINT `document_updates_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`office_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`related_document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`office_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
