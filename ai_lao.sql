-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 01:55 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ai_lao`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_templates`
--

CREATE TABLE `ai_templates` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `model_key` varchar(50) DEFAULT 'nano-banana-pro',
  `system_prompt` text DEFAULT NULL,
  `text_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`text_config`)),
  `price` decimal(10,2) DEFAULT 5000.00,
  `image_preview` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_templates`
--

INSERT INTO `ai_templates` (`id`, `title`, `cost_price`, `model_key`, `system_prompt`, `text_config`, `price`, `image_preview`, `is_active`) VALUES
(1, 'ສ້າງປ້າຍລາຄາເກມ', 900.00, 'nano-banana-pro', 'Marketing poster for {game_name} mobile game. High quality, cinematic lighting, professional sports or fantasy background. Leave empty space in the center for text overlay.', '{\r\n    \"title\": {\r\n        \"x\": 50,\r\n        \"y\": 750,\r\n        \"size\": 60,\r\n        \"color\": \"white\"\r\n    },\r\n    \"price\": {\r\n        \"x\": 50,\r\n        \"y\": 850,\r\n        \"size\": 90,\r\n        \"color\": \"#FFD700\"\r\n    },\r\n    \"closing\": {\r\n        \"x\": 50,\r\n        \"y\": 960,\r\n        \"size\": 40,\r\n        \"color\": \"#cccccc\"\r\n    }\r\n}', 5000.00, NULL, 1),
(2, 'ສ້າງຮູບສິນຄ້າທົວໄປ', 1000.00, 'nano-banana-pro', '54', '{\r\n    \"title\": {\r\n        \"x\": 50,\r\n        \"y\": 800,\r\n        \"size\": 60,\r\n        \"color\": \"white\"\r\n    }\r\n}', 5000.00, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `task_id` varchar(100) DEFAULT NULL,
  `user_inputs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`user_inputs`)),
  `final_image_path` varchar(255) DEFAULT NULL,
  `status` enum('processing','completed','failed') DEFAULT 'processing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `template_id`, `task_id`, `user_inputs`, `final_image_path`, `status`, `created_at`) VALUES
(1, 1, 1, NULL, NULL, NULL, '', '2026-01-13 06:34:08'),
(2, 1, 1, NULL, NULL, NULL, 'failed', '2026-01-13 06:37:36'),
(3, 1, 1, 'd2963c686071b3e060b35be48f9f8d34', NULL, NULL, 'processing', '2026-01-13 06:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `credit` decimal(10,2) DEFAULT 0.00,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `fullname`, `email`, `avatar`, `credit`, `role`, `created_at`) VALUES
(1, 'test_google_id', 'ເປົາ ໄຊຍະສານ', 'admin@test.com', 'https://ui-avatars.com/api/?name=Admin', 9985000.00, 'admin', '2026-01-13 04:43:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_templates`
--
ALTER TABLE `ai_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_templates`
--
ALTER TABLE `ai_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
